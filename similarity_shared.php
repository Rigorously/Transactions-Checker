<?php

require_once "txchars.php";

// Config

// TODO Configurable ranges
$minTransactions = 5;
$maxTransactions = 20;
$maxTopPlayers = 200;

// Damerau–Levenshtein distance cost
$insCost = 1;
$delCost = 1;
$subCost = 1;
$transCost = 1;

// Input
$filename = strip_all(basename($_SERVER['PHP_SELF']));
$identifier = strip_all($_GET["identifier"] ?? "");
$playerType = strtolower(strip_all($_GET["player_type"] ?? "")) == 'pilot' ? 'Pilot' : 'Crew';

$defaultMinMatch = 30;
$paramMinMatch = strip_all($_GET["min_match"] ?? $defaultMinMatch);
$minMatchPercent = $paramMinMatch >= 0 && $paramMinMatch <= 100 ? $paramMinMatch : $defaultMinMatch;

$defaultMaxLevenshtein = 20;
$paramMaxLevenshtein = strip_all($_GET["max_levenshtein"] ?? $defaultMaxLevenshtein);
$maxLevenshtein = $paramMaxLevenshtein >= 0 && $paramMaxLevenshtein <= 100 ? $paramMaxLevenshtein : $defaultMaxLevenshtein;

$defaultOffset = 0;
$paramOffset = strip_all($_GET["offset"] ?? $defaultOffset);
$offset = $paramOffset >= 0 && $paramOffset <= 1000 ? $paramOffset : $defaultOffset;

$txFilter = [];
if (isset($_GET["tx_filter"]) && is_array($_GET["tx_filter"]))
{
	$txFilter = $_GET["tx_filter"];
}
else
{
	$paramTxFilter = strtolower(strip_all($_GET["tx_filter"] ?? ""));
	foreach (str_split($paramTxFilter) as $c)
	{
		$key = array_search($c, $txChars);
		if ($key)
		{
			$txFilter[] = $key;
		}
	}
}

function showOffsetControl($offset)
{
	echo '<label for="OffsetSlider">Offset: </label><input type="range" min="0" max="1000" value="'. $offset . '" class="slider" name="offset" id="offsetSlider"> <span id="offsetDisplay"></span> transactions';
	echo '<script>
		var offsetSlider = document.getElementById("offsetSlider");
		var offsetDisplay = document.getElementById("offsetDisplay");
		offsetDisplay.innerHTML = offsetSlider.value;
		offsetSlider.oninput = function() {
			offsetDisplay.innerHTML = this.value;
		}
		</script>';
}

function showTxFilter($txFilter)
{
	global $txStrings, $txChars;

	if (!empty($txFilter))
	{
		$txCharStr = "- ";
		foreach ($txFilter as $txType)
		{
			$txCharStr .= $txChars[$txType];
		}
	}

	echo "<button class='accordion'>Transaction Type Filter $txCharStr</button>\n
	<div class='panel'>\n
	<p id='tx_filter'>\n";

	foreach ($txStrings as $tx => $desc)
	{
		$char = $txChars[$tx];
		$checked = '';
		if (in_array($tx, $txFilter))
		{
			$checked = 'checked';
		}
		echo "<input type='checkbox' id='{$tx}' name='tx_filter[]' value='{$tx}' $checked><label for='{$tx}'>{$char} - {$desc}</label><br>\n";
	}
	echo "</p></div>\n";
	echo '
	<script>
		var acc = document.getElementsByClassName("accordion");
		var i;

		for (i = 0; i < acc.length; i++) 
		{
			acc[i].addEventListener("click", function() 
			{
				event.preventDefault();
				this.classList.toggle("active");
				var panel = this.nextElementSibling;
				if (panel.style.maxHeight) {
					panel.style.maxHeight = null;
				} else {
					panel.style.maxHeight = panel.scrollHeight + "px";
				}
			});
		}
	</script>';
}

$sessionCache = [];
$useDatabaseCache = false;

function getEarlyTransactions($dbconn, string $publicKey, int $maxTransactions, int $offset, array $txFilter)
{
	global $sessionCache, $useDatabaseCache;

	if (isset($sessionCache[$publicKey]))
	{
		return $sessionCache[$publicKey];
	}

	$txFilter = array_map(function ($tx)
	{
		return "'" . $tx . "'";
	}, $txFilter);

	if ($useDatabaseCache)
	{
		$query = "SELECT code_type, data->>'shielded' AS shielded, header_height, TO_CHAR(header_time::timestamp, 'YYYY-MM-DD HH24:MI:SS') AS time
			FROM shielded_expedition.early_tx 
			WHERE memo = $1 ";
		empty($txFilter) ? "" : $query .= " AND code_type NOT IN (" . implode(',', $txFilter) . ") ";
		$query .= "ORDER BY header_height ASC LIMIT $2 OFFSET $3;";

		$result = pg_query_params(
			$dbconn,
			$query,
			[$publicKey, $maxTransactions, $offset]
		);
		$obj = pg_fetch_all($result, PGSQL_ASSOC);
	}

	if (!$useDatabaseCache || count($obj) == 0)
	{
		$query = "SELECT code_type, data->>'shielded' AS shielded, header_height, TO_CHAR(header_time::timestamp, 'YYYY-MM-DD HH24:MI:SS') AS time
		FROM shielded_expedition.transactions 
		LEFT JOIN shielded_expedition.blocks 
		ON transactions.block_id = blocks.block_id 
		WHERE code_type <> 'none' AND memo = $1 ";
		empty($txFilter) ? "" : $query .= " AND code_type NOT IN (" . implode(',', $txFilter) . ") ";
		$query .= "ORDER BY header_height ASC LIMIT $2 OFFSET $3;";

		$result = pg_query_params(
			$dbconn,
			$query,
			[$publicKey, $maxTransactions, $offset]
		);
		$obj = pg_fetch_all($result, PGSQL_ASSOC);
	}

	$sessionCache[$publicKey] = $obj;

	return $obj;
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

function matchSort($a, $b)
{
	return $a['Levenshtein'] > $b['Levenshtein'];
	//return $a['matchPercent'] < $b['matchPercent'];
}

function getTopPlayers($dbconn, $playerType, $maxTopPlayers)
{
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
	$sanitizedQueryString = filter_var($currentQueryString, FILTER_SANITIZE_STRING);
	parse_str($sanitizedQueryString, $queryParams);
	$queryParams[$parameter] = $value;
	$updatedQueryString = http_build_query($queryParams);
	return '?' . $updatedQueryString;
}