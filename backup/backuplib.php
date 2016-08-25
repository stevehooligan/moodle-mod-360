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

    // This php script contains all the stuff to backup/restore
    // threesixty mods
    //
    // This is the "graphical" structure of the threesixty mod:
    //
    //       threesixty ------------------- threesixty_competency ------------ threesixty_skill
    //      (CL, pk->id)                     |  (CL, pk->id,   |                (CL, pk->id,
    //            |                          | fk->activityid) |             fk->competencyid)
    //            |                          |                 |                       |
    //            |                          |                 |                       |
    //  threesixty_analysis ----- threesixty_carried_comp      |                       |
    //      (UL, pk->id,              (UL, pk->id,             |                       |
    //     fk->activityid)           fk->competencyid,         |                       |
    //            |                  fk->analysisid)           |                       |
    //            |                                            |                       |
    //            |                                            |                       |
    //            |                                            |                       |
    //  threesixty_respondent -- threesixty_response ---- threesixty_response_comp     |
    //    (UL, pk->id,              (UL, pk->id,                (UL, pk->id,           |
    //     fk->analysisid)         fk->respondentid)         fk->responseid,           |
    //                                     |                fk->competencyid)          |
    //                                     |                                           |
    //                                     |                                           |
    //                                     |----------------------------- threesixty_response_skill
    //                                                                  (UL, pk->id, fk->responseid,
    //                                                                           fk->skillid)
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files)
    //
    //
    // ----------------------------------------------------------------------------------.

// TODO This file is possibly never used, but this needs confirming
// TODO Have hidden errors in this file using @noinspection, these should be removed as part of any recommission

function threesixty_backup_mods($bf, $preferences) {

    // global $CFG;
	global $DB;

    $status = true;

    // Iterate over threesixty table.
    $threesixties = $DB->get_records("threesixty", "course", $preferences->backup_course, "id");
    if ($threesixties) {
        foreach ($threesixties as $threesixty) {
	        /** @noinspection PhpUndefinedFunctionInspection */
	        if (backup_mod_selected($preferences, 'threesixty', $threesixty->id)) {
                $status = threesixty_backup_one_mod($bf, $preferences, $threesixty);
            }
        }
    }
    return $status;
}

function threesixty_backup_one_mod($bf, $preferences, $threesixty) {

    // global $CFG;
	global $DB;
	
    if (is_numeric($threesixty)) {
        $threesixty = $DB->get_record('threesixty', 'id', $threesixty);
    }

    // $status = true;

    // Start mod.
	/** @noinspection PhpUndefinedFunctionInspection */
	fwrite($bf, start_tag('MOD', 3, true));
    // Print threesixty data.
	/** @noinspection PhpUndefinedFunctionInspection */
	fwrite($bf, full_tag('ID', 4, false, $threesixty->id));
	/** @noinspection PhpUndefinedFunctionInspection */
	fwrite($bf, full_tag('MODTYPE', 4, false, "threesixty"));
	/** @noinspection PhpUndefinedFunctionInspection */
	fwrite($bf, full_tag('NAME', 4, false, $threesixty->name));
	/** @noinspection PhpUndefinedFunctionInspection */
	fwrite($bf, full_tag('COMPETENCIESCARRIED', 4, false, $threesixty->competenciescarried));
	/** @noinspection PhpUndefinedFunctionInspection */
	fwrite($bf, full_tag('REQUIREDRESPONDENTS', 4, false, $threesixty->requiredrespondents));
	/** @noinspection PhpUndefinedFunctionInspection */
	fwrite($bf, full_tag('TIMECREATED', 4, false, $threesixty->timecreated));
	/** @noinspection PhpUndefinedFunctionInspection */
	fwrite($bf, full_tag('TIMEMODIFIED', 4, false, $threesixty->timemodified));

    // Threesixty_competency should do call threesixty_skill.
    backup_threesixty_competency($bf, $preferences, $threesixty->id);

    // Only if preferences->backup_users != 2 (none users). Else, teachers entries will be included.
    if ($preferences->backup_users != 2) {
        // Threesixty_analysis also backs up rest of user level data.
        backup_threesixty_analysis($bf, $preferences, $threesixty->id);
    }

    // End mod.
	/** @noinspection PhpUndefinedFunctionInspection */
	$status = fwrite($bf, end_tag('MOD', 3, true));

    return $status;
}

