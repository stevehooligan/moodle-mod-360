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
 * Allows a student to assess their skills accross competencies
 *
 * @author  Francois Marier <francois@catalyst.net.nz>
 * @package mod/threesixty
 */

require_once('../../config.php');
require_once('locallib.php');
require_once('respondents_form.php');

global $CFG, $DB, $PAGE, $USER;

define('RESPONSE_BASEURL', "$CFG->wwwroot/mod/threesixty/score.php?code=");

$a       = required_param('a', PARAM_INT);  // ...threesixty instance ID.
$userid  = optional_param('userid', 0, PARAM_INT);
$delete  = optional_param('delete', 0, PARAM_INT);
$remind  = optional_param('remind', 0, PARAM_INT);

if (!$activity = $DB->get_record('threesixty', array('id' => $a))) {
    print_error('Course module is incorrect');
}
if (!$course = $DB->get_record('course', array('id' => $activity->course))) {
    print_error('Course is misconfigured');
}
if (!$cm = get_coursemodule_from_instance('threesixty', $activity->id, $course->id)) {
    print_error('Course Module ID was incorrect');
}

$context = context_module::instance($cm->id);

require_login($course, true, $cm);

$PAGE->set_url('/mod/threesixty/respondents.php', array('a' => $a));
$PAGE->set_pagelayout('incourse');

if (!has_capability('mod/threesixty:viewrespondents', $context)) {
    require_capability('mod/threesixty:participate', $context);
    $userid = $USER->id; // ...force same user.
}

$user = null;
if ($userid > 0 and !$user = $DB->get_record('user', array('id' => $userid), $fields='id, firstname, lastname')) {
    print_error('Invalid User ID');
}

$baseurl = "respondents.php?a=$activity->id";

$mform = null;
if (isset($user)) {

    // Make sure the form has been submitted by the student.
    $returnurl = "view.php?a=$activity->id";
    if (!$analysis = $DB->get_record('threesixty_analysis', array('activityid' => $activity->id, 'userid' => $user->id))) {
        print_error('error:noscoresyet', 'threesixty', $returnurl);
    }

    $currenturl = "$baseurl&amp;userid=$user->id";

    // Handle manual (non-formslib) actions.
    if ($remind > 0) {
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', 'error', $currenturl);
        }

        if (!send_reminder($remind, fullname($user))) {
            print_error('error:cannotsendreminder', 'threesixty', $currenturl);
        }
        redirect($currenturl);
    }
    if ($delete > 0) {
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', 'error', $currenturl);
        }

        if (!threesixty_delete_respondent($delete)) {
            print_error('error:cannotdeleterespondent', 'threesixty', $currenturl);
        }
        redirect($currenturl);
    }

    $typelist = array();
    $i = 0;
    foreach (explode("\n", get_config(null, 'threesixty_respondenttypes')) as $type) {
        $t = trim($type);
        if (!empty($t)) {
            $typelist[$i] = $t;
            $i++;
        }
    }
    if (empty($typelist)) {
        $typelist = array(0 => get_string('none'));
    }
    $table_respondent = '{threesixty_respondent}';
    $currentinvitations = $DB->count_records_sql(
        "SELECT COUNT(1) FROM ".$table_respondent.
        " WHERE analysisid = ".$analysis->id." AND uniquehash IS NOT NULL"
    );
    $remaininginvitations = $activity->requiredrespondents - $currentinvitations;

    $analysisid = $analysis->id;

    $mform = new mod_threesity_respondents_form(null, compact('a', 'analysisid', 'userid', 'typelist', 'remaininginvitations'));

    if ($fromform = $mform->get_data()) {

        if (!request_respondent($fromform, $analysis->id, fullname($user))) {
            print_error('error:cannotinviterespondent', 'threesixty', $currenturl);
        }
        redirect($currenturl);
    }

    // TODO add_to_log($course->id, 'threesixty', 'respondents', $currenturl, $activity->id);
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
$currenttab = 'respondents';
$section = null;

require_once('tabs.php');

if (isset($mform)) {
    if ($USER->id != $userid) {
        // ...echo "<pre>";.
        print threesixty_selected_user_heading($user, $course->id, $baseurl);
        // ...var_dump(threesixty_selected_user_heading($user, $course->id, $baseurl)); die();.
    }

    $remaininginvitations = isset($remaininginvitations) ? $remaininginvitations : 0;
    if ($remaininginvitations > 0) {
        $mform->display();
    }

    $canremind = has_capability('mod/threesixty:remindrespondents', $context);
    $candelete = has_capability('mod/threesixty:deleterespondents', $context);
	$analysis = isset($analysis) ? $analysis : array('id'=>-1);
    print_respondent_table($activity->id, $analysis->id, $user->id, $canremind, $candelete);
} else {
    // ....print threesixty_user_listing($activity, $baseurl);.
    print print_participants_listing($activity, $baseurl);
}

echo $OUTPUT->footer();

function print_participants_listing($activity, $baseurl) {
    global $CFG;

    if ($users = threesixty_users($activity)) {
        $table = new html_table();
        $table->head = array(get_string('name'), get_string('numberrespondents', 'threesixty'));
        $table->head[] = get_string('self:responseoptions', 'threesixty');
        $table->data = array();
        foreach ($users as $user) {
            $name = format_string(fullname($user));
            $userurl = "<a href=".$CFG->wwwroot."/user/view.php?id={$user->id}&course={$activity->course}>".$name."</a>";
            $selectlink = "<a href=\"$baseurl&amp;userid=$user->id\">View</a>";

            $numrespondents = count_respondents($user->id, $activity->id);
            $table->data[] = array($userurl, $numrespondents, $selectlink);
        }
        return get_string('selectuser', 'threesixty').html_writer::table($table);
    } else {
        return get_string('nousersfound', 'threesixty');
    }
}

