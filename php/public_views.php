<?php
if(!class_exists('ad_public_controller')){
	
class ad_public_views {
	
	function __construct() { 
	
	}
	
	function init() {
		global $assignment_desk;
		$options = $assignment_desk->general_options;
		
		wp_enqueue_script('ad-public-views', WP_PLUGIN_URL . '/assignment-desk/js/public_views.js', array('jquery'));
		
		// Run save_pitch_form() at WordPress initialization
		$_REQUEST['assignment_desk_messages']['pitch_form'] = $this->save_pitch_form();
		$_REQUEST['assignment_desk_messages']['volunteer_form'] = $this->save_volunteer_form();
		// @todo Save vote form
		
		add_filter('the_content', array(&$this, 'show_all_posts') );
		if ( $options['pitch_form_enabled'] ) {
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
			
			if ( $_REQUEST['assignment_desk_messages']['pitch_form']['success'] ) {
				$pitch_form .= '<div class="message success"><p>Pitch submitted successfully.</p></div>';
			} else if ( count($_REQUEST['assignment_desk_messages']['pitch_form']['errors']) ) {
				$pitch_form .= '<div class="message error"><p>Please correct the error(s) below.</p></div>';
			}
		
			$pitch_form .= '<form method="post" id="assignment_desk_pitch_form">';
			// Title
			if ( $options['pitch_form_title_label'] ) {
				$title_label = $options['pitch_form_title_label'];
			} else {
				$title_label = 'Title';
			}
			$pitch_form .= '<fieldset><label for="assignment_desk_title">' . $title_label . '</label>'
						. '<input type="text" id="assignment_desk_title" name="assignment_desk_title" />';
			if ( $options['pitch_form_title_description'] ) {
			$pitch_form .= '<p class="description">'
						. $options['pitch_form_title_description']
						. '</p>';
			}
			if ( $_REQUEST['assignment_desk_messages']['pitch_form']['errors']['title'] ) {
				$pitch_form .= '<p class="error">'
							. $_REQUEST['assignment_desk_messages']['pitch_form']['errors']['title']
							. '</p>';
			}
			$pitch_form	.= '</fieldset>';
			
			// Description
			if ( $options['pitch_form_description_enabled'] ) {
				if ( $options['pitch_form_description_label'] ) {
					$description_label = $options['pitch_form_description_label'];
				} else {
					$description_label = 'Description';
				}
				$pitch_form .= '<fieldset><label for="assignment_desk_description">' . $description_label . '</label>'
							. '<textarea id="assignment_desk_description"'
							. ' name="assignment_desk_description">';
				$pitch_form .= '</textarea>';
				if ( $options['pitch_form_description_description'] ) {
				$pitch_form .= '<p class="description">'
							. $options['pitch_form_description_description']
							. '</p>';
				}
				$pitch_form .= '</fieldset>';
			}
			
			// Categories
			if ( $options['pitch_form_categories_enabled'] ) {
				if ( $options['pitch_form_category_label'] ) {
					$category_label = $options['pitch_form_category_label'];
				} else {
					$category_label = 'Category';
				}	
				$pitch_form .= '<fieldset><label for="assignment_desk_categories">' . $category_label . '</label>';
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
			if ( $options['pitch_form_tags_enabled'] ) {
				if ( $options['pitch_form_tags_label'] ) {
					$tags_label = $options['pitch_form_tags_label'];
				} else {
					$tags_label = 'Tags';
				}	
				$pitch_form .= '<fieldset><label for="assignment_desk_tags">' . $tags_label . '</label>'
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
			if ( $options['pitch_form_location_enabled'] ) {
				if ( $options['pitch_form_location_label'] ) {
					$location_label = $options['pitch_form_location_label'];
				} else {
					$location_label = 'Location';
				}
				$pitch_form .= '<fieldset><label for="assignment_desk_location">' . $location_label . '</label>'
							. '<input type="text" id="assignment_desk_location"'
							. ' name="assignment_desk_location" />';
				if ( $options['pitch_form_location_description'] ) {
				$pitch_form .= '<p class="description">'
							. $options['pitch_form_location_description']
							. '</p>';
				}
				$pitch_form .= '</fieldset>';
			}
			
			// Volunteer
			if ( $options['pitch_form_volunteer_enabled'] ) {
				if ( $options['pitch_form_volunteer_label'] ) {
					$volunteer_label = $options['pitch_form_volunteer_label'];
				} else {
					$volunteer_label = 'Volunteer';
				}	
				$pitch_form .= '<fieldset><label for="assignment_desk_volunteer">' . $volunteer_label . '</label><ul id="assignment_desk_volunteer">';
				foreach ( $user_roles as $user_role ) {
					$pitch_form .= '<li><input type="checkbox" '
								. 'id="assignment_desk_volunteer_' . $user_role->term_id
								. '" name="assignment_desk_volunteer[]"'
								. ' value="' . $user_role->term_id . '"'
								. ' /><label for="assignment_desk_volunteer_'
								. $user_role->term_id .'">' . $user_role->name
								. '</label>';
				}
				$pitch_form .= '</ul>';
				if ( $options['pitch_form_volunteer_description'] ) {
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
						. '<input type="submit" value="Submit" id="assignment_desk_pitch_submit" name="assignment_desk_pitch_submit" /></fieldset>';					
					
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
		
		if ($_POST['assignment_desk_pitch_submit']) {
			
			$form_messages = array();
			
			$sanitized_title = $_POST['assignment_desk_title'];
			if ( !$sanitized_title ) {
				$form_messages['errors']['title'] = 'Please add a title to this pitch.';
			}
			$sanitized_author = (int)$_POST['assignment_desk_author'];
			$sanitized_description = $_POST['assignment_desk_description'];
			$sanitized_tags = $_POST['assignment_desk_tags'];
			$sanitized_categories = (int)$_POST['assignment_desk_categories'];
			$sanitized_location = $_POST['assignment_desk_location'];
			$sanitized_volunteer = $_POST['assignment_desk_volunteer'];
			
			if ( count($form_messages['errors']) ) {
				return $form_messages;
			}
		
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
				
			}
			
			$form_messages['success']['post_id'] = $post_id;
			
			return $form_messages;
		}
		
		return null;
		
	}
	
	/**
	 * Print a form with available roles and ability to volunteer
	 * @todo Display checked boxes for roles already volunteered for
	 */
	function volunteer_form( $post_id ) {
	    global $assignment_desk, $current_user;
		wp_get_current_user();
	    $user_roles = $assignment_desk->custom_taxonomies->get_user_roles();
	
		$existing_roles = get_post_meta( $post_id, "_ad_participant_$current_user->ID" );
		$existing_roles = $existing_roles[0];
	
	    $volunteer_form = '';
	    $volunteer_form .= '<form method="post" id="assignment_desk_volunteer_form">';
		$volunteer_form .= '<fieldset><ul id="assignment_desk_volunteer">';
		foreach ( $user_roles as $user_role ) {
			$volunteer_form .= '<li><input type="checkbox" id="assignment_desk_volunteer_' . $user_role->term_id
							. '" name="assignment_desk_volunteer_roles[]"'
							. ' value="' . $user_role->term_id . '"';
			if ( in_array($user_role->term_id, $existing_roles) ) {
				$volunteer_form .= ' checked="checked"';
			}
			$volunteer_form .= ' /><label for="assignment_desk_volunteer_'
							. $user_role->term_id .'">' . $user_role->name
							. '</label></li>';
		}
		$volunteer_form .= '</ul>';
	    $volunteer_form .= "<input type='hidden' name='assignment_desk_volunteer_user_id' value='$current_user->ID' />";	
	    $volunteer_form .= "<input type='hidden' name='assignment_desk_volunteer_post_id' value='$post_id' />";		
	    $volunteer_form .= '<input type="submit" id="assignment_desk_volunteer_submit" name="assignment_desk_volunteer_submit" class="button primary" value="Submit" />';
	    $volunteer_form .= "</form>";
	    return $volunteer_form;
	}
	
	/**
	 * Sanitize the user volunteer information and add them as a volunteer.
	 */
	function save_volunteer_form() {
	    global $assignment_desk, $current_user;
	    
		if ( $_POST['assignment_desk_volunteer_submit'] ) {
	    
		    // @todo Check for a nonce
			// @todo Ensure the user saving is the same user who submitted the form
			wp_get_current_user();
	    
		    $post_id = (int)$_POST['assignment_desk_volunteer_post_id'];
			$sanitized_roles = $_POST['assignment_desk_volunteer_roles'];
			$sanitized_user_id = (int)$_POST['assignment_desk_volunteer_user_id'];
			if ( $sanitized_user_id != $current_user->ID ) {
				return false;
			}
	    
		    // Filter the roles to make sure they're valid.
		    $user_roles = $assignment_desk->custom_taxonomies->get_user_roles();
			// @todo abstract this to class method
		    $valid_roles = array();
		    foreach($sanitized_roles as $maybe_role){
		        $maybe_role = (int)$maybe_role;
		        foreach ( $user_roles as $role ){
		            if ( $maybe_role == $role->term_id ) {
						$valid_roles[] = $maybe_role;
					}
		        }
	        }
	    
		    foreach ( $valid_roles as $role_id ) {
		        $role_meta = get_post_meta($post_id, '_ad_participant_role_' . $role_id, true);
		        if(!$role_meta){ $role_meta = array(); }
		        $role_meta[$current_user->user_login] = 'volunteered';
		        update_post_meta($post_id, '_ad_participant_role_' . $role_id, $role_meta);
		    }
			// Save the roles associated with the user id as well
			update_post_meta( $post_id, "_ad_participant_$sanitized_user_id", $valid_roles );
	
		}
	
	}
	
	/*
	* Replace an html comment <!--assignment-desk-all-posts--> with ad public pages.
	*/
	function show_all_posts( $the_content ) {
		global $wpdb, $assignment_desk;
		$options = $assignment_desk->general_options;
	  
		$template_tag = '<!--' . $assignment_desk->all_posts_key . '-->';
		
		$html = '';
		
		// @todo This should be customizable
		$args = array(	'post_status' => 'pitch,assigned' );
		
		$posts = new WP_Query($args);
		
		if ($posts->have_posts()) {
			while ($posts->have_posts()) {
				$posts->the_post();
				
				$post_id = get_the_ID();
				if ( get_post_meta($post_id, '_ad_private', true) == 1){
				    continue;
				}
				
				$description = get_post_meta($post_id, '_ef_description', true);
				$location = get_post_meta($post_id, '_ef_location', true);
				$duedate = get_post_meta($post_id, '_ef_duedate', true);
				$duedate = date_i18n('M d, Y', $duedate);
				
				$html .= '<div><h3><a href="' . get_permalink($post_id) . '">' . get_the_title($post_id) . '</a></h3>';
				if ($description || $duedate || $location) {
				    $html .= '<p class="meta">';
				}
				if ($description) {
				    $html .= '<label>Description:</label> ' . $description . '<br />';
				}
				if ($duedate) {
				    $html .= '<label>Due date:</label> ' . $duedate . '<br />';	
				}
				if ($location) {
				    $html .= '<label>Location:</label> ' . $location . '<br />';	
				}
				if ($description || $duedate || $location) {
				    $html .= '</p>';
				}
				
				$html .= $this->volunteer_form($post_id);
				$html .= "</div><br>";
			}
		}
		
		$the_content = str_replace($template_tag, $html, $the_content);
		
        return $the_content;
	}
} // END:class ad_public_controller

} // END:if(!class_exists('ad_public_controller'))
?>