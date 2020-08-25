<?php
// Only for command line
if(!isset($argc)) die();

// Update workunits
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");

db_connect();

$workunits_in_progress_array = db_query_to_array("SELECT `uid` FROM `workunits` WHERE `in_progress` = 1");

foreach($workunits_in_progress_array as $row) {
	$workunit_uid = $row['uid'];
	$workunit_uid_escaped = db_escape($workunit_uid);

	$is_old = db_query_to_variable("SELECT 1 FROM `workunit_results`
										WHERE `result_hash` IS NULL AND
											DATE_SUB(NOW(),INTERVAL $workunit_max_interval) > `created` AND
											`workunit_uid` = '$workunit_uid_escaped'");
	if($is_old) {
		echo "workunit_uid $workunit_uid\n";
		db_query("UPDATE `workunits` SET `in_progress` = 0 WHERE `uid` = '$workunit_uid_escaped'");
	}
}
die();
$old_workunits_array=db_query_to_array("SELECT `uid`, `workunit_uid`
		FROM `workunit_results`
		WHERE `result_hash` IS NULL AND DATE_SUB(NOW(),INTERVAL $workunit_max_interval)>`created`");

foreach($old_workunits_array as $row) {
	$uid=$row['uid'];
	$workunit_uid=$row['workunit_uid'];
	$uid_escaped=db_escape($uid);
	$workunit_uid_escaped=db_escape($workunit_uid);

	db_query("UPDATE `workunits` SET `in_progress`=GREATEST(`in_progress`-1,0) WHERE `uid`='$workunit_uid_escaped'");
	db_query("UPDATE `workunit_results` SET `is_valid`=0,`reward`=0,`result_hash`='',`completed`=NOW() WHERE `uid`='$uid_escaped'");
}
?>
