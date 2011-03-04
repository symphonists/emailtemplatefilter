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
			$options = array();
			
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
			if (isset($_POST['preview-selected'])) {
				$this->entry = $_POST['preview-selected'];
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
				$url = $email->getPreviewURL($this->entry);
				
				$iframe = new XMLElement('iframe');
				$iframe->setAttribute('src', $url);
				$this->Form->appendChild($iframe);
			}
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'xsettings');
			$fieldset->appendChild(new XMLElement('legend', __('Email Preview')));
			
			if (isset($url)) {
				$help = new XMLElement('p');
				$help->setAttribute('class', 'help');
				$help->setValue(__('Previewing: <a href="%1$s">%1$s</a>', array($url)));
				//$fieldset->appendChild($help);
			}
			
			// Mark current option as selected:
			$options = $this->options;
			
			foreach ($options as $index_1 => $section) {
				if (!isset($section['options'])) continue;
				
				foreach ($section['options'] as $index_2 => $option) {
					if ($option[0] != $this->entry) continue;
					
					$options[$index_1]['options'][$index_2][1] = true; break;
				}
			}
			
			$label = new XMLElement('p');
			$label->setAttribute('class', 'label');
			
			$span = new XMLElement('span');
			$span->appendChild(Widget::Select('preview-selected', $options));
			$span->appendChild(Widget::Input('action[apply]', __('Preview'), 'submit'));
			$label->appendChild($span);
			$fieldset->appendChild($label);
			
			$help = new XMLElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue(__('Choose one of the available entries above to send to the template page for a preview.'));
			
			$fieldset->appendChild($help);
			$this->Form->appendChild($fieldset);
		}
	}
	
?>