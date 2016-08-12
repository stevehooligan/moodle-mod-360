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

require_once('locallib.php');

/**
 * Library of functions and constants for module threesixty
 * This file should have two well differenced parts:
 *   - All the core Moodle functions, neeeded to allow
 *     the module to work integrated in Moodle.
 *   - All the threesixty specific functions, needed
 *     to implement all the module logic. Please, note
 *     that, if the module become complex and this lib
 *     grows a lot, it's HIGHLY recommended to move all
 *     these module specific functions to a new php file,
 *     called "locallib.php" (see forum, quiz...). This will
 *     help to save some memory when Moodle is performing
 *     actions across all modules.
 */

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $threesixty An object from the form in mod_form.php
 * @return int The id of the newly inserted threesixty record
 */
function threesixty_add_instance($threesixty) {
    global $DB;
    $threesixty->timecreated = time();
    $threesixty->timemodified = $threesixty->timecreated;

    // You may have to add extra stuff in here.

    return $DB->insert_record('threesixty', $threesixty);
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $threesixty An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function threesixty_update_instance($threesixty) {

    global $DB;

    $threesixty->timemodified = time();
    $threesixty->id = $threesixty->instance;

    // You may have to add extra stuff in here .

    return $DB->update_record('threesixty', $threesixty);
}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function threesixty_delete_instance($id) {

    global $DB;

    if (!$threesixty = $DB->get_record('threesixty', array('id' => $id))) {
        return false;
    }

    $transaction = $DB->start_delegated_transaction();

    // Delete all competencies and skills.
    if ($competencies = $DB->get_records('threesixty_competency', array('activityid' => $id), $fields='id')) {
        foreach ($competencies as $competency) {
            if (!threesixty_delete_competency($competency->id, true)) {
                $transaction->rollback(new Exception());
                return false;
            }
        }
    }

    // Delete all analysis records.
    if ($analyses = $DB->get_records('threesixty_analysis', array('activityid' => $id))) {
        foreach ($analyses as $analysis) {
            if (!threesixty_delete_analysis($analysis->id, true)) {
                $transaction->rollback(new Exception());
                return false;
            }
        }
    }

    if (!$DB->delete_records('threesixty', array('id' => $threesixty->id))) {
        $transaction->rollback(new Exception());
        return false;
    }

    $transaction->allow_commit();
    return true;
}

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function threesixty_supports($feature) {
    switch($feature) {
        case FEATURE_BACKUP_MOODLE2:          return true;

        default: return null;
    }
}


/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param $course
 * @param $user
 * @param $mod
 * @param $threesixty
 * @return null
 * @todo Finish documenting this function
 */
// TODO: implement this function, because I hope it never gets used
function threesixty_user_outline($course, $user, $mod, $threesixty) {
    return true;
	// was: return $return;
}


/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param $course
 * @param $user
 * @param $mod
 * @param $threesixty
 * @return bool
 * @todo Finish documenting this function
 */
function threesixty_user_complete($course, $user, $mod, $threesixty) {
    return true;
}


/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in threesixty activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param $course
 * @param $isteacher
 * @param $timestart
 * @return bool
 * @todo Finish documenting this function
 */
function threesixty_print_recent_activity($course, $isteacher, $timestart) {
    return false;  //  True if anything was printed, otherwise false.
}


/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function threesixty_cron () {
    return true;
}


/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of threesixty. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $threesixtyid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function threesixty_get_participants($threesixtyid) {
    return false;
}


/**
 * This function returns if a scale is being used by one threesixty
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $threesixtyid ID of an instance of this module
 * @param $scaleid
 * @return mixed
 * @todo Finish documenting this function
 */
function threesixty_scale_used($threesixtyid, $scaleid) {
    $return = false;

    // ....$rec = $DB->get_record("threesixty","id","$threesixtyid","scale","-$scaleid");.
    //
    // ...if (!empty($rec) && !empty($scaleid)) {.
    // ...$return = true;.
    // ...}.

    return $return;
}


/**
 * Checks if scale is being used by any instance of threesixty.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any threesixty
 */
function threesixty_scale_used_anywhere($scaleid) {
	global $DB;
    if ($scaleid and $DB->record_exists('threesixty', 'grade')) {
        return true;
    } else {
        return false;
    }
}
