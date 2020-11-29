<?php

include __DIR__.'/../../helpers/grade_router.php';

class GradingWorker
{
    private $c;
    private $active_grading_rules;
    private $active_submission;
    private $grade_router;
    private $grading_result = [
        'logs' => '',
        'items' => [],
        'result' => 0,
    ];
    private $local_cache = [];
    private $error_log = '';

    //@ Accepts, [{grading_ruleset},{submission_configuration},{database_connection_object},{mock_flagger}]
    public function __construct($grading_rules, $submission_instance, $connection, $sampling = false)
    {
        $this->c = $connection;
        $this->active_grading_rules = @json_decode($grading_rules, true) ?? $grading_rules;
        $this->active_submission = @json_decode($submission_instance, true) ?? $submission_instance;

        //@ Die a loud death
        if (!isset($grading_rules) || !isset($submission_instance) || !isset($connection)) {
            $error_message = '';
            if (!isset($grading_rules)) {
                $error_message .= "\ngrading rules are not defined";
            }
            if (!isset($submission_instance)) {
                $error_message .= "\na submission instance was not found";
            }
            if (!isset($connection)) {
                $error_message .= "\nno connection object was defined";
            }
            echo json_encode(['status' => 400, 'data' => ['message' => $error_message, 'command' => 'The grading worker was called without all required values', 'actual' => $grading_rules]]);
            exit;
        }

        $this->grading_result['logs'] .= @"\n\n🌟 🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟\nCommencing with grading for `".$this->active_submission['attempt_name'].'` ('.$this->active_submission['attempt_student_identifier'].")\n🌟 🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟🌟\n";

        //@ Prep the router object
        $this->grade_router = new GradeRouter($this->active_submission['attempt_main_path']);
        $this->grading_result['logs'] .= "\n\nSet the main grading url to {$this->active_submission['attempt_main_path']}\n";

        //@ start the actual grading
        if (!$sampling) {
            return $this->doGrading();
        }
    }

    // @ Handles the validation of rules prior to saving them
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


    // ------------------------------------------------

    public function safeJSONEncode($array)
    {
        // json_encode (array_walk_recursive ($array, function (&$a) {
        //     if (is_string ($a)) {
        //         $a = utf8_encode ($a);
        //     }
        // }));
       return json_encode($array,JSON_FORCE_OBJECT);
    }

    //@ check if is associative array
    private function isAssociativeArray(array $arr)
    {
        return ([] === $arr)
        ? false
        : array_keys($arr) !== range(0, count($arr) - 1);
    }

    //@ Handle auth header separation
    private function doAuthenticationHeaderSeparation($all_headers)
    {
        $result = ['headers' => []];

        $handle_addition = function ($header_key, $header_value, $basket) {
            // if(strtolower( preg_replace('/\s+/', '', $header_key)) == "authorization")
            // {
            //     $basket[auth] = [null, $header_value];
            // }
            // else {
            $basket['headers'][$header_key] = $header_value;
            // }
            return $basket;
        };

        if (is_array($all_headers)) {
            foreach ($all_headers as $header_key => $header_value) {
                if (is_array($header_value)) {
                    foreach ($header_value as $key => $value) {
                        $result = $handle_addition($key, $value, $result);
                    }
                } else {
                    $result = $handle_addition($header_key, $header_value, $result);
                }
            }
        }

        return $result;
    }

    //@ Handle parent rule extraction
    private function doParentRuleExtraction($parent_rule_array)
    {
        $found = [];
        foreach ($parent_rule_array as $parent_id) {
            if (@$this->local_cache[$parent_id]) {
                array_push($found, $this->local_cache[$parent_id]);
            }
        }

        return $found;
    }

