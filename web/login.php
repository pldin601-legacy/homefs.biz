<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/core/application.class.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/core/functions.core.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/core/common.core.php");

$config = homefs::app('conf')->config();
homefs::app('account')->login_session();

if(isset($_GET['do'])) {
	switch($_GET['do']) {
		case 'in': {
			if(isset($_POST['login'], $_POST['password'])) {
				homefs::app('account')->login_user($_POST['login'], $_POST['password']);
			}
			break;
		}
		case 'out': {
			 homefs::app('account')->logout_user();
		}
	}
}

echo json_encode(array(
	'status' 	=> 'SUCCESS', //homefs::app('account')->get_last_status(),
	'uid'		=> 1, //homefs::app('account')->get_loggedin_uid(),
	'name'		=> 'Guest', //homefs::app('account')->get_loggedin_user()
));
