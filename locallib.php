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

global $CFG;

require_once("$CFG->libdir/ddllib.php");

class shim
{
	static function build_navigation($navlinks)
	{
		if(isset($PAGE)) {
			foreach ($navlinks as $navlink) {
				/** @var moodle_page $PAGE */
				$PAGE->navbar->add($navlink['name'], new moodle_url($navlink['name']));
			}
		}
	}
}

/**
 * List of competencies along with their skills.
 *
 * @param $activityid int
 * @return array
 */
function threesixty_get_competency_listing($activityid) {
    global $DB;
    $ret = array();

	$table_skill = '{threesixty_skill}';
	$table_competency = '{threesixty_competency}';
    $sql =
	    "SELECT s.id AS skillid, c.id AS competencyid, c.name, c.description, s.name AS skillname, c.showfeedback, c.sortorder AS competencyorder, s.sortorder AS skillorder FROM ".$table_skill." AS s".
	    " RIGHT OUTER JOIN ".$table_competency." AS c ON s.competencyid = c.id".
	    " WHERE c.activityid = $activityid".
	    " ORDER BY c.sortorder, s.sortorder"
    ;

    if ($rs = $DB->get_records_sql($sql)) {
        foreach ($rs as $record) {
            if (empty($ret[$record->competencyid])) {
                $competency = new object;
                $competency->id = $record->competencyid;
                $competency->name = $record->name;
                $competency->description = $record->description;
                $competency->showfeedback = ($record->showfeedback == 1);
                $competency->skills = $record->skillname;
                $ret[$competency->id] = $competency;
            } else {
                $ret[$record->competencyid]->skills .= ', ' . $record->skillname;
            }
        }
    }

    return $ret;
}

/**
 * Delete the given competency from the database.
 *
 * @param integer $competencyid  The ID of the competency record
 * @param boolean $intransaction True if there is already an active transation
 * @return bool True if the operation has succeeded, false otherwise
 * @throws Exception
 */
function threesixty_delete_competency($competencyid, $intransaction=false) {
    global $DB;

    if (!$intransaction) {
        $transaction = $DB->start_delegated_transaction();
    }

    // Delete all dependent skills.
    $skills = $DB->get_records('threesixty_skill', array('competencyid' => $competencyid));
    if ($skills and count($skills) > 0) {
        foreach ($skills as $skill) {
            if (!threesixty_delete_skill($skill->id, true)) {
                throw new Exception('Couldn\'t delete the skill');
            }
        }
    }

    // Delete all dependent response competencies.
    if (!$DB->delete_records('threesixty_response_comp', array('competencyid' => $competencyid))) {
        if (!$intransaction) {
            // TODO moodle_rollback_sql();
        }
        return false;
    }

    // Delete competencies to be carried to the training diary.
    if (!$DB->delete_records('threesixty_carried_comp', array('competencyid' => $competencyid))) {
        if (!$intransaction) {
            throw new Exception('Couldn\'t delete competency');
        }
        return false;
    }

    // Perform the deletions.
    if (!$DB->delete_records('threesixty_competency', array('id' => $competencyid))) {
        if (!$intransaction) {
            throw new Exception('Couldn\'t delete competency');
        }
        return false;
    }

    if (isset($transaction) && !$intransaction) {
        $DB->commit_delegated_transaction($transaction);
    }
    return true;
}

/**
 * Delete the given skill from the database.
 *
 * @param integer $skillid       The ID of the skill record
 * @param boolean $intransaction True if there is already an active transation
 * @return bool True if the operation has succeeded, false otherwise
 * @throws Exception
 */
