<?php
	
	class EmailBuilderEmailIterator extends ArrayIterator {
		public function __construct() {
			parent::__construct(
				 Symphony::Database()->fetchCol('id', "
					SELECT
						t.id
					FROM
						`tbl_etf_emails` AS t
					ORDER BY
						t.name ASC
				")
			);
		}
		
		public function current() {
			return EmailBuilderEmail::load(parent::current());
		}
	}
	
?>