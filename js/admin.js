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
	
	/**
	 * Handle a user's response to their story assignments
	 * @todo Clean up the animations. They should match WordPress UI exactly
	 * @todo Add the role to an existing assignment listing if the user has already accepted one role
	 */
	jQuery('a.assignment_desk_response').click(function() {
		var link = jQuery(this);
		if ( link.hasClass('assignment_desk_accept') ) {
			var action = 'assignment_desk_accept';
		} else if ( link.hasClass('assignment_desk_decline') ) {
			var action = 'assignment_desk_decline';
		}
		var role_id = jQuery(this).siblings('input.assignment_desk_role_id').val();
		var post_id = jQuery(this).siblings('input.assignment_desk_post_id').val();
		var post_div = jQuery('div#pending-assignment-'+post_id+'-role-'+role_id)
		jQuery.ajax({
			type: 'POST',
			data: {
				post_id: post_id,
				role_id: role_id,
				action: action,
			},
			success: function(data) {
				if ( data == 'accepted' ) {
					post_div.find('span.accept-decline-actions').remove();
					post_div.animate( { 'backgroundColor':'#CCEEBB' }, 350 ).animate( { 'backgroundColor': '#FFFFFF' }, 350 );
					post_div.removeClass('pending').addClass('accepted');					
					post_div.find('h4 span').removeClass('pending').addClass('accepted');
				} else if ( data == 'declined' ) {
					var title = post_div.find('h4 a').html();
					var message = 'The <strong>'+title+'</strong> assignment has been declined.';
					/* post_div.animate( { 'backgroundColor':'#ceb' }, 350 ).animate( { 'backgroundColor': '#F4F4F4' }, 350 ); */
					post_div.empty().removeClass('pending').addClass('declined');
					post_div.html(message);
				}
			},
		});
		return false;

	});
	
	/**
	 * Show and hide editorial metadata details on upcoming assignments dashboard widget
	 */
	jQuery('a.assignment_desk_view_details').click(function() {
		var post_div = jQuery(this).closest('.assignment-desk-item');
		if ( jQuery(this).html() == 'View Details' ) {
			jQuery(this).html('Hide Details');			
			post_div.find('.post-details').slideDown( 250 );
		} else {
			jQuery(this).html('View Details');			
			post_div.find('.post-details').slideUp( 250 );
		}
		return false;
	})
	
});