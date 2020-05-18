<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");

db_connect();

$users=db_query_to_variable("SELECT count(*) FROM `users`");

$active_users=db_query_to_variable("SELECT count(DISTINCT `user_uid`) FROM `workunit_results` WHERE DATE_SUB(NOW(),INTERVAL 1 HOUR)<`created`");

$workunits=db_query_to_variable("SELECT count(*) FROM `workunits`");

$results=db_query_to_variable("SELECT count(*) FROM `workunit_results`");

$workunits_complete=db_query_to_variable("SELECT count(*) FROM `workunits` WHERE `is_completed`=1");

$results_complete=db_query_to_variable("SELECT count(*) FROM `workunit_results` WHERE `result_hash` IS NOT NULL");

$wallet_balance=db_query_to_variable("SELECT `value` FROM `variables` WHERE `name`='wallet_balance'");

$users_balance=db_query_to_variable("SELECT SUM(`balance`) FROM `users`");

echo "users:$users active_users:$active_users workunits:$workunits workunits_complete:$workunits_complete results:$results results_complete:$results_complete wallet_balance:$wallet_balance users_balance:$users_balance\n";
?>
