<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/core/application.class.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/core/functions.core.php");

$config 	= homefs::app('conf')->config();

site_init();
homefs::app('account')->login_session();

$format = isset($_GET['format'], $config['listen_formats'][$_GET['format']]) ? $_GET['format'] : 'mp3';

/* Quality settings */
if( preg_match("/^(172\.16\.|127\.0\.0\.1)/", $_SERVER['HTTP_X_REAL_IP']) )
	$params = array(320, 2, false);
elseif(homefs::app('account')->get_loggedin_uid() == 0)
	$params = array(128, 2, false);
elseif(preg_match("/^212.26.135.202$/", $_SERVER['HTTP_X_REAL_IP']))
	$params = array(96, 2, true);
else
	$params = array(256, 2, false);

homefs::app('session')->end();

list($inbitrate, $inchannels, $throttle) = $params;


if(!isset($_GET['id'] )) {
	header('HTTP/1.1 404 Not Found');
	die('File not found');
}

$filepath = homefs::app('filesystem')->get_file_path($_GET['id']);

if(!$filepath)  {
	header('HTTP/1.1 404 Not Found');
	die('File not found');
}

if(! file_exists($filepath) ) {
	header('HTTP/1.1 404 Not Found');
	die('File not found');
}

/* Downloading section */
$file 			= homefs::app('filesystem')->get_file($_GET['id']);
$filesize 		= $file['size'];
$mtime			= $file['modified'];

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $mtime) {
	header('HTTP/1.1 304 Not Modified');
	die();
} else {
	header("Last-Modified: " . gmdate("D, d M Y H:i:s", $mtime) . " GMT");
	header('Cache-Control: max-age=0');
}

$metadata 		= homefs::app('filesystem')->get_single_file_metadata($_GET['id']);
$duration 		= isset($metadata) 		? $metadata['duration'] 	: 0;
$bitrate 		= isset($metadata) 		? $metadata['bitrate'] 		: 0;

$projected_size	= (int) ($duration * ($inbitrate * 1000 / 8));

$sc = homefs::app('filesystem')->get_scale($_GET['id']);

if($sc <= 0.5)
	$scale 		= 2;
else
	$scale 		= 1;

header('Content-Length: ' . $projected_size);

header($config['listen_formats'][$_GET['format']][0]);

$filepath_esc = escapeshellarg($filepath);
$timestart = 0;

do_this("ffmpeg -ss {$timestart} -i {$filepath_esc} -vn -ar 44100 -ac {$inchannels} {$config['listen_formats'][$format][1]} -ab {$inbitrate}k -af volume={$scale} -map_metadata -1 - 2>/dev/null", $projected_size, $throttle, $inbitrate);


function do_this($cmd, $elapsed, $throttle, $inbitrate) {

//	error_log("$cmd");

	$pre_seconds = 5;
	$buffer = 4096;
	
	$pre_load  = ($inbitrate * $pre_seconds) * 125;
	$pipe_size = $inbitrate * 125 * 2;
	$delay_sec = 1 / ($pipe_size / $buffer) * 1000000;
	
	$proc = popen($cmd, "r");
	$actual_size = 0;
  
	if(!$proc) return 1;
	$dt = time();
	
	$time_start = microtime(true);	// begin counting timer

	$hC = true;
	while($hC) {
		$header = fread($proc, 3); // strip metadata
		if($header == 'ID3') {
			$ver = fread($proc, 3);
			$sizec = fread($proc, 4);
			$size = unpack("N", $sizec);
			$size = $size[1];
			$id3 = fread($proc, $size);
		} else {
			$hC = false;
			echo $header;
			$actual_size += strlen($header);
		}
	}

	while( $data = fread($proc, $buffer) ) {

		if($actual_size + strlen($data) > $elapsed) {
			$data = substr($data, 0, $elapsed - $actual_size);
			$actual_size += strlen($data);
			echo $data;
			break;
		}

		$actual_size += strlen($data);

		echo $data;
		
		if(($actual_size > $pre_load) && ($throttle == 1)) usleep($delay_sec);
		
		flush();
		
		set_time_limit(30);
	}

	pclose( $proc );
	
	$padding = $elapsed - $actual_size;

	if($padding > 0) {
		$pads = floor($padding / 4096);
		for($n=1;$n<=$pads;$n++)
			echo str_repeat("\x00", 4096);
		$sub_padding = $padding - (4096 * $pads);
		echo str_repeat("\x00", $sub_padding);
	}
	
	error_log("Elapsed: $elapsed, Result: $actual_size");

}

function scale_rate($string) {
	$max = 0;
	$length = strlen($string);
	for($n=1;$n<$length;$n++) {
		$el = ord(substr($string, $n, 1));
		if($el > $max)
			$max = $el;
	}
	return 1 / 127 * $max;
}

?>
