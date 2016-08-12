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
 * Process a click from a user claiming that the user code did not
 * pick up the right email address. This will delete that respondent
 * and the associated response record if any.
 *
 * @author  Francois Marier <francois@catalyst.net.nz>
 * @package mod/threesixty
 */

require_once('../../config.php');
require_once('locallib.php');

global $DB;

$code = required_param('code', PARAM_ALPHANUM); // Unique hash.

if (!$respondent = $DB->get_record('threesixty_respondent', 'uniquehash', $code)) {
    print_error('error:invalidcode', 'threesixty');
}
if (!$analysis = $DB->get_record('threesixty_analysis', 'id', $respondent->analysisid)) {
	print_error('Analysis ID is incorrect');
}
if (!$activity = $DB->get_record('threesixty', 'id', $analysis->activityid)) {
	print_error('Course module is incorrect');
}
if (!$course = $DB->get_record('course', 'id', $activity->course)) {
	print_error('Course is misconfigured');
}
if (!$cm = get_coursemodule_from_instance('threesixty', $activity->id, $course->id)) {
	print_error('Course Module ID was incorrect');
}

// TODO add_to_log($course->id, 'threesixty', 'wrongemail', "wrongemail.php?code=$code", $activity->id);

/** @var core_renderer $OUTPUT */
echo $OUTPUT->header();

$strthreesixtys = get_string('modulenameplural', 'threesixty');
$strthreesixty  = get_string('modulename', 'threesixty');

$navlinks = array();
$navlinks[] = array('name' => $strthreesixtys, 'link' => "index.php?id=$course->id", 'type' => 'activity');
$navlinks[] = array('name' => format_string($activity->name), 'link' => '', 'type' => 'activityinstance');

shim::build_navigation($navlinks);

/** @var moodle_page $PAGE */
$PAGE->set_title(format_string($activity->name));

// Main content.
if (threesixty_delete_respondent($respondent->id)) {
    // TODO add_to_log($course->id, $cm->id, 'delete');
    // ...error_log("threesixty: user claims the response code doesn't match their email address.
    // ...-- deleted $respondent->email from (analysisid=$analysis->id)");.
}
// ...else {.
    // ...error_log("threesixty: user claims the response code doesn't match their email address.
    // ...-- could not delete $respondent->email (analysisid=$analysis->id)");.
// }.

echo $OUTPUT->box(get_string('adminnotified', 'threesixty'));

// ...print_footer($course);.
echo $OUTPUT->footer();
