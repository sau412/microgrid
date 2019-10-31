<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");

db_connect();

$rating=get_global_rating();

set_variable("rating_cache",json_encode($rating));
?>
