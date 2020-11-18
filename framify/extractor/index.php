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

    $parent_data_array = '[{"error":false,"body":{},"headers":{"X-Powered-By":["@framify"],"Access-Control-Allow-Headers":["Origin, X-Requested-With,Content-Type, Access-Control-Allow-Origin, Authorization, Origin, Accept, x-auth-token"],"Access-Control-Allow-Origin":["*"],"Access-Control-Allow-Methods":["GET, POST, PUT, DELETE, OPTIONS"],"Content-Type":["application\/json; charset=utf-8"],"Content-Length":["319"],"ETag":["W\/\"13f-Br7ZsJNXpwOaM\/3v16GG+EDQon8\""],"Vary":["Accept-Encoding"],"Date":["Mon, 16 Nov 2020 02:14:45 GMT"],"Connection":["close"]},"status":200,"content":{"token":"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZW1iZXJfaWQiOiIzNiIsInVzZXJuYW1lIjoiaWFubWluMjIiLCJqb2luZWQiOiIyMDIwLTExLTE2VDAwOjM5OjA5LjgxM1oiLCJhY3RpdmUiOnRydWUsIm5hbWUiOiJJYW4gS2FtYXUiLCJpYXQiOjE2MDU0OTI4ODUsImV4cCI6MzYwMDE2MDU0OTI4ODUsImlzcyI6IjE5Mi4xNjguMS4xODQifQ.pe5Qrv4unFJ8oaibgJFA0GIil_jKAm-N6B0kXckj6yc"},"response":{}}]';

    $parent_data_string = '[{"error":false,"body":{},"headers":{"X-Powered-By":["@framify"],"Access-Control-Allow-Headers":["Origin, X-Requested-With,Content-Type, Access-Control-Allow-Origin, Authorization, Origin, Accept, x-auth-token"],"Access-Control-Allow-Origin":["*"],"Access-Control-Allow-Methods":["GET, POST, PUT, DELETE, OPTIONS"],"Content-Type":["application\/json; charset=utf-8"],"Content-Length":["402"],"ETag":["W\/\"192-a65NeniwK1s07OjAXNB2h69BMfc\""],"Vary":["Accept-Encoding"],"Date":["Wed, 18 Nov 2020 02:11:34 GMT"],"Connection":["close"]},"status":200,"content":[{"service_id":"1","service_name":"SMS","service_code":"BX_SMS","service_added":"2020-10-18T17:20:48.184Z","service_active":true},{"service_id":"5","service_name":"SERVICEPI","service_code":"SRV1","service_added":"2020-10-18T21:45:44.579Z","service_active":true},{"service_id":"6","service_name":"realService","service_code":"real one","service_added":"2020-11-16T02:51:19.396Z","service_active":true}],"response":{}}]';

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
    ///{{parent.test_number}}/{parent.phantom_id}

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
                $replacement_key = preg_replace('/({)|(})|(parent\.)/i','',$value);

                //@ Attempt a data extract for the key 
                $replacement_value = $extract_values($replacement_key,$parameter_bank);

                //@ Conditionally Apply the replacement to the string
                if( $replacement_value )
                {
                    $parent_value = preg_replace( "/{$value}/i" ,$replacement_value, $parent_value);
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

       return is_array($parsed_value) ? $process_array_values($parsed_value,$data_bank) : $process_string_values($parsed_value, $data_bank);

    };

    return $deterministic_processor($canvas, $transform_values);

}


    // $enc = json_decode($parent_data,true);
    // $enc["content"] = json_decode($enc["content"],true) ?? $enc["content"];

    // print_r($enc);

    print_r(doValueExtraction($sample_array, $parent_data_array));
    echo "\n\n\n";
    print_r(doValueExtraction($sample_string, $parent_data_string));
    echo "\n\n\n";
