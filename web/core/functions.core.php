<?php

function list_modules_js() {
	$src = array(
		homefs::app('conf')->config()['dir']['js'],
		homefs::app('conf')->config()['dir']['modules']
	);
	foreach($src as $path) {
		$hdl = opendir($path);
		while($file = readdir($hdl)) {
			if(preg_match("/\.js$/", $file)) {
				$mtime = filemtime($path."/".$file);
				echo "<SCRIPT src=\"/$path/$file?r=$mtime\"></SCRIPT>\n";
			}
		}
		closedir($hdl);
	}
}

function list_modules_css() {
	$src = array(
		homefs::app('conf')->config()['dir']['css'],
		homefs::app('conf')->config()['dir']['modules']
	);
	foreach($src as $path) {
		$hdl = opendir($path);
		while($file = readdir($hdl)) {
			if(preg_match("/\.css$/", $file)) {
				$mtime = filemtime($path."/".$file);
				echo "<LINK href=\"/$path/$file?r=$mtime\" rel=\"stylesheet\" type=\"text/css\">\n";
			}
		}
		closedir($hdl);
	}
}

function list_templates() {
	$src = array(
		homefs::app('conf')->config()['dir']['templates'],
		homefs::app('conf')->config()['dir']['modules']
	);
	foreach($src as $path) {
		$hdl = opendir($path);
		while($file = readdir($hdl)) {
			if(preg_match("/\\.tmpl$/", $file)) {
				$contents = file_get_contents($path."/".$file);
				echo "$contents\n";
			}
		}
		closedir($hdl);
	}
}

function list_html() {
	$src = array(
		homefs::app('conf')->config()['dir']['modules']
	);
	foreach($src as $path) {
		$hdl = opendir($path);
		while($file = readdir($hdl)) {
			if(preg_match("/\\.html$/", $file)) {
				$contents = file_get_contents($path."/".$file);
				echo "$contents\n";
			}
		}
		closedir($hdl);
	}
}


function cdir($path) {
	if($path == '/') {
		return $path;
	} else {
		$item = explode('/', $path);
		return end($item);
	}
}

function readable_size($bytes) {
	$sfx = explode(" ", "Bytes KiB MiB GiB TiB");
	for($pw=0;$bytes>1024;$pw++)
		$bytes /= 1024;
	return sprintf(($pw>0 ? "%1.1f %s" : "%d %s"), $bytes, $sfx[$pw]);
}

function readable_date($unixtime) {
	return date("d.m.Y H:i:s", $unixtime);
}

function relative_date($inunix) {
  if($inunix == null) return '--';
  $delta = time() - $inunix;
  $ru_month = explode(' ', 'jan feb mar apr may jun jul aug sep oct nov dec');

  switch(true) {
    case ($delta < 60):			{ return $delta . " sec. ago"; }
    case ($delta < 3600):		{ return floor($delta / 60) . " min. ago"; }
    case ($delta < 86400):		{ return floor($delta / 3600) . " hr. ago"; }
    case ($delta < 604800):		{ return floor($delta / 86400) . " days ago"; }
    default:					{ return date("j", $inunix) . " " . $ru_month[date("m", $inunix)-1] . " " . date("Y, H:i:s", $inunix); }
  }
}

function fs_word_wrap($word, $wrap) {
	if(mb_strlen($word, 'utf-8') > $wrap) {
		$begin = mb_substr($word, 0, floor($wrap / 2) - 2 , 'utf-8');
		$end   = mb_substr($word, -(floor($wrap / 2) - 1), strlen($word), 'utf-8');
		return $begin . "..." . $end;
	} else {
		return $word;
	}
}

function fs_word_wrap_pixel($text, $size, $width) {
	$temp = $text;
	$iter = 0;
	
	if(gd_text_width($text, $size) <= $width)
		return $text;

	$text_len = mb_strlen($text, 'utf-8');

	for(;;++$iter) {
		$begin	= mb_substr($text, 0, floor($text_len / 2) - $iter, 'utf-8');
		$end	= mb_substr($text, ($text_len - floor($text_len / 2)) + $iter, $text_len, 'utf-8');
		$temp = $begin . '...' . $end;
		if(gd_text_width($temp, $size) <= $width) break;
	}
	return $temp;
}

function gd_text_width($text, $size) {
	$type_space = imagettfbbox($size, 0, $_SERVER['DOCUMENT_ROOT'] . '/images/open-sans.ttf', $text);
	return $type_space[4];
}

function site_init() {
	date_default_timezone_set('Europe/Kiev');
}

function screen_quotes($text) {
	return str_replace("\"", "\\'", $text);
}

function ae_detect_ie() {
    if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
		header("Content-type: text/html; charset=utf-8");
		echo "<html><head><title>Stop Internet Explorer!</title></head><body style=\"font-family:tahoma;font-size:14px\">
		<center>
			<img style=\"margin:50px\" src=\"/images/noie.jpg\" alt=\"Stop IE!\" width=\"350\" height=\"350\"><br>
			Sorry, but I have no time to make this site working in IE!
		</center></body></html>";
		die();
	}
}

function _get_default($key, $default = NULL) {
	return isset($_GET[$key]) ? $_GET[$key] : $default;
}

function _post_default($key, $default = NULL) {
	return isset($_POST[$key]) ? $_POST[$key] : $default;
}

function _my_execute($cmd) {
	$data = array();
	$var = popen($cmd, "r");
	while($data[] = fread($var, 4096));
	pclose($var);
	return implode('', $data);
}

function dieout($message) {
	die("<html><body><h1>$message</h1></body></html>");
}

function my_ghandler($buffer, $mode) {
	$encoded = gzencode($buffer);
	$host = $_SERVER['HTTP_HOST'];
	$uri = $_SERVER['REQUEST_URI'];

	//homefs::app('myRedis')->_()->setex('uri_cache:' . $host . ':' . md5($uri), 86400, $encoded);
	
	//limitFlood();

	header("Content-Encoding: gzip");
	header('Vary: accept-encoding');
	return $encoded;
}

function limitFlood() {

	$remote = $_SERVER['HTTP_X_REAL_IP'];
	homefs::app('myRedis')->_()->incr('homefs.biz:floodControl:' . $remote);
	homefs::app('myRedis')->_()->setTimeout('homefs.biz:floodControl:' . $remote, 1);
	
	$keys = homefs::app('myRedis')->_()->keys('homefs.biz:floodControl:*');
	foreach($keys as $key) {
		$val = homefs::app('myRedis')->_()->get($key);
		//if($val > 10)
		//	$keys = homefs::app('myRedis')->_()->setex('homefs.biz:floodList:' . $remote, 300, true);
	}	
}

function checkFlood() {
	$remote = $_SERVER['HTTP_X_REAL_IP'];
	if(homefs::app('myRedis')->_()->exists('homefs.biz:floodList:' . $remote))
		return true;
	else
		return false;
}

/*
function mime_content_type($filename) {

        $mime_types = array(

			// other
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } else {
            return 'application/octet-stream';
        }
}
*/

?>