<?php
/*
	* m4Labs Framework
	
*/
class cleaner{
	
	public $path;
	public $file_types = array();
	public $days;
	public $jsoncallback;
		
	public function __construct( $jsoncallback='', $file_path='backup/history', $all=true, $days=0, $file_types='*' ){ 
		
		$this->jsoncallback = $jsoncallback;
									
		if($file_types == '*' && $all == true){ 
		
			$allAll = "*";
						 		
		}
		
		$file_types = explode(',',$file_types);
		
			
			$i = 0;
			while($i <= count($file_types)-1){
				
				$this->file_types[] = $file_types[$i];
				$this->days = $days;
				$i++;
					
			}
			$this->path = getcwd()."/".$file_path."/";
			
			if($all == true){
				
				if($allAll == "*"){
					$this->cleanThemAll();
				}else{
					$this->clean_it();
				}
				
			}
		
			
	}
	
	
	private function clean_it(){ 
		//$this->path = str_replace("\\","/",$this->path); //$this->path
		if($handle = @opendir(str_replace("\\","/",$this->path))){
			
			while(false !== ($file = readdir($handle))){
				
				if(is_file(str_replace("\\","/",$this->path).$file)){
					
					$file_info = @pathinfo(str_replace("\\","/",$this->path).$file);
					
					if(isset($file_info['extension']) && in_array(strtolower($file_info['extension']), $this->file_types)){
					
						if( @filemtime(str_replace("\\","/",$this->path).$file) < (time() - ($this->days * 24 * 60 * 60)) ){
							
							@unlink(str_replace("\\","/",$this->path).$file);
							
						}
						
					}
					
				}				
				
			}
			
			 $respArray = array("response" => "SUCCESS", "data" => array( "message"=>"File cleaning complete!", "command"=>"") );
			 echo $this->jsoncallback."(".json_encode($respArray).")";
			 exit;
			
		}else{
			
			$respArray = array("response" => "ERROR", "data" => array( "message"=>"Could not find the specified path!", "command"=>"") );
			echo $this->jsoncallback."(".json_encode($respArray).")";
			exit;
		}

	}
	
	
	
	public function cleanThemAll(){
		
		if(@chdir(str_replace("\\","/",$this->path))){
		
			$files = glob("*.*");
				
			foreach($files as $file){
				
				if( @filemtime(str_replace("\\","/",$this->path).$file) < (time() - ($this->days * 24 * 60 * 60)) ){
								
					@unlink($file);
								
				}
				
			}
			
			$respArray = array("response" => "SUCCESS", "data" => array( "message"=>"File cleaning complete!", "command"=>"") );
			echo $this->jsoncallback."(".json_encode($respArray).")";
			exit;
		
		}else{
			
			$respArray = array("response" => "ERROR", "data" => array( "message"=>"Could not find the specified path!", "command"=>"") );
			echo $this->jsoncallback."(".json_encode($respArray).")";
			exit;
					
		}
		
	}
	
	
	
	public function clean($file, $file_path='backup/history', $isMany=false){
		
		$this->path = getcwd()."/".$file_path."/";
		
		if($handle = @opendir(str_replace("\\","/",$this->path))){
			
			if(@unlink(str_replace("\\","/",$this->path).$file)){
				
				if($isMany){
					return true;
				}else{
					$respArray = array("response" => "SUCCESS", "data" => array( "message"=>"File cleaning complete!", "command"=>"") );
					echo $this->jsoncallback."(".json_encode($respArray).")";
					exit;
				}
				
			}else{
				
				$respArray = array("response" => "ERROR", "data" => array( "message"=>"Could not find the specified file!", "command"=>"") );
				echo $this->jsoncallback."(".json_encode($respArray).")";
				exit;
				
			}
			
		}else{
			
			$respArray = array("response" => "ERROR", "data" => array( "message"=>"Could not find the specified path!", "command"=>"") );
			echo $this->jsoncallback."(".json_encode($respArray).")";
			exit;
			
		}
		
	}
	
	
	public function clean2($files, $file_path='backup/history'){
		
		$files = explode(",",$files); 
		
		$isMany = true;
		foreach($files as $file){
			
			$this->clean($file, $file_path,$isMany);
			
		}
		
		$respArray = array("response" => "SUCCESS", "data" => array( "message"=>"Multiple File cleaning complete!", "command"=>"") );
		echo $this->jsoncallback."(".json_encode($respArray).")";
		exit;
		
	}
	
//End Of class	
}
?>