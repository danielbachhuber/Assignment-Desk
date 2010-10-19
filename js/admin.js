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
	
	jQuery('a.assignment_desk_response').click(function() {
		var link = jQuery(this);
		if ( link.hasClass('assignment_desk_accept') ) {
			var action = 'assignment_desk_accept';
		} else if ( link.hasClass('assignment_desk_decline') ) {
			var action = 'assignment_desk_decline';
		}
		var role_id = link.parent().sibling('.assignment_desk_role_id').val();
		var post_id = link.closest('input').val();
		alert(role_id);
		jQuery.ajax({
			type: 'POST',
			data: {
				post_id: post_id,
				role_id: role_id,
				action: action,
			},
			success: function(data) {
				//alert(data);
			},
		});
		return false;

	});
	
});