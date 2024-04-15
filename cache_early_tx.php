<?php
			include "includes567.php";
			
			$playerType = "Crew";
			$topPlayers = getTopPlayers($playerType);
			
			createEarlyTxTable();
			truncateEarlyTxTable();

			foreach ($topPlayers as $tp)
			{
				populateEarlyTxTable($tp['public_key']);
			}

			function getTopPlayers($playerType)
			{
				global $dbconn;
				$result = pg_query_params($dbconn,"SELECT name, address, public_key, score 
					FROM shielded_expedition.players 
					WHERE player_type = $1
					ORDER BY score DESC LIMIT 100;",
					[$playerType]
				);
				$obj = pg_fetch_all($result, PGSQL_ASSOC);
				return $obj;
			}

			function populateEarlyTxTable($publicKey)
			{
				global $dbconn;
				$result = pg_query_params($dbconn, "INSERT INTO shielded_expedition.early_tx (memo, header_height, code_type, hash)
					SELECT memo, header_height, code_type, hash
					FROM shielded_expedition.transactions 
					LEFT JOIN shielded_expedition.blocks 
					ON transactions.block_id = blocks.block_id 
					WHERE code_type <> 'none' AND memo = $1
					ORDER BY header_height ASC LIMIT 20;",
					[$publicKey]
				);
				echo "\nInserted $publicKey into early_tx table: " . ($result != false);
			}

			function createEarlyTxTable()
			{
				echo "\nCreating early_tx table";
				global $dbconn;
				$result = pg_query($dbconn, 
					"CREATE TABLE IF NOT EXISTS 
					shielded_expedition.early_tx (
						memo bytea NOT NULL, 
						header_height integer NOT NULL, 
						code_type text NOT NULL, 
						hash bytea NOT NULL
					)
				");
			}

			function truncateEarlyTxTable()
			{
				echo "\nTruncating early_tx table";
				global $dbconn;
				$result = pg_query($dbconn, "TRUNCATE shielded_expedition.early_tx;");
			}

			function createIndices()
			{
				// memo
				// unique hash
			}
