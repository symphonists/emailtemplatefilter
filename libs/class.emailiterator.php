<?php
	
	class EmailBuilderEmailIterator extends ArrayIterator {
		public function __construct() {
			parent::__construct(
				Symphony::Database()->fetch("
					SELECT
						t.*
					FROM
						`tbl_etf_emails` AS t
					ORDER BY
						t.name ASC
				")
			);
		}
		
		public function current() {
			return new EmailBuilderEmail(parent::current());
		}
	}
	
?>