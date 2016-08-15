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
 * Allows a student (or an external person using a code) to assess
 * skills accross competencies.
 *
 * @author  Francois Marier <francois@catalyst.net.nz>
 * @package mod/threesixty
 */

require_once('../../config.php');
require_once('locallib.php');
require_once('score_form.php');

global $DB, $USER, $PAGE, $CFG, $COURSE;

$a    = optional_param('a', 0, PARAM_INT);  // Threesixty instance ID.
$code = optional_param('code', '', PARAM_ALPHANUM); // Unique hash.
$page = optional_param('page', 0, PARAM_INT); // Page number.
$typeid = optional_param('typeid', 0, PARAM_INT); // Type of response.

$respondent = null;
$analysis = null;
$activity = null;
$user = null;
$userid = 0;

$externalrespondent = !empty($code);

if ($externalrespondent) {
    // External respondent.
    if (!$respondent = $DB->get_record('threesixty_respondent', array('uniquehash' => $code))) {
        // ...error_log("threesixty: Invalid response hash from {$_SERVER['REMOTE_ADDR']}");.
        print_error('error:invalidcode', 'threesixty');
    }
    if (!$analysis = $DB->get_record('threesixty_analysis', array('id' => $respondent->analysisid))) {
	    print_error('Analysis ID is incorrect');
    }
    if (!$activity = $DB->get_record('threesixty', array('id' => $analysis->activityid))) {
	    print_error('Course module is incorrect');
    }
    if (!$user = $DB->get_record('user', array('id' => $analysis->userid), $fields='id, firstname, lastname')) {
	    print_error('Invalid User ID');
    }

} else if ($a > 0) {
    // Logged-in respondent.
    if (!$activity = $DB->get_record('threesixty', array('id' => $a))) {
	    print_error('Course module is incorrect');
    }

    $userid = optional_param('userid', $USER->id, PARAM_INT);

    if (!$user = $DB->get_record('user', array('id' => $userid), $fields='id, firstname, lastname')) {
	    print_error('Invalid User ID');
    }

    if ($analysis = $DB->get_record('threesixty_analysis', array('userid' => $userid, 'activityid' => $a))) {
        $respondent = $DB->get_record('threesixty_respondent',
                array('analysisid' => $analysis->id, 'type' => $typeid, 'uniquehash' => null));
    }
} else {
    // We need either $a or $code to be defined.
	print_error('Missing activity ID');
}

if (!$course = $DB->get_record('course', array('id' => $activity->course))) {
	print_error('Course is misconfigured');
}
if (!$cm = get_coursemodule_from_instance('threesixty', $activity->id, $course->id)) {
	print_error('Course Module ID was incorrect');
}

if (!$externalrespondent) {
    // Capability checks only relevant to logged-in users.
    $context = context_module::instance($cm->id);

    require_login($course, true, $cm);
    require_capability('mod/threesixty:view', $context);

    // ...echo "<pre>"; var_dump($user); echo "</pre>";.

    if ($USER->id == $user->id) {
        require_capability('mod/threesixty:participate', $context);
    } else {
        require_capability('mod/threesixty:viewreports', $context);
    }
}

$PAGE->set_url('/mod/threesixty/score.php', array('a' => $a));
$PAGE->set_pagelayout('incourse');

// Set URLs based on logged-in v. loginless mode.
$cancelurl = '';
$baseurl = "score.php";
if (!$externalrespondent) {
    $baseurl .= "?a=$activity->id&amp;userid=$user->id&amp;typeid=$typeid";
    $cancelurl = "$CFG->wwwroot/course/view.php?id=$COURSE->id";
} else {
    $baseurl .= "?code=$code";
}
$currenturl = "$baseurl&amp;page=$page";


if ($page < 1) {
    $page = threesixty_get_first_incomplete_competency($activity->id, $USER->id, $respondent);
}

$nbpages = null;
$mform = null;
$fromform = null;

if ($competency = get_competency_details($page, $activity->id, $user->id, $respondent)) {
    $nbpages = $DB->count_records('threesixty_competency', array('activityid' => $activity->id));
    $mform = new mod_threesixty_score_form(null, compact('a', 'code', 'competency', 'page', 'nbpages', 'userid', 'typeid'));

    if ($mform->is_cancelled()) {
        redirect($cancelurl);
    }

    $fromform = $mform->get_data();
} else if ($page > 1) {
    print_error('error:invalidpagenumber', 'threesixty');
}