    //@ Handle parameter substitution
    private function doValueExtraction($canvas, $transform_values)
    {
        $rgx = '/({{.*}})|({.*})/i';

        $that = $this;

        $isAssoc = function (array $arr) use ($that) {
            return $that->isAssociativeArray($arr);
        };

        // var_dump($isAssoc);
        // exit;

        //@ handle independent extractions ["last as first"]
        $independent_extractor = function ($parameter_bank = [], $value_key = '') use ($isAssoc) {
            // echo "\n\n@ independent extractor\n";

            $parameter_bank = @json_decode($parameter_bank, true) ?? $parameter_bank;

            // print_r($parameter_bank);

            $found = null;

            if ($isAssoc($parameter_bank)) {
                // echo "\n\n@ is assoc\n\n";
                // print_r($parameter_bank);

                //@ Attempt a direct extraction
                $found = @$parameter_bank[$value_key];
            } else {
                // echo "\n\n@ is not assoc\n\n";
                // print_r($parameter_bank);

                foreach (array_reverse($parameter_bank) as $key => $value) {
                    if ($found) {
                        continue;
                    }
                    if (@$value[$value_key]) {
                        $found = @$value[$value_key];
                    }
                }
            }

            return $found;
        };

        $extract_from_assoc = function ($param_value, $value_key) use ($independent_extractor) {
            //@ Attempt a direct extraction [from the main object]
            $found = @$param_value[$value_key];

            //@ Attempt a body extraction
            if (!$found && @$param_value['content']) {
                // print_r($param_value);
                $found = $independent_extractor($param_value['content'], $value_key);
                // echo "\n\n1<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\n";
                 // print_r($found);
                 // echo "\n1>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n\n";
            }

            //@ Attempt a headers extraction
            if (!$found && $param_value['headers']) {
                $found = $independent_extractor($param_value['headers'], $value_key);
                if (is_array($found)) {
                    $found = $found[0] ?? json_encode($found);
                }
                // echo "\n\n2<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\n";
                 // print_r($found);
                 // echo "\n2>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n\n";
            }

            return $found;
        };

        //@ Get the value matching the key from the parameter bank
        $extract_values = function ($value_key, $parameter_bank) use ($isAssoc, $independent_extractor, $extract_from_assoc) {
            //@ Attempt to convert the conversion pool to an array
            $parameter_bank = @json_decode($parameter_bank, true) ?? $parameter_bank;

            $found = null;

            if (!$isAssoc($parameter_bank)) {
                foreach ($parameter_bank as $param_key => $param_value) {
                    if ($found) {
                        continue;
                    }

                    $param_value = !is_array($param_value) ? json_decode($param_value, true) ?? $param_value : $param_value;

                    //@ Check if the array is associative
                    if ($isAssoc($param_value)) {
                        $found = $extract_from_assoc($param_value, $value_key);
                    } else {
                        //@ Loop through each, from the last value to the first
                        $found = $independent_extractor($param_value, $value_key);
                    }
                }
            } else {
                $found = $extract_from_assoc($parameter_bank, $value_key);
            }

            return $found;
            // return is_array($found) ? $found[0] : $found;
        };

        //@ Process extraction for string values
        $process_string_values = function ($parent_value, $parameter_bank) use ($rgx, $extract_values) {
            //@ Check if any regex matches exist
            $string_matches = preg_match($rgx, $parent_value, $matches);

            //@ where none exist, don't waste time
            if (!$string_matches) {
                return $parent_value;
            }

            //@ Filter duplicates from matches
            $matches = array_unique($matches);

            //@ Loop through each potential match
            foreach ($matches as $key => $value) {
                //@ Ignore false positives
                if ($value) {
                    //@ Capture the proper parameter name
                    $replacement_key = preg_replace('/({)|(})|(parent\.)/i', '', trim($value));

                    //@ Attempt a data extract for the key
                    $replacement_value = $extract_values($replacement_key, $parameter_bank);

                    //@ Conditionally Apply the replacement to the string
                    if ($replacement_value) {
                        $parent_value = preg_replace("/{$value}/i", trim($replacement_value), $parent_value);
                    }
                }
            }

            return $parent_value;
        };

        //@ Process extraction for array values [in the default format [{key,value},{key,value}]]
        $process_array_values = function ($parent_array_value, $parameter_bank) use ($rgx, $process_string_values) {
            //@ Define an output variable
            $found = [];

            // print_r("\n**********************************************\nENTERED ARRAY_VALUES\n**************************************\n");

            //@ Loop through each array value
            foreach ($parent_array_value as $ky => $val) {
                // //@ Start a local holder
                // $output = [];
                // //@ Use the string transform option to set the value
                // $output[$val["key"]] = $process_string_values($val["value"], $parameter_bank);
                // //@ Update the transformed values tracker
                // $found[$ky] = $output;
                $found[$val['key']] = $process_string_values($val['value'], $parameter_bank);
            }

            //@ Return the transformed value
            return $found;
        };

        $deterministic_processor = function ($input_value, $data_bank) use ($process_string_values, $process_array_values) {
            $parsed_value = @json_decode($input_value, true) ?? $input_value;
            $data_bank = @json_decode($data_bank, true) ?? $data_bank;

            return is_array($parsed_value) ? $process_array_values($parsed_value, $data_bank) : $process_string_values($parsed_value, $data_bank);
        };

        return $deterministic_processor($canvas, $transform_values);
    }

