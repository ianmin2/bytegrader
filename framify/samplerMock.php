<?php

require __DIR__."/vendor/autoload.php";




//@ LOAD ENVIRONMENT VARIABLES 
use Symfony\Component\Dotenv\Dotenv;



$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');


//@ PERFORM BASIC AUTHENTICATION & DEPENDENCY IMPORTATION
include(__DIR__."/__adminAuth.php");

 include (__DIR__."/sampler.php");



 
 $sampler = new Sampler($connection);

 $sampler->mockGrading();

 print_r($_ENV);
 exit;

 echo json_encode(["tester" => "live", "path" => __DIR__."/sampler.php"]);
 exit;