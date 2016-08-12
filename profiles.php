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

/*
 * Shows the students' responses to the different profile types required.
 *
 * @author Eleanor Martin <eleanor.martin@kineo.com>
 * @package mod/threesixty
 */

require_once('../../config.php');
require_once('locallib.php');

global $DB, $PAGE;

$a       = required_param('a', PARAM_INT);  // Threesixty instance ID.
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

$context = context_module::instance($cm->id);

require_login($course, true, $cm);

$PAGE->set_url('/mod/threesixty/profiles.php', array('a' => $a));
$PAGE->set_pagelayout('incourse');

$user = null;
if ($userid > 0 and !$user = $DB->get_record('user', array('id' => $userid), null, 'id, firstname, lastname')) {
	print_error('Invalid User ID');
}

/** @var core_renderer $OUTPUT */
echo $OUTPUT->header();

$baseurl = "profiles.php?a=$activity->id";
$strthreesixtys = get_string('modulenameplural', 'threesixty');
$strthreesixty  = get_string('modulename', 'threesixty');

$navlinks = array();
$navlinks[] = array('name' => $strthreesixtys, 'link' => "index.php?id=$course->id", 'type' => 'activity');
$navlinks[] = array('name' => format_string($activity->name), 'link' => '', 'type' => 'activityinstance');

shim::build_navigation($navlinks);

/** @var moodle_page $PAGE */
$PAGE->set_title(format_string($activity->name));

// Main content.
$currenttab = 'activity';
$section = null;
require_once('tabs.php');

threesixty_self_profile_options($course->id, $baseurl, $activity, $context);

// ...print_footer($course);.
echo $OUTPUT->footer();

function threesixty_self_profile_options($courseid, $baseurl, $activity, $context) {
    global $CFG, $USER;

    $view_all_users = has_capability('mod/threesixty:viewreports', $context);
    $canedit = has_capability('mod/threesixty:edit', $context);
    if ($view_all_users) {
        // ...$users = threesixty_users($activity);.
        $users = threesixty_get_possible_participants($context);
    } else {
        $users = array($USER);
    }
    $selfresponses = explode("\n", get_config(null, 'threesixty_selftypes'));
    if (!empty($selfresponses)) {

        $table = new html_table();
        $table->head = array();
        if ($view_all_users) {
            $table->head[] = 'User';
        }
        $table->head[] = get_string('self:responsetype', 'threesixty');
        $table->head[] = get_string('self:responsecompleted', 'threesixty');
        $table->head[] = get_string('self:responseoptions', 'threesixty');
        foreach ($users as $user) {
            $data = array();
            if ($view_all_users) {
                $data[] = "<a href=".$CFG->wwwroot.
                        "/user/view.php?id={$user->id}&course={$activity->course}>".
                                format_string($user->firstname." ".$user->lastname)."</a>";
            }
            $responsenumber = 0; // This provides the type id of the response.
            foreach ($selfresponses as $responsetype) {
                if ($responsenumber>0) {
                    $data = array();
                    if ($view_all_users) {
                        $data[] = "&nbsp;";
                    }
                }
                $data[] = $responsetype;
                $timecompleted = get_completion_time($activity->id, $user->id, $responsenumber, true);
                if ($timecompleted>0) {
                    $canreallyedit = $canedit;
                    $timeoutput = userdate($timecompleted);
                } else {
                    $canreallyedit = false;
                    $timeoutput = "<span class=\"incomplete\">".get_string('self:incomplete', 'threesixty')."</span>";
                }
                $data[] = $timeoutput;

                $data[] = get_options($activity->id, $user->id, $responsenumber, $view_all_users, $canreallyedit);
                $responsenumber += 1;
                $table->data[] = $data;
            }
        }
        // ...print_table($table);.
        echo html_writer::table($table);
    }
}
function get_completion_time($activityid, $userid, $responsetype, $self=false) {
    global $DB;

    $table_analysis = '{threesixty_analysis}';
    $table_respondent = '{threesixty_respondent}';
    $table_response = '{threesixty_response}';
    $sql = "SELECT r.timecompleted FROM ".$table_analysis." AS a".
           " JOIN ".$table_respondent." AS rp ON a.id = rp.analysisid".
           " JOIN ".$table_response." AS r ON rp.id = r.respondentid".
           " WHERE a.userid = ".$userid." AND a.activityid = ".$activityid." AND rp.type = ".$responsetype
    ;
    if ($self) {
        $sql .= " AND rp.uniquehash IS NULL";
    } else {
        $sql .= " AND rp.uniquehash IS NOT NULL";
    }

    $times = $DB->get_records_sql($sql);
    if ($times) {
        if (count($times)>1) {
            echo "There has been a problem retrieving the time completed. Please contact your administrator.";
        } else {
            $time = array_pop($times);
            return $time->timecompleted;
        }
    }
    return "ERROR";
}
function get_options($activityid, $userid, $typeid, $view_all_users, $canedit) {
    global $CFG;

    $scoreurl = $CFG->wwwroot."/mod/threesixty/score.php?a=".$activityid;
    if ($view_all_users) {
        $scoreurl.="&userid=".$userid;
    }
    $scoreurl.="&typeid=".$typeid;
    $output = "<a href='".$scoreurl."'>View</a>";

    if ($canedit) {
        $amendurl =$CFG->wwwroot."/mod/threesixty/amend.php?a=".$activityid."&typeid=".$typeid."&userid=".$userid;
        $output.=" | <a href='".$amendurl."'>Amend</a>";
    }
    return $output;
}