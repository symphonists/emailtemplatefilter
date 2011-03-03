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