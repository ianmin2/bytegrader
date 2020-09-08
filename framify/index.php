<?php

if (isset($_SERVER["CONTENT_TYPE"]) && strpos($_SERVER["CONTENT_TYPE"], "application/json") !== false) {
    $_POST = array_merge($_POST, (array) json_decode(trim(file_get_contents('php://input')), true));
    $_REQUEST = array_merge($_REQUEST, $_POST);
}


require "vendor/autoload.php";

//@ LOAD ENVIRONMENT VARIABLES 
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');


//@ LOAD THE JWT HELPER 
include "jwt.php";
$jwtHandler = new AuthTokenHandler("ianmin2");



function authValidate($authToken, $jwtFactory)
{
    $tkn = ($jwtFactory->decode($authToken));

    if ($tkn["response"] != 200) return false;
    return $tkn["data"]["message"];
}


// $BaggedToken = ($tokenizer->encode(array("username" => "ianmin2")));

// echo "<br><br>Bagged:\t <pre>";
// print_r($BaggedToken);
// echo "</pre>";

// $token = $BaggedToken["data"]["message"];
// echo "<br><br>Extracted:\t <pre>$token</pre>";

// echo "Actual \t <pre>";
// print_r($tokenizer->decode($token));
// echo "</pre>";


// echo "Spoofed \t" . $tokenizer->decode($spoofedToken);



//@ HANDLE RUNTIME ERROR DISPLAY
ini_set('display_errors', @$_ENV["DEBUG"] == "true" ? 1 : 0);
ini_set('display_startup_errors', @$_ENV["DEBUG"] == "true" ? 1 : 0);
error_reporting(@$_ENV["DEBUG"] == "true" ? E_ALL : 0);

//@ ALLOW CORS REQUESTS
// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Headers: *");
// header("Access-Control-Allow-Methods: *");
// header("Content-Type:application/json");

// echo json_encode($_REQUEST);
// exit;

// echo phpinfo();
// exit;

//@ PERFORM BASIC AUTHENTICATION & DEPENDENCY IMPORTATION
include("__adminAuth.php");

$command = @$_REQUEST["command"];
$table = @$_REQUEST["table"];
unset($_REQUEST["command"]);


//# REMOVE THE CALLBACK VARIABLES 
//! [REMOVE ONLY AFTER INCLUDING THE CONNECTION CLASSES TO ENABLE CORS]
unset($_REQUEST["callback"]);
unset($_REQUEST["_"]);
unset($_REQUEST["token"]);


if (@$command) {


    include __DIR__ . "/api/api.php";

    $proc = new DissertationAPI($connection);

    switch ($command) {

            //# ADDER HANDLER
        case 'add':
            switch ($table) {
                case 'user':
                    //@ Add a user
                    unset($_REQUEST["table"]);
                    echo $proc->addUser($_REQUEST);
                    break;

                case 'assignment':
                    //@ Add an assignment 
                    echo $proc->addAssignment($_REQUEST);
                    break;

                case 'rule':
                    //@ Add a new assignment ruleset 
                    echo $proc->addRoute($_REQUEST);
                    break;

                case 'chaining':
                    //@ Add a new assignment chaining 
                    echo $proc->addChaining($_REQUEST);
                    break;

                case 'attempt':
                    //@ Add a new assignment attempt record
                    echo $proc->addAttempt($_REQUEST);
                    break;

                default:
                    #//@ Give a generic failure message
                    echo '{"response":404, "data": {"message": "Could not find the referenced addition resource."}}';
                    break;
            }

            break;

            //# SIMPLE COUNTER FUNCTION 

        case 'count':
            echo $proc->countFunc($_REQUEST);
            exit;
            break;

        case "auth":
            $creds = $proc->loginUser($_REQUEST);
            echo $creds;
            exit;
            break;

            //# SIMPLE GETTER HANDLER
        case 'get':

            switch ($table) {
                case 'routes':
                    echo $proc->getRoutes();
                    break;

                case "assignments":
                    echo $proc->getAssignments();
                    break;

                case "users":
                    echo $proc->getUsers();
                    break;

                case "chaining":
                    echo $proc->getChainings();
                    break;

                case "attempts":
                    echo $proc->getAttempts();
                    break;

                default:
                    echo "Nadaaaaaaaaa! {$table}";
                    break;
            }

            // echo $proc->getFunc($_REQUEST);
            // exit;
            break;

            //# ADVANCED GETTER FUNCTION
        case 'getAll':
            echo $proc->getAllFunc($_REQUEST);
            exit;
            break;

            //# DELETION HANDLER
            // case 'del':
            //     echo $proc->delFunc($_REQUEST);
            //     exit;
            //     break;

            //     # UPDATE HANDLER
            // case 'update':
            //     echo $proc->updateFunc($_REQUEST);
            //     exit;
            //     break;

            //     # TRUNCATE HANDLER
            // case 'truncate':
            //     echo $proc->truncateFunc($_REQUEST);
            //     exit;
            //     break;

            //     # TABLE DROP HANDLER
            // case 'drop':
            //     echo $proc->dropFunc($_REQUEST);
            //     exit;
            //     break;

            //     # PERFORM FULLY CUSTOM MANIPULATIONS
            // case 'custom':
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



// $command = @$_REQUEST["command"];
// unset($_REQUEST["command"]);


// //# REMOVE THE CALLBACK VARIABLES 
// //! [REMOVE ONLY AFTER INCLUDING THE CONNECTION CLASSES TO ENABLE CORS]
// unset($_REQUEST["callback"]);
// unset($_REQUEST["_"]);
// unset($_REQUEST["token"]);