// Backup threesixty competencies (executed from threesixty_backup_one_mod).
function backup_threesixty_competency($bf, $preferences, $threesixty) {
    // global $CFG;
	global $DB;

    $status = true;

    $competencies = $DB->get_records('threesixty_competency', 'activityid', $threesixty, 'id');
    // If there are competencies.
    if ($competencies) {
	    /** @noinspection PhpUndefinedFunctionInspection */
	    fwrite($bf, start_tag('COMPETENCIES', 4, true));

        // Iterate over each competency.
        foreach ($competencies as $competency) {
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, start_tag('COMPETENCY', 5, true));
	
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('ID', 6, false, $competency->id));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('ACTIVITYID', 6, false, $competency->activityid));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('NAME', 6, false, $competency->name));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('DESCRIPTION', 6, false, $competency->description));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('SHOWFEEDBACK', 6, false, $competency->showfeedback));

            backup_threesixty_skill($bf, $preferences, $competency->id);
	
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, end_tag('COMPETENCY', 5, true));
        }

        // Write end tag.
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $status = fwrite($bf, end_tag('COMPETENCIES', 4, true));
    }
    return $status;
}

// Backup threesixty skills (executed from backup_threesixty_competency).
function backup_threesixty_skill($bf, /** @noinspection PhpUnusedParameterInspection */
                                 $preferences, $competencyid) {
    // global $CFG;
	global $DB;

    $status = true;

    $skills = $DB->get_records('threesixty_skill', 'competencyid', $competencyid, 'id');
    // If there are skills.
    if ($skills) {
	    /** @noinspection PhpUndefinedFunctionInspection */
	    fwrite($bf, start_tag('SKILLS', 6, true));

        // Iterate over each skill.
        foreach ($skills as $skill) {
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, start_tag('SKILL', 7, true));
	
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('ID', 8, false, $skill->id));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('COMPETENCYID', 8, false, $skill->competencyid));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('NAME', 8, false, $skill->name));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('DESCRIPTION', 8, false, $skill->description));
	
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, end_tag('SKILL', 7, true));
        }

        // Write end tag.
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $status = fwrite($bf, end_tag('SKILLS', 6, true));
    }
    return $status;
}

// Backup threesixty analyses (executed from threesixty_backup_one_mod).
function backup_threesixty_analysis($bf, $preferences, $threesixty) {
    // global $CFG;
	global $DB;

    $status = true;

    $analyses = $DB->get_records('threesixty_analysis', 'activityid', $threesixty, 'id');
    // If there are analyses.
    if ($analyses) {
	    /** @noinspection PhpUndefinedFunctionInspection */
	    fwrite($bf, start_tag('ANALYSES', 4, true));

        // Iterate over each analysis.
        foreach ($analyses as $analysis) {
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, start_tag('ANALYSIS', 5, true));
	
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('ID', 6, false, $analysis->id));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('ACTIVITYID', 6, false, $analysis->activityid));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('USERID', 6, false, $analysis->userid));

            backup_threesixty_carried_comp($bf, $preferences, $analysis->id);
            backup_threesixty_respondent($bf, $preferences, $analysis->id);
	
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, end_tag('ANALYSIS', 5, true));
        }

        // Write end tag.
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $status = fwrite($bf, end_tag('ANALYSES', 4, true));

    }
    return $status;
}

// Backup threesixty carried comps (executed from backup_threesixty_analysis).
function backup_threesixty_carried_comp($bf, /** @noinspection PhpUnusedParameterInspection */
                                        $preferences, $analysisid) {
//    global $CFG;
	global $DB;

    $status = true;

    $carried_comps = $DB->get_records('threesixty_carried_comp', 'analysisid', $analysisid, 'id');
    // If there are carried_comps.
    if ($carried_comps) {
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $status = $status && fwrite($bf, start_tag('CARRIED_COMPS', 6, true));

        // Iterate over each carried_comp.
        foreach ($carried_comps as $carried_comp) {
	        /** @noinspection PhpUndefinedFunctionInspection */
	        $status = $status && fwrite($bf, start_tag('CARRIED_COMP', 7, true));
	
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('ID', 8, false, $carried_comp->id));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('ANALYSISID', 8, false, $carried_comp->analysisid));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('COMPETENCYID', 8, false, $carried_comp->competencyid));
	
	        /** @noinspection PhpUndefinedFunctionInspection */
	        $status = $status && fwrite($bf, end_tag('CARRIED_COMP', 7, true));
        }

        // Write end tag.
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $status = $status && fwrite($bf, end_tag('CARRIED_COMPS', 6, true));

    }
    return $status;
}

