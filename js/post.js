/**
* Add a trim() method to all string objects
*/
if ( typeof(String.prototype.trim) === "undefined" ) {
    String.prototype.trim = function() {
        return String(this).replace(/^\s+|\s+$/g, '');
    };
}

/**
 * Find all of the Remove participant buttons and bind a function to the click event.
 */
function ad_instrument_remove_participant_buttons(){
	/**
	 * This function is attached to the click event of the "Remove" buttons.
	 * This removes the participant record from the next submission.
	 */
	jQuery('button.ad-remove-participant-button').click(function(){
		// Hide the entire wrapper if we're removing the last participant
		if ( jQuery(this).parents('div.ad-role-wrap').find('p').length == 1 ) {
			jQuery(this).parents('div.ad-role-wrap').remove();
		}
		// Create a hidden form element so we can remove the participant duirng save_post
		var remove_input = '<input type="hidden" name="ad-participant-remove[]" value="' + jQuery(this).val() + '">';
		jQuery('div#ad-participants-wrap').append(remove_input);
		// Remove the participant display
		jQuery(this).parents('p').remove();
		return false;
	});
}

/**
* Take the user input and the selected role and add that user to the list 
* of users assigned to a post with that role.
* Take care to show the div that surrounds the role list of the div was initially hidden.
*/
function ad_add_to_participants(user_id, user_nicename, role_id, role_name){
	var error_message = false;
	
	// @todo This check doesn't work all that well
	user_id = parseInt(user_id);
	if ( !user_id ) {
	    error_message = '<div id="ad-participant-error-message" class="message alert">'
						+ assignment_desk_no_user_selected
						+ '</div>';
	}

	jQuery("#ad-participant-error-message").remove();
	
	var participant_record = jQuery('p#ad-participants-' + role_id + '-' + user_id);
	if ( !error_message && participant_record.length != 0 ) {
		if ( participant_record.html().indexOf('(volunteered)') == -1 ) {
			error_message = '<div id="ad-participant-error-message" class="message alert">'
							+ user_nicename + ' ' + assignment_desk_already_added + ' ' + role_name
							+ '</div>';
		}
	};
	
	if ( !error_message ) {
		jQuery('#ad-no-participants').remove();
		var role_wrap = jQuery("#ad-participant-role-" + role_id + "-wrap");
		// Create a wrap for the role if it doesn't exist.
		if ( role_wrap.length == 0 ) {
			jQuery("div#ad-participants-wrap").append(
				'<div id="ad-participant-role-' + role_id + '-wrap" class="ad-role-wrap"><h5>' + role_name + '</h5></div>'
			);	
			role_wrap = jQuery("#ad-participant-role-" + role_id + "-wrap");
		}
		// Append a new p that holds a hidden form element
		role_wrap.append(
			'<p id="ad-participants-' + role_id + '-' + user_id + '">'
			+ '<input type="hidden" id="ad-participant-'+ user_id + '" name="ad-participant-assign[]" value="'+ role_id + '|' + user_id + '"/>'
			+ user_nicename + ' (pending) <span class="ad-participant-buttons">'
			+ '<button class="button ad-remove-participant-button" name="ad-participant-remove[]" value="'
			+ role_id + '|' + user_id + '">Remove</button></span></p>'
			);	
	} else {
		jQuery("#ad-assign-form").prepend(error_message);
	}
	ad_instrument_remove_participant_buttons();
	return false;
}

