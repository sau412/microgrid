<?php
require_once("../lib/settings.php");
require_once("../lib/language.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/html.php");
require_once("../lib/microgrid.php");
require_once("../lib/captcha.php");

db_connect();

$session=get_session();
$user_uid=get_user_uid_by_session($session);
$token=get_user_token_by_session($session);

// Captcha
if(isset($_GET['captcha'])) {
	captcha_show($session);
	die();
}

// JS module
if(isset($_GET['project_script'])) {
	$project_uid=stripslashes($_GET['project_script']);
	header("Content-type: application/javascript");
	echo microgrid_get_function($project_uid);
	die();
}

if(isset($_POST['action'])) $action=stripslashes($_POST['action']);
else if(isset($_GET['action'])) $action=stripslashes($_GET['action']);

if(isset($action)) {
	if(isset($_POST['token'])) $received_token=stripslashes($_POST['token']);
	else if(isset($_GET['token'])) $received_token=stripslashes($_GET['token']);
	if($received_token!=$token) die("Wrong token");

	if($action=='login') {
		$captcha_code=stripslashes($_POST['captcha_code']);
		if(captcha_check($session,$captcha_code)) {
			$login=stripslashes($_POST['login']);
			$password=stripslashes($_POST['password']);
			$message=user_login($session,$login,$password);
		} else {
			$message="login_failed_invalid_captcha";
		}
		captcha_regenerate($session);
	} else if($action=='register') {
		$captcha_code=stripslashes($_POST['captcha_code']);
		if(captcha_check($session,$captcha_code)) {
			$login=stripslashes($_POST['login']);
			$mail=stripslashes($_POST['mail']);
			$password1=stripslashes($_POST['password1']);
			$password2=stripslashes($_POST['password2']);
			$withdraw_address=stripslashes($_POST['withdraw_address']);
			$message=user_register($session,$mail,$login,$password1,$password2,$withdraw_address);
		} else {
			$message="register_failed_invalid_captcha";
		}
		captcha_regenerate($session);
	} else if($action=='logout') {
		user_logout($session);
		$message="logout_successfull";
	} else if($action=='get_balance') {
		$result['balance'] = get_user_balance($user_uid);
		echo json_encode($result);
		die();
	} else if($action=='get_new_task') {
		$project_uid=stripslashes($_POST['project']);
		$workunit_result_uid=microgrid_generate_workunit_task($project_uid,$user_uid);
		$workunit_data=microgrid_get_workunit_data_by_workunit_result_uid($workunit_result_uid);
		echo json_encode($workunit_data);
		die();
	} else if($action=='task_store_result') {
		$result=stripslashes($_POST['result']);
		$version=stripslashes($_POST['version']);
		$workunit_result_uid=stripslashes($_POST['workunit_result_uid']);
		$save_result=microgrid_save_workunit_results($user_uid,$workunit_result_uid,$version,$result);
		echo json_encode($save_result);
		users_update_active_time($user_uid);
		die();
	} else if($action=='user_change_settings') {
		$mail=stripslashes($_POST['mail']);
		$withdraw_address=stripslashes($_POST['withdraw_address']);
		$password=stripslashes($_POST['password']);
		$new_password1=stripslashes($_POST['new_password1']);
		$new_password2=stripslashes($_POST['new_password2']);

		$message=user_change_settings($user_uid,$mail,$withdraw_address,$password,$new_password1,$new_password2);
	} else if($action=='admin_change_settings' && is_admin($user_uid)) {
		$login_enabled=stripslashes($_POST['login_enabled']);
		$payouts_enabled=stripslashes($_POST['payouts_enabled']);
		$info=stripslashes($_POST['info']);
		$global_message=stripslashes($_POST['global_message']);
		$message=admin_change_settings($login_enabled,$payouts_enabled,$info,$global_message);
	}
	if(isset($message) && $message!='') setcookie("message",$message);
	header("Location: ./");
	die();
}

if(isset($_GET['ajax']) && isset($_GET['block'])) {
	if($user_uid) {
		switch($_GET['block']) {
			case 'control':
				if(is_admin($user_uid)) {
					echo html_admin_settings($user_uid,$token);
				}
				break;
			default:
			case 'calc':
				echo html_compute_block($user_uid,$token);
				break;
			case 'rating':
				echo html_rating_block($user_uid,$token);
				break;
			case 'payouts':
				echo html_payouts($user_uid,$token);
				break;
			case 'info':
				echo html_info();
				break;
			case 'stats':
				echo html_user_stats($user_uid,$token);
				break;
			case 'log':
				if(is_admin($user_uid)) {
					echo html_log_section_admin();
				}
				break;
			case 'settings':
				echo html_user_settings($user_uid,$token);
				break;
		}
	} else {
		switch($_GET['block']) {
			default:
			case 'info':
				echo html_info();
				break;
			case 'login':
				echo html_login_form($token);
				break;
			case 'register':
				echo html_register_form($token);
				break;
			case 'rating':
				echo html_rating_block($user_uid,$token);
				break;
		}
	}
	die();
}

if(isset($_COOKIE['message'])) {
	$message=$_COOKIE['message'];
	setcookie("message","");
} else {
	$message="";
}
echo html_page_begin($project_name,$token);
echo html_message_global();

if($user_uid) {
	echo html_logout_form($user_uid,$token);
}

if($message) {
	$lang_message=lang_message($message);
	if($lang_message!='') {
		echo "<p class='message'>$lang_message</p>";
	}
}
echo html_tabs($user_uid);
echo html_loadable_block();

echo html_page_end();

?>