// Backup threesixty respondents (executed from backup_threesixty_analysis).
function backup_threesixty_respondent($bf, $preferences, $analysisid) {
    // global $CFG;
	global $DB;

    $status = true;

    $respondents = $DB->get_records('threesixty_respondent', 'analysisid', $analysisid, 'id');
    // Responses from self don't appear in the respondent table.
    // Check the response table to see if any self responses are made.
    $selfresponse = $DB->get_records_select('threesixty_response',
                    "respondentid IS NULL AND analysisid=$analysisid", 'id');

    // If there are respondents or a self response.
    if ($respondents || $selfresponse) {
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $status = $status && fwrite($bf, start_tag('RESPONDENTS', 6, true));

        if ($selfresponse) {
            // If their are self responses, create a SELF tag to contain them.
	        /** @noinspection PhpUndefinedFunctionInspection */
	        $status = $status && fwrite($bf, start_tag('SELF', 7, true));
            $status = $status && backup_threesixty_response($bf, $preferences, null, $analysisid);
	        /** @noinspection PhpUndefinedFunctionInspection */
	        $status = $status && fwrite($bf, end_tag('SELF', 7, true));
        }

        if ($respondents) {
            // Iterate over each respondent.
            foreach ($respondents as $respondent) {
	            /** @noinspection PhpUndefinedFunctionInspection */
	            $status = $status && fwrite($bf, start_tag('RESPONDENT', 7, true));
	
	            /** @noinspection PhpUndefinedFunctionInspection */
	            fwrite($bf, full_tag('ID', 8, false, $respondent->id));
	            /** @noinspection PhpUndefinedFunctionInspection */
	            fwrite($bf, full_tag('EMAIL', 8, false, $respondent->email));
	            /** @noinspection PhpUndefinedFunctionInspection */
	            fwrite($bf, full_tag('TYPE', 8, false, $respondent->type));
	            /** @noinspection PhpUndefinedFunctionInspection */
	            fwrite($bf, full_tag('ANALYSISID', 8, false, $respondent->analysisid));
	            /** @noinspection PhpUndefinedFunctionInspection */
	            fwrite($bf, full_tag('UNIQUEHASH', 8, false, $respondent->uniquehash));

                $status = $status && backup_threesixty_response($bf, $preferences, $respondent->id);
	
	            /** @noinspection PhpUndefinedFunctionInspection */
	            $status = $status && fwrite($bf, end_tag('RESPONDENT', 7, true));
            }
        }
        // Write end tag.
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $status = $status && fwrite($bf, end_tag('RESPONDENTS', 6, true));
    }

    return $status;
}

// Backup threesixty responses (executed from backup_threesixty_respondent).
function backup_threesixty_response($bf, $preferences, $respondentid, $analysisid=null) {
    // global $CFG;
	global $DB;

    $status = true;
    if ($respondentid !== null) {
        $responses = $DB->get_records('threesixty_response', 'respondentid', $respondentid, 'id');
    } else {
        $responses = $DB->get_records_select('threesixty_response', "respondentid IS NULL AND analysisid=$analysisid", 'id');
    }
    // If there are responses.
    if ($responses) {
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $status = fwrite($bf, start_tag('RESPONSES', 8, true));

        // Iterate over each response.
        foreach ($responses as $response) {
	        /** @noinspection PhpUndefinedFunctionInspection */
	        $status = $status && fwrite($bf, start_tag('RESPONSE', 9, true));
	
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('ID', 10, false, $response->id));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('ANALYSISID', 10, false, $response->analysisid));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('RESPONDENTID', 10, false, $response->respondentid));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('TIMECOMPLETED', 10, false, $response->timecompleted));

            $status = $status && backup_threesixty_response_comp($bf, $preferences, $response->id);
            $status = $status && backup_threesixty_response_skill($bf, $preferences, $response->id);
	
	        /** @noinspection PhpUndefinedFunctionInspection */
	        $status = $status && fwrite($bf, end_tag('RESPONSE', 9, true));
        }

        // Write end tag.
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $status = $status && fwrite($bf, end_tag('RESPONSES', 8, true));

    }
    return $status;
}

