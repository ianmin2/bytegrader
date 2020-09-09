<?php
//@ ALLOW CORS REQUESTS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST,GET,OPTIONS");
header("Content-Type:application/json");
// echo phpinfo();
// include __DIR__ . "/php-router/index.php";
include __DIR__ . "/framify/index.php";
