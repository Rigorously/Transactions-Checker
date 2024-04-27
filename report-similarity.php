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

	require_once "DamerauLevenshtein.php";

	use Oefenweb\DamerauLevenshtein\DamerauLevenshtein;

	require_once "includes567.php";
	require_once "txchars.php";
	require_once "similarity_shared.php";

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
	$topPlayers = getTopPlayers($dbconn, $playerType, $maxTopPlayers);

	foreach ($topPlayers as $player)
	{
		if ($player)
		{
			$earlyTransactions = getEarlyTransactions($dbconn, $player['public_key'], $maxTransactions);
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

					$tpTransactions = getEarlyTransactions($dbconn, $tp['public_key'], $maxTransactions);

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




	?>

</body>

</html>