<?php

require_once("db_settings567.php");

$result = pg_query($dbconn, "SELECT to_regclass('shielded_expedition.players')");
$obj = pg_fetch_object($result);
if ($obj?->to_regclass == 'shielded_expedition.players')
{
	echo "Table shielded_expedition.players already exists\n";
}
else
{
	$result = pg_query($dbconn, "SELECT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'player_type' AND typtype = 'e')");
	$obj = pg_fetch_object($result);

	if ($obj->exists !== true)
	{
		echo "Adding the player_type enum\n";
		pg_query($dbconn, "CREATE TYPE player_type AS ENUM('Unknown', 'Pilot', 'Crew', 'Team', 'Validator')");
	}
	else
	{
		$result = pg_query($dbconn, "SELECT COUNT(e.enumlabel) FROM pg_enum e JOIN pg_type t ON e.enumtypid = t.oid WHERE t.typname = 'player_type' AND enumlabel = 'Validator';");
		if ($result)
		{
			$obj = pg_fetch_object($result);
			if ($obj->count)
			{
				echo "Adding Validator to existing player_type enum\n";
				pg_query($dbconn, "ALTER TYPE player_type ADD VALUE 'Validator';");
			}
		}
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

$result = pg_query($dbconn, "SELECT to_regclass('shielded_expedition.validators')");
$obj = pg_fetch_object($result);
if (isset($obj) && $obj->to_regclass == 'shielded_expedition.validators')
{
	echo "Table shielded_expedition.validators already exists\n";
}
else
{
	echo "Creating table shielded_expedition.validators\n";
	pg_query($dbconn, 'CREATE TABLE IF NOT EXISTS 
			shielded_expedition.validators (
				id serial NOT NULL,
				name character varying(255) NOT NULL,
				address character varying(255) NOT NULL,
				genesis boolean NOT NULL,
				voting_power bigint NOT NULL,
				email character varying(255) NULL,
				description character varying(255) NULL,
				website character varying(255) NULL,
				discord character varying(255) NULL,
				avatar character varying(255) NULL,
				rate numeric NOT NULL,
				max_change numeric NOT NULL,
				state smallint NOT NULL,
				PRIMARY KEY(id),
				UNIQUE(address))'
	);
}

$filename = 'validators.json';
$json = file_get_contents($filename);


if ($json === false)
{
	die("Error opening file: $filename");
}

$jsonObj = json_decode($json, false);
$validators = $jsonObj->validatorsDigest->valsAllData;
foreach ($validators as $val)
{
	$row = [];
	$row[1] = $val?->moniker;
	$row[2] = $val?->tnam_addr;
	$row[3] = $val?->genesis ? 'true' : 'false';
	$row[4] = $val?->vp;
	$row[5] = substr($val?->email, 0, 255);
	$row[6] = substr($val?->description, 0, 255);
	$row[7] = substr($val?->website, 0, 255);
	$row[8] = substr($val?->discord, 0, 255);
	$row[9] = substr($val?->avatar, 0, 255);
	$row[10] = substr($val?->rate, 0, 255);
	$row[11] = substr($val?->max_change, 0, 255);
	$row[12] = substr($val?->state, 0, 255);

	$res = pg_query_params($dbconn, 'INSERT INTO shielded_expedition.validators 
		(name, address, genesis, voting_power, email, description, website, discord, avatar, rate, max_change, state)
	 	VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12) ON CONFLICT DO NOTHING', $row);
	if ($res)
	{
		//echo "Successfully stored {$row[2]}\n";
	}
	else
	{
		print_r($row);
		die("User must have sent wrong inputs\n");

	}
}
