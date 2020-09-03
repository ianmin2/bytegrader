<?php

//! HENDLE NATIVE php ERROR MESSAGE DISPLAY
ini_set('display_errors', @$_ENV["DEBUG"] == "true" ? 1 : 0);
ini_set('display_startup_errors', @$_ENV["DEBUG"] == "true" ? 1 : 0);
error_reporting(@$_ENV["DEBUG"] == "true" ? E_ALL : 0);

//@ SET THE json CONTENT TYPE
header("Content-Type:application/json");

//@ p-connect-framify SETUP
$id         = "conn";
$connect    = true;

include("crypto.php");
include("classes/r_main.php");

//@ REMOVE UNNECESSARY PARAMETERS
unset($_REQUEST["password2"]);

//ADD FILTERS TO PREVENT PIGGYBACKING ON ALL PARAMS
$secure = ["add", "auth", "del", "update", "truncate", "drop", "getAll", "custom"];
// $secure = [];

$errMsg = $connection->wrapResponse(500, "Could not verify your access level to perform this task!<br>Please login to continue.");

//! HANDLE *** IF THE SPECIFIED COMMAND REQUIRES EXTRA AUTHENTICATION
if (in_array($_REQUEST['command'], $secure)) {

	//! ENSURE THAT THE AUTHENTICATION TOKEN HAS BEEN PROVIDED
	if (!@$_REQUEST["token"] || !@$_REQUEST["token"]["user"] || !@$_REQUEST["token"]["key"]) {
		echo $errMsg;
		exit;
	}

	//! ENSURE THAT THE AUTHENTICATION TOKEN IS AUTHENTIC
	if ($connection->num_rows("SELECT * FROM admins WHERE admin_name='" . $_REQUEST["token"]["user"] . "' AND password='" . $_REQUEST["token"]["key"] . "' AND active='true' ", true) <= 0) {
		echo $errMsg;
		exit;
	}

	//! LOG ALL 'AUTHENTICATION REQUIRED' REQUESTS 
	file_put_contents(".access_log.log", "\n" . date('l F j Y h:i:s A') . "\t" . json_encode($_REQUEST), FILE_APPEND);
}
