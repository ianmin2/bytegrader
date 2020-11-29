<?php

//! HENDLE NATIVE php ERROR MESSAGE DISPLAY
ini_set('display_errors', @$_ENV["OUT_LOUD"] == "true" ? 1 : 0);
ini_set('display_startup_errors', @$_ENV["OUT_LOUD"] == "true" ? 1 : 0);
error_reporting(@$_ENV["OUT_LOUD"] == "true" ? E_ALL : 0);

//@ SET THE json CONTENT TYPE
header("Content-Type:application/json");

//@ p-connect-framify SETUP
$id         = "conn";
$connect    = true;

include(__DIR__."/crypto.php");
include(__DIR__."/classes/r_main.php");

//@ REMOVE UNNECESSARY PARAMETERS
unset($_REQUEST["password2"]);

//ADD FILTERS TO PREVENT PIGGYBACKING ON ALL PARAMS
$secure = ["add", "del", "update", "truncate", "drop", "getAll", "custom", 'grade','mock']; //"auth",
// $secure = [];

$errMsg = $connection->wrapResponse(500, "Could not verify your access level to perform this task!<br>Please login to continue.");

//! HANDLE *** IF THE SPECIFIED COMMAND REQUIRES EXTRA AUTHENTICATION
//@ Add a concession for user registration
if (in_array(@$_REQUEST['command'], $secure)  && !($_REQUEST['command'] == "add" && $_REQUEST['table'] == "user")) {

	//@ Capture the headers
	$headers = getallheaders();

	//! ENSURE THAT THE AUTHENTICATION TOKEN HAS BEEN PROVIDED
	if (!$headers["Authorization"]) die($errMsg);


	$headers["Authorization"] = preg_replace('/^Bearer\s|\sBearer\s/i', '', $headers["Authorization"]);

	//! ENSURE THAT THE AUTHENTICATION TOKEN IS AUTHENTIC
	if ($GLOBALS["jwt"]->decode($headers["Authorization"])["response"] != 200) die($errMsg);

	//! LOG ALL 'AUTHENTICATION REQUIRED' REQUESTS 
	file_put_contents(__DIR__ . "/.authorized_requests.log", "\n" . date('l F j Y h:i:s A') . "\t" . json_encode(["request" => $_REQUEST, "headers" => $headers]), FILE_APPEND);
}