    //@ Handle checks for missing dependencies
    private function containsMissingDependencies($dependency_array)
    {
        $contains_missing = false;
        foreach ($dependency_array as  $rule_identifier) {
            if (!$this->local_cache[$rule_identifier]) {
                $contains_missing = true;
                $this->error_log .= "\nCould not find a local reference to the grading rule {$current_parent_rule} required by {$current_rule['rule_id']}\n\t Ensure that {$current_parent_rule} is defined before {$current_rule['rule_id']}\n";
            }
        }

        return $contains_missing;
    }

    //@ Keep track of grades [in multiples where applicable]
    private function addToGradeTracker($rule_id, $call_response)
    {
        //@ Handle first attempts
        if (!$this->local_cache[$rule_id]) {
            $this->local_cache[$rule_id] = $call_response;
        }
        //@ Handle tertiary attempts
        elseif (is_array($this->local_cache[$rule_id])) {
            array_push($this->local_cache[$rule_id], $call_response);
        }
        //@ Handle secondary attempts
        else {
            $this->local_cache[$rule_id] = [$this->local_cache[$rule_id], $call_response];
        }
    }

    //@!!!!!!!!!!!!!!!!!!!

    //@ Test the grading call against the non-parameter rule criteria
    private function computeCallResultAccuracy($grading_call_result = [], $grading_rule_object = [], $logger)
    {
        $grading_call_result = @json_decode($grading_call_result, true) ?? $grading_call_result;
        $grading_rule_object = @json_decode($grading_rule_object, true) ?? $grading_rule_object;
        $grading_rule_combo = is_array($grading_rule_object) ? json_decode($grading_rule_object['rule_grading'], true) ?? $grading_rule_object['rule_grading'] : '';

        $grading_breakdown = [
            'result' => 0,
            'actual' => [],
        ];

        $that = $this;

        $isAssoc = function (array $arr) use ($that) {
            return $that->isAssociativeArray($arr);
        };

        //@ Check for matches
        $find_structured_matches = function ($parameter_name, $expected_value, $actual_result) use ($grading_rule_combo) {
            $out = ['explanation' => '', 'result' => 0];

            $actual_result = @json_encode($actual_result);
            $expected_value = @json_encode($expected_value);

            //@ fetch the parameter from the call result
            if ($expected_value == $actual_result || (false !== stripos(preg_replace('/(\")|(\')/i', '', stripslashes($actual_result)), preg_replace('/(\")|(\')/i', '', stripslashes($expected_value))))) {
                $out['explanation'] .= "\n\nThe expected value for '{$parameter_name}' ({$expected_value}) perfectly matches as ".($actual_result);
                $out['result'] = $grading_rule_combo[$parameter_name]['match'];
            } else {
                $actual_result = @json_decode($actual_result, true) ?? $actual_result;
                $actual_result = is_array($actual_result) ? $actual_result[0] ?? $actual_result : $actual_result;

                //@ Loop through the matches array
                foreach ($grading_rule_combo[$parameter_name]['matches'] as $key => $value) {
                    if (0 != $out['result']) {
                        continue;
                    }

                    if ($actual_result == $value['alternative'] || (false !== stripos($actual_result, $value['alternative']))) {
                        $out['explanation'] .= "\n\nThe expected value for secondary '{$parameter_name}' ({$value['alternative']}) matches the alternative as  {$actual_result} ";
                        $out['result'] = $value['match'];
                    } else {
                        $out['explanation'] .= "\n\nNo match was found for secondary '{$parameter_name}'   ({$value['alternative']}) as {$actual_result}.";
                        $out['result'] = $value['no_match'];
                    }
                }
            }

            if (0 == $out['result']) {
                $out['explanation'] .= "\n\nNo match was found in the specified criteria where '{$parameter_name}' == '{$actual_result}', expected ({$expected_value})";
                $out['result'] = $grading_rule_combo[$parameter_name]['no_match'];
            }

            return $out;
        };

        //@ Ensure that both datasets are in the proper format
        if (is_array($grading_call_result) && is_array($grading_rule_combo)) {
            //@ Define the comparison parameters

            $call_result_extraction_parameters = ['status_code' => 'status', 'mime_type' => 'Content-Type'];
            $rule_extraction_parameters = ['status_code' => 'rule_expected_status_code', 'mime_type' => 'rule_expected_data_type'];
            // :["application\/json; charset=utf-8"]

            if ($grading_call_result[0]) {
                $grading_call_result = $grading_call_result[0];
            }

            //@ Compare response data types
            //@ compare rule matches [status_codes, mime_type]
            foreach ($call_result_extraction_parameters as $rule_parameter => $result_parameter) {
                $value_extracted_from_call = ($grading_call_result[$result_parameter]) ?? $grading_call_result['headers'][$result_parameter];

                $output = $find_structured_matches($rule_parameter, $grading_rule_object[$rule_extraction_parameters[$rule_parameter]], ($value_extracted_from_call));

                // echo "\n\n{$rule_parameter}\t\t{$rule_extraction_parameters[$rule_parameter]}\n\n";
                // echo "\n\n".json$value_extracted_from_call."\n\n";

                array_push($grading_breakdown['actual'], $output);
                $grading_breakdown['result'] += $output['result'];
            }
        }

        return $grading_breakdown;
    }

