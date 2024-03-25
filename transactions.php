<?php include "includes567.php"; ?>
<?php

$address=strip_all($_GET["address"] ?? "");
$mode=strip_all($_GET["mode"] ?? "");
$ascdesc = strip_all($_GET["ascdesc"] ?? "");
$from = strip_all($_GET["from"] ?? "");
$to = strip_all($_GET["to"] ?? "");
$sort = strip_all($_GET["sort"] ?? "");
$range = strip_all($_GET["range"] ?? "");
$run_mode = $mode;

// Initialize an empty hash map
$addressToName = [];
$addressToPlayer = [];

// Load player.csv into an address<->name lookup table
//loadPlayerData();

//validate all inputs and reset the sht if anyone has tried passing badness
$is_address = validate_tnam1($address);
$is_public_key = validate_tpknam1($address);
if ($is_public_key) {
  $player = getPlayerFromAddress($address);
  $address = $player->address;
}
if ((!$is_address and !$is_public_key) or (!empty($ascdesc) and $ascdesc !== 'asc' and $ascdesc !== 'desc') or (!empty($sort) and $sort !== '5' and $sort !== '2' and $sort !== '3' and $sort !== '4' and $sort !== '6' and $sort !== '7') or ($mode !== 'transactions' and $mode !== 'connected' and $mode !== 'num_transfers' and !empty($mode)) or (!empty($range) and $range !== 'num_incoming' and $range!== 'num_outgoing' and $range !== 'sum_incoming' and $range !== 'sum_outgoing' and $range !== 'wallets_outgoing' and $range !== 'wallets_incoming')  ) {
    $run_mode = 'default';
    $address = '';
    $from = '';
    $to = '';
    $ascdesc = '';
    $sort = '';
    $error = 'don\t mess around with the inputs!';
} elseif (($mode === 'transactions' or $mode === 'connected') and empty($address)) {
	$run_mode = 'default';
	$error = 'input valid tnam.';
} elseif ( (!empty($from) and !validate_double($from)) or (!empty($to) and !validate_double($to)) ) {
  $from = ''; $to = '';
  $error = 'only numbers accepted in range field';
} elseif ( !empty($from) and !empty($to) and $to < $from ) {
       $run_mode = 'default';	
    $error = 'from value must be less than to value!';
 }


?>
<html>

<head>
<link rel="stylesheet" href="https://cdn.simplecss.org/simple.min.css">
<style>
body { 
  display: block;
  padding: 10px;
}
table, th, td {
white-space: nowrap;
}


</style>

<title>Transactions Explorer</title>

</head>

<body>

<h2>Transactions Explorer</h2>

<?php
if (!empty($maintenance)) {
  echo("<div>Maintenance note: $maintenance</div><br><br>");
}
?>

<div>
This tool lets you check various aspects around transactions. It's based on the indexer database, so results may be limited to what's decoded there.<br><br> 
Note that only wallets that have conducted at least one transfer will appear in this dataset, so the list is incomplete particularly when it comes to wallets with zero transactions.<br><br>

We will be adding functions continously. Join our <a href="https://github.com/opsecx/Transactions-Checker/" target="_blank">github</a> for helping out!<br><br>
</div>
<?php 

$filename = strip_all(basename($_SERVER['PHP_SELF'])); 



if (!empty($error)) {
  echo("<div style=\"color:red\">$error</div><br>");
}

?>



<form action="<?php echo($filename);?>" method="get">
	Address or public key: <input type="text" name="address" size="41" value="<?php echo($address);?>"><br>
	<button type="submit" name="mode" value="transactions">Check Transactions</button> 
	<button type="submit" name="mode" value="connected">Check Connected Wallets</button><br><br>
	Sort by <select name="sort">
