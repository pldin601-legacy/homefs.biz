<?php
class filesystem {
	private 
		$db, 
		$filetypes = array(),
		$root_dir = array(
			array(
				'id' 		=> 0,
				'name' 		=> 'Index',
				'parent_id'     => null,
				'modified' 	=> 0,
				'lft' 		=> null,
				'rgt' 		=> null
			)
		),
		$parents_buffer = array();
	function __construct() {
		$this->db = homefs::app('database');
		$this->load_file_types();
	}

	function search_files($q, $s, $c) {
		list($count, $files) = $this->db->query_found_rows("SELECT SQL_CALC_FOUND_ROWS * FROM `files` WHERE MATCH(`tags`) AGAINST(? IN BOOLEAN MODE) ORDER BY `modified` DESC LIMIT $s, $c", array($q));
		foreach($files as &$file) {
			$file['path'] = $this->build_parent_ids($file['dir_id']);
			$file['type'] = $this->get_kind_of_file($file['name']);
		}
		return array($count, $files);
	}

	function search_dirs($q, $s, $c) {
		list($count, $files) = $this->db->query_found_rows("SELECT *, SUBSTRING_INDEX(`name`, '/', -1) as `name` FROM `dirs` WHERE MATCH(`name`) AGAINST(? IN BOOLEAN MODE) ORDER BY `modified` DESC LIMIT $s, $c", array($q));
		foreach($files as &$file) {
			$file['path'] = $this->build_parent_ids($file['parent_id']);
		}
		return array($count, $files);
	}

	function search_files_count($q) {
		return $this->db->query_single_col("SELECT COUNT(*) FROM `files` WHERE MATCH(`tags`) AGAINST(? IN BOOLEAN MODE)", array($q));
	}

	function search_dirs_count($q) {
		return $this->db->query_single_col("SELECT COUNT(*) FROM `dirs` WHERE MATCH(`name`) AGAINST(? IN BOOLEAN MODE)", array($q));
	}

	function get_dup_files($s, $c) {
		$files = $this->db->query("SELECT a.* FROM `files` a WHERE (SELECT COUNT(b.`id`) FROM `files` b WHERE a.`md5` = b.`md5` AND a.`id` != b.`id`) ORDER BY a.`md5` LIMIT $s, $c", array());
		foreach($files as &$file) {
			$file['path'] = $this->build_parent_ids($file['dir_id']);
			$file['type'] = $this->get_kind_of_file($file['name']);
		}
		return $files;
	}

	function dup_files_count() {
		return $this->db->query_single_col("SELECT SUM(`copies`) FROM (SELECT COUNT(`md5`) as `copies` FROM `files` GROUP BY `md5` HAVING COUNT(`md5`) > 1) t");
	}
	
	/* Search by metadata tags */
	function get_files_by_artist($artist, $s, $c) {
		$files = $this->db->query("SELECT * FROM `files` a WHERE `id` IN (SELECT `id` FROM `metadata` WHERE `artist` = ? ORDER BY `album`, `date`, `tracknumber`) LIMIT $s, $c", array($artist));
		foreach($files as &$file) {
			$file['path'] = $this->build_parent_ids($file['dir_id']);
			$file['type'] = $this->get_kind_of_file($file['name']);
		}
		return $files;
	}
	
	function count_files_by_artist($artist) {
		return $this->db->query_single_col("SELECT COUNT(*) FROM `metadata` WHERE `artist` = ?", array($artist));
	}

	function get_files_by_genre($genre, $s, $c) {
		$files = $this->db->query("SELECT * FROM `files` WHERE `id` IN (SELECT `id` FROM `metadata` WHERE `genre` LIKE ? ORDER BY `artist`, `date`, `album`, `tracknumber`) LIMIT $s, $c", array($genre));
		foreach($files as &$file) {
			$file['path'] = $this->build_parent_ids($file['dir_id']);
			$file['type'] = $this->get_kind_of_file($file['name']);
		}
		return $files;
	}
	