    //@ Performs grade score computations depending on expected output
    //@ { $call_result_array, $complex_open_expected }
    //@ { call_result, expected_data }
    //@ convert the simple structures into a [ ["key" => "", "value" => ""] ] format
    private function gradeCallResultAssessor($grading_call_result, $grading_rule_object, $logger)
    {
        //@ Set the basic grading breakdown
        $grading_breakdown = [
            'template' => 'INVALID GRADING OBJECT DEFINED',
            'nested' => false,
            'parsed' => [],
            'actual' => [],
            'grading' => [],
            'result' => 0,
        ];

        //@ Convert rule object to array
        $grading_rule_object = @json_decode($grading_rule_object, true) ?? $grading_rule_object;
        
        //@ Ensure that the grading rule object is workable
        if (is_array($grading_rule_object)) {
            //@ Extract the expected_data from the current rule
            $expected_data = $grading_rule_object['rule_expected_data'];

            //@ Update the grading breakdown template with the expected data
            $grading_breakdown['template'] = $expected_data;


            // $call_res = @json_decode($grading_call_result,true) ?? $grading_call_result;
            //@ Ensure that the grading call result was not an error
            if($grading_call_result["error"])
            {
                $grading_breakdown["grading"]["breakdown"] = 'The call triggered an error, no marks to assign';
                $grading_breakdown['grading']['result'] = 0;
                $logger("The call to the grading endpoint triggered an error, no marks can be warded for this");
                return $grading_breakdown;
            }


            //@ Setup an output template
            $output_data = [];

            //@>>>>>>>>>>>>>>>>>>>>>>>>>>> EXPECTED_DATA (out of 100 points)

            //@ If expected data is defined, check it
            if ($expected_data && '' != $expected_data) {
                //@ Attempt to convert to json
                $parsed_expectations = @json_decode($expected_data, 2) ?? ('string' == gettype($expected_data)) ? trim($expected_data) : $expected_data;

                //@ Value tester helper closure method
                $has_string = function ($test_string) use ($parsed_expectations) {
                    return false !== strpos($parsed_expectations, $test_string);
                };

                //@ Extract the value from the provided call result
                $extract_value_from_call_result = function ($key) use ($grading_call_result) {
                    $key = (strpos($key, '{')) ? $key : '{'.$key.'}';
                    $resultant_value = $this->doValueExtraction($key, $grading_call_result);

                    return $key == $resultant_value ? null : $resultant_value;
                };

                //@ Get the proper value from the passed token
                $extract_key_value_as_array = function ($parent_value) {
                    $tmp_holder = [];
                    foreach (explode(',', preg_replace('/({)|(})|(parent\.)/i', '', trim($parent_value))) as $key => $value) {
                        $tmp_holder[$value] = '';
                    }

                    return $tmp_holder;
                };

                //@ Transform an array to output
                $get_data_from_array = function ($parsed_array) use ($extract_value_from_call_result) {
                    $item_total = count($parsed_array) ?? 1;
                    $points_per_match = 100 / $item_total;
                    $points_garnered = 0;
                    $output_data = [ "explanation" => "" ];
                    $output_data['explanation'] = "Found {$item_total} expected items each worth {$points_per_match} points";
                    //@ Loop through each item and get its value
                    foreach ($parsed_array as $key => $value) {
                        if (@$value['key']) {
                            //@ Attempt to extract a value from the data
                            $output_data[$value['key']] = $extract_value_from_call_result($value['key']);
                            //@ if the data value exists, add a point to match
                            if ($output_data[$value['key']]) {
                                $points_garnered += $points_per_match;
                                $output_data['explanation'] .= "\n\nAdded {$points_per_match} to the score for the existing key '{$value['key']}'\nResult: \t '".@json_encode($output_data[$value['key']])."'";
                            }                           
                        } else {
                            $output_data[$key] = $extract_value_from_call_result($key);
                            if ($output_data[$key]) {
                                $points_garnered += $points_per_match;
                                $output_data['explanation'] .= "\n\nAdded {$points_per_match} to the score for the existing key '{$key}'\nResult: \t '".@json_encode($output_data[$key])."'";
                            }
                        }
                    }

                    //@ Add the results to the output object
                    $output_data['result'] = $points_garnered;

                    return $output_data;
                };

                //@ Do the actual grade score computation
                $compare_expected_with_call_results = function ($expected_format = [], $formated_call_result = [], $no_match_weight = 50) {
                    $out = ['explanation' => '', 'result' => 0];

                    $item_total = count($expected_format) ?? 1;
                    $points_per_item = 100 / $item_total;

                    $out['explanation'] = "Found {$item_total} expected items each worth {$points_per_item} points";

                    $cumulative_points = 0;
                    //@ loop through each expected results item
                    foreach ($expected_format as $key => $value) {
                        if (array_key_exists($key, $formated_call_result)) {
                            // $out["explanation"] .= "\n ${key}";
                            $new_points = ($value)
                            ? ($value == $formated_call_result[$key])
                                ? $points_per_item
                                : $points_per_item * 0 //((intval($no_match_weight)??50)/100)
                            : (null == $formated_call_result[$key]) ? 0 : $points_per_item;
                            $encoded_value = @json_encode($value);
                            $encoded_value = ($encoded_value == "" || $encoded_value == NULL ) ? '<API RESULT>' : $encoded_value;
                            $out['explanation'] .= "\n\nAdded {$new_points} to the score for matching the key '{$key}'\nResult: \texpected '".$encoded_value."' \tgot '".@json_encode($formated_call_result[$key])."'";
                        } else {
                            $out['explanation'] .= "\n\nNo points scored for the key '{$key}'\nResult: \texpected '".@json_encode($value)."'";
                        }
                        //@ Add up the points;
                        $cumulative_points += $new_points;
                    }

                    $out['explanation'] .= "\n\n{$cumulative_points} points scored for matching rules";
                    $out['result'] = round($cumulative_points, 2, PHP_ROUND_HALF_DOWN);
                    //@ give the judgement on the score
                    return $out;
                };

                //@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

                //@ If expectations is of type, array [i.e $explicit_expected]
                if (is_array($parsed_expectations)) {
                    $output_data = $get_data_from_array($parsed_expectations);
                }
                //@  if has curly but no parent square braces  [i.e $simple_open_expected, $complex_open_expected]
                elseif (!$has_string('[') && !$has_string(']') && $has_string('}') && $has_string('{')) {
                    $extracted_arr = $extract_key_value_as_array($parsed_expectations);
                    $output_data = $get_data_from_array($extracted_arr);
                }
                //@ If is array of simple or open types
                elseif (preg_match('/^\[.*\]$/i', trim($parsed_expectations)) && $has_string('{') && $has_string('}')) {
                    //@ keep a note that it is nested
                    $grading_breakdown['nested'] = true;

                    //@ Remove the JS array placeholder items
                    $altered_expectations = preg_replace('/^\[|\]$/i', '', trim($parsed_expectations));

                    $extracted_arr = $extract_key_value_as_array($altered_expectations);
                    $output_data = $get_data_from_array($extracted_arr);
                }

                //@ Update the 'expected_data' section of the grading object
                $grading_breakdown['grading']['expected_data'] = $compare_expected_with_call_results($extracted_arr ?? $parsed_expectations, $output_data);
            } else {
                //@ If no response is expected, assign the highest score
                $grading_breakdown['grading']['expected_data'] = ['result' => 100, 'explanation' => 'Could not find rules to verify against, full score assigned'];
            }

            //@ Update the grading breakdown object result with the parameter adherance result
            $grading_breakdown['grading']['result'] += $grading_breakdown['grading']['expected_data']['result'] ?? 0;

            //@>>>>>>>>>>>>>>>>>>>>>>>>>>> RESPONSE_FORMAT (out of 200 points)

            //@ Go through each of the grading rules and check the general result's compliance (status_code,mime_type, ...)
            $grading_breakdown['grading']['response_format'] = $this->computeCallResultAccuracy($grading_call_result, $grading_rule_object, $logger);

            //@ Update the grading breakdown object result with the general rule adherance result
            $grading_breakdown['grading']['result'] += $grading_breakdown['grading']['response_format']['result'] ?? 0;

            //@ Add a reference to the actual call result [value]
            $grading_breakdown['actual'] = $output_data;
            $grading_breakdown['parsed'] = $extracted_arr ?? $parsed_expectations;

            //@ Log the current grade result explanation
            $logger(@$grading_breakdown['grading']['expected_data']["explanation"]);
        }

        //@ Attach the grading result to the final result
        $grading_breakdown['result'] = $grading_breakdown['grading']['result'];

        //@ Get back to the people with the results
        return $grading_breakdown;
    }

