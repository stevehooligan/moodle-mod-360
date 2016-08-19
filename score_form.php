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
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

global $CFG;

require_once($CFG->dirroot.'/lib/formslib.php');

class mod_threesixty_score_form extends moodleform {

    public function definition() {

        $mform =& $this->_form;
        $competency = $this->_customdata['competency'];
        $page = $this->_customdata['page'];
        $nbpages = $this->_customdata['nbpages'];

        $mform->addElement('hidden', 'a', $this->_customdata['a']);
        $mform->setType('a', PARAM_INT);
        $mform->addElement('hidden', 'code', $this->_customdata['code']);
        $mform->setType('code', PARAM_ALPHANUM);
        $mform->addElement('hidden', 'page', $this->_customdata['page']);
        $mform->setType('page', PARAM_INT);
        $mform->addElement('hidden', 'userid', $this->_customdata['userid']);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'typeid', $this->_customdata['typeid']);

        $mform->addElement('header', 'competency', format_string($competency->name));
        $mform->addElement('html', '<div class="competencydescription">'.format_text($competency->description).'</div>');
        $mform->addElement('html', '<div class="completionlegend"><p class="legendheading">'.
                get_string('legend:heading', 'threesixty').'</p>');
        $mform->addElement('html', '<ul><li>Level 1: '.get_string('legend:level1', 'threesixty').'</li>');
        $mform->addElement('html', '<li>Level 2: '.get_string('legend:level2', 'threesixty').'</li>');
        $mform->addElement('html', '<li>Level 3: '.get_string('legend:level3', 'threesixty').'</li>');
        $mform->addElement('html', '<li>Level 4: '.get_string('legend:level4', 'threesixty').'</li></ul></div>');

        if ($competency->skills and count($competency->skills) > 0) {
            foreach ($competency->skills as $skill) {
                $mform->addElement('html', '<div class="skillset">');
                if (strlen($skill->description)>0) {
                    $mform->addElement('html', '<div><span style="font-weight: bolder">'.format_string($skill->name).'</span> - '.format_string($skill->description).'</div>');
                }
                $elementname = "score_{$skill->id}";
                $radioarray = array();
                $radioarray[] = &$mform->createElement('radio', $elementname, '', get_string('notapplicable', 'threesixty'), 0);
                $radioarray[] = &$mform->createElement('radio', $elementname, '', '1', 1);
                $radioarray[] = &$mform->createElement('radio', $elementname, '', '2', 2);
                $radioarray[] = &$mform->createElement('radio', $elementname, '', '3', 3);
                $radioarray[] = &$mform->createElement('radio', $elementname, '', '4', 4);
                if(strlen($skill->description)>0) {
                    $mform->addGroup($radioarray, "radioarray_$skill->id");
                }
                else {
                    $mform->addGroup($radioarray, "radioarray_$skill->id", format_string($skill->name));
                }
                $mform->addElement('html', '</div>');
                if ($competency->locked) {
                    $mform->hardFreeze("radioarray_{$skill->id}");
                }
            }
        } else {
            $mform->addElement('html', get_string('noskills', 'threesixty'));
        }

        // ... if (1 == $competency->showfeedback and empty($this->_customdata['code'])) { .
        // Kat - allowed externals to leave feedback.
        if (1 == $competency->showfeedback) {
            $mform->addElement('textarea', 'feedback', get_string('feedback'), array('cols'=>'53', 'rows'=>'8'));
            if ($competency->locked) {
                $mform->hardFreeze('feedback');
            }
        }

        // Paging buttons.
        $buttonarray = array();
        if ($page > 1) {
            $buttonarray[] = &$mform->createElement('submit', 'previous', get_string('previous'));
        } else {
            $buttonarray[] = &$mform->createElement('submit', 'previous', get_string('previous'),
                                                   array('disabled'=>true));
        }
        if ($page < $nbpages) {
            $buttonarray[] = &$mform->createElement('submit', 'next', get_string('next'));
        } else {
            $buttonlabel = get_string('finishbutton', 'threesixty');
            if ($competency->locked) {
                $buttonlabel = get_string('closebutton', 'threesixty');
            }
            $buttonarray[] = &$mform->createElement('submit', 'finish', $buttonlabel);
        }

        $a = new object;
        $a->page = $page;
        $a->nbpages = $nbpages;

        // ...$mform->addGroup($buttonarray, 'buttonarray', '', ' ' . get_string('page', 'threesixty') .
        // ...          . ' ' . $a->page . ' ' . get_string('of', 'threesixty') .
        // ...                                         . ' ' . $a->nbpages . ' ');.
        $mform->addGroup($buttonarray, 'buttonarray', '', ' ' . get_string('xofy', 'threesixty', $a) . ' ');
        $mform->closeHeaderBefore('buttonarray');
    }
}