<option value="2"<?php if($sort==="2") {echo(" selected");} ?>># Transactions Sent</option>
<option value="3"<?php if($sort==="3") {echo(" selected");} ?>># Transactions Received</option>
<option value="4"<?php if($sort==="4") {echo(" selected");} ?>>Amount Sent</option>
<option value="5"<?php if($sort==="5" or empty($sort)) {echo(" selected");} ?>>Amount Received</option>
<option value="6"<?php if($sort==="6") {echo(" selected");} ?>># Wallets Outgoing</option>
<option value="7"<?php if($sort==="7") {echo(" selected");} ?>># Wallets Incoming</option>
</select>
<select name="ascdesc"><option value="asc"<?php if($ascdesc === 'asc') {echo('selected');}?>>Ascending</option><option value="desc"<?php if ($ascdesc === 'desc' or empty($ascdesc)) {echo(' selected');}?>>Descending</option></select><br> From <input type="text" name="from" value="<?php echo($from);?>"> To <input type="text" name="to" value="<?php echo($to);?>">
<select name="range">
<option value="num_outgoing"<?php if($range==='num_outgoing') {echo(" selected");} ?>># Transactions Sent</option>
<option value="num_incoming"<?php if($range==='num_incoming') {echo(" selected");} ?>># Transactions Received</option>
<option value="sum_outgoing"<?php if($range==='sum_outgoing') {echo(" selected");} ?>>Amount Sent</option>
<option value="sum_incoming"<?php if($range==='sum_incoming' or empty($range)) {echo(" selected");} ?>>Amount Received</option>
<option value="wallets_outgoing"<?php if($range==='wallets_outgoing' or empty($range)) {echo(" selected");} ?>># Wallets Outgoing</option>
<option value="wallets_incoming"<?php if($range==='wallets_incoming' or empty($range)) {echo(" selected");} ?>># Wallets Incoming</option>
</select>

<br>
<button type="submit" name="mode" value="num_transfers">Show Wallets</button>
</form>

<?php
//assume $dbconn opened by includes

if ($run_mode == 'transactions') {	
  $query = "SELECT TO_CHAR(header_time::timestamp, 'YYYY-MM-DD HH24:MI:SS') AS time, REPLACE(source, 'tnam1pcqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqzmefah', 'shielded') AS source, REPLACE(target, 'tnam1pcqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqzmefah', 'shielded') AS target, amount, header_height AS block_height, tx_id AS transaction_id FROM shielded_expedition.tx_transfer LEFT JOIN shielded_expedition.transactions ON tx_transfer.tx_id = shielded_expedition.transactions.hash LEFT JOIN shielded_expedition.blocks ON transactions.block_id = blocks.block_id
 WHERE token = 'tnam1qxvg64psvhwumv3mwrrjfcz0h3t3274hwggyzcee' AND (source = '$address' OR target = '$address') ORDER BY 5 ASC";
//  $table_headers = array('date_time1', 'source', 'target', 'amount', 'block_height', 'transaction id'); 
} elseif ($run_mode == 'connected') {
  $query = "SELECT REPLACE(COALESCE(query1.wallet, query2.wallet),
    'tnam1pcqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqzmefah', 'shielded') as wallet,
       COALESCE(num_outgoing, 0) as transactions_out,
       COALESCE(num_incoming, 0) as transactions_in,
       COALESCE(sum_outgoing, 0) as amount_sent,
       COALESCE(sum_incoming, 0) as amount_received
        FROM
