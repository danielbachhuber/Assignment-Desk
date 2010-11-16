jQuery(document).ready(function() {
	
	jQuery(".ad_datepicker").datepicker();
	
	/**
	 * Handle all AJAX voting requests
	 */
	jQuery('a.assignment_desk_voting_submit').click(function() {
		var link = jQuery(this);
		// We've saved all relevant data as hidden input forms
		var post_id = link.siblings('input.assignment_desk_post_id').val();
		var nonce = link.siblings('input.assignment_desk_voting_nonce').val();
		var action = link.siblings('input.assignment_desk_action').val();
		var assignment_desk_voting_text_custom = link.siblings('input.assignment_desk_voting_text_custom').val();
		// By default, this is a GET request to the originating URL. Suitable for our purposes
		jQuery.ajax({
			data: {
				post_id: post_id,
				nonce: nonce,
				action: action,
			},
			success: function(data) {
				// Change the text of the button, the action in the action input, and number of votes depending on response
				if ( data == 'added' ) {
					link.siblings('input.assignment_desk_action').val('assignment_desk_delete_vote');
					link.find('span.assignment_desk_voting_text').html('Thanks!');
					var votes = parseFloat(link.find('span.assignment_desk_voting_votes').html());
					votes = votes + 1;
					link.find('span.assignment_desk_voting_votes').html(votes);
				} else if ( data == 'deleted' ) {
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
				} else if ( data == 'auth_error' ) {
					var message = '<div class="message alert">You must be logged in to vote</div>';
					jQuery('div.message.alert').remove();
					link.closest('.assignment-desk-action-links').append(message);
				}
			}
		})
		return false;
	});
	
});