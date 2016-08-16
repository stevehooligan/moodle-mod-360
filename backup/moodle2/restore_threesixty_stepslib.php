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
 * Define all the restore steps that will be used by the restore_threesixty_activity_task
 *
 * @package   mod_threesixty
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete threesixtyment structure for restore, with file and id annotations
 *
 * @package   mod_threesixty
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_threesixty_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the structure of the restore workflow
     * @return restore_path_element $structure
     */
    protected function define_structure() {

        $paths = array();
        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $paths[] = new restore_path_element('threesixty', '/activity/threesixty');
        $paths[] = new restore_path_element('threesixty_competency', '/activity/threesixty/competencies/competency');
        $paths[] = new restore_path_element('threesixty_analysis', '/activity/threesixty/analysis_table/analysis');
        $paths[] = new restore_path_element('threesixty_skill', '/activity/threesixty/competencies/competency/skills/skill');
        $paths[] = new restore_path_element('threesixty_response_skill', '/activity/threesixty/competencies/competency/skills/skill/response_skill_table/response_skill');
        $paths[] = new restore_path_element('threesixty_response_comp',
                '/activity/threesixty/analysis_table/analysis/respondents/respondent/responses/response/response_comp_table/response_comp');
        $paths[] = new restore_path_element('threesixty_carried_comp', '/activity/threesixty/analysis_table/analysis/carried_comp_table/carried_comp');

        if ($userinfo) {
            $paths[] = new restore_path_element('threesixty_respondent', '/activity/threesixty/analysis_table/analysis/respondents/respondent');
            $paths[] = new restore_path_element('threesixty_response', '/activity/threesixty/analysis_table/analysis/respondents/respondent/responses/response');

        }
        // ...$paths[] = new restore_path_element('threesixty_plugin_config', '/activity/threesixty/plugin_configs/plugin_config');.

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process an threesixty restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_threesixty($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('threesixty', $data);

        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process an threesixty_competency restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_threesixty_competency($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->activityid = $this->get_new_parentid('threesixty');

        $newitemid = $DB->insert_record('threesixty_competency', $data);
        $this->set_mapping('threesixty_competency', $oldid, $newitemid);
    }

    /**
     * Process an threesixty_analysis restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_threesixty_analysis($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->activityid = $this->get_new_parentid('threesixty');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('threesixty_analysis', $data);
        $this->set_mapping('threesixty_analysis', $oldid, $newitemid);
    }

    /**
     * Process an threesixty_skill restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_threesixty_skill($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->competencyid = $this->get_new_parentid('threesixty_competency');

        $newitemid = $DB->insert_record('threesixty_skill', $data);
        $this->set_mapping('threesixty_skill', $oldid, $newitemid);
    }

    /**
     * Process an threesixty_respondent restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_threesixty_respondent($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->analysisid = $this->get_new_parentid('threesixty_analysis');

        $newitemid = $DB->insert_record('threesixty_respondent', $data);
        $this->set_mapping('threesixty_respondent', $oldid, $newitemid);
    }

    /**
     * Process an threesixty_response restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_threesixty_response($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->analysisid = $this->get_new_parentid('threesixty_analysis');
        $data->respondentid = $this->get_new_parentid('threesixty_respondent');

        $data->timecompleted = $this->apply_date_offset($data->timecompleted);

        $newitemid = $DB->insert_record('threesixty_response', $data);
        $this->set_mapping('threesixty_response', $oldid, $newitemid);
    }

    /**
     * Process an threesixty_response_skill restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_threesixty_response_skill($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->responseid = $this->get_new_parentid('threesixty_response');
        $data->skillid = $this->get_new_parentid('threesixty_skill');

        $newitemid = $DB->insert_record('threesixty_response_skill', $data);
        $this->set_mapping('threesixty_response_skill', $oldid, $newitemid);
    }

    /**
     * Process an threesixty_response_comp restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_threesixty_response_comp($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->responseid = $this->get_new_parentid('threesixty_response');
        $data->competencyid = $this->get_new_parentid('threesixty_competency');

        $newitemid = $DB->insert_record('threesixty_response_comp', $data);
        $this->set_mapping('threesixty_response_comp', $oldid, $newitemid);
    }

    /**
     * Process an threesixty_carried_comp restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_threesixty_carried_comp($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->analysisid = $this->get_new_parentid('threesixty_analysis');
        $data->competencyid = $this->get_new_parentid('threesixty_competency');

        $newitemid = $DB->insert_record('threesixty_carried_comp', $data);
        $this->set_mapping('threesixty_carried_comp', $oldid, $newitemid);
    }


    /**
     * Once the database tables have been fully restored, restore the files
     * @return void
     */
    protected function after_execute() {
        // ...$this->add_related_files('mod_threesixty', 'intro', null);.
    }
}
