<?php
	
	require_once(EXTENSIONS . '/emailbuilder/libs/class.page.php');
	
	class ContentExtensionEmailBuilderEmails extends EmailBuilderPage {
		public function action() {
			$items = (
				(isset($_POST['items']) && is_array($_POST['items']))
					? array_keys($_POST['items'])
					: null
			);
			
			// Delete selected emails:
			if ($_POST['with-selected'] == 'delete' && !empty($items)) {
				EmailBuilderEmail::deleteAll($items);
			}
		}
		
		public function view() {
			$iterator = new EmailBuilderEmailIterator();
			
			$this->setPageType('table');
			$this->setTitle(__('Symphony &ndash; Emails'));
			
			$this->appendSubheading('Emails', Widget::Anchor(
				__('Create New'), $this->root_url . '/email/',
				__('Create a new email'), 'create button'
			));
			
			$table = new XMLElement('table');
			$table->appendChild(
				Widget::TableHead(array(
					array(__('Name'), 'col'),
					array(__('Subject'), 'col'),
					array(__('Sender Name'), 'col'),
					array(__('Sender Address'), 'col'),
					array(__('Recipient Address'), 'col'),
					array(__('Logs'), 'col'),
					array(null, 'col')
				))
			);
			$table->setAttribute('class', 'selectable');
			
			if (!$iterator->valid()) {
				$table->appendChild(Widget::TableRow(array(
					Widget::TableData(
						__('None Found.'),
						'inactive',
						null, 6
					)
				)));
			}
			
			else foreach ($iterator as $email) {
				$row = new XMLElement('tr');
				
				$first_cell = Widget::TableData(
					Widget::Anchor(
						$email->data()->name,
						sprintf(
							'%s/email/%d/',
							$this->root_url,
							$email->data()->id
						)
					)
				);
				$first_cell->appendChild(Widget::Input(
					sprintf('items[%d]', $email->data()->id),
					null, 'checkbox'
				));
				$row->appendChild($first_cell);
				$row->appendChild(Widget::TableData($email->data()->subject));
				$row->appendChild(Widget::TableData($email->data()->sender_name));
				$row->appendChild(Widget::TableData($email->data()->sender_address));
				$row->appendChild(Widget::TableData($email->data()->recipient_address));
				$row->appendChild(Widget::TableData(
					Widget::Anchor(
						sprintf(
							'%d &rarr;',
							$email->countLogs()
						),
						sprintf(
							'%s/logs/%d/',
							$this->root_url,
							$email->data()->id
						)
					)
				));
				$row->appendChild(Widget::TableData(
					Widget::Anchor(
						'Preview &rarr;',
						sprintf(
							'%s/preview/%d/',
							$this->root_url,
							$email->data()->id
						)
					)
				));
				$table->appendChild($row);
			}
			
			$this->Form->appendChild($table);
			
			$actions = new XMLElement('div');
			$actions->setAttribute('class', 'actions');
			
			$options = array(
				array(null, false, __('With Selected...')),
				array('delete', false, 'Delete')									
			);
			
			$actions->appendChild(Widget::Select('with-selected', $options));
			$actions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));
			
			$this->Form->appendChild($actions);
		}
	}
	
?>