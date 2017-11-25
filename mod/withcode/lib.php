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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package withcode
 * @copyright 2016 pddring blog.withcode.uk
 *
 * @author Pete Dring
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 require_once('locallib.php');

function withcode_add_instance($withcode) {
	global $DB;
	
	$withcode->timecreated = time();
	$withcode->timemodified = time();
	return $DB->insert_record('withcode', $withcode);
}

function withcode_supports($feature) {
    switch($feature) {
        case FEATURE_GRADE_HAS_GRADE:         return true; 
        default: return null;
    }
}

function withcode_grade_item_update($withcode, $grades=NULL){
	$params = array('itemname'=>$withcode->name);
	$max_scores = withcode_get_max_scores($withcode);
	$params['gradetype'] = GRADE_TYPE_VALUE;
	
	$params['grademax']  = $max_scores->max_total;
	$params['grademin']  = 0;
	
	
	if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }
 
    return grade_update('mod/withcode', $withcode->course, 'mod', 'withcode', $withcode->id, 0, $grades, $params);
}

function withcode_update_grades($withcode, $userid=0, $nullifnone=true){
	global $DB;
//	$gradebookgrades = grade_get_grades($withcode->course, 'mod', 'withcode', $withcode->id, $userid);
	if ($grades = $DB->get_records_sql('SELECT userid AS id, s.timecreated as dategraded, s.timecreated as datesubmitted, score_total AS rawgrade, userid FROM {withcode_snippet} AS s WHERE s.withcodeid=?', array($withcode->id))) {
		
		withcode_grade_item_update($withcode, $grades);
 
    } else if ($userid and $nullifnone) {
        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = NULL;
        withcode_grade_item_update($withcode, $grade);
 
    } else {
        withcode_grade_item_update($withcode);
    }
}

function withcode_reset_gradebook($courseid, $type='') {
	global $CFG, $DB;
 
    $activities = $DB->get_records('withcode', array('course'=>$courseid));
 
    foreach ($activities as $activity) {
        withcode_grade_item_update($activity, 'reset');
    }
}


function withcode_update_instance($withcode) {
	global $DB;
	$withcode->timemodified = time();
	$withcode->id = $withcode->instance;
	
	$withcode->desctry = $withcode->desctry['text'];
	$withcode->descdebug = $withcode->descdebug['text'];
	$withcode->descextend = $withcode->descextend['text'];
	
	withcode_grade_item_update($withcode);
	
	return $DB->update_record('withcode', $withcode);
}

function withcode_delete_instance($id) {
	global $DB;
	if (!$mod = $DB->get_record('withcode', array('id' => $id))) {
        return false;
    }
	$result = true;
	
    if (!$DB->delete_records('withcode', array('id' => $id))) {
        $result = false;
    }
	return $result;
}