<?php

class conf {
	function config() {
		return array(
			'app_name'		=> 'Home File Server',
			'url'			=> 'http://homefs.tedirens.com',
			'loc_max_width'		=> 55,
			'loc_max_name_width'	=> 55,
			'file_max_width'	=> 58,

			'db' => array(
				'host'		=> 'localhost',
				'login'		=> 'root',
				'password'		=> 'GDk4F/so',
				'database'		=> 'jfs3'
			),
			'bin' => array(
				'ffmpeg'		=> '/usr/local/bin/ffmpeg',
				'mediainfo'		=> '/usr/local/bin/mediainfo'
			),
			'dir' => array(
				'site'		=> $_SERVER['DOCUMENT_ROOT'],
				'cache'		=> $_SERVER['DOCUMENT_ROOT'] . '/../cache',
				'modules'	=> 'modules',
				'js'		=> 'js',
				'css'		=> 'css',
				'templates'	=> 'templates'
			),
			'links' => array(
				'go'			=> '/index.php?id=%d',
				'get'			=> '/get/%d/%s',
				'zip'			=> '/zip/%d/%s'
			),
			'navigation' => array(
				'files_per_block' => 50
			),
			'listen_formats' => array(
				'mp3'			=> array('Content-Type: audio/mpeg', 	'-acodec libmp3lame -f mp3'),
				//'ogg'			=> array('Content-Type: audio/ogg',		'-acodec libvorbis -f ogg'),
				//'webm'			=> array('Content-Type: audio/webm',	'-acodec libvorbis -f webm'),
			)
		);
	}
}

?>