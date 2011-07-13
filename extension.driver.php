<?php

	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.entrymanager.php');

	class Extension_EmailTemplateFilter extends Extension {
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public static $params = array();
		public static $page = null;

		public function about() {
			return array(
				'name'			=> 'Filter: Email Template',
				'version'		=> '1.1.0',
				'release-date'	=> '2011-03-01',
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://rowanlewis.com/',
					'email'			=> 'me@rowanlewis.com'
				)
			);
		}

		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_etf_templates`");
			Symphony::Database()->query("DROP TABLE `tbl_etf_conditions`");
			Symphony::Database()->query("DROP TABLE `tbl_etf_logs`");
		}

		public function install() {
			Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_etf_templates` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`name` varchar(255) NOT NULL,
					`conditions` int(11) unsigned default NULL,
					`datasources` text default NULL,
					PRIMARY KEY (`id`)
				)
			");

			Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_etf_conditions` (
					`id` int(11) NOT NULL auto_increment,
					`template_id` int(11) NOT NULL,
					`expression` varchar(255),
					`sortorder` int(11) NOT NULL,
					`type` varchar(255) NOT NULL,
					`subject` varchar(255) NOT NULL,
					`sender` varchar(255) NOT NULL,
					`senders` varchar(255) NOT NULL,
					`recipients` varchar(255) NOT NULL,
					`page` int(11) NOT NULL,
					`params` varchar(255),
					PRIMARY KEY (`id`)
				)
			");

			Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_etf_logs` (
					`id` int(11) NOT NULL auto_increment,
					`template_id` int(11) NOT NULL,
					`condition_id` int(11) NOT NULL,
					`entry_id` int(11) NOT NULL,
					`success` enum('yes','no') NOT NULL,
					`date` datetime NOT NULL,
					`subject` varchar(255),
					`sender` varchar(255),
					`senders` varchar(255),
					`recipients` varchar(255),
					`message` text,
					PRIMARY KEY (`id`)
				)
			");

			return true;
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
					'location'	=> 250,
					'name'		=> 'Emails',
					'children'	=> array(
						array(
							'name'		=> 'Templates',
							'link'		=> '/templates/'
						),
						array(
							'name'		=> 'Logs',
							'link'		=> '/logs/'
						)
					)
				)
			);
		}

		public function setPage($context) {
			self::$page = $context['page'];
		}

		public function setParams($context) {
			self::$params = $context['params'];
		}

	/*-------------------------------------------------------------------------
		Utility functions:
	-------------------------------------------------------------------------*/

		public function getConditions($template_id = null) {
			if (is_numeric($template_id)) {
				$results = Symphony::Database()->fetch("
					SELECT
						c.*
					FROM
						`tbl_etf_conditions` AS c
					WHERE
						c.template_id = {$template_id}
					ORDER BY
						c.sortorder ASC
				");
			}

			else {
				$results = Symphony::Database()->fetch("
					SELECT
						c.*
					FROM
						`tbl_etf_conditions` AS c
					ORDER BY
						c.sortorder ASC
				");
			}

			return $results;
		}

		public function countLogs() {
			return (integer)Symphony::Database()->fetchVar('total', 0, "
				SELECT
					COUNT(l.id) AS `total`
				FROM
					`tbl_etf_logs` AS l
			");
		}

		public function getLogs($page) {
			$start = ($page - 1) * 17;

			return Symphony::Database()->fetch("
				SELECT
					l.*
				FROM
					`tbl_etf_logs` AS l
				ORDER BY
					l.date DESC
				LIMIT {$start}, 17
			");
		}

		public function getLog($log_id) {
			return Symphony::Database()->fetchRow(0, "
				SELECT
					l.*
				FROM
					`tbl_etf_logs` AS l
				WHERE
					l.id = '{$log_id}'
				LIMIT 1
			");
		}

		public function getPages() {
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

			return $result;
		}

		public function getPage($page_id) {
			return (object)Symphony::Database()->fetchRow(0, "
				SELECT
					p.*
				FROM
					`tbl_pages` AS p
				WHERE
					p.id = '{$page_id}'
				LIMIT 1
			");
		}

		public function getTemplates() {
			return Symphony::Database()->fetch("
				SELECT
					t.*
				FROM
					`tbl_etf_templates` AS t
				ORDER BY
					t.name ASC
			");
		}

		public function getTemplate($template_id) {
			return Symphony::Database()->fetchRow(0, "
				SELECT
					t.id, t.name, t.datasources
				FROM
					`tbl_etf_templates` AS t
				WHERE
					t.id = '{$template_id}'
				LIMIT 1
			");
		}

	/*-------------------------------------------------------------------------
		Filter functions:
	-------------------------------------------------------------------------*/

		public function addFilterToEventEditor(&$context) {
			$templates = $this->getTemplates();

			foreach ($templates as $template) {
				$id = "etf-{$template['id']}";
				$selected = is_array($context['selected']) && in_array($id, $context['selected']);

				$context['options'][] = array(
					$id, $selected,
					General::sanitize("Send Email: {$template['name']}")
				);
			}
		}

		public function eventFinalSaveFilter(&$context) {
			$event = $context['event'];
			$templates = $this->getTemplates();

			foreach ($templates as $template) {
				if (in_array("etf-{$template['id']}", $event->eParamFILTERS)) {
					$this->sendEmail($context['entry']->get('id'), $template['id']);
				}
			}
		}

		protected function getData($template, $entry_id) {
			$data = new XMLElement('data');

			if (!empty(self::$params)) {
				$params = new XMLElement('param');

				foreach (self::$params as $key => $value) {
					if (is_integer($key)) $key = 'item';

					$key = General::sanitize($key);

					if (is_array($value)) {
						$child = new XMLElement($key);
						$this->getDataParam($value, $child);
					}

					else {
						if (is_bool($value)) {
							$value = ($value ? 'yes' : 'no');
						}

						$child = new XMLElement($key, General::sanitize((string)$value));
					}

					$params->appendChild($child);
				}

				$data->appendChild($params);
			}

			self::$page->processDatasources($template['datasources'], $data, array(
				'etf-entry-id'	=> $entry_id
			));

			$dom = new DOMDocument();
			$dom->loadXML($data->generate(true));

			return $dom;
		}

		public function sendEmail($entry_id, $template_id) {
			$template = $this->getTemplate($template_id);
			$conditions = $this->getConditions($template_id);
			$data = $this->getData($template, $entry_id);
			$xpath = new DOMXPath($data);
			$email = null;

			// Find condition:
			foreach ($conditions as $condition) {
				if (empty($condition['expression'])) {
					$email = $condition; break;
				}

				$results = $xpath->query($condition['expression']);

				if ($results->length > 0) {
					$email = $condition; break;
				}
			}

			if (is_null($email)) return;

			// Replace {xpath} queries:
			foreach ($email as $key => $value) {
				$content = $email[$key];
				$replacements = array();

				// Find queries:
				preg_match_all('/\{[^\}]+\}/', $content, $matches);

				// Find replacements:
				foreach ($matches[0] as $match) {
					$results = @$xpath->query(trim($match, '{}'));

					if ($results->length) {
						$items = array();

						foreach ($results as $item) {
							if (!isset($item->nodeValue)) continue;

							$items[] = $item->nodeValue;
						}

						$replacements[$match] = implode(', ', $items);
					}

					else {
						$replacements[$match] = '';
					}
				}

				$content = str_replace(
					array_keys($replacements),
					array_values($replacements),
					$content
				);

				$email[$key] = $content;
			}

			// Find generator:
			$page = $this->getPage($email['page']);
			$generator = URL;

			if ($page->path) $generator .= '/' . $page->path;

			$generator .= '/' . $page->handle;
			$generator = rtrim($generator, '/');
			$params = trim($email['params'], '/');
			$generator = "{$generator}/{$params}/";

			// Add values:
			$email['message'] = (string)file_get_contents($generator);
			$email['condition_id'] = $email['id'];
			$email['entry_id'] = $entry_id;
			$email['recipients'] = array_unique(preg_split(
				'/\s*[,]\s*/', $email['recipients']
			));

			// Remove junk:
			unset($email['id']);
			unset($email['expression']);
			unset($email['type']);
			unset($email['sortorder']);
			unset($email['page']);
			unset($email['params']);

			$send = Email::create();
			$success = false;

			try {
				$send->recipients = $email['recipients'];
				$send->sender_name = $email['sender'];
				$send->sender_email_address = $email['senders'];
				$send->subject = $email['subject'];
				$send->text_html = $email['message'];
				$send->attachments = $this->findAttachments($email);
				$send->send();
				$success = true;
			}

			catch (EmailGatewayException $e) {
			    throw new SymphonyErrorPage('Error sending email. ' . $e->getMessage());
			}

			catch (EmailException $e) {
			    throw new SymphonyErrorPage('Error sending email. ' . $e->getMessage());
			}

			// Log the email:
			$email['success'] = ($success ? 'yes' : 'no');
			$email['date'] = DateTimeObj::get('c');
			$email['recipients'] = implode(', ', $email['recipients']);

			Symphony::Database()->insert($email, 'tbl_etf_logs');

			return $return;
		}

		public function findAttachments($email) {
			$document = new DOMDocument();
			$document->loadHTML($email['message']);
			$xpath = new DOMXPath($document);
			$attachments = array();

			foreach ($xpath->query('//link[@rel = "attachment"]') as $node) {
				$path = $node->getAttribute('href');

				// Relative to root:
				if (strpos($path, './') === 0) {
					$attachments[] = realpath(DOCROOT . '/' . $path);
				}

				// Absolute path:
				else if (strpos($path, '/') === 0) {
					$attachments[] = $path;
				}

				// URLs:
				else {
					$attachments[] = str_replace(URL, DOCROOT, $path);
				}
			}

			return $attachments;
		}
	}

?>