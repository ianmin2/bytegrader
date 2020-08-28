<?php 
@session_start();
class mailer{
	
	public $id 			= "mailer.php";
	public $from  		= "holemark@holemark.co.ke";
	public $reply_to 	= "holemark@holemark.co.ke";
	public $selfname 	= "HOLEMARK | Exam Management";
	
		
	public $headers;		public $message;			public $image_url; 
	public $to;				public $subject;			public $content;
	public $to_name;		public $action;				public $csslink; 
	public $messageT;
	
	public function __construct($to_name ,$to_email, $subject, $message_content, $custom_image_url, $action){
		
		if(@$to_email != '' && @$subject != '' && @$message_content != ''){
			$this->to 			= strip_tags( $to_email );
			$this->to_name		= $to_name;
			$this->subject 		= $subject;
			$this->content		= $message_content;
			$this->action		= $action;
			$id 				= $this->id;
			$this->sitepath    =  @$this_site; 
			
			
			$random_hash = md5( date('r', time()) ); 
			//define the headers we want passed. Note that they are separated with \r\n
			$this->headers = "From: ".strip_tags($this->from)."\r\nReply-To: ".strip_tags($this->reply_to)."";
			//add boundary string and mime type specification
			$this->headers .= "\r\nContent-Type:  text/html; boundary=\"PHP-alt-".$random_hash."\""; 
						
			
			$this->messageT = "";
			//Custom header image
			$this->messageT .= "\n\r";
			$this->messageT .= "Name: " .@$this->to_name. "\n\r";
			$this->messageT .= "Email: " .@$this->to. "\n\r";
			$this->messageT .= "Message Type: " .@$this->subject. "\n\r";
			$this->messageT .= "Details: " .@$this->content. "\n\r";
			$this->messageT .= "\n\r";
			//$this->message .= "\n\r ";
			
$this->message = <<<FILES

FILES;
			
			if($this->action == 'sendmail'){
				$this->sendmail();
			}
			
			/*
			//Worth considering / [debateable]
			//check for the actions that may exist 
			//send email [sendmail]
			//verify registration [verify]
			//recover lost password [recovery]
			*/
					 
		}else{
			
			return ('<div class="alert alert-danger alert-bold-border fade in alert-dismissable">
			  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			  <strong>OOPS!</strong><br> Failed to Initialize Mailer <a href="#" class="alert-link">Try Again</a>.
			</div>');
			
		}
		
	}
	
	
	
	
	private function sendmail(){
		/*
		if(mail($this->to, $this->subject, $this->messageT, $this->headers)){
			$_SESSION['mailed'] = true;
		}else{
			$_SESSION['mailed'] = false;
		}
		*/
		
		@$this->truemail();
	}
	

	private function truemail(){
		
		$uri = 'https://mandrillapp.com/api/1.0/messages/send.json';
				
		$postString = '{
		"key": "C2JgjLf0pN0os9FMvJrc7Q",
		"message": {
			"html":  '.@json_encode($this->message).',
			"text": '.@json_encode($this->messageT).',
			"subject": '.@json_encode($this->subject).',
			"from_email": '.@json_encode($this->from).',
			"from_name": "Holemark Printers",
			"to": [
				{
					"email": '.@json_encode($this->to).',
					"name": '.@json_encode($this->to_name).'
				}
			],
			"headers": {
				
			},
			"track_opens": true,
			"track_clicks": true,
			"auto_text": true,
			"url_strip_qs": true,
			"preserve_recipients": true,
		
			"merge": true,
			"global_merge_vars": [
		
			],
			"merge_vars": [
		
			],
			"tags": [
		
			],
			"google_analytics_domains": [
		
			],
			"google_analytics_campaign": "...",
			"metadata": [
		
			],
			"recipient_metadata": [
		
			],
			"attachments": [
		
			]
		},
		"async": false
		}';
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
		
		
		return curl_exec($ch);
		
		//echo @$result;
		
		
		
	}

		
//End of class		
}


?>		



