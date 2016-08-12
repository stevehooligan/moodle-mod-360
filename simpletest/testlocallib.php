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
**
 * Unit tests for mod/threesixty/locallib.php
 *
 * @author Simon Coggins <simonc@catalyst.net.nz>
 * @package mod/threesixty
 */
// TODO Recommission this class for latest unit testing, for now I've put @noinspection in front of all the obvious errors

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    //  It must be included from a Moodle page.
}

global $CFG;

require_once($CFG->dirroot . '/mod/threesixty/locallib.php');
require_once($CFG->libdir . '/simpletestlib.php');

/** @noinspection PhpUndefinedClassInspection */
class locallib_test extends prefix_changing_test_case {
    // ...test data for database.
	public function __construct()
	{
		throw(new Exception('Unit testing for threesixty module currently awaiting modernisation'));
	}

    public $user_data = array(
            array('id', 'firstname', 'lastname', 'username'),
            array(1, 'admin', 'user', 'adminuser'),
            array(2, 'test', 'user', 'testuser')
        );

    public $threesixty_data = array(
            array('id', 'course', 'name', 'competenciescarried', 'requiredrespondents', 'timecreated', 'timemodified'),
            array(1, 1, 'Test 360', 3, 10, 1255991305, 0),
        );

    public $threesixty_competency_data = array(
            array('id', 'activityid', 'name', 'description', 'showfeedback'),
            array(1, 1, 'C1', 'C1D', 1),
            array(2, 1, 'C2', 'C2D', 1),
            array(3, 1, 'C3', 'C3D', 0)
        );

    public $threesixty_skill_data = array(
            array('id', 'competencyid', 'name', 'description'),
            array(1, 1, 'S1', 'S1D'),
            array(2, 1, 'S2', 'S2D'),
            array(3, 2, 'S3', 'S3D'),
            array(4, 2, 'S4', 'S4D'),
            array(5, 3, 'S5', 'S5D'),
            array(6, 3, 'S6', 'S6D')
        );

    public $threesixty_analysis_data = array(
            array('id', 'activityid', 'userid'),
            array(1, 1, 1),
            array(2, 1, 2)
        );

    public $threesixty_carried_comp_data = array(
            array('id', 'analysisid', 'competencyid'),
            array(1, 1, 1),
            array(2, 1, 2),
            array(3, 1, 3)
        );

    public $threesixty_respondent_data = array(
            array('id', 'email', 'type', 'analysisid', 'uniquehash'),
            array(1, 'test@example.com', 0, 1, '001f78072cd900336a3be617f2546ae03f277125'),
            array(2, 'test2@example.com', 0, 2, 'aaabbb')
        );

    // ...order of first two entries swapped so respondentid type is set to int.
    // ...(column type is determined by first row when creating tables).
    public $threesixty_response_data = array(
        array('id', 'analysisid', 'respondentid', 'timecompleted'),
            array(1, 1, 1, 1256268568),
            array(2, 1, null, 1256268568),
            array(3, 2, null, 0),
            array(4, 2, 2, 0)
        );

    public $threesixty_response_skill_data = array(
            array('id', 'responseid', 'skillid', 'score'),
            array(1, 1, 1, 5),
            array(2, 1, 2, 4),
            array(3, 1, 3, 3),
            array(4, 1, 4, 2),
            array(5, 1, 5, 1),
            array(6, 1, 6, 0),
            array(7, 2, 1, 3),
            array(8, 2, 2, 3),
            array(9, 2, 3, 3),
            array(10, 2, 4, 3),
            array(11, 2, 5, 3),
            array(12, 2, 6, 3),
            array(13, 3, 1, 1),
            array(14, 3, 2, 1),
            array(15, 3, 3, 2),
            array(16, 3, 4, 2),
            array(17, 3, 5, 3),
            array(18, 3, 6, 3),
            array(19, 4, 1, 5),
            array(20, 4, 2, 5),
            array(21, 4, 3, 5),
            array(22, 4, 4, 0),
            array(23, 4, 5, 0),
            array(24, 4, 6, 0)
        );

