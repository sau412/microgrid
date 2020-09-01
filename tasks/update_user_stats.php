<?php
// For calculating user results

require_once("../lib/settings.php");
require_once("../lib/logger.php");
require_once("../lib/db.php");
require_once("../lib/core.php");

db_connect();

$users_array=db_query_to_array("SELECT `uid` FROM `users`");

foreach($users_array as $user) {
        $user_uid = $user['uid'];
        $user_uid_escaped = db_escape($user_uid);
        echo "$user_uid\n";
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

        if(!$valid_count) $valid_count = 0;
        if(!$in_process) $in_process = 0;
        if(!$paid) $paid = 0;
        if(!$not_paid) $not_paid = 0;

        echo "total $total\n";
        echo "valid_count $valid_count\n";
        echo "in_process $in_process\n";
        echo "paid $paid\n";
        echo "not_paid $not_paid\n";
        echo "total_reward $total_reward\n";
        echo "\n";

        //db_query("UPDATE `users` SET `total_results`='$total' WHERE `uid`='$user_uid_escaped'");
        //db_query("UPDATE `users` SET `valid_results`='$valid_count' WHERE `uid`='$user_uid_escaped'");
        //db_query("UPDATE `users` SET `in_process`='$in_process' WHERE `uid`='$user_uid_escaped'");
        //db_query("UPDATE `users` SET `paid_results`='$paid' WHERE `uid`='$user_uid_escaped'");
        //db_query("UPDATE `users` SET `not_paid_results`='$not_paid' WHERE `uid`='$user_uid_escaped'");
        //db_query("UPDATE `users` SET `total_earned`='$total_reward' WHERE `uid`='$user_uid_escaped'");
}