	function count_files_by_type($type) {
		return $this->db->query_single_col("SELECT COUNT(*) FROM `files` WHERE `extension` IN (SELECT `extension` FROM `extensions` WHERE `type` = ?)", array($type));
	}

	function get_files_by_type($type, $s, $c) {
		$files = $this->db->query("SELECT * FROM `files` WHERE `extension` IN (SELECT `extension` FROM `extensions` WHERE `type` = ?) ORDER BY `dir_id`, `name` LIMIT $s, $c", array($type));
		foreach($files as &$file) {
			$file['path'] = $this->build_parent_ids($file['dir_id']);
			$file['type'] = $this->get_kind_of_file($file['name']);
		}
		return $files;
	}
	
	function count_files_by_genre($genre) {
		return $this->db->query_single_col("SELECT COUNT(*) FROM `metadata` WHERE `genre` LIKE ?", array($genre));
	}
	
	function encode_query($query) {

		$words = preg_split("/[\.\,\_\s\+\-\<\>\(\)\~\*\"\/\\\\]+/", $query);
		$words_filtered = array();
	
		foreach($words as $word) {
			if(strlen($word) > 1) 
				$words_filtered[] = "+" . $word;
		}
		return implode(' ', $words_filtered);

	}

	function list_files($id, $s, $c) {
		$files = $this->db->query("SELECT * FROM `files` WHERE `dir_id` = ? ORDER BY `extension`, `name` LIMIT $s, $c", array($id));
		foreach($files as &$file) {
			$file['path'] = $this->build_parent_ids($file['dir_id']);
			$file['type'] = $this->get_kind_of_file($file['name']);
		}
		return $files;
	}

	

	function list_dirs($id, $s, $c) {
		return $this->db->query("SELECT *, SUBSTRING_INDEX(`name`, '/', -1) as `name` FROM `dirs` WHERE `parent_id` <=> ? ORDER BY `name` LIMIT $s, $c", array($id));
	}
	
	function count_dirs($id) {
		return $this->db->query_single_col("SELECT COUNT(*) FROM `dirs` WHERE `parent_id` <=> ?", array($id));
	}

	function count_files($id) {
		return $this->db->query_single_col("SELECT COUNT(*) FROM `files` WHERE `dir_id` = ?", array($id));
	}
	
	function total_dirs() {
		return $this->db->query_single_col("SELECT COUNT(*) FROM `dirs` WHERE 1");
	}
	
	function total_files() {
		return $this->db->query_single_col("SELECT COUNT(*) FROM `files` WHERE 1");
	}
	
	function total_size() {
		return $this->db->query_single_col("SELECT SUM(`size`) FROM `files` WHERE 1");
	}

	function fs_modified() {
		return $this->db->query_single_col("SELECT `value` FROM `statistics` WHERE `parameter` = 'index_mtime'");
	}

	function total_duplicates() {
		return $this->db->query_single_col("SELECT COUNT(*) FROM (SELECT `md5` FROM `files` GROUP BY `md5` HAVING COUNT(`md5`) > 1) t");
	}
	
	function get_dir($id) {
		if(is_null($id))
			return $this->root_dir;
		else
			return $this->db->query("SELECT *,SUBSTRING_INDEX(`name`, '/', -1) as `name` FROM `vi_dirs` WHERE `id` <=> ?", array($id));
	}
	
	function get_dir_full($id) {
		if(is_null($id))
			return $this->root_dir;
		else
			return $this->db->query("SELECT * FROM `vi_dirs` WHERE `id` <=> ?", array($id));
	}

	function get_file($id) {
		return $this->db->query_single_row("SELECT * FROM `files` WHERE `id` <=> ?", array($id));
	}
	
	function get_file_name($id) {
		return $this->db->query_single_col("SELECT `name` FROM `files` WHERE `id` <=> ?", array($id));
	}

	function get_dir_name($id) {
		if($id == 0)
			return $this->root_dir['name'];
		else
			return $this->db->query_single_col("SELECT `name` FROM `dirs` WHERE `id` <=> ?", array($id));
	}

