<?php
/*
	* m4Labs Framework
	*Simple database management class
	*
	*
	*
	*
	*
*/
class connection{
	
	private $db_name;		 private $db_host; 			private $db_username;				public $allbkup;			
	private $db_passwd;      public $jsoncallback;		public $query_error = array();		public $bkpath;
	

//The class constructor	
	public function __construct($db_name, $db_host='localhost', $db_username='root', $db_passwd='', $jsoncallback=''){
		
		$this->jsoncallback = $jsoncallback;
	
		if(@$db_host != '' && @$db_name != '' && @$db_username != ''){
			$this->db_host 		= 	@$db_host;
			$this->db_name 		= 	@$db_name;
			$this->db_passwd 	= 	@$db_passwd;
			$this->db_username 	= 	@$db_username;
			$this->db_connect();
			
		}else{
			
			$respArray = $this->makeResponse( "ERROR", "Critical Error: Could recognize database connection criteria!" , "");
			echo  $this->jsoncallback."(".json_encode($respArray).")";
			exit;
		}
	
	}
	
// The database connector!

	public final function db_connect(){
		
		$this->con = mysqli_connect($this->db_host,$this->db_username,$this->db_passwd,$this->db_name);
		
		if (mysqli_connect_errno($this->con)){
			
			$respArray = $this->makeResponse( "ERROR", "Response: ".mysqli_connect_error(), "");
			echo  $this->jsoncallback."(".json_encode($respArray).")";
			exit;
			
		}
	}
	
	
	public function die_on_err($stops){
		
		if(@$stops){
			
			$respArray = $this->makeResponse( "ERROR", "CRITICAL ERROR: ".mysqli_error($this->con), "");
			echo  $this->jsoncallback."(".json_encode($respArray).")";
			exit;
			
		}else{
						
			if(mysqli_error($this->con)){
				$this->query_error[] = mysqli_error($this->con);			
			}
			
		}
		
	}
	
	public function query($statement, $stops=false){  
	
		$query =  mysqli_query($this->con,"$statement")or $this->die_on_err($stops); 
		return $query;
		
	}
	
	public function aQuery($statement, $stops=false, $success, $failure, $scommand='', $fcommand='' ){  
	
		$query = $this->query($statement,$stops);
		
		if($query){
			
			
			$respArray = $this->makeResponse( "SUCCESS", $success , $scommand);
			return  $this->jsoncallback."(".json_encode($respArray).")";
						
		}else{
			
			$respArray = $this->makeResponse( "ERROR", $failure , $fcommand);
			return  $this->jsoncallback."(".json_encode($respArray).")";
						
		} 
		
	}
	
	
	public function sQuery($statement, $dbField, $delimeter, $stops){
		
		$elementArr = $this->query($statement, $stops);
		
		$elements = array();
		while( $element = mysqli_fetch_array($elementArr) ){
			$element = (count($elements) == 0)? $element[$dbField] : ",".$element[$dbField];
			$elements[] = $element;
		}
		return $elements;
		
	}
	
	
	public function num_rows($statement, $stops=false){
		
		$stmt = $this->query($statement, $stops);
		return mysqli_num_rows( $stmt );
		
				
	}
	
	//SIMPLE RETURN RESULTS EXECUTOR
	function printQueryResults($statement, $bool=false){
		
		$elementArr	=	$this->query("$statement",$bool);
				
		$elements = array();
		while($element = mysqli_fetch_array($elementArr)){
			$elements[] = $element;
		}
		
		return $elements;
		
	}
	
	
	public function error_alert($status=true){
		if(@$status){
						
			if(@count($this->query_error) > 0){
											
				return  $this->makeResponse("ERROR", $this->query_error, "" );
				
			}else{
				return 0; 
			}
			
		}
	}
	
	
	
	private function makeResponse($response, $message, $command){
		
		return array( "response" => $response, "data" => array( "message" => $message, "command" => $command ) );
		
	}
	
	//the backup file generator
	/*
		*$auth				--	Accepts the authentication status [true/false]
		*$bkup_path 		--	Accepts the relative path to the general backup folder [backup]
		*$bkup_path_all		--	Accepts the relative path to the backup of backups folder [backup/history]	
	*/
		
	function bkup( $tables='*', $bkup_path='backup', $bkup_path_all='backup/history'){
		
		$this->bkpath 	= getcwd()."/".$bkup_path."/";
		$this->allbkup 	= getcwd()."/".$bkup_path_all."/";
		
		//Create the backup directories if they do not exist
		@mkdir($this->bkpath);
		@mkdir($this->allbkup);
		
		//Get the list of tables from the 
		if($tables == '*'){
			$tables = $this->query("SHOW TABLES",true);
			while($table = mysqli_fetch_array($tables)){
				$this->doStore($table, $i, $bkup_path, $bkup_path_all);
			}
		}else{
			$i =0;
			$tables = explode(",",$tables);
			foreach($tables as $tabl){
				$this->doStore($tables, $i, $bkup_path, $bkup_path_all);
				$i++;
			}
		}
			
	}
	
	function bkup_restore( $keyer=NULL, $tables='*', $bkpath='backup' ){
		
		if($keyer == $this->db_passwd):
					
			$i = 0;
						
			if($tables == '*'){
				
			$tables = $this->query("SHOW TABLES",true);
			while($table = mysqli_fetch_array($tables)){
				$this->doRestore($table, $i, $bkpath);
			}
			
		}else{
			
			$i =0;
			$tables = explode(",",$tables);
			foreach($tables as $tabl){
				$this->doRestore($tables, $i, $bkpath);
				$i++;
			}
			
		}
			
		else:
		
			$respArray = $this->makeResponse("ERROR","User Authentication Failed!", "alert('Could Not Perform Backup:\n\n User Authentication Failed!');");
			echo $this->jsoncallback."(".json_encode($respArray).")";
			exit;
			
		endif;
	}

	
	
	function doStore($table , $i=0, $bkup_path='backup', $bkup_path_all='backup/history'){
		
		$bkupfile = $this->bkpath.$table[$i].".sql";
		$pbkup = $this->allbkup.$table[$i].date("Y-M-D H.i.s").".sql";
					
		@unlink(str_replace("\\","/",$bkupfile));
		
		$bkup = $this->query("SELECT * INTO OUTFILE '".str_replace("\\","/",$bkupfile)."' FROM $table[$i]",true);
		if($bkup);
		
		$bkup	=	$this->query("SELECT * INTO OUTFILE '".str_replace("\\","/",$pbkup)."' FROM $table[$i]",true);
		if($bkup);	
		
	}
	
	
	

	function doRestore($table , $i=0, $bkpath='backup'){
		
		$success = "";
		$error = "";
		
		$this->bkpath 	= getcwd()."/".$bkpath."/";
		$bkupfile = $this->bkpath.$table[$i].".sql";
		
		$trunc = $this->query("TRUNCATE $table[$i]",true);
		if($trunc);
		
		$load = $this->query("LOAD DATA INFILE '".str_replace("\\","/",$bkupfile)."' INTO TABLE $table[$i] ",true);
		if($load){$success .= $table[$i]."\n\n ";}
	
	}
	
//End of Class	
}

	