    public $threesixty_response_comp_data = array(
            array('id', 'responseid', 'competencyid', 'feedback'),
            array(1, 1, 1, 'C1 R1 feedback'),
            array(2, 1, 2, 'C2 R1 feedback'),
            array(3, 2, 1, 'C1 R2 feedback'),
            array(4, 2, 2, 'C2 R2 feedback')
        );

    public function setUp() {
        global $DB;
	    /** @noinspection PhpUndefinedClassInspection */
	    parent::setup();
	    /** @noinspection PhpUndefinedFunctionInspection */
	    load_test_table('{user}', $this->user_data, $DB);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    load_test_table('{threesixty}', $this->threesixty_data, $DB);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    load_test_table('{threesixty_competency}', $this->threesixty_competency_data, $DB);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    load_test_table('{threesixty_skill}', $this->threesixty_skill_data, $DB);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    load_test_table('{threesixty_analysis}', $this->threesixty_analysis_data, $DB);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    load_test_table('{threesixty_carried_comp}', $this->threesixty_carried_comp_data, $DB);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    load_test_table('{threesixty_respondent}', $this->threesixty_respondent_data, $DB);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    load_test_table('{threesixty_response}', $this->threesixty_response_data, $DB);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    load_test_table('{threesixty_response_skill}', $this->threesixty_response_skill_data, $DB);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    load_test_table('{threesixty_response_comp}', $this->threesixty_response_comp_data, $DB);
    }

    public function tearDown() {
        global $DB;
	
	    /** @noinspection PhpUndefinedFunctionInspection */
	    remove_test_table('{unittest_threesixty_response_comp}', $DB);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    remove_test_table('{unittest_threesixty_response_skill}', $DB);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    remove_test_table('{unittest_threesixty_response}', $DB);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    remove_test_table('{unittest_threesixty_respondent}', $DB);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    remove_test_table('{unittest_threesixty_carried_comp}', $DB);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    remove_test_table('{unittest_threesixty_analysis}', $DB);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    remove_test_table('{unittest_threesixty_skill}', $DB);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    remove_test_table('{unittest_threesixty_competency}', $DB);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    remove_test_table('{unittest_threesixty}', $DB);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    remove_test_table('{unittest_user}', $DB);
	
	    /** @noinspection PhpUndefinedClassInspection */
	    parent::tearDown();
    }

    public function test_mod_trdiary_get_competency_listing() {
        $activityid = 1;
        $activityid_2 = 999;

        $obj = new object();
        $obj->id = '1';
        $obj->name = 'C1';
        $obj->description = 'C1D';
        $obj->showfeedback = true;
        $obj->skills = 'S1, S2';
	
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(count(threesixty_get_competency_listing($activityid)), 3);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(array_shift(threesixty_get_competency_listing($activityid)), $obj);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertFalse(threesixty_get_competency_listing($activityid_2));
    }

    public function test_mod_threesixty_delete_competency() {
        global $DB;
        $competencyid = 1;
        $competencyid_2 = 999;

        $comp_before = $DB->count_records('threesixty_competency');
        $skill_before = $DB->count_records('threesixty_skill');
        $carried_before = $DB->count_records('threesixty_carried_comp');
        $resp_before = $DB->count_records('threesixty_response_comp');

        // ...this should fail and records remain unchanged.
        threesixty_delete_competency($competencyid_2);

        $comp_after = $DB->count_records('threesixty_competency');
        $skill_after = $DB->count_records('threesixty_skill');
        $carried_after = $DB->count_records('threesixty_carried_comp');
        $resp_after = $DB->count_records('threesixty_response_comp');
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($comp_before-$comp_after, 0);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($carried_before - $carried_after, 0);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($skill_before-$skill_after, 0);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($resp_before-$resp_after, 0);

        $comp_before2 = $DB->count_records('threesixty_competency');
        $skill_before2 = $DB->count_records('threesixty_skill');
        $carried_before2 = $DB->count_records('threesixty_carried_comp');
        $resp_before2 = $DB->count_records('threesixty_response_comp');

        // ...now do a real delete.
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertTrue(threesixty_delete_competency($competencyid));

        $comp_after2 = $DB->count_records('threesixty_competency');
        $skill_after2 = $DB->count_records('threesixty_skill');
        $carried_after2 = $DB->count_records('threesixty_carried_comp');
        $resp_after2 = $DB->count_records('threesixty_response_comp');
        // ...deleting this competency should delete 1 competency, 1 carried comp,.
        // ...2 response competencies and 2 skills.
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($comp_before2 - $comp_after2, 1);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($carried_before2 - $carried_after2, 1);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($skill_before2 - $skill_after2, 2);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($resp_before2 - $resp_after2, 2);

    }

