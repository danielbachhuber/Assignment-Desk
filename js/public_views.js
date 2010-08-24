jQuery(document).ready(function() {

	// Volunteer links show the form
	jQuery("a[id^=assignment_desk_volunteer_]").each(function(index, link){
		var post_id = link.id.split('_')[3];
		jQuery(link).click(function(){
			jQuery('div#assignment_desk_volunteer_form_' + post_id).toggle();
		});
	});
	
	// Cancel links hide the form.
	jQuery("a[id^=assignment_desk_volunteer_cancel]").each(function(index, link){
		var post_id = link.id.split('_')[4];
		jQuery(link).click(function(){
			jQuery('div#assignment_desk_volunteer_form_' + post_id).toggle();
		});
	});
	
	jQuery("#assignment_desk_duedate").datepicker();
});