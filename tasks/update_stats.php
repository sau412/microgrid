<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");

db_connect();

$users=db_query_to_variable("SELECT count(*) FROM `users`");

$active_users=db_query_to_variable("SELECT count(DISTINCT `uid`) FROM `users` WHERE DATE_SUB(NOW(),INTERVAL 1 HOUR)<`active_time`");

//$workunits=db_query_to_variable("SELECT count(*) FROM `workunits`");
//$results=db_query_to_variable("SELECT count(*) FROM `workunit_results`");
//$workunits_complete=db_query_to_variable("SELECT count(*) FROM `workunits` WHERE `is_completed`=1");
//$results_complete=db_query_to_variable("SELECT count(*) FROM `workunit_results` WHERE `result_hash` IS NOT NULL");

$users_balance=db_query_to_variable("SELECT SUM(`balance`) FROM `users`");

set_variable("users", $users);
set_variable("active_users", $active_users);
//set_variable("workunits", $workunits);
//set_variable("results", $results);
//set_variable("workunits_complete", $workunits_complete);
//set_variable("results_complete", $results_complete);
set_variable("users_balance", $users_balance);

?>
