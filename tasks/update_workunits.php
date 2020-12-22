<?php
// Only for command line
if(!isset($argc)) die();

// Update workunits
require_once("../lib/settings.php");
require_once("../lib/logger.php");
require_once("../lib/db.php");
require_once("../lib/core.php");

db_connect();

$workunits_in_progress_array = db_query_to_array("SELECT `uid` FROM `workunits` WHERE `in_progress` <> 0");
$workunit_max_interval = "1 HOUR";

foreach($workunits_in_progress_array as $row) {
	$workunit_uid = $row['uid'];
	$workunit_uid_escaped = db_escape($workunit_uid);

	$is_new = db_query_to_variable("SELECT 1 FROM `workunit_results`
										WHERE `result_hash` IS NULL AND
											DATE_SUB(NOW(),INTERVAL $workunit_max_interval) < `created` AND
											`workunit_uid` = '$workunit_uid_escaped'");
	if(!$is_new) {
		echo "workunit_uid $workunit_uid\n";
		db_query("UPDATE `workunits` SET `in_progress` = 0 WHERE `uid` = '$workunit_uid_escaped'");
	}
}

