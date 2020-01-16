<?php

// Standard page begin
function html_page_begin($title,$token) {
	global $project_name;
//	$lang_select_form=lang_select_form($token);

	return <<<_END
<!DOCTYPE html>
<html>
<head>
<title>$title</title>
<meta charset="utf-8" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link rel="icon" href="favicon.png" type="image/png">
<script src='jquery-3.3.1.min.js'></script>
<link rel="stylesheet" type="text/css" href="style.css">
<script src='script.js'></script>
</head>
<body>
<center>
$lang_select_form
<h1>$project_name</h1>

_END;
}

// Page end, scripts and footer
function html_page_end() {
	global $project_counter_name;

	$result=<<<_END
<hr width=10%>
<p>Opensource Gridcoin distribute computing project (<a href='https://github.com/sau412/microgrid'>github</a>) by <a href='https://arikado.xyz/'>sau412</a>.</p>
<p><img src='https://arikado.xyz/counter/?site=$project_counter_name'></p>
</center>
<script>

var hash = window.location.hash.substr(1);

if(hash != null && hash != '') {
        show_block(hash);
} else {
        show_block('dashboard');
}
</script>
</body>
</html>

_END;
	return lang_parser($result);
}

function html_login_form($token) {
	global $recaptcha_public_key;
	$login_submit=lang_message("login_submit");
	$captcha=html_captcha();
	$result=<<<_END
<h2>Login</h2>
<form name=login method=post>
<input type=hidden name=action value='login'>
<input type=hidden name=token value='$token'>
<p>Login: <input type=text name=login></p>
<p>Password: <input type=password name=password></p>
$captcha
<p><input type=submit value='Submit'></p>
</form>

_END;
	return lang_parser($result);
}

function html_logout_form($user_uid,$token) {
	global $currency_short;
	$username=get_username_by_uid($user_uid);
	$balance=get_user_balance($user_uid);
	$result=<<<_END
<p>Welcome, $username (<a href='?action=logout&token=$token'>logout</a>), your balance: <span id='balance'>$balance</span> $currency_short</p>

_END;
	return lang_parser($result);
}

function html_register_form($token) {
	global $recaptcha_public_key;
	$captcha=html_captcha();
	$result=<<<_END
<h2>Register</h2>
<form name=register method=post>
<input type=hidden name=action value='register'>
<input type=hidden name=token value='$token'>
<p>Login: <input type=text name=login></p>
<p>E-mail: <input type=text name=mail></p>
<p>Password 1: <input type=password name=password1></p>
<p>Password 2: <input type=password name=password2></p>
<p>Payout address: <input type=text name=withdraw_address></p>
$captcha
<p><input type=submit value='Submit'></p>
</form>

_END;
	return lang_parser($result);
}

function html_tabs($user_uid) {
	global $currency_short;
	$result="";
	$result.="<div style='display: inline-block;'>\n";
	$result.="<ul class=horizontal_menu>\n";
	if($user_uid) {
		$result.=html_menu_element("info","Info");
		$result.=html_menu_element("comp","Compute for $currency_short");
		$result.=html_menu_element("stats","User stats");
		$result.=html_menu_element("rating","Rating");
		$result.=html_menu_element("payouts","Payouts");
		$result.=html_menu_element("settings","Settings");
		if(is_admin($user_uid)) {
			$result.=html_menu_element("control","Control");
			$result.=html_menu_element("log","Log");
		}
	} else {
		$result.=html_menu_element("info","Info");
		$result.=html_menu_element("login","Login");
		$result.=html_menu_element("register","Register");
		$result.=html_menu_element("rating","Rating");
	}
	$result.="</ul>\n";
	$result.="</div>\n";

	return lang_parser($result);
}

function html_menu_element($block,$text) {
	return "<li><a href='#$block' onClick=\"show_block('$block')\">$text</a>\n";
}

