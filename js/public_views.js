jQuery(document).ready(function() {
	
	jQuery("#assignment_desk_duedate").datepicker();
	
	/**
	 * Handle all AJAX voting requests
	 */
	jQuery('a.assignment_desk_voting_submit').click(function() {
		var link = jQuery(this);
		// We've saved all relevant data as hidden input forms
		var user_id = link.siblings('input.assignment_desk_user_id').val();
		var post_id = link.siblings('input.assignment_desk_post_id').val();
		var nonce = link.siblings('input.assignment_desk_voting_nonce').val();
		var action = link.siblings('input.assignment_desk_action').val();
		var assignment_desk_voting_text_custom = link.siblings('input.assignment_desk_voting_text_custom').val();
		// By default, this is a GET request to the originating URL. Suitable for our purposes
		jQuery.ajax({
			data: {
				user_id: user_id,
				post_id: post_id,
				nonce: nonce,
				action: action,
			},
			success: function(data) {
				// Change the text of the button, the action in the action input, and number of votes depending on response
				if ( data.indexOf('added') != -1 ) {
					link.siblings('input.assignment_desk_action').val('assignment_desk_delete_vote');
					link.find('span.assignment_desk_voting_text').html('Thanks!');
					var votes = parseFloat(link.find('span.assignment_desk_voting_votes').html());
					votes = votes + 1;
					link.find('span.assignment_desk_voting_votes').html(votes);
				} else if (data.indexOf('deleted') != -1) {
					link.siblings('input.assignment_desk_action').val('assignment_desk_add_vote');
					if ( assignment_desk_voting_text_custom ) {
						var button_text = assignment_desk_voting_text_custom;
					} else {
						var button_text = 'Vote';
					}
					link.find('span.assignment_desk_voting_text').html(button_text); // @todo Use custom voting text
					var votes = parseFloat(link.find('span.assignment_desk_voting_votes').html());
					votes = votes - 1;
					link.find('span.assignment_desk_voting_votes').html(votes);
				} else if ( data.indexOf('auth_error') != -1 ) {
					var message = '<div class="message alert">You must be logged in to vote</div>';
					jQuery('div.message.alert').remove();
					link.parent('.assignment-desk-action-links').append(message);
				}
			}
		})
		return false;
	});
	
});