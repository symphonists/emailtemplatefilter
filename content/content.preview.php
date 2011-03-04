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
				array(null, false, __('Preview with...'))
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
			if (isset($_POST['preview-selected'])) {
				$this->entry = $_POST['preview-selected'];
			}
		}
		
		public function view() {
			$email = $this->email;
			
			$this->setPageType('table');
			$this->setTitle(__(
				'%1$s &ndash; %2$s &ndash; %3$s',
				array(
					__('Symphony'),
					__('Emails'),
					$email->data()->name
				)
			));
			$this->appendSubheading($email->data()->name);
			$this->addStylesheetToHead(URL . '/extensions/emailbuilder/assets/preview.css');
			
			$actions = new XMLElement('div');
			$actions->setAttribute('class', 'actions');
			
			// Mark current option as selected:
			$options = $this->options;
			
			foreach ($options as $index_1 => $section) {
				if (!isset($section['options'])) continue;
				
				foreach ($section['options'] as $index_2 => $option) {
					if ($option[0] != $this->entry) continue;
					
					$options[$index_1]['options'][$index_2][1] = true; break;
				}
			}
			
			$actions->appendChild(Widget::Select('preview-selected', $options));
			$actions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));
			
			$this->Form->appendChild($actions);
		}
	}
	
?>