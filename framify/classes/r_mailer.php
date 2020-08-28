<?php

	class mailer{

		public $to, $from, $headers, $message, $template, $recipients, $method;

		public function __construct($jsoncallback='', $to='', $from='', $headers='', $message='', $template='', $method=''){

			if( @$to['email'] != "" && @$from['email'] != ""   ){

			echo $message['hybrid']."\n\n";
			echo $message['text']."\n\n";
			//print_r( $message['html']['body'] )."\n\n";

				$this->to 		= $to;
				$this->from		= $from;
				$this->headers	= $headers;
				$this->message	= $message;
				$this->template = $template;
				$this->method   = $method;
				
				$this->headers["subject"] = (@$headers["subject"] == "") ? "An email to you" : $headers["subject"];

				/* BEGIN CONSTRUCTING THE EMAIL LAYOUT */
				$this->buildMail();
			
			}else{

				echo $jsoncallback."(".json_encode(array( "response" => "ERROR", "data" => array( "message" => "NOT ALL REQUIRED PARAMETERS HAVE BEEN MET!", "command" => ""))).")";

			}

		}

		public function buildMail(){

			if( $this->method["type"] == "mandrill" ){

			if( strpos($this->to['email'], ',') !== false ){

				/* Building a recipient list from the provided email addresses */
				$recipientList  = explode( ",", $this->to['email'] );
				$recipientName  = explode( ",", $this->to['name'] );
				


				$numEmail = count($recipientList);
				$numNames = count($recipientName);

				$i = 0;
				$recipients = "";
				$this->errorList  = "";

				foreach($recipientList as $recipient){
					$name = (@$recipientName[$i] != "" && @$recipientName[$i] != NULL)? @$recipientName[$i] : $this->to["name"];

					if( filter_var($recipient, FILTER_VALIDATE_EMAIL) ){

					$recipients  .= '{ 
								"email": 	'.json_encode($recipient).', 
								"name": 	'.json_encode($name).' 
							}';

					}else{
						$this->errorList .= '{"email": 	'.json_encode($recipient).', "name": 	'.json_encode($name).' }'."\n\n";
					}
				
					$i++;	
				}
				$this->recipients['mandrill'] = $recipients;
				/* print_r($this->recipients['mandrill']); */
				}else{
					$this->recipients['mandrill'] = '{ "email":'.@json_encode($this->to["email"]).',"name":'.@json_encode($this->to["name"]).'}';
				}

				if( 
					($this->message['hybrid'] == "" || $this->message['text'] == "")
					 && 
					($this->message['html']['header'] != "" ) 
					 &&
					($this->template == "" || $this->template['head'] == "" ) 
				) :

					/*Construct the email using the default layout */
					include "mail/mailTemplate.php";
					
					$template = new mailTemplate( $this->message['html']['header'], $this->message['html']['body'], $this->message['html']['footer'] );
					
					$this->message['html'] = $template->build001();
					
					unset($template); 

				elseif( $this->template['head'] != "" ):
				
					/*Construct a message from a template*/
					$message = @$this->template['head'].
							@$this->template['bs'].
							@$this->template['bhs'].
							@$this->message['html']['header'].
							@$this->template['bhe'].
							@$this->template['bms'].
							@$this->message['html']['body'].
							@$this->template['bme'].
							@$this->template['fms'].
							@$this->message['html']['footer'].
							@$this->template['fme'].
							@$this->template['end'];

					$this->message['html'] = $message;

				elseif($this->message['hybrid'] != ""):
					
					/*Prepare the hybrid mail content for sending*/
					$this->message['html'] = $this->message['hybrid'];

				else:

					/*Prepare the text mail content for sending*/
					$this->message['html'] = $this->message['text'];

				endif;

			}
			/* End of email recipient list builder */

			/* SEND THE EMAIL */
			$this->mailIt();

		}

		private function mailIt(){
			

			if( $this->method["type"] == "php" ){

				if(mail( $this->to['email'], $this->from[email]."<".$this->from['name'].">", $this->message['html'], $this->headers['subject'] )){ 

					echo $jsoncallback."(".json_encode(array( "response" => "SUCCESS", "data" => array( "message" => "EMAIL SENT to ".$this->to['email']."!", "command" => ""))).")";
					exit;

				 }else{

					echo $jsoncallback."(".json_encode(array( "response" => "ERROR", "data" => array( "message" => "FAILED TO SEND EMAIL!", "command" => ""))).")";
					exit;
				
				}

			}elseif( $this->method["type"] == "mandrill" && $this->method["key"] != "" ){

				

				$uri = 'https://mandrillapp.com/api/1.0/messages/send.json';
				/* DeFaUlT I&E API KEY "C2JgjLf0pN0os9FMvJrc7Q" */
				$postString = '{
				"key": '.$this->method["key"].',	
				"message": {
					"html":  	'.$this->message["html"].',
					"text":  	'.$this->message["text"].',
					"subject": 	'.$this->headers["subject"].',
					"from_email": 	'.$this->from["email"].',
					"from_name": 	'.$this->from["name"].',
					"to": [
						'.
							$this->recipients['mandrill']
						.'

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
				curl_setopt(	$ch, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt(	$ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt(	$ch, CURLOPT_URL,		$uri );
				curl_setopt(	$ch, CURLOPT_FOLLOWLOCATION, 	true  );
				curl_setopt(	$ch, CURLOPT_RETURNTRANSFER, 	true  );
				curl_setopt(	$ch, CURLOPT_SSL_VERIFYPEER, 	false );
				curl_setopt(	$ch, CURLOPT_POST, 		true  );
				curl_setopt(	$ch, CURLOPT_POSTFIELDS, 	$postString );
		
				$result = curl_exec($ch);
				echo $result;

			}else{
				echo $jsoncallback."(".json_encode(array( "response" => "ERROR", "data" => array( "message" => "NOT ALL REQUIRED METHOD PARAMETERS HAVE BEEN MET! [The API actively refused the connection]", "command" => ""))).")";
				exit;
			}

		}

/*END OF CLASS*/
	}


?>
