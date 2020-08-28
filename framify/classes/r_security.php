<?php

	class security{
		
		public function __construct(){
			
		}
		
		private function hide(){}
		
		private function obsfucate(){}
		
		
	}
	
/*	
	$di = new RecursiveDirectoryIterator('c:\xampp\htdocs\reports');
	$files = array();
	$extension = '.php';
foreach (new RecursiveIteratorIterator($di) as $filename => $file) {
	
   /* echo $filename . ' - ' . $file->getSize() . ' bytes <br/>'; */
 /*  if (strpos($filename, $extension ) !== false):
    $files[] = $filename;
	endif;
    
  }
  /*
	  echo "<pre>";
		print_r($files);
	  echo "</pre>";
  */
  
  	
	  	
 
  $dirs = glob('..\..\*', GLOB_ONLYDIR);
       foreach($dirs as $dir){
          echo '<pre value="'.$dir.'">'.$dir."</pre>\n";
		  
       }         
 
  


?>