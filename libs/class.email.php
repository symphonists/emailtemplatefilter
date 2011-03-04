<?php
	
	class EmailBuilderEmail {
		static public function deleteAll($items) {
			$result = array();
			
			foreach ($items as $id) {
				$result[] = self::load($id)->delete();
			}
			
			return array_sum($result) == count($result);
		}
		
		static public function exists($id) {
			$data = Symphony::Database()->fetch(sprintf("
				SELECT
					e.*
				FROM
					`tbl_etf_emails` AS e
				WHERE
					e.id = %d
				",
				$id
			));
			
			return isset($data[0]['id']);
		}
		
		static public function load($id) {
			$data = Symphony::Database()->fetch(sprintf("
				SELECT
					e.*
				FROM
					`tbl_etf_emails` AS e
				WHERE
					e.id = %d
				",
				$id
			));
			$overrides = Symphony::Database()->fetch(sprintf("
				SELECT
					o.*
				FROM
					`tbl_etf_overrides` AS o
				WHERE
					o.email_id = %d
				ORDER BY
					o.sortorder ASC
				",
				$id
			));
			
			$email = new self();
			$email->setData($data[0]);
			$email->setOverrides($overrides);
			
			return $email;
		}
		
		protected $data;
		protected $errors;
		protected $overrides;
		
		public function __construct() {
			$this->data = (object)array();
			$this->errors = (object)array();
			$this->overrides = array();
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
		
		public function delete() {
			$result = array();
			
			$result[] = Symphony::Database()->query(sprintf("
				DELETE FROM
					`tbl_etf_emails`
				WHERE
					`id` = %d
				",
				$this->data->id
			));
			
			$result[] = Symphony::Database()->query(sprintf("
				DELETE FROM
					`tbl_etf_logs`
				WHERE
					`email_id` = %d
				",
				$this->data->id
			));
			
			$result[] = Symphony::Database()->query(sprintf("
				DELETE FROM
					`tbl_etf_overrides`
				WHERE
					`email_id` = %d
				",
				$this->data->id
			));
			
			return array_sum($result) == count($result);
		}
		
		public function getPreviewURL($entry_id) {
			$page = Symphony::Database()->fetchRow(0, sprintf("
				SELECT
					p.path,
					p.handle
				FROM
					`tbl_pages` as p
				WHERE
					p.id = %d
				",
				$this->data->page_id
			));
			
			$path = trim(rtrim($page['path'], '/') . '/' . $page['handle'], '/');
			
			return sprintf(
				'%s/%s?eb-entry-id=%d',
				URL, $path, $entry_id
			);
		}
		
		public function setData($data) {
			$this->data = (object)array();
			
			foreach ($data as $key => $value) {
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
		
		public function save() {
			$result = array();
			$fields = array(
				'id'				=> null,
				'page_id'			=> null,
				'name'				=> null,
				'subject'			=> null,
				'sender_name'		=> null,
				'sender_address'	=> null,
				'recipient_address'	=> null
			);
			
			foreach ($fields as $key => $value) {
				if (!isset($this->data->{$key})) continue;
				
				$fields[$key] = $this->data->{$key};
			}
			
			$result[] = Symphony::Database()->insert($fields, 'tbl_etf_emails', true);
			
			if (!isset($this->data->id)) {
				$this->data->id = Symphony::Database()->getInsertID();
			}
			
			// Delete old overrides:
			$result[] = Symphony::Database()->query(sprintf("
				DELETE FROM
					`tbl_etf_overrides`
				WHERE
					`email_id` = %d
				",
				$this->data->id
			));
			
			// Insert new overrides:
			foreach ($this->overrides as $override) {
				$override->data()->email_id = $this->data->id;
				$result[] = $override->save();
			}
			
			return array_sum($result) == count($result);
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
		}
	}
	
?>