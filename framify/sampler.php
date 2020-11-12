<?php
// include __DIR__."/workers/grading/index.php";
 class ClassName 
 {
   //  public $omni;
     public function __construct($omni) {
      //   $this->omni = [
      //      "1" => ["token"=>"1234"],
      //      "atha" => [ "accept" => "text/html"]
      //   ];
        $param_data = json_decode('[{"key":"Authorization", "value": "{{parent.token}}" }, { "key" : "content-type", "value": "application/json"}, {"key":"Accept", "value": "{{parent.accept}}" }]',true);
       
       $transformTokeyValueArray = function ($temp_values,$item) use ($omni){
           //@ check if it is a replacement item
           if(preg_match("/{{.*}}/i",$item["value"],$vl))
           {
              print_r(preg_replace('/({{)|(}})|(parent\.)/i','',$vl));
               echo "YEEEEEES\n\n";
           }
         $temp_values[$item["key"]] = $item["value"];
         return $temp_values;
        };
        print_r( array_reduce($param_data,$transformTokeyValueArray, array()));
     }

      
 }
 new ClassName([
   "1" => ["token"=>"1234"],
   "atha" => [ "accept" => "text/html"]
]);
// echo new GradingWorker( null, [1], [1], true);
