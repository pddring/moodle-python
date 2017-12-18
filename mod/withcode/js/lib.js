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

var withcode = {
	setupForm: function() {
		require(['jquery'], function($){
			function addAceEditor(id, mode) {
				var ta = $('#id_' + id);
				var html = '<div id="ace_' + id + '" class="ace_form_editor"></div>';
				ta.after(html);
				var code = ace.edit("ace_" + id);
				code.$blockScrolling = Infinity
				code.getSession().setMode("ace/mode/" + mode);
				code.setValue(ta.val());
				code.on("change", function(e) {
					ta.val(code.getValue());
				});
				ta.hide();
			}
			
			addAceEditor('codetry', 'python');
			addAceEditor('codedebug', 'python');
			addAceEditor('codeextend', 'python');
			addAceEditor('testtry', 'javascript');
			addAceEditor('testdebug', 'javascript');
			addAceEditor('testextend', 'javascript');
		})	
		/*
		
		
		*/
	},
	
	init: function(files, tests, id) {
		
		require(['jquery', 'mod_withcode/withcode'], function($, PythonIDE) {
			$('#loading').hide();
			$('#holder').show();
			if(files == undefined)
				files = {'try_it.py': '', 'debug_it.py':'', 'extend_it.py':''};
			PythonIDE.files = files;
			PythonIDE.instance = id;
			progressUpdates = {};
			var jsTests = {};
			if(tests){
				for(file in tests){
					try {
						jsTests[file] = JSON.parse(tests[file]);
						for(var i = 0; i < jsTests[file].length; i++) {
							var test = jsTests[file][i];
							
							for(var j = 0; j < test.conditions.length; j++) {
								for (condition in test.conditions[j]) {
									if(test.conditions[j][condition][0] == "/" && test.conditions[j][condition].slice(-1) == "/") {
										test.conditions[j][condition] = RegExp(test.conditions[j][condition].slice(1,-1));
									}
									
									//console.log(condition, test.conditions[j][condition]);
								}
							}
						}
						//console.log(file, jsTests[file]);
					} catch(e) {
						console.log("Could not parse test:", e, tests[file]);
					}
				}
			}
			
			
			
			
			function getProgressBar(score, total) {

				var w = Math.floor(score / total * 100);
				var h = Math.floor(score / total * 120);
				var html = '<div class="student_progress_bar"><div class="student_progress_bar_inner" style="width:' + w + '%; background-color:hsl('+h+',100%,50%);border:2px solid hsl('+h+',100%,50%);"></div></div>';
				return html;
			}
			
			
			
			PythonIDE.tests = jsTests;
			PythonIDE.init();
			
			
			$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
			  var target = $(e.target).attr("href") // activated tab
			  switch(target){
				  case '#tab_try':
					PythonIDE.editFile('try_it.py');
				  break;
				  case '#tab_debug':
					PythonIDE.editFile('debug_it.py');
				  break;
				  case'#tab_extend':
					PythonIDE.editFile('extend_it.py');
				  break;
			  }
			});
			
			$('.user_progress_header th').click(function(e) {
				var groupid = e.currentTarget.id.split("_")[2];
				var sortby = e.currentTarget.id.split("_")[1];
				
				var jqSortIcon = $('#' + e.currentTarget.id + " i");
				var sortorder = "asc";
				if(jqSortIcon.hasClass('fa-sort-amount-asc')) {
					sortorder = "desc";
				}
				$('#tab_group_' + groupid + " th i").removeClass();
				jqSortIcon.addClass('fa fa-sort-amount-' + sortorder);
				
				var spinner = $('#btn_reload_progress_group_' + groupid + " i").addClass('fa-spin');
				$.getJSON('api.php', {
					cmd: 'get_progress',
					group: groupid,
					withcodeid: PythonIDE.instance,
					since: 0,
					sortby: sortby,
					sortorder: sortorder
				}, function(e) {
					spinner.removeClass('fa-spin');
					$('#tab_group_' + groupid + ' tr.user_progress_row').remove();
					var table = $('#tab_group_' + groupid + " tbody");
					var maxTry = 100;
					var maxDebug = 100;
					var maxExtend = 100;
					var maxTotal = 300;
					if(PythonIDE.maxScores) {
						maxTry = PythonIDE.maxScores['try_it.py'];
						maxDebug = PythonIDE.maxScores['debug_it.py'];
						maxExtend = PythonIDE.maxScores['extend_it.py'];
						maxTotal = PythonIDE.maxScores['total'];
					}
					for(var i = 0; i < e.code.length; i++) {
						var snippet = e.code[i];
						var html = '<tr class="user_progress_row" id="user_progress_row_' + snippet.userid + '">';
						html += '<td class="user_progress_firstname">' + snippet.firstname + '</td>';
						html += '<td class="user_progress_lastname">' + snippet.lastname + '</td>';
						var viewHtml = '';
						if(snippet.id) {
							viewHtml = '<a href="api.php?cmd=showsnippet&snippet=' + snippet.id +'" target="_blank"><i id="btn_view_snippet_' + snippet.id + '" class="fa fa-share btn_view_snippet"></i></a>';
						}
						html += '<td class="user_progress_lastupdated">' + snippet.lastrun + viewHtml + '</td>';
						html += '<td class="user_progress_score_try">' + snippet.score_try + getProgressBar(snippet.score_try, maxTry) + '</td>';
						html += '<td class="user_progress_score_debug">' + snippet.score_debug + getProgressBar(snippet.score_debug, maxDebug) + '</td>';
						html += '<td class="user_progress_score_extend">' + snippet.score_extend + getProgressBar(snippet.score_extend, maxExtend) + '</td>';
						html += '<td class="user_progress_score_total">' + snippet.score_total + getProgressBar(snippet.score_total, maxTotal) + '</td>';
						html += '</tr>';
						table.append(html);
					}
					
				});
				
			});
			
			$('.btn_reload_progress_group').click(function(e) {
				var spinner = $('#' + e.currentTarget.id + " i").addClass('fa-spin');
				var groupid = e.currentTarget.id.split("_")[4];
				$.getJSON('api.php', {
					cmd: 'get_progress',
					group: groupid,
					withcodeid: PythonIDE.instance,
					since: progressUpdates[groupid]
				}, function(e) {
					spinner.removeClass('fa-spin');
					
					var maxTry = 100;
					var maxDebug = 100;
					var maxExtend = 100;
					var maxTotal = 300;
					if(PythonIDE.maxScores) {
						maxTry = PythonIDE.maxScores['try_it.py'];
						maxDebug = PythonIDE.maxScores['debug_it.py'];
						maxExtend = PythonIDE.maxScores['extend_it.py'];
						maxTotal = PythonIDE.maxScores['total'];
					}
					for(var id in e.code) {
						var snippet = e.code[id];
						var row = $('#user_progress_row_' + snippet.userid);
						row.find('.user_progress_score_try').html(snippet.score_try + getProgressBar(snippet.score_try, maxTry));
						row.find('.user_progress_score_debug').html(snippet.score_debug + getProgressBar(snippet.score_debug, maxDebug));
						row.find('.user_progress_score_extend').html(snippet.score_extend + getProgressBar(snippet.score_extend, maxExtend));
						row.find('.user_progress_score_total').html(snippet.score_total + getProgressBar(snippet.score_total, maxTotal));
						var viewHtml = '';
						if(snippet.id) {
							viewHtml = '<a href="api.php?cmd=showsnippet&snippet=' + snippet.id +'" target="_blank"><i id="btn_view_snippet_' + snippet.id + '" class="fa fa-share btn_view_snippet"></i></a>';
						}
						row.find('.user_progress_lastupdated').html(snippet.lastrun + viewHtml);
					}
					
				});
			});
		});
	}
}