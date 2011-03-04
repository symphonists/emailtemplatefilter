<?php
	
	class EmailBuilderOverride {
		protected $data;
		protected $errors;
		
		public function __construct($data = array()) {
			$this->data = (object)$data;
		}
		
		public function data() {
			return $this->data;
		}
		
		public function errors() {
			return $this->errors;
		}
		
		public function save() {
			$fields = array(
				'email_id'			=> null,
				'expression'		=> null,
				'sortorder'			=> null,
				'subject'			=> null,
				'sender_name'		=> null,
				'sender_address'	=> null,
				'recipient_address'	=> null
			);
			
			foreach ($fields as $key => $value) {
				if (!isset($this->data->{$key})) continue;
				
				$fields[$key] = $this->data()->{$key};
			}
			
			return Symphony::Database()->insert($fields, 'tbl_etf_overrides');
		}
		
		public function validate() {
			$valid = true;
			
			if (!isset($this->data->expression) || trim($this->data->expression) == '') {
				$this->errors->expression = __('Expression must not be empty.');
				$valid = false;
			}
			
			return $valid;
		}
	}
	
?>