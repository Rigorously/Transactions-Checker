<?php
			include "includes567.php";
			
			//dropEarlyTxTable($dbconn);
			//createEarlyTxTable($dbconn);
			truncateEarlyTxTable($dbconn);

			$playerType = "Crew";
			$topPlayers = getTopPlayers($dbconn, $playerType);

			foreach ($topPlayers as $tp)
			{
				populateEarlyTxTable($dbconn, $playerType, $tp['public_key']);
			}

			$playerType = "Pilot";
			$topPlayers = getTopPlayers($dbconn, $playerType);

			foreach ($topPlayers as $tp)
			{
				populateEarlyTxTable($dbconn, $playerType, $tp['public_key']);
			}

			function getTopPlayers($dbconn, $playerType)
			{
				$result = pg_query_params($dbconn,"SELECT name, address, public_key, score 
					FROM shielded_expedition.players 
					WHERE player_type = $1
					ORDER BY score DESC LIMIT 200;",
					[$playerType]
				);
				$obj = pg_fetch_all($result, PGSQL_ASSOC);
				return $obj;
			}

			function populateEarlyTxTable($dbconn, $playerType, $publicKey)
			{
				$result = pg_query_params($dbconn, "INSERT INTO shielded_expedition.early_tx (memo, header_height, header_time, code_type, hash, data)
					SELECT memo, header_height, header_time, code_type, hash, data
					FROM shielded_expedition.transactions 
					LEFT JOIN shielded_expedition.blocks 
					ON transactions.block_id = blocks.block_id 
					WHERE code_type <> 'none' AND code_type <> 'tx_vote_proposal' AND memo = $1 
					ORDER BY header_height ASC LIMIT 20
					ON CONFLICT DO NOTHING;",
					[$publicKey]
				);
				echo "\nInserted $publicKey into early_tx table: " . ($result != false);
			}

			function createEarlyTxTable($dbconn)
			{
				echo "\nCreating early_tx table";
				global $dbconn;
				$result = pg_query($dbconn, 
					"CREATE TABLE IF NOT EXISTS 
					shielded_expedition.early_tx (
						memo bytea NOT NULL, 
						header_height integer NOT NULL,
						header_time text NOT NULL,
						code_type text NOT NULL, 
						hash bytea NOT NULL,
						data json,
						UNIQUE(hash)
					)
				");
			}

			function truncateEarlyTxTable($dbconn)
			{
				echo "\nTruncating early_tx table";
				$result = pg_query($dbconn, "TRUNCATE shielded_expedition.early_tx;");
			}

			function dropEarlyTxTable($dbconn)
			{
				echo "\nDropping early_tx table";
				$result = pg_query($dbconn, "DROP TABLE shielded_expedition.early_tx;");
			}

			function populateTxcharsTable($dbconn, $playerType, $publicKey)
			{
				// TODO populate txchars table
				$result = false;
				echo "\nInserted $publicKey into txchars table: " . ($result != false);
			}

			function createTxcharsTable($dbconn)
			{
				echo "\nCreating txchars table";
				$result = pg_query($dbconn, 
					"CREATE TABLE IF NOT EXISTS 
					shielded_expedition.txchars(
						memo bytea,
						txchars varchar(255
					)
				");
			}

			function truncateTxcharsTable($dbconn)
			{
				echo "\nTruncating txchars table";
				$result = pg_query($dbconn, "TRUNCATE shielded_expedition.txchars;");
			}

			function dropTxcharsTable($dbconn)
			{
				echo "\nDropping txchars table";
				$result = pg_query($dbconn, "DROP TABLE shielded_expedition.txchars;");
			}

			function createIndices()
			{
				// memo
				// unique hash
			}
