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
$jwtHandler = new AuthTokenHandler($_ENV["ENCODING_KEY"]);
$GLOBALS["jwt"] = $jwtHandler;


function authValidate($authToken, $jwtFactory)
{
    $tkn = ($jwtFactory->decode($authToken));

    if ($tkn["response"] != 200) return false;
    return $tkn["data"]["message"];
}


// // $BaggedToken = ($tokenizer->encode(array("username" => "ianmin2")));

// // echo "<br><br>Bagged:\t <pre>";
// // print_r($BaggedToken);
// // echo "</pre>";

// // $token = $BaggedToken["data"]["message"];
// // echo "<br><br>Extracted:\t <pre>$token</pre>";

// // echo "Actual \t <pre>";
// // print_r($tokenizer->decode($token));
// // echo "</pre>";


// // echo "Spoofed \t" . $tokenizer->decode($spoofedToken);


//@ HANDLE RUNTIME ERROR DISPLAY
ini_set('display_errors', @$_ENV["OUT_LOUD"] == "true" ? 1 : 0);
ini_set('display_startup_errors', @$_ENV["OUT_LOUD"] == "true" ? 1 : 0);
error_reporting(@$_ENV["OUT_LOUD"] == "true" ? E_ALL : 0);



//@ PERFORM BASIC AUTHENTICATION & DEPENDENCY IMPORTATION
include("__adminAuth.php");

$command = @$_REQUEST["command"];
$table = @$_REQUEST["table"];
unset($_REQUEST["command"]);
// unset($_REQUEST["table"]);

//# REMOVE THE CALLBACK VARIABLES 
//! [REMOVE ONLY AFTER INCLUDING THE CONNECTION CLASSES TO ENABLE CORS]
unset($_REQUEST["callback"]);
unset($_REQUEST["_"]);
unset($_REQUEST["token"]);
unset($_REQUEST["buttons"]);




if (@$command) {

    include __DIR__ . "/api/api.php";

    $proc = new DissertationAPI($connection);

    // echo $proc->c->wrapResponse(500, "Tested death!");
    // exit;

    switch ($command) {

            //# ADDITION HANDLER
        case 'add':
            switch ($table) {

                case 'user':
                    //@ Add a user
                    unset($_REQUEST["table"]);
                    echo $proc->addUser($_REQUEST);
                    exit;
                    break;

                case 'assignment':
                    //@ Add an assignment 
                    unset($_REQUEST["table"]);
                    echo $proc->addAssignment($_REQUEST);
                    exit;
                    break;

                case 'rule':
                    //@ Add a new assignment ruleset 
                    unset($_REQUEST["table"]);
                    echo $proc->addRoute($_REQUEST);
                    exit;
                    break;

                case 'chaining':
                    //@ Add a new assignment chaining 
                    echo $proc->addChaining($_REQUEST);
                    exit;
                    break;

                case 'attempt':
                    //@ Add a new assignment attempt record
                    echo $proc->addAttempt($_REQUEST);
                    exit;
                    break;

                default:
                    #//@ Give a generic failure message
                    echo '{"response":404, "data": {"message": "Could not find the referenced addition resource."}}';
                    exit;
                    break;
            }

            break;

            //# SIMPLE COUNTER FUNCTION 

            //# DATA UPDATE HANDLER
        case 'update':
            switch ($table) {

                case 'user':
                    //@ Update a user
                    unset($_REQUEST["table"]);
                    echo $proc->updateUser($_REQUEST);
                    exit;
                    break;

                case 'assignment':
                    //@ Update an assignment 
                    unset($_REQUEST["table"]);
                    echo $proc->updateAssignment($_REQUEST);
                    exit;
                    break;

                case 'rule':
                    //@ Update an assignment ruleset 
                    unset($_REQUEST["table"]);
                    echo $proc->updateRoute($_REQUEST);
                    exit;
                    break;

                case 'chaining':
                    //@ Update an assignment chaining 
                    echo $proc->updateChaining($_REQUEST);
                    exit;
                    break;

                case 'attempt':
                    //@ Update an assignment attempt record
                    echo $proc->updateAttempt($_REQUEST);
                    exit;
                    break;

                default:
                    #//@ Give a generic failure message
                    echo $proc->c->wrapResponse(404, "Could not find the referenced update resource.", $_REQUEST);
                    exit;
                    break;
            }

            break;


        case 'count':
            switch ($table) {

                case 'users':
                    //@ Count users
                    echo $proc->c->ctr($table);
                    exit;
                    break;

                case 'assignments':
                    //@ Count existing assignments
                    echo $proc->c->ctr($table);
                    exit;
                    break;

                case 'routes':
                    //@ Count defined rules
                    echo $proc->c->ctr($table);
                    exit;
                    break;

                case 'chainings':
                    //@ Count the existing assignment chaining 
                    echo $proc->c->ctr($table);
                    exit;
                    break;

                case 'attempts':
                    //@ Count assignment attempt records
                    echo $proc->c->ctr($table);
                    exit;
                    break;

                default:
                    #//@ Give a generic failure message
                    echo '{"response":404, "data": {"message": "Could not find the referenced count resource."}}';
                    exit;
                    break;
            }

        case "auth":
            $creds = $proc->loginUser($_REQUEST);
            echo $creds;
            exit;
            break;

            //# SIMPLE GETTER HANDLER
        case 'get':

            switch ($table) {
                case 'routes':
                    echo ($_REQUEST['id'] == null) ? $proc->getRoutes() : $proc->getRoute($_REQUEST['id'], $_REQUEST['byAssignment']);
                    exit;
                    break;

                case "assignments":
                    echo ($_REQUEST['id'] == null) ? $proc->getAssignments() : $proc->getAssignment($_REQUEST['id']);
                    exit;
                    break;

                case "users":
                    echo $proc->getUsers();
                    exit;
                    break;

                case "chaining":
                    echo $proc->getChainings();
                    exit;
                    break;

                case "attempts":
                    echo $proc->getAttempts();
                    exit;
                    break;

                default:
                    echo "Nadaaaaaaaaa! {$table}";
                    exit;
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
