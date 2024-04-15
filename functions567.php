<?php


function strip_all($in) {
  $in = trim($in);
  $in = stripslashes($in);
  $in = htmlspecialchars($in);
  return $in;
}

function validate_tnam1($in) {
  if (empty($in)) {
    return false;
  }    
  strip_all($in);	
  if (strlen($in) <> 45) {return false;}	
  $reg_tnam1 = '/tnam1[a-zA-Z0-9]{40}$/i';	
  return preg_match($reg_tnam1, $in);
}

function validate_tpknam1($in) {
  if (empty($in)) {
    return false;
  }    
  strip_all($in);	
  if (strlen($in) <> 66) {return false;}	
  $reg_tpknam1 = '/tpknam1[a-zA-Z0-9]{59}$/i';	
  return preg_match($reg_tpknam1, $in);
}

function validate_integer($in) {
  if (empty($in)) {
    return false;
  }    
  strip_all($in);
  if (strlen($in) > 25) {return false;}
  $reg_int = '/\d+$/i';
  return preg_match($reg_int, $in);
}

function validate_proposal_id($in) {
  return validate_integer($in);
}

function validate_double($in) {
  if (empty($in)) {return false;}	
  $in = strip_all($in);
  if (strlen($in) > 25) {return false;}
  $reg_double = '/^\d*\.?\d+$/i';
  return preg_match($reg_double, $in);
}

function hyperlink_address($in, $mode_arg) {
  $in = strip_all($in);
  $mode_arg = strip_all($mode_arg);
  if (validate_tnam1($in) == false) {
    die('wrong internal parameter call');
  }
  $uri = strip_all($_SERVER['REQUEST_URI']);
  $parts = explode('?', $uri);
  $uri = array_shift($parts);
  $servername = strip_all($_SERVER['HTTP_HOST']);
  $player = getPlayerFromAddress($in);
  $newlink = '//' . $servername . $uri . '?address=' . $in . '&mode=' . $mode_arg;
  $returnstring = '<a href="' . $newlink . '" class="' . $player->playerType . '">' . $player->name . '</a>';
  return $returnstring;
}

function hyperlink_proposal($in, $mode_arg) {
  $uri = strip_all($_SERVER['REQUEST_URI']);
  $parts = explode('?', $uri);
  $uri = array_shift($parts);
  $servername = strip_all($_SERVER['HTTP_HOST']);
  $newlink = '//' . $servername . $uri . '?proposal_id=' . $in;
  $returnstring = '<a href="' . $newlink . '">' . $in . '</a>';
  return $returnstring;
}

class Player {
  public $name;
  public $address;
  public $publicKey;
  public $score;
  public $playerType = "Unknown";
  public $isValidator = false;
  public $isGenesis = false;
}

function loadPlayerData() {
  global $addressToName;
  global $addressToPlayer;

  $filename = 'players.csv';
  $file = fopen($filename, 'r');

  if ($file === false) {
    die("Error opening file: $filename");
  }

  // Read each line from the CSV file
  while (($row = fgetcsv($file)) !== false) {
    // Assuming the CSV has two columns: address and name
    $name = $row[0];
    $address = $row[1];
    $publicKey = $row[2];
    $score = $row[3];
    $playerType = $row[4];

    // Add the address and name to the hash map
    $addressToName[$address] = $name;

    // Create a new Player object
    $player = new Player();
    $player->name = $name;
    $player->address = $address;
    $player->publicKey = $publicKey;
    $player->score = $score;
    $player->playerType = $playerType;

    // Add Player object to lookup table
    $addressToPlayer[$address] = $player;
  }

  // Close the file
  fclose($file);
}

// Returns the moniker associated with the specified Namada address, or returns the address if not found
function getNameFromAddress($address) {
  global $addressToName;

  if (isset($addressToName[$address])) {
    return $addressToName[$address];
  } else {
    return $address;
  }
}

// Returns a Player object associated with the specified Namada address, or creates and returns a new Player object if not found
function getPlayerFromAddress($address) {
  global $addressToPlayer;

  loadPlayerFromDatabase($address);

  return $addressToPlayer[$address];
}

function loadPlayerFromDatabase($address) {
  global $addressToPlayer;

  if (!isset($addressToPlayer[$address])) {
    $is_public_key = validate_tpknam1($address);
    $result = null;
    if ($is_public_key) {
      $result = pg_query_params('SELECT * FROM shielded_expedition.players WHERE public_key = $1 LIMIT 1', [$address]);  
    } else {
      $result = pg_query_params('SELECT * FROM shielded_expedition.players WHERE address = $1 LIMIT 1', [$address]);
    }
    if ($result)
    {
      $obj = pg_fetch_object($result);
      $player = new Player();
      $player->address = $obj->address;
      $player->name = $obj->name ?? $address;
      $player->publicKey = $obj->public_key ?? '';
      $player->score = $obj->score ?? 0;
      $player->playerType = $obj->player_type ?? $player->playerType;
      
      $result = pg_query_params('SELECT * FROM shielded_expedition.validators WHERE address = $1 LIMIT 1', [$address]);
      if ($result)
      {
        $obj = pg_fetch_object($result);
        if ($obj)
        {
          $player->isValidator = true;
          $label = "[Validator]";
          $player->isGenesis = $obj->genesis && $obj->genesis != 'f';
          if ($player->isGenesis) {
            $label = "[Genesis]";
            if ($player->playerType == 'Unknown')
            {
              $player->playerType = 'Genesis';
            }
          }
          $player->name = $obj->name ? $obj->name : $player->name;
          $player->name .= " " . $label;
        }
      }

      // if $address is a public key, add as both address and public key
      $addressToPlayer[$address] = $player;
      $addressToPlayer[$player->address] = $player;
    }
  }
}