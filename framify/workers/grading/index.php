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
        $this->active_grading_rules = $grading_rules;
        $this->active_submission    = $submission_instance;

        
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

    private function transformNestedParams($param_array, $parent_references = [], $rgx = "/{{.*}}/i") {
           
        $replaceWithParentValue = function ($vl) use ($parent_references,$this,$rgx)
        {
            $target_parameter = preg_replace('/({{)|(}})|(parent\.)/i','',$vl);
            $found = NULL;
            for($i = 0; $i < count($this.$parent_references); $i++){
                if(isset($found)) break;

                //@ ensure the cached response isn't an error   
                if(!$this->local_cache[$parent_references[$i]]["error"])
                {
                    //@ search in the body 

                    //@ search in the head
                }
            }
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
        
        return ( array_reduce($param_data, $transformTokeyValueArray, array()));

    }


    private function extractParametersFromParent($param_data, $parent_references)
    {


        //@ convert the parameters to arrays
        if($param_data){
            $param_data = $this->transformNestedParams( json_decode($param_data) );


        }else{
            return [];
        }
    }

    private function doGrading()
    {
       
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

        //@ Capture current method;
        $call_method = $current_rule["rule_method"];
        $call_path = $current_rule["rule_path"];
        $call_data = [
            "headers" => [],
            "body"    => []
        ]

        // ( $method = "GET", $path = "/", $parameters = [] )
        //@ Execute the rule against the submission/attempt
        // $attempt_response = $this->grade_router->call()
   
        //@ Simulate rule execution
        $this->local_cache[$current_rule["rule_id"]] = "placeholder";     



    }


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