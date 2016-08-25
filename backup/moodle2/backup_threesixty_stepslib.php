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
 * Define all the backup steps that will be used by the backup_threesixty_activity_task
 *
 * @package   mod_threesixty
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// ...defined('MOODLE_INTERNAL') || die();.

/**
 * Define the complete choice structure for backup, with file and id annotations
 *
 * @package   mod_threesixty
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
    // ---------------------------------------------------------------------------------- !!!


class backup_threesixty_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define the structure for the threesixty activity
     * @return backup_nested_element the $activitystructure wrapped by the common 'activity' element
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.

        $threesixty = new backup_nested_element('threesixty', array('id'), array(
                                                'course', 'name', 'competenciescarried',
                                                'requiredrespondents', 'timecreated', 'timemodified'));

        $competencies = new backup_nested_element('competencies');
        $competency = new backup_nested_element('competency', array('id'), array(
                                                'name', 'description', 'showfeedback', 'sortorder'));

        $analysis_table = new backup_nested_element('analysis_table');
        $analysis = new backup_nested_element('analysis', array('id'), array('userid'));

        $skills = new backup_nested_element('skills');
        $skill = new backup_nested_element('skill', array('id'), array('name', 'description', 'sortorder'));

        $respondents = new backup_nested_element('respondents');
        $respondent = new backup_nested_element('respondent', array('id'), array('email', 'type', 'uniquehash'));

        $responses = new backup_nested_element('responses');
        $response = new backup_nested_element('response', array('id'), array('timecompleted'));

        $response_skill_table = new backup_nested_element('response_skill_table');
        $response_skill = new backup_nested_element('response_skill', array('id'), array('score'));

        $carried_comp_table = new backup_nested_element('carried_comp_table');
        $carried_comp = new backup_nested_element('carried_comp');

        $response_comp_table = new backup_nested_element('response_comp_table');
        $response_comp = new backup_nested_element('response_comp');

        // Build the tree.
        $threesixty->add_child($competencies);
        $threesixty->add_child($analysis_table);

        $competencies->add_child($competency);
        $competency->add_child($skills);

        $analysis_table->add_child($analysis);
        $analysis->add_child($respondents);
        $analysis->add_child($carried_comp_table);

        $skills->add_child($skill);
        $skill->add_child($response_skill_table);

        $respondents->add_child($respondent);
        $respondent->add_child($responses);

        $responses->add_child($response);
        $response->add_child($response_comp_table);

        $response_skill_table->add_child($response_skill);

        $carried_comp_table->add_child($carried_comp);
        $response_comp_table->add_child($response_comp);

        // Define sources.
        $threesixty->set_source_table('threesixty', array('id' => backup::VAR_ACTIVITYID, 'course' => backup::VAR_COURSEID));
        $competency->set_source_table('threesixty_competency', array('activityid' => backup::VAR_ACTIVITYID));
        $analysis->set_source_table('threesixty_analysis', array('activityid' => backup::VAR_ACTIVITYID));

        $skill->set_source_table('threesixty_skill', array('competencyid' => backup::VAR_PARENTID));
        $response_skill->set_source_table('threesixty_response_skill',
                array('responseid' => backup::VAR_PARENTID, 'skillid' => backup::VAR_PARENTID));
        $carried_comp->set_source_table('threesixty_carried_comp',
                array('analysisid' => backup::VAR_PARENTID, 'competencyid' => backup::VAR_PARENTID));
        $response_comp->set_source_table('threesixty_response_comp',
                array('responseid' => backup::VAR_PARENTID, 'competencyid' => backup::VAR_PARENTID));

        if ($userinfo) {
            $respondent->set_source_table('threesixty_respondent', array('analysisid' => backup::VAR_PARENTID));
            $response->set_source_table('threesixty_response',
                    array('analysisid' => backup::VAR_PARENTID, 'respondentid' => backup::VAR_PARENTID));
        }

        // Define id annotations.
        $analysis->annotate_ids('user', 'userid');

        // Define file annotations.

        // Return the root element (threesixty), wrapped into standard activity structure.
        return $this->prepare_activity_structure($threesixty);
    }
}
