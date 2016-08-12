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

class mod_threesixty_carryover_form extends moodleform {

    public function definition() {

        $mform =& $this->_form;
        $complist = $this->_customdata['complist'];
        $nbcarried = $this->_customdata['nbcarried'];

        $mform->addElement('hidden', 'a', $this->_customdata['a']);
        $mform->setType('a', PARAM_INT);
        $mform->addElement('hidden', 'section', 'carryover');
        $mform->setType('section', PARAM_ALPHA);
        $mform->addElement('hidden', 'userid', $this->_customdata['userid']);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'nbcarried', $nbcarried);
        $mform->setType('nbcarried', PARAM_INT);

        $mform->addElement('header', 'carryover', get_string('carryoverheading', 'threesixty'));

        $mform->addElement('html', get_string('carryoverexplanation', 'threesixty', $nbcarried));

        for ($i=0; $i < $nbcarried; $i++) {
            $mform->addElement('select', "comp$i", ($i + 1) . ':', $complist);
        }

        $mform->addElement('html', get_string('carryovernote', 'threesixty', $nbcarried));

        $this->add_action_buttons();
    }

    // Couldn't access "nbcasrried" protected value directly.
    public function getnbcarried() {
        return $this->_customdata['nbcarried'];
    }

}
