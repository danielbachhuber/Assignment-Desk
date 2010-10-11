jQuery(document).ready(function() {
	
	jQuery("#assignment_desk_duedate").datepicker();
	
	jQuery('a.assignment_desk_voting_submit').click(function() {
		var link = jQuery(this);
		jQuery.ajax({
			url: link.attr('href'),
			success: function(data) {
				if ( data.indexOf('added') != -1 ) {
					link.find('span.assignment_desk_voting_text').html('Thanks!');
				} else {
					link.find('span.assignment_desk_voting_text').html('Vote');
				}
			}
		})
		return false;
	});
	
});