<?php

require_once("db_settings567.php");

$result = pg_query($dbconn, "SELECT to_regclass('shielded_expedition.players')");

if (isset($result) && $result == 'shielded_expedition.players')
{
	print_r($result);
	echo "Table shielded_expedition.players already exists\n";
}
else
{
	$result = pg_query($dbconn, "SELECT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'player_type' AND typtype = 'e')");
	$obj = pg_fetch_object($result);
	if (!$obj?->exists ?? true)
	{
		echo "Adding the player_type enum\n";
		pg_query($dbconn, "CREATE TYPE player_type AS ENUM('Unknown', 'Pilot', 'Crew', 'Team')");
	}

	echo "Creating table shielded_expedition.players\n";
	pg_query($dbconn, 'CREATE TABLE IF NOT EXISTS 
			shielded_expedition.players (
			id serial NOT NULL,
			name character varying(255) NOT NULL,
			address character varying(255) NOT NULL,
			public_key character varying(255) NOT NULL,
			score bigint NOT NULL DEFAULT 0,
			player_type player_type NOT NULL,
			PRIMARY KEY(id),
			UNIQUE(address))'
	);
}

$filename = 'players.csv';
$file = fopen($filename, 'r');

if ($file === false)
{
	die("Error opening file: $filename");
}

while (($row = fgetcsv($file)) !== false)
{
	$row[4] = $row[4] ?? 'Unknown';
	$res = pg_query_params($dbconn, 'INSERT INTO shielded_expedition.players (name, address, public_key, score, player_type) VALUES ($1, $2, $3, $4, $5) ON CONFLICT DO NOTHING', $row);
	if ($res)
	{
		echo "Successfully stored {$row[0]}\n";
	}
	else
	{
		print_r($row);
		die("User must have sent wrong inputs\n");

	}
}