// User settings
function html_user_settings($user_uid,$token) {
	$result="";

	$user_uid_escaped=db_escape($user_uid);
	$user_settings_data=db_query_to_array("SELECT `mail`,`withdraw_address` FROM `users` WHERE `uid`='$user_uid_escaped'");
	$user_settings=array_pop($user_settings_data);
	$mail=$user_settings['mail'];
	$withdraw_address=$user_settings['withdraw_address'];

	$result.=lang_parser("<h2>Settings</h2>\n");
	$result.="<form name=user_settings method=post>\n";
	$result.="<input type=hidden name=action value='user_change_settings'>\n";
	$result.="<input type=hidden name=token value='$token'>\n";

	// Notifications
	$mail_html=html_escape($mail);
	$result.=lang_parser("<p>E-mail:")." <input type=text size=40 name=mail value='$mail_html'>";
	$result.="</p>";

	// Withdraw addresss
	//$result.="<h3>Withdraw address</h3>\n";
	$withdraw_address_html=html_escape($withdraw_address);
	$result.=lang_parser("<p>Payout address:")." <input type=text size=40 name=withdraw_address value='$withdraw_address_html'></p>";

	// Password options
	//$result.="<h3>Password</h3>\n";
	$result.=lang_parser("<p>Actual password: <input type=password name=password></p>");
	$result.=lang_parser("<p>New password 1: <input type=password name=new_password1></p>");
	$result.=lang_parser("<p>New password 2: <input type=password name=new_password2></p>");

	// Submit button
	$result.=lang_parser("<p><input type=submit value='Submit'></p>\n");
	$result.="</form>\n";

	return $result;
}

// Admin settings
function html_admin_settings($user_uid,$token) {
	$result="";

	$result.=lang_parser("<h2>Project settings</h2>\n");
	$result.="<form name=admin_settings method=post>\n";
	$result.="<input type=hidden name=action value='admin_change_settings'>\n";
	$result.="<input type=hidden name=token value='$token'>\n";

	$login_enabled=get_variable("login_enabled");
	$login_enabled_selected=$login_enabled?"selected":"";

	$payouts_enabled=get_variable("payouts_enabled");
	$payouts_enabled_selected=$payouts_enabled?"selected":"";

	$result.=lang_parser("<p>Login/register state: <select name=login_enabled><option value='disabled'>disabled</option><option value='enabled' $login_enabled_selected>enabled</option></select>\n");
	$result.=lang_parser(", payouts state: <select name=payouts_enabled><option value='disabled'>disabled</option><option value='enabled' $payouts_enabled_selected>enabled</option></select>\n");

	$info=get_variable("info");
	$info_html=html_escape($info);
	$result.=lang_parser("<p>Info block</p>")."<p><textarea name=info rows=10 cols=50>$info_html</textarea></p>";

	$global_message=get_variable("global_message");
	$global_message_html=html_escape($global_message);
	$result.=lang_parser("<p>Global message: ")."<input type=text size=60 name=global_message value='$global_message_html'></p>\n";

	// Submit button
	$result.=lang_parser("<p><input type=submit value='Submit'></p>\n");
	$result.="</form>\n";

	return $result;
}

// Global message
function html_message_global() {
	$result="";

	$global_message=get_variable("global_message");
	if($global_message!='') {
		$result.="<div class='message_global'>$global_message</div>";
	}

	return $result;
}

