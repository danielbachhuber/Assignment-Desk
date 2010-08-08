<?php
if(!class_exists('ad_public_controller')){
	
class ad_public_views {
	
	function __construct() { 
	
	}
	
	function init() {
		global $assignment_desk;
		$options = $assignment_desk->general_options;
		
		// Run save_pitch_form() at WordPress initialization
		$message = $this->save_pitch_form();
		
		if ( $message ) {
			// @todo Add a message to top of form if exists
		}
		
		add_filter('the_content', array(&$this, 'filter_show_public_pages') );
		if ($options['pitch_form_enabled']) {
			add_filter('the_content', array(&$this, 'show_pitch_form') );
		}
	}
	
	function show_pitch_form($the_content) {
		global $assignment_desk, $current_user;
		
		if ($assignment_desk->edit_flow_exists()) {
			global $edit_flow;
		}
		wp_get_current_user();
		
		$options = $assignment_desk->general_options;
		$user_roles = $assignment_desk->custom_taxonomies->get_user_roles();
		
		$category_args = array(
	    	  	'type'		=> 'post',
	    		'child_of'	=> 0,
	    		'orderby'	=> 'id',
	    		'order'		=> 'ASC',
	    		'hide_empty'=> 0,
	    		'hierarchical'=> True  
	    );
	    $categories = get_categories($category_args);
		
		$template_tag = '<!--assignment-desk-pitch-form-->';

		if ( is_user_logged_in() ) {

			$pitch_form = '';
		
			// Title
			$pitch_form .= '<form method="post" id="assignment_desk_pitch_form">'
						. '<fieldset><label for="assignment_desk_title">Title</label>'
						. '<input type="text" id="assignment_desk_title" name="assignment_desk_title" /></fieldset>';
			
			// Description
			if ($options['pitch_form_description_enabled']) {		
				$pitch_form .= '<fieldset><label for="assignment_desk_description">Description</label>'
							. '<textarea id="assignment_desk_description"'
							. ' name="assignment_desk_description">';
				$pitch_form .= '</textarea>';
				if ($options['pitch_form_description_description']) {
				$pitch_form .= '<p class="description">'
							. $options['pitch_form_description_description']
							. '</p>';
				}
				$pitch_form .= '</fieldset>';
			}
			
			// Categories
			if ($options['pitch_form_categories_enabled']) {		
				$pitch_form .= '<fieldset><label for="assignment_desk_categories">Category</label>';
				$pitch_form .= '<select id="assignment_desk_categories" name="assignment_desk_categories">';
				foreach ( $categories as $category ) {
					$pitch_form .= '<option value="' . $category->term_id . '">'
								. $category->name
								. '</option>';
				}
				$pitch_form .= '</select>';
				if ($options['pitch_form_categories_description']) {
				$pitch_form .= '<p class="description">'
							. $options['pitch_form_categories_description']
							. '</p>';
				}
				$pitch_form .= '</fieldset>';
			}		
						
			// Tags
			if ($options['pitch_form_tags_enabled']) {		
				$pitch_form .= '<fieldset><label for="assignment_desk_tags">Tags</label>'
							. '<input type="text" id="assignment_desk_tags"'
							. ' name="assignment_desk_tags" />';
				if ($options['pitch_form_tags_description']) {
				$pitch_form .= '<p class="description">'
							. $options['pitch_form_tags_description']
							. '</p>';
				}
				$pitch_form .= '</fieldset>';
			}
			
			// Location
			if ($options['pitch_form_location_enabled']) {		
				$pitch_form .= '<fieldset><label for="assignment_desk_location">Location</label>'
							. '<input type="text" id="assignment_desk_location"'
							. ' name="assignment_desk_location" />';
				if ($options['pitch_form_location_description']) {
				$pitch_form .= '<p class="description">'
							. $options['pitch_form_location_description']
							. '</p>';
				}
				$pitch_form .= '</fieldset>';
			}
			
			// Volunteer
			if ($options['pitch_form_volunteer_enabled']) {		
				$pitch_form .= '<fieldset><label for="assignment_desk_volunteer">Volunteer</label><ul id="assignment_desk_volunteer">';
				foreach ($user_roles as $user_role) {
					$pitch_form .= '<li><input type="checkbox" '
								. 'id="assignment_desk_volunteer_' . $user_role->term_id
								. '" name="assignment_desk_volunteer[]"'
								. ' value="' . $user_role->term_id . '"'
								. ' /><label for="assignment_desk_volunteer_'
								. $user_role->term_id .'">' . $user_role->name
								. '</label>';
				}
				$pitch_form .= '</ul>';
				if ($options['pitch_form_volunteer_description']) {
				$pitch_form .= '<p class="description">'
							. $options['pitch_form_volunteer_description']
							. '</p>';
				}
				$pitch_form .= '</fieldset>';
			}
			
			// @todo Confirm your user information
			
			// Author information and submit
			$pitch_form .= '<fieldset>'
						. '<input type="hidden" id="assignment_desk_author" name="assignment_desk_author" value="' . $current_user->ID . '" />'
						. '<input type="submit" value="Submit Pitch" id="assignment_desk_submit" name="assignment_desk_submit" /></fieldset>';					
					
			$pitch_form .= '</form>';
			
		} else {
			
			$pitch_form = '<div class="message alert">Oops, you have to be logged in to submit a pitch.</div>';
			
		}

		$the_content = str_replace($template_tag, $pitch_form, $the_content);
		return $the_content;
	}
	
	function save_pitch_form() {
		global $assignment_desk, $current_user;
		$message = array();
		$options = $assignment_desk->general_options;
		$user_types = $assignment_desk->custom_taxonomies->get_user_types();

		if ($assignment_desk->edit_flow_exists()) {
			global $edit_flow;
		}
		
		
		// @todo Check for a nonce
		// @todo Sanitize all of the fields
		// @todo Validate all of the fields
		
		if ($_POST['assignment_desk_submit']) {
			
			$sanitized_title = $_POST['assignment_desk_title'];
			$sanitized_author = (int)$_POST['assignment_desk_author'];
			$sanitized_description = $_POST['assignment_desk_description'];
			$sanitized_tags = $_POST['assignment_desk_tags'];
			$sanitized_categories = (int)$_POST['assignment_desk_categories'];
			$sanitized_location = $_POST['assignment_desk_location'];
			$sanitized_volunteer = $_POST['assignment_desk_volunteer'];
		
			$new_pitch = array();
			$new_pitch['post_title'] = $sanitized_title;
			$new_pitch['post_author'] = $sanitized_author;
			$new_pitch['post_content'] = '';
			if ( $assignment_desk->edit_flow_exists() ) {
				$default_status = get_term_by('term_id', $options['default_workflow_status'], 'post_status');
				$new_pitch['post_status'] = $default_status->slug;
			} else {
				$new_pitch['post_status'] = 'draft';
			}
			$new_pitch['post_category'] = array($sanitized_categories);
			$new_pitch['tags_input'] = $sanitized_tags;
			$post_id = wp_insert_post($new_pitch);
			
			// Once the pitch is saved, we can save data to custom fields
			if ( $post_id ) {
				
				// Save description to Edit Flow metadata field
				update_post_meta($post_id, '_ef_description', $sanitized_description);
				
				// Save location to Edit Flow metadata field
				update_post_meta($post_id, '_ef_location', $sanitized_location);
				
				// Save pitched_by_participant and pitched_by_date information
				update_post_meta($post_id, '_ad_pitched_by_participant', $sanitized_author);
				update_post_meta($post_id, '_ad_pitched_by_timestamp', date_i18n('U'));
				
				// Set assignment status to default setting
				$default_status = $assignment_desk->custom_taxonomies->get_default_assignment_status();
				wp_set_object_terms($post_id, (int)$default_status->term_id, $assignment_desk->custom_taxonomies->assignment_status_label);
				
				// A new assignment gets all User Types by default
				foreach ($user_types as $user_type) {			
					update_post_meta($post_id, "_ad_participant_type_$user_type->term_id", 'on');
				}
				
				// Save the roles user volunteered for both with each role
				// and under the user's row
				foreach ($sanitized_volunteer as $volunteered_role) {
					$role_data = array();
					$role_data[$sanitized_author] = 'volunteered';
					update_post_meta($post_id, "_ad_participant_role_$volunteered_role", $role_data);
				}
				update_post_meta($post_id, "_ad_participant_$sanitized_author", $sanitized_volunteer);
				
			} else {
				return 'error';
			}
			
			return 'message';
		}
		
		return null;
		
	}
	
	
	/*
	* Replace an html comment <!--assignment-desk-all-stories-> with ad public pages.
	*/
	function filter_show_public_pages($the_content){
		global $wpdb, $assignment_desk;
	  
		$tag = '<!--assignment-desk-all-stories-->';
		$start = strpos($the_content, $tag);
		$my_content  = $the_content;
        if ($start){
            $before_ad = substr($the_content, 0, $start);
            $after_ad = substr($the_content, $start + strlen($tag), strlen($the_content));
            $ad = $this->public_content();
            $my_content = $before_ad . $ad . $after_ad;
        }
        
        return $my_content;
	}
	
	function public_content(){
	    return 'Im public yo.';
	}
} // END:class ad_public_controller

} // END:if(!class_exists('ad_public_controller'))
?>