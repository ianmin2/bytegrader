<?php

require "vendor/autoload.php";

//@ LOAD ENVIRONMENT VARIABLES 
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');


//@ HANDLE RUNTIME ERROR DISPLAY
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(@$_ENV["DEBUG"] == "true" ? E_ALL : 0);

//@ ALLOW CORS REQUESTS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");
header("Content-Type:application/json");

// echo json_encode($_REQUEST);
// exit;

// echo phpinfo();
// exit;

//@ PERFORM BASIC AUTHENTICATION & DEPENDENCY IMPORTATION
include("__adminAuth.php");

$command = @$_REQUEST["command"];
unset($_REQUEST["command"]);


//# REMOVE THE CALLBACK VARIABLES 
//! [REMOVE ONLY AFTER INCLUDING THE CONNECTION CLASSES TO ENABLE CORS]
unset($_REQUEST["callback"]);
unset($_REQUEST["_"]);
unset($_REQUEST["token"]);

if (@$command) {

	include("process.php");

	$proc = new Process($connection);

	switch ($command) {

			//# ADDER HANDLER
		case 'add':
			echo $proc->addFunc($_REQUEST);
			exit;
			break;

			//# SIMPLE COUNTER FUNCTION 
		case 'count':
			echo $proc->countFunc($_REQUEST);
			exit;
			break;

			//# SIMPLE GETTER HANDLER
		case 'get':
			echo $proc->getFunc($_REQUEST);
			exit;
			break;

			//# ADVANCED GETTER FUNCTION
		case 'getAll':
			echo $proc->getAllFunc($_REQUEST);
			exit;
			break;

			//# DELETION HANDLER
		case 'del':
			echo $proc->delFunc($_REQUEST);
			exit;
			break;

			//# UPDATE HANDLER
		case 'update':
			echo $proc->updateFunc($_REQUEST);
			exit;
			break;

			//# TRUNCATE HANDLER
		case 'truncate':
			echo $proc->truncateFunc($_REQUEST);
			exit;
			break;

			//# TABLE DROP HANDLER
		case 'drop':
			echo $proc->dropFunc($_REQUEST);
			exit;
			break;

			//# PERFORM FULLY CUSTOM MANIPULATIONS
		case 'custom':
			echo $proc->customFunc($_REQUEST);
			exit;
			break;

		default:
			echo $connection->wrapResponse(500, "The required parameters were not met. Please ensure that they are defined");
			exit;
			break;
	}
} else {

	echo $connection->wrapResponse(500, "The main action definition parameter was not defined. Could not proceed with the command.");
	exit;
}
