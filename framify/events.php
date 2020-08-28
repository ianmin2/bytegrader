<?php

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(@$_ENV["DEBUG"] == "true" ? E_ALL : 0);

//@ ALLOW CORS REQUESTS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");
header("Content-Type:application/json");

//@ SET THE json CONTENT TYPE
header("Content-Type:application/json");

//@ p-connect-framify SETUP
$id         = "conn";
$connect    = true;

include("crypto.php");
include("classes/r_main.php");

$owner          = $_REQUEST['owner'];
$owner_query    = ($owner) ? " WHERE owner='" . $owner . "'" : "";
$formatted      = [];
$results        = $connection->printQueryResults("SELECT * FROM vw_appointments " . $owner_query);
foreach ($results as $key => $result) {
    array_push($formatted, array(
        "id" => $result["appointment_id"], "title" => $result["name"] . " " . $result["telephone"] . "", "url" => $result["email"], "class" => "event-important", "start" => strtotime($result["scheduled"]), "stop" => strtotime($result["scheduled"]) + 900
    ));
}
echo json_encode(array('success' => 1, 'result' => $formatted));
exit;