(SELECT source AS wallet, count(amount) as num_incoming,
        SUM(amount::double precision) AS sum_incoming
FROM shielded_expedition.tx_transfer
WHERE target = '$address'
AND token = 'tnam1qxvg64psvhwumv3mwrrjfcz0h3t3274hwggyzcee'
GROUP BY SOURCE) as query2
FULL JOIN (SELECT target as wallet, count(amount) as num_outgoing,
            SUM(amount::double precision) AS sum_outgoing
     FROM shielded_expedition.tx_transfer
     WHERE source = '$address'
     AND token = 'tnam1qxvg64psvhwumv3mwrrjfcz0h3t3274hwggyzcee'
                                          GROUP BY target) AS query1
        ON query2.wallet = query1.wallet
  ORDER BY 5 DESC";
} elseif ($run_mode == 'num_transfers') {
	if (empty($range)) {die("internal error");}
	$range_c = "COALESCE($range, 0)";
	$where_clause = '';
	if ((!empty($from) or $from === '0') and (!empty($to) or $to === '0')) {
	  $where_clause = "WHERE $range_c <= $to AND $range_c >= $from";
	} elseif (!empty($from)) {
	  $where_clause = "WHERE $range_c >= $from";
	} elseif (!empty($to)) {
	  $where_clause = "WHERE $range_c <= $to";
	} //if both are empty, where clause will be blank	
	$sort_o = '';
	if ($sort === '2') {$sort_o = "2 $ascdesc, 4 $ascdesc, 1 asc";} 
	elseif ($sort === '3') {$sort_o = "3 $ascdesc, 5 $ascdesc, 1 asc";}
	elseif ($sort === '4') {$sort_o = "4 $ascdesc, 2 $ascdesc, 1 asc";}
	elseif ($sort === '5') {$sort_o = "5 $ascdesc, 3 $ascdesc, 1 asc";}
	elseif ($sort === '6') {$sort_o = "6 $ascdesc, 4 $ascdesc, 1 asc";}
	elseif ($sort === '7') {$sort_o = "7 $ascdesc, 5 $ascdesc, 1 asc";}
	else {$sort_o = "$sort $ascdesc";}
	if(!empty($sort_o)) {$order_clause = "ORDER BY $sort_o";} else {$order_clause = '';}
	//echo("$order_clause $where_clause");
   $query = "SELECT REPLACE(COALESCE(query1.wallet, query2.wallet),
    'tnam1pcqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqzmefah', 'shielded') as wallet,
       COALESCE(num_outgoing, 0) as num_tx_out,
       COALESCE(num_incoming, 0) as num_tx_in,
       COALESCE(sum_outgoing, 0) as sum_sent,
       COALESCE(sum_incoming, 0) as sum_received,
	COALESCE(wallets_outgoing, 0) as wallets_outgoing,
        COALESCE(wallets_incoming, 0) as wallets_incoming
        FROM
(SELECT source AS wallet, count(amount) as num_outgoing, SUM(amount::double precision) AS sum_outgoing, count(distinct target) as wallets_outgoing
FROM shielded_expedition.tx_transfer WHERE token = 'tnam1qxvg64psvhwumv3mwrrjfcz0h3t3274hwggyzcee' GROUP BY SOURCE) as query2
FULL JOIN (SELECT target as wallet, count(amount) as num_incoming,
	    SUM(amount::double precision) AS sum_incoming, 
	count(distinct source) as wallets_incoming
     FROM shielded_expedition.tx_transfer WHERE token = 'tnam1qxvg64psvhwumv3mwrrjfcz0h3t3274hwggyzcee' GROUP BY target) AS query1
	ON query2.wallet = query1.wallet
  $where_clause  
  $order_clause";
}