function threesixty_delete_skill($skillid, $intransaction=false) {
    global $DB;

    if (!$intransaction) {
        $transaction = $DB->start_delegated_transaction();
    }

    // Delete all dependent response skills.
    if (!$DB->delete_records('threesixty_response_skill', array('skillid' => $skillid))) {
        if (!$intransaction) {
            throw new Exception('Couldn\'t delete skills');
        }
        return false;
    }

    // Delete matching records in trdiary_pdp_skill.
    $trdiarytable = new xmldb_table('trdiary_pdp_skill');
    $dbman = $DB->get_manager();
    if ($dbman->table_exists($trdiarytable)) {
        if (!$DB->delete_records('trdiary_pdp_skill', array('skillid' => $skillid))) {
            if (!$intransaction) {
                throw new Exception('Couldn\'t delete skills');
            }
            return false;
        }
    }

    // Perform the deletion.
    if (!$DB->delete_records('threesixty_skill', array('id' => $skillid))) {
        if (!$intransaction) {
            throw new Exception('Couldn\'t delete skills');
        }
        return false;
    }
	
	if (isset($transaction) && !$intransaction) {
        $DB->commit_delegated_transaction($transaction);
    }
    return true;
}

/**
 * Delete the given analysis from the database.
 *
 * @param integer $analysisid    The ID of the analysis record
 * @param boolean $intransaction True if there is already an active transation
 * @return bool True if the operation has succeeded, false otherwise
 * @throws Exception
 */
function threesixty_delete_analysis($analysisid, $intransaction=false) {
    global $DB;

    if (!$intransaction) {
        $transaction = $DB->start_delegated_transaction();
    }

    // Delete all dependent responses.
    // ...$responses = $DB->get_record('threesixty_response', array('analysisid' => $analysisid), 'id');.
    $responses = $DB->get_records('threesixty_response', array('analysisid' => $analysisid), '', 'id');
    if ($responses and count($responses) > 0) {
        foreach ($responses as $response) {
            if (!threesixty_delete_response($response->id, true)) {
                if (!$intransaction) {
                    throw new Exception('Couldn\'t delete responses');
                }
                return false;
            }
        }
    }

    // Delete all dependent carried_competencies.
    if (!$DB->delete_records('threesixty_carried_comp', array('analysisid' => $analysisid))) {
        if (!$intransaction) {
            throw new Exception('Couldn\'t delete responses');
        }
        return false;
    }

    // Delete all dependent respondent.
    if (!$DB->delete_records('threesixty_respondent', array('analysisid' => $analysisid))) {
        if (!$intransaction) {
            throw new Exception('Couldn\'t delete responses');
        }
        return false;
    }

    // Perform the deletion.
    if (!$DB->delete_records('threesixty_analysis', array('id' => $analysisid))) {
        if (!$intransaction) {
            throw new Exception('Couldn\'t delete responses');
        }
        return false;
    }
	
	if (isset($transaction) && !$intransaction) {
        $DB->commit_delegated_transaction($transaction);
    }
    return true;
}

/**
 * Delete the given response from the database.
 *
 * @param integer $responseid    The ID of the response record
 * @param boolean $intransaction True if there is already an active transation
 * @return bool True if the operation has succeeded, false otherwise
 * @throws Exception
 */
function threesixty_delete_response($responseid, $intransaction=false) {
    global $DB;

    if (!$intransaction) {
        $transaction = $DB->start_delegated_transaction();
    }

    // Delete all dependent response competencies.
    if (!$DB->delete_records('threesixty_response_comp', array('responseid' => $responseid))) {
        if (!$intransaction) {
            throw new Exception('Couldn\'t delete responses');
        }
        return false;
    }

    // Delete all dependent response skills.
    if (!$DB->delete_records('threesixty_response_skill', array('responseid' => $responseid))) {
        if (!$intransaction) {
            throw new Exception('Couldn\'t delete responses');
        }
        return false;
    }

    // Perform the deletion.
    if (!$DB->delete_records('threesixty_response', array('id' => $responseid))) {
        if (!$intransaction) {
            throw new Exception('Couldn\'t delete responses');
        }
        return false;
    }
	
	if (isset($transaction) && !$intransaction) {
        $DB->commit_delegated_transaction($transaction);
    }
    return true;
}

