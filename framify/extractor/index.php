<?php

    // $parent_data = json_encode([ 
    //    "content" => '{"token": "AUTH_TOKEN_UNVEILED"}',
    //    "body" => [
    //         [
    //             "service_id" => 123,
    //             "test_number" => "KAA123"
    //         ],
    //         [
    //             "service_id" => 300,
    //             "test_number" => "KAA300"
    //         ]
    //     ],        
    // ]);

    $call_result_object = $parent_data_object = '[{"error":false,"body":{},"headers":{"X-Powered-By":["@framify"],"Access-Control-Allow-Headers":["Origin, X-Requested-With,Content-Type, Access-Control-Allow-Origin, Authorization, Origin, Accept, x-auth-token"],"Access-Control-Allow-Origin":["*"],"Access-Control-Allow-Methods":["GET, POST, PUT, DELETE, OPTIONS"],"Content-Type":["application\/json; charset=utf-8"],"Content-Length":["319"],"ETag":["W\/\"13f-Br7ZsJNXpwOaM\/3v16GG+EDQon8\""],"Vary":["Accept-Encoding"],"Date":["Mon, 16 Nov 2020 02:14:45 GMT"],"Connection":["close"]},"status":200,"content":{"token":"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZW1iZXJfaWQiOiIzNiIsInVzZXJuYW1lIjoiaWFubWluMjIiLCJqb2luZWQiOiIyMDIwLTExLTE2VDAwOjM5OjA5LjgxM1oiLCJhY3RpdmUiOnRydWUsIm5hbWUiOiJJYW4gS2FtYXUiLCJpYXQiOjE2MDU0OTI4ODUsImV4cCI6MzYwMDE2MDU0OTI4ODUsImlzcyI6IjE5Mi4xNjguMS4xODQifQ.pe5Qrv4unFJ8oaibgJFA0GIil_jKAm-N6B0kXckj6yc"},"response":{}}]';
    


    $call_result_array = $parent_data_array = '[{"error":false,"body":{},"headers":{"X-Powered-By":["@framify"],"Access-Control-Allow-Headers":["Origin, X-Requested-With,Content-Type, Access-Control-Allow-Origin, Authorization, Origin, Accept, x-auth-token"],"Access-Control-Allow-Origin":["*"],"Access-Control-Allow-Methods":["GET, POST, PUT, DELETE, OPTIONS"],"Content-Type":["application\/json; charset=utf-8"],"Content-Length":["402"],"ETag":["W\/\"192-a65NeniwK1s07OjAXNB2h69BMfc\""],"Vary":["Accept-Encoding"],"Date":["Wed, 18 Nov 2020 02:11:34 GMT"],"Connection":["close"]},"status":200,"content":[{"service_id":"1","service_name":"SMS","service_code":"BX_SMS","service_added":"2020-10-18T17:20:48.184Z","service_active":true},{"service_id":"5","service_name":"SERVICEPI","service_code":"SRV1","service_added":"2020-10-18T21:45:44.579Z","service_active":true},{"service_id":"6","service_name":"realService","service_code":"real one","service_added":"2020-11-16T02:51:19.396Z","service_active":true}],"response":{}}]';

    $sample_string = "/services/{{parent.service_id}}";
    $sample_array = json_encode([
        [
            "key"   =>  "name",
            "value" =>  "{{parent.ETag}}"
        ],
        [
            "key"   =>  "identifier",
            "value" =>  "Authorization {token}"
        ]
    ]);
    $simple_array = json_encode([        
        "eTag" =>  "",
       "token" =>  ""        
    ]);

    ///{{parent.test_number}}/{parent.phantom_id}


      
$explicit_expected = '{ "response": 200, "data": { "message": "OK" } }';
$simple_open_expected   = '   {token}    ';
$complex_open_expected  = '{service_id,service_name,service_fee,service_code,service_added,service_active}';
$super_complex_open_expected = '{{parent.service_id},{parent.service_name},{parent.service_fee},{parent.service_code},{parent.service_added},{parent.service_active}}';
$complex_open_nested_expected  = ' [{service_id,service_name,service_fee,service_code,service_added,service_active}] ';

function doValueExtraction( $canvas, $transform_values )
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


// print_r(doValueExtraction($sample_array, $parent_data_array));
// exit;

// print_r(doValueExtraction($simple_open_expected, $parent_data_array));
// echo "\n\n\n";
// print_r(doValueExtraction($sample_string, $parent_data_array));
// echo "\n\n\n";


/**
 * gradeCallResultAssessor($grading_call_result, $expected_data, $grading_criteria );
 * $grading_call_result = result from a submission rule call/invokation.
 * $expected_data = any expected data defined in the grading route rule ['rule_expected_data'].
 * $grading_criteria = 'rule_grading' from the grading route rule.
 */
