<?php

	class minify{
	
		public $file_types;
		public $minify_path;
		public $curr_path;
		private $edit;
				
		public function __construct(){
			include "r_editor.php";
			$this->edit = new r_editor();
			$curr_path = getcwd();	
		}
				
		function minify($download=false, $file_types=array('js','txt','css'), $minify_path='minify' ){
			
			$this->file_types 	= $file_types;
			$this->minify_path 	= $minify_path;
			
			@mkdir($this->minify_path);
			$files = array();
			
			foreach($file_types as $file_type){
				$files[] = glob("*.".$file_type);
			}
			
			foreach($files as $file){
				
				foreach($file as $filename){
				
					$filename_ = 							explode(".", $filename);
					$filename_[count($filename_) - 1] = 	"min.".$filename_[count($filename_) - 1];
					$filename_ = 							implode(".", $filename_);
					
					
					$filepath = $this->curr_path.$filename;
					
					  $minifile = $this->minify_path."/".$filename_;
					
					@unlink(str_replace("\\","/",$minifile));
					
									
					$string = file_get_contents(str_replace("\\","/",$filepath));
					$string = $this->edit->uncomment($string);
					$string = preg_replace('/\s+/',' ',$string);
					$string = str_replace('<?php','<?php ',$string);
					file_put_contents(str_replace("\\","/",$minifile),$string);
					
					if($download == true):
						@header('Content-Type: application/octet-stream');
						@header("Content-Transfer-Encoding: Binary"); 
						@header("Content-disposition: attachment; filename=\"" . basename($minifile) ); 
						@readfile($minifile); 
					endif;
					
				}
				
			}
			
			/*print_r($file);*/
			die;
					
		}
	}
	

?>