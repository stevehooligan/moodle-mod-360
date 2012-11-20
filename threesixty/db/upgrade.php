<?php

function xmldb_threesixty_upgrade($oldversion = 0) {

    global $CFG, $THEME, $DB;

    $result = true;
    if ($result && $oldversion < 2012102301) {
        //Add a display order column for the competency table.
        $comptable = new xmldb_table('threesixty_competency');
        $field = new xmldb_field('sortorder');
        $field->setattributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '999', 'showfeedback');
        if (!add_field($comptable, $field)) {
            $result = false;
        }
        reorder_competencies();
        $skilltable = new xmldb_table('threesixty_skill');
        $field->previous = 'description';
        if (!add_field($skilltable, $field)) {
            $result = false;
        }
        //Update the existing competency data.
        reorder_skills();
    }

    return $result;
}

function reorder_competencies() {
    global $CFG, $DB;
    //$sql = "SELECT * FROM ".$CFG->prefix."threesixty_competency ORDER BY activityid, id";
    if ($competencies = $DB->get_records("threesixty_competency", '', "activityid")) {
        $activityid = 0;
        $nextposition = 0;
        foreach ($competencies as $competency) {
            if ($activityid != $competency->activityid) {
                $nextposition = 0;
                $activityid = $competency->activityid;
            }
            $competency->sortorder = $nextposition;
            $nextposition++;
            $DB->update_record("threesixty_competency", $competency);
        }
    }
}

function reorder_skills() {
    global $CFG, $DB;
    //$sql = "SELECT * FROM ".$CFG->prefix."threesixty_skill ORDER BY competencyid, id";
    if ($skills = $DB->get_records('threesixty_skill', '', 'competencyid')) {
        $competencyid = 0;
        $nextposition = 0;
        foreach ($skills as $skill) {

            if ($competencyid != $skill->competencyid) {
                $nextposition = 0;
                $competencyid = $skill->competencyid;
            }
            $skill->sortorder = $nextposition;
            $nextposition++;
            $DB->update_record("threesixty_skill", $skill);
        }
    }
}