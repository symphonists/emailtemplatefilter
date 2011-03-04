<?php
	
	require_once(TOOLKIT . '/class.administrationpage.php');
	
	class EmailBuilderPage extends AdministrationPage {
		protected $root_url;
		
		public function __construct() {
			parent::__construct(Symphony::Engine());
			
			$data = Symphony::Engine()->getPageCallback();
			
			$this->root_url = sprintf(
				'%s%s', SYMPHONY_URL, dirname($data['pageroot'])
			);
		}
	}
	
?>