function generate_uniquehash($email) {
    $timestamp = time();
    $salt = mt_rand();
    return sha1("$salt $email $timestamp");
}

function send_email($recipientemail, $messageid, $extrainfo) {
    // Fake user object necessary for email_to_user().
    $user = new object();
    $user->id = 0; // ...required for bounce handling and get_user_preferences().
    $user->email = $recipientemail;

    $a = new object();
    $a->url = $extrainfo['url'];
    $a->userfullname = $extrainfo['userfullname'];

    $from = $extrainfo['userfullname'];
    $subject = get_string("email:{$messageid}subject", 'threesixty', $a);
    $messagetext = get_string("email:{$messageid}body", 'threesixty', $a);

    return email_to_user($user, $from, $subject, $messagetext);
}

function request_respondent($formfields, $analysisid, $senderfullname) {
    global $DB;
    $respondent = new object();
    $respondent->analysisid = $analysisid;
    $respondent->email = strtolower($formfields->email);
    $respondent->type = (int)$formfields->type;
    $respondent->uniquehash = generate_uniquehash($formfields->email);

    $extrainfo = array('url' => RESPONSE_BASEURL . $respondent->uniquehash,
                       'userfullname' => $senderfullname);
    if (!send_email($respondent->email, 'request', $extrainfo)) {
        return false;
    }

    if (!$respondent->id = $DB->insert_record('threesixty_respondent', $respondent)) {
        return false;
    }

    return true;
}

function send_reminder($respondentid, $senderfullname) {
    global $DB;
    if (!$respondent = $DB->get_record('threesixty_respondent', array('id'=> $respondentid))) {
        return false;
    }

    $extrainfo = array('url' => RESPONSE_BASEURL. $respondent->uniquehash,
                       'userfullname' => $senderfullname);
    if (!send_email($respondent->email, 'reminder', $extrainfo)) {
        return false;
    }

    return true;
}

function print_respondent_table($activityid, $analysisid, $userid, $canremind=false, $candelete=false) {
    global $typelist, $USER, $OUTPUT;

    $respondents = threesixty_get_external_respondents($analysisid);
    if ($respondents) {
        $table = new html_table();
        $table->head = array(get_string('email'), get_string('respondenttype', 'threesixty'),
                             get_string('completiondate', 'threesixty'));
        if ($candelete or $canremind) {
            $table->head[] = '&nbsp;';
        }
        $table->data = array();

        foreach ($respondents as $respondent) {
            $data = array();
            $data[] = format_string($respondent->email);

            if (empty($typelist[$respondent->type])) {
                $data[] = get_string('unknown');
            } else {
                $data[] = $typelist[$respondent->type];
            }

            if (empty($respondent->timecompleted)) {
                $data[] = get_string('none');
            } else {
                $data[] = userdate($respondent->timecompleted, get_string('strftimedate'));
            }

            // Action buttons.
            $buttons = '';
            if ($canremind and empty($respondent->timecompleted)) {
                $options = array('a' => $activityid, 'remind' => $respondent->id,
                                 'userid' => $userid, 'sesskey' => $USER->sesskey);
                $url = new moodle_url("respondents.php", $options);
                $buttons .=  $OUTPUT->single_button($url, get_string('remindbutton', 'threesixty'), 'post', array('_self'=>true));
            }
            if ($candelete) {
                $options = array('a' => $activityid, 'delete' => $respondent->id,
                                 'userid' => $userid, 'sesskey' => $USER->sesskey);
                $url = new moodle_url("respondents.php", $options);
                $buttons .= $OUTPUT->single_button($url, get_string('delete'), 'post', array('_self'=>true));
            }
            if (!empty($buttons)) {
                $data[] = $buttons;
            }

            $table->data[] = $data;
        }
        // ...print_table($table);.
        echo html_writer::table($table);
    } else {
        // ...print_box_start();.
        echo $OUTPUT->box_start();
        echo "No respondents have been entered yet.";
        // ...print_box_end();.
        echo $OUTPUT->box_end();
    }
}

function threesixty_get_external_respondents($analysisid) {
    global $DB;

    $table_respondent = '{threesixty_respondent}';
    $table_response = '{threesixty_response}';
    $sql = "SELECT rt.id, rt.email, rt.type, re.timecompleted FROM ".$table_respondent." AS rt".
           " LEFT OUTER JOIN ".$table_response." AS re ON re.respondentid = rt.id".
           " WHERE rt.analysisid = ".$analysisid.
           " AND rt.uniquehash IS NOT NULL".
           " ORDER BY rt.email"
    ;

    $respondents = $DB->get_records_sql($sql);

    return $respondents;
}
function count_respondents($userid, $activityid) {
    global $DB;
	$table_respondent = '{threesixty_respondent}';
	$table_analysis = '{threesixty_analysis}';
    $sql = "SELECT COUNT(1) FROM ".$table_respondent." AS r".
	       " JOIN ".$table_analysis." AS a ON r.analysisid = a.id".
	       " WHERE a.userid = ".$userid." AND a.activityid = ".$activityid." AND r.uniquehash IS NOT NULL"
    ;
    return $DB->count_records_sql($sql);
}