if ($fromform) {
    if (!empty($fromform->buttonarray['previous'])) { // Previous button.
        $errormsg = save_changes($fromform, $activity->id, $user->id, $competency, false, $respondent);
        if (!empty($errormsg)) {
            print_error('error:cannotsavescores', 'threesixty', $currenturl, '', $errormsg);
        }

        $newpage = max(1, $page - 1);
        redirect("$baseurl&amp;page=$newpage");
    } else if (!empty($fromform->buttonarray['next'])) { // Next button.
        $errormsg = save_changes($fromform, $activity->id, $user->id, $competency, false, $respondent);
        if (!empty($errormsg)) {
            print_error('error:cannotsavescores', 'threesixty', $currenturl, '', $errormsg);
        }

        $newpage = min($nbpages, $page + 1);
        redirect("$baseurl&amp;page=$newpage");
    } else if (!empty($fromform->buttonarray['finish'])) {
        $errormsg = save_changes($fromform, $activity->id, $user->id, $competency, true, $respondent);
        if (!empty($errormsg)) {
            print_error('error:cannotsavescores', 'threesixty', $currenturl, '', $errormsg);
        }

        if (!$externalrespondent) {
            redirect("view.php?a=$activity->id");
        } else {
            redirect("thankyou.php?a=$activity->id");
        }
    } else {
        print_error('error:unknownbuttonclicked', 'threesixty', $cancelurl);
    }
}

// TODO add_to_log($course->id, 'threesixty', 'score', $currenturl, $activity->id);

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
if (!$externalrespondent) {
    $currenttab = 'activity';
    $section = null;
    require_once('tabs.php');
} else {
    $message = get_string('respondentwelcome', 'threesixty', format_string($respondent->email));
    if ($competency->locked) {
        $message .= get_string('thankyoumessage', 'threesixty');
    } else {
        $message .= get_string('respondentwarning', 'threesixty', "wrongemail.php?code=$code");
        $message .= get_string('respondentinstructions', 'threesixty');
        $message .= "<p>".get_string('respondentindividual', 'threesixty', $user->firstname." ".$user->lastname)."</p>";
    }
    echo $OUTPUT->box($message);
}
if ($mform) {
    set_form_data($mform, $competency);
    $mform->display();
} else {
    print_string('nocompetencies', 'threesixty');
}

// ...print_footer($course);.
echo $OUTPUT->footer();

function get_competency_details($page, $activityid, $userid, $respondent) {
    global $DB;

    // ...if ($record = $DB->get_record('threesixty_competency',
    // array('activityid' => $activityid), $fields='*', IGNORE_MULTIPLE)) {.
    if ($record = $DB->get_records('threesixty_competency', array('activityid' => $activityid), '', '*', $page - 1, 1)) {

        // ...get first (and last) array element for we only want one and one should be returned by get_records.
        $current_record = current($record);

        $respondentclause = 'r.respondentid IS NULL';
        if ($respondent != null) {
            $respondentclause = "r.respondentid = $respondent->id";
        }

        if (empty($current_record->id) && !is_int(empty($current_record->id))) {
            print_error('error:competencyidnotexist', 'threesixty');
        }

        $responsesql = "SELECT r.id AS responseid, c.feedback AS competencyfeedback,
                        r.timecompleted AS timecompleted
                        FROM {threesixty_analysis} a
                        LEFT OUTER JOIN {threesixty_response} r ON a.id = r.analysisid
                        LEFT OUTER JOIN {threesixty_response_comp} c ON c.responseid = r.id
                        AND c.competencyid = {$current_record->id}
                        WHERE a.userid = $userid AND a.activityid = $activityid  AND $respondentclause";

        $response = $DB->get_record_sql($responsesql);

        if ($response and !empty($response->competencyfeedback)) {
            $current_record->feedback = $response->competencyfeedback;
        }

        $current_record->locked = false;
        if ($response and !empty($response->timecompleted)) {
            $current_record->locked = true;
        }

        // Get skill descriptions.
        $current_record->skills = $DB->get_records('threesixty_skill', array('competencyid' => $current_record->id), '',
                                                                    'id, name, description, sortorder, 0 AS score');

        if ($current_record->skills and $response and $response->responseid != null) {
            // Get scores.
            $sql = "SELECT s.id, r.score
                      FROM {threesixty_skill} s
                      JOIN {threesixty_response_skill} r ON s.id = r.skillid
                     WHERE s.competencyid = $current_record->id AND r.responseid = $response->responseid";

            if ($scores = $DB->get_records_sql($sql)) {
                foreach ($scores as $s) {
                    $current_record->skills[$s->id]->score = $s->score;
                }
            }
        }

        return $current_record;
    }
    return false;
}

/**
 * @param $mform moodleform
 * @param $competency
 */
function set_form_data($mform, $competency) {
    $toform = array();

    if (!empty($competency->feedback)) {
        $toform['feedback'] = $competency->feedback;
    }

    if (!empty($competency->skills) and count($competency->skills) > 0) {
        foreach ($competency->skills as $skill) {
            $toform["radioarray_{$skill->id}[score_{$skill->id}]"] = $skill->score;
        }
    }

    
    $mform->set_data($toform);
}