// Backup threesixty response competency (executed from backup_threesixty_response).
function backup_threesixty_response_comp($bf, /** @noinspection PhpUnusedParameterInspection */
                                         $preferences, $responseid) {
    // global $CFG;
	global $DB;

    $status = true;

    $response_comps = $DB->get_records('threesixty_response_comp', 'responseid', $responseid, 'id');
    // If there are response_comps.
    if ($response_comps) {
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $status = $status && fwrite($bf, start_tag('RESPONSE_COMPS', 10, true));

        // Iterate over each response_comp.
        foreach ($response_comps as $response_comp) {
	        /** @noinspection PhpUndefinedFunctionInspection */
	        $status = $status && fwrite($bf, start_tag('RESPONSE_COMP', 11, true));
	
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('ID', 12, false, $response_comp->id));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('RESPONSEID', 12, false, $response_comp->responseid));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('COMPETENCYID', 12, false, $response_comp->competencyid));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('FEEDBACK', 12, false, $response_comp->feedback));
	
	        /** @noinspection PhpUndefinedFunctionInspection */
	        $status = $status && fwrite($bf, end_tag('RESPONSE_COMP', 11, true));
        }
        // Write end tag.
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $status = $status && fwrite($bf, end_tag('RESPONSE_COMPS', 10, true));

    }
    return $status;
}

// Backup threesixty response skill (executed from backup_threesixty_response).
function backup_threesixty_response_skill($bf, /** @noinspection PhpUnusedParameterInspection */
                                          $preferences, $responseid) {
    // global $CFG;
	global $DB;

    $status = true;

    $response_skills = $DB->get_records('threesixty_response_skill', 'responseid', $responseid, 'id');
    // If there are response_skills.
    if ($response_skills) {
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $status = $status && fwrite($bf, start_tag('RESPONSE_SKILLS', 10, true));

        // Iterate over each response_skill.
        foreach ($response_skills as $response_skill) {
	        /** @noinspection PhpUndefinedFunctionInspection */
	        $status = $status && fwrite($bf, start_tag('RESPONSE_SKILL', 11, true));
	
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('ID', 12, false, $response_skill->id));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('RESPONSEID', 12, false, $response_skill->responseid));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('SKILLID', 12, false, $response_skill->skillid));
	        /** @noinspection PhpUndefinedFunctionInspection */
	        fwrite($bf, full_tag('SCORE', 12, false, $response_skill->score));
	
	        /** @noinspection PhpUndefinedFunctionInspection */
	        $status = $status && fwrite($bf, end_tag('RESPONSE_SKILL', 11, true));
        }

        // Write end tag.
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $status = $status && fwrite($bf, end_tag('RESPONSE_SKILLS', 10, true));
    }
    return $status;
}

// Return an array of info (name,value).
function threesixty_check_backup_mods($course, $user_data=false, $backup_unique_code, $instances=null) {
    if (!empty($instances) && is_array($instances) && count($instances)) {
        $info = array();
        foreach ($instances as $id => $instance) {
            $info += threesixty_check_backup_mods_instances($instance, $backup_unique_code);
        }
        return $info;
    }
    // First the course data.
    $info[0][0] = get_string('modulenameplural', 'threesixty');
    if ($ids = threesixty_ids($course)) {
        $info[0][1] = count($ids);
    } else {
        $info[0][1] = 0;
    }

    $info[1][0] = get_string('competenciesheading', 'threesixty');
    if ($ids = threesixty_competency_ids_by_course($course)) {
        $info[1][1] = count($ids);
    } else {
        $info[1][1] = 0;
    }

    $info[2][0] = get_string('skills', 'threesixty');
    if ($ids = threesixty_skill_ids_by_course($course)) {
        $info[2][1] = count($ids);
    } else {
        $info[2][1] = 0;
    }

    // Now, if requested, the user_data.
    if ($user_data) {
        $info[3][0] = get_string('analyses', 'threesixty');
        if ($ids = threesixty_analysis_ids_by_course ($course)) {
            $info[3][1] = count($ids);
        } else {
            $info[3][1] = 0;
        }
    }
    return $info;
}

