<?php
	
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.entrymanager.php');
	require_once(dirname(__FILE__) . '/libs/class.email.php');
	require_once(dirname(__FILE__) . '/libs/class.emailiterator.php');
	require_once(dirname(__FILE__) . '/libs/class.override.php');
	
	class Extension_EmailBuilder extends Extension {
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/
		
		public static $params = array();
		public static $page = null;
		
		public function about() {
			return array(
				'name'			=> 'Email Builder',
				'version'		=> '1.0',
				'release-date'	=> '2011-03-03',
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://rowanlewis.com/',
					'email'			=> 'me@rowanlewis.com'
				)
			);
		}
		
		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_etf_emails`");
			Symphony::Database()->query("DROP TABLE `tbl_etf_overrides`");
			Symphony::Database()->query("DROP TABLE `tbl_etf_parameters`");
			Symphony::Database()->query("DROP TABLE `tbl_etf_logs`");
		}
		
		public function install() {
			$drop = array();
			
			try {
				Symphony::Database()->query("
					CREATE TABLE IF NOT EXISTS `tbl_etf_emails` (
						`id` int(11) unsigned not null auto_increment,
						`page_id` int(11) not null,
						`name` varchar(255) not null,
						`subject` text not null,
						`sender_name` text not null,
						`sender_address` text not null,
						`recipient_address` text not null,
						PRIMARY KEY (`id`),
						KEY `page_id` (`page_id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8;
				");
				$drop[] = 'tbl_etf_emails';
				
				Symphony::Database()->query("
					CREATE TABLE IF NOT EXISTS `tbl_etf_overrides` (
						`id` int(11) not null auto_increment,
						`email_id` int(11) not null,
						`page_id` int(11) not null,
						`expression` text not null,
						`sortorder` int(11) not null,
						`subject` text not null,
						`sender_name` text not null,
						`sender_address` text not null,
						`recipient_address` text not null,
						PRIMARY KEY (`id`),
						KEY `email_id` (`email_id`),
						KEY `page_id` (`page_id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8;
				");
				$drop[] = 'tbl_etf_overrides';
				
				Symphony::Database()->query("
					CREATE TABLE IF NOT EXISTS `tbl_etf_parameters` (
						`id` int(11) not null auto_increment,
						`email_id` int(11) not null,
						`override_id` int(11) not null,
						`name` varchar(255) not null,
						`expression` text not null,
						PRIMARY KEY (`id`),
						KEY `email_id` (`email_id`),
						KEY `override_id` (`override_id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8;
				");
				$drop[] = 'tbl_etf_parameters';
				
				Symphony::Database()->query("
					CREATE TABLE IF NOT EXISTS `tbl_etf_logs` (
						`id` int(11) NOT NULL auto_increment,
						`email_id` int(11) not null,
						`entry_id` int(11) NOT NULL,
						`date` datetime NOT NULL,
						`success` enum('yes','no') NOT NULL,
						`sender_name` text,
						`sender_address` text,
						`recipient_address` text,
						`subject` text,
						`message` text,
						PRIMARY KEY (`id`),
						KEY `email_id` (`email_id`),
						KEY `entry_id` (`entry_id`),
						KEY `date` (`date`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8;
				");
				
				return true;
			}
			
			catch (DatabaseException $e) {
				foreach ($drop as $table) {
					Symphony::Database()->query("DROP TABLE `{$table}`");
				}
				
				throw $e;
			}
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/blueprints/events/edit/',
					'delegate'	=> 'AppendEventFilter',
					'callback'	=> 'addFilterToEventEditor'
				),				
				array(
					'page'		=> '/blueprints/events/new/',
					'delegate'	=> 'AppendEventFilter',
					'callback'	=> 'addFilterToEventEditor'
				),
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'EventFinalSaveFilter',
					'callback'	=> 'eventFinalSaveFilter'
				),
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendPageResolved',
					'callback'	=> 'setPage'
				),
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendParamsResolve',
					'callback'	=> 'setParams'
				)
			);
		}
		
		public function fetchNavigation() {
			return array(
				array(
					'location'	=> __('Blueprints'),
					'name'		=> 'Emails',
					'link'		=> '/emails/'
				)
			);
		}
		
		public function setPage($context) {
			self::$page = $context['page'];
		}
		
		public function setParams($context) {
			self::$params = $context['params'];
		}
	}
	
?>