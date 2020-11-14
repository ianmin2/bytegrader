<?php

require __DIR__."/../vendor/autoload.php";

use GuzzleHttp\Client;

//@ Create a re-usable remote-query runner for the grader
class GradeRouter
{
    private $base_url;
    private $client;

    function __construct ($base_url){
        $this->base_url = $base_url;
        $this->client = new GuzzleHttp\Client(['base_uri' => $this->base_url]);
    }

    function call ( $method = "GET", $path = "/", $parameters = [] )
    {

        // // print_r($this->local_cache);
        // print_r(["method" => $method, "base_url" => $this->base_url, "path" => $path, "parameters" => $parameters]);
        // exit;

        $responseObject = [];
        try {
            $response = $this->client->request(strtoupper($method), $path, $parameters );
            $responseObject = [ "error" => false, "body" => $response->getBody(), "headers" => $response->getHeaders(), "status" => $response->getStatusCode(), "content" => $response->getBody()->getContents(), "response" => $response];
        } catch (\Throwable $th) {
           $responseObject =[ "error" => true,  "object" => $th,  "message" =>  $th->__toString(), "response" => $response];
        }

        return $responseObject;
       
    }    
}


// $graderObj = new GradeRouter("https://bixbyte.io");
// $graderObj->call("GET","/",["parameters" => ""],"call_type");

