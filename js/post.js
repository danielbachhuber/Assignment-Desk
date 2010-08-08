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
function add_user_to_assignees(){
	// get the form data
	var data = add_to_assignees(jQuery("#ad-assignee-search").val().trim().split('|')[1], 
	                             jQuery("#ad-assign-form #ad-user-role-select :selected").val());
	return data;
	
}

/** 
* Get the user contributor user_login and selected role and add to the assign.
*/
function add_participants_to_assignees(user_login){
    // Get the role from the assignee form
    var role_id = jQuery('#ad_participants_' + user_login + ' #ad-user-role-select :selected').val();
    add_to_assignees(user_login.toString().trim(), role_id);
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
function add_to_assignees(user_login, role_id){	    
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

jQuery(document).ready(function(){
    
	/**
     * Toggle post_meta_box subheads
	 */
	jQuery('h4.toggle').click(function() {
		var inner = jQuery(this).parent().find('div.inner').slideToggle();
	});
		
	// Add the add_to_assignees function as a hook on the assign button
	jQuery("#ad-assign-button").click(add_user_to_assignees);
	
	jQuery('#ad-edit-participant-types').click(function(){
		jQuery(this).hide();
		jQuery('#ad-participant-types-select').slideToggle();
	});
	
	jQuery('#save-ad-participant-types').click(function(){
		jQuery('#ad-participant-types-select').slideToggle();
		// @todo More logic with manipulating dom
		jQuery('#ad-edit-participant-types').show();
	});
	
	jQuery('#cancel-ad-participant-types').click(function(){
		jQuery('#ad-participant-types-select').slideToggle();
		// @todo More logic with manipulating dom		
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