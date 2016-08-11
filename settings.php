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


/** @var navigation_node $settings ??? */
$settings->add(new admin_setting_configtextarea('threesixty_selftypes',
        get_string('setting:selftypes', 'threesixty'),
        get_string('setting:selftypesdesc', 'threesixty'),
        get_string('setting:selftypesdefault', 'threesixty'), PARAM_TEXT, 60, 8));
$settings->add(new admin_setting_configtextarea('threesixty_respondenttypes',
        get_string('setting:respondenttypes', 'threesixty'),
        get_string('setting:respondenttypesdesc', 'threesixty'),
        get_string('setting:respondenttypesdefault', 'threesixty'), PARAM_TEXT, 60, 8));