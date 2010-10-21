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
		var role_id = jQuery(this).siblings('input.assignment_desk_role_id').val();
		var post_id = jQuery(this).siblings('input.assignment_desk_post_id').val();	
		jQuery.ajax({
			type: 'POST',
			data: {
				post_id: post_id,
				role_id: role_id,
				action: action,
			},
			success: function(data) {
				if ( data == 'accepted' ) {
					jQuery('div#pending-assignment-'+post_id).removeClass('pending').addClass('accepted');
					jQuery('div#pending-assignment-'+post_id).find('p.row-actions').remove()
					jQuery('div#pending-assignment-'+post_id).find('h4 span').removeClass('pending').addClass('accepted');
				} else if ( data == 'declined' ) {
					var title = jQuery('div#pending-assignment-'+post_id+' h4 a').html();
					var message = 'The <strong>'+title+'</strong> assignment has been declined.';
					jQuery('div#pending-assignment-'+post_id).empty().removeClass('pending').addClass('declined');
					jQuery('div#pending-assignment-'+post_id).html(message);
				}
			},
		});
		return false;

	});
	
});