	function get_file_path($id) {
		$fullpath = array();
		$res = $this->db->query("SELECT * FROM `files` WHERE `id` = ?", array($id));
		if($res == false) return false;
		
		$fullpath[] = $res[0]['name'];
		$parent = $res[0]['dir_id'];
		while($parent != 0) {
			$res = $this->get_dir_full($parent);
			$fullpath[] = ($res[0]['name'] != '/') ? $res[0]['name'] : '';
			$parent = $res[0]['parent_id'];
		}
		return implode('/', array_reverse($fullpath));
	}
	
	function get_parent_id($id) {
		return $this->db->query_single_col("SELECT `parent_id` FROM `dirs` WHERE `id` <=> ?", array($id));
	}
	
	function get_waveform($id) {
		return $this->db->query_single_col("SELECT `data` FROM `waveform` WHERE `id` <=> ?", array($id));
	}

	function get_scale($id) {
		return $this->db->query_single_col("SELECT `scale` FROM `waveform` WHERE `id` <=> ?", array($id));
	}
	
	function save_waveform($id, $data) {
		return $this->db->query_single_col("INSERT INTO `waveform` VALUES(?, ?) ON DUPLICATE KEY UPDATE `data` = ?", array($id, $data, $data));
	}

	function load_file_types() {
		$rows = $this->db->query("SELECT * FROM `extensions`", array());
		foreach($rows as $row)
			$this->filetypes[$row['extension']] = $row['type'];
	}
	
	function get_kind_of_file($filename) {
		$extension = pathinfo(strtolower($filename), PATHINFO_EXTENSION);
		if(isset($this->filetypes[$extension]))
			return $this->filetypes[$extension];
		else 
			return 'regular';
	}

	function build_parent_ids($id) {
	
		if(isset($this->parents_buffer[$id]))
			return $this->parents_buffer[$id];
		
		$parents = array();
		$parents[] = array(
			array(
				'id'		=> 0,
				'name'		=> 'Home File Server',
				'parent_id'	=> null,
				'modified'	=> time()
			)
		);
		$tmp = $this->db->query_single_row("SELECT `lft`, `rgt` FROM `vi_dirs` WHERE `id` <=> ?", array($id));
		foreach($this->db->query("SELECT `id`,SUBSTRING_INDEX(`name`, '/', -1) as `name`,`parent_id`,`modified` FROM `vi_dirs` WHERE (`lft` <= ? AND `rgt` >= ?) ORDER BY `lft` ASC", array($tmp['lft'], $tmp['rgt'])) as $row)
			$parents[] = array($row);

		$this->parents_buffer[$id] = $parents;
		return $parents;
	}


	function concat_files_ids($array) {
		$ids = array();
		foreach($array as $item) {
			array_push($ids, $item['id']);
		}
		return implode(',',$ids);
	}
	
	/* File System Directory Info */
	function get_directory_info($id) {
		list($count, $size) = $this->db->query_single_row("SELECT COUNT(*), SUM(`size`) FROM `files` WHERE `dir_id` <=> ?", array($id), true);
		$mtime = $this->db->query_single_col("SELECT `modified` FROM `dirs` WHERE `id` = ?", array($id));
		return array($count, $size, $mtime);
	}
	
	/* File System Metadata Info */
	function get_file_metadata($id) {
		$res = $this->db->query("SELECT * FROM `metadata` WHERE `id` IN ($id)");
		$output = array();
		foreach($res as $i)	$output[$i['id']] = $i;
		return $output;
	}

	function get_single_file_metadata($id) {
		$res = $this->db->query_single_row("SELECT * FROM `metadata` WHERE `id` = ?", array($id));
		return $res;
	}
	
	function log_search_request($query) {
		$this->db->query("INSERT INTO `search_requests` VALUES (1, ?) ON DUPLICATE KEY UPDATE `count` = `count` + 1", array($query));
	}
	
	function get_search_requests($like) {
		return $this->db->query("SELECT `request`, `count` FROM `search_requests` WHERE `request` LIKE ? ORDER BY `count` DESC LIMIT 10", array($like . '%'));
	}
	
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

}
?>