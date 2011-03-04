<?php
	
	class EmailBuilderEmailResult {
		protected $attachments;
		protected $body;
		protected $headers;
		
		public function __construct() {
			$this->attachments = array();
			$this->body = (object)array(
				'html'				=> null,
				'text'				=> null
			);
			$this->headers = (object)array(
				'recipient_address'	=> null,
				'subject'			=> null,
				'sender_name'		=> null,
				'sender_address'	=> null
			);
		}
		
		public function attachments() {
			return $this->attachments;
		}
		
		public function body() {
			return $this->body;
		}
		
		public function headers() {
			return $this->headers;
		}
	}
	
?>