    // ...this also tests threesixty_delete_response() as it is called.
    // ...from threesixty_delete_analysis.
    public function test_mod_threesixty_delete_analysis() {
        global $DB;
        $analysisid = 1;
        $analysisid_2 = 999;

        $analysis_before = $DB->count_records('threesixty_analysis');
        $carried_before = $DB->count_records('threesixty_carried_comp');
        $resp_before = $DB->count_records('threesixty_response_comp');
        $respondent_before = $DB->count_records('threesixty_respondent');

        // ...this should fail and records remain unchanged.
        threesixty_delete_analysis($analysisid_2);

        $analysis_after = $DB->count_records('threesixty_analysis');
        $carried_after = $DB->count_records('threesixty_carried_comp');
        $resp_after = $DB->count_records('threesixty_response_comp');
        $respondent_after = $DB->count_records('threesixty_respondent');
	
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($analysis_before-$analysis_after, 0);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($carried_before - $carried_after, 0);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($resp_before-$resp_after, 0);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($respondent_before-$respondent_after, 0);

        $analysis_before2 = $DB->count_records('threesixty_analysis');
        $carried_before2 = $DB->count_records('threesixty_carried_comp');
        $resp_before2 = $DB->count_records('threesixty_response_comp');
        $respondent_before2 = $DB->count_records('threesixty_respondent');

        // ...now do a real delete.
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertTrue(threesixty_delete_analysis($analysisid));

        $analysis_after2 = $DB->count_records('threesixty_analysis');
        $carried_after2 = $DB->count_records('threesixty_carried_comp');
        $resp_after2 = $DB->count_records('threesixty_response_comp');
        $respondent_after2 = $DB->count_records('threesixty_respondent');
	
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($analysis_before2-$analysis_after2, 1);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($carried_before2 - $carried_after2, 3);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($resp_before2 - $resp_after2, 4);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($respondent_before2 - $respondent_after2, 1);

    }

    public function test_mod_threesixty_delete_respondent() {
        global $DB;
        $respondentid = 1;
        $respondentid_2 = 999;

        $resp_before = $DB->count_records('threesixty_response_comp');
        $respondent_before = $DB->count_records('threesixty_respondent');

        // ...this should fail and records remain unchanged.
        threesixty_delete_respondent($respondentid_2);

        $resp_after = $DB->count_records('threesixty_response_comp');
        $respondent_after = $DB->count_records('threesixty_respondent');
	
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($resp_before - $resp_after, 0);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($respondent_before - $respondent_after, 0);

        $resp_before2 = $DB->count_records('threesixty_response_comp');
        $respondent_before2 = $DB->count_records('threesixty_respondent');

        // ...now do a real delete.
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertTrue(threesixty_delete_respondent($respondentid));

        $resp_after2 = $DB->count_records('threesixty_response_comp');
        $respondent_after2 = $DB->count_records('threesixty_respondent');
	
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($resp_before2 - $resp_after2, 2);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($respondent_before2 - $respondent_after2, 1);

    }

    public function test_mod_threesixty_get_skill_names() {
        $activityid = 1;
        $activityid_2 = 999;
        $obj = new stdClass();
        $obj->competencyid = '1';
        $obj->competencyname = 'C1';
        $obj->skillname = 'S1';
        $obj->id = 1;
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(array_shift(threesixty_get_skill_names($activityid)), $obj);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(count(threesixty_get_skill_names($activityid)), 6);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertFalse(threesixty_get_skill_names($activityid_2));

    }

