<?php

include __DIR__.'/../../helpers/grade_router.php';

class GradingWorker
{
    

    private $c;
    private $active_grading_rules;
    private $active_submission;
    private $grade_router;
    private $grading_result = ['logs' => ''];
    private $local_cache = [];
    private $error_log = "";

    public function __construct( $grading_rules, $submission_instance, $connection, $sampling = false)
    {
        $this->c  = $connection;
        $this->active_grading_rules = is_array($grading_rules) ? $grading_rules : json_decode($grading_rules,true);
        $this->active_submission    = is_array($submission_instance) ? $submission_instance : json_decode($grading_rules, true);

        
        //@ Die a loud death
        if(!isset($grading_rules)||!isset($submission_instance)||!isset($connection)){
            $error_message = "";
            if(!isset($grading_rules)) $error_message .= "\ngrading rules are not defined";
            if(!isset($submission_instance)) $error_message .= "\na submission instance was not found";
            if(!isset($connection)) $error_message .= "\nno connection object was defined";
            echo json_encode(["status" => 400, "data" => [ "message" => $error_message, "command" => "The grading worker was called without all required values", "actual" => $grading_rules ]]);
            exit;
        } 
        
        //@ Prep the router object
        $this->grade_router = new GradeRouter($this->active_submission["attempt_main_path"]);
        $this->grading_result['logs'] .= "\nSet the main grading url to {$this->active_submission["attempt_main_path"]}";

        //@ start the actual grading
        if(!$sampling){ $this->doGrading(); }

        
    }

    public function validateRules(){

        // if(!is_array($this->active_grading_rules)){
        //     echo gettype($this->active_grading_rules);
        //     exit;
        // }

        //@ Loop through each rule, 
        for ($idx=0; $idx < count($this->active_grading_rules); $idx++) { 
            
            $current_rule = $this->active_grading_rules[$idx];
            $parent_rules = $current_rule['parent_rules'];

            //@ Check if the parent rules are defined
            if( is_array($parent_rules) )
            {
                //@ Ensure that all the parent rules have been cached already
                for($i = 0; $i<count($parent_rules);$i++){

                    $current_parent_rule = $parent_rules[$i];
                    if(!$this->local_cache[$current_parent_rule]){
                        $this->error_log .= "\nCould not find a local reference to the grading rule {$current_parent_rule} required by {$current_rule['rule_id']}\n\t Ensure that {$current_parent_rule} is defined before {$current_rule['rule_id']}\n";
                    }
                }
                
            }
       
            //@ Simulate rule execution
            $this->local_cache[$current_rule["rule_id"]] = "placeholder";     



        }

        if($this->error_log != ""){

            return $this->c->wrapResponse(400, $this->error_log, ["cache"=>$this->local_cache]);
        }
       
        return false;

    }

    private function extractParametersFromParent($param_data, $parent_references = [], $rgx = "/{{.*}}/i") {
        
        $that = $this;

        $replaceWithParentValue = function ($vl) use ($parent_references,$that,$rgx)
        {
            $target_parameter = preg_replace('/({{)|(}})|(parent\.)/i','',$vl);
            $found = NULL;
            for($i = 0; $i < count($parent_references); $i++){
                if(isset($found)) break;

                //@ ensure the cached response isn't an error   
                if(!$that->local_cache[$parent_references[$i]]["error"])
                {
                    //@ search in the body 
                    $body_search = @$that->local_cache[$parent_references[$i]]["body"][$target_parameter]; 
                    if($body_search)
                    {
                        $that->grading_result["logs"].="\n\nReplaced the inherited parameter {{$target_parameter}} from rule #{$parent_references[$i]} with the extracted body value '{$body_search}'.";
                        $found = $body_search;
                        break;
                    }

                    //@ search in the head
                    $headers_search = @$that->local_cache[$parent_references[$i]]["headers"][$target_parameter]; 
                    if($headers_search)
                    {
                        $that->grading_result["logs"].="\n\nReplaced the inherited parameter {{$target_parameter}} from rule #{$parent_references[$i]} with the extracted header value '{$header_search}'.";
                        $found = $headers_search;
                        break;
                    }
                }
                else {
                   $that->grading_result["logs"].="\n\nEncountered an error while trying to populate the inherited parameter {{$target_parameter}} from rule #{$parent_references[$i]}.";
                }
            }
            return isset($found) ? $found : "";
        };


        $transformTokeyValueArray = function ($temp_values,$item) use ($rgx){

            //@ check if it is a replacement item
            if(preg_match($rgx,$item))
            {
                $item["value"] = $replaceWithParentValue($item["value"]);
            }
            $temp_values[$item["key"]] = $item["value"];

            return $temp_values;
        };
      
        if($param_data)
        {
            return ( array_reduce(json_decode($param_data, true), $transformTokeyValueArray, array()));
        }
        else {
            return [];
        }
       

    }

