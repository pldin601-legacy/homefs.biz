<?php

require_once("config.class.php");
require_once("database.class.php");
require_once("filesystem.class.php");
require_once("account.class.php");

class homefs {
	static private $objects = array();
	static function app($class) {
		$args = func_get_args();
		if(count($args) == 0) {
			return null;
		} else {
			$class = $args[0];
			$send = array_slice($args, 1);
		}
		if(isset(self::$objects{$class})) {
			return self::$objects{$class};
		} else {
			$refl = new ReflectionClass($class);
			self::$objects{$class} = call_user_func_array(array($refl, "newInstance"), $send);
			return self::$objects{$class};
		}
	}
}

class session {
	function get($arg) {
		if(isset($_COOKIE['PHPSESSID'])) {
			if(session_status() == PHP_SESSION_NONE) $this->init();
			if(isset($_SESSION[$arg])) return $_SESSION[$arg]; else return null;
		} else return null;
	}
	function test($arg) {
		if(isset($_COOKIE['PHPSESSID'])) {
			if(session_status() == PHP_SESSION_NONE) $this->init();
			if(isset($_SESSION[$arg])) return true; else return false;
		} else return false;
	}
	function set($arg, $val) {
		if(session_status() == PHP_SESSION_NONE) $this->init();
		$_SESSION[$arg] = $val;
		return $this;
	}
	function rem($arg) {
		if(session_status() == PHP_SESSION_NONE) $this->init();
		if(isset($_SESSION[$arg])) unset($_SESSION[$arg]);
		return $this;
	}
	function destroy() {
		unset($_SESSION);
		session_unset();
		session_destroy();
	}
	function end() {
		if(session_status() != PHP_SESSION_NONE) {
			session_write_close();
			$_SESSION = null;
		}
		return $this;
	}
	private function init() {
		session_set_cookie_params(60 * 60 * 24 * 14);
		session_start();
	}
}

class myRedis {
	private $handle;
	function __construct() {
		$this->handle = new Redis();
		$this->handle->connect('127.0.0.1', 6379);
	}
	function _() {
		return $this->handle;
	}
}

?>