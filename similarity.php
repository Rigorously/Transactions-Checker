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
	</style>
</head>

<body>
	<?php
	include "includes567.php";
	$filename = strip_all(basename($_SERVER['PHP_SELF']));
	$identifier = strip_all($_GET["identifier"] ?? "");
	$playerType = strtolower(strip_all($_GET["player_type"] ?? "")) == 'pilot' ? 'Pilot' : 'Crew';
	?>
	<h2>Compare the early transactions of a player with the top 100</h2>
	<form action="<?php echo ($filename); ?>" method="get">
		<label for="identifier">Moniker, address or public key:</label><input type="text" name="identifier" id="identifier" size="80"
			value="<?php echo ($identifier); ?>">
		<label for="player_type">Player type: </label><select name="player_type" id="player_type">
			<option value="crew" <?=($playerType == 'Crew') ? 'selected' : '' ?>>Crew Member</option>
			<option value="pilot" <?=($playerType == 'Pilot') ? 'selected' : '' ?>>Pilot</option>
		</select>
		<button type="submit">Show</button>
	</form>
	<?php

	if ($identifier)
	{
		// TODO Configurable ranges
		$maxTransactions = 20;
		$maxTopPlayers = 100;
		$minMatchPercent = 30;

		$player = getPlayer($identifier, $playerType);

		if ($player)
		{
			$earlyTransactions = getEarlyTransactions($player['public_key']);
			$topPlayers = getTopPlayers($playerType);

			echo "<table><tr><td>Moniker</td><td>" . $player['name'] . "</td></tr>";
			echo "<tr><td>Address</td><td>" . $player['address'] . "</td></tr>";
			echo "<tr><td>Public key</td><td><a href='https://extended-nebb.kintsugi.tech/player/" . $player['public_key'] . "'>" . $player['public_key'] . "</a></td></tr>";
			echo "<tr><td>Score</td><td>" . $player['score'] . "</td></tr>";
			echo "</table>";

			echo "<table>";
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
					$numTransactions++;
					$class = '';
					if ($et['code_type'] == $tpTransactions[$ettxid]['code_type'])
					{
						$matches++;
						$class = 'match';
					}
					$list .= "<tr class='$class'><td>#$ettxid</td><td>" . $et['code_type'] . "</td><td> " . $tpTransactions[$ettxid]['code_type'] . "</td></tr>\n";
				}
				$matchPercent = round($matches / $numTransactions * 100);
				if ($matchPercent > $minMatchPercent)
				{
					echo "<tr class='moniker'><td>" . $matchPercent . "%</td><td>" . $player['name'] . "</td><td>" . $tp['name'] . "</td></tr>\n";
					echo $list;
				}
			}
			echo "</table>";
		}
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
		$result = pg_query_params($dbconn, "SELECT code_type 
			FROM shielded_expedition.early_tx 
			WHERE memo = $1
			ORDER BY header_height ASC LIMIT $2;",
			[$publicKey, $maxTransactions]
		);
		$obj = pg_fetch_all($result, PGSQL_ASSOC);
		return $obj;
	}

	?>

</body>

</html>