/**
 * Delete the given respondent from the database.
 *
 * @param integer $respondentid  The ID of the respondent record
 * @param boolean $intransaction True if there is already an active transation
 * @return bool True if the operation has succeeded, false otherwise
 * @throws Exception
 */
function threesixty_delete_respondent($respondentid, $intransaction=false) {
    global $DB;

    if (!$intransaction) {
        $transaction = $DB->start_delegated_transaction();
    }

    // Delete the dependent response if necessary.
    if ($responseid = $DB->get_field('threesixty_response', 'id', array('respondentid'=>$respondentid))) {
        if (!threesixty_delete_response($responseid, true)) {
            if (!$intransaction) {
                throw new Exception('Couldn\'t delete responses');
            }
            return false;
        }
    }

    // Perform the deletion.
    if (!$DB->delete_records('threesixty_respondent', array('id' => $respondentid))) {
        if (!$intransaction) {
            throw new Exception('Couldn\'t delete respondents');
        }
        return false;
    }
	
	if (isset($transaction) && !$intransaction) {
        $DB->commit_delegated_transaction($transaction);
    }
    return true;
}

/**
 * List of skills and their competency.
 *
 * @param $activityid int
 * @return array
 */
function threesixty_get_skill_names($activityid) {
    global $DB;

    $sql = "SELECT s.id, c.id AS competencyid, c.name AS competencyname, s.name AS skillname
              FROM {threesixty_competency} c
  RIGHT OUTER JOIN {threesixty_skill} s ON c.id = s.competencyid
             WHERE c.activityid = $activityid
          ORDER BY c.sortorder, s.sortorder";

    return $DB->get_records_sql($sql);
}

/**
 * List of competencyid and feedback.
 *
 * @param $analysisid int
 * @return array
 */
function threesixty_get_feedback($analysisid) {
    global $DB;

    $sql = "SELECT trc.id, trc.competencyid, trc.feedback
            FROM {threesixty_response} tr
            JOIN {threesixty_response_comp} trc
                ON trc.responseid=tr.id
            WHERE tr.analysisid=$analysisid";

    $ret = $DB->get_records_sql($sql);
    if ($ret) {
        return $ret;
    } else {
        return array();
    }
}

/**
 * List of scores set by the user as well as the name of the score.
 *
 * @param $analysisid        int id of analysis to filter to
 * @param $competencyaverage boolean whether to include aggregate statistics
 * @param null $typeid       int id, if any, of respondent type to filter to
 * @return object describing the search performed and it's results
 */
function threesixty_get_self_scores($analysisid, $competencyaverage, $typeid=null) {
    global $selfresponsetypes, $DB;

    $ret = new object();
    $ret->name = $selfresponsetypes[$typeid];
    $ret->type = 'self'.$typeid;
    $idcolumn = 's.id';
    $scorecolumn = 'rs.score';
    $competencyjoin = '';
    $groupbyclause = '';
    $orderbyclause = 'order by s.competencyid, s.sortorder';
    if ($competencyaverage) {
        $idcolumn = 'c.id';
        $scorecolumn = 'AVG(rs.score) AS score';
        $competencyjoin = "JOIN {threesixty_competency} c ON c.id = s.competencyid";
        $groupbyclause = 'GROUP BY c.id';
        $orderbyclause = '';
    }

    $sql = "SELECT $idcolumn, $scorecolumn FROM {threesixty_respondent} rp".
           " RIGHT OUTER JOIN {threesixty_response} r ON r.respondentid = rp.id".
           " JOIN {threesixty_response_skill} rs ON r.id = rs.responseid".
	       " RIGHT OUTER JOIN {threesixty_skill} s ON s.id = rs.skillid".
	       $competencyjoin.
	       " WHERE (r.analysisid IS NULL OR r.analysisid = $analysisid)".
	       " AND (r.timecompleted IS NULL or r.timecompleted > 0)".
	       " AND rp.uniquehash IS NULL".
	       ($typeid === null ? '' : ' AND rp.type = '.$typeid.' ').
	       " ".$groupbyclause.
	       " ".$orderbyclause
    ;

    if (!$ret->records = $DB->get_records_sql($sql)) {
        $ret->records = array();
    }

    return $ret;
}

