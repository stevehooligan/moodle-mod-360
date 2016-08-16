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
    // ...----------------------------------------------------------------------------------.

// TODO This file is possibly never used, but this needs confirming
// TODO Have hidden errors in this file using @noinspection, these should be removed as part of any recommission

// This function executes all the restore procedure about this mod.
function threesixty_restore_mods($mod, $restore) {

    global $DB;

    $status = true;

    // Get record from backup_ids.
	/** @noinspection PhpUndefinedFunctionInspection */
	$data = backup_getid($restore->backup_unique_code, $mod->modtype, $mod->id);

    if ($data) {
        // Now get completed xmlized object.
        $info = $data->info;
        // If necessary, write to restorelog and adjust date/time fields.
        if ($restore->course_startdateoffset) {
	        /** @noinspection PhpUndefinedFunctionInspection */
	        restore_log_date_changes('Three Sixty Diagnostic', $restore, $info['MOD']['#'], array('TIMECREATED', 'TIMEMODIFIED'));
        }

        // ...traverse_xmlize($info);                                                   //Debug.
        // ...print_object ($GLOBALS['traverse_array']);                                //Debug.
        // ...$GLOBALS['traverse_array']="";                                            //Debug.

        // Now, build the three sixty record structure.
	    $threesixty = isset($threesixty) ? $threesixty : new stdClass();
        $threesixty->course = $restore->course_id;
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $threesixty->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $threesixty->competenciescarried = backup_todb($info['MOD']['#']['COMPETENCIESCARRIED']['0']['#']);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $threesixty->requiredrespondents = backup_todb($info['MOD']['#']['REQUIREDRESPONDENTS']['0']['#']);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $threesixty->timecreated = backup_todb($info['MOD']['#']['TIMECREATED']['0']['#']);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $threesixty->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);

        // The structure is equal to the db, so insert the training diary.
        $newid = $DB->insert_record('threesixty', $threesixty);

        // Do some output.
        if (!defined('RESTORE_SILENTLY')) {
            echo "<li>" . get_string("modulename", "threesixty") . " \""
                     . format_string(stripslashes($threesixty->name), true) . "\"</li>";
        }
	    /** @noinspection PhpUndefinedFunctionInspection */
	    backup_flush(300);

        if ($newid) {
            // We have the newid, update backup_ids.
	        /** @noinspection PhpUndefinedFunctionInspection */
	        backup_putid($restore->backup_unique_code, $mod->modtype,
                             $mod->id, $newid);

            // Restore threesixty_competency - threesixty_skill restored from here.
            $status = $status && threesixty_competency_restore_mods($mod->id, $newid, $info, $restore);

            // If userinfo was selected, restore the values.
	        /** @noinspection PhpUndefinedFunctionInspection */
	        if (restore_userdata_selected($restore, 'threesixty', $mod->id)) {
                $status = $status && threesixty_analysis_restore_mods($mod->id, $newid, $info, $restore);
            }
        } else {
                $status = false;
        }
    } else {
            $status = false;
    }

    return $status;
}



// This function restores the threesixty competencies.
function threesixty_competency_restore_mods($old_threesixty_id, $new_threesixty_id, $info, $restore) {

    global $DB;

    $status = true;

    // Get the competencies array.
    if (isset($info['MOD']['#']['COMPETENCIES']['0']['#']['COMPETENCY'])) {
        $competencies = $info['MOD']['#']['COMPETENCIES']['0']['#']['COMPETENCY'];
    } else {
        $competencies = array();
    }

    // Iterate over competencies.
    for ($i = 0; $i < count($competencies); $i++) {
        $competency_info = $competencies[$i];
        // ...traverse_xmlize($competency_info);                                        //Debug.
        // ...print_object ($GLOBALS['traverse_array']);                                //Debug.
        // $GLOBALS['traverse_array']="";                                            //Debug.

        // We'll need this later!!
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $oldid = backup_todb($competency_info['#']['ID']['0']['#']);

        // Now, build the competency record structure.
        $competency = new object();
        $competency->activityid = $new_threesixty_id;
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $competency->name = backup_todb($competency_info['#']['NAME']['0']['#']);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $competency->description = backup_todb($competency_info['#']['DESCRIPTION']['0']['#']);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $competency->showfeedback = backup_todb($competency_info['#']['SHOWFEEDBACK']['0']['#']);

        // The structure is equal to the db, so insert the competencies.
        $newid = $DB->insert_record("threesixty_competency", $competency);

        // Do some output.
        if (($i+1) % 50 == 0) {
            if (!defined('RESTORE_SILENTLY')) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
            }
	        /** @noinspection PhpUndefinedFunctionInspection */
	        backup_flush(300);
        }
        if ($newid) {
            // We have the newid, update backup_ids.
	        /** @noinspection PhpUndefinedFunctionInspection */
	        backup_putid($restore->backup_unique_code, "threesixty_competency", $oldid, $newid);

                // Restore skills for this competency.
            $status = $status && threesixty_skill_restore_mods($oldid, $newid, $competency_info, $restore);

        } else {
            $status = false;
        }

    }

    return $status;
}

