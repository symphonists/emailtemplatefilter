<?php
	
	class EmailBuilderEmail {
		static public function deleteAll($items) {
			foreach ($items as $id) {
				Symphony::Database()->query("
					DELETE FROM
						`tbl_etf_emails`
					WHERE
						`id` = {$id}
				");
				
				Symphony::Database()->query("
					DELETE FROM
						`tbl_etf_logs`
					WHERE
						`email_id` = {$id}
				");
				
				Symphony::Database()->query("
					DELETE FROM
						`tbl_etf_overrides`
					WHERE
						`email_id` = {$id}
				");
				
				Symphony::Database()->query("
					DELETE FROM
						`tbl_etf_parameters`
					WHERE
						`email_id` = {$id}
				");
			}
		}
		
		protected $data;
		protected $errors;
		protected $overrides;
		
		public function __construct($data = array()) {
			$this->setData($data);
		}
		
		public function data() {
			return $this->data;
		}
		
		public function errors() {
			return $this->errors;
		}
		
		public function overrides() {
			return $this->overrides;
		}
		
		public function countLogs() {
			return 0;
		}
		
		public function setData($data) {
			$this->data = (object)$data;
			
			foreach ($data['fields'] as $key => $value) {
				$this->data->{$key} = $value;
			}
		}
		
		public function setOverrides($overrides) {
			$this->overrides = array();
			
			foreach ($overrides as $order => $override) {
				$this->overrides[$order] = new EmailBuilderOverride($override);
				$this->overrides[$order]->data()->sortorder = $order;
			}
		}
		
		public function validate() {
			$this->errors = new StdClass();
			$valid = true;
			
			if (!isset($this->data->name) || trim($this->data->name) == '') {
				$this->errors->name = __('Name must not be empty.');
				$valid = false;
			}
			
			if (!isset($this->data->subject) || trim($this->data->subject) == '') {
				$this->errors->subject = __('Subject must not be empty.');
				$valid = false;
			}
			
			if (!isset($this->data->sender_name) || trim($this->data->sender_name) == '') {
				$this->errors->sender_name = __('Sender Name must not be empty.');
				$valid = false;
			}
			
			if (!isset($this->data->sender_address) || trim($this->data->sender_address) == '') {
				$this->errors->sender_address = __('Sender Address must not be empty.');
				$valid = false;
			}
			
			if (!isset($this->data->recipient_address) || trim($this->data->recipient_address) == '') {
				$this->errors->recipient_address = __('Recipient Address must not be empty.');
				$valid = false;
			}
			
			if (!isset($this->data->page_id) || trim($this->data->page_id) == '') {
				$this->errors->page_id = __('You must choose a template page.');
				$valid = false;
			}
			
			foreach ($this->overrides as $order => $override) {
				$valid = (
					$override->validate()
						? $valid
						: false
				);
			}
			
			return $valid;
			
			/*
			foreach ($this->_conditions as $sortorder => $condition) {
				if (empty($condition['subject'])) {
					$this->errors["{$sortorder}:subject"] = __('Subject must not be empty.');
				}
				
				if (empty($condition['sender'])) {
					$this->errors["{$sortorder}:sender"] = __('Sender Name must not be empty.');
				}
				
				if (empty($condition['senders'])) {
					$this->errors["{$sortorder}:senders"] = __('Senders must not be empty.');
				}
				
				if (empty($condition['recipients'])) {
					$this->errors["{$sortorder}:recipients"] = __('Recipients must not be empty.');
				}
				
				if (empty($condition['page'])) {
					$this->errors["{$sortorder}:page"] = __('Page must not be empty.');
				}
			}
			*/
		}
	}
	
?>