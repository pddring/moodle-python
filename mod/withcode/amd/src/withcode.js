define(['jquery'], function($){
var PythonIDE = {
	toolsVisible:false,
	editFile: function(filename) {
		if(PythonIDE.files[PythonIDE.currentFile])
			PythonIDE.files[PythonIDE.currentFile] = PythonIDE.editor.getValue();
		PythonIDE.currentFile = filename;
		PythonIDE.editor.setValue(PythonIDE.files[filename]);
		PythonIDE.editor.clearSelection();
		PythonIDE.editor.focus();
		
		var extension = filename.match(/(\.[^.]+)/);
		if(extension && extension.length > 1)
			extension = extension[1];
		switch(extension) { 
			case '.py':
				PythonIDE.editor.getSession().setMode("ace/mode/python");
			break;
			case '.html':
				PythonIDE.editor.getSession().setMode("ace/mode/html");
			break;
			case '.js':
				PythonIDE.editor.getSession().setMode("ace/mode/javascript");
			break;
			case '.sql':
				PythonIDE.editor.getSession().setMode("ace/mode/mysql");
				break;
			default:
				PythonIDE.editor.getSession().setMode("ace/mode/text");
			break;
		}
		
		PythonIDE.updateFileTabs();
	},
	
	updateFileTabs: function() {
		var html = '';
		for(var file in PythonIDE.files){
			html += '<span class="file_tab';
			if(file == PythonIDE.currentFile) {
				html += ' file_tab_selected">'
				if((file != 'try_it.py') && (file!= 'debug_it.py') && (file != 'extend_it.py')){
					html += '<img class="btn_file_settings" alt="File settings" title="File settings" src="pix/settings.png">';
				}
			} else {
				html += '">';
			}
			html += file + '</span>';
		}
		html += '<span class="file_tab"><img class="btn_file_settings" alt="Create new file" title="Create new file" src="pix/tools.png"></span>';
		$('#file_tabs').html(html);
		$('.file_tab').click(function(e) {
			var fileName = e.currentTarget.textContent;
			switch(fileName) {
				case "":
					fileName = 'newfile.txt';
					if(PythonIDE.files[fileName] === undefined){
						PythonIDE.files[fileName] = '';
					}
					PythonIDE.editFile('newfile.txt');
					break;
				case PythonIDE.currentFile:
					if(!((fileName == "try_it.py") || (fileName == "debug_it.py") || (fileName == "extend_it.py"))) {
						$('#file_settings').show().modal();
						$('#txt_file_name').val(fileName).focus();
					}
					break;
				default:
					PythonIDE.editFile(e.currentTarget.textContent);
					break;
			}
			
		});
	},
	
	currentFile: 'try_it.py',
	
	countFiles: function() {
		var c = 0;
		for(var f in PythonIDE.files) {
			c++;
		}
		return c;
	},
	
	files: {'try_it.py':''},
	
	readFile: function(filename) {
		return PythonIDE.files[filename];
	},
	
	writeFile: function(filename, contents) {
		PythonIDE.files[filename] = contents;
		PythonIDE.updateFileTabs();
	},
	
	welcomeMessage: "Press Ctrl+Enter to run",
	
	getOption: function(optionName, defaultValue) {
		if(localStorage && localStorage['OPT_' + optionName])
			return localStorage['OPT_' + optionName]
		return defaultValue;
	},
	
	setOption: function(optionName, value) {
		localStorage['OPT_' + optionName] = value;
		return value;
	},
	
	showHint: function(msg) {
		if(PythonIDE.hideHintTimeout) {
			clearTimeout(PythonIDE.hideHintTimeout);
		}
		PythonIDE.hideHintTimeout = setTimeout(function(){
			delete PythonIDE.hideHintTimeout;
			$('#hintBar').fadeOut();
		}, 5000);
		$('#hintBar').html(msg).show();
	},
	
	python: {
		outputListeners: [],
		
		output: function(text, header) {
			var id = header == undefined?'consoleOut': 'headerOut';
			var c = document.getElementById(id);
			c.innerHTML += text;
			
			var i = 0;
			while(i < PythonIDE.python.outputListeners.length) {
				var l = PythonIDE.python.outputListeners[i];
				try {
					l(text);
					i++;
				} catch(e) {
					PythonIDE.python.outputListeners.splice(i, 1);
				}
			}
			var c = c.parentNode.parentNode;
			c.scrollTop = c.scrollHeight;
			
		},
		
		clear: function() {
			var c = document.getElementById('consoleOut');
			c.innerHTML = '';
			var c = c.parentNode.parentNode;
			c.scrollTop = c.scrollHeight;
		},
		
		builtinread: function(x) {
			if (Sk.builtinFiles === undefined || Sk.builtinFiles["files"][x] === undefined)
            throw "File not found: '" + x + "'";
			return Sk.builtinFiles["files"][x];
		}
	},
	
	runAsync: function(asyncFunc) {
		var p = new Promise(asyncFunc); 
		var result;
		var susp = new Sk.misceval.Suspension();
		susp.resume = function() {
			return result;
		}
		susp.data = {
			type: "Sk.promise",
			promise: p.then(function(value) {
				result = value;
				return value;
			}, function(err) {
				result = "";
				PythonIDE.handleError(err);
				return new Promise(function(resolve, reject){
				});
			})
		};	
		return susp;
	},
	
	watchVariables: {
		expandHandlers:[]
	},
	
	
	
	runCode: function(runMode) {
		if(PythonIDE.unhandledError)
			delete PythonIDE.unhandledError;
		
		if(PythonIDE.animTimeout && runMode != "anim") {
			clearTimeout(PythonIDE.animTimeout);
			delete PythonIDE.animTimeout;
			return;
		}
		
		if(PythonIDE.continueDebug) {
			if(runMode != "normal") {
				PythonIDE.continueDebug();
				return;
			}
		}
		
		if(runMode === undefined)
			runMode = "normal";
		

		PythonIDE.runMode = runMode;
		PythonIDE.python.outputListeners = [];
		
		PythonIDE.showHint("Running code...");
		$('#btn_stopRunning').addClass('visibleButton');
					
		var code = PythonIDE.files[PythonIDE.currentFile];
		$('#run_filename').html('Running: ' + PythonIDE.currentFile);
		localStorage.lastRunCode = code;
		
		Sk.configure({
			breakpoints:function(filename, line_number, offset, s) {
				//console.log(line_number, PythonIDE.runMode);
				if(PythonIDE.runMode == "anim") {
					if(PythonIDE.continueDebug) {
						PythonIDE.animTimeout = setTimeout(function() {
							PythonIDE.runCode("anim");
						}, 1000);
					}
				}
				PythonIDE.editor.gotoLine(line_number);
				
				// check for errors in external libraries
				if(PythonIDE.unhandledError) {
					throw PythonIDE.unhandledError;
				}
				return true;
			},
			debugging: true,
			output: PythonIDE.python.output,
			readFile: PythonIDE.readFile,
			writeFile: PythonIDE.writeFile,
			read: PythonIDE.python.builtinread});
		
		//PythonIDE.saveSnapshot();
		
		
		var html = '';
		html += '<div id="headerOut"></div>';
		html += '<pre id="consoleOut"><div id="watch"></div></pre>';
		html += '</pre>';
		if(code.indexOf("turtle") > 0) {
			html += '<div id="canvas"></div>';
		} 
		
		
		
		$('#output').html(html);
		$('#dlg').show().modal();
		$('#btn_stop').show();
		
		
		if(!PythonIDE.whenFinished) {
			$('#btn_hideConsole').click(function() {
				$('#dlg').hide();
			});
		} else {
			$('#btn_hideConsole').hide();
		}
		
		var handlers = [];
		if(runMode != "normal") { 
			handlers["Sk.debug"] = function(susp) {
				// globals
				//console.log(susp.child);
				var html = '<h2>Global variables:</h2><table><tr><th>Name</th><th>Data type</th><th>Value</th></tr>';
				PythonIDE.watchVariables.expandHandlers = [];
				for(var key in susp.child.$gbl) {
					var pyVal = susp.child.$gbl[key];
					var val = JSON.stringify(Sk.ffi.remapToJs(pyVal));
					
					if(val === undefined) {
						val = "";
					}
					
					if(val && val.length && val.length > 20) {
						var eH = {"id":PythonIDE.watchVariables.expandHandlers.length, "fullText": val, "shortText": val.substring(0,17)};
						
						PythonIDE.watchVariables.expandHandlers.push(eH);
						val = '<span class="debug_expand_zone" id="debug_expand_' + eH.id + '">' + val.substring(0, 17) + '<img src="/media/tools.png" class="debug_expand" title="Click to see full value"></span>';
					}
					
					var type = pyVal.skType?pyVal.skType : pyVal.__proto__.tp$name;
					if(type == "function") {
						continue;
					} 
					if(type == "str") {
						type = "string";
					}
					if(type === undefined) {
						//console.log(pyVal, val, type);
						continue;
					}
					html += '<tr><td>' + key + '</td><td>' + type + '</td><td>' + val + '</td></tr>';
				}
				html += '</table>';
				
				
				
				$('#watch').html(html);
				
				$('span.debug_expand_zone').click(function(e) {
					var id = e.currentTarget.id;
					var idNum = id.replace("debug_expand_", "");
					$('#' + id).html(PythonIDE.watchVariables.expandHandlers[idNum].fullText);
				});
				
				var p = new Promise(function(resolve,reject){
					PythonIDE.continueDebug = function() {
						return resolve(susp.resume());
					}
					
					PythonIDE.abortDebug = function() {
						delete PythonIDE.abortDebug;
						delete PythonIDE.continueDebug;
						return reject("Program aborted");
					}
					
				});
				return p;
			}
			setTimeout(function() {PythonIDE.runCode(runMode); }, 100);
			$('#watch').show();
		} 
		
		Sk.misceval.callsimAsync(handlers, function() {
			return Sk.importMainWithBody("try_it",false,code,true);
		}).then(function(module){
			PythonIDE.showHint('Program finished running');
			if(PythonIDE.continueDebug)
				delete PythonIDE.continueDebug;
			if(PythonIDE.abortDebug)
				delete PythonIDE.abortDebug;
			$('#btn_stop').hide();
			$('#btn_stopRunning').removeClass('visibleButton').addClass('hiddenButton');
			if(PythonIDE.whenFinished) {
				PythonIDE.whenFinished();
			}
			PythonIDE.runTests();
		}, PythonIDE.handleError);
		
	},
	
	handleError:function (err){
		
		PythonIDE.runTests();
		
		if(!PythonIDE.unhandledError && PythonIDE.continueDebug) {
			PythonIDE.unhandledError = err;
			return;
		}
		
		var html = '<span class="error">' + err.toString() + '</span>';
		PythonIDE.showHint(html);
		PythonIDE.python.output(html);
	},
	
	load: function() {
		$.getJSON('api.php', {
			cmd: 'load',
			id: PythonIDE.instance
		}, function(data) {
			if(data.success && data.data) {
				var snippet = undefined;
				for(key in data.data) {
					snippet = data.data[key];
					break;
				}
				if(snippet && snippet.files) {
					PythonIDE.files = snippet.files;
					PythonIDE.updateFileTabs();
					PythonIDE.editor.setValue(PythonIDE.files[PythonIDE.currentFile]);
					PythonIDE.editor.clearSelection();
					PythonIDE.runTests();
					PythonIDE.showHint(data.message);
				} else {
					PythonIDE.showHint("No files saved yet");
				}
			}
			
		}, function(e) {
			console.log("Error", e);
		});
	},
	
	save: function() {
		
		var data = {id: PythonIDE.instance,
		files: PythonIDE.files,
		score_try: 0,
		score_debug: 0,
		score_extend: 0,
		score_total: 0
		};

		for(file in PythonIDE.tests){
			var total = 0;
			for(var i = 0; i < PythonIDE.tests[file].length; i++){
				if(PythonIDE.tests[file][i].completed > 0) {
					total += (PythonIDE.tests[file][i].points * PythonIDE.tests[file][i].completed);
					data.score_total += (PythonIDE.tests[file][i].points * PythonIDE.tests[file][i].completed);
				}
			}
			total = Math.round(total);
			data.score_total = Math.round(data.score_total);
			switch(file) {
				case 'try_it.py':
					data.score_try += total;
				break;
				case 'debug_it.py':
					data.score_debug += total;
				break;
				case 'extend_it.py':
					data.score_extend += total;
				break;
			}
		}

		
		$.post('api.php?cmd=save', data, function(response) {			
			//PythonIDE.showHint(response.message);
			
		},'json');
		//console.log("save", data);
		
		/*
		PythonIDE.showHint("Saving...");
		var code = PythonIDE.files['mycode.py'];
		if(PythonIDE.countFiles() > 1) {
			code = JSON.stringify(PythonIDE.files);
		}
		$.getJSON('/lib/api.php', {
			cmd: 'save',
			code: encodeURIComponent(btoa(code))
		}, function(data) {
			//console.log(data);
			var link = 'https://create.withcode.uk/python/'+data.hash;
			window.location=link;
		});*/
	},
	
	autoSize: function(e) {
		if(e && e.target.localName == "div")
			return;
		// expand editor to fit height of the screen.
		$('.holder').css({height: window.innerHeight - 80});

	},
	
	tests: [],
	
	controlledTest: function(code, test, iteration) {
		if(!test.conditions){
			test.conditions = [];
		}
		if(iteration === undefined)
			iteration = 0;
		var conditions = test.conditions;
		for(var i = 0; i < conditions.length; i++) {
			conditions[i].met[iteration] = false;
		}
		var p = new Promise(function(resolve, reject){
			var examples = [
				{o:/Hello/}, // Expect output of Hello/}
				{p:/What is your name\?/, i:"Bob"}, // input "Bob" when prompted with "What is your name"
				{g:'name', v:'Pete'}, // global variable name set to value Pete
				{f:'test.txt', d:/^1234$/}, // file test.txt contains 1234
				{c:/this code/}, // comment including the text "this code" (on current line or above)
				{l:/raw_input/} // current line includes the text raw_input
			];
			
			var i = 0; // current condition number
			
			function output(msg) {
				//console.log(msg, conditions[i])
				if(i < conditions.length && conditions[i].o) {
					if(Array.isArray(conditions[i].o)) {
						if(msg.match(conditions[i].o[iteration])){
							conditions[i].met[iteration] = true;
							i++;
						}
					} else {
						if(msg.match(conditions[i].o)){
							conditions[i].met[iteration] = true;
							i++;
						}
					}					
				}
				
				if(msg.trim().length > 0) {
					//console.log("Test", msg);
				}
			};
			
			function input(question) {
				var answer = "";
				if(i < conditions.length && conditions[i].p && conditions[i].i) {
					if(Array.isArray(conditions[i].i)) {
						if(question.match(conditions[i].p)) {
							conditions[i].met[iteration] = true;
							answer = conditions[i].i[iteration];
							i++;	
						}
					} else {
						if(question.match(conditions[i].p)) {
							conditions[i].met[iteration] = true;
							answer = conditions[i].i;
							i++;	
						}
					}			
				}
				return answer;
			};
			
			var filesBackup = JSON.parse(JSON.stringify(PythonIDE.files));
			var inputBackup = Sk.inputfun;
			Sk.inputfun = input;
			Sk.configure({
				breakpoints:function(filename, line_number, offset, s) {
					if(i < conditions.length && conditions[i].c) {
						var lines = code.split("\n");
						for(var j = line_number - 1; j >= 0; j--) {
							var sections = lines[j].split('#', 2);
							if(sections.length > 1) { 
								if(sections[1].match(conditions[i].c)) {
									conditions[i].met = true;
									i++;
									break;
								}
							}
							if(j < line_number - 1 && sections[0].trim().length > 0) {
								break;
							}
							
						}
					}
					
					if(i < conditions.length && conditions[i].l) {
						var lines = code.split("\n");
						if (lines.length > line_number && lines[line_number - 1].match(conditions[i].l)){
							conditions[i].met = true;
							i++;
							
						}
					}
					
					return true;
				},
				debugging: true,
				output: output,
				readFile: PythonIDE.readFile,
				writeFile: PythonIDE.writeFile,
				read: PythonIDE.python.builtinread});
			Sk.execLimit = 2000;
			Sk.python3 = true;
			
			var handlers = [];
			handlers["Sk.debug"] = function(susp) {
				///console.log(susp);
				
				if(i < conditions.length) {
					// check global variable
					if(conditions[i].g && susp.child.$gbl[conditions[i].g]) {
						if(Sk.ffi.remapToJs(susp.child.$gbl[conditions[i].g]) == conditions[i].v) {
							conditions[i].met = true;
							i++;
						}
					}
					
					// check file
					if(conditions[i].f && PythonIDE.files[conditions[i].f]){
						if(PythonIDE.files[conditions[i].f].match(conditions[i].d)) {
							conditions[i].met = true;
							i++;
						}
					}
					
				} 
				
			}
			Sk.misceval.callsimAsync(handlers, function() {
				return Sk.importMainWithBody("test",false,code,true);
			}).then(function(module){
				
				Sk.inputfun = inputBackup;
				PythonIDE.files = filesBackup;
				PythonIDE.updateFileTabs();
				var result = {conditions: conditions, met: 0, total: 0};
				for(var i = 0; i < conditions.length; i++) {
					var iterationScore = 0;
					for(var j = 0; j < conditions[i].met.length; j++) {
						if(conditions[i].met[j]) {
							iterationScore++;
						}	
					}
					result.met+=(iterationScore / conditions[i].met.length);
					result.total++;
				}
				if(result.total < 1) {
					test.completed = 0;
				} else {
					test.completed = result.met / result.total;
				}
				
				resolve(result);
				
			}, function (e) {
				Sk.inputfun = inputBackup;
				PythonIDE.updateFileTabs();
				PythonIDE.files = filesBackup;
				reject(e);
			});
			
		});
		
		return p;
		
		
	
		
	},
	
	runTests: function() {
		var tests = [];
		for(file in PythonIDE.tests){
			
			for(var i = 0; i < PythonIDE.tests[file].length; i++) {
				var t = PythonIDE.tests[file][i];
				t.completed = 0;
				var code = PythonIDE.files[file];
				if(t.codeHeader)
					code = t.codeHeader + "\n" + code;
				if(t.codeFooter)
					code += "\n" + t.codeFooter;
				if(!t.iterations) 
					t.iterations = 1;
				
				for(var j = 0; j < t.conditions.length; j++) {
					t.conditions[j].met = [];
					for(var k = 0; k < t.iterations; k++) {
						t.conditions[j].met[k] = false;
					}
				}
				
				for(var j = 0; j < t.iterations; j++) {
					var p = PythonIDE.controlledTest(code, t, j);
					tests.push(p);
				}				
			}
			
			
		}
		var i = 0;
		function runNextTest(){
			if(i < tests.length) {
				tests[i].then(function(result){
					//console.log("Success:", result);
					i++;
					runNextTest();
				}, function(e) {
					PythonIDE.updateTests();
					//console.log("Error:", e);
				});
			} else {
				//console.log("done!");
				PythonIDE.updateTests();
				PythonIDE.save();
			}
		
		}
		runNextTest();
		
	},
	
	updateTests: function() {
		var maxScores = {total: 0};
		var currentScores = {total: 0};
		for(var file in PythonIDE.tests) {
		
			var html = '<h3>Tests:</h3>';
			for(var i =0; i < PythonIDE.tests[file].length; i++) {
				var test = PythonIDE.tests[file][i];
				if(test.iterations == undefined) {
					test.iterations = 1;
				}
					
				if(maxScores[file] == undefined)
					maxScores[file] = 0;
				maxScores[file] += test.points;
				maxScores.total += test.points;
				html += '<div class="test_descriptions">';
				if(test.completed > 0) {
					html += '<h4><i class="fa fa-check-circle-o test_passed"></i> ';
					if(currentScores[file] == undefined)
						currentScores[file] = 0;
					currentScores[file] += (test.points * test.completed);
					currentScores.total += (test.points * test.completed);
				} else {
					html += '<h4><i class="fa fa-times-circle-o test_failed"></i> ';
				}
				if(undefined == test.description) {
					test.description = '<ol>';
					for(var j = 0; j < test.conditions.length; j++) {
						var c = test.conditions[j];
						var passCount = 0;
						var iterationStatus = '';						
						var optionalClass = '';
						if(c.met) {
							for(var k = 0; k < c.met.length; k++) {
								if(c.met[k])
									passCount++;
							}
							if(c.met.length > 1) {
								iterationStatus = passCount + "/" + c.met.length + ' ';
								if(passCount < c.met.length) {
									optionalClass = ' test_partial';
								}
							}
						}

						var indicator = '<i class="fa fa-' + ((passCount > 0)?'check test_passed':'times test_failed') + optionalClass + '"></i> ' + iterationStatus;
						if(c.o) {
							test.description += '<li> ' + indicator + 'Display the text: <pre>' + c.o + '</pre></li>';
						}
						if(c.p) {
							if(c.i) {
								test.description += '<li>' + indicator + 'When asked <pre>' + c.p + '</pre>, the user inputs: <pre>' + c.i + '</pre> </li>';
							} else {
								test.description += '<li>' + indicator + 'Ask the user: <pre>' + c.p + '</pre></li>';
							}
							
						}
						if(c.g) {
							test.description += '<li>' + indicator + 'Set the variable ' + c.g + ' to the value ' + c.v + '</li>';
						}
						if(c.f) {
							test.description += '<li>' + indicator + 'Save <pre>' + c.d + '</pre> into the file ' + c.f + '</li>'
						}
						if(c.c) {
							test.description += '<li>' + indicator + 'Add a comment including ' + c.c + '</li>';
						}
						if(c.l) {
							test.description += '<li>' + indicator + 'Include the text <pre>' + c.l + '</pre> in your code</li>';
						}
					}
					test.description += '</ol>';
					html += test.name + '</h4>' + test.description ;
					delete test.description;
				} else {
					html += test.name + '</h4>' + test.description ;
				}

				html += '</div>';
				
				
				
			}
			var target = '';
			switch(file) {
				case 'try_it.py':target = 'tests_try';break;
				case 'debug_it.py':target = 'tests_debug';break;
				case 'extend_it.py':target = 'tests_extend';break;
			}
			$('#' + target).html(html);
			
		}
		$('#points').html('<p>score:</p><span class="score">' + currentScores.total + '</span><p>' + (maxScores.total - currentScores.total) + ' more available</p>');
		$('.try_total').html(" / " + maxScores['try_it.py']);
		$('.debug_total').html(" / " + maxScores['debug_it.py']);
		$('.extend_total').html(" / " + maxScores['extend_it.py']);
		$('.total_total').html(" / " + maxScores['total']);
		
		PythonIDE.maxScores = maxScores;
		
	},
	
	inputLog: {},
	
	init: function(style) {
		PythonIDE.showHint(PythonIDE.welcomeMessage);
		window.onresize = PythonIDE.autoSize;
		PythonIDE.updateFileTabs();
		PythonIDE.updateTests();
		
		PythonIDE.editor = ace.edit("editor");
		PythonIDE.editor.getSession().setMode("ace/mode/python");
		PythonIDE.editor.setTheme("ace/theme/dreamweaver");
		PythonIDE.editor.$blockScrolling = Infinity;
		PythonIDE.editor.setValue(PythonIDE.files['try_it.py']);
		PythonIDE.editor.clearSelection();
		PythonIDE.editor.setShowPrintMargin(false);
		
		PythonIDE.load();
		
		if(style != "embed" && style != "run") {
			PythonIDE.editor.focus();
		}
		
		$('#btn_stop').click(function() {
			localStorage.loadAction = "restoreCode";
			location.reload();
		});
		
		PythonIDE.editor.on("change", function(e) {
			if(PythonIDE.abortDebug) {
				PythonIDE.abortDebug();
			}
			PythonIDE.files[PythonIDE.currentFile] = PythonIDE.editor.getValue();
		});
		
		
		
		$('#file_settings button').click(function(e) {
			switch(e.currentTarget.id) {
				case 'btn_file_rename':
					var newFileName = $('#txt_file_name').val();
					if(!newFileName.match(/^[A-Za-z0-9_.]+$/)){
						PythonIDE.showHint("Invalid file name");
						break;
					}
					if(PythonIDE.files[newFileName]) {
						PythonIDE.showHint('A file with this name already exists');
						break;
					}
					var fileContents = PythonIDE.files[PythonIDE.currentFile]
					delete PythonIDE.files[PythonIDE.currentFile];
					PythonIDE.currentFile = newFileName;
					PythonIDE.files[PythonIDE.currentFile] = fileContents;
					PythonIDE.updateFileTabs();
					$('#file_settings').hide().modal("hide");
					PythonIDE.editFile(newFileName);
					
				break;
				case 'btn_file_delete':
					delete PythonIDE.files[PythonIDE.currentFile];
					PythonIDE.editFile("try_it.py");

				case 'btn_file_cancel':
					$('#file_settings').hide().modal("hide");
				break;
			}
			//console.log(e.currentTarget.id);
		});
		
		if(localStorage && !localStorage.options) {
			localStorage.options = {
				codeSize:12,
				outputSize: 12,
				outputTransparency: 0,
				stepAnimtime: 1000
			}
		}
		
		
		window.onerror=function(err) {
			var msg = err.toString().replace("Uncaught ", "");
			var html = '<span class="error">' + msg + '</span>';
			PythonIDE.showHint(html);
			PythonIDE.python.output(html);
			console.log(err);
			
			return true;
		}
		
		// setup keyboard shortcutts
		$(window).keydown(function(e) {
			if(e.ctrlKey) {
				switch(e.keyCode) {
					case 13: // CTRL + ENTER = run / stop
						PythonIDE.runCode("normal");
						e.preventDefault();
					break;
					
					case 83: // CTRL + S = save
						PythonIDE.save();
						e.preventDefault();
						break;
						
					case 190: // CTRL + . = step | CTRL SHIFT + . = anim
						if(e.altKey) {
							if(PythonIDE.abortDebug) {
								PythonIDE.abortDebug();
							}
						} else {
							if(e.shiftKey) {
								PythonIDE.runCode("anim");
							} else {
								PythonIDE.runCode("step");
							}
						}
						e.preventDefault();
						break;
						
					case  66: // Ctrl B load
						PythonIDE.load();
					break;
					default:
						//console.log("Control + keycode:" + e.keyCode);
					break;
				}
			}
		});

		
		$('#btn_login').click(function() {
			var username = $('#txt_username').val();
			var password = $('#txt_password').val();
			//console.log(username, password);
		});
		
		
		
		
		
		if(localStorage.loadAction) {
			switch(localStorage.loadAction) {
				case 'showShare':
					PythonIDE.showShare();
				break;
				case 'restoreCode':
					PythonIDE.editor.setValue(localStorage.lastRunCode);
				break;
			}
			delete localStorage.loadAction;
			PythonIDE.editor.clearSelection();
			PythonIDE.editor.focus();
		}
		
		(Sk.TurtleGraphics || (Sk.TurtleGraphics = {})).target = 'canvas';
		
		Sk.inputfun = function(prompt) {
			//return window.prompt(prompt);
			var p = new Promise(function(resolve, reject) {
				if($('#raw_input_holder').length > 0) {
					return;
				}
				PythonIDE.python.output('<form><div id="raw_input_holder"><label for="raw_input">' + prompt + '</label><input type="text" name="raw_input" id="raw_input" value=""/><button id="raw_input_accept" type="submit">OK</button></div></form>');
				
				var btn = $('#raw_input_accept').click(function() {
					var val = $('#raw_input').val();
					$('#raw_input_holder').remove();
					PythonIDE.python.output(prompt + ' <span class="console_input">' + val + "</span>\n");
					
					if(!PythonIDE.inputLog[prompt])
						PythonIDE.inputLog[prompt] = [];
					PythonIDE.inputLog[prompt].push(val);
					resolve(val);
				});
				$('#raw_input').focus();
			});
			return p;
		}
		Sk.execLimit = Infinity;
		Sk.configure({
			breakpoints:function(filename, line_number, offset, s) {
				//console.log(line_number, PythonIDE.runMode);
				if(PythonIDE.runMode == "anim") {
					if(PythonIDE.continueDebug) {
						PythonIDE.animTimeout = setTimeout(function() {
							PythonIDE.runCode("anim");
						}, 1000);
					}
				}
				PythonIDE.editor.gotoLine(line_number);
				
				// check for errors in external libraries
				if(PythonIDE.unhandledError) {
					throw PythonIDE.unhandledError;
				}
				return true;
			},
			debugging: true,
			output: PythonIDE.python.output,
			readFile: PythonIDE.readFile,
			writeFile: PythonIDE.writeFile,
			read: PythonIDE.python.builtinread});
			
		Sk.externalLibraries = {
			schooldirect: {
				path: 'js/skulpt/schooldirect/__init__.js'
			},
			withcode: {
				path: 'js/skulpt/withcode/__init__.js'
			},
			sqlite3: {
				path: 'js/skulpt/sqlite3/__init__.js'
			},
			microbit: {
				path: 'js/skulpt/microbit/__init__.js'
			},
			music: {
				path: 'js/skulpt/music/__init__.js'
			},
			py3d: {
				path: 'js/skulpt/py3d/__init__.js',
				dependencies: ['js/skulpt/py3d/three.js'],
			}
		};

		// expand editor to fit height of the screen.
		$('.holder').css({height: window.innerHeight - 80});

		$('#footer').css({bottom: 0});

		$('.toolButton').hover(function(e) {
			// mouse over tool button
			PythonIDE.showHint($('#' + e.currentTarget.id).attr('alt'));
		}, function(e) {
			// mouse out tool button
			PythonIDE.showHint(PythonIDE.welcomeMessage);
		}).click(function(e) {
			// tool button click
			switch(e.currentTarget.id) {
				case 'btn_edit':
					window.open(window.location.href.replace('embed', 'python').replace('run', 'python'));
				break;
				
				case 'btn_show_recover':
					PythonIDE.recover();
				break;
				
				case 'btn_stopRunning':
					localStorage.loadAction = "restoreCode";
					location.reload();
				break;
				
				case 'btn_tools':
					PythonIDE.toolsVisible = !PythonIDE.toolsVisible;
					if(PythonIDE.toolsVisible) {
						$('.toolButton').addClass('visibleButton');
					} else {
						$('.toolButton').removeClass('visibleButton');
					}
				break;
				
				case 'btn_show_output':
					$('#dlg').show().modal();
				break;
	
				
				case 'btn_run':
					PythonIDE.runCode(PythonIDE.runMode);
				break;
			}
		});
		if(style == "run") {
			
			$('#editor').hide();
			var output = $('#output').detach();
			$('#holder').append(output);
			$('#dlg').remove();
			PythonIDE.whenFinished = function() {
				var link = window.location.href.replace('run/', 'python/');
				var html = '<div><a class="nounderline" href="http://withcode.uk" target="_blank"><h1 id="title" onmouseover="animateTitle(\'create.withcode.uk\', \'title_text\')"><span class="brackets">{</span><span id="title_text">withcode.uk</span><span class="brackets">}</span></h1></a></div>';
				PythonIDE.python.output(html + '<p>This python app was written using <a href="https://create.withcode.uk">create.withcode.uk</a>. <a href="' + link + '">Click here to edit the python code and create/share your own version</a> or check out <a href="http://blog.withcode.uk">blog.withcode.uk</a> for ideas, tips and resources</p> <button id="btn_run_again">Run again</button>');
				$('#btn_run_again').click(function() {PythonIDE.runCode()});
			}
			PythonIDE.runCode();
		}
	}
}
return PythonIDE;
});