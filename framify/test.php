<?php

require "vendor/autoload.php";

use Firebase\JWT\JWT;


$key = "ianmin2";

$payload = array("username" => "ianmin2");

$encoded = JWT::encode($payload, $key);

// $spoofedToken = $encoded . " "; 
// $token = $BaggedToken["data"]["message"];



// echo "Actual \t" . JWT::decode($encoded, $key, array('HS256'));

// echo "Spoofed \t" . JWT::decode($encoded . " ", $key, array('HS256'));