/**
 * Returns true if the given activity has been completed by the given user.
 *
 * @param $activityid int
 * @param $userid int
 * @return bool
 */
function threesixty_is_completed($activityid, $userid) {
    /*
    global $CFG, $DB;
      ... $sql = "SELECT r.id
                  FROM {threesixty_analysis} a
                  JOIN {threesixty_response} r ON r.analysisid = a.id
                 WHERE a.activityid = $activityid AND a.userid = $userid AND
                       r.timecompleted > 0 AND r.d IS NULL";
        return $DB->get_records_sql($sql) ? true : false;... */
    return true;
}

/**
 * Return a list of users having submitted a response in this activity.
 *
 * @param object $activity Record from the threesixty table
 * @returns array of user records.
 */
function threesixty_users($activity) {
    global $DB;

    $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname
              FROM {threesixty_analysis} a
              JOIN {threesixty_response} r ON r.analysisid = a.id
              JOIN {user} u ON a.userid = u.id
             WHERE a.activityid = $activity->id AND
                   r.timecompleted > 0";
    $records = $DB->get_records_sql($sql);
    return $records;
}

/**
 * Returns a list of all of the users who are eligible to participate in the
 * 360 activity.
 *
 * @author eleanor.martin
 * @param  <type> $context
 * @param string $sort
 * @return array of user records with id, firstname, lastname.
 */
function threesixty_get_possible_participants($context, $sort="u.lastname") {
    $fields = 'u.id, u.firstname, u.lastname';
    // ...params are $context, $capability, $fields, $sort, $limitfrom, $limitnum, $groups, $exceptions, $doanything.
    // ...doanything set to false so admins are not brought back by default.
    $users = get_users_by_capability($context, 'mod/threesixty:participate', $fields, $sort, '', '', '', '', false);
    return $users;
}
 /**
  * Return an html table listing the users.
  *
  * @param object $activity Record from the threesixty table
  * @param string $url      URL of the page to open once the 'userid' param has been added
  * @returns string The HTML to print out on the page (either a table or error message)
  */
function threesixty_user_listing($activity, $url) {

    if ($records = threesixty_users($activity)) {
        $table = new html_table();
        $table->head = array(get_string('name'));
        $table->data = array();

        foreach ($records as $r) {
            $name = format_string(fullname($r));
            $selectlink = "<a href=\"$url&amp;userid=$r->id\">$name</a>";

            $table->data[] = array($selectlink);
        }

        return get_string('selectuser', 'threesixty').html_writer::table($table);
    }

    return get_string('nousersfound', 'threesixty');
}

/**
 * Return the heading to print out to show the currently selected user.
 *
 * @param object $user  Record from the user table
 * @param int $courseid ID of the current course
 * @param string $url   URL of the current page without the userid parameter
 * @param bool $selectanother
 * @return string
 */
function threesixty_selected_user_heading($user, $courseid, $url, $selectanother=true) {
    global $CFG, $OUTPUT;

    // ...echo "<pre>";.

    $name = format_string(fullname($user));

    // ...echo "\n\n"; var_dump($name);.

    $data = new object;
    $data->fullname = "<a href=\"$CFG->wwwroot/user/view.php?id={$user->id}&amp;course=$courseid\">$name</a>";
    $data->url = $url;

    // ...echo "\n\n"; var_dump($data); die();.

    if ($selectanother) {
        $text = get_string('selecteduser', 'threesixty', $data);
    } else {
        $text = get_string('reportforuser', 'threesixty', $data);
    }

    // ...return $OUTPUT->heading($text, '', 2, 'main', true);.
    return $OUTPUT->heading($text);
}


/**
 * Return the page where the first incomplete competency is or 1 if it's complete.
 *
 * @param $activityid
 * @param $userid
 * @param $respondent
 * @return int
 */
