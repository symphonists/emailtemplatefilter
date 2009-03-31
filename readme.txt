Filter: Email Template
-------------------------------------------------------------------------------

Version: 1.0.11
Author: Rowan Lewis <rowan@pixelcarnage.com>
Build Date: 31 March 2009
Requirements: Symphony 2.0.1


Installation
-------------------------------------------------------------------------------

1.	Edit `symphony/lib/toolkit/class.frontendpage.php` and change line 416 to:
	
	private function __processDatasources($datasources, &$wrapper) {
	
2.	Upload the 'emailtemplatefilter' folder in this archive to your Symphony
	'extensions' folder.
	
3.	Enable it by selecting the "Filter: Email Template", choose Enable from the
	with-selected menu, then click Apply.
	
4.	Add your email templates, and attach them to your events.