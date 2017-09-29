<?php 
function insertHeader($style="normal"){
?>
<!DOCTYPE html>
<html>
<head>
<style>

#loading{
	position:absolute;
	top: 40%;
	width:33%;
	left: 33%;
	text-align: center;
	background-color: #CCC;
	border-radius: 10px;
	padding: 20px;
	animation: loading 2s alternate infinite;
	border: 1px solid #000;
}
@keyframes loading{
	from {background-color: #CCC;}
	to {background-color: #0C0;}
}

</style>

	<meta charset="UTF-8">
	<meta name="description" content="Write, run, debug and share python code in your browser"/>
	<title>Create with code</title>
	<link rel="stylesheet" href="/styles.css">
	<script src="/lib/ace/ace.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.2/jquery.min.js"></script>
	<script src="/lib/lib.js"></script>
	<script src="/lib/skulpt/skulpt.min.js"></script>
	<script src="/lib/skulpt/skulpt-stdlib.js"></script>
	<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
	<script src="/lib/jq/jquery.ui.touch-punch.min.js"></script>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	<link href='https://fonts.googleapis.com/css?family=Quicksand' rel='stylesheet' type='text/css'>
</head>

<body>
<?php 
include_once('ga.php'); 
include_once('lib/api.php');
if($style=="normal") {
?>
<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<!-- withcode.cuk -->
<center>
<ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-3910402064044077"
     data-ad-slot="5065905609"
     data-ad-format="auto"></ins></center>
<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>
<div>
<a class="nounderline" href="http://withcode.uk" target="_blank">
<h1 id="title" onmouseover="animateTitle('create.withcode.uk', 'title_text')"><span class="brackets">{</span><span id="title_text">withcode.uk</span><span class="brackets">}</span></h1>
</a>
</div>
<?php }  ?>



<div id="loading">
Loading... please wait
</div>
<?php
if($style == "embed"){
	echo('<div id="file_spacer">&nbsp;</div>');
}
?>
<div id="holder" class="holder" style="display:none">

<?php 


if($style!="run") {?>
<span id="file_tabs"><span class="file_tab file_tab_selected">mycode.py</span></span>
<?php } 
}
function insertEditor($style="normal", $code='') {?>
<pre>
<div id="editor"><?php echo($code);?></div>
</pre>
</div>

<?php }

function insertFooter($style="normal"){
?>

<div id="userDetails">You are not logged in</div>
<div id="hintBar"></div>
<span id="footer">
<?php if($style=="normal") {?>
	<img alt="Click to show/hide tool buttons" title="Show/hide tools" class="toolButton" src="/media/tools.png" id="btn_tools">
	<img title="Run" alt="Click to run your code" id="btn_run" class="toolButton hiddenButton" src="/media/play.png">
	<img alt="Stop running" title="Stop" class="toolButton hiddenButton" src="/media/stop.png" id="btn_stopRunning">
	<img title="Console" alt="Show python output" id="btn_show_output" class="toolButton hiddenButton" src="/media/console.png">
	<img title="Share" alt="Share your code" id="btn_show_share" class="toolButton hiddenButton" src="/media/share.png">
	<img title="Settings" alt="Customize the screen" id="btn_show_settings" class="toolButton hiddenButton" src="/media/settings.png">
	<img title="Code recovery" alt="Recover your code from a previous session" id="btn_show_recover" class="toolButton hiddenButton" src="/media/recover.png">
<?php } else { ?>
	<img alt="Click to edit / run this code" title="Show Tools" class="toolButton" src="/media/tools.png" id="btn_edit">
<?php	
}
?>
</span>
<div id="recover" title="Recover" style="display:none"></div>
<div id="dlg" title="mycode.py" style="display:none"><div id="output"></div></div>
<div id="login" title="Login" style="display:none">
<p>Logging in lets you save and share your code</p>
<label for="txt_username">Username:</label><input type="text" id="txt_username">
<label for="txt_password">Password:</label><input type="password" id="txt_password">
<button id="btn_login">Log in</button>
</div>

<div id="file_settings" title="File" style="display:none">
<p>Be careful: if you choose to delete a file, you will not be able to recover it unless you've saved a copy</p>
<label for="txt_file_name">Rename file:</label><input id="txt_file_name" name="txt_file_name" value="">
<button id="btn_file_rename">Rename</button>
<button id="btn_file_delete">Delete</button>
<button id="btn_file_cancel">Cancel</button>
</div>

<div id="settings" title="Settings" style="display:none">
<h3>Text size</h3>
<label for="txt_code_size">Code font size:</label><input type="text" id="txt_code_size" readonly>
<div id="slider_code_size" class="slider"></div>
<label for="txt_output_size">Output font size:</label><input type="text" id="txt_output_size" readonly>
<div id="slider_output_size" class="slider"></div>

<h3>Editor colours</h3>
<div id="radio_code_style">
<p>Lighter colours are great for coding at day time. Some people prefer coding at night:</p>
<input type="radio" id="radio_code_style_light" name="radio_code_style" checked="checked"><label for="radio_code_style_light">Light</label>
<input type="radio" id="radio_code_style_dusk" name="radio_code_style"><label for="radio_code_style_dusk">Dusk</label>
<input type="radio" id="radio_code_style_dark" name="radio_code_style"><label for="radio_code_style_dark">Dark</label>
</div>

<h3>Output console colours</h3>
<p>Darker colours look more like a command prompt. Lighter colours look more like an app window:</p>
<div id="radio_output_style">
<input type="radio" id="radio_output_style_light" name="radio_output_style"><label for="radio_output_style_light">Light</label>
<input type="radio" id="radio_output_style_dusk" name="radio_output_style"><label for="radio_output_style_dusk">Dusk</label>
<input type="radio" id="radio_output_style_dark" name="radio_output_style" checked="checked"><label for="radio_output_style_dark">Dark</label>
</div>

<h3>Run mode</h3>
<p>Running your code line by line can be a useful way of finding bugs</p>
<div id="radio_run_mode">
<input type="radio" id="radio_run_mode_all" name="radio_run_mode" checked="checked"><label for="radio_run_mode_all">Whole program</label>
<input type="radio" id="radio_run_mode_single" name="radio_run_mode"><label for="radio_run_mode_single">Step through single line</label>
<input type="radio" id="radio_run_mode_anim" name="radio_run_mode"><label for="radio_run_mode_anim">Animate line by line</label>
</div>
<p>Choosing a longer time between animating each line helps you understand and explain your code as it runs</p>
<label for="txt_step_anim_time">Time delay between running each line:</label><input type="text" id="txt_step_anim_time" readonly>
<div id="slider_step_anim_time" class="slider"></div>

<p>Making your output window transparent helps you see your code underneath</p>
<label for="txt_output_transparency">Output window transparency</label><input type="text" id="txt_output_transparency" readonly>
<div id="slider_output_transparency" class="slider"></div>

</div>

<div id="share" title="Share" style="display:none">
How would you like to share your code?
<div id="radio_share_mode">
<input type="radio" id="radio_share_mode_code" name="radio_share_mode" checked="checked"><label for="radio_share_mode_code">Share link to view code</label>
<input type="radio" id="radio_share_mode_run" name="radio_share_mode"><label for="radio_share_mode_run">Share link to run code</label>
</div>
<div id="share_tabs">
<ul>
<li><a href="#share_link">Link</a></li>
<li><a href="#share_embed">Embed</a></li>
<li><a href="#share_qr">QR Code</a></li>
</ul>
<div id="description">
<p>create.withcode.uk allows you to write, run, debug and share python code in your web browser.</p>
</div>
<div id="share_link">
<p>
Anyone can access your code using this link: <input size="35" id="share_link_val">
</p>
<p>
<a target="_blank" id="share_tweet_val" href=""><img src="/media/tweet.png" alt="tweet"></a><p>Click this button to tweet a link to your code. You can edit the tweet before it posts</p>
</p>
</div>
<div id="share_embed">
<p>
Copy and paste this html to embed your code in a blog / website: <textarea rows="4" cols="35" id="share_embed_val"></textarea>
</p>
</div>
<div id="share_qr">
<p>
Anyone who scans this QR code will be able to view and run your code: <div id="share_qr_val"></div>
</p>
</div>
</div>
</div>
<script>
 var toolsVisible = false;

 
 $(function() {
	$('#loading').hide();
	$('#holder').show();
	PythonIDE.init('<?php echo($style)?>');
 });
</script>

</body>

</html>
<?php
}
?>