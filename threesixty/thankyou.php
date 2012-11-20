<?php

/**
 * Display a confirmation that the response has been accepted.
 *
 * @author  Francois Marier <francois@catalyst.net.nz>
 * @package mod/threesixty
 */

require_once '../../config.php';

$a = required_param('a', PARAM_INT);  // threesixty instance ID

if (!$activity = $DB->get_record('threesixty', array('id' => $a))) {
    error('Course module is incorrect');
}
if (!$course = $DB->get_record('course', array('id' => $activity->course))) {
    error('Course is misconfigured');
}
if (!$cm = get_coursemodule_from_instance('threesixty', $activity->id, $course->id)) {
    error('Course Module ID was incorrect');
}

// Header
$strthreesixtys = get_string('modulenameplural', 'threesixty');
$strthreesixty  = get_string('modulename', 'threesixty');

$navlinks = array();
$navlinks[] = array('name' => $strthreesixtys, 'link' => "index.php?id=$course->id", 'type' => 'activity');
$navlinks[] = array('name' => format_string($activity->name), 'link' => '', 'type' => 'activityinstance');

$navigation = build_navigation($navlinks);

print_header_simple(format_string($activity->name), '', $navigation, '', '', true,
                    update_module_button($cm->id, $course->id, $strthreesixty), navmenu($course, $cm));

// Main content
echo $OUTPUT->box((get_string('thankyoumessage', 'threesixty')));

//print_footer($course);
echo $OUTPUT->footer();
