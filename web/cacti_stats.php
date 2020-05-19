<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");

db_connect();

$users = get_variable('users');
$active_users = get_variable('active_users');
$workunits = get_variable('workunits');
$results = get_variable('results');
$workunits_complete = get_variable('workunits_complete');
$results_complete = get_variable('results_complete');
$wallet_balance = get_variable('wallet_balance');
$users_balance = get_variable('users_balance');

echo "users:$users active_users:$active_users workunits:$workunits workunits_complete:$workunits_complete results:$results results_complete:$results_complete wallet_balance:$wallet_balance users_balance:$users_balance\n";
?>
