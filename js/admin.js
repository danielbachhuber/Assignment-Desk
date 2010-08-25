/**
 * Functions and methods used in the WordPress admin
 */

jQuery(document).ready(function() {
	
	/* ============================ General Settings ============================ */
	
	/**
	 * Show and hide a pitch form's label and description fields
	 */
	jQuery('ul.ad_elements span.field input').click(function() {
		if ( jQuery(this).is(':checked') ) {
			jQuery(this).closest('li').find('span.copy').removeClass('hidden');
		} else {
			jQuery(this).closest('li').find('span.copy').addClass('hidden');
		}
	});
	
});