    public function test_mod_threesixty_get_self_scores() {
        $analysisid = 1;
        $analysisid_2 = 999;
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(count(threesixty_get_self_scores($analysisid, false)->records), 6);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(count(threesixty_get_self_scores($analysisid, true)->records), 3);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(count(threesixty_get_self_scores($analysisid_2, false)->records), 0);

        $res = threesixty_get_self_scores($analysisid, false)->records;
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($res[1]->score, 3);
        $res = threesixty_get_self_scores($analysisid, true)->records;
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($res[1]->score, 3);

    }

    public function test_mod_threesixty_get_feedback() {
        $analysisid = 1;
        $analysisid_2 = 999;
        threesixty_get_feedback($analysisid);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(count(threesixty_get_feedback($analysisid)), 2);
        threesixty_get_feedback($analysisid_2);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(count(threesixty_get_feedback($analysisid_2)), 0);

    }

    public function test_mod_threesixty_is_completed() {
        $activityid = 1;
        $activityid_2 = 999;
        $userid = 1;
        $userid_2 = 999;
	
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertTrue(threesixty_is_completed($activityid, $userid));
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertFalse(threesixty_is_completed($activityid_2, $userid));
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertFalse(threesixty_is_completed($activityid, $userid_2));
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertFalse(threesixty_is_completed($activityid_2, $userid_2));

    }

    public function test_mod_threesixty_user_listing() {
        $activity = new object;
        $activity->id = 1;
        $activity_2 = new object;
        $activity_2->id = 999;
        $url = "test.html";
	
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(strlen(threesixty_user_listing($activity, $url)), 356);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(threesixty_user_listing($activity_2, $url), get_string('nousersfound', 'threesixty'));
    }

    public function test_mod_threesixty_selected_user_heading() {
        $user = new object();
        $user->id = 1;
        $courseid = 1;
        $url = "test.html";
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(strlen(threesixty_selected_user_heading($user, $courseid, $url)), 163);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(strlen(threesixty_selected_user_heading($user, $courseid, $url, false)), 124);
    }

    public function test_mod_threesixty_get_first_incomplete_competency() {
        $activityid = 1;
        $activityid_2 = 999;
        $userid = 1;
        $userid_2 = 2;
        $respondent = new object();
        $respondent->id = 1;
        $respondent_2 = new object();
        $respondent_2->id = 2;

        // ...activity complete show first page.
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(threesixty_get_first_incomplete_competency($activityid, $userid, null), 1);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(threesixty_get_first_incomplete_competency($activityid, $userid, $respondent), 1);
        // ...all skills have been scored, go to last page.
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(threesixty_get_first_incomplete_competency($activityid, $userid_2, null), 3);
        // ...partially complete show which page to display.
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(threesixty_get_first_incomplete_competency($activityid, $userid_2, $respondent_2), 2);

        // ...no responses exist show first page.
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(threesixty_get_first_incomplete_competency($activityid_2, $userid_2, $respondent_2), 1);

    }

    public function test_mod_threesixty_get_average_skill_scores() {
        $analysisid = 1;
        $analysisid_2 = 999;
        $respondenttype_2 = 999;

        $obj = new stdClass();
        $obj->score = '0.00000000000000000000';
        $obj->id = 6;
        // ...check format of a single result.
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(array_shift(threesixty_get_average_skill_scores($analysisid, 0, false)->records), $obj);

        // ...check the number of results.
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(count(threesixty_get_average_skill_scores($analysisid, 0, true)->records), 3);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(count(threesixty_get_average_skill_scores($analysisid, 0, false)->records), 6);

        // ...zero records if bad analysisid or respondenttype.
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(count(threesixty_get_average_skill_scores($analysisid_2, 0, false)->records), 0);
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual(count(threesixty_get_average_skill_scores($analysisid, $respondenttype_2, false)->records), 0);

        // ...check some numbers for different situations.
        $res = threesixty_get_average_skill_scores(1, false, false)->records;
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($res[1]->score, 4);
        $res = threesixty_get_average_skill_scores(1, 0, false)->records;
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($res[1]->score, 5);
        $res = threesixty_get_average_skill_scores(1, false, true)->records;
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($res[1]->score, 3.75);
        $res = threesixty_get_average_skill_scores(1, 0, true)->records;
	    /** @noinspection PhpUndefinedMethodInspection */
	    $this->assertEqual($res[1]->score, 4.5);

    }

}
