<?php
function withcode_get_user_code($withcode_instance, $userid) {
	global $DB;
	$result = (object)array('timecreated'=>0, 'files'=>'', 'score_try'=>0, 'score_debug'=>0, 'score_extend'=>0, 'score_total'=>0);
	if($DB->record_exists('withcode_snippet', array('userid'=>$userid, 'withcodeid'=>$withcode_instance))) {
		$result = $DB->get_record('withcode_snippet', array('userid'=>$userid, 'withcodeid'=>$withcode_instance));
	}
	return $result;
}

function withcode_get_max_scores($withcode) {
	$result = new stdClass();
	$result->success = true;
	$result->max_try = 0;
	$result->max_debug = 0;
	$result->max_extend = 0;
	$result->max_total = 0;
	try {
		if($tests = json_decode($withcode->testtry)) {
			foreach($tests as $test) {
				$result->max_try += $test->points;
			}
		} 
	} catch(Exception $e) {
		$result->success = false;
	}
	$result->max_total += $result->max_try;

	try {
		if($tests = json_decode($withcode->testdebug)) {
			foreach($tests->tests as $test) {
				$result->max_debug += $test->points;
			}
		} 
	} catch(Exception $e) {
		$result->success = false;
	}
	$result->max_total += $result->max_debug;


	try {
		if($tests = json_decode($withcode->testextend)) {
			foreach($tests->tests as $test) {
				$result->max_extend += $test->points;
			}
		} 
	} catch(Exception $e) {
		$result->success = false;
	}
	$result->max_total += $result->max_extend;
	return $result;
	
}

function withcode_show_snippet($snippet) {
	?>
    <!DOCTYPE html>
    <html>
    <head>
    <style>
	form {
		display: none;
	}
	body {
		text-align: center;
		font-family: "Courier New", Courier, monospace;
		color: #360;
	}
	</style>
    </head>
    <h1>Redirecting... please wait</h1>
    <body>
    <form id="redirect" action="https://create.withcode.uk" method="post">
    <input name="py_files" type="text" value="<?php echo(htmlspecialchars($snippet->files));?>">
    <input type="submit">
    </form>
    <script type="text/javascript">
    document.getElementById('redirect').submit();
    </script>
    </body>
    </html>
    <?php
}

function withcode_get_time_diff($timestamp) {
	if($timestamp == 0) {
		return "never";
	}
	
	$seconds = time() - $timestamp;
	if($seconds < 60) {
		return $seconds . "s ago";
	}
	
	$mins = floor($seconds / 60);
	if($mins < 60) {
		return $mins . " mins ago";
	}
	
	$hours = floor($mins / 60);
	if($hours < 24) {
		return $hours . " hours ago";
	}
	
	$days = floor($hours / 24);
	if($days < 7) {
		return $days . " days ago";
	}
	
	$weeks = floor($days / 7);
	if($weeks < 52) {
		return $weeks . " weeks ago";
	}
	
	$years = floor($weeks / 52);
	return $years . " years ago";
}