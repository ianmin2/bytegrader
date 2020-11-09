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


        print_r([
            "rules"=> $grading_rules,
            "rules_empty" => isset($grading_rules),
            "submission" => $submission_instance,
            "submission_empty" => isset($submission_instance),
            "connection" => $connection,
            "connection_empty" => isset($connection)   
        ]);

        $this->c  = $connection;
        $this->active_grading_rules = $grading_rules;
        $this->active_submission   = $submission_instance;

        
        //@ Die a loud death
        if(!isset($grading_rules)||!isset($submission_instance)||!isset($connection)){
            echo json_encode(["status" => 400, "data" => [ "message" => "Invalid Grading Worker dependencies defined.", "comamnd" => "The grading worker was called without all required values" ]]);
            exit;
        } 
        echo "\nHere!\n";
        //@ Prep the router object
        $this->grade_router = new GradeRouter($this->active_submission["attempt_main_path"]);
        $this->grading_result['logs'] .= "\nSet the main grading url to {$this->active_submission["attempt_main_path"]}";

        //@ start the actual grading
        if(!$sampling) $this->doGrading();


        echo "\nHere!\n";
    }

    public function validateRules(){

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

            }
       
            //@ Simulate rule execution
            $this->local_cache[$current_rule["rule_id"]] = "placeholder";            
            

        }

        if($this->$error_log != ""){
            return $this->c->wrapResponse(400,$error_log."\n\nEnsure that the grading rules are arranged sequentialy.");
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