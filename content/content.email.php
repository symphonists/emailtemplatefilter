<?php
	
	require_once(EXTENSIONS . '/emailbuilder/libs/class.page.php');
	
	class ContentExtensionEmailBuilderEmail extends EmailBuilderPage {
		protected $email;
		protected $errors;
		
		public function getPageOptions($page_id, $data = array()) {
			$pages = Symphony::Database()->fetch("
				SELECT
					p.*
				FROM
					`tbl_pages` AS p
				ORDER BY
					`sortorder` ASC
			");
			$result = array();
			
			foreach ($pages as $page) {
				$page = (object)$page;
				$path = '';
				
				if ($page->path) {
					$path = '/' . $page->path;
				}
				
				$path .= '/' . $page->handle;
				
				$result[] = (object)array(
					'id'	=> $page->id,
					'path'	=> $path
				);
			}
			
			sort($result);
			
			foreach ($result as $page) {
				$selected = ($page->id == $page_id);
				$data[] = array(
					$page->id, $selected, $page->path
				);
			}
			
			return $data;
		}
		
		public function getAttachmentOptions($type, $data = array()) {
			/**
			 * Ask other extensions for alternative attachments handlers.
			 *
			 * @delegate AppendAttachmentHandler
			 * @param string $context
			 * '/extension/emailbuilder/'
			 * @param string $type
			 * @param array $options
			 */
			Symphony::ExtensionManager()->notifyMembers(
				'AppendAttachmentHandler',
				'/extension/emailbuilder/',
				array(
					'type'		=> &$type,
					'options' 	=> &$data
				)
			);
			
			return $data;
		}
		
		public function getPlainTextOptions($type, $data = array()) {
			/**
			 * Ask other extensions for alternative plain text handlers.
			 *
			 * @delegate AppendPlainTextHandler
			 * @param string $context
			 * '/extension/emailbuilder/'
			 * @param string $type
			 * @param array $options
			 */
			Symphony::ExtensionManager()->notifyMembers(
				'AppendPlainTextHandler',
				'/extension/emailbuilder/',
				array(
					'type'		=> &$type,
					'options' 	=> &$data
				)
			);
			
			return $data;
		}
		
		public function build($context) {
			// Load existing email:
			if (isset($context[0]) && EmailBuilderEmail::exists($context[0])) {
				$this->email = EmailBuilderEmail::load($context[0]);
			}
			
			// Create new email:
			else {
				$this->email = new EmailBuilderEmail();
			}
			
			return parent::build($context);
		}
		
		public function action() {
			$email = $this->email;
			
			// Delete:
			if (isset($_POST['action']['delete'])) {
				if ($email->delete()) {
					redirect(sprintf(
						'%s/emails/',
						$this->root_url
					));
				}
				
				$this->pageAlert(
					__('An error occurred while processing this form. <a href="#error">See below for details.</a>'),
					Alert::ERROR
				);
			}
			
			// Edit or create:
			else {
				$action = (
					isset($email->data()->id)
						? 'saved'
						: 'created'
				);
				
				// Update email with post data:
				if (isset($_POST['fields']) && is_array($_POST['fields'])) {
					$email->setData($_POST['fields']);
				}
				
				if (isset($_POST['overrides'])) {
					$email->setOverrides($_POST['overrides']);
				}
				
				else {
					$email->setOverrides(array());
				}
				
				// Email passes validation:
				if ($email->validate() && $email->save()) {
					redirect(sprintf(
						'%s/email/%d/%s/',
						$this->root_url,
						$email->data()->id,
						$action
					));
				}
				
				$this->pageAlert(
					__('An error occurred while processing this form. <a href="#error">See below for details.</a>'),
					Alert::ERROR
				);
			}
		}
		
		public function view() {
			$email = $this->email;
			
			// Use 'Untitled' as page title when email name is empty:
			$title = (
				isset($email->data()->name) && trim($email->data()->name) != ''
					? $email->data()->name
					: __('Untitled')
			);
			
			$this->setPageType('form');
			$this->setTitle(__(
				(
					isset($email->data()->id)
						? '%1$s &ndash; %2$s &ndash; %3$s'
						: '%1$s &ndash; %2$s'
				),
				array(
					__('Symphony'),
					__('Emails'),
					$title
				)
			));
			$this->appendSubheading($title, (
				(
					isset($email->data()->id)
						?  Widget::Anchor(
							__('Preview Email'),
							sprintf(
								'%s/preview/%d/',
								$this->root_url,
								$email->data()->id
							),
							__('Preview Email'),
							'button'
						)
						: null
				)
			));
			$this->addScriptToHead(URL . '/extensions/emailbuilder/assets/email.js');
			
			// Status message:
			if (isset($this->_context[1])) {
				$action = null;
				
				switch ($this->_context[1]) {
					case 'saved': $action = '%1$s updated at %2$s. <a href="%3$s">Create another?</a> <a href="%4$s">View all %5$s</a>'; break;
					case 'created': $action = '%1$s created at %2$s. <a href="%3$s">Create another?</a> <a href="%4$s">View all %5$s</a>'; break;
				}
				
				if ($action) $this->pageAlert(
					__(
						$action, array(
							__('Email'), 
							DateTimeObj::get(__SYM_TIME_FORMAT__), 
							URL . '/symphony/extension/emailbuilder/email/', 
							URL . '/symphony/extension/emailbuilder/emails/',
							__('Emails')
						)
					),
					Alert::SUCCESS
				);
			}
			
			$this->appendEssentialsFieldset($email, $this->Form);
			$this->appendContentFieldset($email, $this->Form);
			//$this->appendTemplateFieldset($email, $this->Form);
			$this->appendOverridesFieldset($email, $this->Form);
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(
				Widget::Input('action[save]',
					(
						isset($email->data()->id)
							? __('Save Changes')
							: __('Create Template')
					),
					'submit', array(
						'accesskey'		=> 's'
					)
				)
			);
			
			if (isset($email->data()->id)) {
				$button = new XMLElement('button', 'Delete');
				$button->setAttributeArray(array(
					'name'		=> 'action[delete]',
					'class'		=> 'button confirm delete',
					'title'		=> __('Delete this email')
				));
				$div->appendChild($button);
			}
			
			$this->Form->appendChild($div);
		}
		
		public function appendContentFieldset($email, $wrapper) {
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Content')));
			
			$help = new XMLElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue(__(
				'To access the XML of your template page, use XPath expressions:<br /><code>%s</code>.',
				array('{datasource/entry/field-one}')
			));
			
			$fieldset->appendChild($help);
			
			// Subject:
			$label = Widget::Label(__('Subject'));
			$label->appendChild(Widget::Input(
				'fields[subject]',
				General::sanitize($email->data()->subject)
			));
			
			if (isset($email->errors()->subject)) {
				$label = Widget::wrapFormElementWithError($label, $email->errors()->subject);
			}
			
			$fieldset->appendChild($label);
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			// Sender Name
			$label = Widget::Label(__('Sender Name'));
			$label->appendChild(Widget::Input(
				'fields[sender_name]',
				General::sanitize($email->data()->sender_name)
			));
			
			if (isset($email->errors()->sender_name)) {
				$label = Widget::wrapFormElementWithError($label, $email->errors()->sender_name);
			}
			
			$group->appendChild($label);
			
			// Senders
			$label = Widget::Label(__('Sender Address'));
			$label->appendChild(Widget::Input(
				'fields[sender_address]',
				General::sanitize($email->data()->sender_address)
			));
			
			if (isset($email->errors()->sender_address)) {
				$label = Widget::wrapFormElementWithError($label, $email->errors()->sender_address);
			}
			
			$group->appendChild($label);
			$fieldset->appendChild($group);
			
			// Recipients
			$label = Widget::Label(__('Recipient Address'));
			$label->appendChild(Widget::Input(
				'fields[recipient_address]',
				General::sanitize($email->data()->recipient_address)
			));
			
			if (isset($email->errors()->recipient_address)) {
				$label = Widget::wrapFormElementWithError($label, $email->errors()->recipient_address);
			}
			
			$fieldset->appendChild($label);
			$wrapper->appendChild($fieldset);
		}
		
		public function appendEssentialsFieldset($email, $wrapper) {
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));
			
			if (!empty($email->data()->id)) {
				$fieldset->appendChild(Widget::Input(
					'fields[id]', $email->data()->id, 'hidden'
				));
			}
			
			// Name:
			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input(
				'fields[name]',
				General::sanitize($email->data()->name)
			));
			
			if (isset($email->errors()->name)) {
				$label = Widget::wrapFormElementWithError($label, $email->errors()->name);
			}
			
			$fieldset->appendChild($label);
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			// Attachments:
			$label = Widget::Label(__('Attachments'));
			$options = $this->getAttachmentOptions(
				$email->data()->send_attachments,
				array(
					array(null, false, __('Don\'t send attachments'))
				)
			);
			
			$select = Widget::Select(
				"fields[send_attachments]", $options
			);
			$select->setAttribute('class', 'page-picker');
			$label->appendChild($select);
			
			if (isset($email->errors()->send_attachments)) {
				$label = Widget::wrapFormElementWithError($label, $email->errors()->send_attachments);
			}
			
			$group->appendChild($label);
			
			// Plain Text:
			$label = Widget::Label(__('Plain Text'));
			$options = $this->getPlainTextOptions(
				$email->data()->send_plain_text,
				array(
					array(null, false, __('Don\'t send plain text'))
				)
			);
			
			$select = Widget::Select(
				"fields[send_plain_text]", $options
			);
			$select->setAttribute('class', 'page-picker');
			$label->appendChild($select);
			
			if (isset($email->errors()->send_plain_text)) {
				$label = Widget::wrapFormElementWithError($label, $email->errors()->send_plain_text);
			}
			
			$group->appendChild($label);
			$fieldset->appendChild($group);
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			// HTML Page:
			$label = Widget::Label(__('Template Page'));
			$options = $this->getPageOptions(
				$email->data()->page_id,
				array(
					array(null, false, __('Choose one...'))
				)
			);
			
			$select = Widget::Select(
				"fields[page_id]", $options
			);
			$select->setAttribute('class', 'page-picker');
			$label->appendChild($select);
			
			if (isset($email->errors()->page_id)) {
				$label = Widget::wrapFormElementWithError($label, $email->errors()->page_id);
			}
			
			$group->appendChild($label);
			$fieldset->appendChild($group);
			
			$help = new XMLElement('p');
			$help->setAttribute('class', 'help');
			$help->setAttribute('style', 'clear: both;');
			$help->setValue(__('The <code>%s</code> parameter can be used by any datasources on your template page.', array('$etf-entry-id')));
			
			$fieldset->appendChild($help);
			$wrapper->appendChild($fieldset);
		}
		
		public function appendOverridesFieldset($email, $wrapper) {
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Overrides')));
			
			$help = new XMLElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue(__('An override changes the above content when its expression matches an XML element or is <code>true()</code>.'));
			
			$fieldset->appendChild($help);
			
			$ol = new XMLElement('ol');
			$ol->setAttribute('id', 'email-conditions-duplicator');
			
			// Add existing conditions:
			foreach ($email->overrides() as $order => $override) {
				$item = new XMLElement('li');
				$this->appendOverrideItem($override, $item, $order);
				$ol->appendChild($item);
			}
			
			// Add condition template:
			$item = new XMLElement('li');
			$item->setAttribute('class', 'template');
			$this->appendOverrideItem(new EmailBuilderOverride(), $item, -1);
			$ol->appendChild($item);
			
			$fieldset->appendChild($ol);
			$wrapper->appendChild($fieldset);
		}
		
		public function appendOverrideItem($override, $wrapper, $order) {
			$prefix = "overrides[{$order}]";
			
			$wrapper->appendChild(new XMLElement('h4', __('Override')));
			
			if (isset($override->data()->id)) {
				$wrapper->appendChild(Widget::Input(
					"{$prefix}[id]",
					$override->data()->id, 'hidden'
				));
			}
			
			// Expression
			$label = Widget::Label(__('Expression'));
			$label->appendChild(Widget::Input(
				"{$prefix}[expression]",
				General::sanitize(
					isset($override->data()->expression)
						? $override->data()->expression
						: null
				)
			));
			
			if (isset($override->errors()->expression)) {
				$label = Widget::wrapFormElementWithError($label, $override->errors()->expression);
			}
			
			$wrapper->appendChild($label);
			
			// Subject
			$fieldset = new XMLElement('fieldset');
			$fieldset->appendChild(new XMLElement('legend', __('Content')));
			
			$label = Widget::Label(__('Subject'));
			$label->appendChild(Widget::Input(
				"{$prefix}[subject]",
				General::sanitize(
					isset($override->data()->subject)
						? $override->data()->subject
						: null
				)
			));
			
			if (isset($override->errors()->subject)) {
				$label = Widget::wrapFormElementWithError($label, $override->errors()->subject);
			}
			
			$fieldset->appendChild($label);
			
			// Sender Name
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			$label = Widget::Label(__('Sender Name'));
			$label->appendChild(Widget::Input(
				"{$prefix}[sender_name]",
				General::sanitize(
					isset($override->data()->sender_name)
						? $override->data()->sender_name
						: null
				)
			));
			
			if (isset($override->errors()->sender_name)) {
				$label = Widget::wrapFormElementWithError($label, $override->errors()->sender_name);
			}
			
			$group->appendChild($label);
			
			// Senders
			$label = Widget::Label(__('Sender Address'));
			$label->appendChild(Widget::Input(
				"{$prefix}[sender_address]",
				General::sanitize(
					isset($override->data()->sender_address)
						? $override->data()->sender_address
						: null
				)
			));
			
			if (isset($override->errors()->sender_address)) {
				$label = Widget::wrapFormElementWithError($label, $override->errors()->sender_address);
			}
			
			$group->appendChild($label);
			$fieldset->appendChild($group);
			
			// Recipients
			$label = Widget::Label(__('Recipient Address'));
			$label->appendChild(Widget::Input(
				"{$prefix}[recipient_address]",
				General::sanitize(
					isset($override->data()->recipient_address)
						? $override->data()->recipient_address
						: null
				)
			));
			
			if (isset($override->errors()->recipient_address)) {
				$label = Widget::wrapFormElementWithError($label, $override->errors()->recipient_address);
			}
			
			$fieldset->appendChild($label);
			$wrapper->appendChild($fieldset);
			
			// Page
			//$this->appendTemplateFieldset($override, $wrapper, $prefix);
		}
		
		public function appendTemplateFieldset($email, $wrapper, $prefix = 'fields') {
			$fieldset = new XMLElement('fieldset');
			$fieldset->appendChild(new XMLElement('legend', __('Template')));
			
			if ($prefix == 'fields') {
				$fieldset->setAttribute('class', 'settings');
				
				$help = new XMLElement('p');
				$help->setAttribute('class', 'help');
				$help->setValue(__('The <code>%s</code> parameter can be used by any datasources on your template page.', array('$etf-entry-id')));
				
				$fieldset->appendChild($help);
				
				$group = new XMLElement('div');
				$group->setAttribute('class', 'group');
				
				// HTML Page:
				$label = Widget::Label(__('Template Page'));
				$options = $this->getPageOptions(
					$email->data()->page_id,
					array(
						array(null, false, __('Choose one...'))
					)
				);
				
				$select = Widget::Select(
					"{$prefix}[page_id]", $options
				);
				$select->setAttribute('class', 'page-picker');
				$label->appendChild($select);
				
				if (isset($email->errors()->page_id)) {
					$label = Widget::wrapFormElementWithError($label, $email->errors()->page_id);
				}
				
				$group->appendChild($label);
				$fieldset->appendChild($group);
				
				$group = new XMLElement('div');
				$group->setAttribute('class', 'group');
				
				// Attachments:
				$label = Widget::Label(__('Attachments'));
				$options = $this->getAttachmentOptions(
					$email->data()->send_attachments,
					array(
						array(null, false, __('Don\'t send attachments'))
					)
				);
				
				$select = Widget::Select(
					"{$prefix}[send_attachments]", $options
				);
				$select->setAttribute('class', 'page-picker');
				$label->appendChild($select);
				
				if (isset($email->errors()->send_attachments)) {
					$label = Widget::wrapFormElementWithError($label, $email->errors()->send_attachments);
				}
				
				$group->appendChild($label);
				
				// Plain Text:
				$label = Widget::Label(__('Plain Text'));
				$options = $this->getPlainTextOptions(
					$email->data()->send_plain_text,
					array(
						array(null, false, __('Don\'t send plain text'))
					)
				);
				
				$select = Widget::Select(
					"{$prefix}[send_plain_text]", $options
				);
				$select->setAttribute('class', 'page-picker');
				$label->appendChild($select);
				
				if (isset($email->errors()->send_plain_text)) {
					$label = Widget::wrapFormElementWithError($label, $email->errors()->send_plain_text);
				}
				
				$group->appendChild($label);
				$fieldset->appendChild($group);
			}
			
			$wrapper->appendChild($fieldset);
		}
	}
	
?>