<?php
require_once("../lib/settings.php");
require_once("../lib/logger.php");
require_once("../lib/db.php");

db_connect();

$result=db_query("SELECT `uid` FROM `workunits` WHERE `project_uid`=1 ORDER BY `start_number`");

while($row = mysql_fetch_assoc($result)) {
	$uid=$row['uid'];
	$result=db_query_to_variable("SELECT `result` FROM `workunits` WHERE `uid`='$uid'");
	if($result == "") {
		$start_number=db_query_to_variable("SELECT `start_number` FROM `workunits` WHERE `uid`='$uid'");
		$stop_number=db_query_to_variable("SELECT `stop_number` FROM `workunits` WHERE `uid`='$uid'");
		echo "Missed results from $start_number to $stop_number\n";
	} else {
		$result=str_replace(array("[","]"),"",$result);
		$numbers=explode(",",$result);
		foreach($numbers as $number) {
			$pair = $number + 2;
			echo "$number $pair\n";
		}
	}
}
?>
