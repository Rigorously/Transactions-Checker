<?php

require_once "db_settings567.php";

echo "Creating index for blocks.block_id\n";
pg_query(
	$dbconn,
	'CREATE INDEX IF NOT EXISTS block_id_1714224528914_index ON shielded_expedition.blocks USING btree ("block_id");'
);

echo "Creating index for transactions.block_id\n";
pg_query(
	$dbconn,
	'CREATE INDEX IF NOT EXISTS block_id_1714224602222_index ON shielded_expedition.transactions USING btree ("block_id");'
);

echo "Creating index for transactions.code_type\n";
pg_query(
	$dbconn,
	'CREATE INDEX IF NOT EXISTS code_type_1714224633012_index ON shielded_expedition.transactions USING btree ("code_type");'
);

echo "Creating index for transactions.memo\n";
pg_query(
	$dbconn,
	'CREATE INDEX IF NOT EXISTS memo_1714224664911_index ON shielded_expedition.transactions USING hash ("memo");'
);

echo "Creating index for players.name\n";
pg_query(
	$dbconn,
	'CREATE INDEX IF NOT EXISTS players_name_index ON shielded_expedition.players USING btree ("name");'
);