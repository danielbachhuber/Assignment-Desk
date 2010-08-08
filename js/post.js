/**
* Add a trim() method to all string objects
*/
if(typeof(String.prototype.trim) === "undefined") {
    String.prototype.trim = function() {
        return String(this).replace(/^\s+|\s+$/g, '');
    };
}

/** 
* Get the user search input and selected role and add to the assign.
*/
function ad_add_user_to_assignees(){
	// get the form data
	var data = ad_add_to_assignees(jQuery("#ad-assignee-search").val().trim().split('|')[1], 
	                             jQuery("#ad-assign-form #ad-user-role-select :selected").val());
	return data;
	
}

/** 
* Get the user contributor user_login and selected role and add to the assign.
*/
function ad_add_participants_to_assignees(user_login){
    // Get the role from the assignee form
    var role_id = jQuery('#ad_participants_' + user_login + ' #ad-user-role-select :selected').val();
    ad_add_to_assignees(user_login.toString().trim(), role_id);
    jQuery('#ad_participants_' + user_login).remove();
	var participants_count = parseInt(jQuery('#ad-participants-count').html());
	participants_count -= 1;
	if(participants_count == 1){
		jQuery('#ad-participants-count-wrap').html('Contributors (1)');
	}
	else {
		jQuery('#ad-participants-count-wrap').html('Contributors (' + participants_count + ')');
	}
}

/**
* Take the user input and the selected role and add that user to the list 
* of users assigned to a post with that role.
* Take care to show the div that surrounds the role list of the div was initially hidden.
*/
function ad_add_to_assignees(user_login, role_id){	    
	if(!user_login.length || !role_id){
	    return false;
	}
	var already_assigned = false;
	
	// Check to see if this user was already assigned to this story.
	jQuery("input[name='ad-assignees[]']").each(function(){
		var split = jQuery(this).val().split('|');
		if (user_login == split[0]){
			already_assigned = true;
		}
	})
	
	if (already_assigned){
		jQuery('div#ad-error-messages').html(user_login + ' is already assigned to this story.').show();
		return false;
	}
	
	// create a new list item that hold a hidden form element.
	var field_html = '<li><input type="hidden" name="ad-assignees[]" value="'+ user_login + '|' + role_id + '"/>' + user_login + '</li>';
	// Append it to the list
	jQuery("ul#ad_assignees_role_" + role_id).append(field_html);

	// Show if initially empty (length now == 1)
	if(jQuery("ul#ad_assignees_role_" + role_id + ' > li').length == 1){
		jQuery('div#ad_assignees_role_' + role_id).slideToggle();
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
	
	var ad_current_participant_types = [];
    
	/**
     * Toggle post_meta_box subheads
	 */
	jQuery('h4.toggle').click(function() {
		var inner = jQuery(this).parent().find('div.inner').slideToggle();
	});
		
	// Add the ad_add_to_assignees function as a hook on the assign button
	jQuery("#ad-assign-button").click(ad_add_user_to_assignees);
	
	
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
	});
		
    jQuery('#ad-assignee-search').suggest(coauthor_ajax_suggest_link,
		{ onSelect: 
			function() {
  			var vals = this.value.split("|");				
			var author = {}
			author.id = jQuery.trim(vals[0]);										
			author.login = jQuery.trim(vals[1]);
			author.name = jQuery.trim(vals[2]);
			Query('#ad-assignee-search').val(author.name)
		}
	}).keydown(function(e) {
		// ignore the enter key
		if(e.keyCode == 13) { return false; }
	});
		
});