// This function restores the threesixty skills.
function threesixty_skill_restore_mods($old_competency_id, $new_competency_id, $info, $restore) {

    global $DB;

    $status = true;

    // Get the threesixty_skill array.
    if (isset($info['#']['SKILLS']['0']['#']['SKILL'])) {
        $skills = $info['#']['SKILLS']['0']['#']['SKILL'];
    } else {
        $skills = array();
    }

    // Iterate over threesixty_skill.
    for ($i = 0; $i < count($skills); $i++) {
        $skill_info = $skills[$i];
        // ...traverse_xmlize($skill_info);                                            //Debug.
        // ...print_object ($GLOBALS['traverse_array']);                               //Debug.
        // $GLOBALS['traverse_array']="";                                           //Debug.

        // We'll need this later!!
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $oldid = backup_todb($skill_info['#']['ID']['0']['#']);

        // Now, build the threesixty_skill record structure.
	    $skill = isset($skill) ? $skill : new stdClass();
        $skill->competencyid = $new_competency_id;
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $skill->name = backup_todb($skill_info['#']['NAME']['0']['#']);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $skill->description = backup_todb($skill_info['#']['DESCRIPTION']['0']['#']);

        // The structure is equal to the db, so insert the skill.
        $newid = $DB->insert_record("threesixty_skill", $skill);

        // Do some output.
        if (($i+1) % 50 == 0) {
            if (!defined('RESTORE_SILENTLY')) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
            }
	        /** @noinspection PhpUndefinedFunctionInspection */
	        backup_flush(300);
        }
        if ($newid) {
            // We have the newid, update backup_ids.
	        /** @noinspection PhpUndefinedFunctionInspection */
	        backup_putid($restore->backup_unique_code, "threesixty_skill", $oldid, $newid);
        } else {
            $status = false;
        }
    }

    return $status;
}



// This function restores the threesixty analyses.
function threesixty_analysis_restore_mods($old_threesixty_id, $new_threesixty_id, $info, $restore) {

    global $DB;

    $status = true;

    // Get the analyses array.
    if (isset($info['MOD']['#']['ANALYSES']['0']['#']['ANALYSIS'])) {
        $analyses = $info['MOD']['#']['ANALYSES']['0']['#']['ANALYSIS'];
    } else {
        $analyses = array();
    }

    // Iterate over analyses.
    for ($i = 0; $i < count($analyses); $i++) {
        $analysis_info = $analyses[$i];
        // ...traverse_xmlize($analysis_info);                                          //Debug.
        // ...print_object ($GLOBALS['traverse_array']);                                //Debug.
        // $GLOBALS['traverse_array']="";                                            //Debug.

        // We'll need this later!!
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $oldid = backup_todb($analysis_info['#']['ID']['0']['#']);

        // Now, build the analysis record structure.
        $analysis = new object();
        $analysis->activityid = $new_threesixty_id;
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $analysis->userid = backup_todb($analysis_info['#']['USERID']['0']['#']);

        // We have to recode the userid field.
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $user = backup_getid($restore->backup_unique_code, "user", $analysis->userid);
        if ($user) {
            $analysis->userid = $user->new_id;
        }

        // The structure is equal to the db, so insert the analyses.
            $newid = $DB->insert_record("threesixty_analysis", $analysis);

        // Do some output.
        if (($i+1) % 50 == 0) {
            if (!defined('RESTORE_SILENTLY')) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
            }
	        /** @noinspection PhpUndefinedFunctionInspection */
	        backup_flush(300);
        }
        if ($newid) {
            // We have the newid, update backup_ids.
	        /** @noinspection PhpUndefinedFunctionInspection */
	        backup_putid($restore->backup_unique_code, "threesixty_analysis", $oldid, $newid);

            // Restore carried comp and respondent for this analysis.
            $status = $status && threesixty_carried_comp_restore_mods($oldid, $newid, $analysis_info, $restore);
            $status = $status && threesixty_respondent_restore_mods($oldid, $newid, $analysis_info, $restore);
        } else {
            $status = false;
        }

    }

    return $status;
}


