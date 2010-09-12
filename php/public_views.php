<?php
if(!class_exists('ad_public_controller')){

require_once('utils.php');
	
class ad_public_views {
	
	function __construct() { 

	}
	
	/**
	 * We need to make sure all of this is established every time the plugin is loaded 
	 */
	function init() {
		global $assignment_desk;
		$pitch_form_options = $assignment_desk->pitch_form_options;		
		$public_facing_options = $assignment_desk->public_facing_options;
		
		// The datepicker UI is used on the pitch submission form. Only load if enabled
		if ( $pitch_form_options['pitch_form_enabled'] ) {
			wp_enqueue_script('jquery-datepicker-js', ASSIGNMENT_DESK_URL .'js/jquery.datepicker.js', array('jquery-ui-core'));
		}
		wp_enqueue_script('ad-public-views', ASSIGNMENT_DESK_URL . 'js/public_views.js', array('jquery', 'jquery-datepicker-js'));
		
		wp_enqueue_style('ad-public', ASSIGNMENT_DESK_URL . 'css/public.css');
		
		// Run save_pitch_form() at WordPress initialization
		$_REQUEST['assignment_desk_messages']['pitch_form'] = $this->save_pitch_form();
		$_REQUEST['assignment_desk_messages']['volunteer_form'] = $this->save_volunteer_form();
		$this->save_voting_form();
		
		add_filter( 'the_content', array(&$this, 'show_all_posts') );
		
		add_filter( 'the_posts', array(&$this, 'show_single_post') );
		add_filter( 'the_content', array(&$this, 'handle_single_post_metadata') );		
		
		// Only add voting if its enabled
		if ( $public_facing_options['public_facing_voting_enabled'] ) {
			add_filter( 'the_content', array(&$this, 'prepend_voting_to_post') );		
		}
		// Only add commenting if its enabled
		add_filter( 'comments_open', array(&$this, 'enable_disable_commenting') );
		if ( $public_facing_options['public_facing_commenting_enabled'] ) {
			add_action( 'comment_on_draft', array(&$this, 'handle_comment_post'), 1 );
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
	
	/**
	 * Initialize first use of the plugin with default settings
	 * @todo Finish this method
	 */
	function activate_once() {
		
	}
	
	/**
	 * Show the pitch form on post or pages with template tag if enabled
	 */
	function show_pitch_form( $the_content ) {
		global $assignment_desk, $current_user;

		$options = $assignment_desk->pitch_form_options;		
		
		if ( $assignment_desk->edit_flow_exists() ) {
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

		// Only show the pitch form if the User is logged in
		if ( is_user_logged_in() ) {
            
			$pitch_form = '';

			
			// Messages to the User appear at the top of the form

			if ( $_REQUEST['assignment_desk_messages']['pitch_form']['success'] ) {
				$pitch_form .= '<div class="message success"><p>Pitch submitted successfully.</p></div>';
			} else if ( count($_REQUEST['assignment_desk_messages']['pitch_form']['errors']) ) {
				$pitch_form .= '<div class="message error"><p>Please correct the error(s) below.</p></div>';
			}

			if ( $_REQUEST['assignment_desk_messages']['pitch_form']['errors']['secret'] ) {
                $pitch_form .= '<p class="error">'
							. $_REQUEST['assignment_desk_messages']['pitch_form']['errors']['secret']
							. '</p>';
            }
		
			/**
			 * For all of the fields, the admin has the ability to define a label and a description
			 * in the settings. If those aren't defined, then the stock label will show with no description
			 */
		
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
			
			// Edit Flow Description
			if ( $options['pitch_form_description_enabled'] && $assignment_desk->edit_flow_exists() ) {
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
			
			// Edit Flow Due Date
			if ( $options['pitch_form_duedate_enabled'] && $assignment_desk->edit_flow_exists() ) {
				if ( $options['pitch_form_duedate_label'] ) {
					$duedate_label = $options['pitch_form_duedate_label'];
				} else {
					$duedate_label = 'Due Date';
				}	
				$pitch_form .= '<fieldset class="standard"><label for="assignment_desk_duedate">' . $duedate_label . '</label>';
				$pitch_form .= '<input type="text" size="12" name="assignment_desk_duedate" id="assignment_desk_duedate">';
				if ( $options['pitch_form_duedate_description'] ) {
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
				if ( $options['pitch_form_categories_label'] ) {
					$category_label = $options['pitch_form_categories_label'];
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
			
			// Edit Flow Location
			if ( $options['pitch_form_location_enabled'] && $assignment_desk->edit_flow_exists() ) {
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
			
			// Author information and submit
			$pitch_form .= '<fieldset class="submit">'
						. '<input type="hidden" id="assignment_desk_author" name="assignment_desk_author" value="' . $current_user->ID . '" />';
						
			// Set a random one-time token in the form to prevent duplicate submissions.
			$_SESSION['ASSIGNMENT_PITCH_FORM_SECRET'] = md5(uniqid(rand(), true));
			$pitch_form .= "<input type='hidden' name='assignment_pitch_form_secret' id='assignment_pitch_form_secret' value='{$_SESSION['ASSIGNMENT_PITCH_FORM_SECRET']}' />";				
						
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
	
	/**
	 * Saves data after a User submits a pitch form
	 */
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

		if ( $_POST && isset($_POST['assignment_desk_pitch_submit']) ) {
		    $form_messages = array();

			// Check to see whether this is the second time the form has been submitted
		    $form_secret = $_POST['assignment_pitch_form_secret'];			
			session_start();
            if ( !isset($_SESSION['ASSIGNMENT_PITCH_FORM_SECRET']) || 
                 strcasecmp($form_secret, $_SESSION['ASSIGNMENT_PITCH_FORM_SECRET']) != 0 ) {
                $form_messages['errors']['secret'] = __('Did you just refresh the browser?');
                return $form_messages;
            }

			// Ensure that it was the user who submitted the form, not a bot
			if ( !wp_verify_nonce($_POST['assignment_desk_pitch_nonce'], 'assignment_desk_pitch') ) {
				return $form_messages['error']['nonce'];
			}

			$sanitized_title = strip_tags($_POST['assignment_desk_title']);
			if ( !$sanitized_title ) {
				$form_messages['errors']['title'] = 'Please add a title to this pitch.';
			}
			$sanitized_author = (int)$_POST['assignment_desk_author'];
			$sanitized_description = wp_kses($_POST['assignment_desk_description'], $allowedposttags);
			$sanitized_tags = $_POST['assignment_desk_tags'];
			$sanitized_categories = (int)$_POST['assignment_desk_categories'];
			$sanitized_location = wp_kses($_POST['assignment_desk_location'], $allowedposttags);
			$sanitized_volunteer = $_POST['assignment_desk_volunteer'];
			
			if ( $_POST['assignment_desk_duedate'] ) {
    			// Sanitize the duedate
    			$sanitized_duedate = false;
    			$duedate_split = split('/', $_POST['assignment_desk_duedate']);
    			if ( count($duedate_split) == 3) {
    			    $duedate_month = (int)$duedate_split[0];
    			    $duedate_day = (int)$duedate_split[1];
    			    $duedate_year = (int)$duedate_split[2];
    			    // Zero pad for strtime
    			    if ( $duedate_month < 10 ) {
    			        $duedate_month = "0$duedate_month";
    			    }
    			    $sanitized_duedate = strtotime($duedate_day . '-' . $duedate_month . '-' . $duedate_year);
    			    if ( !$sanitized_duedate ) {
    			        $form_messages['errors']['duedate'] = 'Please enter a valid date of the form MM/DD/YYYY';
    			    }
    			}
    			else {
    			    $form_messages['errors']['duedate'] = 'Please enter a valid date of the form MM/DD/YYYY';
    			}
			}
			
			// Don't process the form if any errors have been set
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
				
				// Only handle description if Edit Flow exists
				if ( $assignment_desk->edit_flow_exists() ) {
					update_post_meta($post_id, '_ef_description', $sanitized_description);
				}
				
				// Only handle duedate if Edit Flow exists
				if ( $sanitized_duedate && $assignment_desk->edit_flow_exists() ) {
    				update_post_meta($post_id, '_ef_duedate', $sanitized_duedate);
				}
				
				// Only handle location if Edit Flow exists
				if ( $assignment_desk->edit_flow_exists() ) {
					update_post_meta($post_id, '_ef_location', $sanitized_location);
				}
				
				// Save pitched_by_participant and pitched_by_date information
				update_post_meta($post_id, '_ad_pitched_by_participant', $sanitized_author);
				update_post_meta($post_id, '_ad_pitched_by_timestamp', date_i18n('U'));
				
				// Set assignment status to default setting
				$default_status = $assignment_desk->custom_taxonomies->get_default_assignment_status();
				wp_set_object_terms($post_id, (int)$default_status->term_id, $assignment_desk->custom_taxonomies->assignment_status_label);
				
				// All User Types can participate in a new assignment by default
				foreach ( $user_types as $user_type ) {
					update_post_meta($post_id, "_ad_participant_type_$user_type->term_id", 'on');
				}
				
				// Record any roles a User has volunteered for
				if ( $sanitized_volunteer ) {
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
	 * @param int $post_id The Post ID
	 * @return string the voting form HTML.
	 * @todo Functionality to remove a vote
	 */
	function voting_form( $post_id = null ) {
		global $assignment_desk, $current_user;
		$options = $assignment_desk->public_facing_options;
		
		if ( !$post_id ) {
			global $post;
			$post_id = $post->ID;
		}
		
		// Only show the voting form to logged-in users
		if ( is_user_logged_in() ) {
			
			wp_get_current_user();
			$all_votes = get_post_meta( $post_id, '_ad_votes_all', true );
			$total_votes = (int)get_post_meta( $post_id, '_ad_votes_total', true );
						
			if ( !is_array($all_votes) ){
			    $all_votes = array();
			}
			
			$user_id = $current_user->ID;
			
			/**
			 * Only show the vote button if the user hasn't voted before
			 * Text display is user-configurable but defaults to 'vote'
			 */
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
			}
			
		}
		
	}
	
	/**
	 * Display the number of votes and avatars for the users who have voted on the item.
	 * @param int $post_id The Post ID
 	 * @return string the voting results in HTML.
	 */
	function show_all_votes( $post_id = null ) {
		global $assignment_desk, $current_user;
		
		if ( !$post_id ) {
			global $post;
			$post_id = $post->ID;
		}
		
		$all_votes = get_post_meta( $post_id, '_ad_votes_all', true );
		if ( !$all_votes ) {
		    $all_votes = array();
		}
		$total_votes = (int)get_post_meta( $post_id, '_ad_votes_total', true );
		
		if ( !$total_votes ) {
			$total_votes = 0;
			$votes_html = '<div class="ad_all_votes"><span class="ad_total_votes">' . $total_votes . '</span></div>';
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
	
	/**
	 * Save the voting form when submitted by the User
	 * @return array messages indicating results.
	 */
	function save_voting_form() {
		global $assignment_desk, $current_user;
	    
		// Only logged-in users have the ability to vote
		if ( $_POST['assignment_desk_voting_submit'] && is_user_logged_in() ) {
			$form_messages = array();
			
			// Ensure that it was the user who submitted the form, not a darn bot
			if ( !wp_verify_nonce($_POST['assignment_desk_voting_nonce'], 'assignment_desk_voting') ) {
				return $form_messages['error']['nonce'] = true;
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
			
			// Catch if $all_votes has been set yet
			if ( !is_array($all_votes) ){
			    $all_votes = array();
			}
			
			// Check whether the user has voted on this post yet. Users can only vote once
			if ( !in_array( $sanitized_user_id, $all_votes ) ) {
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
	 * @param int $post_id The Post ID
 	 * @return string the volunteer form HTML
	 * @todo Better message for logged-out users
	 */
	function volunteer_form( $post_id = null ) {
	    global $assignment_desk, $current_user;
		$pitch_form_options = $assignment_desk->pitch_form_options;
	
		if ( !$post_id ) {
			global $post;
			$post_id = $post->ID;
		}
	
		// Only logged-in users can volunteer on assignments
		if ( is_user_logged_in() ) {
	
			wp_get_current_user();
		    $user_roles = $assignment_desk->custom_taxonomies->get_user_roles();
	
			// See whether the user has already volunteered for the story
			$existing_roles = get_post_meta( $post_id, "_ad_participant_$current_user->ID", true );
			if ( !$existing_roles ) {
		        $existing_roles = array();  
			}
			
			$current_user_type = (int)get_usermeta( $current_user->ID, 'ad_user_type' );
			// Do not equal negative if someone created a new user type on us that
			// hasn't been saved in association with the post
			if ( get_post_meta( $post_id, "_ad_participant_type_$current_user_type" , true ) == 'off' ) {
				return false;
			}
	
		    $volunteer_form = '<a name="assignment_desk_volunteer_form"></a>';
		    $volunteer_form .= '<form method="post" class="assignment_desk_volunteer_form">';
			$volunteer_form .= '<fieldset class="standard">';
			if ( $pitch_form_options['pitch_form_volunteer_label'] ) {
				$volunteer_label = $pitch_form_options['pitch_form_volunteer_label'];
			} else {
				$volunteer_label = 'Volunteer';
			}
			$volunteer_form .= '<label for="assignment_desk_volunteer">' . $volunteer_label . '</label>';
			$volunteer_form .= '<ul class="assignment_desk_volunteer">';
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
			$volunteer_form .= '</ul>';
			if ( $pitch_form_options['pitch_form_volunteer_description'] ) {
			$pitch_form .= '<p class="description">'
						. $pitch_form_options['pitch_form_volunteer_description']
						. '</p>';
			}
			$volunteer_form .= '</fieldset>';
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
  	 * @return string the volunteer display HTML
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
	    global $assignment_desk, $current_user, $wpdb;
	    
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
		    foreach( $sanitized_roles as $maybe_role ){
		        $maybe_role = (int)$maybe_role;
		        foreach ( $user_roles as $role ){
		            if ( $maybe_role == $role->term_id ) {
						$valid_roles[] = $maybe_role;
					}
		        }
	        }
	
			foreach ( $user_roles as $user_role ) {
			    // Get previous roles, 
				$previous_values = get_post_meta($post_id, '_ad_participant_role_' . $user_role->term_id, true);
				// New participant is a volunteer
				if ( in_array( $user_role->term_id, $valid_roles ) && !isset( $previous_values[$current_user->ID] ) ) {
					$previous_values[$current_user->ID] = 'volunteered';
					update_usermeta($current_user->ID, '_ad_volunteer', $post_id);
				} 
				// Invalid role suibmitted by a volunteer?
				else if ( !in_array( $user_role->term_id, $valid_roles ) && $previous_values[$current_user->ID] == 'volunteered' ) {
					unset($previous_values[$current_user->ID]);
				}
				$new_values = $previous_values;
				update_post_meta($post_id, '_ad_participant_role_' . $user_role->term_id, $new_values);
			}
			// Save the roles associated with the user id as well
			update_post_meta( $post_id, "_ad_participant_$sanitized_user_id", $valid_roles );
			// Update the count of total volunteers
			$volunteers = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key='_ad_volunteer' AND meta_value=$post_id");
    		update_post_meta($post_id, '_ad_total_volunteers', $volunteers);
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
	
	/**
	 * Get all of the CSS classes we might want on a pitch
	 *
	 * @param $post_id int The post ID
	 * @todo Class for has votes
	 * @todo Class for has volunteers
	 * @todo Class for has comments
	 * @return $classes array All of the classes to include in the HTML
	 */
	function get_css_classes_for_pitch( $post_id = null ) {
		global $assignment_desk;
		$public_facing_options = $assignment_desk->public_facing_options;
		
		$classes = array();
		
		if ( !$post_id ) {
			global $post;
			$post_id = $post->ID;
		}
		
		$classes[] = 'assignment-desk-post-status-' . get_post_status( $post_id );
		
		if ( $public_facing_options['public_facing_voting_enabled'] ) {
			$classes[] = 'assigment-desk-voting-enabled';			
		}
		if ( $public_facing_options['public_facing_commenting_enabled'] ) {
			$classes[] = 'assigment-desk-commenting-enabled';
		}
		if ( $public_facing_options['public_facing_volunteering_enabled'] ) {
			$classes[] = 'assigment-desk-volunteering-enabled';
		}
		
		return $classes;
	}
	
	/*
	* Replace an html comment <!--assignment-desk-all-posts--> with ad public pages.
	*/
	function show_all_posts( $the_content ) {
		global $wpdb, $assignment_desk, $post, $edit_flow, $current_user;
		wp_get_current_user();
		$options = $assignment_desk->public_facing_options;
	  
		$template_tag = '<!--' . $assignment_desk->all_posts_key . '-->';
		
		if ( !strpos( $the_content, $template_tag ) ) {
			return $the_content;
		}
		
		// Save the parent post so we can reset the object later
		$parent_post = $post;		
		
		$html = '<div class="assignment-desk assignment-desk-all-pitches">';
		$action_links = '';
		
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
		
		if ( $options['public_facing_filtering_post_status_enabled'] || $options['public_facing_filtering_participant_type_enabled'] ) {
			$html .= '<span class="left">';
		}
		
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
		
		if ( $options['public_facing_filtering_post_status_enabled'] || $options['public_facing_filtering_participant_type_enabled'] ) {
			$html .= '<input type="submit" name="assignment-desk-filter-button" class="assignment-desk-filter-button" value="Filter" />';
			$html .= '</span>';
		}
		
		// Sorting functionality is optional and configured by the admin
		if ( $options['public_facing_filtering_sort_by_enabled'] ) {
			$html .= '<span class="right">';			
			$html .= '<select name="sort_by" class="assignment-desk-sort-by">'
				. '<option value="post_date"';
			if ( $sort_by == 'post_date' ) {
				$html .= ' selected="selected"';
			}
			$html .= '>Post date</option>';
			// Only show the sort by rank option if voting is enabled
			if ( $options['public_facing_voting_enabled'] ) {
				$html .= '<option value="ranking"';
				if ( $sort_by == 'ranking' ) {
					$html .= ' selected="selected"';
				}
				$html .= '>Ranking</option>';
			}
			// Only show the due_date option if Edit Flow exists and display of data is 'on'
			if ( $assignment_desk->edit_flow_exists() && $options['public_facing_duedate_enabled'] ) {
				$html .= '<option value="due_date"';
				if ( $sort_by == 'due_date' ) {
					$html .= ' selected="selected"';
				}
				$html .= '>Due date</option>';
			}
			
			$html .= '</select>';
			$html .= '<input type="submit" name="assignment-desk-sort-button" class="assignment-desk-sort-button" value="Sort" />';
			$html .= '</span>';	
		}
		$html .= '</form>';
			
 		if ( is_array($all_pitches) ) {
		
			foreach ( $all_pitches as $pitch ) {
			
				$post_id = $pitch->ID;
				$description = get_post_meta( $post_id, '_ef_description', true );
				$location = get_post_meta( $post_id, '_ef_location', true );
				$duedate = get_post_meta( $post_id, '_ef_duedate', true );
				$duedate = date_i18n( 'M d, Y', $duedate );
				$assignment_status = wp_get_object_terms($post_id, $assignment_desk->custom_taxonomies->assignment_status_label);
				if ( is_array($assignment_status) ) {
				    $assignment_status = $assignment_status[0];
				}
				
				$css_classes = $this->get_css_classes_for_pitch( $post_id );
				
			
				$html .= '<div class="assignment-desk-pitch';
				if ( $css_classes ) {
					$html .= ' ' . implode( ' ', $css_classes );
				}
				$html .= '"><h3><a href="' . get_permalink( $post_id ) . '">' . get_the_title( $post_id ) . '</a></h3>';
				// Only show voting if it's enabled
				if ( $options['public_facing_voting_enabled'] ) {
					$html .= $this->show_all_votes( $post_id );					
					$html .= $this->voting_form( $post_id );
				}
			
				if ( $options['public_facing_content_enabled'] && $pitch->post_content ) {
					// @todo This method doesn't work
					$html .= '<p>' . $pitch->post_content . '</p>';
				}
			
				if ( $description || $duedate || $location ) {
				    $html .= '<div class="meta">';
				}
				if ( $options['public_facing_assignment_status_enabled'] && $assignment_status ) {
				    $html .= '<p><label>Status:</label> ' . $assignment_status->name . '</p>';
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
					if ( $tags ) {
					    foreach ( $tags as $tag ) {
						    $tags_html .= '<a href="' . get_tag_link($tag->term_id) . '">' . $tag->name . '</a>, ';
					    }
				    }
					$html .= '<p><label>Tags:</label> ' . rtrim( $tags_html, ', ' ) . '</p>';
				}
				if ( $description || $duedate || $location ) {
				    $html .= '</div>';
				}
				if ( $options['public_facing_volunteering_enabled'] ) {
				    $html .= $this->show_all_volunteers( $post_id );
					$action_links .= '<a href="' . get_permalink( $post_id ) . '#assignment_desk_volunteer_form">Volunteer</a> | ';
			    }
				if ( $options['public_facing_commenting_enabled'] ) {
					$action_links .= '<a href="' . get_permalink( $post_id ) . '#respond">Comment</a> |';
				}
				if ( $options['public_facing_volunteering_enabled'] || $options['public_facing_commenting_enabled'] ) {
					$html .= '<div class="links">';
					$html .= rtrim( $action_links, ' |' );
					$html .= '</div>';					
				}
				$html .= "</div>";
			
			} // END foreach
			
		} else {
			if ( !$options['public_facing_no_pitches_message'] ) {
				$no_pitches_message = $options['public_facing_no_pitches_message'];
			} else {
				$no_pitches_message = 'Sorry, there are currently no pitches listed.';
			}
			$html .= '<div class="message alert">' . $no_pitches_message . '</div>';
		}
		
		$html .= '</div>';		
		
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
	 * Append metadata to the single post content if enabled.
	 */
	function handle_single_post_metadata( $the_content ) {
		global $post, $assignment_desk;
		$options = $assignment_desk->public_facing_options;
		$post_id = $post->ID;
		
		$new_content = '';
		if ( is_single() && $post->post_status != 'publish' ) {
			
			$description = get_post_meta( $post_id, '_ef_description', true );
			$location = get_post_meta( $post_id, '_ef_location', true );
			$duedate = get_post_meta( $post_id, '_ef_duedate', true );
			$duedate = date_i18n( 'M d, Y', $duedate );
			
			if ( $options['public_facing_content_enabled'] ) {
				$new_content .= $the_content;
			}
			
			if ( $description || $duedate || $location ) {
			    $new_content .= '<div class="meta">';
			}
			if ( $options['public_facing_description_enabled'] && $description ) {
			    $new_content .= '<p><label>Description:</label> ' . $description . '</p>';
			}
			if ( $options['public_facing_duedate_enabled'] && $duedate ) {
			    $new_content .= '<p><label>Due date:</label> ' . $duedate . '</p>';	
			}
			if ( $options['public_facing_location_enabled'] && $location ) {
			    $new_content .= '<p><label>Location:</label> ' . $location . '</p>';	
			}
			if ( $description || $duedate || $location ) {
			    $new_content .= '</div>';
			}
			
		} else {
			$new_content = $the_content;
		}
		return $new_content;
		
	}
	
	/**
	 * Appending volunteering functionality to the ending of a post's content
	 */
	function append_volunteering_to_post( $the_content ) {
		global $post, $assignment_desk, $current_user;
		wp_get_current_user();
		
		if ( is_single() && $post->post_status != 'publish' ) {
			$the_content .= $this->show_all_volunteers( $post->ID );
			
			$current_user_type = (int)get_usermeta( $current_user->ID, 'ad_user_type' );
			// Do not equal negative if someone created a new user type on us that
			// hasn't been saved in association with the post
			if ( get_post_meta( $post->ID, "_ad_participant_type_$current_user_type" , true ) != 'off' ) {
				$the_content .= $this->volunteer_form( $post->ID );
			}
		}
		
		return $the_content;		
	}
	
	/**
	 * Enable or disable public commenting on pitches based on preferences
	 */
	function enable_disable_commenting( $status ) {
		global $assignment_desk, $post;
		$public_facing_options = $assignment_desk->public_facing_options;
		
		// Only alter commenting preferences on single posts that are unpublished
		if ( is_single() && $post->post_status != 'publish' ) {		
			if ( $public_facing_options['public_facing_commenting_enabled'] ) {
				return true;
			} else {
				return false;
			}
		} else {
			return $status;
		}
		
	}
	
	/**
	 * Handle a comment being posted to the system
	 * Ugly hack to get around limitation on commenting on unpublished content
	 * Code copy and pasted from WordPress 2.9.2
	 */
	function handle_comment_post( $comment_post_id ) {
		require( ABSPATH . '/wp-load.php' );		
		global $current_user, $wpdb;
		
		$comment_author       = ( isset($_POST['author']) )  ? trim(strip_tags($_POST['author'])) : null;
		$comment_author_email = ( isset($_POST['email']) )   ? trim($_POST['email']) : null;
		$comment_author_url   = ( isset($_POST['url']) )     ? trim($_POST['url']) : null;
		$comment_content      = ( isset($_POST['comment']) ) ? trim($_POST['comment']) : null;

		// If the user is logged in
		$current_user = wp_get_current_user();
		if ( $current_user->ID ) {
			if ( empty( $current_user->display_name ) )
				$current_user->display_name = $current_user->user_login;
			$comment_author       = $wpdb->escape($current_user->display_name);
			$comment_author_email = $wpdb->escape($current_user->user_email);
			$comment_author_url   = $wpdb->escape($current_user->user_url);
			if ( current_user_can('unfiltered_html') ) {
				if ( wp_create_nonce('unfiltered-html-comment_' . $comment_post_ID) != $_POST['_wp_unfiltered_html_comment'] ) {
					kses_remove_filters(); // start with a clean slate
					kses_init_filters(); // set up the filters
				}
			}
		} else {
			if ( get_option('comment_registration') || 'private' == $status->post_status )
				wp_die( __('Sorry, you must be logged in to post a comment.') );
		}

		$comment_type = '';

		if ( get_option('require_name_email') && !$user->ID ) {
			if ( 6 > strlen($comment_author_email) || '' == $comment_author )
				wp_die( __('Error: please fill the required fields (name, email).') );
			elseif ( !is_email($comment_author_email))
				wp_die( __('Error: please enter a valid email address.') );
		}

		if ( '' == $comment_content )
			wp_die( __('Error: please type a comment.') );

		$comment_parent = isset($_POST['comment_parent']) ? absint($_POST['comment_parent']) : 0;

		$commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID');
	
		$comment_id = wp_new_comment( $commentdata );
		
		// The proper comment_post_id isn't set for some reason or another, so we have to update it here
		$query = $wpdb->prepare( "UPDATE $wpdb->comments SET comment_post_id = %d WHERE comment_id = $comment_id", array($comment_post_id) );
		$wpdb->query( $query );

		$comment = get_comment($comment_id);
		if ( !$user->ID ) {
			$comment_cookie_lifetime = apply_filters('comment_cookie_lifetime', 30000000);
			setcookie('comment_author_' . COOKIEHASH, $comment->comment_author, time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN);
			setcookie('comment_author_email_' . COOKIEHASH, $comment->comment_author_email, time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN);
			setcookie('comment_author_url_' . COOKIEHASH, esc_url($comment->comment_author_url), time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN);
		}

		$location = get_permalink( $comment_post_id ) . '#comment-' . $comment_id;
		$location = apply_filters('comment_post_redirect', $location, $comment);

		wp_redirect($location);
		
	}
	
} // END:class ad_public_controller

} // END:if(!class_exists('ad_public_controller'))
?>