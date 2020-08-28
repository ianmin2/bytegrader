<?php
/*
	* m4Labs Framework
	*Simple Encryption class
	*Allows the encryption of data with the use of pre-defined keys.
	*
	*
	*
	*
*/
	
	
	class obsfucate{
	
	 public $id = "obsfucate.php";
	 private $keyz; 		private $sel;	
		
	 public function __construct($key , $salt){ 
		
			//Setting of the key and the salt
			$this->keyz = $key;
			$this->sel = $salt;
			
	 }
	
	public function makePass($stringy){
		
		$res = '';
		for( $i = 0; $i < strlen($stringy); $i++){
				  $c = ord(substr($stringy, $i));
				  $c += ord(substr($this->keyz, (($i + 1) % strlen($this->keyz))));
				  $res .= chr($c & 0xFF);
		}
		$res = base64_encode($res);
	
		return $res; 
		
	}
	
		
	public function getPass($stringy){
		
		$recovered_passwd = ''; 
		
		$stringy = base64_decode($stringy);
		
		 for( $i = 0; $i < strlen($stringy); $i++){
			 
				 $c 				 = ord(substr($stringy, $i));
				 $c 				-= ord(substr($this->keyz, (($i + 1) % strlen($this->keyz))));
				 $recovered_passwd  .= chr(abs($c) & 0xFF);
					
		 }
		
		 return $recovered_passwd;
		
	}
	
	
	
	public function makeKey($stringy){
	
		$reckey = crypt($stringy,$this->sel);
		$reckey = md5($reckey);
		
		return $reckey;
		
	}
	
		
	//End Of Class	
	}
	
	
?>