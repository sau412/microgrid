<?php
require_once("settings.php");
require_once("db.php");
require_once("core.php");

db_connect();

db_query("LOCK TABLES `users` WRITE, `payouts` WRITE");

$payout_data=db_query_to_array("SELECT `uid`,`balance`,`withdraw_address` FROM `users` WHERE `balance`<>0 AND `withdraw_address`<>''");

foreach($payout_data as $payout_row) {
        $user_uid=$payout_row['uid'];
        $balance=$payout_row['balance'];
        $address=$payout_row['withdraw_address'];

        $balance=round($balance,8);

        $balance_escaped=db_escape($balance);
        $address_escaped=db_escape($address);
        $user_uid_escaped=db_escape($user_uid);

        // Add payout
        db_query("INSERT INTO `payouts` (`user_uid`,`address`,`amount`) VALUES ('$user_uid_escaped','$address_escaped','$balance_escaped')");

        // Set balance to zero
        db_query("UPDATE `users` SET `balance`=0 WHERE `uid`='$user_uid_escaped'");
}

db_query("UNLOCK TABLES");
?>