    private function doGrading()
    {

       //@ Loop through each rule, 
       for ($idx=0; $idx < count($this->active_grading_rules); $idx++) { 
            
            $current_rule = $this->active_grading_rules[$idx];
            $parent_rules = $current_rule['parent_rules'];

            // print_r(["current" => $current_rule, "parent" => $parent_rules]);
            // exit;

            //@ Check if the parent rules are defined
            if( is_array($parent_rules) )
            {
                if($parent_rules[0])
                {
                     //@ Ensure that all the parent rules have been cached already
                    for($i = 0; $i<count($parent_rules);$i++){
                        $current_parent_rule = $parent_rules[$i];
                        if(!$this->local_cache[$current_parent_rule]){
                            $this->error_log .= "\nCould not find a local reference to the grading rule {$current_parent_rule} required by {$current_rule['rule_id']}\n\t Ensure that {$current_parent_rule} is defined before {$current_rule['rule_id']}\n";
                        }
                    }
                }
            }

            //@ Capture current method;
            $call_method = $current_rule["rule_method"];
            $call_path = $current_rule["rule_path"];

            // // print_r(["method" => $call_method, "path" => $call_path]);
            // // exit;
            
            // print_r(["method" => $current_rule["rule_headers"], "path" => $current_rule["rule_parameters"]]);
            // exit;
            $parameter_type = ($call_method == "GET") ? "query" : "json";
            $payload_params = $this->extractParametersFromParent($current_rule["rule_parameters"], $parent_rules,"/({{.*}})|({.*})/i");
            $header_params = $this->extractParametersFromParent($current_rule["rule_headers"], $parent_rules,"/({{.*}})|({.*})/i");
            $call_data = [
                "headers" => $header_params,
                "{$parameter_type}"    => ($call_method == "GET") ? $payload_params : ($payload_params)   ,
                'http_errors' => false
            ];

            $this->grading_result["logs"].="\n\nInherited and static parameters locked for rule #{$parent_references[$i]}.";

            // ( $method = "GET", $path = "/", $parameters = [] )
            //@ Execute the rule against the submission/attempt
            $attempt_response = $this->grade_router->call($call_method, $call_path, $call_data);

            $this->grading_result['logs'].="\n\nFinalized a {$call_method} request to '{$call_path}' and got a ".$attempt_response['status']." response with the message:\n\t".$attempt_response["content"];

            // // // print_r($this->local_cache);
            // echo "\n\nROUTER RESPONSE:";
            // print_r($attempt_response["content"]);
            // echo "\n\n from response with headers:\n";
            // print_r($header_params);
            // exit;
    
            //@ Populate the local cache with the received data
            $this->local_cache[$current_rule["rule_id"]] = $attempt_response;     

            //@ Match against expectations assigning a grade_router

            //@

        }

        //@ Show a breakdown of the grading procedure
        echo $this->grading_result["logs"];


    }

}

//@ Capture the actual grading bundle

//@ Validate against the provided route

// function createGreeter($who, $dot=".") {
//     return function() use ($who, $dot) {
//         echo "Hello {$who}{$dot}";
//     };
// }

// $greeter = createGreeter("World");
// $greeter(); // Hello World


?>