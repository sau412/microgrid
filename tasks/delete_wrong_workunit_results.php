<?php
require_once("../lib/settings.php");
require_once("../lib/logger.php");
require_once("../lib/db.php");

db_connect();

db_query("DELETE FROM `workunit_results` WHERE `is_valid` = '0'");