// Log
function html_log_section_admin() {
	$result="";
	$result.=lang_parser("<h2>Log</h2>\n");
	$data_array=db_query_to_array("SELECT u.`login`,l.`message`,l.`timestamp` FROM `log` AS l
LEFT JOIN `users` u ON u.`uid`=l.`user_uid`
ORDER BY `timestamp` DESC LIMIT 100");

	$result.="<table class='table_horizontal'>\n";
	$result.=lang_parser("<tr><th>Timestamp</th><th>Login</th><th>Message</th></tr>\n");
	foreach($data_array as $row) {
		$login=$row['login'];
		$timestamp=$row['timestamp'];
		$message=$row['message'];
		$login_html=html_escape($login);
		$message_html=html_escape($message);
		$result.="<tr><td>$timestamp</td><td>$login_html</td><td>$message_html</td></tr>\n";
	}
	$result.="</table>\n";
	return $result;
}

function html_message($message) {
	return "<div style='background:yellow;'>".html_escape($message)."</div>";
}

function html_address_url($address) {
	global $address_url;
	$address_begin=substr($address,0,10);
	$address_end=substr($address,-10,10);
	$send_to_link=lang_parser(html_send_to_link($address,"send to"));
	//$result="<div class='url_with_qr_container'>$address_begin......$address_end<div class='qr'>$address<br><a href='$address_url$address'>explorer</a>, <a href='#'>copy</a>, $send_to_link, $address_book_link<br><img src='qr.php?str=$address'></div></div>";
	$result=lang_parser("<div class='url_with_qr_container'>$address<div class='qr'>$address<br><a href='$address_url$address'>Block explorer</a>, $send_to_link<br><img src='qr.php?str=$address'></div></div>");
	return $result;
}

function html_tx_url($tx) {
	global $tx_url;
	if($tx=='') return '';
	$tx_begin=substr($tx,0,10);
	$tx_end=substr($tx,-10,10);
	$result=lang_parser("<div class='url_with_qr_container'>$tx_begin......$tx_end<div class='qr'>$tx<br><a href='$tx_url$tx'>Block explorer</a><br><img src='qr.php?str=$tx'></div></div>");
	return $result;
}

function html_block_hash($hash) {
	global $block_url;
	if($hash=='') return '';
	$hash_begin=substr($hash,0,10);
	$hash_end=substr($hash,-10,10);
	$result=lang_parser("<span class='url_with_qr_container'>$hash_begin......$hash_end<span class='qr'>$hash<br><a href='$block_url$hash'>Block explorer</a><br><img src='qr.php?str=$hash'></span></span>");
	return $result;
}

function html_send_to_link($address,$text) {
	return "<a href='#' onClick=\"document.getElementById('send_address').value='$address'; return false;\">$text</a>";
}

function html_address_book_link($address,$text) {
	return "<a href='#' onClick=\"document.getElementById('alias_address').value='$address'; return false;\">$text</a>";
}

// Loadable block for ajax
function html_loadable_block() {
        return lang_parser("<div id='main_block'>Loading block...</div>\n");
}

// Show info
function html_info() {
	$result='';
	$result.=lang_parser("<h2>Info</h2>\n");
	$result.=get_variable("info");
	return $result;
}

// Show captcha
function html_captcha() {
	$result=<<<_END
<p><img src='?captcha'><br>Code from image above: <input type=text name=captcha_code></p>
_END;
	return $result;
}

// Send and receive
function html_send_receive($user_uid,$token) {
	global $currency_short;
	$result="";

	$deposit_address=get_user_deposit_address($user_uid);
	$withdraw_address=get_user_withdraw_address($user_uid);
	$balance=get_user_balance($user_uid);
	$withdraw_min=get_variable("withdraw_min");

	$result.=<<<_END
<h2>Deposit</h2>
<p>Your deposit address is <strong>$deposit_address</strong></p>
<p><img src='qr.php?str=$deposit_address'</p>
<p>
<form name=withdraw method=post>
<h2>Withdraw</h2>
<input type=hidden name=action value='withdraw'>
<input type=hidden name=token value='$token'>
<p>Your balance: $balance $currency_short</p>
<p>Your withdraw address: <strong>$withdraw_address</strong></p>
<p>Min withdraw amount: $withdraw_min $currency_short</p>
<p><input type=text name=amount value='0'> $currency_short <input type=submit value='withdraw'>
</form>
</p>

_END;

	$result.=html_transactions($user_uid,$token);

	return $result;
}

// Transactions
function html_transactions($user_uid,$token) {
	global $currency_short;
	global $wallet_receive_confirmations;
	$result="";

	$user_uid_escaped=db_escape($user_uid);

	$tx_data_array=db_query_to_array("SELECT `amount`,`address`,`status`,`tx_id`,`confirmations`,`timestamp` FROM `transactions` WHERE `user_uid`='$user_uid_escaped' ORDER BY `timestamp` DESC LIMIT 20");

	$result.="<h2>Transactions</h2>\n";
	$result.="<p>\n";
	$result.="<table class='table_horizontal'>\n";
	$result.="<tr><th>Address</th><th>Amount, $currency_short</th><th>Status</th><th>TX ID</th><th>Timestamp</th></tr>";
	foreach($tx_data_array as $tx_data) {
		$address=$tx_data['address'];
		$amount=$tx_data['amount'];
		$status=$tx_data['status'];
		$tx_id=$tx_data['tx_id'];
		$confirmations=$tx_data['confirmations'];
		$timestamp=$tx_data['timestamp'];

		$address_html=html_escape($address);

		if($status=='pending') {
			$status_text="$status ($confirmations/$wallet_receive_confirmations)";
		} else {
			$status_text=$status;
		}

		$result.="<tr><td>$address_html</td><td>$amount</td><td>$status_text</td><td>$tx_id</td><td>$timestamp</td></tr>\n";
	}
	$result.="</table>\n";
	$result.="</p>\n";

	return $result;
}

// Start computations block
function html_compute_block($user_uid,$token) {
	global $currency_short;

	$result="";

	$projects_array=db_query_to_array("SELECT `uid`,`name`,`version` FROM `projects` WHERE `is_enabled`=1");
	$project_selector="";
	foreach($projects_array as $project_data) {
		$project_uid=$project_data['uid'];
		$project_name=$project_data['name'];
		$project_version=$project_data['version'];
		$project_selector.="<option value='$project_uid'>$project_name ver $project_version</option>\n";
	}

	$result.=<<<_END
<h2>Compute for $currency_short</h2>
<form name=load_comp_block>
<p>Select project: <select id=project name=project>$project_selector</select></p>
<p>Threads: <input type=number id=threads name=threads value='1'></p>
<p><input type=button value='Start' id=start_button onClick='start_calculations()'></p>
</form>

<div class=comp_progress_block id=comp_progress_block>
</div>
<script>
var progress=[];
var status=[];

if(typeof(navigator.hardwareConcurrency) !== 'undefined') {
	document.getElementById('threads').value=navigator.hardwareConcurrency;
}

function start_calculations() {
	// Disable buttons
	document.getElementById('threads').disabled=true;
	document.getElementById('start_button').disabled=true;

	var threads = document.getElementById('threads').value;
	var project_id = document.getElementById('project').value;

	if(threads <= 0) {
		alert("Threads number should be positive");
		return false;
	}

	// Generate table
	var calc_progress_block = document.getElementById('comp_progress_block');
	calc_progress_block.innerHTML='';

	var progress_table = document.createElement("table");
	progress_table.setAttribute('class','comp_progress_block_table');
	var i;
	for(i=0;i<threads;i++) {
		var row = document.createElement("tr");

		var cell = document.createElement("td");
		cell.setAttribute('class','comp_progress_block_td');
		var cell_text = document.createTextNode("Thread " + i);
		cell.appendChild(cell_text);
		row.appendChild(cell);

		var cell = document.createElement("td");
		cell.setAttribute('class','comp_progress_block_td');
		cell.setAttribute('id','progress_' + i);
		var cell_text = document.createTextNode("0 %");
		cell.appendChild(cell_text);
		row.appendChild(cell);

		var cell = document.createElement("td");
		cell.setAttribute('class','comp_progress_block_td');
		cell.setAttribute('id','status_' + i);
		var cell_text = document.createTextNode("init");
		cell.appendChild(cell_text);
		row.appendChild(cell);

		calc_progress_block.appendChild(row);
	}

	// Start calculations
	for (var worker_id = 0; worker_id < threads; worker_id++) {
		repeatable_worker(project_id, worker_id);
	}
}

// Set status and progress for worker
function worker_set_status_and_progress(worker_id, status, progress) {
	document.getElementById("status_" + worker_id).innerHTML = status;
	document.getElementById("progress_" + worker_id).innerHTML = progress + '&thinsp;%';
}

// Run worker
function run_next_worker(worker_id, project_id) {
	// Run worker
	var data = {
		action: "get_new_task",
		project: project_id,
		token: "$token"
	};
	$.post("./",data,function (result) {
		status = 'working';
		progress = '0';
		worker_set_status_and_progress(worker_id, status, progress);
		task_data = JSON.parse(result);
		var start_number = task_data.start_number;
		var stop_number = task_data.stop_number;
		var workunit_result_uid = task_data.workunit_result_uid;
		myWorker.postMessage([worker_id, workunit_result_uid, start_number, stop_number]);
	})
	.fail(function() {
		status = "downloading repeat";
		progress = "0";
		worker_set_status_and_progress(worker_id, status, progress);
		// Repeat after minute
		setTimeout(function() {
			run_next_worker(worker_id, project_id);
		},60000);
	});
}

function repeatable_worker(project_id, worker_id) {
//	$.post("./",[action:"get_new_task",token:"$token"],function (result) {

	var myWorker = new Worker('?project_script=' + project_id);
	myWorker.addEventListener('message', function(e) {
		// e.data[0] is worker id
		// e.data[1] is message type: 0 progress, 1 result
		// e.data[2] is data (progress or result itself)
		// Progress data
		worker_id = e.data[0];
		if(e.data[1] == 0) {
			status = 'working';
			progress = Math.floor(e.data[2]*100);
			worker_set_status_and_progress(worker_id, status, progress);
			//document.getElementById("status_"+e.data[0]).innerHTML='working';
			//document.getElementById("progress_"+e.data[0]).innerHTML=Math.floor(e.data[2]*100) + '&thinsp;%';
		}
		// Result data
		else if(e.data[1] == 1) {
			console.log('result: ', e.data);
			status = 'uploading';
			progress = '100';
			worker_set_status_and_progress(worker_id, status, progress);
			//document.getElementById("status_"+e.data[0]).innerHTML='uploading';
			//document.getElementById("progress_"+e.data[0]).innerHTML='100 %';
			var workunit_version = e.data[2];
			var workunit_result_uid = e.data[3];
			var workunit_result = JSON.stringify(e.data[4]);
			data = {
				action:'task_store_result',
				token:'$token',
				version:workunit_version,
				workunit_result_uid:workunit_result_uid,
				result:workunit_result
			};
			$.post("./",data,function (reply) {
				reply_json=JSON.parse(reply);
				if(reply_json.result=="ok") {
					// Run next worker
					document.getElementById("status_"+e.data[0]).innerHTML='downloading';
					var data = {
						action:"get_new_task",
						project:project_id,
						token:"$token"
					};
					$.post("./",data,function (result) {
						task_data=JSON.parse(result);
						var start_number=task_data.start_number;
						var stop_number=task_data.stop_number;
						var workunit_result_uid=task_data.workunit_result_uid;
						document.getElementById("status_"+e.data[0]).innerHTML='working';
						myWorker.postMessage([worker_id,workunit_result_uid,start_number,stop_number]);
					});
				} else {
					document.getElementById("status_"+e.data[0]).innerHTML=reply_json.message;
				}
			});
		}
	}, false);

	// Run worker
	run_next_worker(worker_id, project_id);
}

</script>

_END;
	return $result;
}

function html_payouts($user_uid,$token) {
	global $currency_short;

	$result='';

	$user_uid_escaped=db_escape($user_uid);
	$payouts_data=db_query_to_array("SELECT `address`,`amount`,`tx_id`,`timestamp` FROM `payouts` WHERE `user_uid`='$user_uid_escaped' ORDER BY `timestamp` DESC LIMIT 100");

	$result.=<<<_END
<h2>Payouts</h2>
<p>
<table class='table_horizontal'>
<tr><th>Address</th><th>Amount, $currency_short</th><th>TX ID</th><th>Timestamp</th></tr>

_END;

	foreach($payouts_data as $payout_info) {
		$address=$payout_info['address'];
		$amount=$payout_info['amount'];
		$tx_id=$payout_info['tx_id'];
		$timestamp=$payout_info['timestamp'];

		$address_html=html_address_url($address);
		$amount_html=html_escape($amount);
		$tx_id_html=html_tx_url($tx_id);
		$timestamp_html=html_escape($timestamp);

		$result.="<tr><td>$address_html</td><td>$amount_html</td><td>$tx_id_html</td><td>$timestamp_html</td></tr>\n";
	}

	$result.=<<<_END
</table>
</p>

_END;
	return $result;
}

function html_rating_block($user_uid,$token) {
	global $currency_short;

	$result="";

	$user_uid_escaped=db_escape($user_uid);
	$rating_data=json_decode(get_variable("rating_cache"),TRUE);
	/*$rating_data=db_query_to_array("SELECT `users`.`login`,count(*) AS 'count',SUM(`reward`) AS 'reward'
		FROM `workunit_results`
		JOIN `users` ON `users`.`uid`=`workunit_results`.`user_uid`
		WHERE `workunit_results`.`is_valid`=1
		GROUP BY `users`.`login` ORDER BY count(*) DESC LIMIT 100");
*/
	$result.=<<<_END
<h2>Rating</h2>
<p>
<table class='table_horizontal'>
<tr><th>User</th><th>Workunits</th><th>$currency_short earned</th></tr>

_END;

	foreach($rating_data as $rating_row) {
		$login=$rating_row['login'];
		$count=$rating_row['count'];
		$reward=$rating_row['reward'];

		$login_html=html_escape($login);
		$reward=floor($reward);
		if($reward<1) $reward="<1";

		$result.="<tr><td>$login_html</td><td>$count</td><td>$reward</td></tr>\n";
	}

	$result.=<<<_END
</table>
</p>

_END;

	return $result;
}

// User results
function html_user_stats($user_uid, $token) {
	global $currency_short;

	$result = "";

	$user_uid_escaped = db_escape($user_uid);
	$results_data = db_query_to_array("SELECT count(*) AS total,
						SUM(IF(`is_valid`=1,1,0)) AS valid_count,
						SUM(IF(`result_hash` IS NULL,1,0)) AS in_process,
						SUM(IF(`reward` IS NOT NULL AND `reward`>0,1,0)) AS paid,
						SUM(IF(`reward` IS NULL OR `reward`=0,1,0)) AS not_paid,
						SUM(`reward`) AS total_reward
						FROM `workunit_results` WHERE `user_uid` = '$user_uid_escaped'");

	$results_row = array_pop($results_data);
	$total = $results_row['total'];
	$valid_count = $results_row['valid_count'];
	$in_process = $results_row['in_process'];
	$paid = $results_row['paid'];
	$not_paid = $results_row['not_paid'];
	$total_reward = sprintf("%0.8F",$results_row['total_reward']);

	$result.=<<<_END
<h2>User stats</h2>
<p>
<table class='table_horizontal'>
<tr><th>Counter</th><th>Value</th></tr>
<tr><td>Total results</td><td>$total</td></tr>
<tr><td>Valid results</td><td>$valid_count</td></tr>
<tr><td>In process</td><td>$in_process</td></tr>
<tr><td>Paid results count</td><td>$paid</td></tr>
<tr><td>Not paid results count</td><td>$not_paid</td></tr>
<tr><td>Total earned</td><td>$total_reward $currency_short</td></tr>
</table>
</p>

_END;

	return $result;
}

?>
