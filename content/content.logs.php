<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	
	class contentExtensionEmailTemplateFilterLogs extends AdministrationPage {
		protected $_errors = array();
		protected $_fields = array();
		protected $_action = '';
		protected $_status = '';
		protected $_template = 0;
		protected $_valid = false;
		protected $_editing = false;
		protected $_prepared = false;
		protected $_driver = null;
		protected $_conditions = array();
		
		public function __construct(&$parent){
			parent::__construct($parent);
			
			$this->_driver = Symphony::ExtensionManager()->create('emailtemplatefilter');
		}
		
		public function __viewPreview() {
			$log = $this->_driver->getLog(
				isset($this->_context[1]) && is_numeric($this->_context[1])
					? (integer)$this->_context[1]
					: 0
			);
			
			header('content-type: text/html; charset=utf-8');
			
			echo $log['message'];
			
			exit;
		}
		
		public function __actionIndex() {
			$checked = (
				(isset($_POST['items']) && is_array($_POST['items']))
					? array_keys($_POST['items'])
					: null
			);
			
			if (is_array($checked) and !empty($checked)) {
				switch ($_POST['with-selected']) {
					case 'delete':
						foreach ($checked as $log_id) {
							Symphony::Database()->query("
								DELETE FROM
									`tbl_etf_logs`
								WHERE
									`id` = {$log_id}
							");
						}
						
						redirect(URL . '/symphony/extension/emailtemplatefilter/logs/');
						break;
				}
			}
		}
		
		public function __viewIndex() {
			$this->setPageType('table');
			$this->setTitle('Symphony &ndash; Email Logs');
			$this->appendSubheading('Logs');
			
			$page = (
				isset($_GET['pg']) && (integer)$_GET['pg'] > 1
					? (integer)$_GET['pg']
					: 1
			);
			$logs = $this->_driver->getLogs($page);
			$start = max(1, (($page - 1) * 17));
			$end = ($start == 1 ? 17 : $start + count($logs));
			$total = $this->_driver->countLogs();
			$pages = ceil($total / 17);
			
			$sectionManager = new SectionManager(Symphony::Engine());
			$entryManager = new EntryManager(Symphony::Engine());
			
			$tableHead = array(
				array('Date', 'col'),
				array('Subject', 'col'),
				array('Sender Name', 'col'),
				array('Senders', 'col'),
				array('Recipients', 'col'),
				array('Template', 'col'),
				//array('Section', 'col'),
				array('Entry', 'col')
			);
			
			$tableBody = array();
			
			if (!is_array($logs) or empty($logs)) {
				$tableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None Found.'), 'inactive', null, count($tableHead))))
				);
			}
			
			else foreach ($logs as $log) {
				extract($log, EXTR_PREFIX_ALL, 'log');
				
				$col_date = Widget::TableData(
					Widget::Anchor(
						DateTimeObj::get(__SYM_DATETIME_FORMAT__, strtotime($log_date)),
						URL . "/symphony/extension/emailtemplatefilter/logs/preview/{$log_id}/"
					)
				);
				$col_date->appendChild(Widget::Input("items[{$log_id}]", null, 'checkbox'));
				
				if (!empty($log_subject)) {
					$col_subject = Widget::TableData(
						General::sanitize($log_subject)
					);
				}
				
				else {
					$col_subject = Widget::TableData('None', 'inactive');
				}
				
				if (!empty($log_sender)) {
					$col_sender = Widget::TableData(
						General::sanitize($log_sender)
					);
				}
				
				else {
					$col_sender = Widget::TableData('None', 'inactive');
				}
				
				if (!empty($log_senders)) {
					$col_senders = Widget::TableData(
						General::sanitize($log_senders)
					);
				}
				
				else {
					$col_senders = Widget::TableData('None', 'inactive');
				}
				
				if (!empty($log_recipients)) {
					$col_recipients = Widget::TableData(
						General::sanitize($log_recipients)
					);
				}
				
				else {
					$col_recipients = Widget::TableData('None', 'inactive');
				}
				
				if ($template = $this->_driver->getTemplate($log_template_id)) {
					$col_template = Widget::TableData(
						Widget::Anchor(
							General::sanitize($template['name']),
							URL . "/symphony/extension/emailtemplatefilter/templates/edit/{$log_template_id}/"
						)
					);
				}
				
				else {
					$col_template = Widget::TableData('None', 'inactive');
				}
				
				$entries = $entryManager->fetch($log_entry_id, null, null, null, null, null, false, true);
				
				if (!empty($entries) and $entry = $entries[0]) {
					$section_id = $entry->get('section_id');
					$section = $sectionManager->fetch($section_id);
					$column = array_shift($section->fetchVisibleColumns());
					
					$data = $entry->getData($column->get('id'));
					$link = Widget::Anchor('None', URL . '/symphony/publish/' . $section->get('handle') . '/edit/' . $entry->get('id') . '/', $entry->get('id'), 'content');
					
					/*
					$col_section = Widget::TableData(
						Widget::Anchor(
							General::sanitize($section->get('name')),
							URL . '/symphony/publish/' . $section->get('handle') . '/'
						)
					);
					*/
					
					$col_entry = Widget::TableData($column->prepareTableValue($data, $link));
				}
				
				else {
					//$col_section = Widget::TableData('None', 'inactive');
					$col_entry = Widget::TableData('None', 'inactive');
				}
				
				$tableBody[] = Widget::TableRow(
					array(
						$col_date, $col_subject, $col_sender,
						$col_senders, $col_recipients,
						$col_template, $col_entry
					)
				);
			}
			
			$table = Widget::Table(
				Widget::TableHead($tableHead), null, 
				Widget::TableBody($tableBody)
			);
			$table->setAttribute('class', 'selectable');
			
			$this->Form->appendChild($table);
			
			$actions = new XMLElement('div');
			$actions->setAttribute('class', 'actions');
			
			$options = array(
				array(null, false, 'With Selected...'),
				array('delete', false, 'Delete')									
			);

			$actions->appendChild(Widget::Select('with-selected', $options));
			$actions->appendChild(Widget::Input('action[apply]', 'Apply', 'submit'));
			
			$this->Form->appendChild($actions);
			
			// Pagination:
			if ($pages > 1) {
				$ul = new XMLElement('ul');
				$ul->setAttribute('class', 'page');
				
				## First
				$li = new XMLElement('li');
				
				if ($page > 1) {
					$li->appendChild(
						Widget::Anchor('First', Symphony::Engine()->getCurrentPageURL() . '?pg=1')
					);
				}
				
				else {
					$li->setValue('First');
				}
				
				$ul->appendChild($li);
				
				## Previous
				$li = new XMLElement('li');
				
				if ($page > 1) {
					$li->appendChild(
						Widget::Anchor('&larr; Previous', Symphony::Engine()->getCurrentPageURL(). '?pg=' . ($page - 1))
					);
				}
				
				else {
					$li->setValue('&larr; Previous');
				}
				
				$ul->appendChild($li);

				## Summary
				$li = new XMLElement('li', 'Page ' . $page . ' of ' . max($page, $pages));
				
				$li->setAttribute('title', 'Viewing ' . $start . ' - ' . $end . ' of ' . $total . ' entries');
				
				$ul->appendChild($li);

				## Next
				$li = new XMLElement('li');
				
				if ($page < $pages) {
					$li->appendChild(
						Widget::Anchor('Next &rarr;', Symphony::Engine()->getCurrentPageURL(). '?pg=' . ($page + 1))
					);
				}
				
				else {
					$li->setValue('Next &rarr;');
				}
				
				$ul->appendChild($li);

				## Last
				$li = new XMLElement('li');
				
				if ($page < $pages) {
					$li->appendChild(
						Widget::Anchor('Last', Symphony::Engine()->getCurrentPageURL(). '?pg=' . $pages)
					);
				}
				
				else {
					$li->setValue('Last');
				}
				
				$ul->appendChild($li);
				$this->Form->appendChild($ul);	
			}
		}
	}
	
?>