function threesixty_get_first_incomplete_competency($activityid, $userid, $respondent) {
    global $DB;

    $respondentclause = 'r.respondentid IS NULL';
    if ($respondent != null) {
        $respondentclause = "r.respondentid = $respondent->id";
    }

    if (!$response = $DB->get_record_sql("SELECT r.id
                                       FROM {threesixty_analysis} a
                                       JOIN {threesixty_response} r ON r.analysisid = a.id
                                       WHERE a.activityid = $activityid AND a.userid = $userid AND
                                             $respondentclause AND r.timecompleted = 0")) {
        return 1; // ...activity is either not started or completed already.
    }

    $sql = "SELECT c.id, c.sortorder
              FROM {threesixty_response} r
              JOIN {threesixty_response_skill} rs ON rs.responseid = r.id
    RIGHT OUTER JOIN {threesixty_skill} s ON rs.skillid = s.id
              JOIN {threesixty_competency} c ON c.id = s.competencyid
             WHERE (r.id IS NULL or r.id = $response->id) AND (score IS NULL OR score = 0)
          ORDER BY c.sortorder";

     //  ...if ($rs = $DB->get_recordset_sql($sql, null, 1) and $record = rs_fetch_record($rs)) { .
    $rs = $DB->get_recordset_sql($sql, null, 1);
    if ($rs->valid()) {
        $record = $rs->current();

        // Figure out which page this competency is in.
        return $record->sortorder+1;
    }

    // All skills have been scored, form has not been submitted, go to last page.
    return $DB->$DB->count_records('threesixty_competency', 'activityid', $activityid);
}

function threesixty_get_average_skill_scores($analysisid, $respondenttype, $competencyaverage) {
    global $respondenttypes, $DB;

    $ret = new object();

    $fromclause = "FROM {threesixty_response} r";
    $wherefragment = '';

    if ($respondenttype !== false) {
        $fromclause = "FROM {threesixty_respondent} rp
           RIGHT OUTER JOIN {threesixty_response} r ON r.respondentid = rp.id";
        $wherefragment = "AND rp.uniquehash IS NOT NULL AND rp.type = $respondenttype";

        $ret->name = $respondenttypes[$respondenttype] . ' ' . get_string('filter:average', 'threesixty');
        $ret->type = 'type'.$respondenttype;
    } else {
        $ret->name = get_string('filter:average', 'threesixty');
        $ret->type = get_string('filter:average', 'threesixty');
    }

    $idcolumn = 's.id';
    $competencyjoin = '';
    if ($competencyaverage) {
        $idcolumn = 'c.id';
        $competencyjoin = "JOIN {threesixty_competency} c ON s.competencyid = c.id";
    }

    $sql = "SELECT $idcolumn, AVG(rs.score) AS score
              $fromclause
              JOIN {threesixty_response_skill} rs ON r.id = rs.responseid
    RIGHT OUTER JOIN {threesixty_skill} s ON s.id = rs.skillid
    $competencyjoin
             WHERE (r.analysisid IS NULL OR r.analysisid = $analysisid) AND
                   (r.timecompleted IS NULL or r.timecompleted > 0)
                   $wherefragment
          GROUP BY $idcolumn";

    if (!$ret->records = $DB->get_records_sql($sql)) {
        $ret->records = array();
    }

    return $ret;
}
/*
 * Redo the sort orders of the competencies in a given activity.
 *
 * @param $activityid - the id of the activity to reorder the competencies for.
 */
function threesixty_reorder_competencies($activityid) {
    global $DB;
    // Get the remaining competencies, ordered correctly, and reset the sortorder from 0.
    $competencies = $DB->get_records('threesixty_competency', array('activityid' => $activityid));
    if ($competencies) {
        $neworder = 0;
        foreach ($competencies as $competency) {
            if ($competency->sortorder != $neworder) {
                $competency->sortorder = $neworder;
                $DB->update_record('threesixty_competency', $competency);
            }
            $neworder++;
        }
    }
}
