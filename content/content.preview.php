<?php
	
	require_once(EXTENSIONS . '/emailbuilder/libs/class.page.php');
	
	class ContentExtensionEmailBuilderPreview extends EmailBuilderPage {
		protected $email;
		protected $entry;
		protected $options;
		
		public function getOptions() {
			$entryManager = new EntryManager(Symphony::Engine());
			$sectionManager = new SectionManager(Symphony::Engine());
			$sections = $sectionManager->fetch(null, 'ASC', 'sortorder');
			$options = array(
				array(null, false, __('Choose an entry to preview...'))
			);
			
			foreach ($sections as $section) {
				$entries = $entryManager->fetch(null, $section->get('id'), 5, 0);
				$group = array(
					'label'		=> $section->get('name'),
					'options'	=> array()
				);
				
				foreach ($entries as $entry) {
					$fields = $entry->getData();
					$label = null;
					
					foreach ($fields as $field) {
						if (!isset($field['handle']) || !isset($field['value'])) continue;
						
						$label = $field['value']; break;
					}
					
					$group['options'][] = array(
						$entry->get('id'), false, $label
					);
				}
				
				$options[] = $group;
			}
			
			return $options;
		}
		
		public function build($context) {
			// Load existing email:
			if (isset($context[0]) && EmailBuilderEmail::exists($context[0])) {
				$this->email = EmailBuilderEmail::load($context[0]);
				$this->options = $this->getOptions();
			}
			
			// Create new email:
			else {
				redirect($this->root_url . '/emails/');
			}
			
			return parent::build($context);
		}
		
		public function action() {
			if (isset($_POST['entry'])) {
				$this->entry = $_POST['entry'];
			}
		}
		
		public function view() {
			$email = $this->email;
			
			$this->setPageType('form');
			$this->setTitle(__(
				'%1$s &ndash; %2$s &ndash; %3$s',
				array(
					__('Symphony'),
					__('Emails'),
					$email->data()->name
				)
			));
			$this->appendSubheading($email->data()->name, (
				Widget::Anchor(
					__('Edit Email'),
					sprintf(
						'%s/email/%d/',
						$this->root_url,
						$email->data()->id
					),
					__('Edit Email'),
					'button'
				)
			));
			$this->addStylesheetToHead(URL . '/extensions/emailbuilder/assets/preview.css');
			$this->addScriptToHead(URL . '/extensions/emailbuilder/assets/preview.js');
			
			if ($this->entry) {
				$result = $email->fetch($this->entry);
				
				if (isset($result->body()->html)) {
					$fieldset = new XMLElement('fieldset');
					$fieldset->appendChild(new XMLElement('legend', __('HTML Output')));
					$iframe = new XMLElement('iframe');
					$iframe->setAttribute('src', sprintf(
						'data:text/html;base64,%s',
						base64_encode($result->body()->html)
					));
					$fieldset->appendChild($iframe);
					$this->Form->appendChild($fieldset);
				}
				
				if (isset($result->body()->text)) {
					$fieldset = new XMLElement('fieldset');
					$fieldset->appendChild(new XMLElement('legend', __('Plain Text Output')));
					$iframe = new XMLElement('iframe');
					$iframe->setAttribute('src', sprintf(
						'data:text/plain;base64,%s',
						base64_encode($result->body()->text)
					));
					$fieldset->appendChild($iframe);
					$this->Form->appendChild($fieldset);
				}
			}
			
			if (isset($url)) {
				$help = new XMLElement('p');
				$help->setAttribute('class', 'help');
				$help->setValue(__('Previewing: <a href="%1$s">%1$s</a>', array($url)));
				//$fieldset->appendChild($help);
			}
			
			// Entry:
			$options = $this->options;
			
			foreach ($options as $index_1 => $section) {
				if (!isset($section['options'])) continue;
				
				foreach ($section['options'] as $index_2 => $option) {
					if ($option[0] != $this->entry) continue;
					
					$options[$index_1]['options'][$index_2][1] = true; break;
				}
			}
			
			$actions = new XMLElement('div');
			$actions->setAttribute('class', 'actions');
			$actions->appendChild(
				Widget::Select('entry', $options)
			);
			$actions->appendChild(
				Widget::Input('action[preview]',
					__('Update Preview'),
					'submit'
				)
			);
			
			$this->Form->appendChild($actions);
		}
	}
	
?>