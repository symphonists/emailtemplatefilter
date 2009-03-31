Filter: Email Template
-------------------------------------------------------------------------------

Version: 1.0.12
Author: Rowan Lewis <rowan@pixelcarnage.com>
Build Date: 31 March 2009
Requirements: Symphony 2.0.1


Installation
-------------------------------------------------------------------------------

1.	Edit `symphony/lib/toolkit/class.frontendpage.php` and replace the function
	on 416 with this:
	
		public function __processDatasources($datasources, &$wrapper, $params = array()) {
			if (trim($datasources) == '') return;
			
			$datasources = preg_split('/,\s*/i', $datasources, -1, PREG_SPLIT_NO_EMPTY);
			$datasources = array_map('trim', $datasources);
			
			if (!is_array($datasources) || empty($datasources)) return;
			
			$this->_env['pool'] = $params;
			$pool = $params;
			$dependencies = array();
			
			foreach ($datasources as $handle) {
				$this->_Parent->Profiler->seed();
				
				$pool[$handle] =& $this->DatasourceManager->create($handle, null, false);
				
				$dependencies[$handle] = $pool[$handle]->getDependencies();
				
				unset($ds);
			}
			
			$dsOrder = $this->__findDatasourceOrder($dependencies);
			
			foreach ($dsOrder as $handle) {
				$this->_Parent->Profiler->seed();
				
				$ds = $pool[$handle];
				$ds->processParameters(array('env' => $this->_env, 'param' => $this->_param));
				
				if ($xml = $ds->grab($this->_env['pool'])) {
					if (is_object($xml)) {
						$wrapper->appendChild($xml);
						
					} else {
						$wrapper->setValue($wrapper->getValue() . self::CRLF . "\t" . trim($xml));
					}
				}
				
				$this->_Parent->Profiler->sample($handle, PROFILE_LAP, 'Datasource');
				
				unset($ds);
			}
		}
	
2.	Upload the 'emailtemplatefilter' folder in this archive to your Symphony
	'extensions' folder.
	
3.	Enable it by selecting the "Filter: Email Template", choose Enable from the
	with-selected menu, then click Apply.
	
4.	Add your email templates, and attach them to your events.