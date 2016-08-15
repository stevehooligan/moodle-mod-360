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
 * Administration settings for selecting which competencies to carry
 * over to the Training Diary.
 *
 * @author  Francois Marier <francois@catalyst.net.nz>
 * @package mod/threesixty
 */

require_once('../../config.php');
require_once('locallib.php');
require_once('carryover_form.php');

global $DB, $PAGE;

define('MAX_DESCRIPTION', 255); // Max number of characters of the description to show in the table.

$a       = required_param('a', PARAM_INT);  // ...threesixty instance ID.
$userid  = optional_param('userid', 0, PARAM_INT);

if (!$activity = $DB->get_record('threesixty', array('id' => $a))) {
    print_error('Course module is incorrect');
}
if (!$course = $DB->get_record('course', array('id' => $activity->course))) {
	print_error('Course is misconfigured');
}
if (!$cm = get_coursemodule_from_instance('threesixty', $activity->id, $course->id)) {
	print_error('Course Module ID was incorrect');
}
$user = null;
if ($userid > 0 and !$user = $DB->get_record('user', array('id' => $userid), 'id, firstname, lastname')) {
	print_error('Invalid User ID');
}

$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/threesixty:manage', $context);

$PAGE->set_url('/mod/threesixty/carryover.php', array('a' => $a));
$PAGE->set_pagelayout('incourse');


$baseurl = "carryover.php?a=$activity->id";

$mform = null;
if (isset($user)) {
    $returnurl = "view.php?a=$activity->id";
    $currenturl = "$baseurl&amp;userid=$user->id";

    if (!$analysis = $DB->get_record('threesixty_analysis', array('activityid' => $activity->id, 'userid' => $user->id))) {
        print_error('error:nodataforuserx', 'threesixty', $returnurl, fullname($user));
    }

    $complist = get_full_competency_list($activity->id);
    $nbcarried = $activity->competenciescarried;

    $mform = new mod_threesixty_carryover_form(null, compact('a', 'userid', 'complist', 'nbcarried'));

    if ($fromform = $mform->get_data()) {
        if ($mform->is_cancelled()) {
            redirect($baseurl);
        }

        if (save_changes($fromform, $analysis->id)) {
            redirect($currenturl);
        } else {
            redirect($currenturl, get_string('error:cannotsavechanges',
                    'threesixty', get_string('error:databaseerror', 'threesixty')));
        }
    }

    // TODO add_to_log($course->id, 'threesixty', 'carryover', $currenturl, $activity->id);
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
$section = 'carryover';
require_once('tabs.php');

if (isset($mform)) {
    print threesixty_selected_user_heading($user, $course->id, $baseurl);
	
	if (isset($analysis)) {
		set_form_data($mform, $analysis->id);
	}
    $mform->display();
} else {
    display_current_user_data($activity, $baseurl);
}

// ...print_footer($course);.
echo $OUTPUT->footer();

function get_full_competency_list($activityid) {
    global $DB;
    $ret = array(0 => get_string('none'));

    $table_skill = '{threesixty_skill}';
    $table_competency = '{threesixty_competency}';
    $sql = "SELECT s.*, c.name as competency FROM ".$table_skill." AS s". 
           " JOIN ".$table_competency." AS c ON s.competencyid = c.id". 
           " WHERE c.activityid = ".$activityid
    ;
    if ($records = $DB->get_records_sql($sql)) {
        foreach ($records as $record) {
            $ret[$record->id] = $record->competency.": ".$record->name;
        }
    }

    return $ret;
}

/**
 * @param $mform mod_threesixty_carryover_form
 * @param $analysisid
 */
function set_form_data($mform, $analysisid) {
    global $DB;

    if (!$carriedcomps = $DB->get_records('threesixty_carried_comp', array('analysisid' => $analysisid), 'id, competencyid')) {
        return; // ...no existing data.
    }

    $toform = array();

    $previousvalues = array();
    $i = 0;
    foreach ($carriedcomps as $carried) {
        // ...$mform->_customdata['nbcarried'] - protected property cannot be accessed directly.
        // See public function getnbcarried in carryover_form.php file.
        // ...if ($i >= $mform->_customdata['nbcarried']) {.
        if ($i >= $mform->getnbcarried()) {
            break;
        }

        $compid = $carried->competencyid;
        if (!empty($previousvalues[$compid])) {
            $i++;
            continue; // ...only add competencies once.
        }
        $previousvalues[$compid] = true;

        $toform["comp$i"] = $compid;
        $i++;
    }

    $mform->set_data($toform);
}

function save_changes($formfields, $analysisid) {
    global $DB;

    if (!empty($formfields->nbcarried)) {
        $transaction = $DB->start_delegated_transaction();

        // Remove existing ones.
        if (!$DB->delete_records('threesixty_carried_comp', array('analysisid' => $analysisid))) {
            $transaction->rollback(new Exception());
            return false;
        }

        // Add all new selected competencies.
        $previousvalues = array();
        for ($i=0; $i < $formfields->nbcarried; $i++) {
            $fieldname = "comp$i";
            if (empty($formfields->$fieldname)) {
                continue; // ...missing from the form data (or set to 'None').
            }

            $compid = (int)$formfields->$fieldname;
            if (!empty($previousvalues[$compid])) {
                continue; // ...only add competencies once.
            }
            $previousvalues[$compid] = true;

            $record = new object();
            $record->analysisid = $analysisid;
            $record->competencyid = $compid;

            if (!$DB->insert_record('threesixty_carried_comp', $record)) {
                $transaction->rollback(new Exception());
                return false;
            }
        }

        $transaction->allow_commit();
    }

    return true;
}
function display_current_user_data($activity, $url) {
    global $CFG, $DB;

    $table = new html_table();
    $table->head = array('User');
    $nbcarried = $activity->competenciescarried;
    for ($i=1; $i<=$nbcarried; $i++) {
        $table->head[] = 'Skill '.$i;
    }
    $table->head[] = 'Options';

    $users = threesixty_users($activity);
    if ($users) {
        foreach ($users as $user) {
            $data = array("<a href=".$CFG->wwwroot.
                "/user/view.php?id={$user->id}&course={$activity->course}>".
                format_string($user->firstname." ".$user->lastname)."</a>");
            $sql = "SELECT c.name AS competency
                    FROM {threesixty_analysis} a
                    JOIN {threesixty_carried_comp} cc ON a.id = cc.analysisid
                    JOIN {threesixty_skill} c ON cc.competencyid = c.id
                    WHERE a.userid = {$user->id} and a.activityid = {$activity->id}";
            $carriedcomps = $DB->get_records_sql($sql);
            $missingcells = $nbcarried;
            if ($carriedcomps) {
                foreach ($carriedcomps as $comp) {
                    $data[] = $comp->competency;
                    $missingcells--;
                }
            }
            if ($missingcells) {
                for ($i=0; $i<$missingcells; $i++) {
                    $data[] = "&nbsp;";
                }
            }
            $data[] = "<a href=\"$url&amp;userid=$user->id\">Edit</a>";
            $table->data[] = $data;
        }
        // ...print_table($table);.
        echo html_writer::table($table);
    } else {
        print "No users to display";
    }
}