jQuery(document).ready(function() {
	
	var ad_current_assignment_status = '';
	var ad_current_participant_types = [];
	var ad_current_participant_roles = [];	
	var ad_current_pitched_by_participant = '';

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
				jQuery('#ad-assignee-search-user_id').val(author.id);
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
		jQuery(this).parent().find('div.inner').slideToggle();
	});
	
	/**
	 * Fires when the user hits the assign button.
	 * Get the user search box, check if its a valid user, and add to the selected role.
 	 */
	jQuery("a#ad-assign-button").click(function(){
		
		var data_exists = false;
		
		if ( jQuery('#ad-assignee-search').length ) {
			
			if (jQuery('#ad-assignee-search').val().length > 0) {
				var user_id = jQuery('#ad-assignee-search-user_id').val();
				var user_nicename = jQuery('#ad-assignee-search').val();
				data_exists = true;
			} else {
				jQuery("#ad-participant-error-message").remove();
				jQuery("#ad-assign-form").prepend(
					'<div id="ad-participant-error-message" class="message alert">'
						+ assignment_desk_no_user_selected + '</div>' );
			}
			
		} else {
			var user_id = jQuery('select#ad-assignee-dropdown option:selected').val();
			var user_nicename = jQuery('select#ad-assignee-dropdown option:selected').html();
			data_exists = true;	
		}
		
		if ( data_exists ) {
			
            // Call AJAX function verify the username
            jQuery.ajax({
                url: ajaxurl, 
                data: { action: 'user_check', q: user_id },
                success: function(response){
                    // valid username returns user->ID > 0
                    if(parseInt(response) > 0){
                        // Clear the text box and hidden id
						if ( jQuery('#ad-assignee-search').length ) {
                        	jQuery('#ad-assignee-search').val('');
                        	jQuery('#ad-assignee-search-user_id').val(0);
						}
                        // Fetch the role information
                        role_id = jQuery('#ad-participant-role-dropdown option:selected').val();
                        role_name = jQuery('#ad-participant-role-dropdown option:selected').text();
                        ad_add_to_participants(user_id, user_nicename, role_id, role_name);
                    }
                    else {
                        // flag the invalid_user and display an error message
						if ( jQuery('#ad-assignee-search').length ) {
                        	jQuery('#ad-assignee-search-user_id').val(0);
						}
                        jQuery('#ad-participant-error-message').remove();
                        error_message = '<div id="ad-participant-error-message" class="message alert">' + 
                                            user_nicename + ' ' +  assignment_desk_invalid_user + '</div>';
                        jQuery("#ad-assign-form").prepend(error_message);
                    }
                    return false;
                }
            });
		}
		return false;
	});
	
	/**
	* Assign a volunteer to the story.
	*/
	jQuery("button.ad-assign-participant-button").each(function(index, button){
		var spl = jQuery(button).val().split('|');
		var role_id = spl[0];
		var role_name = spl[1];
		var user_id = spl[2];
		var user_nicename = spl[3];
	
		jQuery(button).click(function(){
			jQuery(button).parents('p').remove();
			ad_add_to_participants(user_id, user_nicename, role_id, role_name);
			return false;
		});
	});
	
	ad_instrument_remove_participant_buttons();
	
	/* ============================ Assignment Status ============================ */
	
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
	
	/* ============================ Participant Types ============================ */
	
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
	
	/* ============================ Participant Roles ============================ */
	
	/**
	 * Manipulate the DOM when the user wants to "Edit" participant roles
	 * In short, save current checkbox states and then show list of roles
	 */
	jQuery('#ad-edit-participant-roles').click(function(){
		jQuery(this).hide();
		jQuery("input[name='ad-participant-roles[]']").each(function(){
			if (jQuery(this).is(':checked')) {
				ad_current_participant_roles[jQuery(this).val()] = 'on';
			} else {
				ad_current_participant_roles[jQuery(this).val()] = 'off';
			}
		});
		jQuery('#ad-participant-roles-select').slideToggle();
		return false;		
	});
	
	/**
	 * Manipulate the DOM when the user hits "Save" on participant roles
	 * In short, build new field label and then hide list of roles
	 */
	jQuery('#save-ad-participant-roles').click(function(){
		jQuery('#ad-participant-roles-select').slideToggle();
		var ad_all_participant_roles = [];
		var ad_display_participant_roles = '';
		jQuery("input[name='ad-participant-roles[]']").each(function(){
			if ( jQuery(this).is(':checked') ) {
				ad_display_participant_roles += jQuery(this).parent().find('label').html() + ', ';
				ad_all_participant_roles[jQuery(this).val()] = 'on';
			} else {
				ad_all_participant_roles[jQuery(this).val()] = 'off';
			}
		});
		// Hacky way to check if values are in array
		var joined = '|' + ad_all_participant_roles.join('|') + '|';
		if (joined.indexOf('on') != -1 && joined.indexOf('off') == -1) {
			ad_display_participant_roles = 'All';
		} else if (joined.indexOf('on') == -1 && joined.indexOf('off') != -1) {
			ad_display_participant_roles = 'None';
		} else {
			ad_display_participant_roles = ad_display_participant_roles.slice(0, ad_display_participant_roles.length - 2);
		}
		// Update the label for the field because we have new values
		jQuery('#ad-participant-roles-display').html(ad_display_participant_roles);
		jQuery('#ad-edit-participant-roles').show();
		return false;		
	});
	
	/**
	 * Manipulate the DOM when the user hits "Cancel" on participant roles
	 * In short, restore checkbox values and hide the list of options
	 */
	jQuery('#cancel-ad-participant-roles').click(function(){
		jQuery('#ad-participant-roles-select').slideToggle();
		// Restore checkbox values to what they were previously
		jQuery("input[name='ad-participant-roles[]']").each(function(){
			if (ad_current_participant_roles[jQuery(this).val()] == 'on') {
				jQuery(this).attr('checked', 'checked');
			} else {
				jQuery(this).removeAttr('checked');
			}
		});		
		jQuery('#ad-edit-participant-roles').show();
		return false;
	});
	
	/* ============================ Pitched By ============================ */
	
	/**
	 * Manipulate the DOM when the user wants to "Edit" the pitched by field.
	 * In short, save the currently selected user and show the selection tool.
	 */
	jQuery('#ad-edit-pitched-by-participant').click(function(){
		jQuery(this).hide();
		ad_current_pitched_by_participant = jQuery('select#ad-pitched-by-participant').val();
		jQuery('#ad-pitched-by-participant-select').slideToggle();
		return false;		
	});
	
	/**
	 * Manipulate the DOM when the user wants to "Save" the pitched by field.
	 */
	jQuery('#ad-save-pitched-by-participant').click(function(){
		jQuery('#ad-edit-pitched-by-participant').show();
		var text = jQuery('select#ad-pitched-by-participant option:selected').text();
		var user_id = jQuery('select#ad-pitched-by-participant option:selected').val();
		var link = "None"
		if ( parseInt(user_id) ) {
			link = '<a href="' + wp_admin_url + 'user-edit.php?user_id=' + user_id + '">' + text + "</a>";
		}
		jQuery('#ad-pitched-by-participant-display').html(link);
		jQuery('#ad-pitched-by-participant-select').slideToggle();
		return false;		
	});
	
	/**
	 * Manipulate the DOM when the user hits "Cancel" on pitched by field.
	 * In short, restore field value and hide the pitched by form.
	 */
	jQuery('#ad-cancel-pitched-by-participant').click(function(){
		jQuery('#ad-pitched-by-participant-select').slideToggle();
		jQuery('select#ad-pitched-by-participant').val(ad_current_pitched_by_participant);
		jQuery('#ad-edit-pitched-by-participant').show();
		return false;
	});
		
});