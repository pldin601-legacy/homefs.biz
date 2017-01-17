<?php



require_once($_SERVER['DOCUMENT_ROOT'] . "/core/application.class.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/core/functions.core.php");

$config = homefs::app('conf')->config();
$fs = homefs::app('filesystem');

site_init();
set_time_limit(0);

homefs::app('account')->login_session();

if(homefs::app('account')->get_loggedin_uid() == 0) {
//	header('HTTP/1.1 403 Forbidden');
//	die("Forbidden. Please sign in.");
}

if(!isset($_GET['id'] )) {
	header('HTTP/1.1 404 Not Found');
	dieout("File not set!");
}

$filepath = $fs->get_file_path($_GET['id']);

if(!$filepath)  {
	header('HTTP/1.1 404 Not Found');
	dieout("File not found in database!");
}

if(! file_exists($filepath) ) {
	header('HTTP/1.1 404 Not Found');
	dieout("File not found on disks!");
}

/* Downloading section */
$filename = $fs->get_file_name($_GET['id']);
$filesize = filesize($filepath);

if (isset($_SERVER['HTTP_RANGE'])) {
	$range = $_SERVER['HTTP_RANGE'];
	$range = str_replace('bytes=', '', $range);
	list($start, $end) = explode('-', $range);
} else {
	$start = 0; 
}

$end = filesize($filepath);

if(isset($range)) {
	header($_SERVER['SERVER_PROTOCOL'].' 206 Partial Content');
} else {
	header($_SERVER['SERVER_PROTOCOL'].' 200 OK');
}

header('Accept-Ranges: bytes');
header("Access-Control-Allow-Headers: range, accept-encoding");
header("Access-Control-Allow-Origin: *");

header('Content-Disposition: filename="' . $filename . '"');
header('Content-Length: ' . $filesize);
header('Content-Type: ' . $fs->mime_content_type($filename));

if(isset($range)) header("Content-Range: bytes " . $start . "-" . ($filesize - 1) . "/" . $filesize);

homefs::app('session')->end();

$fh = fopen($filepath, "r");
if (isset($range)) {
	fseek($fh, $start);
}

while(!feof($fh)) {
	set_time_limit(30);
	$data = fread($fh, 4096);
	echo $data;
	flush();
}


?>