if ($run_mode == 'transactions' or $run_mode == 'connected' or $run_mode == 'num_transfers' or $run_mode == 'sum_received' or $run_mode == 'sum_sent') {

$results = pg_query($dbconn, $query)
 or die('could not fetch results');

if ($run_mode === 'transactions') {
  	
  $query2 = "SELECT COUNT(*) AS outgoing FROM shielded_expedition.tx_transfer WHERE source = '$address' and token = 'tnam1qxvg64psvhwumv3mwrrjfcz0h3t3274hwggyzcee'";
  $query3 = "SELECT COUNT(*) AS incoming FROM shielded_expedition.tx_transfer WHERE target = '$address' AND token = 'tnam1qxvg64psvhwumv3mwrrjfcz0h3t3274hwggyzcee'";
  $query4 = "SELECT SUM(amount::double precision) AS sum_outgoing FROM shielded_expedition.tx_transfer WHERE source = '$address' AND token = 'tnam1qxvg64psvhwumv3mwrrjfcz0h3t3274hwggyzcee'";
  $query5 = "SELECT SUM(amount::double precision) AS sum_incoming FROM shielded_expedition.tx_transfer WHERE target = '$address' AND token = 'tnam1qxvg64psvhwumv3mwrrjfcz0h3t3274hwggyzcee'";
  $query6 = "SELECT COUNT(*) FROM (SELECT target FROM shielded_expedition.tx_transfer WHERE source = '$address' AND token = 'tnam1qxvg64psvhwumv3mwrrjfcz0h3t3274hwggyzcee' GROUP BY tx_transfer.target) AS query1";
  $query7 = "SELECT COUNT(*) FROM (SELECT source FROM shielded_expedition.tx_transfer WHERE target = '$address' AND token = 'tnam1qxvg64psvhwumv3mwrrjfcz0h3t3274hwggyzcee' GROUP BY tx_transfer.source) AS query1";
  $results2 = pg_query($dbconn, $query2) or die('could not fetch count2');
  $results3 =  pg_query($dbconn, $query3) or die('could not fetch count3');
  $results4 =  pg_query($dbconn, $query4) or die('could not fetch count4');
  $results5 =  pg_query($dbconn, $query5) or die('could not fetch count5');
  $results6 =  pg_query($dbconn, $query6) or die('could not fetch count6');
  $results7 =  pg_query($dbconn, $query7) or die('could not fetch count7');

  $sent = pg_fetch_array($results2, null, PGSQL_NUM)[0];
  $received = pg_fetch_array($results3, null, PGSQL_NUM)[0];
  $sum_sent = pg_fetch_array($results4, null, PGSQL_NUM)[0];
  $sum_received = pg_fetch_array($results5, null, PGSQL_NUM)[0];
  $wallets_sent = pg_fetch_array($results6, null, PGSQL_NUM)[0];
  $wallets_received = pg_fetch_array($results7, null, PGSQL_NUM)[0];

  $player = getPlayerFromAddress($address);
  
  echo("Name: {$player->name}<br>\n");
  echo("Address: $address<br>\n");
  if ($player->publicKey) echo("Public Key: {$player->publicKey}<br>\n");
  echo("Player type: {$player->playerType}<br>\n");
  echo("Transactions sent: $sent Transactions received: $received<br>\n");
  echo("Total amount sent: $sum_sent naan Total amount received: $sum_received naan<br>\n");
  echo("Number of wallet sent to: $wallets_sent Number of wallets received from: $wallets_received<br>\n");
  
  $score = number_format($player->score);
  echo("ROIDs: {$score}<br><br>\n");
  pg_free_result($results2); 
  pg_free_result($results3);
  pg_free_result($results4);
  pg_free_result($results5);
  pg_free_result($results6);
  pg_free_result($results7); 


 }

echo "<table>\n";

if (!empty($table_headers)) {
	foreach ($table_headers as $x) {
  	  echo "<th>$x</th>";
		}
} else {
	$i = 0;
	while ($i < pg_num_fields($results)) {

	echo('<th>' . pg_field_name($results, $i) . '</td>');
	$i = $i + 1;
	}
}

while ($line = pg_fetch_array($results, null, PGSQL_ASSOC)) {
    echo "\t<tr>\n";
    foreach ($line as $col_value) {
	echo("\t\t<td>"); 
	if ($col_value != $address and validate_tnam1($col_value)) {
	   echo(hyperlink_address($col_value, 'transactions'));
	} elseif ($col_value == $address) {
     $player = getPlayerFromAddress($address);
     echo ("<div style=\"color: grey\">{$player->name}</div>");
	} else {
	  echo($col_value);
	}
	echo("</td>\n");
    }
    echo "\t</tr>\n";
}
echo "</table>\n";

pg_free_result($results);
pg_close($dbconn);

//echo("test");

//echo($dbconn);

//phpinfo();

}




?>


</body>

</html>
