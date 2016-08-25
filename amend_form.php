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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // ...It must be included from a Moodle page.
}

/** @noinspection PhpUndefinedVariableInspection */
require_once($CFG->dirroot.'/lib/formslib.php');

class mod_threesity_amend_form extends moodleform {

    public function definition() {

        $mform =& $this->_form;
        $skills = $this->_customdata['skillnames'];

        $mform->addElement('hidden', 'a', $this->_customdata['a']);
        $mform->setType('a', PARAM_INT);
        $mform->addElement('hidden', 'userid', $this->_customdata['userid']);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'typeid', $this->_customdata['typeid']);
        $mform->setType('typeid', PARAM_INT);

        // ...Seems unneeded. Leaving it here just in case it was used somewhere.
        // ...$radioarray = array();.
        // ...$radioarray[] = &$mform->createElement('radio', 'score_dummy', '', '', 0, 'class="radioarray_dummy"');.
        // ...$mform->addGroup($radioarray, "radioarray_dummy", '');.

        $competency = new object();
        $competency->skills = false;

        if ($skills and count($skills) > 0) {
            $curcompetency = 0;
            foreach ($skills as $skill) {
                if ($curcompetency != $skill->competencyid) {
                    $mform->addElement(
                    	'html',
                    	'<br /><br />'.
                    	'<div class="compheader">'.
                    		'<span class="complabel">'.format_string($skill->competencyname).'</span>'.
                    	'</div>'.
                    	'<div class="clear">&nbsp;</div>');
                    $curcompetency = $skill->competencyid;
                }

                $mform->addElement('html', '<div class="skillset">');
                $elementname = "score_{$skill->id}";
                $radioarray = array();
                $radioarray[] = &$mform->createElement('radio', $elementname, '', get_string('notapplicable', 'threesixty'), 0);
                $radioarray[] = &$mform->createElement('radio', $elementname, '', '1', 1);
                $radioarray[] = &$mform->createElement('radio', $elementname, '', '2', 2);
                $radioarray[] = &$mform->createElement('radio', $elementname, '', '3', 3);
                $radioarray[] = &$mform->createElement('radio', $elementname, '', '4', 4);
                $skillname = "<div class='skillname'>".format_string($skill->skillname)."</div>";
                $mform->addGroup($radioarray, "radioarray_$skill->id", $skillname);
                $mform->addElement('html', '</div>');
            }
        }
        $this->add_action_buttons();
    }
}
