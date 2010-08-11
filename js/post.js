/**
* Add a trim() method to all string objects
*/
if(typeof(String.prototype.trim) === "undefined") {
    String.prototype.trim = function() {
        return String(this).replace(/^\s+|\s+$/g, '');
    };
}

/**
* Take the user input and the selected role and add that user to the list 
* of users assigned to a post with that role.
* Take care to show the div that surrounds the role list of the div was initially hidden.
*/
function ad_add_to_participants(user_id, user_nicename, role_id, role_name){
	var error_message = false;
	
	// @todo This check doesn't work all that well    
	if (user_id.length == 0){
	    error_message = '<div id="ad-participant-error-message" class="message alert">'
						+ 'No user selected'
						+ '</div>';
	}
	
	var user_role_status = 'pending';
	jQuery("#ad-participant-error-message").remove();
	
	// @todo check to see whether use was already assigned in this rold
	jQuery('input[name="ad-participant-role-' + role_id
	+ '[]"]').each(function(){
		if (jQuery(this).val().split('|')[0] == user_id ) {
			error_message = '<div id="ad-participant-error-message" class="message alert">'
							+ user_nicename + ' has already been added as ' + role_name
							+ '</div>';
		}
	})
	
	// Add it to the existing role wrap if that already exists
	// Else, create a new one
	if (jQuery("#ad-user-role-" + role_id + "-wrap").length > 0 && !error_message) {
		// create a new list item that hold a hidden form element.
		var field_html = '<li><input type="hidden" id="ad-participant-'
						+ user_id +'" name="ad-participant-role-'+role_id
						+ '[]" value="'+ user_id + '|' + user_role_status + '"/>'
						+ user_nicename + ' | ' + user_role_status + '</li>';
		// Append it to the list
		jQuery("ul#ad-participants-" + role_id).append(field_html);
	} else if (!error_message) {
		jQuery('#ad-no-participants').remove();
		var field_html = '<div id="ad-user-role-' + role_id + '-wrap" class="ad-role-wrap">'
						+ '<h5>' + role_name + '</h5>'
						+ '<ul id="ad-participants-' + role_id + '">';
		field_html += '<li><input type="hidden" id="ad-participant-'
						+ user_id +'" name="ad-participant-role-'+role_id
						+ '[]" value="'+ user_id + '|' + user_role_status + '"/>'
						+ user_nicename + ' | ' + user_role_status + '</li>';
		field_html += '</ul></div>';				
		jQuery("#ad-participants-wrap").append(field_html);			
	} else {
		jQuery("#ad-assign-form").prepend(error_message);
	}
	return false;

}

/**
* When a user clicks the "Assign" button for a contributor, show them a form to select the role.
*/
function show_participant_assign_form(user_login){
    user_login.trim()
    var participant_assign_form = "<label>User: </label>" + user_login;
    participant_assign_form += jQuery('div#ad-hidden-user-role-select').html();
    participant_assign_form += '<a class="button" onclick="javascript: return add_participant_to_assignees(\'' + user_login + '\');">Assign</a>';
    jQuery('#ad_participant_' + user_login).html(participant_assign_form);
    return false;  
}