// This function restores the threesixty carried competencies.
function threesixty_carried_comp_restore_mods($old_analysis_id, $new_analysis_id, $info, $restore) {

    global $DB;

    $status = true;

    // Get the threesixty_carried_comp array.
    if (isset($info['#']['CARRIED_COMPS']['0']['#']['CARRIED_COMP'])) {
        $carried_comps = $info['#']['CARRIED_COMPS']['0']['#']['CARRIED_COMP'];
    } else {
        $carried_comps = array();
    }

    // Iterate over threesixty_carried_comp.
    for ($i = 0; $i < count($carried_comps); $i++) {
        $carried_comp_info = $carried_comps[$i];
        // ...traverse_xmlize($carried_comp_info);                                     //Debug.
        // ...print_object ($GLOBALS['traverse_array']);                               //Debug.
        // $GLOBALS['traverse_array']="";                                           //Debug.

        // We'll need this later!!
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $oldid = backup_todb($carried_comp_info['#']['ID']['0']['#']);

        // Now, build the threesixty_carried_comp record structure.
	    $carried_comp = isset($carried_comp) ? $carried_comp : new stdClass();
        $carried_comp->analysisid = $new_analysis_id;
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $carried_comp->competencyid = backup_todb($carried_comp_info['#']['COMPETENCYID']['0']['#']);

        // We have to recode the competencyid field.
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $competency = backup_getid($restore->backup_unique_code, "threesixty_competency", $carried_comp->competencyid);
        if ($competency) {
            $carried_comp->competencyid = $competency->new_id;
        }

        // The structure is equal to the db, so insert the carried_comp.
        $newid = $DB->insert_record("threesixty_carried_comp", $carried_comp);

        // Do some output.
        if (($i+1) % 50 == 0) {
            if (!defined('RESTORE_SILENTLY')) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
            }
	        /** @noinspection PhpUndefinedFunctionInspection */
	        backup_flush(300);
        }
        if ($newid) {
            // We have the newid, update backup_ids.
	        /** @noinspection PhpUndefinedFunctionInspection */
	        backup_putid($restore->backup_unique_code, "threesixty_carried_comp", $oldid, $newid);
        } else {
            $status = false;
        }
    }

    return $status;
}

// This function restores the threesixty respondent.
function threesixty_respondent_restore_mods($old_analysis_id, $new_analysis_id, $info, $restore) {

    global $DB;

    $status = true;
    // Restore any responses for self.
    if (isset($info['#']['RESPONDENTS']['0']['#']['SELF']['0'])) {
        $self = $info['#']['RESPONDENTS']['0']['#']['SELF']['0'];
        $status = threesixty_response_restore_mods(null, null, $self, $restore);
    }

    // Get the threesixty_respondent array.
    if (isset($info['#']['RESPONDENTS']['0']['#']['RESPONDENT'])) {
        $respondents = $info['#']['RESPONDENTS']['0']['#']['RESPONDENT'];
    } else {
        $respondents = array();
    }

    // Iterate over threesixty_respondent.
    for ($i = 0; $i < count($respondents); $i++) {
        $respondent_info = $respondents[$i];
        // ...traverse_xmlize($respondent_info);                                       //Debug.
        // ...print_object ($GLOBALS['traverse_array']);                               //Debug.
        // $GLOBALS['traverse_array']="";                                           //Debug.

        // We'll need this later!!
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $oldid = backup_todb($respondent_info['#']['ID']['0']['#']);

        // Now, build the threesixty_respondent record structure.
	    $respondent = isset($respondent) ? $respondent : new stdClass();
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $respondent->email = backup_todb($respondent_info['#']['EMAIL']['0']['#']);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $respondent->type = backup_todb($respondent_info['#']['TYPE']['0']['#']);
        $respondent->analysisid = $new_analysis_id;
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $respondent->uniquehash = backup_todb($respondent_info['#']['UNIQUEHASH']['0']['#']);

        // The structure is equal to the db, so insert the respondent.
        $newid = $DB->insert_record("threesixty_respondent", $respondent);

        // Do some output.
        if (($i+1) % 50 == 0) {
            if (!defined('RESTORE_SILENTLY')) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
            }
	        /** @noinspection PhpUndefinedFunctionInspection */
	        backup_flush(300);
        }
        if ($newid) {
            // We have the newid, update backup_ids.
	        /** @noinspection PhpUndefinedFunctionInspection */
	        backup_putid($restore->backup_unique_code, "threesixty_respondent", $oldid, $newid);

            // ...restore responses for this respondent.
            $status = threesixty_response_restore_mods($oldid, $newid, $respondent_info, $restore);
        } else {
            // ...print_error_log('Error: Could not write to respondent table during restore. This has most likely
            // ...happened because someone tried to restore a threesixty module with user info when that
            // ...user info already exists in that moodle instance. This is not allowed because the
            // ...uniquehash field is constrained to be unique in the database. The threesixty module was
            // ...not designed to be used in this way');.
            $status = false;
        }
    }

    return $status;
}


