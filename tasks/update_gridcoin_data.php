<?php
// Only for command line
if(!isset($argc)) die();

// Gridcoinresearch send rewards
require_once("../lib/settings.php");
require_once("../lib/logger.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/gridcoin_web_wallet.php");

// Check if unsent rewards exists
db_connect();

// Get balance
$current_balance=grc_web_get_balance();
set_variable("wallet_balance",$current_balance);
echo "Current balance: $current_balance\n";

// Get payout information for GRC
$payout_data_array=db_query_to_array("SELECT `uid`,`user_uid`,`address`,`amount`,`wallet_uid` FROM `payouts`
					WHERE `status` IN ('requested','processing') AND `address` IS NOT NULL");

// Sending unsent transactions
foreach($payout_data_array as $payout_data) {
	$uid=$payout_data['uid'];
	$user_uid=$payout_data['user_uid'];
	$address=$payout_data['address'];
	$amount=$payout_data['amount'];
	$wallet_uid=$payout_data['wallet_uid'];

	$uid_escaped=db_escape($uid);

	// If we have funds for this
	if($wallet_uid) {
		$tx_data=grc_web_get_tx_status($wallet_uid);
		if($tx_data) {
			switch($tx_data->status) {
				case 'address error':
					echo "Address error wallet uid '$wallet_uid' for address '$address' amount '$amount' GRC\n";
					//write_log("Address error wallet uid '$wallet_uid' for address '$address' amount '$amount' GRC");
					db_query("UPDATE `payouts` SET `tx_id`='address error',`status`='error' WHERE `uid`='$uid_escaped'");
					break;
				case 'sending error':
					echo "Sending error wallet uid '$wallet_uid' for address '$address' amount '$amount' GRC\n";
					//write_log("Sending error wallet uid '$wallet_uid' for address '$address' amount '$amount' GRC");
					db_query("UPDATE `payouts` SET `tx_id`='sending error',`status`='error' WHERE `uid`='$uid_escaped'");
					break;
				case 'received':
				case 'pending':
				case 'sent':
					$tx_id=$tx_data->tx_id;
					$tx_id_escaped=db_escape($tx_id);
					//write_log("Sent wallet uid '$wallet_uid' for address '$address' amount '$amount' GRC");
					echo "Sent wallet uid '$wallet_uid' for address '$address' amount '$amount' GRC\n";
					db_query("UPDATE `payouts` SET `tx_id`='$tx_id_escaped',`status`='sent' WHERE `uid`='$uid_escaped'");
					break;
			}
		}
	} else if($amount<$current_balance) {
		echo "Sending $amount to $address\n";

		// Send coins, get txid
		$wallet_uid=grc_web_send($address,$amount);
		$wallet_uid_escaped=db_escape($wallet_uid);
		if($wallet_uid && is_numeric($wallet_uid)) {
			db_query("UPDATE `payouts` SET `status`='processing',`wallet_uid`='$wallet_uid_escaped' WHERE `uid`='$uid_escaped'");
		} else {
			write_log("Sending error, no wallet uid for address '$address' amount '$amount' GRC");
		}
		echo "----\n";
	} else {
		// No funds
		echo "Insufficient funds for sending rewards\n";
		write_log("Insufficient funds for sending rewards");
		break;
	}
}

// Update balance after all
$current_balance=grc_web_get_balance();
set_variable("wallet_balance",$current_balance);

?>
