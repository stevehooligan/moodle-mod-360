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
 * This page prints a particular instance of threesixty by redirecting
 * to the most appropriate page based on the user's capabilities.
 *
 * @author  Francois Marier <francois@catalyst.net.nz>
 * @package mod/threesixty
 */

require_once('../../config.php');

global $DB, $CFG;

$id     = optional_param('id', 0, PARAM_INT); // ...course_module ID, or.
$a      = optional_param('a', 0, PARAM_INT);  // ...threesixty instance ID.
$userid = optional_param('userid', 0, PARAM_INT);

if ($id) {
    if (!$cm = get_coursemodule_from_id('threesixty', $id)) {
        print_error('Course Module ID was incorrect');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course) )) {
	    print_error('Course is misconfigured');
    }
    if (!$activity = $DB->get_record('threesixty', array('id' => $cm->instance) )) {
	    print_error('Course module is incorrect');
    }
} else if ($a) {
    if (!$activity = $DB->get_record('threesixty', array('id' => $a))) {
	    print_error('Course module is incorrect');
    }
    if (!$course = $DB->get_record('course', array('id' => $activity->course))) {
	    print_error('Course is misconfigured');
    }
    if (!$cm = get_coursemodule_from_instance('threesixty', $activity->id, $course->id)) {
	    print_error('Course Module ID was incorrect');
    }
} else {
	print_error('You must specify a course_module ID or an instance ID');
}

if(isset($cm) && isset($course) && isset($activity)) {
	$context = context_module::instance($cm->id);
	
	require_login($course, true, $cm);
	require_capability('mod/threesixty:view', $context);
	
	if (has_capability('mod/threesixty:viewreports', $context)) {
		redirect("$CFG->wwwroot/mod/threesixty/report.php?a=$activity->id&amp;userid=$userid");
	}
	
	/* ...if ($analysis = $DB->get_record('threesixty_analysis', 'activityid', $activity->id, 'userid', $USER->id)) {
		...if ($response = $DB->get_record('threesixty_response', 'analysisid', $analysis->id, 'uniquehash', null, 'typeid', 0)) {
			...if ($response->timecompleted > 0) {.
				// Activity is finished/completed.
				...redirect("$CFG->wwwroot/mod/threesixty/respondents.php?a=$activity->id");.
			...}.
		...}.
	...}. */

// ...redirect("$CFG->wwwroot/mod/threesixty/score.php?a=".$activity->id."&typeid=0");.
	redirect("$CFG->wwwroot/mod/threesixty/profiles.php?a=$activity->id");
}
