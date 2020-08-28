<?php

	class mailer{

		public $to, $from, $subject, $message, $template, $recipients;

		public function __construct($jsoncallback, $to, $from, $subject, $message, $template, $method){

			if( $to != "" && $from != "" && $message != "" ){
				$this->to 	= $to;
				$this->from	= $from;
				$this->subject	= $subject;
				$this->message	= $message;
				$this->template = $template;
				
				/* BEGIN CONSTRUCTING THE EMAIL LAYOUT */
				$this->buildMail();

			}else{

				return $jsoncallback."(".json_encode(array( "response" => "ERROR", "data" => array( "message" => "NOT ALL REQUIRED PARAMETERS HAVE BEEN MET!", "command" => ""))).")";

			}

		}

		public function buildMail(){

			/* Building a recipient list from the provided email addresses */
			$this->recipients['mandrill'] = "";

			$recipients  = explode( ",", $this->to['email'] );
			
			$numEmail = count($this->to['email']);
			$numNames = count($this->to['name']);

			$i = 0;

			foreach($recipients as $recipient){

				$name = ($this->to['name'][$i] != "" && $this->to["name"][$i] != NULL)? $this->to["name"][$i] : $this->to["name"];

				$this->recipients[''] += '{
							"email": 	'.@json_encode($recipient).',
							"name": 	'.@json_encode($name).'
						          }';

				$i++;
			}
			/* End of email recipient list builder */

			/* SEND THE EMAIL */
			$this->mailIt();

		}

		private function mailIt(){

			if( $this->method["type"] == "php" ){

				if(mail( $this->to, $this->from, $this->message, $this->headers[subject] )){ 

					echo $jsoncallback."(".json_encode(array( "response" => "SUCCESS", "data" => array( "message" => "EMAIL SENT!", "command" => ""))).")";
					exit;

				 }else{

					echo $jsoncallback."(".json_encode(array( "response" => "ERROR", "data" => array( "message" => "FAILED TO SEND EMAIL!", "command" => ""))).")";
					exit;
				
				}

			}elseif( $this->method["type"] == "mandrill" && $this->method["key"] != "" ){

				$uri = 'https://mandrillapp.com/api/1.0/messages/send.json';
				
				$postString = '{
				"key": '.@json_encode($this->method["key"]).',	/* DeFaUlT I&E API KEY "C2JgjLf0pN0os9FMvJrc7Q" */
				"message": {
					"html":  	'.@json_encode($this->message["html"]).',
					"text":  	'.@json_encode($this->message["text"]).',
					"subject": 	'.@json_encode($this->headers["subject"]).',
					"from_email": 	'.@json_encode($this->from["email"]).',
					"from_name": 	'.@json_encode($this->from["name"]).',
					"to": [
						'.
							$this->recipients
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
				curl_setopt(	$ch, CURLOPT_URL,		 $uri );
				curl_setopt(	$ch, CURLOPT_FOLLOWLOCATION, 	true  );
				curl_setopt(	$ch, CURLOPT_RETURNTRANSFER, 	true  );
				curl_setopt(	$ch, CURLOPT_SSL_VERIFYPEER, 	false );
				curl_setopt(	$ch, CURLOPT_POST, 		true  );
				curl_setopt(	$ch, CURLOPT_POSTFIELDS, 	$postString );
		
				//$result = curl_exec($ch);
				//echo $result;

			}else{
				echo $jsoncallback."(".json_encode(array( "response" => "ERROR", "data" => array( "message" => "NOT ALL REQUIRED PARAMETERS HAVE BEEN MET! [The API actively refused the connection]", "command" => ""))).")";
				exit;
			}

		}


	}

?>
