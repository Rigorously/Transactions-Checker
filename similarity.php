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

		.txchars {
			font-size: small;
			display: block;
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
	<h2>Compare the early transactions of a player with the top 100</h2>
	<form action="<?php echo ($filename); ?>" method="get">
		<p><label for="identifier">Moniker, address or public key:</label><input type="text" name="identifier"
				id="identifier" size="80" value="<?php echo ($identifier); ?>">
		<p><label for="player_type">Player type: </label><select name="player_type" id="player_type">
				<option value="crew" <?= ($playerType == 'Crew') ? 'selected' : '' ?>>Crew Member</option>
				<option value="pilot" <?= ($playerType == 'Pilot') ? 'selected' : '' ?>>Pilot</option>
			</select>
			<label for="maxLevenshteinSlider">Highest Damerau-Levenshtein distance: </label><input type="range" min="0"
				max="60" value="<?= $maxLevenshtein ?>" class="slider" name="max_levenshtein" id="maxLevenshteinSlider">
			<span id="maxLevenshteinDisplay"></span>
			<label for="minMatchPercentSlider">Exactly matching transactions at least: </label><input type="range" min="0" max="100"
				value="<?= $minMatchPercent ?>" class="slider" name="min_match" id="minMatchPercentSlider"> <span
				id="minMatchPercentDisplay"></span>%
		<p><button type="submit">Show</button>
	</form>
	<script>
		var maxLevenshteinSlider = document.getElementById("maxLevenshteinSlider");
		var maxLevenshteinDisplay = document.getElementById("maxLevenshteinDisplay");
		maxLevenshteinDisplay.innerHTML = maxLevenshteinSlider.value;
		maxLevenshteinSlider.oninput = function () {
			maxLevenshteinDisplay.innerHTML = this.value;
		}

		var slider = document.getElementById("minMatchPercentSlider");
		var minMatchPercentDisplay = document.getElementById("minMatchPercentDisplay");
		minMatchPercentDisplay.innerHTML = minMatchPercentSlider.value;
		minMatchPercentSlider.oninput = function () {
			minMatchPercentDisplay.innerHTML = this.value;
		}

		function changeParameter(parameter, value) {
			const currentUrl = window.location.href;
			const url = new URL(currentUrl);
			const params = url.searchParams;
			params.set(parameter, value);
			const updatedUrl = url.href;
			window.location.href = updatedUrl;
		}

		function changeIdentifier(identifier) {
			changeParameter('identifier', identifier);
		}

		function changePlayerType(playerType) {
			changeParameter('player_type', playerType);
		}
	</script>
	<?php

	if ($identifier)
	{
		// TODO Configurable ranges
		$minTransactions = 5;
		$maxTransactions = 20;
		$maxTopPlayers = 100;


		$player = getPlayer($identifier, $playerType);

		if ($player)
		{
			echo "<table><tr><td>Moniker</td><td>" . $player['name'] . "</td></tr>\n";
			echo "<tr><td>Address</td><td>" . $player['address'] . "</td></tr>\n";
			echo "<tr><td>Public key</td><td><a href='https://extended-nebb.kintsugi.tech/player/" . $player['public_key'] . "'>" . $player['public_key'] . "</a></td></tr>\n";
			$scoreboardTime = $playerType == 'Crew' ? "April 16th" : "March 13th";
			echo "<tr><td>Score</td><td>" . number_format($player['score']) . " as of $scoreboardTime</td></tr>\n";
			echo "</table>\n";

			$earlyTransactions = getEarlyTransactions($player['public_key']);
			$thisPlayerCodeTypes = [];
			$thisPlayerTxChars = '';
			foreach ($earlyTransactions as $ettxid => $et)
			{
				$thisPlayerCodeTypes[$ettxid] = getTxDescription($et);
				$thisPlayerTxChars .= getTxChar($et);
			}

			if (count($earlyTransactions) == 0)
			{
				echo "<p>Player has no transactions</p>";
			}
			else
			{
				$topPlayers = getTopPlayers($playerType);

				$matchPool = [];
				foreach ($topPlayers as $tp)
				{
					if ($tp['public_key'] == $player['public_key'])
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
							continue;
						}
						$numTransactions++;
						$class = '';
						$thatPlayerCodeType = getTxDescription($tpTransactions[$ettxid]);
						$thatPlayerTxChars .= getTxChar($tpTransactions[$ettxid]);
						if ($thisPlayerCodeTypes[$ettxid] == $thatPlayerCodeType)
						{
							$matches++;
							$class = "class='match'";
						}
						$list .= "<tr $class><td>#$ettxid</td><td>" . $thisPlayerCodeTypes[$ettxid] . "</td><td> " . $thatPlayerCodeType . "</td></tr>\n";
					}
					if ($numTransactions < $minTransactions)
					{
						continue;
					}
					$matchPercent = round($matches / $numTransactions * 100);
					//$levenshtein = levenshtein($thisPlayerTxChars, $thatPlayerTxChars, 1, 2, 1);
					$damerauLevenshtein = new DamerauLevenshtein($thisPlayerTxChars, $thatPlayerTxChars, 2, 2, 1, 1);
					$levenshtein = $damerauLevenshtein->getSimilarity();
					if ($levenshtein <= $maxLevenshtein && $matchPercent >= $minMatchPercent)
					{
						$header = "<tr class='moniker'><td>DL" . $levenshtein . "<br>" . $matchPercent . "%</td><td>"
							. "<span class='txchars'>$thisPlayerTxChars</span>" . $player['name'] . "</td><td>"
							. "<span class='txchars'>$thatPlayerTxChars</span>" . "<a href='#' onclick='changeIdentifier(\"" . $tp['name'] . "\")'>" . $tp['name'] . "</a>" . "</td></tr>\n";
						$match = [];
						$match['matchPercent'] = $matchPercent;
						$match['Levenshtein'] = $levenshtein;
						$match['table'] = $header . $list;
						$matchPool[] = $match;
					}
				}

				usort($matchPool, "matchSort");
				foreach ($matchPool as $m)
				{
					echo "<table>";
					echo $m['table'];
					echo "</table>";
				}
			}
		}
		else
		{
			$playerType = $playerType == 'Crew' ? 'Pilot' : 'Crew';
			echo "<p>Player not found in database. Try <a href='#' onclick='changePlayerType(\"" . $playerType . "\")'>" . $playerType . "</a> database.</p>";
		}
	}

	function matchSort($a, $b)
	{
		return $a['Levenshtein'] > $b['Levenshtein'];
		//return $a['matchPercent'] < $b['matchPercent'];
	}

	function getPlayer($identifier, $playerType)
	{
		global $dbconn;
		$result = pg_query_params($dbconn, "SELECT address, name, public_key, score 
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
		$result = pg_query_params($dbconn, "SELECT name, address, public_key, score 
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
		global $dbconn, $maxTransactions;
		$result = pg_query_params($dbconn, "SELECT code_type, data->>'shielded' AS shielded
			FROM shielded_expedition.early_tx 
			WHERE memo = $1
			ORDER BY header_height ASC LIMIT $2;",
			[$publicKey, $maxTransactions]
		);
		$obj = pg_fetch_all($result, PGSQL_ASSOC);
		if (count($obj) == 0)
		{
			$result = pg_query_params($dbconn, "SELECT code_type, data->>'shielded' AS shielded
				FROM shielded_expedition.transactions 
				LEFT JOIN shielded_expedition.blocks 
				ON transactions.block_id = blocks.block_id 
				WHERE code_type <> 'none' AND memo = $1
				ORDER BY header_height ASC LIMIT $2;",
				[$publicKey, $maxTransactions]
			);
			$obj = pg_fetch_all($result, PGSQL_ASSOC);
		}
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

	?>

</body>

</html>