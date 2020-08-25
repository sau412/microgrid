<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");

db_connect();

$projects_array = db_query("SELECT `uid` FROM `projects`");

foreach($projects_array as $project) {
    $project_uid = $project['uid'];
    $project_uid_escaped = db_escape($project_uid);

    $result=db_query("SELECT `uid`, `start_number`, `stop_number`, `result`, `is_completed` FROM `workunits`
                        WHERE `project_uid`='$project_uid_escaped' AND DATE_SUB(NOW(), INTERVAL 1 MONTH) > `timestamp`
                        ORDER BY `start_number` LIMIT 100");
    while($row = mysql_fetch_assoc($result)) {
        if($row['is_completed'] == 0) break;
        $start = $row['start_number'];
        $stop = $row['stop_number'];
        $result = $row['result'];
        $csv = "$start;$stop;$result;\n";
        file_put_contents("../../results/${project_uid}.txt", $csv, FILE_APPEND);
    }
}
