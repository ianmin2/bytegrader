<?php
/*
	* Author: Ian Innocent 
	* For: The Connection
*/
@session_start();
class redirect{
	
	
	public $id = "redirect.php";
	public $loc;

	public function __construct($location){ 
		
		@$this->redir($location);
		
	}
	
	
	
	public static function redir($loc){
			
			if(@$loc != ''){
				
				echo '<script>window.location='.@json_encode($loc).';</script>';
				exit;
				
			}else{
				
				echo '<script> history.back();  </script>';
				exit;
				
			}
			
		}

}

?>







