<?php
// Core functions

// Escape text to show in html page as text
function html_escape($data) {
	$data=htmlspecialchars($data);
	$data=str_replace("'","&apos;",$data);
	return $data;
}

// Checks is string contains only ASCII symbols
function validate_ascii($string) {
	if(strlen($string)>100) return FALSE;
	if(is_string($string)==FALSE) return FALSE;
	for($i=0;$i!=strlen($string);$i++) {
		if(ord($string[$i])<32 || ord($string[$i])>127) return FALSE;
	}
	return TRUE;
}

// Checks is string contains number
function validate_number($string) {
	if(strlen($string)>20) return FALSE;
	if(is_string($string)==FALSE) return FALSE;
	return is_numeric($string);
}

// Get variable
function get_variable($name) {
	$name_escaped=db_escape($name);
	return db_query_to_variable("SELECT `value` FROM `variables` WHERE `name`='$name_escaped'");
}

// Set variable
function set_variable($name,$value) {
	$name_escaped=db_escape($name);
	$value_escaped=db_escape($value);
	db_query("INSERT INTO `variables` (`name`,`value`) VALUES ('$name_escaped','$value_escaped') ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
}

// Increase variable counter
function inc_variable($name) {
	$name_escaped = db_escape($name);
	db_query("UPDATE `variables` SET `value` = `value` + 1 WHERE `name` = '$name_escaped'");
}

// Create or get session
function get_session() {
	if(isset($_COOKIE['session_id']) && validate_ascii($_COOKIE['session_id'])) {
		$session=$_COOKIE['session_id'];
		$session_escaped=db_escape($session);
		$session_exists=db_query_to_variable("SELECT 1 FROM `sessions` WHERE `session`='$session_escaped'");
		if(!$session_exists) {
			unset($session);
		}
	}

	if(!isset($session)) {
		$session=bin2hex(random_bytes(32));
		$token=bin2hex(random_bytes(32));
		setcookie('session_id',$session,time()+86400*30);
		$session_escaped=db_escape($session);
		$token_escaped=db_escape($token);
		db_query("INSERT INTO `sessions` (`session`,`token`) VALUES ('$session_escaped','$token_escaped')");
	}
	return $session;
}

// Get user uid
function get_user_uid_by_session($session) {
	$session_escaped=db_escape($session);
	$user_uid=db_query_to_variable("SELECT `user_uid` FROM `sessions` WHERE `session`='$session_escaped'");
	return $user_uid;
}

// Get user token
function get_user_token_by_session($session) {
	$session_escaped=db_escape($session);
	$token=db_query_to_variable("SELECT `token` FROM `sessions` WHERE `session`='$session_escaped'");
	return $token;
}

// Create new user
function user_register($session,$mail,$login,$password1,$password2,$withdraw_address) {
	global $global_salt;

	if(get_variable("login_enabled")==0) return "register_failed_disabled";

	if($password1!=$password2) return "register_failed_password_mismatch";

	$session_escaped=db_escape($session);
	$salt=bin2hex(random_bytes(16));
	$salt_escaped=db_escape($salt);

	$password_hash=hash("sha256",$password1.strtolower($login).$salt.$global_salt);

	$message="";

	if(validate_ascii($login)) {
		$login_escaped=db_escape($login);
		$mail_escaped=db_escape($mail);
		$withdraw_address_escaped=db_escape($withdraw_address);
		$exists_hash=db_query_to_variable("SELECT `password_hash` FROM `users` WHERE `login`='$login_escaped'");
		if($exists_hash=="") {
			log_write("New user '$login' mail '$mail'", 6);
			db_query("INSERT INTO `users` (`mail`,`login`,`password_hash`,`salt`,`register_time`,`login_time`,`withdraw_address`)
VALUES ('$mail_escaped','$login_escaped','$password_hash','$salt_escaped',NOW(),NOW(),'$withdraw_address_escaped')");
			$user_uid=db_query_to_variable("SELECT `uid` FROM `users` WHERE `login`='$login_escaped'");
			$user_uid_escaped=db_escape($user_uid);
			db_query("UPDATE `sessions` SET `user_uid`='$user_uid_escaped' WHERE `session`='$session_escaped'");
			return "register_successful";
			return TRUE;
		} else if($password_hash==$exists_hash) {
			log_write("Logged in '$login'", 6);
			$user_uid=db_query_to_variable("SELECT `uid` FROM `users` WHERE `login`='$login_escaped'");
			$user_uid_escaped=db_escape($user_uid);
			db_query("UPDATE `sessions` SET `user_uid`='$user_uid_escaped' WHERE `session`='$session_escaped'");
			return "login_successful";
		} else {
			log_write("Invalid password for '$login'", 5);
			return "register_failed_invalid_password";
		}
	} else {
		log_write("Invalid login for '$login'", 5);
		return "register_failed_invalid_login";
	}
}

// Check user login and password
function user_login($session,$login,$password) {
	global $global_salt;

	$session_escaped=db_escape($session);

	$message="";

	if(validate_ascii($login)) {
		$login_escaped=db_escape($login);
		$exists_hash=db_query_to_variable("SELECT `password_hash` FROM `users` WHERE `login`='$login_escaped'");
		$salt=db_query_to_variable("SELECT `salt` FROM `users` WHERE `login`='$login_escaped'");
		$user_uid=db_query_to_variable("SELECT `uid` FROM `users` WHERE `login`='$login_escaped'");
		$user_uid_escaped=db_escape($user_uid);

		if(get_variable("login_enabled")==0 && !is_admin($user_uid)) return "login_failed_disabled";

		$password_hash=hash("sha256",$password.strtolower($login).$salt.$global_salt);

		if($password_hash==$exists_hash) {
			log_write("Logged in user '$login'", 6);
			//notify_user($user_uid,"Logged in $login","IP: ".$_SERVER['REMOTE_ADDR']);
			db_query("UPDATE `sessions` SET `user_uid`='$user_uid' WHERE `session`='$session_escaped'");
			db_query("UPDATE `users` SET `login_time`=NOW() WHERE `uid`='$user_uid_escaped'");
			return "login_successful";
		} else {
			log_write("Invalid password for '$login'", 5);
			//notify_user($user_uid,"Log in failed","IP: ".$_SERVER['REMOTE_ADDR']);
			return "login_failed_invalid_password";
		}
	} else {
		log_write("Invalid login for '$login'", 5);
		return "login_failed_invalid_login";
	}
}

// Change settings
function user_change_settings($user_uid,$mail,$withdraw_address,$password,$new_password1,$new_password2) {
	global $global_salt;

	if($new_password1!=$new_password2) {
		//notify_user($user_uid,"Change settings fail","Change settings failed, new password mismatch");
		return "user_change_settings_failed_new_password_mismatch";
	}

	$user_uid_escaped=db_escape($user_uid);
	$user_data_array=db_query_to_array("SELECT `mail`,`login`,`salt`,`password_hash`,`withdraw_address` FROM `users` WHERE `uid`='$user_uid_escaped'");
	$user_data=array_pop($user_data_array);
	$login=$user_data['login'];
	$salt=$user_data['salt'];
	$password_hash=$user_data['password_hash'];
	$entered_password_hash=hash("sha256",$password.strtolower($login).$salt.$global_salt);

	if($password_hash==$entered_password_hash) {
		if($mail!=$user_data['mail']) {
			//notify_user($user_uid,"Settings changed","E-mail changed to: $mail");
			$mail_escaped=db_escape($mail);
			db_query("UPDATE `users` SET `mail`='$mail_escaped' WHERE `uid`='$user_uid_escaped'");
			$change_log="New e-mail: $mail\n";
		}

		if($new_password1!='') {
			$new_password_hash=hash("sha256",$new_password1.strtolower($login).$salt.$global_salt);
			$new_password_hash_escaped=db_escape($new_password_hash);
			db_query("UPDATE `users` SET `password_hash`='$new_password_hash_escaped' WHERE `uid`='$user_uid_escaped'");
			$change_log="New password applied\n";
		}

		if($withdraw_address!=$user_data['withdraw_address']) {
			$withdraw_address_escaped=db_escape($withdraw_address);
			db_query("UPDATE `users` SET `withdraw_address`='$withdraw_address_escaped' WHERE `uid`='$user_uid_escaped'");
			$change_log="New withdraw address: $withdraw_address\n";
		}

		//notify_user($user_uid,"Settings changed",$change_log);
		return "user_change_settings_successful";
	} else {
		//notify_user($user_uid,"Change settings fail","Change settings failed, password incorrect");
		return "user_change_settings_failed_password_incorrect";
	}
}

// Admin change settings
function admin_change_settings($login_enabled,$payouts_enabled,$info,$global_message) {
	// Login enabled
	$login_enabled_value=$login_enabled=="enabled"?"1":"0";
	set_variable("login_enabled",$login_enabled_value);

	// Payouts enabled
	$payouts_enabled_value=$payouts_enabled=="enabled"?"1":"0";
	set_variable("payouts_enabled",$payouts_enabled_value);

	// News
	set_variable("info",$info);

	// Global message
	set_variable("global_message",$global_message);

	return "admin_change_settings_successful";
}

// Get username by uid
function get_username_by_uid($user_uid) {
	$user_uid_escaped=db_escape($user_uid);
	$login=db_query_to_variable("SELECT `login` FROM `users` WHERE `uid`='$user_uid_escaped'");
	return $login;
}

// Logout user
function user_logout($session) {
	$user_uid=get_user_uid_by_session($session);
	$username=get_username_by_uid($user_uid);
	log_write("Logged out user '$username'", 6);
	//notify_user($user_uid,"Log out $username","IP: ".$_SERVER['REMOTE_ADDR']);

	$session_escaped=db_escape($session);
	db_query("UPDATE `sessions` SET `user_uid`=NULL WHERE `session`='$session_escaped'");
	return "logout_successful";
}

// Get user balance
function get_user_balance($user_uid) {
	$user_uid_escaped=db_escape($user_uid);
	$balance=db_query_to_variable("SELECT `balance` FROM `users` WHERE `uid`='$user_uid_escaped'");
	$balance=sprintf("%0.8F",$balance);
	return $balance;
}

function recaptcha_check($response) {
	global $recaptcha_private_key;
	$recaptcha_url="https://www.google.com/recaptcha/api/siteverify";
	$query="secret=$recaptcha_private_key&response=$response&remoteip=".$_SERVER['REMOTE_ADDR'];
	$ch=curl_init();
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
	curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);
	curl_setopt($ch,CURLOPT_POST,TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$query);
	curl_setopt($ch,CURLOPT_URL,$recaptcha_url);
	$result = curl_exec ($ch);
	$data = json_decode($result);
	if($data->success) return TRUE;
	else return FALSE;
}

// Checks is user admin
function is_admin($user_uid) {
	$user_uid_escaped=db_escape($user_uid);
	$result=db_query_to_variable("SELECT `is_admin` FROM `users` WHERE `uid`='$user_uid_escaped'");
	if($result==1) return TRUE;
	else return FALSE;
}

// Change user balance
function change_user_balance($user_uid,$balance_delta) {
	$user_uid_escaped=db_escape($user_uid);
	$balance_delta_escaped=db_escape($balance_delta);
	db_query("UPDATE `users`
				SET `balance`=`balance`+'$balance_delta_escaped',
					`total_earned`=`total_earned`+'$balance_delta_escaped'
				WHERE `uid`='$user_uid_escaped'");
}

// Get user deposit address
function get_user_deposit_address($user_uid) {
	$user_uid_escaped=db_escape($user_uid);
	$result=db_query_to_variable("SELECT `deposit_address` FROM `users` WHERE `uid`='$user_uid_escaped'");
	return $result;
}

// Get user withdrawal address
function get_user_withdraw_address($user_uid) {
	$user_uid_escaped=db_escape($user_uid);
	$result=db_query_to_variable("SELECT `withdraw_address` FROM `users` WHERE `uid`='$user_uid_escaped'");
	return $result;
}

// Get global rating
function get_global_rating() {
	$data=db_query_to_array("SELECT `users`.`login`, `total_earned`, `total_results`
                FROM `users`
                ORDER BY `total_results` DESC LIMIT 1000");
	return $data;
}

// Get global results
function get_global_results() {
	//$biggest_wu=db_query_to_variable("SELECT `project_uid`, FROM `");
	return array(
	);
}

// Update last activity
function users_update_active_time($user_uid) {
	$user_uid_escaped = db_escape($user_uid);
	db_query("UPDATE `users` SET `active_time`=NOW() WHERE `uid` = '$user_uid_escaped'");
}

// For php 5 only variant for random_bytes is openssl_random_pseudo_bytes from openssl lib
if(!function_exists("random_bytes")) {
        function random_bytes($n) {
                return openssl_random_pseudo_bytes($n);
        }
}
