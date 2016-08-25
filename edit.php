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
 * Administration settings for a threesixty activity
 *
 * @author  Francois Marier <francois@catalyst.net.nz>
 * @package mod/threesixty
 */

require_once('../../config.php');
require_once('locallib.php');

global $DB, $PAGE;

define('MAX_DESCRIPTION', 255); // ...max number of characters of the description to show in the table.
$strmoveup = get_string('moveup');
$strmovedown = get_string('movedown');
$a = required_param('a', PARAM_INT);  // ...threesixty instance ID.
$move = optional_param('move', 0, PARAM_INT); // Reordering competencies.

if (!$activity = $DB->get_record('threesixty', array('id'=> $a))) {
    print_error('Course module is incorrect');
}
if (!$course = $DB->get_record('course', array('id'=>$activity->course))) {
	print_error('Course is misconfigured');
}
if (!$cm = get_coursemodule_from_instance('threesixty', $activity->id, $course->id)) {
	print_error('Course Module ID was incorrect');
}

$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/threesixty:manage', $context);

$PAGE->set_url('/mod/threesixty/edit.php', array('a' => $a));
$PAGE->set_pagelayout('incourse');


// TODO add_to_log($course->id, 'threesixty', 'admin', "edit.php?a=$activity->id", $activity->id);
if ($move) {
    $c = optional_param('c', 0, PARAM_INT); // The competency id that we're needing to move.
    if (!$competency = $DB->get_record('threesixty_competency', array('id' => $c, 'activityid' => $activity->id))) {
        print_error('Competency id incorrect');
    }
    move_competency($competency, $move);
}

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
$currenttab = 'edit';
$section = 'competencies';
require_once('tabs.php');

echo '<h2>'.get_string('competenciesheading', 'threesixty').'</h2>';

$competencies = threesixty_get_competency_listing($activity->id);

if (count($competencies) > 0) {

    $table = new html_table();

    $table->head = array(get_string('name'), get_string('description'),
                         get_string('skills', 'threesixty'), get_string('feedback', 'threesixty'), '&nbsp;');

    $numcompetencies = count($competencies);
    for ($i=0; $i<$numcompetencies; $i++) {
        $competency = array_shift($competencies);
        $links = '<a href="editcompetency.php?a='.$activity->id.'&amp;c='.$competency->id.'">'.
                get_string('edit', 'threesixty').'</a>';
        $links .= ' | <a href="deletecompetency.php?a='.$activity->id.'&amp;c='.$competency->id.'">'.
                get_string('delete', 'threesixty').'</a>';
        if ($i!=0) {
            $links .= ' | <a href="edit.php?a='.$activity->id.'&amp;c='.$competency->id.'&amp;move=-1" title="'.$strmoveup.'">
                  <img src="'.$OUTPUT->pix_url('/t/up').'" alt="'.$strmoveup.'" /></a>';
        }
        if ($i<$numcompetencies-1) {
            $links .= ' | <a href="edit.php?a='.$activity->id.'&amp;c='.$competency->id.'&amp;move=1" title="'.$strmovedown.'">
                  <img src="'.$OUTPUT->pix_url('/t/down').'" alt="'.$strmovedown.'" /></a>';
        }
        // Shorten the description field.
        $shortdescription = substr($competency->description, 0, MAX_DESCRIPTION);
        if (strlen($shortdescription) < strlen($competency->description)) {
            $shortdescription .= '...';
        }

        $table->data[] = array(format_string($competency->name), format_text($shortdescription),
                               format_string($competency->skills),
                               $competency->showfeedback ? get_string('yes') : get_string('no') , $links);
    }

    // ...print_table($table);.
    echo html_writer::table($table);
} else {
     print_string('nocompetencies', 'threesixty');
}

print '<p><a href="editcompetency.php?a='.$activity->id.'&amp;c=0">'.get_string('addnewcompetency', 'threesixty').'</a></p>';

// ...print_footer($course);.
echo $OUTPUT->footer();

function move_competency($competency, $moveto) {
    global $DB;
    $currentlocation = $competency->sortorder;
    $newlocation = $currentlocation + $moveto;

    $swapcomp = $DB->get_record('threesixty_competency', array('id' => $competency->id), $fields='sortorder');
    if ($swapcomp) {
        $swapcomp->sortorder = $currentlocation;
        $swapcomp->id = $competency->id;
        $DB->update_record('threesixty_competency', $swapcomp);
    }

    $competency->sortorder = $newlocation;
    $DB->update_record('threesixty_competency', $competency);
}