<?php

$sessionCache = [];

function getEarlyTransactions($dbconn, $publicKey, $maxTransactions, $useDatabaseCache = false)
{
	global $sessionCache;

	if (isset($sessionCache[$publicKey]))
	{
		return $sessionCache[$publicKey];
	}

	if ($useDatabaseCache)
	{
		$result = pg_query_params(
			$dbconn,
			"SELECT code_type, data->>'shielded' AS shielded, header_height, TO_CHAR(header_time::timestamp, 'YYYY-MM-DD HH24:MI:SS') AS time
			FROM shielded_expedition.early_tx 
			WHERE memo = $1 AND code_type <> 'tx_vote_proposal' AND code_type <> 'tx_init_account' 
			ORDER BY header_height ASC LIMIT $2;",
			[$publicKey, $maxTransactions]
		);
		$obj = pg_fetch_all($result, PGSQL_ASSOC);
	}

	if (!$useDatabaseCache || count($obj) == 0)
	{
		$result = pg_query_params(
			$dbconn,
			"SELECT code_type, data->>'shielded' AS shielded, header_height, TO_CHAR(header_time::timestamp, 'YYYY-MM-DD HH24:MI:SS') AS time
			FROM shielded_expedition.transactions 
			LEFT JOIN shielded_expedition.blocks 
			ON transactions.block_id = blocks.block_id 
			WHERE code_type <> 'none' AND memo = $1 AND code_type <> 'tx_vote_proposal' AND code_type <> 'tx_init_account' 
			ORDER BY header_height ASC LIMIT $2;",
			[$publicKey, $maxTransactions]
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