<?php

/**
 * This page lists all the instances of threesixty in a particular course
 *
 * @author  Francois Marier <francois@catalyst.net.nz>
 * @package mod/threesixty
 */

require_once '../../config.php';
require_once 'lib.php';

$id = required_param('id', PARAM_INT);   // course

if (! $course = $DB->get_record('course', array('id' => $id))) {
    error('Course ID is incorrect');
}

//require_course_login($course);
require_login($course);

$PAGE->set_url('/mod/threesixty/index.php', array('id' => $id));
$PAGE->set_pagelayout('incourse');


add_to_log($course->id, 'threesixty', 'view all', "index.php?id=$course->id", '');


/// Get all required stringsthreesixty

$strthreesixtys = get_string('modulenameplural', 'threesixty');
$strthreesixty  = get_string('modulename', 'threesixty');


/// Print the header

$navlinks = array();
$navlinks[] = array('name' => $strthreesixtys, 'link' => '', 'type' => 'activity');
$navigation = build_navigation($navlinks);

print_header_simple($strthreesixtys, '', $navigation, '', '', true, '', navmenu($course));

/// Get all the appropriate data

if (! $threesixtys = get_all_instances_in_course('threesixty', $course)) {
    notice('There are no instances of threesixty', "../../course/view.php?id=$course->id");
    die;
}

/// Print the list of instances (your module will probably extend this)

$timenow  = time();
$strname  = get_string('name');
$strweek  = get_string('week');
$strtopic = get_string('topic');

if ($course->format == 'weeks') {
    $table = new html_table();
    $table->head  = array ($strweek, $strname);
    $table->align = array ('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ('center', 'left', 'left', 'left');
} else {
    $table->head  = array ($strname);
    $table->align = array ('left', 'left', 'left');
}

foreach ($threesixtys as $threesixty) {
    if (!$threesixty->visible) {
        //Show dimmed if the mod is hidden
        $link = '<a class="dimmed" href="view.php?id='.$threesixty->coursemodule.'">'.format_string($threesixty->name).'</a>';
    } else {
        //Show normal if the mod is visible
        $link = '<a href="view.php?id='.$threesixty->coursemodule.'">'.format_string($threesixty->name).'</a>';
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array ($threesixty->section, $link);
    } else {
        $table->data[] = array ($link);
    }
}

echo $OUTPUT->heading($strthreesixtys);
html_writer::table($table);

/// Finish the page
echo $OUTPUT->footer();
