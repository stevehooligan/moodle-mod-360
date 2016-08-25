<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
require_once("../../config.php");
require_once("locallib.php");

// The colours the lines will be drawn in.
$linecolours = array("0xFFCC00", "0x66CC00", "0xCC66FF", "0x3366FF", "0xFF3399", "0x336600", "0x66FFFF",
    "0xFF0000", "0x990033", "0x0000FF", "0x999966", "0x99FF00");

// Process params.
$analysisid = required_param("analysisid", PARAM_INT);
$activityid = required_param("activityid", PARAM_INT);
$filter = required_param("filter", PARAM_ALPHANUM);

// Setup respondent types and colours.
$respondenttypes = explode("\n", get_config(null, 'threesixty_respondenttypes'));
$selfresponsetypes = explode("\n", get_config(null, 'threesixty_selftypes'));
$linecolour_index = 0;
$linecolour_by_filter = array();
// ...$linecolour_by_filter["self"] = $linecolours[$linecolour_index++];.
if (!empty($selfresponsetypes)) {
    foreach ($selfresponsetypes as $key => $value) {
        $linecolour_by_filter["self$key"] = $linecolours[$linecolour_index++];
    }
}
if (!empty($respondenttypes)) {
    foreach ($respondenttypes as $key => $value) {
        $linecolour_by_filter["type$key"] = $linecolours[$linecolour_index++];
    }
}
$linecolour_by_filter["average"] = $linecolours[$linecolour_index++];

// Work out the scores depending on the requested filter.
$score = null;
if (strpos($filter, "self") === 0) {
    $typeid = substr($filter, 4);
    $score = threesixty_get_self_scores($analysisid, false, $typeid);
} else if ($filter === "average") {
    $score = threesixty_get_average_skill_scores($analysisid, false, false);
} else if (strpos($filter, "type") === 0) {
    $typeid = substr($filter, 4);
    $score = threesixty_get_average_skill_scores($analysisid, $typeid, false);
}

// Write out scores for Flash to render.
$s = "";
if ($score !== null) {
    $s .= "result=success";
    $s .= "&name=";
    $s .= preg_replace('/[\r\n]/', '', $score->name);
    $s .= "&colour=";
    $s .= $linecolour_by_filter[$filter];

    // Ensure the skills are displayed by Flash in the same order as the results in the query.
    $skills = threesixty_get_skill_names($activityid);
    $ordinal = 0;
    foreach ($skills as $skill) {
        $s .= "&skill_";
        $s .= urlencode($skill->competencyname);
        $s .= "_";
        $s .= urlencode($skill->skillname) . "_";
        $s .= $ordinal . "=";
        if (empty($score->records[$skill->id]) || !$score->records[$skill->id]->score) {
            $s .= "0";
        } else {
            $s .= round($score->records[$skill->id]->score);
        }
        ++$ordinal;
    }
} else {
        $s .= "result=error";
}
echo $s;
