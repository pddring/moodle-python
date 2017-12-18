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
require_once($CFG->dirroot . '/mod/withcode/lib.php');
require_once($CFG->dirroot . '/mod/withcode/locallib.php');


$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/withcode/styles.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/withcode/withcode.css'));
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/withcode/js/ace/ace.js'));
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/withcode/js/skulpt/skulpt.min.js'));
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/withcode/js/skulpt/skulpt-stdlib.js'));
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/withcode/js/lib.js'));



require_once('editorlib.php');

$cmid = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('withcode', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$withcode = $DB->get_record('withcode', array('id' => $cm->instance));

$files = array('try_it.py' => '', 'debug_it.py' => '', 'extend_it.py'=>'');
if(strlen($withcode->codetry) > 0) {
	$files['try_it.py'] = $withcode->codetry;
}
if(strlen($withcode->codedebug) > 0) {
	$files['debug_it.py'] = $withcode->codedebug;
}
if(strlen($withcode->codeextend) > 0) {
	$files['extend_it.py'] = $withcode->codeextend;
}

$tests = array();
if(strlen($withcode->testtry) > 0) {
	$tests['try_it.py'] = $withcode->testtry;
}
if(strlen($withcode->testdebug) > 0) {
	$tests['debug_it.py'] = $withcode->testdebug;
}
if(strlen($withcode->testextend) > 0) {
	$tests['extend_it.py'] = $withcode->testextend;
}
$PAGE->requires->js_function_call('withcode.init', array($files, $tests, $cmid));

 
require_login($course, true, $cm);
$PAGE->set_url('/mod/withcode/view.php', array('id' => $cm->id));
$PAGE->set_title(get_string('modulename', 'withcode'));
$PAGE->set_heading($cm->name);

$context = context_module::instance($cmid);
require_capability('mod/withcode:view', $context, $USER->id);




echo($OUTPUT->header());

if(get_config('withcode', 'linkfontawesome')) {
	echo('<link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">');
}
if(get_config('withcode', 'linkbootstrap')) {
	echo('<script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>');
	echo('<link rel="stylesheet" type="text/stylesheet"href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">');
}


if(has_capability('mod/withcode:viewothercode', $context, $USER->id)) {
	$max_scores = withcode_get_max_scores($withcode); 
	
	echo('<h3><button type="button" class="btn btn-info" data-toggle="collapse" data-target="#submissions"><i class="fa fa-sort-down"></i></button> Manage submissions</h3>');
	

	
	echo('<div id="submissions" class="collapse">');
	
	$gmode = groups_get_activity_groupmode($cm);
	if($gmode == NOGROUPS) {
		$groups = array(0=>(object)array('id'=>0, 'name' => 'All students'));
	} else {
		$groups = groups_get_all_groups($COURSE->id);
	}
	?>
    <div id="tabs_groups">
    <ul class="nav nav-tabs">
    <?php
	foreach($groups as $group) {
		echo('<li class="nav-item"><a class="nav-link" href="#tab_group_' . $group->id . '" data-toggle="tab">' . format_text($group->name) . '</a></li>');
	}
	?>
    </ul>
    <div class="tab-content">
   
	<?php
function getProgressBar($score, $total) {
	if($total > 0) {
		$w = floor($score / $total * 100);
		$h = floor($score / $total * 120);
	} else {
		$w = $h = 0;
	}
	$html = '<div class="student_progress_bar"><div class="student_progress_bar_inner" style="width:' . $w . '%; background-color:hsl('.$h.',100%,50%);border:2px solid hsl('.$h.',100%,50%);"></div></div>';
	return $html;
}	
	
	
    foreach($groups as $group) {
		echo('<div id="tab_group_' . $group->id . '" class="tab-pane">');
		echo('<table class="generaltable">');
		echo('<thead><tr class="user_progress_header"><th id="uph_firstname_' . $group->id . '">Firstname<i></i></th><th id="uph_lastname_' . $group->id . '">Lastname<i></i></th><th id="uph_lastrun_' . $group->id . '">Last run<i></i></th><th id="uph_tryit_' . $group->id . '">Try it<span class="score_total try_total">/'.$max_scores->max_try.'</span><i></i></th><th id="uph_debug_' . $group->id . '">Debug it<span class="score_total debug_total">/'.$max_scores->max_debug.'</span><i></i></th><th id="uph_extend_' . $group->id . '">Extend it<span class="score_total extend_total">/'.$max_scores->max_extend.'</span><i></i></th><th id="uph_total_' . $group->id . '">Total<span class="score_total total_total">/'.$max_scores->max_total.'</span><i></i></th></tr></thead><tbody>');
		
		$users = get_enrolled_users($context, 'mod/withcode:save', $group->id);
		foreach($users as $user) {
			echo('<tr class="user_progress_row" id="user_progress_row_' . $user->id . '">');
			$code = withcode_get_user_code($cm->instance, $user->id);
			echo('<td class="user_progress_firstname"><a href="' . $CFG->wwwroot . '/user/view.php?id=' . $user->id . '&course=' . $COURSE->id . '">' . format_text($user->firstname) . '</a></td>');
			echo('<td class="user_progress_lastname"><a href="' . $CFG->wwwroot . '/user/view.php?id=' . $user->id . '&course=' . $COURSE->id . '">' . format_text($user->lastname) . '</a></td>');
			$viewHtml = '';
			if(isset($code->id)) {
				$viewHtml = '<a href="api.php?cmd=showsnippet&snippet=' . $code->id . '" target="_blank"><i id="btn_view_snippet_' . $code->id . '" class="fa fa-share btn_view_snippet"></i></a>';	
			}
			
			echo('<td class="user_progress_lastupdated">' . withcode_get_time_diff($code->timecreated) . $viewHtml . '</td>');
			echo('<td class="user_progress_score_try">' . $code->score_try . getProgressBar($code->score_try,$max_scores->max_try) . '</td>');
			echo('<td class="user_progress_score_debug">' . $code->score_debug . getProgressBar($code->score_debug,$max_scores->max_debug) . '</td>');
			echo('<td class="user_progress_score_extend">' . $code->score_extend . getProgressBar($code->score_extend,$max_scores->max_extend) . '</td>');
			echo('<td class="user_progress_score_total">' . $code->score_total . getProgressBar($code->score_total,$max_scores->max_total) . '</td>');
			echo('</tr>');
		}
		echo('</tbody></table>');
		echo('<button class="btn_reload_progress_group btn" id="btn_reload_progress_group_' . $group->id . '"><i class="fa fa-refresh"></i> Refresh</button>');
		echo('</div>');
	}
	?>
    </div>
	</div>
	<?php
}

?>
</div>
<div id="points"><p>score:</p><span class="wc_score">0</span><p>0 available</p></div>

<h3><button type="button" class="btn btn-info" data-toggle="collapse" data-target="#instructions"><i class="fa fa-sort-down"></i></button> Instructions</h3>
<div id="instructions" class="collapse in">
<?php 
echo(format_text($withcode->intro, $withcode->introformat));
?>
<div id="tabs_tde">
<ul class="nav nav-tabs">
<li class="nav-item"><a class="nav-link active" href="#tab_try" data-toggle="tab">Try it</a></li>
<li class="nav-item"><a class="nav-link" href="#tab_debug" data-toggle="tab">Debug it</a></li>
<li class="nav-item"><a class="nav-link" href="#tab_extend" data-toggle="tab">Extend it</a></li>
</ul>
<div class="tab-content">
<?php

echo('<div id="tab_try" class="tab-pane active"><img class="tde_icon" src="' . $CFG->wwwroot . '/mod/withcode/pix/tryit.png">');
echo('<a href="api.php?cmd=showtemplatecode&section=try&withcodeid=' . $withcode->id . '" target="_blank"><button class="btn"><i class="fa fa-share"></i> Show template code (try_it.py)</button></a>');
echo(format_text($withcode->desctry, $withcode->desctryformat));
echo('<div id="tests_try"></div>');
echo('</div>');
echo('<div id="tab_debug" class="tab-pane"><img class="tde_icon" src="' . $CFG->wwwroot . '/mod/withcode/pix/debugit.png">');
echo('<a href="api.php?cmd=showtemplatecode&section=debug&withcodeid=' . $withcode->id . '" target="_blank"><button class="btn"><i class="fa fa-share"></i> Show template code (debug_it.py)</button></a>');
echo(format_text($withcode->descdebug, $withcode->descdebugformat));
echo('<div id="tests_debug"></div>');
echo('</div>');
echo('<div id="tab_extend" class="tab-pane"><img class="tde_icon" src="' . $CFG->wwwroot . '/mod/withcode/pix/extendit.png">');
echo('<a href="api.php?cmd=showtemplatecode&section=extend&withcodeid=' . $withcode->id . '" target="_blank"><button class="btn"><i class="fa fa-share"></i> Show template code (extend_it.py)</button></a>');
echo(format_text($withcode->descextend, $withcode->descextendformat));
echo('<div id="tests_extend"></div>');
echo('</div></div></div></div>');

?>

<div id="loading">
Loading... please wait
</div>


<h3><button type="button" class="btn btn-info" data-toggle="collapse" data-target="#code"><i class="fa fa-sort-down"></i></button> Code</h3>
<div id="code" class="collapse in">
<div id="holder" class="holder" style="display:none">
<span id="file_tabs"><span class="file_tab file_tab_selected">try_it.py</span></span>
<?php
insertEditor();
echo('<div><p>&nbsp;</p><a href="api.php?cmd=showtemplatecode&section=all&withcodeid=' . $withcode->id . '" target="_blank"><button class="btn"><i class="fa fa-share"></i> Show all template code</button></a></div>');
?>

<div id="hintBar"></div>
<span id="footer">
<img alt="Click to show/hide tool buttons" title="Show/hide tools" class="toolButton" src="<?php echo($CFG->wwwroot)?>/mod/withcode/pix/tools.png" id="btn_tools">
<img title="Run" alt="Click to run your code" id="btn_run" class="toolButton hiddenButton" src="<?php echo($CFG->wwwroot)?>/mod/withcode/pix/play.png">
<img alt="Stop running" title="Stop" class="toolButton hiddenButton" src="<?php echo($CFG->wwwroot)?>/mod/withcode/pix/stop.png" id="btn_stopRunning">
<img title="Console" alt="Show python output" id="btn_show_output" class="toolButton hiddenButton" src="<?php echo($CFG->wwwroot)?>/mod/withcode/pix/console.png">

</span>

<div id="file_settings" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">File settings</h4>
      </div>
      <div class="modal-body">        
	  <p>Be careful: if you choose to delete a file, you will not be able to recover it unless you've saved a copy</p>
		<label for="txt_file_name">Rename file:</label><input id="txt_file_name" name="txt_file_name" value="">
		
      </div>
      <div class="modal-footer">
		<button id="btn_file_rename">Rename</button>
		<button id="btn_file_delete">Delete</button>
		<button id="btn_file_cancel">Cancel</button>
      </div>
    </div>

  </div>
</div>

<!-- Modal -->
<div id="dlg" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title" id="run_filename">Running filename</h4>
      </div>
      <div class="modal-body" id="output">        
      </div>
      <div class="modal-footer">
        <a target="_blank" href="api.php?cmd=share&withcodeid=<?php echo($withcode->id);?>"><button type="button" id="btn_share" class="btn"><i class="fa fa-share"></i> Share</button></a><button type="button" class="btn" data-dismiss="modal">Hide</button><button class="btn" id="btn_stop">Stop</button>
      </div>
    </div>

  </div>
</div>


<?php
echo($OUTPUT->footer());
