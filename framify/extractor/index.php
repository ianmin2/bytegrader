<?php

    $parent_data = json_encode([
       [
           "service_id" => 123,
           "test_number" => "KAA123"
       ],
       [
        "service_id" => 300,
        "test_number" => "KAA300"
        ]
    ]);
    $sample_string = "/services/{{parent.service_id}}";
    $sample_array = json_encode([
        [
            "key"   =>  "name",
            "value" =>  "{{parent.test_number}}"
        ],
        [
            "key"   =>  "identifier",
            "value" =>  "Authorization {service_id}"
        ]
    ]);
    ///{{parent.test_number}}/{parent.phantom_id}



    $rgx = "/({{.*}})|({.*})/i";

    $isAssoc = function (array $arr) {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    };
   
    //@ Get the value matching the key from the parameter bank
    $extract_values = function( $value_key, $parameter_bank ) use ($isAssoc) {

        //@ Attempt to convert the conversion pool to an array
        $encoded_param_bank = json_decode($parameter_bank,true);

        //@ Ensure that the parameter bank is a php array 
        $parameter_bank = $encoded_param_bank ?? $parameter_bank;

        if(is_array($parameter_bank)){

            $found = NULL;

            //@ Check if the array is associative
            if($isAssoc($parameter_bank))
            {
                //@ Attempt to get a value
                $found = @$parameter_bank[$value_key];
            }
            else {
                //@ Loop through each, from the last value to the first
                foreach (array_reverse($parameter_bank) as $key => $value) {
                    if(@$found) continue;
                    if(@$value[$value_key])
                    {
                        $found = $value[$value_key];
                    }
                }
            }
            return $found;
        }

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

        // [
        //     [
        //         "key"   =>  "name",
        //         "value" =>  "{{parent.test_number}}"
        //     ],
        //     [
        //         "key"   =>  "identifier",
        //         "value" =>  "Authorization {service_id}"
        //     ]
        // ]

        //@ Define an output variable
        $found = [];

        //@ Loop through each array value
        foreach ($parent_array_value as $ky => $val) {
            //@ Start a local holder 
            $output = [];

            //@ Use the string transform option to set the value
            $output[$val["key"]] = $process_string_values($val["value"], $parameter_bank);
            //@ Update the transformed values tracker
            $found[$ky] = $output;           
        }

        //@ Return the transformed value
        return $found;

    };

    $deterministic_processor = function ($input_value, $data_bank) use ($process_string_values, $process_array_values)
    {

        $parsed_value = json_decode($input_value,true) ?? $input_value; 

       return is_array($parsed_value) ? $process_array_values($parsed_value,$data_bank) : $process_string_values($parsed_value, $data_bank);

    };

    print_r($deterministic_processor($sample_array, $parent_data));