function gradeCallResultAssessor($grading_call_result, $expected_data, $grading_criteria) {
        
    //@ Set the basic grading breakdown
    $grading_breakdown = [ 
        "template" => $expected_data,
        "actual"   => [], 
        "accuracy" => 0
    ];

    $counter = 0;

    $points = [];

    $output_data = [];

    //@ If expected data is defined, check it
    if($expected_data)
    {
        //@ Attempt to convert to json
        $parsed_expectations = @json_decode($expected_data ,2) ?? trim($expected_data);
        $grading_call_result = @json_decode($grading_call_result,2) ?? $grading_call_result;


        //@ Value tester helper closure method
        $has_string = function($test_string) use ($parsed_expectations)
        {
            return strpos($parsed_expectations, $test_string);
        };

        //@ Extract the value from the provided call result
        $extract_value_from_call_result = function($key) use ($grading_call_result)
        {
            $key = (strpos($key,'{')) ? $key : "{".$key."}";
            return doValueExtraction($key, $grading_call_result );
        };

        //@ Get the proper value from the passed token
        $extract_key_value_as_array = function($parent_value) {
            $tmp_holder = [];
            foreach (explode(',', preg_replace('/({)|(})|(parent\.)/i', '', trim($parent_value))) as $key => $value) {
                echo "\n\n~273 Extracted {json_encode($value)} from {$parent_value}\n\n";
                $tmp_holder[$value] = "";
            } ;
            return $tmp_holder;
        };

        // var_dump( $extract_key_value_as_array($parsed_expectations) );
        // exit;

        //@ If expectations is of type, array [i.e $explicit_expected]
        if(is_array($parsed_expectations)) {

            //@ Loop through each item and get its value
            foreach ($parsed_expectations as $key => $value) {

            
                if(@$value["key"])
                {
                    $output_data[$value["key"]] = $extract_value_from_call_result($value["key"]);
                }
                else 
                {
                    $output_data[$key] = $extract_value_from_call_result($key);
                }
            
            }
            var_dump($output_data);
            exit;
           
        }
        //@ if has no commas and has closing curly brace [i.e $simple_open_expected]
        else if(!$has_string(',') && $has_string('}') && $has_string('{')) {
            $output_data[$expected_key] = $d = $extract_value_from_call_result($extract_key_value_as_array($parsed_expectations));
            print_r( $expected_key );
            exit;
            echo "\n\n~291 resolved to {$output_data[$expected_key]} in direct call\n\n";
        }
        //@ if has commas and closing curly but no opening and closing braces [i.e $complex_open_expected]
        else if(!$has_string('[') && !$has_string(']') && $has_string('{') && $has_string('}') )
        {
            //@ Transform to a more parseable set of tokens with arbitrary initial values 
            $key_structure = $extract_key_value_as_array($parsed_expectations);

            //@ Loop through each item and get its value
            foreach ($parsed_expectations as $key => $value) {
                $output_data[$key] = $extract_value_from_call_result($key);
                echo "\n\n~300 {$key} => {$value}  resolved to {$output_data[$key]} in reference to the key\n\n";
            }

        }
        //@ If is array of simple or open types
        else if(preg_match('/^\[.*\]$/i',trim($parsed_expectations)) && $has_string('{') && $has_string('}') ) {
            
            //@ Remove the JS array placeholder items
            $altered_expectations = preg_replace('/^\[|\]$/i', '', trim($parsed_expectations) );

            //@ NOTE: Remember to validate actual response as array
            echo "\n\nNOT YET!!\n\n";

            //@ Parse to an associative array
            $key_structure = $extract_key_value_as_array($altered_expectations);

            //@ Get the associated values
            foreach ($variable as $key => $value) {
                $output_data[$key] = $extract_value_from_call_result($key);
            }

        }

        
    }
    
    //@ Go through each of the grading rules and check the result's compliance

    //@ Add a reference to the actual call result [value]
    $grading_breakdown["actual"] =  $output_data;

    return $grading_breakdown;
}


echo "\n\n";
var_dump( gradeCallResultAssessor($call_result_array, $simple_array, []) );
// var_dump( preg_replace('/^\[|\]$/i', '', trim($complex_open_nested_expected) ));
// $key_rsp = explode(',', preg_replace('/({)|(})|(parent\.)/i', '', trim($complex_open_expected)));
// var_dump($key_rsp);
echo "\n\n";

// var_dump( json_decode($explicit_expected,2) ?? $explicit_expected );
// var_dump( json_decode($simple_open_expected,2) ?? $simple_open_expected );
// var_dump( json_decode($complex_open_expected,2) ?? $complex_open_expected );
// var_dump( json_decode($complex_open_nested_expected,2) ?? $complex_open_nested_expected );
  



