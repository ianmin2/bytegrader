<?php

/*
	* m4Labs Framework
	* By Ian Kamau 
	*The main connector file.
	*Allows the collective manipulation of a package
	*
	*
	*
	*
*/

/*
	* m4Labs Framework Database connection variables ... and more! */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");
header("Content-Type:application/json");
error_reporting(@$_ENV["OUT_LOUD"] == "true" ? E_ALL : 0);

date_default_timezone_set("UTC");

//$this_site = "eleanor/concept"; 

$db 	= $_ENV['DB'];
$host 	= $_ENV['DB_HOST'];
$user 	= $_ENV['DB_USER'];
$pass 	= $_ENV['DB_PASS'];
$driver = $_ENV['DB_DRIVER'];
$port 	= $_ENV['DB_PORT'];
$db_test_table = $_ENV['DB_TEST_TABLE'];


// print_r([
// 	"db" => $db,
// 	"host" => $host,
// 	"user" => $user,
// 	"pass" => $pass,
// 	"driver" => $driver,
// 	"port" => $port
// ]);
// exit;


/*
	* m4Labs FrameworkEnd of database connection variable declaration*/
if (@$jsoncallback == "") {
	$jsoncallback = @$_REQUEST['callback'];
}
//If the page resource identifier is provided
if (@$id != '') {

	// WARNING ONLY ADD PAGES THAT ARE FULLY CLASSES OR PURELY FUNCTIONS TO THIS ARRAY Else Face the wrath of a broken connection 
	if ($id == "conn") :

		$connect = true;
		$ids = array(
			'',
			'r_connection.php',
			'r_obsfucate.php'
		);

	else :

		$ids = array(
			'',
			't_mailer.php',		//Framework Mailing	Component
			'r_obsfucate.php',		//Framework Obsfucation Component
			'r_connection.php', 	//Framework Database Manipulation Component
			'r_minify.php',		//Framework File Minifying component
			'r_cleaner.php',		//Framework File Deletion Component	
			'r_redirect.php',		//Framework Page Redirect Cmponent
			'class.login.php'		//for login purposes

		);

	endif;

	//find the position of the given page-id in the above array
	$pos = array_search($id, $ids);

	//if the given page-id is non existent in the array give it's position [currently 'NULL'] the value 'unknown'
	if ($ids[$pos] == '') {
		$ids[$pos] = 'unknown';
	}

	//Loop through the entire resource array			
	for ($i = 0; $i <= (count($ids) - 1); $i++) {

		//if the current position in the array is not the current one, include the given resource page 
		if ($i <> $pos) {

			if ($ids[$i] != '') {

				include "$ids[$i]";
			}
		}
	}

	//Establish a database connection where required
	if (@$connect) {

		//Establishing a database connection courtesy of the imported  resource files
		$connection = new connection($db, $host, $user, $pass, $driver, $jsoncallback, $port);

		// //@ Confirm the structure of the main/selected table
		// $q = $connection->printQueryResults("select COLUMN_NAME from information_schema.columns where table_name = 'users' order by ordinal_position");		
		// die(json_encode($q));

		//@ Check if the startup database table is defined and scaffold the database where applicable
		if ($db_test_table == null) return;

		//@ Check to see if the main table is defined
		$rsp = $connection->query("Select * from {$db_test_table};");

		//@ Go on and escape (where acceptable)
		if ($rsp) return;

		//@ Import the schema file
		$schema = file_get_contents(__DIR__ . "/../db/schema.sql");

		// echo $schema . "\n";

		//@ Attempt to create the database 
		$created = $connection->query($schema, true);
	}

	if (@$crypt[0] && @$crypt['key'] != "" && @$crypt['salt'] != "") {

		$crypto = new obsfucate($crypt['key'], $crypt['salt']);
	}

	//If the page resource identifier is not provided
} else {

	$respArray = makeResponse("ERROR", "Critical Error: Failed to recognize application!", "");
	echo $jsoncallback . "(" . json_encode($respArray) . ")";
	die;
}

/*
	* m4Labs Framework******************************************************
	SIMPLE FUNCTIONS PLACED TO BE COPIED TO THE ACTUAL PAGES WHERE NEEDED
*/

function makeResponse($response, $message, $command)
{

	return array("response" => $response, "data" => array("message" => $message, "command" => $command));
}


function sanitize($value)
{

	return htmlspecialchars(str_replace("'", "\'", $value));
}

function makeCookie($cname, $cval, $days)
{

	$days = ($days * 24 * 60 * 60 * 1000);
	@setcookie($cname, $cval, $days);
}

/*
	* m4Labs Framework*****************************************************

*/
