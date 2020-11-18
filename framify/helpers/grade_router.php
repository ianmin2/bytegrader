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

    private function parseJson( $data )
    {
        try {
          $parsed =  json_decode($data,true);
          return $parsed;
        } catch (\Throwable $th) {
            return $data;
        }
    }
    function call ( $method = "GET", $path = "/", $parameters = [] )
    {
        // echo "\n====================================\n";
        // print_r($parameters);
        // echo "\n====================================\n";
        // print_r(["method" => $method, "base_url" => $this->base_url, "path" => $path, "parameters" => $parameters]);
        // exit;

        echo "\n\n@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@\nCalling {$method} - {$path}\n@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@\n\n";

        $responseObject = [];
        try {
            $response = $this->client->request(strtoupper($method), $path, $parameters );
            $responseObject = [ "error" => false, "body" => $response->getBody(), "headers" => $response->getHeaders(), "status" => $response->getStatusCode(), "content" => $this->parseJson($response->getBody()->getContents()), "response" => $response];
        } catch (\Throwable $th) {
           $responseObject =[ "error" => true,  "object" => $th,  "message" =>  $th->__toString(), "response" => $response];
        }

        $tmpv = json_encode($responseObject["content"]) ?? $responseObject["content"];
        echo ">> ({$responseObject["status"]}) RESPONSE:\n{$tmpv}\n\n";

        return $responseObject;
       
    }    
}


// $graderObj = new GradeRouter("https://bixbyte.io");
// $graderObj->call("GET","/",["parameters" => ""],"call_type");