jQuery(document).ready(function() {
	
	var ad_current_assignment_status = '';
	var ad_current_participant_types = [];
	var ad_current_pitched_by_participant = ''

	/**
	 * Initialize Co-Author Plus auto-suggest if it exists
	 */
	if (coauthor_ajax_suggest_link) {
		jQuery('#ad-assignee-search').suggest(coauthor_ajax_suggest_link, {
	    	onSelect: function() {
	        	var vals = this.value.split('|');
	        	var author = {};
	        	author.id = jQuery.trim(vals[0]);
	        	author.login = jQuery.trim(vals[1]);
	        	author.name = jQuery.trim(vals[2]);
	        	jQuery('#ad-assignee-search').val(author.name);
	    	}
		}).keydown(function(e) {
	    	if (e.keyCode == 13) {
	        	return false;
	    	}
		});
	}
    
	/**
     * Toggle post_meta_box subheads
	 */
	jQuery('h4.toggle').click(function() {
		var inner = jQuery(this).parent().find('div.inner').slideToggle();
	});
		
	/**
	 * Add selected user and selected role to their related participant bucket
	 */
	jQuery("a#ad-assign-button").click(function(){
		if ( jQuery('#ad-assignee-search').length > 0 ) {
			var user_info = jQuery('#ad-assignee-search').val();
			user_info = user_info.split('|');
			var user_id = user_info[0].trim();
			// only the user_id is left in the box currently.
			var user_nicename = user_info[0].trim();
			jQuery('#ad-assignee-search').val('');			
		} else {
			var user_id = jQuery('#ad-assignee-dropdown option:selected').val();
			var user_nicename = jQuery('#ad-assignee-dropdown option:selected').text();
		}
		var role_id = jQuery('#ad-user-role-dropdown option:selected').val();
		var role_name = jQuery('#ad-user-role-dropdown option:selected').text();		
		ad_add_to_participants(user_id, user_nicename, role_id, role_name);
		return false;
	});
	
	
	/**
	 * Manipulate the DOM when the user wants to "Edit" assignment status
	 * In short, save the current status and show the selection tool
	 */
	jQuery('#ad-edit-assignment-status').click(function(){
		jQuery(this).hide();
		ad_current_assignment_status = jQuery('select#ad-assignment-status').val();
		jQuery('#ad-assignment-status-select').slideToggle();
		return false;		
	});
	
	/**
	 * Manipulate the DOM when the user wants to "Save" their assignment status selection
	 * In short, ...
	 */
	jQuery('#ad-save-assignment-status').click(function(){
		jQuery('#ad-edit-assignment-status').show();
		var text = jQuery('select#ad-assignment-status option:selected').text();
		jQuery('#ad-assignment-status-display').html(text);
		jQuery('#ad-assignment-status-select').slideToggle();
		return false;		
	});
	
	/**
	 * Manipulate the DOM when the user wants to "Cancel" their assignment status selection
	 * In short, ...
	 */
	jQuery('#ad-cancel-assignment-status').click(function(){
		jQuery('#ad-edit-assignment-status').show();
		jQuery('select#ad-assignment-status').val(ad_current_assignment_status);
		jQuery('#ad-assignment-status-select').slideToggle();
		return false;		
	});
	
	/**
	 * Manipulate the DOM when the user wants to "Edit" participant types
	 * In short, save current checkbox states and then show list of types
	 */
	jQuery('#ad-edit-participant-types').click(function(){
		jQuery(this).hide();
		jQuery("input[name='ad-participant-types[]']").each(function(){
			if (jQuery(this).is(':checked')) {
				ad_current_participant_types[jQuery(this).val()] = 'on';
			} else {
				ad_current_participant_types[jQuery(this).val()] = 'off';
			}
		});
		jQuery('#ad-participant-types-select').slideToggle();
		return false;		
	});
	
	/**
	 * Manipulate the DOM when the user hits "Save" on participant types
	 * In short, build new field label and then hide list of types
	 */
	jQuery('#save-ad-participant-types').click(function(){
		jQuery('#ad-participant-types-select').slideToggle();
		var ad_all_participant_types = [];
		var ad_display_participant_types = '';
		jQuery("input[name='ad-participant-types[]']").each(function(){
			if ( jQuery(this).is(':checked') ) {
				ad_display_participant_types += jQuery(this).parent().find('label').html() + ', ';
				ad_all_participant_types[jQuery(this).val()] = 'on';
			} else {
				ad_all_participant_types[jQuery(this).val()] = 'off';
			}
		});
		// Hacky way to check if values are in array
		var joined = '|' + ad_all_participant_types.join('|') + '|';
		if (joined.indexOf('on') != -1 && joined.indexOf('off') == -1) {
			ad_display_participant_types = 'All';
		} else if (joined.indexOf('on') == -1 && joined.indexOf('off') != -1) {
			ad_display_participant_types = 'None';
		} else {
			ad_display_participant_types = ad_display_participant_types.slice(0, ad_display_participant_types.length - 2);
		}
		// Update the label for the field because we have new values
		jQuery('#ad-participant-types-display').html(ad_display_participant_types);
		jQuery('#ad-edit-participant-types').show();
		return false;		
	});
	
	/**
	 * Manipulate the DOM when the user hits "Cancel" on participant types
	 * In short, restore checkbox values and hide the list of options
	 */
	jQuery('#cancel-ad-participant-types').click(function(){
		jQuery('#ad-participant-types-select').slideToggle();
		// Restore checkbox values to what they were previously
		jQuery("input[name='ad-participant-types[]']").each(function(){
			if (ad_current_participant_types[jQuery(this).val()] == 'on') {
				jQuery(this).attr('checked', 'checked');
			} else {
				jQuery(this).removeAttr('checked');
			}
		});		
		jQuery('#ad-edit-participant-types').show();
		return false;
	});
	
	/* ============================ Pitched By ============================ */
	
	/**
	 * Manipulate the DOM when the user wants to "Edit" pitched by
	 * In short, save the current status and show the selection tool.
	 */
	jQuery('#ad-edit-pitched-by-participant').click(function(){
		jQuery(this).hide();
		ad_current_pitched_by_participant = jQuery('select#ad-pitched-by-participant').val();
		jQuery('#ad-pitched-by-participant-select').slideToggle();
		return false;		
	});
	
	/**
	 * Manipulate the DOM when the user wants to "Save" the pitched_by participant.
	 */
	jQuery('#ad-save-pitched-by-participant').click(function(){
		jQuery('#ad-edit-pitched-by-participant').show();
		var text = jQuery('select#ad-pitched-by-participant option:selected').text();
		var user_id = jQuery('select#ad-pitched-by-participant option:selected').val();
		var link = '<a href="' + wp_admin_url + 'user-edit.php?user_id=' + user_id + '">' + text + "</a>";
		jQuery('#ad-pitched-by-participant-display').html(link);
		jQuery('#ad-pitched-by-participant-select').slideToggle();
		return false;		
	});
	
	/**
	 * Manipulate the DOM when the user hits "Cancel" on pitched by
	 * In short, restore checkbox value and hide the pitched_by_form
	 */
	jQuery('#ad-cancel-pitched-by-participant').click(function(){
		jQuery('#ad-pitched-by-participant-select').slideToggle();
		jQuery('select#ad-pitched-by-participant').val(ad_current_pitched_by_participant);
		jQuery('#ad-edit-pitched-by-participant').show();
		return false;
	});
		
});