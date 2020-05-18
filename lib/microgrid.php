<?php
// Microgrid functions

function microgrid_generate_workunit_task($project_uid,$user_uid) {
	$project_uid_escaped=db_escape($project_uid);
	$user_uid_escaped=db_escape($user_uid);

	$project_retries=db_query_to_variable("SELECT `retries` FROM `projects` WHERE `uid`='$project_uid_escaped'");

	db_query("LOCK TABLES `workunits` WRITE,`workunit_results` WRITE,`projects` READ");

	// Workunits, that neither completed, nor calculated by that user before
	$exists_uid=db_query_to_variable("SELECT `workunits`.`uid` FROM `workunits`
LEFT OUTER JOIN `workunit_results` wr ON wr.`workunit_uid` = `workunits`.`uid` AND `user_uid`='$user_uid_escaped'
WHERE `project_uid`='$project_uid_escaped' AND `is_completed`=0 AND wr.`uid` IS NULL AND `in_progress`=0
LIMIT 1");

	if($exists_uid) {
		$workunit_uid=$exists_uid;
	} else {
		$workunit_uid=microgrid_generate_workunit($project_uid);
	}

	$workunit_uid_escaped=db_escape($workunit_uid);

	db_query("UPDATE `workunits` SET `in_progress`=`in_progress`+1 WHERE `uid`='$workunit_uid_escaped'");

	db_query("INSERT INTO `workunit_results` (`workunit_uid`,`user_uid`) VALUES ('$workunit_uid_escaped','$user_uid_escaped')");
	$workunit_result_uid=mysql_insert_id();

	db_query("UNLOCK TABLES");
	return $workunit_result_uid;
}

function microgrid_generate_workunit($project_uid) {
	$project_uid_escaped=db_escape($project_uid);
	$workunit_step=db_query_to_variable("SELECT `step` FROM `projects` WHERE `uid`='$project_uid_escaped'");
	$workunit_max_stop=db_query_to_variable("SELECT MAX(`stop_number`) FROM `workunits` WHERE `project_uid`='$project_uid_escaped'");
	if($workunit_max_stop === NULL) {
		$workunit_max_stop=db_query_to_variable("SELECT `start_number` FROM `projects` WHERE `uid`='$project_uid_escaped'");
	}
	$start_number=$workunit_max_stop+1;
	$stop_number=$workunit_max_stop+1+$workunit_step;
	db_query("INSERT INTO `workunits` (`project_uid`,`start_number`,`stop_number`) VALUES ('$project_uid_escaped','$start_number','$stop_number')");
	return mysql_insert_id();
}

function microgrid_get_workunit_data_by_workunit_result_uid($workunit_result_uid) {
	$workunit_result_uid_escaped=db_escape($workunit_result_uid);
	$workunit_data=db_query_to_array("SELECT wr.`uid` AS workunit_result_uid,w.uid,w.project_uid,w.start_number,w.stop_number FROM `workunits` AS w
JOIN `workunit_results` AS wr ON wr.`workunit_uid`=w.`uid`
WHERE wr.`uid`='$workunit_result_uid_escaped'");
	$result=array_pop($workunit_data);
	return $result;
}

function microgrid_save_workunit_results($user_uid,$workunit_results_uid,$version,$result) {
	$user_uid_escaped=db_escape($user_uid);
	$workunit_result_uid_escaped=db_escape($workunit_results_uid);
	$result_hash=hash("sha256",$result);
	$result_escaped=db_escape($result);
	$result_hash_escaped=db_escape($result_hash);

	$workunit_uid=db_query_to_variable("SELECT `workunit_uid` FROM `workunit_results` WHERE `uid`='$workunit_result_uid_escaped' AND `user_uid`='$user_uid_escaped'");
	$workunit_uid_escaped=db_escape($workunit_uid);
	$project_uid=db_query_to_variable("SELECT `project_uid` FROM `workunits` WHERE `uid`='$workunit_uid_escaped' AND `is_completed`=0");
	$project_uid_escaped=db_escape($project_uid);
	$actual_version=microgrid_get_actual_version($project_uid);
	if($version<$actual_version) {
		return array("result"=>"fail","message"=>"Incorrect module version, refresh page and start again");
	}

	db_query("LOCK TABLES `workunits` WRITE,`workunit_results` WRITE,`users` WRITE,`projects` READ");

	// Update workunit_result for specific user
	db_query("UPDATE `workunit_results` SET `result_hash`='$result_hash_escaped',`completed`=NOW() WHERE `uid`='$workunit_result_uid_escaped' AND `user_uid`='$user_uid_escaped'");

	// Check is results matches, mark workunits as completed
	if($workunit_uid!==NULL) {
		// Decrease units in work
		db_query("UPDATE `workunits` SET `in_progress`=`in_progress`-1 WHERE `uid`='$workunit_uid_escaped'");

		// Get project uid
		if($project_uid!==NULL) {
			// Check similar results
			$count=db_query_to_variable("SELECT count(*) FROM `workunit_results` WHERE `workunit_uid`='$workunit_uid_escaped' AND `result_hash`='$result_hash'");

			// If max amount reached, mark workunit as completed
			$required_amount=db_query_to_variable("SELECT `retries` FROM `projects` WHERE `uid`='$project_uid_escaped'");

			if($count>=$required_amount) {
				$reward_amount=db_query_to_variable("SELECT `workunit_price` FROM `projects` WHERE `uid`='$project_uid_escaped'");
				if($reward_amount==0) $reward_amount=0;
				$reward_amount_escaped=db_escape($reward_amount);
				db_query("UPDATE `workunits` SET `in_progress`=0,`is_completed`=1,`result`='$result_escaped' WHERE `uid`='$workunit_uid_escaped'");
				db_query("UPDATE `users` SET `balance`=`balance`+'$reward_amount_escaped' WHERE `uid` IN (SELECT `user_uid` FROM `workunit_results` WHERE `workunit_uid`='$workunit_uid_escaped' AND `result_hash`='$result_hash')");
				db_query("UPDATE `workunit_results` SET `is_valid`=1,`reward`='$reward_amount_escaped' WHERE `workunit_uid`='$workunit_uid_escaped' AND `result_hash`='$result_hash'");
				db_query("UPDATE `workunit_results` SET `is_valid`=0,`reward`=0 WHERE `workunit_uid`='$workunit_uid_escaped' AND `result_hash`<>'$result_hash'");
			}
		}
	}

	db_query("UNLOCK TABLES");
	return array("result"=>"ok");
}

function microgrid_check_timeouts() {
	// Mark timed out workunits as invalid
}

function microgrid_get_actual_version($project_uid) {
	$project_uid_escaped=db_escape($project_uid);

	return db_query_to_variable("SELECT `version` FROM `projects` WHERE `uid`='$project_uid_escaped'");
}

function microgrid_get_function($project_uid) {
	$project_uid_escaped=db_escape($project_uid);

	return db_query_to_variable("SELECT `function` FROM `projects` WHERE `uid`='$project_uid_escaped'");
}

?>
