<?php
if(!class_exists('ad_public_controller')){

require_once(ASSIGNMENT_DESK_DIR_PATH . '/php/utils.php');
	
class ad_public_views {
	
	function __construct() { 

	}
	
	function init() {
		global $assignment_desk;
		$pitch_form_options = $assignment_desk->pitch_form_options;		
		$public_facing_options = $assignment_desk->public_facing_options;
		
		wp_enqueue_script('jquery-datepicker-js', ASSIGNMENT_DESK_URL .'js/jquery.datepicker.js', array('jquery-ui-core'));
		wp_enqueue_script('ad-public-views', ASSIGNMENT_DESK_URL . 'js/public_views.js', array('jquery', 'jquery-datepicker-js'));
		
		wp_enqueue_style('ad-public', ASSIGNMENT_DESK_URL . 'css/public.css');
		
		// Run save_pitch_form() at WordPress initialization
		$_REQUEST['assignment_desk_messages']['pitch_form'] = $this->save_pitch_form();
		$_REQUEST['assignment_desk_messages']['volunteer_form'] = $this->save_volunteer_form();
		$_REQUEST['assignment_desk_messages']['voting'] = $this->save_voting_form();
		
		add_filter( 'the_content', array(&$this, 'show_all_posts') );
		
		add_filter( 'the_posts', array(&$this, 'show_single_post') );
		
		// Only add voting if its enabled
		if ( $public_facing_options['public_facing_voting_enabled'] ) {
			add_filter( 'the_content', array(&$this, 'prepend_voting_to_post') );		
		}
		// Only add volunteering if its enabled
		if ( $public_facing_options['public_facing_volunteering_enabled'] ) {
			add_filter( 'the_content', array(&$this, 'append_volunteering_to_post') );		
		}
		// Only show pitch forms if the functionality is enabled
		if ( $pitch_form_options['pitch_form_enabled'] ) {
			add_filter( 'the_content', array(&$this, 'show_pitch_form') );
		}
	}
	
	function show_pitch_form( $the_content ) {
		global $assignment_desk, $current_user;

		$options = $assignment_desk->pitch_form_options;		
		
		if ($assignment_desk->edit_flow_exists()) {
			global $edit_flow;
		}
		wp_get_current_user();
	
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
			$pitch_form .= '<fieldset class="standard"><label for="assignment_desk_title">' . $title_label . '</label>'
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
				$pitch_form .= '<fieldset class="standard"><label for="assignment_desk_description">' . $description_label . '</label>'
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
			
			// Due Date
			if ( $options['pitch_form_duedate_enabled'] ) {
				if ( $options['pitch_form_dudedate_label'] ) {
					$duedate_label = $options['pitch_form_dudedate_label'];
				} else {
					$duedate_label = 'Due Date';
				}	
				$pitch_form .= '<fieldset class="standard"><label for="assignment_desk_duedate">' . $duedate_label . '</label>';
				$pitch_form .= '<input type="text" size="12" name="assignment_desk_duedate" id="assignment_desk_duedate">';
				if ( $options['pitch_form_dudedate_description'] ) {
				    $pitch_form .= '<p class="description">' . $options['pitch_form_dudedate_description'] . '</p>';
				}
				if ( $_REQUEST['assignment_desk_messages']['pitch_form']['errors']['duedate'] ) {
    				$pitch_form .= '<p class="error">'
    							. $_REQUEST['assignment_desk_messages']['pitch_form']['errors']['duedate']
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
				$pitch_form .= '<fieldset class="standard"><label for="assignment_desk_categories">' . $category_label . '</label>';
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
				$pitch_form .= '<fieldset class="standard"><label for="assignment_desk_tags">' . $tags_label . '</label>'
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
				$pitch_form .= '<fieldset class="standard"><label for="assignment_desk_location">' . $location_label . '</label>'
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
				$pitch_form .= '<fieldset class="standard"><label for="assignment_desk_volunteer">' . $volunteer_label . '</label><ul id="assignment_desk_volunteer">';
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
			$pitch_form .= '<fieldset class="submit">'
						. '<input type="hidden" id="assignment_desk_author" name="assignment_desk_author" value="' . $current_user->ID . '" />';
						
			$pitch_form .= '<input type="hidden" name="assignment_desk_pitch_nonce" value="' 
						. wp_create_nonce('assignment_desk_pitch') . '" />';
			$pitch_form .= '<input type="submit" value="Submit" id="assignment_desk_pitch_submit" name="assignment_desk_pitch_submit" /></fieldset>';					
					
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
		
		// @todo Sanitize all of the fields
		// @todo Validate all of the fields
		
		if ($_POST['assignment_desk_pitch_submit']) {
			
			$form_messages = array();			
			
			// Ensure that it was the user who submitted the form, not a bot
			if ( !wp_verify_nonce($_POST['assignment_desk_pitch_nonce'], 'assignment_desk_pitch') ) {
				return $form_messages['error']['nonce'];
			}
			
			$sanitized_title = $_POST['assignment_desk_title'];
			if ( !$sanitized_title ) {
				$form_messages['errors']['title'] = 'Please add a title to this pitch.';
			}
			$sanitized_author = (int)$_POST['assignment_desk_author'];
			$sanitized_description = wp_kses($_POST['assignment_desk_description'], $allowedposttags);
			$sanitized_tags = $_POST['assignment_desk_tags'];
			$sanitized_categories = (int)$_POST['assignment_desk_categories'];
			$sanitized_location = wp_kses($_POST['assignment_desk_location'], $allowedposttags);
			$sanitized_volunteer = $_POST['assignment_desk_volunteer'];
			
			// Sanitize the duedate
			$sanitized_duedate = '';
			$duedate_split = split('/', $_POST['assignment_desk_duedate']);
			if(!count($duedate_split) == 3){
			    $form_messages['errors']['duedate'] = 'Please enter a valid date of the form MM/DD/YYYY';
			}
			else {
			    $duedate_month = (int)$duedate_split[0];
			    $duedate_day = (int)$duedate_split[1];
			    $duedate_year = (int)$duedate_split[2];
			    
			    // Zero pad for strtime
			    if($duedate_month < 10 ){
			        $duedate_month = "0$duedate_month";
			    }
			    $sanitized_duedate = strtotime($duedate_day . '-' . $duedate_month . '-' . $duedate_year);
			    if(!$sanitized_duedate){
			        $form_messages['errors']['duedate'] = 'Please enter a valid date of the form MM/DD/YYYY';
			    }
			}
			
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
				
				if($sanitized_duedate){
				    // Save duedate to Edit Flow metadata field
    				update_post_meta($post_id, '_ef_duedate', $sanitized_duedate);
				}
				
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
				
				if($sanitized_volunteer){				    
    				// Save the roles user volunteered for both with each role
    				// and under the user's row
    				$all_roles = array();				
    				foreach ($sanitized_volunteer as $volunteered_role) {
    					$volunteered_role = (int)$volunteered_role;
    					$all_roles[] = $volunteered_role;
    					$role_data = array();
    					$role_data[$sanitized_author] = 'volunteered';
    					update_post_meta($post_id, "_ad_participant_role_$volunteered_role", $role_data);
    				}
    				update_post_meta($post_id, "_ad_participant_$sanitized_author", $sanitized_volunteer);
				}
			}
			
			$form_messages['success']['post_id'] = $post_id;
			
			return $form_messages;
		}
		
		return null;
		
	}
	
	/**
	 * Print a form giving the user the option to vote on an item
	 */
	function voting_form( $post_id = null ) {
		global $assignment_desk, $current_user;
		$options = $assignment_desk->public_facing_options;
		
		if ( !$post_id ) {
			global $post;
			$post_id = $post->ID;
		}
		
		if ( is_user_logged_in() ) {
			
			wp_get_current_user();
			$all_votes = get_post_meta( $post_id, '_ad_votes_all', true );
			$total_votes = (int)get_post_meta( $post_id, '_ad_votes_total', true );
						
			if ( !is_array($all_votes) ){
			    $all_votes = array();
			}
			
			$user_id = $current_user->ID;
			
			// If the user hasn't voted before, show the vote button
			if ( !in_array( $user_id, $all_votes ) ) {
				$voting_form = '<form method="post" class="assignment_desk_voting_form">'
							. '<input type="hidden" name="assignment_desk_voting_user_id" value="' . $user_id . '" />'
							. '<input type="hidden" name="assignment_desk_voting_post_id" value="' . $post_id . '" />';
				if ( $options['public_facing_voting_button'] ) {
					$voting_button = $options['public_facing_voting_button'];
				} else {
					$voting_button = 'Vote';
				}
				$voting_form .= '<input type="hidden" name="assignment_desk_voting_nonce" value="' 
							. wp_create_nonce('assignment_desk_voting') . '" />';
				$voting_form .= '<input type="submit" class="assignment_desk_voting_submit button"'
							. ' name="assignment_desk_voting_submit" value="' . $voting_button . '" />'
							. '</form>';
				return $voting_form;			
			} else if ( $_REQUEST['assignment_desk_messages']['voting']['success'] ) {
				$voting_message = '<div class="message success">'
								. $_REQUEST['assignment_desk_messages']['voting']['success']['message']
								. '</div>';
				return $voting_message;
			}
			
		}
		
	}
	
	function show_all_votes( $post_id = null ) {
		global $assignment_desk, $current_user;
		
		if ( !$post_id ) {
			global $post;
			$post_id = $post->ID;
		}
		
		$all_votes = get_post_meta( $post_id, '_ad_votes_all', true );
		if(!$all_votes){
		    $all_votes = array();
		}
		$total_votes = (int)get_post_meta( $post_id, '_ad_votes_total', true );
		
		if (!$total_votes) {
			$votes_html = '<div class="ad_all_votes message alert">No votes yet, you could be the first!</div>';
			return $votes_html;
		} else {
			$votes_html = '<div class="ad_all_votes"><span class="ad_total_votes">' . $total_votes . '</span>';
			foreach ( $all_votes as $user_id ) {
				$votes_html .= get_avatar( $user_id, 40 );
			}
			$votes_html .= '</div>';
			return $votes_html;
		}
				
		
	}
	
	function save_voting_form( ) {
		global $assignment_desk, $current_user;
	    
		if ( $_POST['assignment_desk_voting_submit'] && is_user_logged_in() ) {
			$form_messages = array();
			
			// Ensure that it was the user who submitted the form, not a bot
			if ( !wp_verify_nonce($_POST['assignment_desk_voting_nonce'], 'assignment_desk_voting') ) {
				return $form_messages['error']['nonce'];
			}
	    
			wp_get_current_user();
	    
		    $post_id = (int)$_POST['assignment_desk_voting_post_id'];
			$sanitized_user_id = (int)$_POST['assignment_desk_voting_user_id'];
			// Ensure the user saving is the same user who submitted the form 
			if ( $sanitized_user_id != $current_user->ID ) {
				return false;
			}
			
			$all_votes = get_post_meta( $post_id, '_ad_votes_all', true );
			$total_votes = (int)get_post_meta( $post_id, '_ad_votes_total', true );
			
			if(!is_array($all_votes)){
			    $all_votes = array();
			}
			
			if ( !in_array( $user_id, $all_votes ) ) {
				$all_votes[] = $sanitized_user_id;
				update_post_meta( $post_id, '_ad_votes_all', $all_votes );
				update_post_meta( $post_id, '_ad_votes_total', count($all_votes) );
				$form_messages['success']['message'] = 'Thanks for your vote!';
			} else {
				$form_messages['error']['message'] = 'Whoops, you already voted.';
			}
			return $form_messages;
		}
		
	}
	
	/**
	 * Print a form with available roles and ability to volunteer
	 */
	function volunteer_form( $post_id = null ) {
	    global $assignment_desk, $current_user;
	
		if ( !$post_id ) {
			global $post;
			$post_id = $post->ID;
		}
	
		if ( is_user_logged_in() ) {
	
			wp_get_current_user();
		    $user_roles = $assignment_desk->custom_taxonomies->get_user_roles();
	
			// See whether the user has already volunteered for the story
			$existing_roles = get_post_meta( $post_id, "_ad_participant_$current_user->ID", true );
			if ( !$existing_roles ) {
		        $existing_roles = array();  
			} 
	
		    $volunteer_form = '';
		    $volunteer_form .= '<form method="post" class="assignment_desk_volunteer_form">';
			$volunteer_form .= '<fieldset class="standard"><ul class="assignment_desk_volunteer">';
			foreach ( $user_roles as $user_role ) {
				$volunteer_form .= '<li><input type="checkbox" id="assignment_desk_post_' . $post_id
								. '_volunteer_' . $user_role->term_id
								. '" name="assignment_desk_volunteer_roles[]"'
								. ' value="' . $user_role->term_id . '"';
				if (in_array($user_role->term_id, $existing_roles) ) {
					$volunteer_form .= ' checked="checked"';
				}
				$volunteer_form .= ' /><label for="assignment_desk_post_' . $post_id
								. '_volunteer_' . $user_role->term_id .'">' . $user_role->name
								. '</label></li>';
			}
			$volunteer_form .= '</ul></fieldset>';
		    $volunteer_form .= "<input type='hidden' name='assignment_desk_volunteer_user_id' value='$current_user->ID' />";	
		    $volunteer_form .= "<input type='hidden' name='assignment_desk_volunteer_post_id' value='$post_id' />";	
			$volunteer_form .= '<input type="hidden" name="assignment_desk_volunteering_nonce" value="' 
							. wp_create_nonce('assignment_desk_volunteering') . '" />';	
		    $volunteer_form .= '<fieldset class="submit"><input type="submit" id="assignment_desk_volunteer_submit" name="assignment_desk_volunteer_submit" class="button primary" value="Submit" /></fieldset';
		    $volunteer_form .= "</form>";
		    return $volunteer_form;
		
		} else {
			
			$volunteer_message = '<div class="message alert">You must be logged in to volunteer.</div>';
			return $volunteer_message;
			
		}
	}
	
	/**
	 * List total count for all volunteers
	 * @param int $post_id The Post ID
	 */
	function show_all_volunteers( $post_id = null ) {
	    global $assignment_desk;
	    $user_roles = $assignment_desk->custom_taxonomies->get_user_roles();
	
		if ( !$post_id ) {
			global $post;
			$post_id = $post->ID;
		}
		
		$show_all_volunteers = '<div class="assignment_desk_all_volunteers">';
		foreach ( $user_roles as $user_role ) {
			$show_all_volunteers .= '<span class="label">' . $user_role->name . 's:</span>&nbsp;';
			$volunteers_for_role = array();
			$volunteers_for_role = get_post_meta( $post_id, "_ad_participant_role_$user_role->term_id" );
			$show_all_volunteers .= count($volunteers_for_role[0]) . ', ';
		}
		$show_all_volunteers = rtrim( $show_all_volunteers, ', ' );
		$show_all_volunteers .= '</div>';
		return $show_all_volunteers;
	}
	
	/**
	 * Sanitize the user volunteer information and add them as a volunteer.
	 */
	function save_volunteer_form() {
	    global $assignment_desk, $current_user;
	    
		if ( $_POST['assignment_desk_volunteer_submit'] && is_user_logged_in() ) {
	    
			$form_messages = array();
	
			// Ensure that it was the user who submitted the form, not a bot
			if ( !wp_verify_nonce($_POST['assignment_desk_volunteering_nonce'], 'assignment_desk_volunteering') ) {
				return $form_messages['error']['nonce'];
			}
			wp_get_current_user();
	    
		    $post_id = (int)$_POST['assignment_desk_volunteer_post_id'];
			$sanitized_roles = $_POST['assignment_desk_volunteer_roles'];
			$sanitized_user_id = (int)$_POST['assignment_desk_volunteer_user_id'];
			// Ensure the user saving is the same user who submitted the form 
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
	
			// Get previous roles, 
			foreach ( $user_roles as $user_role ) {
				$previous_values = get_post_meta($post_id, '_ad_participant_role_' . $user_role->term_id, true);
				if ( in_array( $user_role->term_id, $valid_roles ) && !isset( $previous_values[$current_user->ID] ) ) {
					$previous_values[$current_user->ID] = 'volunteered';
					update_usermeta($current_user->ID, '_ad_volunteer', $post_id);
				} else if ( !in_array( $user_role->term_id, $valid_roles ) && $previous_values[$current_user->ID] == 'volunteered' ) {
					unset($previous_values[$current_user->ID]);
				}
				$new_values = $previous_values;
				update_post_meta($post_id, '_ad_participant_role_' . $user_role->term_id, $new_values);
			}
			// Save the roles associated with the user id as well
			update_post_meta( $post_id, "_ad_participant_$sanitized_user_id", $valid_roles );
	
		}
	
	}
	
	/**
	 * Hook into the WP_Query object to show unpublished posts
	 * Will only show the post if it has a 'public' (defined in settings) assignment status
	 */
	function show_single_post( $all_posts ) {
		
		if ( empty($posts) && is_single() ) {
			$args = array(
					'post_id' => $_GET['p'],
					'showposts' => 1
					);
			$results = ad_get_all_public_posts( $args );
			if ( !empty($results) ) {
				$all_posts = $results;
			}
		}
		
		return $all_posts;
	}
	
	/*
	* Replace an html comment <!--assignment-desk-all-posts--> with ad public pages.
	*/
	function show_all_posts( $the_content ) {
		global $wpdb, $assignment_desk, $post, $edit_flow;
		$options = $assignment_desk->public_facing_options;
	  
		$template_tag = '<!--' . $assignment_desk->all_posts_key . '-->';
		
		if ( !strpos( $the_content, $template_tag ) ) {
			return $the_content;
		}
		
		// Save the parent post so we can reset the object later
		$parent_post = $post;		
		
		$html = '';
		
		if ( $_POST['sort_by'] == 'ranking' || $_POST['sort_by'] == 'post_date' || $_POST['sort_by'] == 'due_date' ) {
			$sort_by = $_POST['sort_by'];
		} else {
			$sort_by = 'post_date';
		}
		
		if ( isset($_POST['user_types']) && $_POST['user_types'] != 'all' ) {
			$user_type_filter = (int)$_POST['user_types'];
		} else {
			$user_type_filter = 'all';
		}
		
		if ( isset($_POST['post_status']) && $_POST['post_status'] != 'all' ) {
			$post_status_filter = $_POST['post_status'];
		} else {
			$post_status_filter = 'all';
		}
		
		$args = array(
					'post_status' => $post_status_filter,
					'user_types' => $user_type_filter,
					'sort_by' => $sort_by
					);
		$all_pitches = ad_get_all_public_posts( $args );
		
		$html .= '<form class="assignment-desk-filter-form" method="POST">';
		
		$html .= '<input type="hidden" name="page_id" value="' . $parent_post->ID . '" />';
		
		$html .= '<span class="left">';
		
		if ( $options['public_facing_filtering_post_status_enabled'] ) {
			$html .= '<select name="post_status" class="assignment-desk-filter-post-statuses">';
			$html .= '<option value="all">Show all post statuses</options>';
			if ( $assignment_desk->edit_flow_exists() ) {
				$custom_statuses = $edit_flow->custom_status->get_custom_statuses();
				foreach ( $custom_statuses as $custom_status ) {
					$html .= '<option value="' . $custom_status->slug . '"';
					if ( $custom_status->slug == $post_status_filter ) {
						$html .= ' selected="selected"';
					}
					$html .= '>' . $custom_status->name . '</option>';
				}
			} else {
				$html .= '<option value="pending">Pending Review</option>';
				$html .= '<option value="draft">Draft</option>';
			}
			$html .= '</select>';
		}
		
		if ( $options['public_facing_filtering_participant_type_enabled'] ) {
			$user_types = $assignment_desk->custom_taxonomies->get_user_types();
			$html .= '<select name="user_types" class="assignment-desk-filter-participant-types">';
			$html .= '<option value="all">Show all eligible types</options>';
			foreach ( $user_types as $user_type ) {
				$html .= '<option value="' . $user_type->term_id . '"';
				if ( $user_type_filter == $user_type->term_id ) {
					$html .= ' selected="selected"';
				}
				$html .= '>' . $user_type->name . '</option>';
			}
			$html .= '</select>';
		}
		
		// Filter button
		$html .= '<input type="submit" name="assignment-desk-filter-button" class="assignment-desk-filter-button" value="Filter" />';
		$html .= '</span>';
		
		$html .= '<span class="right">';
		if ( $options['public_facing_filtering_sort_by_enabled'] ) {
			$html .= '<select name="sort_by" class="assignment-desk-sort-by">'
				. '<option value="post_date"';
			if ( $sort_by == 'post_date' ) {
				$html .= ' selected="selected"';
			}
			$html .= '>Post date</option>';
			if ( $options['public_facing_voting_enabled'] ) {
				$html .= '<option value="ranking"';
				if ( $sort_by == 'ranking' ) {
					$html .= ' selected="selected"';
				}
				$html .= '>Ranking</option>';
			}
			if ( $assignment_desk->edit_flow_exists() ) {
				$html .= '<option value="due_date"';
				if ( $sort_by == 'due_date' ) {
					$html .= ' selected="selected"';
				}
				$html .= '>Due date</option>';
			}
			
			$html .= '</select>';
		}
		// Sort button
		$html .= '<input type="submit" name="assignment-desk-sort-button" class="assignment-desk-sort-button" value="Sort" />';
		$html .= '</span>';
		$html .= '</form>';
		
		foreach ( $all_pitches as $pitch ) {
			
			$post_id = $pitch->ID;
			$description = get_post_meta( $post_id, '_ef_description', true );
			$location = get_post_meta( $post_id, '_ef_location', true );
			$duedate = get_post_meta( $post_id, '_ef_duedate', true );
			$duedate = date_i18n( 'M d, Y', $duedate );
			
			$html .= '<div class="assignment-desk-pitch"><h3><a href="' . get_permalink($post_id) . '">' . get_the_title($post_id) . '</a></h3>';
			// Only show voting if it's enabled
			if ( $options['public_facing_voting_enabled'] ) {
				$html .= $this->show_all_votes( $post_id );					
				$html .= $this->voting_form( $post_id );
			}
			
			if ( $description || $duedate || $location ) {
			    $html .= '<div class="meta">';
			}
			if ( $options['public_facing_description_enabled'] && $description ) {
			    $html .= '<p><label>Description:</label> ' . $description . '</p>';
			}
			if ( $options['public_facing_duedate_enabled'] && $duedate ) {
			    $html .= '<p><label>Due date:</label> ' . $duedate . '</p>';	
			}
			if ( $options['public_facing_location_enabled'] && $location ) {
			    $html .= '<p><label>Location:</label> ' . $location . '</p>';	
			}
			if ( $options['public_facing_categories_enabled'] ) {
				$categories = get_the_category( $post_id );
				$categories_html = '';
				foreach ( $categories as $category ) {
					$categories_html .= '<a href="' . get_category_link($category->cat_ID) . '">' . $category->name . '</a>, ';
				}
				$html .= '<p><label>Categories:</label> ' . rtrim( $categories_html, ', ' ) . '</p>';
			}
			if ( $options['public_facing_tags_enabled'] ) {
				$tags = get_the_tags( $post_id );
				$tags_html = '';
				foreach ( $tags as $tag ) {
					$tags_html .= '<a href="' . get_tag_link($tag->term_id) . '">' . $tag->name . '</a>, ';
				}
				$html .= '<p><label>Tags:</label> ' . rtrim( $tags_html, ', ' ) . '</p>';
			}
			if ( $description || $duedate || $location ) {
			    $html .= '</div>';
			}
			if ( $options['public_facing_volunteering_enabled'] ) {
			    $html .= $this->show_all_volunteers( $post_id );
			    $html .= $this->volunteer_form( $post_id );
		    }
			$html .= "</div>";
			
		}
		
		$the_content = str_replace($template_tag, $html, $the_content);
		
		// Reset the $post object to its original state
		$post = $parent_post;
		
        return $the_content;
	}
	
	/**
	 * Prepend voting functionality to the beginning of a post's content
	 */ 
	function prepend_voting_to_post( $the_content ) {
		global $post, $assignment_desk;
		
		if ( is_single() && $post->post_status != 'publish' ) {
			$the_content = $this->voting_form() . $the_content;
			$the_content = $this->show_all_votes() . $the_content;
		}
		
		return $the_content;
		
	}
	
	/**
	 * Appending volunteering functionality to the ending of a post's content
	 */
	function append_volunteering_to_post( $the_content ) {
		global $post, $assignment_desk;
		
		if ( is_single() && $post->post_status != 'publish'  ) {
			$the_content .= $this->show_all_volunteers( $post->ID );
			$the_content .= $this->volunteer_form( $post->ID );
		}
		
		return $the_content;		
	}
	
	
} // END:class ad_public_controller

} // END:if(!class_exists('ad_public_controller'))
?>