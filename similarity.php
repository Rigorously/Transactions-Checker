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
	</style>
</head>

<body>
	<?php
	include "includes567.php";
	$filename = strip_all(basename($_SERVER['PHP_SELF']));
	$identifier = strip_all($_GET["identifier"] ?? "");
	$playerType = strtolower(strip_all($_GET["player_type"] ?? "")) == 'pilot' ? 'Pilot' : 'Crew';
	$paramMinMatch = strip_all($_GET["min_match"] ?? 30);
	$minMatchPercent = $paramMinMatch >= 0 && $paramMinMatch <= 100 ? $paramMinMatch : 30;

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

	?>
	<h2>Compare the early transactions of a player with the top 100</h2>
	<form action="<?php echo ($filename); ?>" method="get">
		<p><label for="identifier">Moniker, address or public key:</label><input type="text" name="identifier"
				id="identifier" size="80" value="<?php echo ($identifier); ?>">
		<p><label for="player_type">Player type: </label><select name="player_type" id="player_type">
				<option value="crew" <?= ($playerType == 'Crew') ? 'selected' : '' ?>>Crew Member</option>
				<option value="pilot" <?= ($playerType == 'Pilot') ? 'selected' : '' ?>>Pilot</option>
			</select>
			<label for="minMatchPercentSlider">Match at least: </label><input type="range" min="0" max="100"
				value="<?= $minMatchPercent ?>" class="slider" name="min_match" id="minMatchPercentSlider"> <span
				id="minMatchPercentDisplay"></span>%
		<p><button type="submit">Show</button>
	</form>
	<script>
		var slider = document.getElementById("minMatchPercentSlider");
		var minMatchPercentDisplay = document.getElementById("minMatchPercentDisplay");
		minMatchPercentDisplay.innerHTML = slider.value;

		slider.oninput = function () {
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
			echo "<tr><td>Score</td><td>" . number_format($player['score']) . " as of March 13th</td></tr>\n";
			echo "</table>\n";

			$earlyTransactions = getEarlyTransactions($player['public_key']);
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
					foreach ($earlyTransactions as $ettxid => $et)
					{
						if (!isset($tpTransactions[$ettxid]))
						{
							continue;
						}
						$numTransactions++;
						$class = '';
						$thisPlayerCodeType = getTxDescription($et);
						$thatPlayerCodeType = getTxDescription($tpTransactions[$ettxid]);
						if ($thisPlayerCodeType == $thatPlayerCodeType)
						{
							$matches++;
							$class = 'match';
						}
						$list .= "<tr class='$class'><td>#$ettxid</td><td>" . $thisPlayerCodeType . "</td><td> " . $thatPlayerCodeType . "</td></tr>\n";
					}
					if ($numTransactions < $minTransactions)
					{
						continue;
					}
					$matchPercent = round($matches / $numTransactions * 100);
					if ($matchPercent >= $minMatchPercent)
					{
						$header = "<tr class='moniker'><td>" . $matchPercent . "%</td><td>" . $player['name'] . "</td><td><a href='#' onclick='changeIdentifier(\"" . $tp['name'] . "\")'>" . $tp['name'] . "</a></td></tr>\n";
						$match = [];
						$match['score'] = $matchPercent;
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
		return $a['score'] < $b['score'];
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

	?>

</body>

</html>