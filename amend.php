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
 * Allows a teacher/admin to edit the scores entered by a student
 *
 * @author  Francois Marier <francois@catalyst.net.nz>
 * @package mod/threesixty
 */

require_once('../../config.php');
require_once('amend_form.php');
require_once('locallib.php');

global $DB, $PAGE;

$a      = required_param('a', PARAM_INT);  // ...threesixty instance ID.
$typeid = required_param('typeid', PARAM_INT); // ...the type of the response.
$userid = optional_param('userid', 0, PARAM_INT);

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
if ($userid > 0 and !$user = $DB->get_record('user',
    array('id' => $userid), $fields='id, firstname, lastname')) {
	print_error('Invalid User ID');
}

$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/threesixty:view', $context);
require_capability('mod/threesixty:edit', $context);

$PAGE->set_url('/mod/threesixty/amend.php', array('a' => $a));
$PAGE->set_pagelayout('incourse');


$baseurl = "amend.php?a=$activity->id&typeid=$typeid";

$mform = null;
$usertable = null;
if (isset($user)) {

    $currenturl = "$baseurl&amp;userid=$user->id";
    $returnurl = "view.php?a=$activity->id";

    $skillnames = threesixty_get_skill_names($activity->id);

    if (!$analysis = $DB->get_record('threesixty_analysis',
        array('activityid' => $activity->id, 'userid' => $user->id))) {
        print_error('error:nodataforuserx', 'threesixty', $returnurl, fullname($user));
    }
    if (!$respondent = $DB->get_record('threesixty_respondent',
        array('analysisid' => $analysis->id, 'type' => $typeid), 'id, uniquehash')) {
        print_error('error:nodataforuserx', 'threesixty', $returnurl, fullname($user));
    }
    if (!$response = $DB->get_record('threesixty_response',
        array('analysisid' => $analysis->id, 'respondentid' => $respondent->id))) {
        print_error('error:nodataforuserx', 'threesixty', $returnurl, fullname($user));
    }
    if (!$response->timecompleted) {
        print_error('error:userxhasnotsubmitted', 'threesixty', $returnurl, fullname($user));
    }
    if (!$selfscores = threesixty_get_self_scores($analysis->id, false, $typeid)) {
        print_error('error:nodataforuserx', 'threesixty', $returnurl, fullname($user));
    }

    $mform = new mod_threesity_amend_form(null, compact('a', 'skillnames', 'userid', 'typeid'));

    if ($mform->is_cancelled()) {
        redirect($baseurl);
    }

    if ($fromform = $mform->get_data()) {

        $returnurl .= "&amp;userid=$user->id";

        if (!empty($fromform->submitbutton)) {
            $errormsg = save_changes($fromform, $response->id, $skillnames);
            if (!empty($errormsg)) {
                print_error('error:cannotsavescores', 'threesixty', $currenturl, $errormsg);
            }

            redirect($returnurl);
        } else {
            print_error('error:unknownbuttonclicked', 'threesixty', $returnurl);
        }
    }

    // TODO add_to_log($course->id, 'threesixty', 'amend', $currenturl, $activity->id);
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

// ...Main content.
$currenttab = 'activity';
$section = 'scores';
require_once('tabs.php');

if (isset($mform)) {
    print threesixty_selected_user_heading($user, $course->id, 'profiles.php?a='.$activity->id);
	
	if(isset($selfscores)) {
		set_form_data($mform, $selfscores);
	}
    $mform->display();
} else {
    print threesixty_user_listing($activity, $baseurl);
}

echo $OUTPUT->footer();

/**
 * @param $mform moodleform
 * @param $scores
 */
function set_form_data($mform, $scores) {
    $toform = array();

    if (!empty($scores->records) and count($scores->records) > 0) {
        foreach ($scores->records as $score) {
            $toform["radioarray_{$score->id}[score_{$score->id}]"] = $score->score;
        }
    }

    $mform->set_data($toform);
}

function save_changes($formfields, $responseid, $skills) {
    global $DB;

    foreach ($skills as $skill) {
        $arrayname = "radioarray_$skill->id";
        if (empty($formfields->$arrayname)) {
            return get_string('error:formsubmissionerror', 'threesixty');
        }
        $a = $formfields->$arrayname;

        $scorename = "score_$skill->id";
        if (!isset($a[$scorename])) {
            return get_string('error:formsubmissionerror', 'threesixty');
        } else {
            $scorevalue = $a[$scorename];
        }

        // ...Save this skill score in the database!!!
        if ($score = $DB->get_record('threesixty_response_skill', array('responseid' => $responseid, 'skillid' => $skill->id))) {
            $newscore = new object();
            $newscore->id = $score->id;
            $newscore->score = $scorevalue;

            if (!$DB->update_record('threesixty_response_skill', $newscore)) {
                return get_string('error:databaseerror', 'threesixty');
            }
        } else {
            // ...return get_string('error:databaseerror', 'threesixty');.
            $newscore = new object();
            $newscore->skillid = $skill->id;
            $newscore->score = $scorevalue;
            $newscore->responseid = $responseid;
            if (!$DB->insert_record('threesixty_response_skill', $newscore)) {
                return get_string('error:databaseerror', 'threesixty');
            }
        }
    }

    return '';
}
