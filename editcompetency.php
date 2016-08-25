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

require_once('../../config.php');
require_once('editcompetency_form.php');
require_once('locallib.php');

global $DB, $PAGE;

$a = required_param('a', PARAM_INT); // ...threesixty instance id.
$c = optional_param('c', 0, PARAM_INT); // ...competency id.

if (!$activity = $DB->get_record('threesixty', array('id' => $a) )) {
    print_error('Activity instance is incorrect: '. $a);
}
if (!$course = $DB->get_record('course', array('id' => $activity->course) )) {
    print_error('Course is misconfigured');
}
if (!$cm = get_coursemodule_from_instance('threesixty', $activity->id, $course->id)) {
    print_error('Course Module ID was incorrect');
}

$competency = null;
$skills = null;
if ($c > 0) {
    if (!$competency = $DB->get_record('threesixty_competency', array('id' => $c))) {
        print_error('Competency ID is incorrect');
    }
    $skills = $DB->get_records('threesixty_skill', array('competencyid' => $competency->id), 'sortorder');
}

$context = context_module::instance($cm->id);

require_login($course->id, false, $cm);
require_capability('mod/threesixty:manage', $context);

$PAGE->set_url('/mod/threesixty/editcompetency.php', array('a' => $a));
$PAGE->set_pagelayout('incourse');


$returnurl = "edit.php?a=$activity->id&amp;section=competencies";

$mform = new mod_threesixty_editcompetency_form(null, compact('a', 'c', 'skills'));

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) { // Form submitted.

    if (empty($fromform->submitbutton)) {
        print_error('error:unknownbuttonclicked', 'threesixty', $returnurl);
    }

    if (!isset($fromform->showfeedback) ) {
        $fromform->showfeedback = 0;
    }
    $todb = new object();
    $todb->activityid = $activity->id;
    $todb->name = trim($fromform->name);
    $todb->description = trim($fromform->description);
    $todb->showfeedback = $fromform->showfeedback;

    $originurl = null;
    $competencyid = null;

    $transaction = $DB->start_delegated_transaction();

    // General.
    if ($competency != null) {

        $competencyid = $competency->id;
        $originurl = "editcompetency.php?a=$activity->id&amp;c=$competencyid";

        $todb->id = $competencyid;
        if ($DB->update_record('threesixty_competency', $todb)) {
            // TODO add_to_log($course->id, 'threesixty', 'update competency',
            // ....          $originurl, $activity->id, $cm->id);
        } else {
            $transaction->rollback(new Exception());
            print_error('error:cannotupdatecompetency', 'threesixty', $returnurl);
        }
    } else {
        $originurl = "editcompetency.php?a=" . $activity->id . "&amp;c=0";
        // Set the sortorder to the end of the line.
        $todb->sortorder = $DB->count_records('threesixty_competency', array('activityid' => $activity->id));
        if ($competencyid = $DB->insert_record('threesixty_competency', $todb, true)) {
            // TODO add_to_log($course->id, 'threesixty', 'add competency',
            // ....          $originurl, $activity->id, $cm->id);
        } else {
            $transaction->rollback(new Exception());
            print_error('error:cannotaddcompetency', 'threesixty', $returnurl);
        }
    }

    // Skills.
    for ($i = 0; $i < $fromform->skill_repeats; $i++) {

        $skillid = $fromform->skillid[$i];
        $skillname = '';
        if (!empty($fromform->skillname[$i])) {
            $skillname = $fromform->skillname[$i];
        }
        $skilldescription = '';
        if (!empty($fromform->skilldescription[$i])) {
            $skilldescription = $fromform->skilldescription[$i];
        }
        $skilldelete = false;
        if (!empty($fromform->skilldelete[$i])) {
            $skilldelete = (1 == $fromform->skilldelete[$i]);
        }

        if ($skillid > 0) { // Existing skill.

            if (!empty($fromform->skilldelete[$i])) { // Delete.
                if (threesixty_delete_skill($skillid, true)) {
                    // TODO add_to_log($course->id, 'threesixty', 'delete skill',
                    // ....           $originurl, $activity->id, $cm->id);
                } else {
                    $transaction->rollback(new Exception());
                    print_error('error:cannotdeleteskill', 'threesixty', $returnurl);
                }
            } else if (!empty($skillname)) { // Update.
                $todb = new object;
                $todb->id = $skillid;
                $todb->name = $skillname;
                $todb->description = $skilldescription;

                if ($DB->update_record('threesixty_skill', $todb)) {
                    // TODO add_to_log($course->id, 'threesixty', 'update skill',
                    // ....           $originurl, $activity->id, $cm->id);
                } else {
                    $transaction->rollback(new Exception());
                    print_error('error:cannotupdateskill', 'threesixty', $returnurl);
                }
            }
        } else if (!$skilldelete and !empty($skillname)) { // Insert.
            $todb = new object;
            $todb->competencyid = $competencyid;
            $todb->name = $skillname;
            $todb->description = $skilldescription;
            $todb->sortorder = $i;

            if ($todb->id = $DB->insert_record('threesixty_skill', $todb)) {
                // TODO add_to_log($course->id, 'threesixty', 'insert skill',
                // ....           $originurl, $activity->id, $cm->id);
            } else {
                // TODO moodle_rollback_sql();
                print_error('error:cannotaddskill', 'threesixty', $returnurl);
            }
        }
    }

    $transaction->allow_commit();
    redirect($returnurl);
} else if ($competency != null) { // Edit mode.

    // Set values for the form.
    $toform = new object();
    $toform->name = $competency->name;
    $toform->description = $competency->description;
    $toform->showfeedback = ($competency->showfeedback == 1);

    if ($skills) {
        $i = 0;
        foreach ($skills as $skill) {
            $idfield = "skillid[$i]";
            $namefield = "skillname[$i]";
            $descriptionfield = "skilldescription[$i]";
            $sortorderfield = "skillsortorder[$i]";
            $toform->$idfield = $skill->id;
            $toform->$namefield = $skill->name;
            $toform->$descriptionfield = $skill->description;
            $toform->$sortorderfield = $skill->sortorder;
            $i++;
        }
    }
    $mform->set_data($toform);
}

/** @var core_renderer $OUTPUT */
echo $OUTPUT->header();

$strthreesixtys = get_string('modulenameplural', 'threesixty');
$strthreesixty  = get_string('modulename', 'threesixty');

$navlinks = array();
$navlinks[] = array('name' => $strthreesixtys, 'link' => "index.php?id=$course->id", 'type' => 'activity');
$navlinks[] = array('name' => format_string($activity->name), 'link' => $returnurl, 'type' => 'activityinstance');

$title = get_string('addnewcompetency', 'threesixty');

if ($competency != null) {
    $title = $competency->name;
}

$navlinks[] = array('name' => format_string($title), 'link' => '', 'type' => 'activityinstance');

shim::build_navigation($navlinks);

/** @var moodle_page $PAGE */
$PAGE->set_title(format_string($activity->name." - ".$title));

require_once('tabs.php');
$mform->display();

// ...print_footer($course);.
echo $OUTPUT->footer();
