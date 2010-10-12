jQuery(document).ready(function() {
	
	jQuery("#assignment_desk_duedate").datepicker();
	
	jQuery('a.assignment_desk_voting_submit').click(function() {
		var link = jQuery(this);
		jQuery.ajax({
			url: link.attr('href'),
			success: function(data) {
				if ( data.indexOf('added') != -1 ) {
					var new_href = link.attr('href').replace('assignment_desk_add_vote', 'assignment_desk_delete_vote');
					link.attr('href', new_href);
					link.find('span.assignment_desk_voting_text').html('Thanks!');
					var votes = parseFloat(link.find('span.assignment_desk_voting_votes').html());
					votes = votes + 1;
					link.find('span.assignment_desk_voting_votes').html(votes);
				} else if (data.indexOf('deleted') != -1) {
					var new_href = link.attr('href').replace('assignment_desk_delete_vote', 'assignment_desk_add_vote');
					link.attr('href', new_href);
					var custom_text = link.find('.assignment_desk_voting_text_custom').html()
					if ( custom_text ) {
						var button_text = custom_text;
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