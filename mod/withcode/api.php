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
require_once('../../config.php');
require_once('locallib.php');
require_once('lib.php');
require_once($CFG->dirroot . '/lib/gradelib.php');
global $DB;
$cmd = optional_param('cmd', '', PARAM_TEXT);
switch($cmd) {
	case 'showtemplatecode':
		$withcodeid = optional_param('withcodeid', -1, PARAM_INT);
		$section = optional_param('section', "", PARAM_TEXT);
		if($withcodeid < 0) {
			die('{"success":false, "message":"Invalid module id"}');
		}
		$withcode = $DB->get_record('withcode', array('id'=>$withcodeid));
		
		$cm = get_coursemodule_from_instance('withcode', $withcode->id, $withcode->course, 0, false, MUST_EXIST);
		$context = context_module::instance($cm->id);
		if(!has_capability('mod/withcode:view', $context, $USER->id)) {
			die('{"success":false, "message":"Permission denied"}');
		}
		$files = array();
		if($section == "try" | $section == "all") {
			$files['try_it.py'] = $withcode->codetry;
		}
		if($section == "debug" | $section == "all") {
			$files['debug_it.py'] = $withcode->codedebug;
		}
		if($section == "extend" | $section == "all") {
			$files['extend_it.py'] = $withcode->codeextend;
		}
		$snippet = new stdClass();
		$snippet->files = json_encode($files);
		withcode_show_snippet($snippet);
		
	break;
	
	case 'regrade':
	/// TODO: remove this option
		$withcodeid = optional_param('withcodeid', -1, PARAM_INT);
		if($withcodeid < 0) {
			die('{"success":false, "message":"Invalid module id"}');
		}
		$withcode = $DB->get_record('withcode', array('id'=>$withcodeid));
		$grades = grade_get_grades($withcode->course, 'mod', 'withcode', $withcode->id, 0);
		
		
		
		print_r($grades);
		
		$grades = $DB->get_records_sql('SELECT userid AS id, s.timecreated as dategraded, s.timecreated as datesubmitted, score_total AS rawgrade, userid FROM {withcode_snippet} AS s WHERE s.withcodeid=?', array($withcode->id));
		print_r($grades);
		
		withcode_grade_item_update($withcode, $grades);
	break;
	
	case 'share':
		$withcodeid = optional_param('withcodeid', -1, PARAM_INT);
		if($withcodeid < 0) {
			die('{"success":false, "message":"Invalid withcode id"}');	
		}
		$withcode = $DB->get_record('withcode', array('id'=>$withcodeid), 'course');
		$cm = get_coursemodule_from_instance('withcode', $withcodeid, $withcode->course, 0, false, MUST_EXIT);
		$context = context_module::instance($cm->id);
		if(!has_capability('mod/withcode:viewmycode', $context, $USER->id)) {
			die('{"success":false, "message":"Permission denied"}');
		}
		if($snippet = $DB->get_record('withcode_snippet', array('withcodeid'=>$withcodeid, 'userid'=>$USER->id))) {
			withcode_show_snippet($snippet);
		} else {
			die('{"success":false, "message":"No code submitted"}');
		}
		

	break;
	
	case 'showsnippet':
		$snippet = optional_param('snippet', -1, PARAM_INT);
		if($snippet < 0) {
			die('{"success":false, "message":"Invalid snippet id"}');	
		} else {
			$snippet = $DB->get_record('withcode_snippet', array('id'=>$snippet));
			$withcode = $DB->get_record('withcode', array('id'=>$snippet->withcodeid), 'course');		
			$cm = get_coursemodule_from_instance('withcode', $snippet->withcodeid, $withcode->course, 0, false, MUST_EXIT);
			$context = context_module::instance($cm->id);
			if(!(has_capability('mod/withcode:viewothercode', $context, $USER->id) || $USER->id == $snippet->userid)) {
				die('{"success":false, "message":"Permission denied"}');
			}
			withcode_show_snippet($snippet);
		}
	break;
	
	case 'get_progress':
	$group = optional_param('group', 0, PARAM_INT);
	$withcodeid = optional_param('withcodeid', -1, PARAM_INT);
	$since = optional_param('since', 0, PARAM_INT);
	$sortby = optional_param('sortby', "", PARAM_TEXT);
	$sortorder = optional_param('sortorder', "", PARAM_TEXT);
	
	if($sortorder == "desc") {
		$sortorder = "DESC";
	} else {
		$sortorder = "ASC";
	}
	
	$orderclause = "";
	switch($sortby) {
		case "firstname":
			$orderclause = " ORDER BY u.firstname " . $sortorder; 
			break;
		case "lastname":
			$orderclause = " ORDER BY u.lastname " . $sortorder; 
			break;
		case "lastrun":
			$orderclause = " ORDER BY s.timecreated " . $sortorder; 
			break;
		case "tryit":
			$orderclause = " ORDER BY s.score_try " . $sortorder; 
			break;
		case "debug":
			$orderclause = " ORDER BY s.score_debug " . $sortorder; 
			break;
		case "extend":
			$orderclause = " ORDER BY s.score_extend " . $sortorder; 
			break;
		case "total":
			$orderclause = " ORDER BY s.score_total " . $sortorder; 
			break;
	}
	
	if($group < 0) {
		die('{"success":false, "message":"Invalid group id"}');	
	}
	
	if($withcodeid <= 0) {
		die('{"success":false, "message":"Invalid withcode id"}');
	}

	
	$cm = get_coursemodule_from_id('withcode', $withcodeid, 0, 0, false, MUST_EXIST);
	$context = context_module::instance($cm->id);
	if(!has_capability('mod/withcode:viewothercode', $context, $USER->id)) {
		die('{"success":false, "message":"Permission denied"}');
	}

	$result = new stdClass();
	$result->success = true;

	
	$users = get_enrolled_users($context, 'mod/withcode:save', $group, 'u.id');
	$uids = array();
	foreach($users as $user) {
		$uids[] = $user->id;
	}
	$result->userids = $uids;
	$result->code = array();
	if(count($uids) > 0) {
	
		$uids = implode(",", $uids);
		$sql = 'SELECT u.id AS userid, u.firstname, u.lastname, s.id AS snippetid, s.withcodeid, s.timecreated, s.files, s.score_try, s.score_debug, s.score_extend, s.score_total FROM {withcode_snippet} AS s RIGHT OUTER JOIN {user} AS u ON u.id=s.userid WHERE u.id IN (' . $uids . ') AND (s.withcodeid = ' . $cm->instance . ' OR s.withcodeid IS NULL) AND (s.timecreated > ' . $since . ' OR s.timecreated IS NULL) ' . $orderclause;
		
		$snippets = $DB->get_records_sql($sql);
		
		foreach($snippets as $snippet) {
			$snippet->lastrun = withcode_get_time_diff($snippet->timecreated);
			$snippet->id = $snippet->snippetid;
			$result->code[] = $snippet;
			$url = $CFG->wwwroot . '/user/view.php?id=' . $snippet->userid . '&course=' . $cm->course;
			$snippet->firstname = '<a href="' . $url . '">' . $snippet->firstname . '</a>';
			$snippet->lastname = '<a href="' . $url . '">' . $snippet->lastname . '</a>';
			$snippet->timecreated = $snippet->timecreated ? $snippet->timecreated : 0;
			$snippet->score_try = $snippet->score_try ? $snippet->score_try : 0;
			$snippet->score_debug = $snippet->score_debug ? $snippet->score_debug : 0;
			$snippet->score_extend = $snippet->score_extend ? $snippet->score_extend : 0;
			$snippet->score_total = $snippet->score_total ? $snippet->score_total : 0;
			
			
		}
	}
	$result->lastchecked = time();
	echo(json_encode($result));
	
		

	break;
	
	case 'load':
		$cmid = optional_param('id', -1, PARAM_INT);
		if($cmid < 0) {
			die('{"success":false, "message":"unknown id"}');
		}
		
		$user = optional_param('user', $USER->id, PARAM_INT);
		$cm = get_coursemodule_from_id('withcode', $cmid, 0, false, MUST_EXIST);
		
		$context = context_module::instance($cmid);
		if(!has_capability($user == $USER->id?'mod/withcode:viewmycode':'mod/withcode:viewothercode', $context, $USER->id)) {
			die('{"success":false, "message":"Access denied"}');
		}
		
		$response = new stdClass();
		$response->success = true;
		
		$latest = $DB->get_records('withcode_snippet', array('withcodeid' => $cm->instance, 'userid' => $user), 'timecreated DESC', '*', 0, 1);
		if(count($latest) == 0) {
			$response->message = 'No code saved';
			
		} else {
			foreach($latest as $snippet) {
				$snippet->files = json_decode($snippet->files);
				break;
			}
			$d = time() - $snippet->timecreated;
			if($d < 60) 
				$response->message = "Loaded from $d seconds ago";
			else {
				$d /= 60;
				$d = round($d);
				if($d < 60) {
					$response->message = "Loaded from $d mins ago";
				} else {
					$d /= 60;
					$d = round($d);
					if($d < 24) {
						$response->message = "Loaded from $d hours ago";
					} else {
						$d /= 24;
						$d = round($d);
						if($d < 7) {
							$response->message = "Loaded from $d days ago";
						} else {
							$d /= 7;
							$d = round($d);
							if($d < 4) {
								$response->message = "Loaded from $d weeks ago";
							} else {
								$d /= 4;
								$d = round($d);
								if($d < 12) {
									$response->message = "Loaded from $d months ago";
								} else {
									$d /= 12;
									$d = round($d);
									$response->message = "Loaded from $d years ago";
								}
							}
						}
					}
				}
			}
			
			
		}
		$response->data = $latest;
		echo(json_encode($response));
	break;
	
	case 'save':
		$cmid = optional_param('id', -1, PARAM_INT);
		if($cmid < 0) {
			die('{"success":false, "message":"unknown id"}');
		} 
		
		
		$context = context_module::instance($cmid);
		if(!has_capability('mod/withcode:save', $context, $USER->id)) {
			die('{"success":false, "message":"Access denied"}');
		}
		
		
		$user = $USER->id;
		$cm = get_coursemodule_from_id('withcode', $cmid, 0, false, MUST_EXIST);
		
		
		
		$files = json_encode(array());
		if(isset($_POST['files']))
			$files = json_encode($_POST['files']);
		
		$snippet = new stdClass();
		
		$snippet->score_try = optional_param('score_try', 0, PARAM_INT);
		$snippet->score_debug = optional_param('score_debug', 0, PARAM_INT);
		$snippet->score_extend = optional_param('score_extend', 0, PARAM_INT);
		$snippet->score_total = optional_param('score_total', 0, PARAM_INT);
		$snippet->userid = $user;
		$snippet->withcodeid = $cm->instance;
		$snippet->timecreated = time();
		$snippet->files = $files;
		
		// sanity checks
		if($snippet->score_total != $snippet->score_try + $snippet->score_debug + $snippet->score_extend) {
			die('{"success":false, "message":"Cheat"}');
		}
		
		// check if current code already exists
		if($record = $DB->get_record('withcode_snippet', array('userid' => $snippet->userid, 'withcodeid'=>$snippet->withcodeid))) {
			$snippet->id = $record->id;
			$DB->update_record('withcode_snippet', $snippet);
		} else {
			$snippet->id = $DB->insert_record('withcode_snippet', $snippet);
		}
		$withcode = $DB->get_record('withcode', array('id'=>$snippet->withcodeid));
		withcode_update_grades($withcode, $snippet->userid);
		echo('{"success":true, "message":"Saved"}');
	break;
	default:
		echo('{"success": false, "message":"Unknown command"}');
	break;
}

//require_login($course, true, $cm);