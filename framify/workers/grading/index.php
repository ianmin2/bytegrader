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


    private function doGrading()
    {
       
        //@ Loop through each rule, 
        for ($idx=0; $idx < count($this->active_grading_rules); $idx++) { 
            
            $current_rule = $this->active_grading_rules[$idx];
            $parent_rules = $current_rule['parent_rules'];

            //@ Check if the parent rules are defined
            if( is_array($parent_rules) )
            {
                //@ Ensure that the parent rules object isn't empty
                if(!$parent_rules[0]) continue;

                //@ Ensure that all the parent rules have been cached already
                for($i = 0; $i<count($parent_rules);$i++){
                    $current_rule = $parent_rules[$i];
                    if(!$this->local_cache[$current_rule["rule_id"]]){
                        $this->$error_log .= "\nCould not find a local reference to the  grading rule with the id ${$current_rule['rule_id']}";
                    }
                } 

               

                //@ If the current rule has a dependency, extract the features from the dependency via the ['local_cache']
                //@ Execute a http request to the rule specified 'path' using the rule specified 'http method'
            }
       

            //@ Keep track of the request response as ['rule_id'] in the 'local_cache'
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