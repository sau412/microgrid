<?php
require_once("../lib/settings.php");
require_once("../lib/logger.php");
require_once("../lib/db.php");

db_connect();

$projects_array = db_query_to_array("SELECT `uid` FROM `projects` WHERE `is_enabled` = 1");

foreach($projects_array as $project) {
    $project_uid = $project['uid'];
    $project_uid_escaped = db_escape($project_uid);

    echo "Exporting project $project_uid\n";

    while(1) {
        $is_earliest_completed = db_query_to_variable("SELECT `is_completed` FROM `workunits`
                            WHERE `project_uid`='$project_uid_escaped' AND DATE_SUB(NOW(), INTERVAL 1 DAY) > `timestamp`
                            ORDER BY `start_number` LIMIT 1");
        if(!$is_earliest_completed) {
            echo "Earliest result is incompleted, skipping\n";
            break;
        }

        $uids_to_delete = [];
        $result = db_query("SELECT `uid`, `start_number`, `stop_number`, `result`, `is_completed` FROM `workunits`
                            WHERE `project_uid`='$project_uid_escaped' AND DATE_SUB(NOW(), INTERVAL 1 DAY) > `timestamp`
                            ORDER BY `start_number` LIMIT 1000");
        $row_count = 0;
        while($row = mysql_fetch_assoc($result)) {
            $uid = $row['uid'];
            $uid_escaped = db_escape($uid);
            echo "Exporing project $project_uid workunit $uid\n";
            if($row['is_completed'] == 0) {
                echo "Incompleted result found, break\n";
                break;
            }
            
            $start = $row['start_number'];
            $stop = $row['stop_number'];
            $result_text = $row['result'];
            $csv = "$start;$stop;$result_text;\n";
            file_put_contents("../../results/${project_uid}.txt", $csv, FILE_APPEND);
            $uids_to_delete[] = $uid_escaped;
            //db_query("DELETE FROM `workunits` WHERE `uid` = '$uid_escaped'");
            //db_query("DELETE FROM `workunit_results` WHERE `workunit_uid` = '$uid_escaped'");
            $row_count ++;
        }

        $uids_to_delete_string_escaped = implode("','", $uids_to_delete);

        echo "Deleting workunits...\n";
        db_query("DELETE FROM `workunits` WHERE `uid` IN ('$uids_to_delete_string_escaped')");
        
        echo "Deleting results...\n";
        db_query("DELETE FROM `workunit_results` WHERE `workunit_uid` IN ('$uids_to_delete_string_escaped')");

        // Break if less than 1000 rows
        if($row_count < 1000) break;
    }
    echo "Exporting project $project_uid done\n";
}

echo "Done\n";