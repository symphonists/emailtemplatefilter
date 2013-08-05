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

		public function __construct(){
			parent::__construct();

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
			$this->setTitle(__('Symphony &ndash; Email Logs'));
			$this->appendSubheading(__('Logs'));

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
				array(__('Date'), 'col'),
				array(__('Subject'), 'col'),
				array(__('Sender Name'), 'col'),
				array(__('Senders'), 'col'),
				array(__('Recipients'), 'col'),
				array(__('Template'), 'col'),
				//array(__('Section'), 'col'),
				array(__('Entry'), 'col')
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
						DateTimeObj::format(strtotime($log_date), __SYM_DATETIME_FORMAT__),
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
					$col_subject = Widget::TableData(__('None'), 'inactive');
				}

				if (!empty($log_sender)) {
					$col_sender = Widget::TableData(
						General::sanitize($log_sender)
					);
				}

				else {
					$col_sender = Widget::TableData(__('None'), 'inactive');
				}

				if (!empty($log_senders)) {
					$col_senders = Widget::TableData(
						General::sanitize($log_senders)
					);
				}

				else {
					$col_senders = Widget::TableData(__('None'), 'inactive');
				}

				if (!empty($log_recipients)) {
					$col_recipients = Widget::TableData(
						General::sanitize($log_recipients)
					);
				}

				else {
					$col_recipients = Widget::TableData(__('None'), 'inactive');
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
					$col_template = Widget::TableData(__('None'), 'inactive');
				}

				$entries = $entryManager->fetch($log_entry_id, null, null, null, null, null, false, true);

				if (!empty($entries) and $entry = $entries[0]) {
					$section_id = $entry->get('section_id');
					$section = $sectionManager->fetch($section_id);
					$column = array_shift($section->fetchVisibleColumns());

					$data = $entry->getData($column->get('id'));
					$link = Widget::Anchor(__('None'), URL . '/symphony/publish/' . $section->get('handle') . '/edit/' . $entry->get('id') . '/', $entry->get('id'), 'content');

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
					$col_entry = Widget::TableData(__('None'), 'inactive');
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
				array(null, false, __('With Selected...')),
				array('delete', false, __('Delete'))
			);

			$actions->appendChild(Widget::Select('with-selected', $options));
			$actions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

			$this->Form->appendChild($actions);

			// Pagination:
			if ($pages > 1) {
				$ul = new XMLElement('ul');
				$ul->setAttribute('class', 'page');

				## First
				$li = new XMLElement('li');

				if ($page > 1) {
					$li->appendChild(
						Widget::Anchor(__('First'), Symphony::Engine()->getCurrentPageURL() . '?pg=1')
					);
				}

				else {
					$li->setValue(__('First'));
				}

				$ul->appendChild($li);

				## Previous
				$li = new XMLElement('li');

				if ($page > 1) {
					$li->appendChild(
						Widget::Anchor(__('&larr; Previous'), Symphony::Engine()->getCurrentPageURL(). '?pg=' . ($page - 1))
					);
				}

				else {
					$li->setValue(__('&larr; Previous'));
				}

				$ul->appendChild($li);

				## Summary
				$li = new XMLElement('li', __('Page %1$s of %2$s', array($page, max($page, $pages))));

				$li->setAttribute('title', __('Viewing %1$s - %2$s of %3$s entries', array($start, $end, $total)));

				$ul->appendChild($li);

				## Next
				$li = new XMLElement('li');

				if ($page < $pages) {
					$li->appendChild(
						Widget::Anchor(__('Next &rarr;'), Symphony::Engine()->getCurrentPageURL(). '?pg=' . ($page + 1))
					);
				}

				else {
					$li->setValue(__('Next &rarr;'));
				}

				$ul->appendChild($li);

				## Last
				$li = new XMLElement('li');

				if ($page < $pages) {
					$li->appendChild(
						Widget::Anchor(__('Last'), Symphony::Engine()->getCurrentPageURL(). '?pg=' . $pages)
					);
				}

				else {
					$li->setValue(__('Last'));
				}

				$ul->appendChild($li);
				$this->Form->appendChild($ul);
			}
		}
	}