// This function restores the threesixty response.
function threesixty_response_restore_mods($old_respondent_id, $new_respondent_id, $info, $restore) {

    global $DB;
    $status = true;

        // Get the threesixty_response array.
    if (isset($info['#']['RESPONSES']['0']['#']['RESPONSE'])) {
            $responses = $info['#']['RESPONSES']['0']['#']['RESPONSE'];
    } else {
        $responses = array();
    }

    // Iterate over threesixty_response.
    for ($i = 0; $i < count($responses); $i++) {
        $response_info = $responses[$i];
        // ...traverse_xmlize($response_info);                                         // Debug.
        // ...print_object ($GLOBALS['traverse_array']);                               //Debug.
        // $GLOBALS['traverse_array']="";                                           //Debug.

        // We'll need this later!!
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $oldid = backup_todb($response_info['#']['ID']['0']['#']);

        // Now, build the threesixty_response record structure.
	    $response = isset($response) ? $response : new stdClass();
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $response->analysisid = backup_todb($response_info['#']['ANALYSISID']['0']['#']);
        $response->respondentid = $new_respondent_id;
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $response->timecompleted = backup_todb($response_info['#']['TIMECOMPLETED']['0']['#']);

        // We have to recode the analysisid field.
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $analysis = backup_getid($restore->backup_unique_code, "threesixty_analysis", $response->analysisid);
        if ($analysis) {
            $response->analysisid = $analysis->new_id;
        }

        // The structure is equal to the db, so insert the response.
        $newid = $DB->insert_record("threesixty_response", $response);

        // Do some output.
        if (($i+1) % 50 == 0) {
            if (!defined('RESTORE_SILENTLY')) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
            }
	        /** @noinspection PhpUndefinedFunctionInspection */
	        backup_flush(300);
        }
        if ($newid) {
            // We have the newid, update backup_ids.
	        /** @noinspection PhpUndefinedFunctionInspection */
	        backup_putid($restore->backup_unique_code, "threesixty_response", $oldid, $newid);

            // ...restore resp_comp and resp_skill.
            $status = $status && threesixty_response_skill_restore_mods($oldid, $newid, $response_info, $restore);
            $status = $status && threesixty_response_comp_restore_mods($oldid, $newid, $response_info, $restore);
        } else {
            $status = false;
        }
    }

    return $status;
}



