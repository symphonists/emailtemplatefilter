// For other good browsers.
jQuery(document).ready(function() {
	jQuery('iframe').load(function() {
		this.style.height = jQuery(this.contentWindow.document.body).outerHeight(true) + 'px';
	});
});