// Return an array of info (name,value).
function threesixty_check_backup_mods_instances($instance, $backup_unique_code) {
    // First the course data.
    $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
    $info[$instance->id.'0'][1] = '';

    $info[$instance->id.'1'][0] = get_string('competenciesheading', 'threesixty');
    if ($ids = threesixty_competency_ids_by_instance($instance->id)) {
        $info[$instance->id.'1'][1] = count($ids);
    } else {
        $info[$instance->id.'1'][1] = 0;
    }

    $info[$instance->id.'2'][0] = get_string('skills', 'threesixty');
    if ($ids = threesixty_skill_ids_by_instance($instance->id)) {
        $info[$instance->id.'2'][1] = count($ids);
    } else {
        $info[$instance->id.'2'][1] = 0;
    }

    // Now, if requested, the user_data.
    if (!empty($instance->userdata)) {
        $info[$instance->id.'3'][0] = get_string('analyses', 'threesixty');
        if ($ids = threesixty_analysis_ids_by_instance ($instance->id)) {
            $info[$instance->id.'3'][1] = count($ids);
        } else {
            $info[$instance->id.'3'][1] = 0;
        }
    }
    return $info;
}

// INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE.

// Returns an array of threesixty ids.
function threesixty_ids ($course) {

    // global $CFG;
	global $DB;

    return $DB->get_records_sql ("SELECT a.id, a.course
                                 FROM {threesixty} a
                                 WHERE a.course = '$course'");
}

// Returns an array of competency ids.
function threesixty_competency_ids_by_course ($course) {

    // global $CFG;
	global $DB;

    return $DB->get_records_sql ("SELECT c.id , c.activityid
                                 FROM {threesixty_competency} c,
                                      {threesixty} a
                                 WHERE a.course = '$course' AND
                                       c.activityid = a.id");
}

// Returns an array of competency ids.
function threesixty_competency_ids_by_instance ($instanceid) {

    // global $CFG;
	global $DB;

    return $DB->get_records_sql ("SELECT c.id , c.activityid
                                FROM {threesixty_competency} c
                                WHERE c.activityid = $instanceid");
}

// Returns an array of skill ids.
function threesixty_skill_ids_by_course ($course) {

    // global $CFG;
	global $DB;

    return $DB->get_records_sql ("SELECT s.id , c.activityid
                                 FROM {threesixty_skill} s,
                                      {threesixty_competency} c,
                                      {threesixty} a
                                 WHERE a.course = '$course' AND
                                       c.activityid = a.id AND
                                       s.competencyid = c.id");
}

// Returns an array of skill ids.
function threesixty_skill_ids_by_instance ($instanceid) {

    // global $CFG;
	global $DB;

    return $DB->get_records_sql ("SELECT s.id , c.activityid
                                 FROM {threesixty_skill} s,
                                      {threesixty_competency} c
                                 WHERE s.competencyid = c.id AND
                                       c.activityid = $instanceid");
}

// Returns an array of analsysis ids.
function threesixty_analysis_ids_by_course ($course) {

    // global $CFG;
	global $DB;

    return $DB->get_records_sql ("SELECT a.id , a.activityid
                                 FROM {threesixty_analysis} a,
                                      {threesixty} t
                                 WHERE t.course = '$course' AND
                                       a.activityid = t.id");
}

// Returns an array of analysis ids.
function threesixty_analysis_ids_by_instance ($instanceid) {

    // global $CFG;
	global $DB;

    return $DB->get_records_sql ("SELECT a.id , a.activityid
                                FROM {threesixty_analysis} a
                                WHERE a.activityid = $instanceid");
}