// This function restores the threesixty response skill.
function threesixty_response_skill_restore_mods($old_response_id, $new_response_id, $info, $restore) {

    global $DB;
    $status = true;

    // Get the threesixty_response_skill array.
    if (isset($info['#']['RESPONSE_SKILLS']['0']['#']['RESPONSE_SKILL'])) {
            $response_skills = $info['#']['RESPONSE_SKILLS']['0']['#']['RESPONSE_SKILL'];
    } else {
        $response_skills = array();
    }

    // Iterate over threesixty_response_skill.
    for ($i = 0; $i < count($response_skills); $i++) {
        $response_skill_info = $response_skills[$i];
        // ...traverse_xmlize($response_skill_info);                                   //Debug.
        // ...print_object ($GLOBALS['traverse_array']);                               //Debug.
        // $GLOBALS['traverse_array']="";                                           //Debug.

        // We'll need this later!!
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $oldid = backup_todb($response_skill_info['#']['ID']['0']['#']);

        // Now, build the threesixty_response_skill record structure.
	    $response_skill = isset($response_skill) ? $response_skill : new stdClass();
        $response_skill->responseid = $new_response_id;
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $response_skill->skillid = backup_todb($response_skill_info['#']['SKILLID']['0']['#']);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $response_skill->score = backup_todb($response_skill_info['#']['SCORE']['0']['#']);

        // We have to recode the skillid field.
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $skill = backup_getid($restore->backup_unique_code, "threesixty_skill", $response_skill->skillid);
        if ($skill) {
            $response_skill->skillid = $skill->new_id;
        }

        // The structure is equal to the db, so insert the response.
        $newid = $DB->insert_record("threesixty_response_skill", $response_skill);

        // Do some output.
        if (($i+1) % 50 == 0) {
            if (!defined('RESTORE_SILENTLY')) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
            }
	        /** @noinspection PhpUndefinedFunctionInspection */
	        backup_flush(300);
        }
        if ($newid) {
            // We have the newid, update backup_ids.
	        /** @noinspection PhpUndefinedFunctionInspection */
	        backup_putid($restore->backup_unique_code, "threesixty_response_skill", $oldid, $newid);
        } else {
            $status = false;
        }
    }

    return $status;
}



// This function restores the threesixty response comp.
function threesixty_response_comp_restore_mods($old_response_id, $new_response_id, $info, $restore) {

    global $DB;
    $status = true;

    // Get the threesixty_response_comp array.
    if (isset($info['#']['RESPONSE_COMPS']['0']['#']['RESPONSE_COMP'])) {
        $response_comps = $info['#']['RESPONSE_COMPS']['0']['#']['RESPONSE_COMP'];
    } else {
        $response_comps = array();
    }

    // Iterate over threesixty_response_comp.
    for ($i = 0; $i < count($response_comps); $i++) {
        $response_comp_info = $response_comps[$i];
        // ...traverse_xmlize($response_comp_info);                                   //Debug.
        // ...print_object ($GLOBALS['traverse_array']);                              //Debug.
        // $GLOBALS['traverse_array']="";                                           //Debug.

        // We'll need this later!!
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $oldid = backup_todb($response_comp_info['#']['ID']['0']['#']);

        // Now, build the threesixty_response_comp record structure.
	    $response_comp = isset($response_comp) ? $response_comp : new stdClass();
        $response_comp->responseid = $new_response_id;
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $response_comp->competencyid = backup_todb($response_comp_info['#']['COMPETENCYID']['0']['#']);
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $response_comp->feedback = backup_todb($response_comp_info['#']['FEEDBACK']['0']['#']);

        // We have to recode the competencyid field.
	    /** @noinspection PhpUndefinedFunctionInspection */
	    $competency = backup_getid($restore->backup_unique_code, "threesixty_competency", $response_comp->competencyid);
        if ($competency) {
            $response_comp->competencyid = $competency->new_id;
        }

        // The structure is equal to the db, so insert the response.
        $newid = $DB->insert_record("threesixty_response_comp", $response_comp);

        // Do some output.
        if (($i+1) % 50 == 0) {
            if (!defined('RESTORE_SILENTLY')) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
            }
	        /** @noinspection PhpUndefinedFunctionInspection */
	        backup_flush(300);
        }
        if ($newid) {
            // We have the newid, update backup_ids.
	        /** @noinspection PhpUndefinedFunctionInspection */
	        backup_putid($restore->backup_unique_code, "threesixty_response_comp", $oldid, $newid);
        } else {
            $status = false;
        }
    }

    return $status;
}

function rewrite_url($restore, $log) {
    $url = $log->url;
    $patterns = array();
    $replaces = array();
    $fields = array('userid' => 'user', 'a' => 'threesixty',
                        'c' => 'threesixty_competency');

    foreach ($fields as $field => $table) {
        if (preg_match("/$field=([^&]*)/", $url, $m)) {
            $oldid = $m[1];
	        /** @noinspection PhpUndefinedFunctionInspection */
	        $backup = backup_getid($restore->backup_unique_code, $table, $oldid);
            if (isset($backup->new_id)) {
                $newid = $backup->new_id;
                $patterns[] = "/$field=$oldid/";
                $replaces[] = "$field=$newid";
            }
        }
    }

    return preg_replace($patterns, $replaces, $url);

}