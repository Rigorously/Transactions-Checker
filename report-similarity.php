<html>

<head>
	<link rel="stylesheet" href="simple.min.css">
	<style>
		.match {
			font-weight: bold;
		}

		.moniker {
			font-size: x-large;
		}

		td:nth-child(n+2) {
			width: 45%;
		}

		table.col-15 td:first-child {
			width: 15%;
		}

		.txchars {
			font-size: small;
			display: block;
		}

		.small {
			font-size: small;
		}

		.score {
			font-size: small;
		}

		.block {
			font-size: small;
		}

		.score.match,
		.block.match {
			color: green;
		}

		.matchPercentage {
			display: none;
			font-size: small;
		}

		.levenshtein:hover+.matchPercentage {
			display: block;
		}

		a.external::after {
			content: "";
			width: 11px;
			height: 11px;
			margin-left: 4px;
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5z'/%3E%3Cpath fill-rule='evenodd' d='M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0v-5z'/%3E%3C/svg%3E");
			background-position: center;
			background-repeat: no-repeat;
			background-size: contain;
			display: inline-block;
		}
	</style>
</head>

<body>
	<?php

	include "DamerauLevenshtein.php";

	use Oefenweb\DamerauLevenshtein\DamerauLevenshtein;

	include "includes567.php";

	$filename = strip_all(basename($_SERVER['PHP_SELF']));
	$identifier = strip_all($_GET["identifier"] ?? "");
	$playerType = strtolower(strip_all($_GET["player_type"] ?? "")) == 'pilot' ? 'Pilot' : 'Crew';

	$defaultMinMatch = 30;
	$paramMinMatch = strip_all($_GET["min_match"] ?? $defaultMinMatch);
	$minMatchPercent = $paramMinMatch >= 0 && $paramMinMatch <= 100 ? $paramMinMatch : $defaultMinMatch;

	$defaultMaxLevenshtein = 20;
	$paramMaxLevenshtein = strip_all($_GET["max_levenshtein"] ?? $defaultMaxLevenshtein);
	$maxLevenshtein = $paramMaxLevenshtein >= 0 && $paramMaxLevenshtein <= 100 ? $paramMaxLevenshtein : $defaultMaxLevenshtein;

	// TODO Configurable ranges
	$minTransactions = 5;
	$maxTransactions = 20;
	$maxTopPlayers = 200;

	// Damerau–Levenshtein distance cost
	$insCost = 1;
	$delCost = 1;
	$subCost = 1;
	$transCost = 1;

	$txStrings = array(
		'tx_become_validator' => 'Become Validator',
		'tx_bond' => 'Bond',
		'tx_bridge_pool' => 'Bridge Pool',
		'tx_change_consensus_key' => 'Change Consensus Key',
		'tx_change_validator_commission' => 'Change Validator Commission',
		'tx_change_validator_comission' => 'Change Validator Commission',
		'tx_change_validator_metadata' => 'Change Validator Metadata',
		'tx_claim_rewards' => 'Claim Rewards',
		'tx_deactivate_validator' => 'Deactivate Validator',
		'tx_ibc' => 'IBC',
		'tx_init_account' => 'Init Account',
		'tx_init_proposal' => 'Init Proposal',
		'tx_reactivate_validator' => 'Reactivate Validator',
		'tx_redelegate' => 'Redelegate',
		'tx_resign_steward' => 'Resign Steward',
		'tx_reveal_pk' => 'Reveal PK',
		'tx_transfer' => 'Transfer',
		'tx_transfert' => 'Transfer',
		'tx_unbond' => 'Unbond',
		'tx_unjail_validator' => 'Unjail Validator',
		'tx_update_account' => 'Update Account',
		'tx_update_steward_commission' => 'Update Steward Commission',
		'tx_vote_proposal' => 'Vote Proposal',
		'tx_withdraw' => 'Withdraw'
	);

	$txChars = array(
		'tx_become_validator' => 'a',
		'tx_bond' => 'b',
		'tx_bridge_pool' => 'c',
		'tx_change_consensus_key' => 'd',
		'tx_change_validator_commission' => 'e',
		'tx_change_validator_comission' => 'e',
		'tx_change_validator_metadata' => 'f',
		'tx_claim_rewards' => 'g',
		'tx_deactivate_validator' => 'h',
		'tx_ibc' => 'i',
		'tx_init_account' => 'j',
		'tx_init_proposal' => 'k',
		'tx_reactivate_validator' => 'l',
		'tx_redelegate' => 'm',
		'tx_resign_steward' => 'n',
		'tx_reveal_pk' => 'o',
		'tx_transfer' => 'p',
		'tx_transfert' => 'p',
		'tx_unbond' => 'q',
		'tx_unjail_validator' => 'r',
		'tx_update_account' => 's',
		'tx_update_steward_commission' => 't',
		'tx_vote_proposal' => 'u',
		'tx_withdraw' => 'v'
	);

	?>
	<h2>Report: early transaction type sequence similarity of the top <?= $maxTopPlayers ?></h2>
	<p>The transactions types of the first 20 transactions of each top <?= $maxTopPlayers ?> are compared. This report lists the matches with the least differences. Players whose transactions match the most with another player's transactions are listed at the top. For Pilots this is less of a red flag, as some tasks involved repeating certain transaction types.</p>
	<form action="<?php echo ($filename); ?>" method="get">
		<p><label for="player_type">Player type: </label><select name="player_type" id="player_type">
				<option value="crew" <?= ($playerType == 'Crew') ? 'selected' : '' ?>>Crew Member</option>
				<option value="pilot" <?= ($playerType == 'Pilot') ? 'selected' : '' ?>>Pilot</option>
			</select></p>
		<p><button type="submit">Show</button></p>
		<p>The matching algorithm Damerau–Levenshtein takes into account insertions, deletions, subtitutions and
			transpositions, each having an edit distance cost (denoted as DL from here on). The more differences between
			transactions, the higher the DL. The lower the DL, the more
			similar both transaction sequences are. Transactions that match exactly are in boldface.</p>
	</form>
	<script>
		var maxLevenshteinSlider = document.getElementById("maxLevenshteinSlider");
		var maxLevenshteinDisplay = document.getElementById("maxLevenshteinDisplay");
		maxLevenshteinDisplay.innerHTML = maxLevenshteinSlider.value;
		maxLevenshteinSlider.oninput = function() {
			maxLevenshteinDisplay.innerHTML = this.value;
		}

		var slider = document.getElementById("minMatchPercentSlider");
		var minMatchPercentDisplay = document.getElementById("minMatchPercentDisplay");
		minMatchPercentDisplay.innerHTML = minMatchPercentSlider.value;
		minMatchPercentSlider.oninput = function() {
			minMatchPercentDisplay.innerHTML = this.value;
		}
	</script>
	<?php

	$playerReports = [];
	$topPlayers = getTopPlayers($playerType);
	$earlyTransactionsCache = [];

	foreach ($topPlayers as $player)
	{
		if ($player)
		{
			$earlyTransactions = getEarlyTransactions($player['public_key']);
			$player['num_transactions'] = count($earlyTransactions);
			$thisPlayerCodeTypes = [];
			$thisPlayerTxChars = '';
			foreach ($earlyTransactions as $ettxid => $et)
			{
				$thisPlayerCodeTypes[$ettxid] = getTxDescription($et);
				$thisPlayerTxChars .= getTxChar($et);
			}

			if (false) //$player['num_transactions'] == 0
			{
				echo "<p>Player {$player['name']} has no transactions</p>";
			}
			else
			{
				$matchPool = [];
				foreach ($topPlayers as $tp)
				{
					if ($player['num_transactions'] == 0 || $tp['public_key'] == $player['public_key'])
					{
						continue;
					}
					$matches = 0;
					$numTransactions = 0;

					$tpTransactions = getEarlyTransactions($tp['public_key']);

					$list = "";
					$thatPlayerTxChars = '';
					foreach ($earlyTransactions as $ettxid => $et)
					{
						if (!isset($tpTransactions[$ettxid]))
						{
							break;
						}
						$numTransactions++;
						$typeMatchClass = '';
						$blockMatchClass = '';
						$thatPlayerCodeType = getTxDescription($tpTransactions[$ettxid]);
						$thatPlayerTxChars .= getTxChar($tpTransactions[$ettxid]);
						if ($thisPlayerCodeTypes[$ettxid] == $thatPlayerCodeType)
						{
							$matches++;
							$typeMatchClass = "match";
						}
						if ($et['header_height'] == $tpTransactions[$ettxid]['header_height'])
						{
							$blockMatchClass = "match";
						}
					}
					if ($numTransactions < $minTransactions)
					{
						continue;
					}
					$matchPercent = round($matches / $numTransactions * 100);
					$levenshtein = levenshtein($thisPlayerTxChars, $thatPlayerTxChars, $insCost, $subCost, $delCost);
					$damerauLevenshtein = new DamerauLevenshtein($thisPlayerTxChars, $thatPlayerTxChars, $insCost, $delCost, $subCost, $transCost);
					$levenshtein = $damerauLevenshtein->getSimilarity();
					
					$roidsMatchClass = '';
					if ($player['score'] == $tp['score'])
					{
						$roidsMatchClass = 'match';
					}
					$header = "<tr class='moniker'><td><div class='levenshtein'>DL" . $levenshtein . "</div><div class='matchPercentage'>EM " . $matchPercent . "%</div></td><td>"
						. "<div class='txchars'>$thisPlayerTxChars</div><div class='moniker'><a href='similarity.php" . modifyQueryString('identifier', $player['name']) . "'>" . $player['name'] ."</a></div>"
						. "<div class='small'>#" . $player['rank'] . " <span class='score $roidsMatchClass'>ROIDs: " . number_format($player['score']) . "</span></div></td><td>"
						. "<div class='txchars'>$thatPlayerTxChars</div><div class='moniker'>" . "<a href='similarity.php" . modifyQueryString('identifier', $tp['name']) . "'>" . $tp['name'] . "</a></div>"
						. "<div class='small'>#" . $tp['rank'] . " <span class='score $roidsMatchClass'>ROIDs: " . number_format($tp['score']) . "</span></div></td></tr>\n";
					$match = [];
					$match['matchPercent'] = $matchPercent;
					$match['Levenshtein'] = $levenshtein;
					$match['header'] = $header;
					$matchPool[] = $match;
					
				}
			
				// Get the lowest DL
				if (!empty($matchPool))
				{
					usort($matchPool, "matchSort");
					$m = $matchPool[0];
					$playerReport = [];
					$playerReport['Levenshtein'] = $m['Levenshtein'];
					$playerReport['header'] = $m['header'];
					$playerReports[] = $playerReport;
				}
				else
				{
					$playerReport = [];
					$playerReport['Levenshtein'] = -1;
					$match['matchPercent'] = 100;
					$header = "<tr class='moniker'><td><div class='moniker'><a href='similarity.php" . modifyQueryString('identifier', $player['name']) . "'>" . $player['name'] ."</a></div>"
					. "<div class='small'>#" . $player['rank'] . " <span>ROIDs: " . number_format($player['score']) . "</span></div></td><td>Not enough transactions: " . $player['num_transactions'] . "</td></tr>\n";
					$playerReport['header'] = $header;
					$playerReports[] = $playerReport;
				}
			}
		}
		else
		{
			//$playerType = $playerType == 'Crew' ? 'Pilot' : 'Crew';
			//echo "<p>Player not found in database. Try <a href='" . modifyQueryString('player_type', $playerType) . "'>" . $playerType . "</a> database.</p>";
		}
	}

	usort($playerReports, "matchSort");
	foreach ($playerReports as $report)
	{
		echo "<table>";
		echo $report['header'];
		echo "</table>";
	}



	function matchSort($a, $b)
	{
		return $a['Levenshtein'] > $b['Levenshtein'];
		//return $a['matchPercent'] < $b['matchPercent'];
	}

	function getPlayer($identifier, $playerType)
	{
		global $dbconn;
		$result = pg_query_params(
			$dbconn,
			"SELECT address, name, public_key, score, rank
			FROM shielded_expedition.players 
			WHERE (LOWER(public_key) = LOWER($1) OR LOWER(name) = LOWER($1) OR LOWER(address) = LOWER($1)) AND player_type = $2
			ORDER BY score DESC LIMIT 1;",
			[$identifier, $playerType]
		);
		$obj = pg_fetch_array($result, null, PGSQL_ASSOC);
		return $obj;
	}

	function getTopPlayers($playerType)
	{
		global $dbconn, $maxTopPlayers;
		$result = pg_query_params(
			$dbconn,
			"SELECT name, address, public_key, score, rank 
			FROM shielded_expedition.players 
			WHERE player_type = $1
			ORDER BY score DESC LIMIT $2;",
			[$playerType, $maxTopPlayers]
		);
		$obj = pg_fetch_all($result, PGSQL_ASSOC);
		return $obj;
	}

	function getEarlyTransactions($publicKey)
	{
		global $dbconn, $maxTransactions, $earlyTransactionsCache;

		if (isset($earlyTransactionsCache[$publicKey]))
		{
			return $earlyTransactionsCache[$publicKey];
		}

		$result = pg_query_params(
			$dbconn,
			"SELECT code_type, data->>'shielded' AS shielded, header_height, TO_CHAR(header_time::timestamp, 'YYYY-MM-DD HH24:MI:SS') AS time
			FROM shielded_expedition.early_tx 
			WHERE memo = $1
			ORDER BY header_height ASC LIMIT $2;",
			[$publicKey, $maxTransactions]
		);
		$obj = pg_fetch_all($result, PGSQL_ASSOC);
		if (count($obj) == 0)
		{
			$result = pg_query_params(
				$dbconn,
				"SELECT code_type, data->>'shielded' AS shielded, header_height, TO_CHAR(header_time::timestamp, 'YYYY-MM-DD HH24:MI:SS') AS time
				FROM shielded_expedition.transactions 
				LEFT JOIN shielded_expedition.blocks 
				ON transactions.block_id = blocks.block_id 
				WHERE code_type <> 'none' AND memo = $1
				ORDER BY header_height ASC LIMIT $2;",
				[$publicKey, $maxTransactions]
			);
			$obj = pg_fetch_all($result, PGSQL_ASSOC);
		}
		$earlyTransactionsCache[$publicKey] = $obj;
		return $obj;
	}

	function getTxDescription($tx)
	{
		global $txStrings;
		$description = '';
		if (isset($tx['shielded']))
		{
			$description = 'Shielded ';
		}
		$description .= $txStrings[$tx['code_type']] ?? $tx['code_type'];
		return $description;
	}

	function getTxChar($tx)
	{
		global $txChars;
		if (isset($tx['shielded']))
		{
			if ($tx['code_type'] == 'tx_transfer' || $tx['code_type'] == 'tx_transfert')
			{
				return 'w';
			}
			elseif ($tx['code_type'] == 'tx_ibc')
			{
				return 'x';
			}
		}
		else
		{
			return $txChars[$tx['code_type']];
		}
	}

	function modifyQueryString($parameter, $value)
	{
		$currentQueryString = $_SERVER['QUERY_STRING'];
		$sanitizedQueryString = htmlspecialchars($currentQueryString);
		parse_str($sanitizedQueryString, $queryParams);
		$queryParams[$parameter] = $value;
		$updatedQueryString = http_build_query($queryParams);
		return '?' . $updatedQueryString;
	}

	?>

</body>

</html>