    //@! The grading entry object
    private function doGrading()
    {
        // print_r($this->active_submission);
        // exit;

        $this->grading_result['logs'] .= "\n\nAt 'grade executor' - Inintiating data loop.`";

        // echo "\n\nactive rules: ".count($this->active_grading_rules)."\n\n";

        //@ Loop through each rule,
        foreach ($this->active_grading_rules as $rule_key => $active_rule) {
            $log = function ($log_data) use ($active_rule) {
                $this->grading_result['logs'] .= @"\n#{$active_rule['rule_id']}\t\t".$log_data."\n";
            };

            $this->grading_result['logs'] .= @"\n\n\n☀️ ☀️ ☀️ ☀️ ☀️ ☀️ ☀️ ☀️ ☀️ ☀️ ☀️ ☀️ ☀️ ☀️ ☀️ ☀️ ☀️ ☀️ ☀️ ☀️ ☀️ ☀️ ☀️ ☀️ >\nProcessing rule #{$active_rule['rule_id']}.\n";

            //@ Lay hold of the parent rules
            $parent_rules = $active_rule['parent_rules'];
            $log('Dependencies decalred as '.json_encode($this->doParentRuleExtraction($parent_rules)));

            //@ Keep track of missing dependencies [initially assume the worst]
            $has_missing_dependencies = true;

            //@ If dependencies exist and are in the array format,
            if (is_array($parent_rules)) {
                //@ Check for missing dependency definitions
                $has_missing_dependencies = $this->containsMissingDependencies($parent_rules);
            }

            //@ PROCEED WITH GRADING PREPARATIONS
            //@ Get the call_method
            $call_method = strtoupper($active_rule['rule_method']);
            $log("Defined call method as {$call_method}.");

            //@ Format the call path and replace any placeholder value
            $call_path = $this->doValueExtraction($active_rule['rule_path'], $this->doParentRuleExtraction($parent_rules));
            $log("Defined call path as {$call_path}.");

            //@ The GUZZLER HTTP handler specific paremeters
            $parameter_type = ('GET' == $call_method) ? 'query' : 'json';
            $log("Defined GUZZLE http data as {$parameter_type}.");

            //@ Format the payload parameters
            $payload_params = $this->doValueExtraction($active_rule['rule_parameters'], $this->doParentRuleExtraction($parent_rules));
            $log("Defined body parameters as:\n".@json_encode($payload_params) ?? $payload_params);

            //@ Format the header parameters
            $header_params = $this->doAuthenticationHeaderSeparation($this->doValueExtraction($active_rule['rule_headers'], $this->doParentRuleExtraction($parent_rules)));
            $log("Defined header parameters as:\n".@json_encode($header_params) ?? $header_params);

            //@ Format the HTTP request object in a 'GUZZLER' compatible way
            $call_data = [
                "{$parameter_type}" => ('GET' == $call_method) ? $payload_params : ($payload_params),
                'http_errors' => false,
            ];
            //Optionally add call headers [headers & auth]
            foreach ($header_params as $header_key => $header_value) {
                if (count($header_value) > 0) {
                    $call_data[$header_key] = $header_value;
                }
            }
            //@ Optionally add authentication
            // if(count(he))

            //@ Execute the rule against the submission/attempt
            $attempt_response = $this->grade_router->call($call_method, $call_path, $call_data);
            $log(".Executed a grading rule validating call and got a ({$attempt_response['status']}):\n".@json_encode($attempt_response) ?? $attempt_response);

            //@ Populate the 'local_cache' with the response $grading_result
            // $this->local_cache[$active_rule["rule_id"]] =  $attempt_response;
            $this->addToGradeTracker($active_rule['rule_id'], $attempt_response);
            $log('Updated the local_cache with the call response result.');

            //@ Pass to the grader for expectation matching and grading
            $rule_grading_result = $this->gradeCallResultAssessor($attempt_response, $active_rule, $log);
            $log("Received the rule grading result as {$rule_grading_result['result']}  of 300 points");

            //@ Update the current 'global' grading result

            $this->grading_result['result'] += $rule_grading_result['result'] ?? 0;
            $log("Incremented the cumulative score by '{$rule_grading_result['result']}' to a current cumulative of '{$this->grading_result['result']}'.");

            //@ Add the grading result data to the global result 'items' store
            array_push($this->grading_result['items'], $rule_grading_result);
            $log("Updated the 'grade result' rule grading 'items' reference.");

            $log("🔥🔥🔥🔥END OF RULE🔥🔥🔥🔥\n\n");
        }

        $max_score = count($this->grading_result['items']) * 300;

        $update_date = date('Y-m-d H:i:s');

        $attempt_grade = json_encode([
            'total' => $this->grading_result['result'],
            'possible' => $max_score,
            'percentage' => round((($this->grading_result['result'] / ($max_score??1)) * 100), 2, PHP_ROUND_HALF_UP), ]);
        

        //@ Keep the grading log up to date
        $this->grading_result['logs'] .= "\n\n===============================================================================\n\n";
        $this->grading_result['logs'] .= "\tTOTAL: \t{$this->grading_result['result']}\n\tPOSSIBLE: \t{$max_score}\n\tPERCENTAGE: \t".round((($this->grading_result['result'] / ($max_score ?? 1)) * 100), 2, PHP_ROUND_HALF_UP)."(%)";
        $this->grading_result['logs'] .= "\n\n===============================================================================\n\n";

        // $attempt_grade_breakdown = json_encode($this->grading_result);
        $attempt_grade_breakdown = $this->safeJSONEncode($this->grading_result);

        // $attempt_grade_breakdown_link = __DIR__.'/grades/'.md5($attempt_grade_breakdown).'.txt';
        // file_put_contents($attempt_grade_breakdown_link, $attempt_grade_breakdown);

        // json_encode($this->grading_result)
        //@ store the grading breakdown for the attempt
        $query_string = 'UPDATE attempts 
        SET attempt_grading_time=?, 
        attempt_grade_breakdown=?,
        attempt_grade=?, 
        attempt_grade_complete=1,
        updated_at=? 
        WHERE 
        attempt_id=? 
        AND 
        attempt_assignment=?';

        $query_data = [
            $update_date,
            $attempt_grade_breakdown,
            $attempt_grade,
            $update_date,
            $this->active_submission['attempt_id'],
            $this->active_submission['attempt_assignment'],
        ];

        //@ Perform the actual database update
        $update_result = $this->c->con->prepare($query_string)->execute($query_data);

        return true;
       
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