function save_changes($formfields, $activityid, $userid, $competency, $finished, $respondent) {
    global $DB;

    if ($competency->locked) {
        // No changes are saved for responses which have been submitted already.
        return '';
    }

    if (!$analysis = $DB->get_record('threesixty_analysis', array('activityid' => $activityid, 'userid' => $userid))) {
        $analysis = new object();
        $analysis->activityid = $activityid;
        $analysis->userid = $userid;

        if (!$analysis->id = $DB->insert_record('threesixty_analysis', $analysis)) {
            // ...error_log('threesixty: could not insert new analysis record');.
            return get_string('error:databaseerror', 'threesixty');
        }
    }

    $respondentid = null;
    if ($respondent == null) {
        $respondent = new object();
        $respondent->analysisid = $analysis->id;
        $respondent->type = $formfields->typeid;
        if (!$respondent->id = $DB->insert_record('threesixty_respondent', $respondent)) {
            // ...error_log('threesixty: could not insert new respondent record');.
            return get_string('error:databaseerror', 'threesixty');
        }
    }
    $respondentid = $respondent->id;
    if (!$response = $DB->get_record('threesixty_response',
            array('analysisid' => $analysis->id, 'respondentid' => $respondentid))) {
        $response = new object();
        $response->analysisid = $analysis->id;
        $response->respondentid = $respondentid;

        if (!$response->id = $DB->insert_record('threesixty_response', $response)) {
            // ...error_log('threesixty: could not insert new response record');.
            return get_string('error:databaseerror', 'threesixty');
        }
    }

    if (!empty($competency->skills)) {
        foreach ($competency->skills as $skill) {
            $arrayname = "radioarray_$skill->id";
            if (empty($formfields->$arrayname)) {
                // ...error_log("threesixty: $arrayname is missing from the submitted form fields");.
                return get_string('error:formsubmissionerror', 'threesixty');
            }
            $a = $formfields->$arrayname;

            $scorename = "score_$skill->id";
            if (empty($a[$scorename])) {
                $scorevalue = 0;
                // Choosing "Not set" will clear the existing value.
            } else {
                $scorevalue = $a[$scorename];
            }

            // Save this skill score in the database.
            if ($score = $DB->get_record('threesixty_response_skill',
                    array('responseid' => $response->id, 'skillid' => $skill->id))) {
                $newscore = new object();
                $newscore->id = $score->id;
                $newscore->score = $scorevalue;

                if (!$DB->update_record('threesixty_response_skill', $newscore)) {
                    // ...error_log("threesixty: could not update score for skill $skill->id");.
                    return get_string('error:databaseerror', 'threesixty');
                }
            } else {
                $score = new object();
                $score->responseid = $response->id;
                $score->skillid = $skill->id;
                $score->score = $scorevalue;

                if (!$score->id = $DB->insert_record('threesixty_response_skill', $score)) {
                    // ...error_log("threesixty: could not insert score for skill $skill->id");.
                    return get_string('error:databaseerror', 'threesixty');
                }
            }
        }
    }

    if (isset($formfields->feedback)) {
        // Save this competency score in the database.
        if ($comp = $DB->get_record('threesixty_response_comp',
                array('responseid' => $response->id, 'competencyid' => $competency->id))) {
            $newcomp = new object();
            $newcomp->id = $comp->id;
            $newcomp->feedback = $formfields->feedback;

            if (!$DB->update_record('threesixty_response_comp', $newcomp)) {
                // ...error_log("threesixty: could not update score for competency $competency->id");.
                return get_string('error:databaseerror', 'threesixty');
            }
        } else {
            $comp = new object();
            $comp->responseid = $response->id;
            $comp->competencyid = $competency->id;
            $comp->feedback = $formfields->feedback;

            if (!$comp->id = $DB->insert_record('threesixty_response_comp', $comp)) {
                // ...error_log("threesixty: could not insert score for competency $competency->id");.
                return get_string('error:databaseerror', 'threesixty');
            }
        }
    }

    if ($finished) {

        $skills = $DB->get_records_sql("SELECT s.id FROM {threesixty_competency} c
                                              JOIN {threesixty_skill} s ON s.competencyid = c.id
                                             WHERE c.activityid = '$activityid';");

        $scores = $DB->get_records_sql("SELECT skillid,score FROM {threesixty_response_skill}
                                    WHERE responseid = '$response->id';");

        // Check that all of the scores have been set.
        foreach ($skills as $skillid => $skill) {
            if (!isset($scores[$skillid])) {
                // Score is not set.
                return get_string('error:allskillneedascore', 'threesixty');
            }
        }

        $newresponse = new object();
        $newresponse->id = $response->id;
        $newresponse->timecompleted = time();
        if (!$DB->update_record('threesixty_response', $newresponse)) {
            // ...error_log('threesixty: could not update the timecompleted field of the response');.
            return get_string('error:databaseerror', 'threesixty');
        }
    }

    return '';
}