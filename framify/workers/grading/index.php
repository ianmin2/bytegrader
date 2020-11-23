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

    //@ Accepts, [{grading_ruleset},{submission_configuration},{database_connection_object},{mock_flagger}]
    public function __construct( $grading_rules, $submission_instance, $connection, $sampling = false)
    {
        $this->c  = $connection;
        $this->active_grading_rules = json_decode($grading_rules,true) ?? $grading_rules;
        $this->active_submission    = json_decode($submission_instance, true) ?? $submission_instance;

    
        //@ Die a loud death
        if(!isset($grading_rules)||!isset($submission_instance)||!isset($connection)){
            $error_message = "";
            if(!isset($grading_rules)) $error_message .= "\ngrading rules are not defined";
            if(!isset($submission_instance)) $error_message .= "\na submission instance was not found";
            if(!isset($connection)) $error_message .= "\nno connection object was defined";
            echo json_encode(["status" => 400, "data" => [ "message" => $error_message, "command" => "The grading worker was called without all required values", "actual" => $grading_rules ]]);
            exit;
        } 
        
        
        $this->grading_result["logs"].=@"\n\n🌟 🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟\nCommencing with grading for `".$this->active_submission["attempt_name"]."` (".$this->active_submission["attempt_student_identifier"].")\n🌟 🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟\n";

        //@ Prep the router object
        $this->grade_router = new GradeRouter($this->active_submission["attempt_main_path"]);
        $this->grading_result['logs'] .= "\n\nSet the main grading url to {$this->active_submission["attempt_main_path"]}\n";


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

        print_r($parent_references);
        echo "\n\n__________";
        // exit;
        $that = $this;
       
        $nestedBodySearch = function($content_pool, $content_key){

            echo "\n=======================\n@ At nested body search\n=======================\n";

            $content_pool = json_decode($content_pool,true) ?? $content_pool;

            $dt = $content_pool[$content_key];
            if(!$dt)
            {
                //@ If is array
                if(is_array($content_pool))
                {
                    $cpLength = count($content_pool);
                    //@ If isn't associative, get the last item
                    if(!isAssoc($content_pool)){
                        $content_pool = $content_pool[$cpLength-1];
                    }
                    //@ loop through
                    foreach($content_pool as $ky => $vl){
                        if(strtolower($ky) == strtolower($content_key))
                        {
                            $dt = $vl;
                        }
                    }
                }
            }
           return $dt;
        };

        $replaceWithParentValue = function ($vl) use ($parent_references,$that,$rgx,$nestedBodySearch)
        {
            echo "\n=======================\n@ At replace with parent value\n=======================\n";

            $target_parameter = preg_replace('/({{)|(}})|(parent\.)/i','',$vl);
            echo($target_parameter);
            echo "+++++++++++++++++++++++++++++++=\n\n\n\n\n\n\n\n\n";
            $found = NULL;
            for($i = 0; $i < count($parent_references); $i++){
                if(isset($found)) break;       

                //@ ensure the cached response isn't an error   
                if(!$that->local_cache[$parent_references[$i]]["error"])
                {

                    //@ search in the body 
                    $body_search = @$nestedBodySearch($that->local_cache[$parent_references[$i]]["content"],$target_parameter); 
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

        $transformTokeyValueArray = function ($temp_values,$item) use ($rgx,$replaceWithParentValue){

            echo "\n=======================\n@ At transform tokey ({$item}) array\n=======================\n";

            //@ check if it is a replacement item
            if(preg_match($rgx,$item["value"],$matched))
            {
                // echo "\n=======================\n@ matches regex ({$matched[0]})\n=======================\n";               
                $item["value"] = $replaceWithParentValue($matched[0]);   
                echo "\n=======================\n@ matches regex ({$matched[0]}) === ({$item['value']})\n=======================\n";      
                $this->grading_result["logs"].="\n\tParameter `{$item['key']}` to `{$item["value"]}.`";          
            }
            $this->grading_result["logs"].="\n\nSetting the parameter `{$item['key']}` to `{$item["value"]}.`";
            $temp_values[$item["key"]] = $item["value"];

            return $temp_values;
        };

        $transformTokenFromString = function($string_value) use ($rgx,$that,$nestedBodySearch,$parent_references){
            preg_match($rgx,$item["value"],$matched);
            $new_val = $string_value;
            foreach ($matched as $ky => $val) {
                $act_parameter = preg_replace('/({{)|(}})|(parent\.)/i','',$val);
                $l = $nestedBodySearch($parent_references,$act_parameter);
                $new_val = preg_replace($rgx,$l,$new_val);
            }
            echo "\n\n###################################".$new_val."\n\n";
            exit;
        };
      
        if($param_data)
        {
            $transformed_param_data = json_decode($param_data, true);
            $transformed_param_data = $transformed_param_data ?? $param_data;
            if(is_array($transformed_param_data))
            {
                return ( array_reduce(json_decode($param_data, true), $transformTokeyValueArray, array()));
            }
            else {
                echo "DOne -----";
                return preg_match($rgx,$item["value"]) ?  $transformTokenFromString($transformed_param_data) : $transformed_param_data;
            }
            // return ( array_reduce(json_decode($param_data, true), $transformTokeyValueArray, array()));
        }
        else {
            return [];
        }
       

    }

    // ------------------------------------------------

    //@ Handle auth header separation
    private function doAuthenticationHeaderSeparation( $all_headers )
    {
        $result = ["headers" => [] ];

        $handle_addition = function($header_key, $header_value, $basket)
        {
            // if(strtolower( preg_replace('/\s+/', '', $header_key)) == "authorization")
            // {
            //     $basket[auth] = [null, $header_value];
            // }
            // else {
                $basket["headers"][$header_key] = $header_value;
            // }
            return $basket;
        };

        if(is_array($all_headers))
        {
            
            foreach ($all_headers as $header_key => $header_value) {

                if(is_array($header_value))
                {
                    foreach ($header_value as $key => $value) {
                       $result = $handle_addition($key,$value,$result);                    
                    }
                }
                else {
                  $result = $handle_addition($header_key,$header_value,$result);
                }

            }
        }

        // echo ">>>>>>>>>>>>>>>>>>";
        // print_r($result);
        // echo ">>>>>>>>>>>>>>>>>>>>>>>>>>".json_encode($all_headers);
        return $result;
    }

    //@ Handle parent rule extraction
    private function doParentRuleExtraction( $parent_rule_array )
    {
        $found = [];
        foreach ($parent_rule_array as $parent_id) {
            if(@$this->local_cache[$parent_id]) array_push($found, $this->local_cache[$parent_id] );
        }
        return $found;
    }

    //@ Handle parameter substitution
    private function doValueExtraction( $canvas, $transform_values )
    {
    
        $rgx = "/({{.*}})|({.*})/i";
    
        $isAssoc = function (array $arr) {
            if (array() === $arr) return false;
            return array_keys($arr) !== range(0, count($arr) - 1);
        };
    
        //@ handle independent extractions ["last as first"]
        $independent_extractor = function($parameter_bank = [], $value_key = "") use ($isAssoc) {
    
            // echo "\n\n@ independent extractor\n";
    
            $parameter_bank = @json_decode($parameter_bank,true) ?? $parameter_bank;
    
            // print_r($parameter_bank);
    
            $found = NULL;
    
            if($isAssoc($parameter_bank))
            {
                // echo "\n\n@ is assoc\n\n";
                // print_r($parameter_bank);
    
                //@ Attempt a direct extraction
                $found = @$parameter_bank[$value_key];
                
            }
            else {
    
                // echo "\n\n@ is not assoc\n\n";
                // print_r($parameter_bank);
    
                foreach (array_reverse($parameter_bank) as $key => $value) {
                    if($found) continue;
                    if(@$value[$value_key])
                    {
                        $found = @$value[$value_key];
                    }
                }
            }
    
            
            return $found;
        };
       
        //@ Get the value matching the key from the parameter bank
        $extract_values = function( $value_key, $parameter_bank ) use ($isAssoc, $independent_extractor) {
        
            //@ Attempt to convert the conversion pool to an array
            $parameter_bank = @json_decode($parameter_bank,true) ?? $parameter_bank;
    
            // print_r($parameter_bank);
            $found = NULL;
            foreach ($parameter_bank as $param_key => $param_value) {
                if($found) continue;
                $param_value = !is_array($param_value)? json_decode($param_value,true): $param_value;
    
               
    
                //@ Check if the array is associative
                if($isAssoc($param_value)){
                
                    //@ Attempt a direct extraction [from the main object]
                    $found = @$param_value[$value_key];
    
                  
                    //@ Attempt a body extraction
                    if(!$found && @$param_value["content"])
                    {
                        // print_r($param_value);
                        $found = $independent_extractor($param_value["content"], $value_key);
                        // echo "\n\n1<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\n";
                        // print_r($found);
                        // echo "\n1>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n\n";
                    }
    
                    //@ Attempt a headers extraction
                    if(!$found && $param_value["headers"])
                    {
                        $found = $independent_extractor($param_value["headers"], $value_key);
                        if(is_array($found))
                        {
                            $found = $found[0] ?? json_encode($found);
                        }
                        // echo "\n\n2<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\n";
                        // print_r($found);
                        // echo "\n2>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n\n";
                    }
    
                }
                else {
                    //@ Loop through each, from the last value to the first
                    $found = $independent_extractor($param_value, $value_key);
                }
               
            }
            return $found;
            // return is_array($found) ? $found[0] : $found;
    
        };
    
        //@ Process extraction for string values
        $process_string_values = function( $parent_value, $parameter_bank ) use ($rgx, $extract_values)
        {
            //@ Check if any regex matches exist
            $string_matches = preg_match($rgx, $parent_value,$matches);
    
            //@ where none exist, don't waste time
            if(!$string_matches)
            {
                return $parent_value;
            }
    
            //@ Filter duplicates from matches
            $matches = array_unique($matches);
    
            //@ Loop through each potential match
            foreach ($matches as $key => $value) {
                //@ Ignore false positives
                if($value)
                {
                    //@ Capture the proper parameter name
                    $replacement_key = preg_replace('/({)|(})|(parent\.)/i', '', trim($value));
    
                    //@ Attempt a data extract for the key 
                    $replacement_value = $extract_values($replacement_key,$parameter_bank);
    
                    //@ Conditionally Apply the replacement to the string
                    if( $replacement_value )
                    {
                        $parent_value = preg_replace( "/{$value}/i" ,trim($replacement_value), $parent_value);
                    }
                }
            }
            
            return $parent_value;
    
        };
    
        //@ Process extraction for array values [in the default format [{key,value},{key,value}]]
        $process_array_values = function( $parent_array_value, $parameter_bank ) use ($rgx, $process_string_values )
        {
    
            //@ Define an output variable
            $found = [];
    
            //@ Loop through each array value
            foreach ($parent_array_value as $ky => $val) {
                // //@ Start a local holder 
                // $output = [];
                // //@ Use the string transform option to set the value
                // $output[$val["key"]] = $process_string_values($val["value"], $parameter_bank);
                // //@ Update the transformed values tracker
                // $found[$ky] = $output;        
                $found[$val["key"]] = $process_string_values($val["value"], $parameter_bank);   
            }
    
            //@ Return the transformed value
            return $found;
    
        };
    
        $deterministic_processor = function ($input_value, $data_bank) use ($process_string_values, $process_array_values)
        {
    
            $parsed_value = json_decode($input_value,true) ?? $input_value; 
            $data_bank = json_decode($data_bank,true) ?? $data_bank;
    
           return is_array($parsed_value) ? $process_array_values($parsed_value, $data_bank) : $process_string_values($parsed_value, $data_bank);
    
        };
    
        return $deterministic_processor($canvas, $transform_values);
    
    }


    //@ Handle checks for missing dependencies
    private function containsMissingDependencies( $dependency_array )
    {
        $contains_missing = false;
        foreach ($dependency_array as  $rule_identifier) {
            if(!$this->local_cache[$rule_identifier])
            {
                $contains_missing = true;
                $this->error_log .= "\nCould not find a local reference to the grading rule {$current_parent_rule} required by {$current_rule['rule_id']}\n\t Ensure that {$current_parent_rule} is defined before {$current_rule['rule_id']}\n";
            }
        }
        return $contains_missing;
    }

    //@ Keep track of grades [in multiples where applicable]
    private function addToGradeTracker($rule_id,$call_response)
    {
        //@ Handle first attempts
        if(!$this->local_cache[$rule_id])
        {
            $this->local_cache[$rule_id] =  $call_response;
        }
        //@ Handle tertiary attempts
        else if(is_array($this->local_cache[$rule_id])){
            array_push($this->local_cache[$rule_id], $call_response);
        }
        //@ Handle secondary attempts
        else {
            $this->local_cache[$rule_id] =  [$this->local_cache[$rule_id],$call_data];
        }
        
    }

//@!!!!!!!!!!!!!!!!!!!

    private function gradeCallResultAssessor($grading_call_result, $expected_data, $grading_criteria){
        
        $grading_breakdown = [];

        //@ If expected data is defined, check it
        if($expected_data)
        {

            $parsed_expectations = @"";

            $grading_breakdown = [ 
                "template" => $expected_data,
                "actual" => [], 
                "accuracy" => 0
            ];
        }
        
        //@ Go through each of the grading rules and check the result's compliance

        return $grading_breakdown;
    }

    //@! The grading entry object
    private function doGrading()
    { 

        // print_r($this->active_submission);
        // exit;

        $this->grading_result["logs"].="\n\nAt 'grade executor' - Inintiating data loop.`";

        // echo "\n\nactive rules: ".count($this->active_grading_rules)."\n\n";
        
       //@ Loop through each rule, 
        foreach ($this->active_grading_rules as $rule_key => $active_rule) {

            $log = function ($data) use ($active_rule) {
                // if( $active_rule["rule_id"] == "20008")
                // {
                //     echo "\n\n#{$active_rule["rule_id"]}".$data."\n";
                // }
            };

            $this->grading_result["logs"].=@"\n\n@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@>>>>>>>>>>>\nProcessing rule #{$active_rule['rule_id']}.";

            //@ Lay hold of the parent rules
            $parent_rules = $active_rule['parent_rules'];
            $this->grading_result["logs"].=@"\n#{$active_rule['rule_id']}\tDependencies decalred as ".json_encode( $this->doParentRuleExtraction($parent_rules));

            //@ Keep track of missing dependencies [initially assume the worst]
            $has_missing_dependencies = true;

            //@ If dependencies exist and are in the array format,
            if(is_array($parent_rules))
            {
                //@ Check for missing dependency definitions
                $has_missing_dependencies = $this->containsMissingDependencies($parent_rules);
            }

            //@ PROCEED WITH GRADING PREPARATIONS
            //@ Get the call_method
            $call_method = strtoupper($active_rule["rule_method"]);
            $this->grading_result["logs"].=@"\n#{$active_rule['rule_id']}\tDefined call method as {$call_method}.";

            //@ Format the call path and replace any placeholder value
            $call_path = $this->doValueExtraction($active_rule["rule_path"], $this->doParentRuleExtraction($parent_rules));
            // $log("callPath");
            $this->grading_result["logs"].=@"\n#{$active_rule['rule_id']}\tDefined call path as {$call_path}.";

            //@ The GUZZLER HTTP handler specific paremeters
            $parameter_type = ($call_method == "GET") ? "query" : "json";
            $this->grading_result["logs"].=@"\n#{$active_rule['rule_id']}\tDefined GUZZLE http data as {$parameter_type}.";

            //@ Format the payload parameters
            $payload_params = $this->doValueExtraction($active_rule["rule_parameters"], $this->doParentRuleExtraction($parent_rules));
            $this->grading_result["logs"].=@"\n#{$active_rule['rule_id']}\tDefined body parameters as:\n".@json_encode($payload_params)??$payload_params;

            //@ Format the header parameters
            $header_params = $this->doAuthenticationHeaderSeparation( $this->doValueExtraction($active_rule["rule_headers"], $this->doParentRuleExtraction($parent_rules)) );
            
            $log("header parameters set to \n".json_encode($header_params)."\n\t\twith data\n".json_encode($this->doParentRuleExtraction($parent_rules))."");
            $this->grading_result["logs"].=@"\n#{$active_rule['rule_id']}\tDefined header parameters as:\n".@json_encode($header_params)??$header_params;

            //@ Format the HTTP request object in a 'GUZZLER' compatible way
            $call_data = [
                "{$parameter_type}"    => ($call_method == "GET") ? $payload_params : ($payload_params),
                'http_errors' => false
            ];
            //Optionally add call headers [headers & auth]
            foreach ($header_params as $header_key => $header_value) {
               if(count($header_value)>0)
               {
                $call_data[$header_key] = $header_value;
               }
            }

            $log("HEADER INFO: ".json_encode($call_data));

            //@ Optionally add authentication 
            // if(count(he))

            //@ Execute the rule against the submission/attempt
            $attempt_response = $this->grade_router->call($call_method, $call_path, $call_data);
            $this->grading_result["logs"].=@"\n#{$active_rule['rule_id']}\t.Executed a grading rule validating call and got a ({$attempt_response["status"]}):\n".@json_encode($attempt_response)??$attempt_response;

            //@ Populate the 'local_cache' with the response $grading_result
            // $this->local_cache[$active_rule["rule_id"]] =  $attempt_response;
            $this->addToGradeTracker($active_rule["rule_id"], $attempt_response);
            $this->grading_result["logs"].=@"\n#{$active_rule['rule_id']}\tUpdated the local_cache with the response result.";

            //@ Pass to the grader for expectation matching and grading
            echo "";
            

            // $this->grading_result["logs"].=@"\n#{$active_rule['rule_id']}\t.";
           
        }

        //@ Show a breakdown of the grading procedure
        echo $this->grading_result["logs"];
        exit;

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