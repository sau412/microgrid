<?php
/**
 *  Microgrid core functions
 */ 

/**
 * Generate workunit task for user
 * 
 * @param $project_uid int Project id
 * @param $user_uid int User id
 * 
 * @return int workunit_result_uid 
 */
function microgrid_generate_workunit_task($project_uid, $user_uid) {
	$project_uid_escaped = db_escape($project_uid);
	$user_uid_escaped = db_escape($user_uid);

	$workunit_generated = false;
	ignore_user_abort(true);
	set_time_limit(0);
	ini_set('max_execution_time', 300);
	db_query("LOCK TABLES `workunits` WRITE, `workunit_results` WRITE, `projects` WRITE");
	$project_data_array = db_query_to_array("SELECT * FROM `projects` WHERE `uid`='$project_uid_escaped'");
	$project_data = array_pop($project_data_array);
	$project_retries = $project_data['retries'];
	//db_query_to_variable("SELECT `retries` FROM `projects` WHERE `uid`='$project_uid_escaped'");

	// Check workunits cache
	/*$workunits_cache_json = db_query_to_variable("SELECT `new_tasks_cache` FROM `projects` WHERE `uid` = '$project_uid_escaped'");
	$workunits_cache = json_decode($workunits_cache_json, true);
	if(false && is_array($workunits_cache) && count($workunits_cache) >= 1) {
		$exists_uid = array_shift($workunits_cache);
		$workunits_cache_json = json_encode($workunits_cache);
		$workunits_cache_json_escaped = db_escape($workunits_cache_json);
		db_query("UPDATE `projects` SET `new_tasks_cache` = '$workunits_cache_json_escaped'
					WHERE `uid` = '$project_uid_escaped'");
	}
	else */ //{
		// Workunits, that neither completed, nor calculated by that user before
		$exists_uid = db_query_to_variable("SELECT `workunits`.`uid` FROM `workunits`
			LEFT OUTER JOIN `workunit_results`
				ON `workunit_results`.`workunit_uid` = `workunits`.`uid` AND
					`user_uid` = '$user_uid_escaped'
			WHERE `project_uid` = '$project_uid_escaped' AND
				`is_completed` = 0 AND
				`workunit_results`.`uid` IS NULL AND
				`in_progress` = 0
			LIMIT 1");
	//}

	if($exists_uid) {
		$workunit_uid = $exists_uid;
	} else {
		$workunit_uid = microgrid_generate_workunit($project_data);
		$workunit_generated = true;
	}

	$workunit_uid_escaped = db_escape($workunit_uid);

	db_query("UPDATE `workunits` SET `in_progress` = `in_progress` + 1 WHERE `uid` = '$workunit_uid_escaped'");

	db_query("INSERT INTO `workunit_results` (`workunit_uid`, `user_uid`) VALUES ('$workunit_uid_escaped', '$user_uid_escaped')");
	$workunit_result_uid = mysql_insert_id();

	db_query("UNLOCK TABLES");

	// Inc results counters
	inc_variable("results");
	db_query("UPDATE `users` SET `total_results` = `total_results` + 1 WHERE `uid` = '$user_uid_escaped'");
	db_query("UPDATE `users` SET `in_process` = `in_process` + 1 WHERE `uid` = '$user_uid_escaped'");
	if($workunit_generated) inc_variable("workunits");
	return $workunit_result_uid;
}

/**
 * Generate workunit for project
 * 
 * @param $project_uid int Project id
 * 
 * @return int workunit_uid
 */
function microgrid_generate_workunit($project_data) {
	$project_uid = $project_data['uid'];

	log_write("Generating new workunits for project $project_uid");
	$project_uid_escaped = db_escape($project_uid);

	//db_query("LOCK TABLES `workunits` WRITE, `variables` WRITE");

	$workunit_step = $project_data['step'];
	//db_query_to_variable("SELECT `step` FROM `projects` WHERE `uid` = '$project_uid_escaped'");
	//$workunit_max_stop = db_query_to_variable("SELECT MAX(`stop_number`) FROM `workunits` WHERE `project_uid`='$project_uid_escaped'");
	$workunit_max_stop = $project_data['max_stop_number'];
	//db_query_to_variable("SELECT `max_stop_number` FROM `projects` WHERE `uid` = '$project_uid_escaped'");
	if($workunit_max_stop === NULL) {
		$workunit_max_stop = $project_data['start_number'];
		//db_query_to_variable("SELECT `start_number` FROM `projects` WHERE `uid` = '$project_uid_escaped'");
	}
	$start_number = $workunit_max_stop + 1;
	$stop_number = $workunit_max_stop + 1 + $workunit_step;
	db_query("INSERT INTO `workunits` (`project_uid`, `start_number`, `stop_number`)
				VALUES ('$project_uid_escaped', '$start_number', '$stop_number')");
	$uid = mysql_insert_id();

	// Update max_stop_number
	$stop_number_escaped = db_escape($stop_number);
	db_query("UPDATE `projects` SET `max_stop_number` = '$stop_number_escaped' WHERE `uid` = '$project_uid_escaped'");
/*
	$workunits_cache_json = db_query_to_variable("SELECT `new_tasks_cache` FROM `projects` WHERE `uid` = '$project_uid_escaped'");
	$workunits_cache = json_decode($workunits_cache_json, true);
	if(!$workunits_cache) {
		$workunits_cache = [];
	}
	for($i = 0; $i != 16; $i ++) {
		$start_number = $stop_number + 1;
		$stop_number = $stop_number + 1 + $workunit_step;
		db_query("INSERT INTO `workunits` (`project_uid`, `start_number`, `stop_number`)
			VALUES ('$project_uid_escaped', '$start_number', '$stop_number')");
		$workunits_cache[] = mysql_insert_id();
		inc_variable("workunits");
	}

	$workunits_cache_json = json_encode($workunits_cache);
	$workunits_cache_json_escaped = db_escape($workunits_cache_json);
	log_write("New tasks cache: $workunits_cache_json");
	db_query("UPDATE `projects` SET `new_tasks_cache` = '$workunits_cache_json_escaped' WHERE `uid` = '$project_uid_escaped'");
	db_query("UPDATE `projects` SET `max_stop_number` = '$stop_number' WHERE `uid` = '$project_uid_escaped'");
*/
	//db_query("UNLOCK TABLES");

	return $uid;
}

/**
 * Get workunit data by workunit result id
 * 
 * @param $workunit_result_uid int Workunit result id
 * 
 * @return Array workunit data
 */
function microgrid_get_workunit_data_by_workunit_result_uid($workunit_result_uid) {
	$workunit_result_uid_escaped=db_escape($workunit_result_uid);
	$workunit_data=db_query_to_array("SELECT wr.`uid` AS workunit_result_uid,w.uid,w.project_uid,w.start_number,w.stop_number
FROM `workunits` AS w
JOIN `workunit_results` AS wr ON wr.`workunit_uid`=w.`uid`
WHERE wr.`uid`='$workunit_result_uid_escaped'");
	$result=array_pop($workunit_data);
	return $result;
}

/**
 * Save workunit data
 * 
 * @param $user_uid int User id
 * @param $workunit_results_uid int Workunit results id
 * @param $version int script version
 * @param $result string JSON-encoded result
 * 
 * @return Array result
 */
function microgrid_save_workunit_results($user_uid, $workunit_results_uid, $version, $result) {
	$user_uid_escaped=db_escape($user_uid);
	$workunit_result_uid_escaped=db_escape($workunit_results_uid);
	$result_hash=hash("sha256",$result);
	$result_escaped=db_escape($result);
	$result_hash_escaped=db_escape($result_hash);

	$workunit_uid=db_query_to_variable("SELECT `workunit_uid`
		FROM `workunit_results` WHERE `uid`='$workunit_result_uid_escaped' AND `user_uid`='$user_uid_escaped'");
	$workunit_uid_escaped=db_escape($workunit_uid);
	$project_uid=db_query_to_variable("SELECT `project_uid`
		FROM `workunits` WHERE `uid`='$workunit_uid_escaped' AND `is_completed`=0");
	$project_uid_escaped=db_escape($project_uid);
	$actual_version=microgrid_get_actual_version($project_uid);
	if($version < $actual_version) {
		return array("result"=>"fail", "message"=>"Incorrect module version, refresh page and start again");
	}

	ignore_user_abort(true);
	set_time_limit(0);
	ini_set('max_execution_time', 300);

	db_query("LOCK TABLES `workunits` WRITE, `workunit_results` WRITE, `users` WRITE, `projects` WRITE, `variables` WRITE");

	// Update workunit_result for specific user
	db_query("UPDATE `workunit_results` SET `result_hash`='$result_hash_escaped',`completed`=NOW() WHERE `uid`='$workunit_result_uid_escaped' AND `user_uid`='$user_uid_escaped'");
	inc_variable("results_complete");

	// Check is results matches, mark workunits as completed
	if($workunit_uid!==NULL) {
		// Decrease units in work
		db_query("UPDATE `workunits` SET `in_progress`=GREATEST(`in_progress` - 1, 0) WHERE `uid`='$workunit_uid_escaped'");
		// Dec results counters
		db_query("UPDATE `users` SET `in_process` = GREATEST(`in_process` - 1, 0) WHERE `uid`='$user_uid_escaped'");
		// Get project uid
		if($project_uid!==NULL) {
			// Check similar results
			$count=db_query_to_variable("SELECT count(*) FROM `workunit_results` WHERE `workunit_uid`='$workunit_uid_escaped' AND `result_hash`='$result_hash'");

			// If max amount reached, mark workunit as completed
			$required_amount=db_query_to_variable("SELECT `retries` FROM `projects` WHERE `uid`='$project_uid_escaped'");

			if($count>=$required_amount) {
				$reward_amount=db_query_to_variable("SELECT `workunit_price` FROM `projects` WHERE `uid`='$project_uid_escaped'");
				if($reward_amount == 0) $reward_amount = 0;
				$reward_amount_escaped = db_escape($reward_amount);

				log_write("Workunit $workunit_uid project $project_uid is completed");
				db_query("UPDATE `workunits` SET `in_progress`=0,`is_completed`=1,`result`='$result_escaped' WHERE `uid`='$workunit_uid_escaped'");
				// Get user uids
				$results_array = db_query_to_array("SELECT `uid`, `user_uid`, `result_hash` FROM `workunit_results`
														WHERE `workunit_uid` = '$workunit_uid_escaped'");
				foreach($results_array as $result) {
					$result_uid = $result['uid'];
					$result_user_uid = $result['user_uid'];
					$result_result_hash = $result['result_hash'];

					$result_uid_escaped = db_escape($result_uid);
					$result_user_uid_escaped = db_escape($result_user_uid);

					if($result_result_hash == $result_hash) {
						// Valid results
						db_query("UPDATE `workunit_results` SET `is_valid`=1, `reward`='$reward_amount_escaped'
									WHERE `uid`='$result_uid_escaped'");
						change_user_balance($result_user_uid, $reward_amount);
						db_query("UPDATE `users` SET `valid_results` = `valid_results` + 1 WHERE `uid`='$result_user_uid_escaped'");
						db_query("UPDATE `users` SET `paid_results` = `paid_results` + 1 WHERE `uid`='$result_user_uid_escaped'");
					}
					else {
						// Invalid results
						db_query("UPDATE `workunit_results` SET `is_valid`=0, `reward`='0' WHERE `uid`='$result_uid_escaped'");
						db_query("UPDATE `users` SET `not_paid_results` = `not_paid_results` + 1 WHERE `uid`='$result_user_uid_escaped'");
					}
					db_query("UPDATE `users` SET `in_process` = `in_process` - 1 WHERE `uid`='$result_user_uid_escaped'");
				}

				/*db_query("UPDATE `users`
							JOIN `workunit_results` ON `users`.`uid` = `workunit_results`.`user_uid`
							SET `balance`=`balance`+'$reward_amount_escaped'
							WHERE `workunit_uid`='$workunit_uid_escaped' AND `result_hash`='$result_hash'");*/
				//db_query("UPDATE `workunit_results` SET `is_valid`=1,`reward`='$reward_amount_escaped' WHERE `workunit_uid`='$workunit_uid_escaped' AND `result_hash`='$result_hash'");
				//db_query("UPDATE `workunit_results` SET `is_valid`=0,`reward`=0 WHERE `workunit_uid`='$workunit_uid_escaped' AND `result_hash`<>'$result_hash'");
				inc_variable("workunits_complete");
			}
		}
	}

	db_query("UNLOCK TABLES");
	return array("result"=>"ok");
}

/**
 * Check timeouts delete workunits that exceed time
 */
function microgrid_check_timeouts() {
	// Mark timed out workunits as invalid
}

/**
 * Get actual script version for project
 * 
 * @param $project_uid int Project id
 * 
 * @return int script version
 */
function microgrid_get_actual_version($project_uid) {
	$project_uid_escaped=db_escape($project_uid);

	return db_query_to_variable("SELECT `version` FROM `projects` WHERE `uid`='$project_uid_escaped'");
}

/**
 * Get computation script
 * 
 * @param $project_uid int Project id
 * 
 * @return string Script data
 */
function microgrid_get_function($project_uid) {
	$project_uid_escaped=db_escape($project_uid);

	return db_query_to_variable("SELECT `function` FROM `projects` WHERE `uid`='$project_uid_escaped'");
}
