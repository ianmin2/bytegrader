<?php

//@ S_O STATIC DON'T COPY
function doValueExtraction($canvas, $transform_values)
{
    $rgx = '/({{.*}})|({.*})/i';

    $isAssoc = function (array $arr) {
        if ([] === $arr) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    };

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

    //@ Get the value matching the key from the parameter bank
    $extract_values = function ($value_key, $parameter_bank) use ($isAssoc, $independent_extractor) {
        //@ Attempt to convert the conversion pool to an array
        $parameter_bank = @json_decode($parameter_bank, true) ?? $parameter_bank;

        // print_r($parameter_bank);
        $found = null;
        foreach ($parameter_bank as $param_key => $param_value) {
            if ($found) {
                continue;
            }
            $param_value = !is_array($param_value) ? json_decode($param_value, true) : $param_value;

            //@ Check if the array is associative
            if ($isAssoc($param_value)) {
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
            } else {
                //@ Loop through each, from the last value to the first
                $found = $independent_extractor($param_value, $value_key);
            }
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
        $parsed_value = json_decode($input_value, true) ?? $input_value;
        $data_bank = json_decode($data_bank, true) ?? $data_bank;

        return is_array($parsed_value) ? $process_array_values($parsed_value, $data_bank) : $process_string_values($parsed_value, $data_bank);
    };

    return $deterministic_processor($canvas, $transform_values);
}
//@ E_O STATIC DON'T COPY

$call_result_object = $parent_data_object = '[{"error":false,"body":{},"headers":{"X-Powered-By":["@framify"],"Access-Control-Allow-Headers":["Origin, X-Requested-With,Content-Type, Access-Control-Allow-Origin, Authorization, Origin, Accept, x-auth-token"],"Access-Control-Allow-Origin":["*"],"Access-Control-Allow-Methods":["GET, POST, PUT, DELETE, OPTIONS"],"Content-Type":["text\/html; charset=utf-8"],"Content-Length":["319"],"ETag":["W\/\"13f-Br7ZsJNXpwOaM\/3v16GG+EDQon8\""],"Vary":["Accept-Encoding"],"Date":["Mon, 16 Nov 2020 02:14:45 GMT"],"Connection":["close"]},"status":200,"content":{"token":"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZW1iZXJfaWQiOiIzNiIsInVzZXJuYW1lIjoiaWFubWluMjIiLCJqb2luZWQiOiIyMDIwLTExLTE2VDAwOjM5OjA5LjgxM1oiLCJhY3RpdmUiOnRydWUsIm5hbWUiOiJJYW4gS2FtYXUiLCJpYXQiOjE2MDU0OTI4ODUsImV4cCI6MzYwMDE2MDU0OTI4ODUsImlzcyI6IjE5Mi4xNjguMS4xODQifQ.pe5Qrv4unFJ8oaibgJFA0GIil_jKAm-N6B0kXckj6yc"},"response":{}}]';

$call_result_array = $parent_data_array = '[{"error":false,"body":{},"headers":{"X-Powered-By":["@framify"],"Access-Control-Allow-Headers":["Origin, X-Requested-With,Content-Type, Access-Control-Allow-Origin, Authorization, Origin, Accept, x-auth-token"],"Access-Control-Allow-Origin":["*"],"Access-Control-Allow-Methods":["GET, POST, PUT, DELETE, OPTIONS"],"Content-Type":["application\/json; charset=utf-8"],"Content-Length":["402"],"ETag":["W\/\"192-a65NeniwK1s07OjAXNB2h69BMfc\""],"Vary":["Accept-Encoding"],"Date":["Wed, 18 Nov 2020 02:11:34 GMT"],"Connection":["close"]},"status":200,"content":[{"service_id":"1","service_name":"SMS","service_code":"BX_SMS","service_added":"2020-10-18T17:20:48.184Z","service_active":true},{"service_id":"5","service_name":"SERVICEPI","service_code":"SRV1","service_added":"2020-10-18T21:45:44.579Z","service_active":true},{"service_id":"6","service_name":"realService","service_code":"real one","service_added":"2020-11-16T02:51:19.396Z","service_active":true}],"response":{}}]';

$grading_rule_object_parsed = '
{
    "rule_id": 10004,
    "rule_method": "POST",
    "rule_path": "/sample/with/Alternatives",
    "rule_name": "Alternates",
    "rule_description": "A sample one with alternative grading",
    "rule_assignment": 6,
    "rule_expected_status_code": 200,
    "rule_expected_data_type": "application/json",
    "rule_expected_data": null,
    "rule_headers": "[{"key":"Age","value":"23"},{"key":"Custom","value":"Val"}]",
    "rule_parameters": "[{"key":"Whatever","value":"Goes"},{"key":"Sad","value":"I know"}]",
    "rule_grading": "{ "verb": { "weight": 100, "match": 100, "no_match": 0, "matches": [{ "alternative": "DELETE", "match": 75, "no_match": 0 }] }, "path": { "weight": 100, "match": 100, "no_match": 0, "matches": [{ "alternative": "/lost", "match": 84, "no_match": 0 }] }, "status_code": { "weight": 100, "match": 100, "no_match": 0, "matches": [{ "alternative": "404", "match": 25, "no_match": 0 }] }, "mime_type": { "weight": 100, "match": 100, "no_match": 0, "matches": [{ "alternative": "text/html", "match": "25", "no_match": 0 }] } }",
    "created_at": "2020-09-27 14:11:25.4500000 +00:00",
    "updated_at": null
  }
';

$grading_rule_object = '
{
    "rule_id": 10004,
    "rule_method": "POST",
    "rule_path": "/sample/with/Alternatives",
    "rule_name": "Alternates",
    "rule_description": "A sample one with alternative grading",
    "rule_assignment": 6,
    "rule_expected_status_code": 200,
    "rule_expected_data_type": "application/json",
    "rule_expected_data": null,
    "rule_headers": "[{\"key\":\"Age\",\"value\":\"23\"},{\"key\":\"Custom\",\"value\":\"Val\"}]",
    "rule_parameters": "[{\"key\":\"Whatever\",\"value\":\"Goes\"},{\"key\":\"Sad\",\"value\":\"I know\"}]",
    "rule_grading": "{ \"verb\": { \"weight\": 100, \"match\": 100, \"no_match\": 0, \"matches\": [{ \"alternative\": \"DELETE\", \"match\": 75, \"no_match\": 0 }] }, \"path\": { \"weight\": 100, \"match\": 100, \"no_match\": 0, \"matches\": [{ \"alternative\": \"\\/lost\", \"match\": 84, \"no_match\": 0 }] }, \"status_code\": { \"weight\": 100, \"match\": 100, \"no_match\": 0, \"matches\": [{ \"alternative\": \"404\", \"match\": 25, \"no_match\": 0 }] }, \"mime_type\": { \"weight\": 100, \"match\": 100, \"no_match\": 0, \"matches\": [{ \"alternative\": \"text\\/html\", \"match\": \"25\", \"no_match\": 0 }] } }",
    "created_at": "2020-09-27 14:11:25.4500000 +00:00",
    "updated_at": null
}
';

$sample_string = '/services/{{parent.service_id}}';
$sample_array = json_encode([
    [
        'key' => 'name',
        'value' => '{{parent.ETag}}',
    ],
    [
        'key' => 'identifier',
        'value' => 'Authorization {token}',
    ],
]);
$simple_array = json_encode([
    'eTag' => '',
    'token' => '',
]);

///{{parent.test_number}}/{parent.phantom_id}

    //@ Test the grading call against the non-parameter rule criteria
    function computeCallResultAccuracy($grading_call_result = [], $grading_rule_object = [])
    {
        $grading_call_result = json_decode($grading_call_result, true) ?? $grading_call_result;
        $grading_rule_object = json_decode($grading_rule_object, true) ?? $grading_rule_object;
        $grading_rule_combo = is_array($grading_rule_object) ? json_decode($grading_rule_object['rule_grading'], true) ?? '' : '';

        $grading_breakdown = [
            'result' => 0,
            'actual' => [],
        ];

        $isAssoc = function (array $arr) {
            if ([] === $arr) {
                return false;
            }

            return array_keys($arr) !== range(0, count($arr) - 1);
        };

        //@ Check for matches
        $find_structured_matches = function ($parameter_name, $expected_value, $actual_result) use ($grading_rule_combo) {
            $out = ['explanation' => '', 'result' => 0];

            $actual_result = json_encode($actual_result);
            $expected_value = json_encode($expected_value);

            //@ fetch the parameter from the call result
            if ($expected_value == $actual_result || (false !== stripos(preg_replace('/"|\\/i', '', $actual_result), preg_replace('/"|\\/i', '', $expected_value)))) {
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
                $out['explanation'] .= "\n\nNo match was found in the specified criteria where '{$parameter_name}' == '{$actual_result}'.";
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
    function gradeCallResultAssessor($grading_call_result, $grading_rule_object)
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
        $grading_rule_object = @json_decode($grading_rule_object, true);

        //@ Ensure that the grading rule object is workable
        if (is_array($grading_rule_object)) {
            //@ Extract the expected_data from the current rule
            $expected_data = $grading_rule_object['rule_expected_data'];

            //@ Update the grading breakdown template with the expected data
            $grading_breakdown['template'] = $expected_data;

            //@ Setup an output template
            $output_data = [];

            //@ If expected data is defined, check it
            if ($expected_data && '' != $expected_data) {
                //@ Attempt to convert to json
                $parsed_expectations = @json_decode($expected_data, 2) ?? trim($expected_data);

                //@ Value tester helper closure method
                $has_string = function ($test_string) use ($parsed_expectations) {
                    return false !== strpos($parsed_expectations, $test_string);
                };

                //@ Extract the value from the provided call result
                $extract_value_from_call_result = function ($key) use ($grading_call_result) {
                    $key = (strpos($key, '{')) ? $key : '{'.$key.'}';
                    $resultant_value = doValueExtraction($key, $grading_call_result);

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
                    $output_data = [];
                    //@ Loop through each item and get its value
                    foreach ($parsed_array as $key => $value) {
                        if (@$value['key']) {
                            $output_data[$value['key']] = $extract_value_from_call_result($value['key']);
                        } else {
                            $output_data[$key] = $extract_value_from_call_result($key);
                        }
                    }

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
                            $out['explanation'] .= "\n\nAdded {$new_points} to the score for matching the key '{$key}'\nResult: \texpected '".@json_encode($value)."' \tgot '".@json_encode($formated_call_result[$key])."'";
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

            //@ Go through each of the grading rules and check the general result's compliance (status_code,mime_type, ...)
            $grading_breakdown['grading']['response_format'] = $this->computeCallResultAccuracy($grading_call_result, $grading_rule_object);

            //@ Update the grading breakdown object result with the general rule adherance result
            $grading_breakdown['result'] += $grading_breakdown['grading']['response_format']['result'] ?? 0;

            //@ Add a reference to the actual call result [value]
            $grading_breakdown['actual'] = $output_data;
            $grading_breakdown['parsed'] = $extracted_arr ?? $parsed_expectations;
        }

        //@ Get back to the people with the results
        return $grading_breakdown;
    }

$explicit_expected = '{ "response": 200, "data": { "message": "OK" } }';
$simple_open_expected = '   {token}    ';
$complex_open_expected = '{service_id,service_name,service_fee,service_code,service_added,service_active}';
$super_complex_open_expected = '{{parent.service_id},{parent.service_name},{parent.service_fee},{parent.service_code},{parent.service_added},{parent.service_active}}';
$complex_open_nested_expected = ' [{service_id,service_name,service_fee,service_code,service_added,service_active}] ';

$simple_array = json_encode([
    'eTag' => '',
    'token' => '',
]);

$botched_array = json_encode([
    [
        'key' => 'Etag',
    ],
    [
        'key' => 'token',
    ],
]);

    echo "\n\n";
    // var_dump(doValueExtraction("token", $call_result_object ));
    // print_r( gradeCallResultAssessor($call_result_array, $complex_open_nested_expected, []) );
    print_r(computeCallResultAccuracy($grading_call_result = [], $grading_rule_combo = []));

//     $no_match_weight = "20";

//    echo ((intval($no_match_weight)??50)/100);
