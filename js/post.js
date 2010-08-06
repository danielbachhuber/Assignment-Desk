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
	return add_to_assignees(jQuery("#ad-assignee-search").val().trim(), 
	                             jQuery("#ad-assign-form .ad-user-role-select :selected").val());
	
}

/** 
* Get the user volunteer user_login and selected role and add to the assign.
*/
function add_volunteer_to_assignees(user_login){
    // Get the role from the assignee form
    var role_id = jQuery('#ad_volunteer_' + user_login + ' .ad-user-role-select :selected').val();
    add_to_assignees(user_login.toString().trim(), role_id);
    jQuery('#ad_volunteer_' + user_login).remove();
	var volunteer_count = parseInt(jQuery('#ad-volunteer-count').html());
	volunteer_count -= 1;
	if(volunteer_count == 1){
		jQuery('#ad-volunteer-count-wrap').html('Volunteer (1)');
	}
	else {
		jQuery('#ad-volunteer-count-wrap').html('Volunteers (' + volunteer_count + ')');
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

function setup_ajax_user_search(){
	// Get the search ket from the assignment desk box
	jQuery('#ad-assignee-search').suggest(coauthor_ajax_suggest_link,
									{ onSelect: 
										function() {
											var vals = this.value.split("|");				
											var author = {}
											author.id = jQuery.trim(vals[0]);										
											author.login = jQuery.trim(vals[1]);
											author.name = jQuery.trim(vals[2]);
											jQuery('#ad-assignee-search').val(author.name)
										}
								    })
    	                            .keydown(function(e) {
    	                                // ignore the enter key
    		                            if(e.keyCode == 13) { return false; }
    	                            })
}

/**
* When a user clicks the "Assign" button for a volunteer, show them a form to select the role.
*/
function show_volunteer_assign_form(user_login){
    user_login.trim()
    var volunteer_assign_form = "<label>User: </label>" + user_login;
    volunteer_assign_form += jQuery('div#ad-hidden-user-role-select').html();
    volunteer_assign_form += '<a class="button" onclick="javascript: return add_volunteer_to_assignees(\'' + user_login + '\');">Assign</a>';
    jQuery('#ad_volunteer_' + user_login).html(volunteer_assign_form);
    return false;  
}

jQuery(document).ready(    
    function(){
        jQuery(".fancybox").fancybox({
            "transitionIn"	:	"elastic",
        	"transitionOut"	:	"elastic",
        	"speedIn"		:	200, 
        	"speedOut"		:	200, 
        	"overlayShow"	:	false
        });
        
        // Toggle the pitch detail div with the link
        jQuery("a#toggle-ad-pitch-detail").click(
            function(){
                jQuery("div#ad-pitch-detail").slideToggle();
                return false;
            }
        );
		
		// Add the add_to_assignees function as a hook on the assign button
		jQuery("#ad-assign-button").click(add_user_to_assignees);
		
		setup_ajax_user_search();
    }
);