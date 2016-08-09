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
 * Display a confirmation that the response has been accepted.
 *
 * @author  Francois Marier <francois@catalyst.net.nz>
 * @package mod/threesixty
 */

require_once('../../config.php');
require_once('locallib.php');

$a = required_param('a', PARAM_INT);  // ...threesixty instance ID.

if (!$activity = $DB->get_record('threesixty', array('id' => $a))) {
    error('Course module is incorrect');
}
if (!$course = $DB->get_record('course', array('id' => $activity->course))) {
    error('Course is misconfigured');
}
if (!$cm = get_coursemodule_from_instance('threesixty', $activity->id, $course->id)) {
    error('Course Module ID was incorrect');
}

// Header.
$strthreesixtys = get_string('modulenameplural', 'threesixty');
$strthreesixty  = get_string('modulename', 'threesixty');

$navlinks = array();
$navlinks[] = array('name' => $strthreesixtys, 'link' => "index.php?id=$course->id", 'type' => 'activity');
$navlinks[] = array('name' => format_string($activity->name), 'link' => '', 'type' => 'activityinstance');

foreach($navlinks as $navlink) {
	/** @var moodle_page $PAGE */
	$PAGE->navbar->add($navlink['name'], new moodle_url($navlink['name']));
}

//TODO: print_header_simple(format_string($activity->name), '', $navigation, '', '', true,
//                    update_module_button($cm->id, $course->id, $strthreesixty), navmenu($course, $cm));

// Main content.
echo $OUTPUT->box((get_string('thankyoumessage', 'threesixty')));

// ...print_footer($course);.
echo $OUTPUT->footer();
