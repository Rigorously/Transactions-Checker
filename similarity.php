<?php

require_once "DamerauLevenshtein.php";

use Oefenweb\DamerauLevenshtein\DamerauLevenshtein;

require_once "includes567.php";
require_once "similarity_shared.php";

if ($identifier)
{
	$player = getPlayer($identifier, $playerType);
}

if ($otherIdentifier)
{
	$otherPlayer = getPlayer($otherIdentifier, $otherPlayerType);
}

?>

<html>

<head>
	<title>Similarity <?= isset($player['name']) ? 'for ' . $player['name'] : 'Tool' ?></title>
	<link rel="stylesheet" href="simple.min.css">
	<link rel="stylesheet" href="similarity.css">
</head>

<body>

	<?php 
	if (isset($player) && is_array($player))
	{
		if (isset($otherPlayer) && is_array($otherPlayer))
		{
			echo "<h2>Compare {$player['name']} with {$otherPlayer['name']}</h2>";
		}
		else
		{
			echo "<h2>Compare {$player['name']} with the top $maxTopPlayers</h2>";
		}
	}
	else
	{
		echo "<h2>Compare a Player with the top $maxTopPlayers Players</h2>";
	}
	?>
	<span><a href="report-similarity.php">Similarity Report of top <?= $maxTopPlayers ?></a> | <a href="similarity.php">Ranking</a></span>
	<form action="<?php echo ($filename); ?>" method="get">
		<input type="submit" style="border:0; padding:0; font-size:0">
		<p><label for="identifier">Moniker, address or public key:</label><input type="text" name="identifier" id="identifier" size="80" value="<?php echo ($identifier); ?>">
		<p><label for="player_type">Player type: </label><select name="player_type" id="player_type">
				<option value="crew" <?= ($playerType == 'Crew') ? 'selected' : '' ?>>Crew Member</option>
				<option value="pilot" <?= ($playerType == 'Pilot') ? 'selected' : '' ?>>Pilot</option>
			</select>
		<hr>
		<p><label for="other_identifier">Other Player (leave empty to compare with the top <?= $maxTopPlayers ?>):</label><input type="text" name="other_identifier" id="other_identifier" size="80" value="<?php echo ($otherIdentifier); ?>">
		<p><label for="other_player_type">Other Player type: </label><select name="other_player_type" id="other_player_type">
				<option value="crew" <?= ($otherPlayerType == 'Crew') ? 'selected' : '' ?>>Crew Member</option>
				<option value="pilot" <?= ($otherPlayerType == 'Pilot') ? 'selected' : '' ?>>Pilot</option>
			</select>
		<hr>
			<label for="maxLevenshteinSlider">Highest Damerau-Levenshtein distance: </label><input type="range" min="0" max="60" value="<?= $maxLevenshtein ?>" class="slider" name="max_levenshtein" id="maxLevenshteinSlider">
			<span id="maxLevenshteinDisplay"></span>
		<p><label>Damerau-Levenshtein edit distance cost:</label>Insertion: <?= $insCost ?> Deletion: <?= $delCost ?>
			Substitution:
			<?= $subCost ?> Transposition: <?= $transCost ?>
		</p>
		<label for="minMatchPercentSlider">Exactly matching transactions at least: </label><input type="range" min="0" max="100" value="<?= $minMatchPercent ?>" class="slider" name="min_match" id="minMatchPercentSlider"> <span id="minMatchPercentDisplay"></span>%
		<hr>
		<?php showOffsetControl($offset); ?>
		<?php showTxFilter($txFilter); ?>
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

		var minMatchPercentSlider = document.getElementById("minMatchPercentSlider");
		var minMatchPercentDisplay = document.getElementById("minMatchPercentDisplay");
		minMatchPercentDisplay.innerHTML = minMatchPercentSlider.value;
		minMatchPercentSlider.oninput = function() {
			minMatchPercentDisplay.innerHTML = this.value;
		}
	</script>
	<?php

	if ($identifier)
	{
		if ($player)
		{
			echo "<table class='col-15'><tr><td>Moniker</td><td>" . $player['name'] . "</td></tr>\n";
			echo "<tr><td>Rank</td><td>" . $player['rank'] . "</td></tr>\n";
			echo "<tr><td>Address</td><td>" . $player['address'] . "</td></tr>\n";
			echo "<tr><td>Public key</td><td><a class='external' href='https://extended-nebb.kintsugi.tech/player/" . $player['public_key'] . "'>" . $player['public_key'] . "</a></td></tr>\n";
			//$scoreboardTime = $playerType == 'Crew' ? "April 16th" : "March 13th";
			$scoreboardTime = "April 24th";
			echo "<tr><td>ROIDs</td><td>" . number_format($player['score']) . " as of $scoreboardTime</td></tr>\n";
			echo "<tr><td>Transfers</td><td><a class='external' href='transactions.php?address=" . $player['address'] . "&mode=transactions'>" . $player['name'] . "</a></td></tr>\n";
			echo "</table>\n";
			
			echo "<form>";
			$arr = getBecomeValidator($dbconn, $player['public_key']);
			echo "<button class='accordion'>become_validator</button><div class='panel'>\n";
			echo "<table><tr><td>#</td><td>address</td><td>email</td><td>description</td><td>website</td><td>discord_handle</td><td>avatar</td></tr>\n";
			foreach ($arr as $aid => $a)
			{
				echo "<tr><td>$aid</td><td>{$a['address']}</td><td>{$a['email']}</td><td>{$a['description']}</td><td>{$a['website']}</td><td>{$a['discord_handle']}</td><td>{$a['avatar']}</td></tr>\n";
			}
			echo "</table></div>";

			$arr = getChangeValidatorMetadata($dbconn, $player['public_key']);
			echo "<button class='accordion'>change_validator_metadata</button><div class='panel'>\n";
			echo "<table><tr><td>#</td><td>validator</td><td>email</td><td>description</td><td>website</td><td>discord_handle</td><td>avatar</td></tr>\n";
			foreach ($arr as $aid => $a)
			{
				echo "<tr><td>$aid</td><td>{$a['validator']}</td><td>{$a['email']}</td><td>{$a['description']}</td><td>{$a['website']}</td><td>{$a['discord_handle']}</td><td>{$a['avatar']}</td></tr>\n";
			}
			echo "</table></div>";
			echo "</form>";

			$earlyTransactions = getEarlyTransactions($dbconn, $player['public_key'], $maxTransactions, $offset, $txFilter);
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
				if (isset($otherPlayer))
				{
					$topPlayers = [];
					$topPlayers[] = $otherPlayer;
				}
				else
				{
					$topPlayers = getTopPlayers($dbconn, $playerType, $maxTopPlayers);
				}

				$matchPool = [];
				foreach ($topPlayers as $tp)
				{
					if ($tp['public_key'] == $player['public_key'])
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
							continue;
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
						$list .= "<tr><td>#". $ettxid + $offset . "</td><td><div class='code_type $typeMatchClass'>" . $thisPlayerCodeTypes[$ettxid] . "</div><div class='block $blockMatchClass'>" . $et['header_height'] . " | " . $et['time'] . "</div>"
							. "</td><td><div class='code_type $typeMatchClass'>" . $thatPlayerCodeType . "</div><div class='block $blockMatchClass'>" . $tpTransactions[$ettxid]['header_height'] . " | " . $tpTransactions[$ettxid]['time'] . "</div></td></tr>\n";
					}
					if ($numTransactions < $minTransactions)
					{
						continue;
					}
					$matchPercent = round($matches / $numTransactions * 100);
					$levenshtein = levenshtein($thisPlayerTxChars, $thatPlayerTxChars, $insCost, $subCost, $delCost);
					$damerauLevenshtein = new DamerauLevenshtein($thisPlayerTxChars, $thatPlayerTxChars, $insCost, $delCost, $subCost, $transCost);
					$levenshtein = $damerauLevenshtein->getSimilarity();
					if ($levenshtein <= $maxLevenshtein && $matchPercent >= $minMatchPercent)
					{
						$roidsMatchClass = '';
						if ($player['score'] == $tp['score'])
						{
							$roidsMatchClass = 'match';
						}
						$header = "<tr class='moniker'><td><div class='levenshtein'>DL" . $levenshtein . "</div><div class='matchPercentage'>EM " . $matchPercent . "%</div></td><td>"
							. "<div class='txchars'>$thisPlayerTxChars</div><div class='moniker'>" . $player['name'] . "</div>"
							. "<div class='small'>#" . $player['rank'] . " <span class='score $roidsMatchClass'>ROIDs: " . number_format($player['score']) . "</span></div></td><td>"
							. "<div class='txchars'>$thatPlayerTxChars</div><div class='moniker'>" . "<a href='" . modifyQueryString('identifier', $tp['name']) . "'>" . $tp['name'] . "</a></div>"
							. "<div class='small'>#" . $tp['rank'] . " <span class='score $roidsMatchClass'>ROIDs: " . number_format($tp['score']) . "</span></div></td></tr>\n";
						$match = [];
						$match['matchPercent'] = $matchPercent;
						$match['Levenshtein'] = $levenshtein;
						$match['table'] = $header . $list;
						$matchPool[] = $match;
					}
				}

				if (count($matchPool) == 0)
				{
					echo "<p>No matches with the given parameters. Try increasing the maximum Damerau-Levenshtein distance or lowering the minimum match percentage.</p>";
				}
				else
				{
					usort($matchPool, "matchSort");
					foreach ($matchPool as $m)
					{
						echo "<table>";
						echo $m['table'];
						echo "</table>";
					}
				}
			}
		}
		else
		{
			$playerType = $playerType == 'Crew' ? 'Pilot' : 'Crew';
			echo "<p>Player not found in database. Try <a href='" . modifyQueryString('player_type', $playerType) . "'>" . $playerType . "</a> database.</p>";
		}
	}
	else
	{
		$topPlayers = getTopPlayers($dbconn, $playerType, 1000);
		echo "<table class='topPlayers'>";
		echo "<tr class='moniker'><td><div class='rank'>Rank</div></td><td>Moniker</td><td>ROIDs</td></tr>";
		foreach ($topPlayers as $tp)
		{
			echo "<tr class='moniker'><td><div class='rank'>#" . $tp['rank'] . "</div></td>"
				. "<td><div class='moniker'><a href='" . modifyQueryString('identifier', $tp['name']) . "'>" . $tp['name'] . "</a></div></td>"
				. "<td>" . number_format($tp['score']) . "</td></tr>";
		}
		echo "</table>";
	}

	?>

</body>

</html>