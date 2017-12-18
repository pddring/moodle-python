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
 * This page lists all the instances of wiki in a particular course
 *
 * @package withcode
 * @copyright 2016 pddring blog.withcode.uk
 *
 * @author Pete Dring
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
 
require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/withcode/lib.php');

error_reporting(E_ALL); ini_set('display_errors', '1');

class mod_withcode_mod_form extends moodleform_mod {
 
    function definition() {
        global $CFG, $DB, $OUTPUT, $PAGE;
 		
        $mform =& $this->_form;
		$PAGE->requires->jquery();
		if(get_config('withcode', 'linkbootstrap')) {
			$html = '<script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script><link rel="stylesheet" type="text/stylesheet"href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">';
			$mform->addElement('html', $html);	
		}
		
		 
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('skillname', 'withcode'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
		
		$this->standard_intro_elements();
 
		$mform->addElement('header', 'code', get_string('code', 'withcode'));
		
		$mform->addElement('html', '<div id="tabs_tde">
		<ul class="nav nav-tabs">
		<li class="nav-item"><a class="nav-link active" href="#tab_try" data-toggle="tab">Try it</a></li>
		<li class="nav-item"><a class="nav-link" href="#tab_debug" data-toggle="tab">Debug it</a></li>
		<li class="nav-item"><a class="nav-link" href="#tab_extend" data-toggle="tab">Extend it</a></li>
		</ul>
		<div class="tab-content">
		<div id="tab_try" class="tab-pane active"><img src="' . $CFG->wwwroot . '/mod/withcode/pix/tryit.png">
		');
		
		
		
		$mform->addElement('editor', 'desctry', get_string('tryit', 'withcode') . " " . get_string('instructions', 'withcode'));
		
        $mform->setType('desctry', PARAM_RAW);
		$mform->addElement('textarea', 'codetry', get_string('tryit', 'withcode') . " " . get_string('code', 'withcode'));
        $mform->setType('codetry', PARAM_RAW);
		$mform->addElement('textarea', 'testtry', get_string('tryit', 'withcode') . " " . get_string('tests', 'withcode'));
        $mform->setType('testtry', PARAM_RAW);
		
		$mform->addElement('html', '</div><div id="tab_debug" class="tab-pane"><img src="' . $CFG->wwwroot . '/mod/withcode/pix/debugit.png">');
		
		$mform->addElement('editor', 'descdebug', get_string('debugit', 'withcode') . " " . get_string('instructions', 'withcode'));
        $mform->setType('descdebug', PARAM_RAW);
		$mform->addElement('textarea', 'codedebug', get_string('debugit', 'withcode') . " " . get_string('code', 'withcode'));
        $mform->setType('codedebug', PARAM_RAW);
		$mform->addElement('textarea', 'testdebug', get_string('debugit', 'withcode') . " " . get_string('tests', 'withcode'));
        $mform->setType('testdebug', PARAM_RAW);
		
		$mform->addElement('html', '</div><div id="tab_extend" class="tab-pane"><img src="' . $CFG->wwwroot . '/mod/withcode/pix/extendit.png">');
		
		$mform->addElement('editor', 'descextend', get_string('extendit', 'withcode') . " " . get_string('instructions', 'withcode'));
        $mform->setType('descextend', PARAM_RAW);
		$mform->addElement('textarea', 'codeextend', get_string('extendit', 'withcode') . " " . get_string('code', 'withcode'));
        $mform->setType('codeextend', PARAM_RAW);
		$mform->addElement('textarea', 'testextend', get_string('extendit', 'withcode') . " " . get_string('tests', 'withcode'));
        $mform->setType('testextend', PARAM_RAW);
		
		$mform->addElement('html', '</div></div></div>');
		
		if($id = $this->get_instance()) {
			$withcode = $DB->get_record('withcode', array('id'=>$id));
		
			$this->set_data(array(
				'desctry' => array('text' => $withcode->desctry),
				'descdebug' => array('text' => $withcode->descdebug),
				'descextend' => array('text' => $withcode->descextend)));
		}
	
		
		$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/withcode/styles.css'));
		$PAGE->requires->jquery();
		$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/withcode/js/ace/ace.js'));
		$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/withcode/js/lib.js'));

		$PAGE->requires->js_function_call('withcode.setupForm', array());
 
 
		
        $this->standard_coursemodule_elements();
 
        $this->add_action_buttons();
    }
}