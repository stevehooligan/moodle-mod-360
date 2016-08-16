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

/**
 * @param int $oldversion
 * @return bool
 */
function xmldb_threesixty_upgrade($oldversion = 0) {

    // global $CFG, $THEME, $DB;

    $result = true;
    if ($result && $oldversion < 2012102301) {
        // TODO Not sure any of this function will work, there are various problems with it.  Problem lines are marked with @noinspection comments
        
        // Add a display order column for the competency table.
        $comptable = new xmldb_table('threesixty_competency');
        $field = new xmldb_field('sortorder');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    if (!add_field($comptable, $field)) {
            $result = false;
        }
        reorder_competencies();
        $skilltable = new xmldb_table('threesixty_skill');
        /** @noinspection PhpParamsInspection */
        $field->setPrevious('description');
        // was: $field->previous = 'description';
	    /** @noinspection PhpUndefinedFunctionInspection */
	    if (!add_field($skilltable, $field)) {
            $result = false;
        }
        // Update the existing competency data.
        reorder_skills();
    }

    return $result;
}

function reorder_competencies() {
    global /*$CFG,*/ $DB;
    // ...$sql = "SELECT * FROM ".$CFG->prefix."threesixty_competency ORDER BY activityid, id";.
    if ($competencies = $DB->get_records("threesixty_competency", '', "activityid")) {
        $activityid = 0;
        $nextposition = 0;
        foreach ($competencies as $competency) {
            if ($activityid != $competency->activityid) {
                $nextposition = 0;
                $activityid = $competency->activityid;
            }
            $competency->sortorder = $nextposition;
            $nextposition++;
            $DB->update_record("threesixty_competency", $competency);
        }
    }
}

function reorder_skills() {
    global /*$CFG,*/ $DB;
    // ...$sql = "SELECT * FROM ".$CFG->prefix."threesixty_skill ORDER BY competencyid, id";.
    if ($skills = $DB->get_records('threesixty_skill', '', 'competencyid')) {
        $competencyid = 0;
        $nextposition = 0;
        foreach ($skills as $skill) {

            if ($competencyid != $skill->competencyid) {
                $nextposition = 0;
                $competencyid = $skill->competencyid;
            }
            $skill->sortorder = $nextposition;
            $nextposition++;
            $DB->update_record("threesixty_skill", $skill);
        }
    }
}