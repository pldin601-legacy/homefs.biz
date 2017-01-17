<?php
class account {
	private $_acc = "";
	private $_uid = 0; // Default User ID for guest clients is 0
	private $_status = "";
	
	function login_user($login, $password) {
		$return = homefs::app('database')->query_single_row("SELECT * FROM `users` WHERE `user` = ? AND `pass` = ? LIMIT 1", array($login, md5($password)));
		if($return) {
			$this->_acc = $return['user'];
			$this->_uid = $return['uid'];
			homefs::app('session')
				->set('auth_user', $login)
				->set('auth_pass', md5($password));
			$this->_status = 'SUCCESS';
		} else {
			$this->_acc = "";
			$this->_uid = 0;
			$this->_status = 'WRONG';
		}
		return $this;
	}
	function login_session() {
		if(homefs::app('session')->get('auth_user') != null && homefs::app('session')->get('auth_pass') != null) {
			$return = homefs::app('database')->query_single_row("SELECT * FROM `users` WHERE `user` = ? AND `pass` = ? LIMIT 1", array(homefs::app('session')->get('auth_user'), homefs::app('session')->get('auth_pass')));
			if($return) {
				$this->_acc = $return['user'];
				$this->_uid = $return['uid'];
				$this->_status = 'SUCCESS';
			} else {
				$this->_acc = "";
				$this->_uid = 0;
				$this->_status = 'WRONG';
			}
		} else $this->_status = 'NOAUTH';
		return $this;
	}
	function create_user($login, $password) {
		$res = homefs::app('database')->query_single_col("SELECT COUNT(*) FROM `users` WHERE `user` = ? LIMIT 1", array($login));
		if($res == 0) {
			homefs::app('database')->query("INSERT INTO `users` (`user`, `pass`) VALUES (?, ?)", array($login, md5($password)));
			$this->_status = 'SUCCESS';
		} else	$this->_status = 'EXISTS';
		return $this;
	}
	function delete_user() {
		if($this->_uid > 0) {
			homefs::app('database')->query("DELETE FROM `users` WHERE `uid` = ?", array($this->_uid));
			$this->_uid = 0;
			$this->_acc = "";
			$this->_status = 'SUCCESS';
		}
	}
	function logout_user() {
		homefs::app('session')->rem('auth_user')->rem('auth_pass');
		$this->_uid = 0;
		$this->_acc = "";
		$this->_status = 'SUCCESS';
	}
	function get_last_status() {
		return $this->_status;
	}
	function get_loggedin_uid() {return $this->_uid;}
	function get_loggedin_user() {return $this->_acc;}
}


?>