<?php
require_once("../lib/settings.php");
require_once("../lib/logger.php");
require_once("../lib/db.php");

db_connect();

echo "Deleting all finished...\n";
db_query("DELETE FROM `workunit_results` WHERE  DATE_SUB(NOW(), INTERVAL $workunit_max_interval) > `created`");

/*
echo "Deleting all not linked to workunits...\n";
$counter = 0;
do {
        $uid_to_delete = db_query_to_variable("SELECT wr.`uid` FROM `workunit_results` wr
                                                LEFT OUTER JOIN `workunits` w ON w.`uid` = wr.`workunit_uid`
                                                WHERE w.`uid` IS NULL LIMIT 1");
        echo "Delete workunit_result $uid_to_delete\n";
        $uid_escaped = db_escape($uid_to_delete);
        db_query("DELETE FROM `workunit_results` WHERE `uid` = '$uid_escaped'");
        $counter ++;
} while($uid_to_delete);

echo "Total: $counter\n";
*/