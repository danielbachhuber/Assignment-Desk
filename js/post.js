/**
* Add a trim() method to all string objects
*/
if(typeof(String.prototype.trim) === "undefined") {
    String.prototype.trim = function() {
        return String(this).replace(/^\s+|\s+$/g, '');
    };
}

/**
* Take the user search input and the selected role and add that user to the list 
* of users assigned to a post with that role.
*
* Take care to show the div that surrounds the role list of the div was initially hidden.
*/
function add_to_assignees(){
	// get the form data
	var user_login = jQuery("#_ad-assignee-search").val().trim();
	var role_name = jQuery("#_ad-assigned-role-select :selected").text().trim();
	
	// create a new list item that hold a hidden form element.
	var field_html = '<li><input type="hidden" name="_ad-assignees[]" value="'+ user_login + '|' + role_name + '"/>' + user_login + '</li>';
	// Append it to the list
	jQuery("ul#_ad_assignees_role_" + role_name).append(field_html);

	// Show if initially empty (length now == 1)
	if(jQuery("ul#_ad_assignees_role_" + role_name + ' > li').length == 1){
		jQuery('div#_ad_assignees_role_' + role_name).slideToggle();
	}
	return false;
}

function setup_ajax_user_search(){
	// Get the search ket from the assignment desk box
	jQuery('#_ad-assignee-search').suggest(coauthor_ajax_suggest_link,
									{ onSelect: 
										function() {
											var vals = this.value.split("|");				
											var author = {}
											author.id = jQuery.trim(vals[0]);										
											author.login = jQuery.trim(vals[1]);
											author.name = jQuery.trim(vals[2]);
											jQuery('#_ad-assignee-search').val(author.name)
										}
								    })
    	                            .keydown(function(e) {
    	                                // ignore the enter key
    		                            if(e.keyCode == 13) { return false; }
    	                            })
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
		jQuery("#ad-assign-button").click(add_to_assignees);
		
		setup_ajax_user_search();
    }
);