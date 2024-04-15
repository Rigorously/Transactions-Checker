<?php

//database configuration
//create the file db.json outside of the web root
//{
//	"db_host": "localhost",
//	"db_port": 5432,
//	"db_name": "dbname",
//	"db_user": "dbuser",
//	"db_password": "dbpasswd"
//}

$db_file = file_get_contents('../db.json');
$db_settings = json_decode($db_file);

$db_host = $db_settings->db_host;
$db_port = $db_settings->db_port;
$db_name = $db_settings->db_name;
$db_user = $db_settings->db_user;
$db_password = $db_settings->db_password;

//end of user settings
//leave the following untouched

if (empty($db_name) or empty($db_user) or empty($db_password)) {
	die('update database settings');
} else {
	//returns $dbconn connection if successful for further use
	$dbconn = pg_connect("host=$db_host dbname=$db_name user=$db_user password=$db_password port=$db_port") or die('database connection error');
}

?>
