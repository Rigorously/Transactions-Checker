<?php

require_once "DamerauLevenshtein.php";

use Oefenweb\DamerauLevenshtein\DamerauLevenshtein;

require_once "includes567.php";
require_once "similarity_shared.php";

?>

<html>

<head>
	<title>Report: Top Players similarity</title>
	<link rel="stylesheet" href="simple.min.css">
	<link rel="stylesheet" href="similarity.css">
</head>

<body>
	<h2>Report: early transaction type sequence similarity of the top <?= $maxTopPlayers ?></h2>
	<p>The transactions types of the first 20 transactions of each top <?= $maxTopPlayers ?> are compared. This report lists the matches with the least differences. Players whose transactions match the most with another player's transactions are listed at the top. For Pilots this is less of a red flag, as some tasks involved repeating certain transaction types.</p>
	<p>ROIDs and ranking are based on NEBB scoreboard data from April 24th.</p>
	<form action="<?php echo ($filename); ?>" method="get">
		<p><label for="player_type">Player type: </label><select name="player_type" id="player_type">
				<option value="crew" <?= ($playerType == 'Crew') ? 'selected' : '' ?>>Crew Member</option>
				<option value="pilot" <?= ($playerType == 'Pilot') ? 'selected' : '' ?>>Pilot</option>
			</select></p>
		<?php showOffsetControl($offset); ?>
		<?php showTxFilter($txFilter); ?>
		<p><button type="submit">Show</button></p>
		<p>The matching algorithm Damerau–Levenshtein takes into account insertions, deletions, subtitutions and
			transpositions, each having an edit distance cost (denoted as DL from here on). The more differences between
			transactions, the higher the DL. The lower the DL, the more
			similar both transaction sequences are. Transactions that match exactly are in boldface.</p>
	</form>

	<?php
	$playerReports = [];
	$topPlayers = getTopPlayers($dbconn, $playerType, $maxTopPlayers);

	foreach ($topPlayers as $player)
	{
		if ($player)
		{
			$earlyTransactions = getEarlyTransactions($dbconn, $player['public_key'], $maxTransactions, $offset, $txFilter);
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

					$tpTransactions = getEarlyTransactions($dbconn, $tp['public_key'], $maxTransactions, $offset, $txFilter);

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
						. "<div class='txchars'>$thisPlayerTxChars</div><div class='moniker'><a href='similarity.php" . modifyQueryString('identifier', $player['name']) . "'>" . $player['name'] . "</a></div>"
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
					$playerReport['Levenshtein'] = 999;
					$match['matchPercent'] = 100;
					$header = "<tr class='moniker'><td><div class='moniker'><a href='similarity.php" . modifyQueryString('identifier', $player['name']) . "'>" . $player['name'] . "</a></div>"
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