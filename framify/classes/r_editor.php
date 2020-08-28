<?php

	class r_editor{
		
		public function __construct($jsoncallback=''){}	
		
		
		public function delete_all_between( $beginning, $end, $string ){
			
			$beginningPos = strpos($string, $beginning);
			$endPos = strpos($string, $end);
			if ($beginningPos === false || $endPos === false) {
			return $string;
			}
			
			$textToDelete = substr($string, $beginningPos, ($endPos + strlen($end)) - $beginningPos);
			
			return str_replace($textToDelete, '', $string);
			
		}
		
		
		public function get_all_between( $beginning, $end, $string ){
			
			$beginningPos = strpos($string, $beginning);
			$endPos = strpos($string, $end);
			if ($beginningPos === false || $endPos === false) {
			return $string;
			}
			
			$textToDelete = substr($string, $beginningPos, ($endPos + strlen($end)) - $beginningPos);
			$textToDelete = str_replace("(", "", $textToDelete);
			$textToDelete = str_replace(")", "", $textToDelete);
			return $textToDelete;
			
		}
		
		public function uncomment($string){
			
			$string = preg_replace('!/\*.*?\*/!s', '', $string);
			$string = preg_replace('/\n\s*\n/', "\n",  $string);
			$string = preg_replace('#^\s*//.+$#m', "", $string);
			
			return  $string;	
		
			
		}
		
		
	}
	
	

?>



