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
		if ( isset( $pitch_form_options['pitch_form_enabled'] ) && $pitch_form_options['pitch_form_enabled'] ) {
			wp_enqueue_script( 'jquery-datepicker-js', ASSIGNMENT_DESK_URL .'js/jquery.datepicker.js', array('jquery-ui-core'), ASSIGMENT_DESK_VERSION, true );
			wp_enqueue_script( 'ad-public-views', ASSIGNMENT_DESK_URL . 'js/public_views.js', array('jquery', 'jquery-datepicker-js' ), ASSIGMENT_DESK_VERSION, true );
		}
		
		wp_enqueue_style('ad-public', ASSIGNMENT_DESK_URL . 'css/public.css');		
		
		add_filter( 'the_content', array( &$this, 'show_all_posts' ) );
		add_filter( 'the_posts', array( &$this, 'show_single_post' ) );
		add_filter( 'the_content', array( &$this, 'handle_single_post_metadata' ) );		
		
		add_action(	'parse_request', array( &$this, 'process_form_submissions' ) );
		
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
			add_filter( 'the_content', array(&$this, 'append_actions_to_post') );		
		}
		// Only show pitch forms if the functionality is enabled
		if ( isset( $pitch_form_options['pitch_form_enabled'] ) && $pitch_form_options['pitch_form_enabled'] ) {
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
	 * Process any form saves
	 */
	function process_form_submissions() {
		global $assignment_desk;
		$pitch_form_options = $assignment_desk->pitch_form_options;		
		$public_facing_options = $assignment_desk->public_facing_options;
				
		// Only process voting if its enabled
		if ( $public_facing_options['public_facing_voting_enabled'] ) {
            $this->save_voting_form();
		}
		// Only process volunteering if its enabled
		if ( $public_facing_options['public_facing_volunteering_enabled'] ) {
		    $_REQUEST['assignment_desk_messages']['volunteer_form'] = $this->save_volunteer_form();	
		}
		// Only process pitch forms if the functionality is enabled
		if (isset( $pitch_form_options['pitch_form_enabled'] ) && $pitch_form_options['pitch_form_enabled'] ) {
		    $_REQUEST['assignment_desk_messages']['pitch_form'] = $this->save_pitch_form();
		}
	}
	
	/**
	 * Helper function which returns a value if the variable is set
	 */
	function return_if_set( $array = array(), $key = null ) {
		if ( isset( $array[$key] ) ) {
			return $array[$key];
		} else {
			return null;
		}
	}
	
	/**
	 * Show the pitch form on post or pages with template tag if enabled
	 */
	function show_pitch_form( $the_content ) {
		global $assignment_desk, $post;

		$options = $assignment_desk->pitch_form_options;		
		
		if ( $assignment_desk->edit_flow_enabled() ) {
			global $edit_flow;
		}
	
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
            
		$pitch_form = '';

		// Messages to the User appear at the top of the form

		if ( isset( $_GET['success'] ) ) {
			if ( $options['pitch_form_success_message'] ) {
		        $search = array(
						'%title%',
					);
				$post_id = $_GET['post_id'];
                $replace = array(
						get_the_title( $post_id ),
					);
                $success_message = str_replace( $search, $replace, $options['pitch_form_success_message'] );
			} else {
				$success_message = _('Pitch submitted successfully. Thanks!');
			}
			$pitch_form .= '<div class="message success">' . $success_message . '</div>';
		} else if ( count($_REQUEST['assignment_desk_messages']['pitch_form']['errors']) ) {
			$pitch_form .= '<div class="message error">Please correct the error(s) below.</div>';
		}

		if ( isset($_REQUEST['assignment_desk_messages']['pitch_form']['errors']['secret']) ) {
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
		if ( isset( $options['pitch_form_title_label'] ) && $options['pitch_form_title_label'] ) {
			$title_label = $options['pitch_form_title_label'];
		} else {
			$title_label = 'Title';
		}
		$pitch_form .= '<fieldset class="standard"><label for="assignment_desk_title">' . $title_label . '</label>'
					. '<input type="text" id="assignment_desk_title" name="assignment_desk_title" ';
		$pitch_form .= 'value="' . $this->return_if_set( $_POST, 'assignment_desk_title' ) . '"/>';
		if ( $options['pitch_form_title_description'] ) {
		$pitch_form .= '<p class="description">'
					. $options['pitch_form_title_description']
					. '</p>';
		}
		if ( isset($_REQUEST['assignment_desk_messages']['pitch_form']['errors']['title']) ) {
			$pitch_form .= '<p class="error">'
						. $_REQUEST['assignment_desk_messages']['pitch_form']['errors']['title']
						. '</p>';
		}
		$pitch_form	.= '</fieldset>';
		
		
		if ( $assignment_desk->edit_flow_enabled() ) {
		
			// Edit Flow v0.6 and higher offers custom editorial metadata. Otherwise, fall back on old
			if ( version_compare( EDIT_FLOW_VERSION, '0.6', '>=' ) ) {
				
				// Build pitch form with custom editorial metadata
				$editorial_metadata = $edit_flow->editorial_metadata->get_editorial_metadata_terms();
				foreach ( $editorial_metadata as $term ) {
					$form_key = $edit_flow->editorial_metadata->get_postmeta_key( $term );
					$enabled_key = 'pitch_form_' . $term->slug . '_enabled';
					$label_key = 'pitch_form_' . $term->slug . '_label';	
					$description_key = 'pitch_form_' . $term->slug . '_description';
					$required_key = 'pitch_form_' . $term->slug . '_required';
					
					// Only show the field if it's enabled
					if ( isset( $options[$enabled_key] ) && $options[$enabled_key] ) {
						
						// Build the label and description field
						$html_label = ( $options[$label_key] ) ? $options[$label_key] : $term->name;
						$html_description = ( $options[$description_key] ) ? $options[$description_key] : '';						
						$html_input = '';
						
						// Give us different inputs based on the metadata type
						switch ( $term_type = $edit_flow->editorial_metadata->get_metadata_type( $term ) ) {
							case 'checkbox':
								$html_input = '<input type="checkbox" id="' . $form_key . '" name="' . $form_key . '" ';
								if ( $this->return_if_set( $_POST, $form_key ) ) $html_input = ' checked="checked"';
								$html_input .= ' />';
								break;
							case 'date':
								$html_input = '<input type="text" id="' . $form_key . '" name="' . $form_key . '" ';
								$html_input .= 'value="' . $this->return_if_set( $_POST, $form_key ) . '" ';
								$html_input .= ' class="ad_datepicker" size="12" />';
								break;
							case 'location':
								$html_input = '<input type="text" id="' . $form_key . '" name="' . $form_key . '" ';
								$html_input .= 'value="' . $this->return_if_set( $_POST, $form_key ) . '"/>';
								break;
							case 'paragraph':
								$html_input = '<textarea id="' . $form_key . '" name="' . $form_key . '">';
								$html_input .= $this->return_if_set( $_POST, $form_key );
								$html_input .= '</textarea>';
								break;
							case 'text':
								$html_input = '<input type="text" id="' . $form_key . '" name="' . $form_key . '" ';
								$html_input .= 'value="' . $this->return_if_set( $_POST, $form_key ) . '"/>';
								break;
							case 'user':
								$selected = ( $this->return_if_set( $_POST, $form_key ) ) ? $this->return_if_set( $_POST, $form_key ) : false;
								$user_dropdown_args = array( 
										'show_option_all' => __( '- Select user -', 'assignment-desk' ), 
										'name'     => $form_key,
										'selected' => $selected,
										'echo' 	=> 0,
									); 
								$html_input = wp_dropdown_users( $user_dropdown_args );
								break;
							default:
								$html_input = '';
								break;
						}
						
						
						if ( $html_input ) {
							$pitch_form .= '<fieldset class="standard"><label for="' . $form_key . '">' . $html_label . '</label>';
							$pitch_form .= $html_input;
							if ( $html_description ) {
							$pitch_form .= '<p class="description">'
										. $html_description
										. '</p>';
							}
							if ( isset( $_REQUEST['assignment_desk_messages']['pitch_form']['errors'][$form_key] ) ) {
				    			$pitch_form .= '<p class="error">'
				    						. $_REQUEST['assignment_desk_messages']['pitch_form']['errors'][$form_key]
				    						. '</p>';
				    		}
							$pitch_form .= '</fieldset>';
						}
						
					}
					
				}
		
			} else {
		
				// Description
				if ( isset( $options['pitch_form_description_enabled'] ) && $options['pitch_form_description_enabled'] ) {
					if ( $options['pitch_form_description_label'] ) {
						$description_label = $options['pitch_form_description_label'];
					} else {
						$description_label = 'Description';
					}
					$pitch_form .= '<fieldset class="standard"><label for="assignment_desk_description">' . $description_label . '</label>'
								. '<textarea id="assignment_desk_description"'
								. ' name="assignment_desk_description">';
					$pitch_form .= $this->return_if_set( $_POST, 'assignment_desk_description' );
					$pitch_form .= '</textarea>';
					if ( $options['pitch_form_description_description'] ) {
					$pitch_form .= '<p class="description">'
								. $options['pitch_form_description_description']
								. '</p>';
					}
			
					if ( isset($_REQUEST['assignment_desk_messages']['pitch_form']['errors']['description']) ) {
		    			$pitch_form .= '<p class="error">'
		    						. $_REQUEST['assignment_desk_messages']['pitch_form']['errors']['description']
		    						. '</p>';
		    		}
    		
					$pitch_form .= '</fieldset>';
				}
		
				// Due date
				if ( $options['pitch_form_duedate_enabled'] ) {
					if ( $options['pitch_form_duedate_label'] ) {
						$duedate_label = $options['pitch_form_duedate_label'];
					} else {
						$duedate_label = 'Due Date';
					}	
					$pitch_form .= '<fieldset class="standard"><label for="assignment_desk_duedate">' . $duedate_label . '</label>';
					$pitch_form .= '<input type="text" size="12" name="assignment_desk_duedate" id="assignment_desk_duedate" ';
					$pitch_form .= 'value="' . $this->return_if_set( $_POST, 'assignment_desk_duedate' ) . '" class="ad_datepicker"/>';
					if ( $options['pitch_form_duedate_description'] ) {
					    $pitch_form .= '<p class="description">' . $options['pitch_form_dudedate_description'] . '</p>';
					}
					if ( isset($_REQUEST['assignment_desk_messages']['pitch_form']['errors']['duedate']) ) {
		   				$pitch_form .= '<p class="error">'
		   							. $_REQUEST['assignment_desk_messages']['pitch_form']['errors']['duedate']
		   							. '</p>';
		   			}
					$pitch_form .= '</fieldset>';
				}
				
				// Location
				if ( isset( $options['pitch_form_location_enabled'] ) && $options['pitch_form_location_enabled'] ) {
					if ( $options['pitch_form_location_label'] ) {
						$location_label = $options['pitch_form_location_label'];
					} else {
						$location_label = 'Location';
					}
					$pitch_form .= '<fieldset class="standard"><label for="assignment_desk_location">' . $location_label . '</label>'
								. '<input type="text" id="assignment_desk_location" name="assignment_desk_location" ';
					$pitch_form .= 'value="' . $this->return_if_set( $_POST, 'assignment_desk_location' ) . '"/>';
					if ( $options['pitch_form_location_description'] ) {
					$pitch_form .= '<p class="description">'
								. $options['pitch_form_location_description']
								. '</p>';
					}
					if ( isset($_REQUEST['assignment_desk_messages']['pitch_form']['errors']['location']) ) {
		    			$pitch_form .= '<p class="error">'
		    						. $_REQUEST['assignment_desk_messages']['pitch_form']['errors']['location']
		    						. '</p>';
		    		}
					$pitch_form .= '</fieldset>';
				}
				
			} // END - Check if Edit Flow > v0.6
			
		} // END - if ( $assignment_desk->edit_flow_enabled() )
		
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
				$pitch_form .= '<option value="' . $category->term_id . '"';
				if ( $category->term_id == $this->return_if_set( $_POST, 'assignment_desk_categories' ) ) {
					$pitch_form .= ' selected="selected"';
				}
				$pitch_form .= '>'
							. $category->name
							. '</option>';
			}
			$pitch_form .= '</select>';
			if ($options['pitch_form_categories_description']) {
			$pitch_form .= '<p class="description">'
						. $options['pitch_form_categories_description']
						. '</p>';
			}
			if ( isset($_REQUEST['assignment_desk_messages']['pitch_form']['errors']['categories']) ) {
    			$pitch_form .= '<p class="error">'
    						. $_REQUEST['assignment_desk_messages']['pitch_form']['errors']['categories']
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
						. '<input type="text" id="assignment_desk_tags" name="assignment_desk_tags"';
			$pitch_form .= 'value="' . $this->return_if_set( $_POST, 'assignment_desk_tags' ) . '"/>';							
			if ( $options['pitch_form_tags_description'] ) {
			$pitch_form .= '<p class="description">'
						. $options['pitch_form_tags_description']
						. '</p>';
			}
			if ( isset($_REQUEST['assignment_desk_messages']['pitch_form']['errors']['tags']) ) {
    			$pitch_form .= '<p class="error">'
    						. $_REQUEST['assignment_desk_messages']['pitch_form']['errors']['tags']
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
			$pitch_form .= '<fieldset class="list"><label for="assignment_desk_volunteer">' . $volunteer_label . '</label><ul id="assignment_desk_volunteer">';
			foreach ( $user_roles as $user_role ) {
				$pitch_form .= '<li><input type="checkbox" '
							. 'id="assignment_desk_volunteer_' . $user_role->term_id
							. '" name="assignment_desk_volunteer[]"';
				if ( $this->return_if_set( $_POST, 'assignment_desk_volunteer' ) && in_array( $user_role->term_id, $this->return_if_set( $_POST, 'assignment_desk_volunteer' ) ) ) {
					$pitch_form .= ' checked="checked"';
				}
				$pitch_form .= ' value="' . $user_role->term_id . '"'
							. ' /><label for="assignment_desk_volunteer_'
							. $user_role->term_id .'">' . $user_role->name
							. '</label>';
				$pitch_form .= '<span class="description">' . $user_role->description . '</span>';
				$pitch_form .= '</li>';
			}
			$pitch_form .= '</ul>';
			if ( $options['pitch_form_volunteer_description'] ) {
			$pitch_form .= '<p class="description">'
						. $options['pitch_form_volunteer_description']
						. '</p>';
			}
			if ( isset($_REQUEST['assignment_desk_messages']['pitch_form']['errors']['volunteer']) ) {
    			$pitch_form .= '<p class="error">'
    						. $_REQUEST['assignment_desk_messages']['pitch_form']['errors']['volunteer']
    						. '</p>';
    		}
			$pitch_form .= '</fieldset>';
		}
		
		// Allow an alternate form of authentication when the pitch form is loaded
		do_action( 'ad_alternate_authentication', 'pitch_form_load' );
		
		if ( is_user_logged_in() ) {
			global $current_user;
			wp_get_current_user();			
		
			// Current user information
			$pitch_form .= '<fieldset class="standard assignment-desk-user">'
						. '<label>You are currently logged in as:</label> '
						. $current_user->display_name . ' &#60;' . $current_user->user_email . '&#62;'
						. '</fieldset>';
					
		} else {
			
			$pitch_form .= '<fieldset class="standard assignment-desk-login">'
						. '<label for="assignment_desk_username">Username:</label> '
						. '<input id="assignment_desk_username" name="assignment_desk_username" type="text" '										
						. 'value="' . $this->return_if_set( $_POST, 'assignment_desk_username' ) . '" />'							
						. '<br /><label for="assignment_desk_password">Password:</label> '
						. '<input id="assignment_desk_password" name="assignment_desk_password" type="password" '
						. '/>';
			// Show a registration link if users can register
			if ( get_option('users_can_register') ) {
				$pitch_form_url = get_permalink( $post->ID );
				$pitch_form_url = apply_filters( 'ad_pitch_form_register_redirect_url', $pitch_form_url );
				if ( $pitch_form_url ) {
					$pitch_form_url = urlencode( $pitch_form_url );
					$registration_url = site_url( 'wp-login.php?action=register&redirect_to=' . $pitch_form_url, 'login' ); 
				} else {
					$registration_url = site_url( 'wp-login.php?action=register', 'login' ); 
				}
				$pitch_form .= '<p>If you need a username, you can <a href="' . $registration_url . '">' . _('register a new account') . '</a>';
			}
			if ( isset($_REQUEST['assignment_desk_messages']['pitch_form']['errors']['login']) ) {
   				$pitch_form .= '<p class="error">'
   							. $_REQUEST['assignment_desk_messages']['pitch_form']['errors']['login']
   							. '</p>';
   			}
			$pitch_form .= '</fieldset>';
			
		}
		
		$pitch_form .= '<fieldset class="submit">';
					
		// Get the current URL to redirect to later
		$pitch_form_url = get_permalink( $post->ID );
		$pitch_form .= "<input type='hidden' name='assignment_desk_pitch_form_url' id='assignment_desk_pitch_form_url' value='{$pitch_form_url}' />";
					
		$pitch_form .= '<input type="hidden" name="assignment_desk_pitch_nonce" value="' 
					. wp_create_nonce('assignment_desk_pitch') . '" />';
		$pitch_form .= '<input type="submit" value="Submit" id="assignment_desk_pitch_submit" name="assignment_desk_pitch_submit" /></fieldset>';				
				
		$pitch_form .= '</form>';

		$the_content = str_replace($template_tag, $pitch_form, $the_content);
		return $the_content;
	}
	
	/**
	 * Saves data after a User submits a pitch form
	 */
	function save_pitch_form() {
		global $assignment_desk;
		$message = array();
		$options = $assignment_desk->general_options;
		$form_options = $assignment_desk->pitch_form_options;		
		$user_types = $assignment_desk->custom_taxonomies->get_user_types();

		if ( $assignment_desk->edit_flow_enabled() ) {
			global $edit_flow;
		}

		if ( $_POST && isset($_POST['assignment_desk_pitch_submit']) ) {
		    $form_messages = array();
			$form_messages['errors'] = array();

			// Ensure that it was the user who submitted the form, not a bot
			if ( !wp_verify_nonce($_POST['assignment_desk_pitch_nonce'], 'assignment_desk_pitch') ) {
				$form_messages['error']['nonce'] = 'Are you a bot?';
			}

			$sanitized_title = strip_tags($_POST['assignment_desk_title']);
			if ( !$sanitized_title ) {
				$form_messages['errors']['title'] = 'Please add a title to this pitch.';
			}
			
			// Allow an alternate form of authentication when the pitch form is saved
			do_action( 'ad_alternate_authentication', 'pitch_form_save' );
			
			if ( is_user_logged_in() ) {
				global $current_user;
				$sanitized_author = $current_user->ID;
			} else {
				require_once(ABSPATH . WPINC . '/registration.php');
				$credentials['user_login'] = $_POST['assignment_desk_username'];
				$credentials['user_password'] = $_POST['assignment_desk_password'];
				$credentials['remember'] = true;
				$user = wp_signon($credentials);
				if ( is_wp_error($user) ) {
				   $form_messages['errors']['login'] = $user->get_error_message();
				} else {
					wp_set_current_user($user->ID);
					$sanitized_author = $user->ID;
				}
			}
			
			if ( $assignment_desk->edit_flow_enabled() ) {
				
				// Edit Flow v0.6 and higher offers custom editorial metadata. Otherwise, fall back on old
				if ( version_compare( EDIT_FLOW_VERSION, '0.6', '>=' ) ) {
					
					$terms = $edit_flow->editorial_metadata->get_editorial_metadata_terms();
					$all_editorial_metadata = array();				

					foreach ( $terms as $term ) {
						// Setup the key for this editorial metadata term (same as what's in $_POST)
						$form_key = $edit_flow->editorial_metadata->get_postmeta_key( $term );
						$required_key = 'pitch_form_' . $term->slug . '_required';
						$editorial_metadata = isset( $_POST[$form_key] ) ? $_POST[$form_key] : '';
						
						$type = $edit_flow->editorial_metadata->get_metadata_type( $term );
						// Process date formats
						if ( $type == 'date' ) {
							$duedate_split = split( '/', $editorial_metadata );
			    			if ( count( $duedate_split ) == 3) {
			    			    $duedate_month = (int)$duedate_split[0];
			    			    $duedate_day = (int)$duedate_split[1];
			    			    $duedate_year = (int)$duedate_split[2];
			    			    // Zero pad for strtime
			    			    if ( $duedate_month < 10 ) {
			    			        $duedate_month = "0$duedate_month";
			    			    }
			    			    $editorial_metadata = strtotime($duedate_day . '-' . $duedate_month . '-' . $duedate_year);
			    			    if ( !$editorial_metadata ) {
			    			        $form_messages['errors'][$form_key] = _('Please enter a valid date of the form MM/DD/YYYY');
									continue;
			    			    }
			    			}
						}

						$editorial_metadata = strip_tags( $editorial_metadata );
						// Ensure there's a value if the field is required
						if ( !$editorial_metadata && $form_options[$required_key] == 'on' ) {
							$form_messages['errors'][$form_key] = _( $term->name . ' is required.' );
						} else {
							$all_editorial_metadata[$form_key] = $editorial_metadata;
						}
						
					}
				} else {
					// Description
					$sanitized_description = '';
					if ( $_POST['assignment_desk_description']) {
					    $sanitized_description = wp_kses($_POST['assignment_desk_description'], $allowedposttags);
					}
					else {
					    if ( $form_options['pitch_form_description_required'] == 'on' ) {
					        $form_messages['errors']['description'] = _('Description is required.');
					    }
					}
					// Location
					$sanitized_location = '';
					if ( $_POST['assignment_desk_location'] ) {
					    $sanitized_location = wp_kses($_POST['assignment_desk_location'], $allowedposttags);
					}
					else {
					    if ( $form_options['pitch_form_location_required'] == 'on' ) {
					        $form_messages['errors']['location'] = _('Location is required.');
					    }
					}
					// Due date
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
		    			        $form_messages['errors']['duedate'] = _('Please enter a valid date of the form MM/DD/YYYY');
		    			    }
		    			}
		    			else {
		    			    $form_messages['errors']['duedate'] = _('Please enter a valid date of the form MM/DD/YYYY');
		    			}
					}
					else {
					    if ( $form_options['pitch_form_duedate_required'] ) {
					        $form_messages['errors']['duedate'] = _('Due date is required.');
					    }
					}
					
				}
				
			}

			
			$sanitized_tags = '';
			if ( isset( $_POST['assignment_desk_tags'] ) ){
			    $sanitized_tags = $_POST['assignment_desk_tags'];
			}
			else {
			    if ( $form_options['pitch_form_tags_required'] ) {
			        $form_messages['errors']['tags'] = _('Tags are required.');
			    }
			}
			
			$sanitized_categories = '';
			if ( isset( $_POST['assignment_desk_categories'] ) ){
			    $sanitized_categories = (int)$_POST['assignment_desk_categories'];
			}
			else {
			    if ( $form_options['pitch_form_categories_required'] ) {
			        $form_messages['errors']['categories'] = _('Category is required.');
			    }
			}			

			
			$sanitized_volunteer = false;
			if ( isset( $_POST['assignment_desk_volunteer'] ) ){
			    $sanitized_volunteer = $_POST['assignment_desk_volunteer'];
			    if (! is_array($sanitized_volunteer) ) {
			        $sanitized_volunteer = array((int)$sanitized_volunteer);
			    }
			}
			else {
			    if ( $form_options['pitch_form_volunteer_required'] ) {
			        $form_messages['errors']['volunteer'] = _('Volunteering is required.');
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
			if ( $assignment_desk->edit_flow_enabled( 'custom_post_statuses' ) ) {
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
				
				// Only handle editorial metadata if Edit Flow exists
				if ( $assignment_desk->edit_flow_enabled() ) {
					
					// Edit Flow v0.6 and higher offers custom editorial metadata. Otherwise, fall back on old
					if ( version_compare( EDIT_FLOW_VERSION, '0.6', '>=' ) ) {
						foreach ( $all_editorial_metadata as $key => $value ) {
							update_post_meta( $post_id, $key, $value );
						}
					} else {
						// Old way of saving post meta
						update_post_meta( $post_id, '_ef_description', $sanitized_description );
						update_post_meta( $post_id, '_ef_duedate', $sanitized_duedate );
						update_post_meta( $post_id, '_ef_location', $sanitized_location );
					}
					
				}
				
				// Save pitched_by_participant and pitched_by_date information
				update_post_meta( $post_id, '_ad_pitched_by_participant', $sanitized_author );
				update_post_meta( $post_id, '_ad_pitched_by_timestamp', date_i18n('U') );
				
				// Set assignment status to default setting
				$default_assignment_status = get_term_by( 'id', $options['default_new_assignment_status'], $assignment_desk->custom_taxonomies->assignment_status_label );
				wp_set_object_terms( $post_id, $default_assignment_status->name, $assignment_desk->custom_taxonomies->assignment_status_label, false );
				
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
				
				$this->send_new_pitch_emails($post_id);
			}
			
			// Redirect to the URL so users can't submit the form twice if successful
			$redirect_url = $_POST['assignment_desk_pitch_form_url'];			
			if ( strpos( $redirect_url, '?' ) ) {
				$redirect_url .= '&success=true';
			} else {
				$redirect_url .= '?success=true';
			}
			if ( $form_options['pitch_form_success_message'] ) $redirect_url .= '&post_id=' . $post_id;
			unset( $_POST );
			wp_redirect( $redirect_url );
			exit;
		}
		
		return null;
		
	}
	
    /**
     * Send an email to users accorindg to the pitch_form_notification_emails setting.
     * @param $post_id int The ID of the new post. 
     */
    function send_new_pitch_emails( $post_id ) {
        global $assignment_desk, $wpdb;
        
        $post = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE ID=$post_id");
        $submitter = get_userdata((int)get_post_meta($post_id, '_ad_pitched_by_participant', true));
        $search = array('%blogname%',
                        '%title%',
                        '%excerpt%',
                        '%description%',
                        '%duedate%',
                        '%location%',
                        '%post_link%',
                        '%dashboard_link%',
                        '%submitter_email%',
                        '%submitter_display_name%',
        );
        $replace = array( get_option('blogname'),
                        $post->post_title,
                        $post->post_excerpt,
                        get_post_meta($post_id, '_ef_description', true),
                        ad_format_ef_duedate((int)get_post_meta($post_id, '_ef_duedate', true)),
                        ad_format_ef_duedate((int)get_post_meta($post_id, '_ef_location', true)),
                        get_permalink($post_id),
                        admin_url(),
                        $submitter->user_email,
                        $submitter->display_name,
        );

        $email_addresses = str_replace('%submitter_email%', $submitter->user_email, $assignment_desk->pitch_form_options['pitch_form_notification_emails']);
        $email_addresses = explode(',', $email_addresses);
        $subject = str_replace($search, $replace, $assignment_desk->pitch_form_options['pitch_form_email_template_subject']);
        $email_template = str_replace($search, $replace, $assignment_desk->pitch_form_options['pitch_form_email_template']);
        
        if ( $email_addresses ) {
            foreach ( $email_addresses as $email_address ) {
                $email_address = str_replace(' ', '', $email_address);
                wp_mail($email_address, $subject, $email_template);
            }
        }
    }

	/**
	 * Print a form giving the user the option to vote on an item
	 * @param int $post_id The Post ID
	 * @return string $voting_form The voting button in HTML
	 */
	function voting_button( $post_id = null ) {
		global $assignment_desk, $current_user;
		$options = $assignment_desk->public_facing_options;
		
		if ( !$post_id ) {
			global $post;
			$post_id = $post->ID;
		}
		
		// Allow alternate form of authentication when voting button is loaded
		do_action( 'ad_alternate_authentication', 'voting_load' );
			
		wp_get_current_user();
		$total_votes = (int)get_post_meta( $post_id, '_ad_votes_total', true );
		$user_id = $current_user->ID;
		
		$voting_form = '<span class="assignment_desk_voting_form">';
		// Save all of the data we need available in the DOM as hidden input fields
		$voting_form .= '<input type="hidden" class="assignment_desk_post_id" name="assignment_desk_post_id" value="' . $post_id . '" />';
		$voting_form .= '<input type="hidden" class="assignment_desk_voting_text_custom" name="assignment_desk_voting_text_custom" value="' . $options['public_facing_voting_button'] . '" />';
		$voting_form .= '<input type="hidden" class="assignment_desk_voting_nonce" name="assignment_desk_voting_nonce" value="' . wp_create_nonce('assignment_desk_voting') . '" />';
		// Button to display if the user is logged in and hasn't voted
		if ( !$this->check_if_user_has_voted( $post_id, $user_id ) && is_user_logged_in() ) {
			$voting_form .= '<input type="hidden" class="assignment_desk_action" name="assignment_desk_action" value="assignment_desk_add_vote" />';
			$voting_form .= '<a class="assignment_desk_voting_submit" href="#">';
			if ( $options['public_facing_voting_button'] ) {
				$voting_button = '<span class="assignment_desk_voting_text">' . $options['public_facing_voting_button'] . '</span>';
			} else {
				$voting_button = '<span class="assignment_desk_voting_text">Vote</span>';
			}
			$voting_button .= ' (<span class="assignment_desk_voting_votes">' . $total_votes . '</span>)';
			$voting_form .= $voting_button . '</a>';
		} else if ( $this->check_if_user_has_voted( $post_id, $user_id ) && is_user_logged_in() ) {
			$voting_form .= '<input type="hidden" class="assignment_desk_action" name="assignment_desk_action" value="assignment_desk_delete_vote" />';
			$voting_form .= '<a class="assignment_desk_voting_submit" href="#">';
			$voting_button = '<span class="assignment_desk_voting_text">Thanks!</span> (<span class="assignment_desk_voting_votes">' . $total_votes . '</span>)';
			$voting_form .= $voting_button . '</a>';
		} else {
			$voting_form .= '<input type="hidden" class="assignment_desk_action" name="assignment_desk_action" value="assignment_desk_add_vote" />';
			$voting_form .= '<a class="assignment_desk_voting_submit" href="#">';
			if ( $options['public_facing_voting_button'] ) {
				$voting_button = '<span class="assignment_desk_voting_text">' . $options['public_facing_voting_button'] . '</span>';
			} else {
				$voting_button = '<span class="assignment_desk_voting_text">Vote</span>';
			}
			$voting_button .= ' (<span class="assignment_desk_voting_votes">' . $total_votes . '</span>)';
			$voting_form .= $voting_button . '</a>';
		}
		$voting_form .= '</span>';
		return $voting_form;		
		
	}
	
	/**
	 * Check if the user has voted before
	 * @param int $post_id The Post ID
	 * @param int $user_id The User ID
	 */
	function check_if_user_has_voted( $post_id, $user_id ) {
		global $assignment_desk, $wpdb;
		
		$query = "SELECT * FROM $assignment_desk->votes_table_name 
						WHERE post_id=$post_id AND user_id=$user_id;";
		$vote = $wpdb->get_results( $query, ARRAY_N );
		
		if ( count( $vote ) ) {
			return true;
		} else {
			return false;
		}
		
	}
	
	/**
	 * Get all of the votes for a post
	 * @param int $post_id The Post ID
	 * @return array $all_votes All vote data in an array
	 */
	function get_all_votes_for_post( $post_id ) {
		global $assignment_desk, $wpdb;
		
		$query = "SELECT * FROM $assignment_desk->votes_table_name 
						WHERE post_id=$post_id ORDER BY last_updated DESC;";
		$all_votes = $wpdb->get_results( $query, ARRAY_N );
		
		if ( isset( $all_votes ) ) {
			return $all_votes;
		} else {
			return array();
		}
		
	}
	
	function update_user_vote_for_post( $post_id, $user_id, $action = 'add' ) {
		global $assignment_desk, $wpdb;
		
		if ( $action == 'add' ) {
			$query = "INSERT INTO $assignment_desk->votes_table_name (post_id, user_id)
							VALUES( '" . $wpdb->escape($post_id) . "', " . $wpdb->escape($user_id) . ");";
			$result = $wpdb->query( $query );
		} else if ( $action == 'remove' ) {
			$query = "DELETE FROM $assignment_desk->votes_table_name WHERE post_id=" . $wpdb->escape($post_id) . " AND user_id=" . $wpdb->escape($user_id) . ";";
			$result = $wpdb->query( $query );
		}
		
		
	}
	
	/**
	 * Display the avatars for the users who have voted on the item.
	 * @param int $post_id The Post ID
 	 * @return string the voting results in HTML.
	 */
	function show_all_voting_avatars( $post_id = null ) {
		global $assignment_desk, $current_user;
		$options = $assignment_desk->public_facing_options;
		
		if ( !$post_id ) {
			global $post;
			$post_id = $post->ID;
		}
		
		$all_votes = $this->get_all_votes_for_post( $post_id );
		$total_votes = (int)get_post_meta( $post_id, '_ad_votes_total', true );		
		
		// Only show avatars if there are lots of votes
		if ( $total_votes ) {
			$votes_html = '<div class="ad_all_votes">';
			$i = 0;
			foreach ( $all_votes as $vote ) {
				if ( $i >= $options['public_facing_voting_avatars'] ) {
					break;
				}
				$votes_html .= get_avatar( $vote['user_id'], 40 );
				$i++;
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
	    
		if ( isset($_GET['action']) && ( $_GET['action'] == 'assignment_desk_add_vote' || $_GET['action'] == 'assignment_desk_delete_vote') ) {
					
			// Ensure that it was the user who submitted the form, not a darn bot
			if ( !wp_verify_nonce( $_GET['nonce'], 'assignment_desk_voting' ) ) {
				$response_message = 'nonce_error';
			}
			
			// Allow alternate form of authentication on voting save
			do_action( 'ad_alternate_authentication', 'voting_save' );
			
			wp_get_current_user();
			if ( !is_user_logged_in() ) {
				$response_message = 'auth_error';
			}
			
			$post_id = (int)$_GET['post_id'];
			$sanitized_user_id = $current_user->ID;
			
			if ( $_GET['action'] == 'assignment_desk_add_vote' && $sanitized_user_id ) {
			
				if ( !$this->check_if_user_has_voted( $post_id, $sanitized_user_id ) ) {
					$this->update_user_vote_for_post( $post_id, $sanitized_user_id, 'add' );
					$total_votes = $this->get_all_votes_for_post( $post_id );
					update_post_meta( $post_id, '_ad_votes_total', count($total_votes) );
					$response_message = 'added';					
				} else {
					$response_message = 'add_error';
				}
			} else if ( $_GET['action'] == 'assignment_desk_delete_vote' && $sanitized_user_id ) {
				if ( $this->check_if_user_has_voted( $post_id, $sanitized_user_id ) ) {
					$this->update_user_vote_for_post( $post_id, $sanitized_user_id, 'remove' );
					$total_votes = $this->get_all_votes_for_post( $post_id );
					update_post_meta( $post_id, '_ad_votes_total', count($total_votes) );
					$response_message = 'deleted';
				} else {
					$response_message = 'delete_error';
				}
			}
			
			// Give a plain message if its an AJAX request
			if ( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) { 
				die( $response_message );
			} else {
				return $response_message;
			}
			
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
		
		// Allow an alternate form of authentication when the volunteer form is loaded
		do_action( 'ad_alternate_authentication', 'volunteer_form_load' );
	
		// Only logged-in users can volunteer on assignments
		if ( is_user_logged_in() ) {
	
			wp_get_current_user();
		    $user_roles = $assignment_desk->custom_taxonomies->get_user_roles();
			$available_roles = $assignment_desk->custom_taxonomies->get_user_roles_for_post( $post_id );
	
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
			$volunteer_form .= '<fieldset class="list">';
			if ( $pitch_form_options['pitch_form_volunteer_label'] ) {
				$volunteer_label = $pitch_form_options['pitch_form_volunteer_label'];
			} else {
				$volunteer_label = 'Volunteer';
			}
			$volunteer_form .= '<label for="assignment_desk_volunteer">' . $volunteer_label . '</label>';
			$volunteer_form .= '<ul class="assignment_desk_volunteer">';
			foreach ( $user_roles as $user_role ) {
				// Only show roles that the editor has specified as available for volunteering
				if ( $available_roles[$user_role->term_id] == 'on' ) {
				$volunteer_form .= '<li><input type="checkbox" id="assignment_desk_post_' . $post_id
								. '_volunteer_' . $user_role->term_id
								. '" name="assignment_desk_volunteer_roles[]"'
								. ' value="' . $user_role->term_id . '"';
				if ( in_array($user_role->term_id, $existing_roles) ) {
					$volunteer_form .= ' checked="checked"';
				}
				$volunteer_form .= ' /><label for="assignment_desk_post_' . $post_id
								. '_volunteer_' . $user_role->term_id .'">' . $user_role->name
								. '</label>';
				$volunteer_form .= '<span class="description">' . $user_role->description . '</span>';								
				$volunteer_form .= '</li>';
				}
			}
			$volunteer_form .= '</ul>';
			if ( $pitch_form_options['pitch_form_volunteer_description'] ) {
			$pitch_form .= '<p class="description">'
						. $pitch_form_options['pitch_form_volunteer_description']
						. '</p>';
			}
			$volunteer_form .= '</fieldset>';	
		    $volunteer_form .= "<input type='hidden' name='assignment_desk_volunteer_post_id' value='$post_id' />";	
			$volunteer_form .= '<input type="hidden" name="assignment_desk_volunteering_nonce" value="' 
							. wp_create_nonce('assignment_desk_volunteering') . '" />';	
		    $volunteer_form .= '<fieldset class="submit"><input type="submit" id="assignment_desk_volunteer_submit" name="assignment_desk_volunteer_submit" class="button primary" value="Submit" /></fieldset';
		    $volunteer_form .= "</form>";
		    return $volunteer_form;
		
		} else {
			return false;
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
	    
		if ( isset($_POST['assignment_desk_volunteer_submit']) ) {
	    
			$form_messages = array();
	
			// Ensure that it was the user who submitted the form, not a bot
			if ( !wp_verify_nonce($_POST['assignment_desk_volunteering_nonce'], 'assignment_desk_volunteering') ) {
				return $form_messages['error']['nonce'];
			}
			
			// Allow an alternate form of authentication when the volunteer form is saved
			do_action( 'ad_alternate_authentication', 'volunteer_form_save' );
			
			if ( !is_user_logged_in() ) {
				return false;
			}
			
			wp_get_current_user();
	    
		    $post_id = (int)$_POST['assignment_desk_volunteer_post_id'];
			$sanitized_roles = $_POST['assignment_desk_volunteer_roles'];
			$sanitized_user_id = $current_user->ID;
	    
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
	function show_single_post( $the_post ) {
		
		if ( empty( $the_post ) && is_single() ) {
			$args = array(
					'post_id' => $_GET['p'],
					'showposts' => 1
					);
			$results = ad_get_all_public_posts( $args );
			if ( !empty( $results ) ) {
				$the_post = $results;
			}
		}
		
		return $the_post;
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
		
		if ( $_POST['sort_by'] == 'ranking' || $_POST['sort_by'] == 'post_date' || $_POST['sort_by'] == 'due_date' || $_POST['sort_by'] == 'volunteers' ) {
			$sort_by = $_POST['sort_by'];
		} else {
			$sort_by = 'post_date';
		}
		
		if ( isset($_POST['sort_by_reverse']) && $_POST['sort_by_reverse'] == 'on' ) {
			$sort_by_reverse = true;
		} else {
			$sort_by_reverse = false;
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
					'sort_by' => $sort_by,
					'sort_by_reverse' => $sort_by_reverse
					);
        
        $permalinks_enabled = false;
        if ( get_option('permalink_structure') ) {
            $permalinks_enabled = true; 
            $page_in_permalink = strpos($_SERVER['REQUEST_URI'], '/page/');
            if ( $page_in_permalink ) {
                $page = substr($_SERVER['REQUEST_URI'], $page_in_permalink + 6, 1);
                $args['page'] = (int)$page;
            }
        }

        $paginator = new ad_paginator($args, ad_count_all_public_posts($args));
        $all_pitches = ad_get_all_public_posts($paginator->args);
		
		$html .= '<form class="assignment-desk-filter-form" method="POST">';
		
		$html .= '<input type="hidden" name="page_id" value="' . $parent_post->ID . '" />';
		
		if ( $options['public_facing_filtering_post_status_enabled'] || $options['public_facing_filtering_participant_type_enabled'] ) {
			$html .= '<span class="left">';
		}
		
		if ( $options['public_facing_filtering_post_status_enabled'] ) {
			$html .= '<select name="post_status" class="assignment-desk-filter-post-statuses">';
			$html .= '<option value="all">Show all post statuses</options>';
			if ( $assignment_desk->edit_flow_enabled() ) {
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
			$html .= '<input type="hidden" name="sort_by_reverse" value="';
			if ( $sort_by_reverse ) {
				$html .= 'off';
			} else {
				$html .= 'on';
			}
			$html .= '" />';
			$html .= '<select name="sort_by" class="assignment-desk-sort-by">'
				. '<option value="post_date"';
			if ( $sort_by == 'post_date' ) {
				$html .= ' selected="selected"';
			}
			$html .= '>Post date</option>';
			// Only show the sort by volunteers option if volunteering is enabled
			if ( $options['public_facing_volunteering_enabled'] ) {
				$html .= '<option value="volunteers"';
				if ( $sort_by == 'volunteers' ) {
					$html .= ' selected="selected"';
				}
				$html .= '>Volunteers</option>';
			}
			// Only show the sort by rank option if voting is enabled
			if ( $options['public_facing_voting_enabled'] ) {
				$html .= '<option value="ranking"';
				if ( $sort_by == 'ranking' ) {
					$html .= ' selected="selected"';
				}
				$html .= '>Ranking</option>';
			}
			
			$html .= '</select>';
			$html .= '<input type="submit" name="assignment-desk-sort-button" class="assignment-desk-sort-button" value="Sort" />';
			$html .= '</span>';	
		}
		$html .= '</form>';
		
		$html .= $paginator->navigation();
			
 		if ( is_array($all_pitches) ) {
		
			foreach ( $all_pitches as $pitch ) {
			
				$post_id = $pitch->ID;
				if ( $assignment_desk->edit_flow_enabled() ) {
					$post_status_object = get_term_by( 'slug', $pitch->post_status, 'post_status' );
					$post_status = $post_status_object->name;
				} else {
					if ( $pitch->post_status == 'draft' ) {
						$post_status = 'Draft';
					} else if ( $pitch->post_status == 'pending' ) {
						$post_status = 'Pending Review';
					}
				}
				
				$css_classes = $this->get_css_classes_for_pitch( $post_id );
			
				$html .= '<div class="assignment-desk-pitch';
				if ( $css_classes ) {
					$html .= ' ' . implode( ' ', $css_classes );
				}
				$html .= '"><h3><a href="' . get_permalink( $post_id ) . '">' . get_the_title( $post_id ) . '</a></h3>';
				// Only show voting if it's enabled
				if ( $options['public_facing_voting_enabled'] && $options['public_facing_voting_avatars'] ) {
					$html .= $this->show_all_voting_avatars( $post_id );
				}
				
                if ( $options['public_facing_pitched_by_enabled'] ) {
                    $pitched_by = get_post_meta($pitch->ID, '_ad_pitched_by_participant', true);
                    $user = get_userdata($pitched_by);
                    if ( $pitched_by && $user ) { 
                        $html .= '<p ><label>Pitched by:</label> ';
                        $html .= get_avatar($user->ID, '32');
                        $html .= ' ';
                        // @todo - Link or overlay for the user name.
                        if ( $user->display_name ){
                            $html .=  $user->display_name;
                        }
                        else if ( $user->user_nicename ){
                            $html .= $user->user_nicename;
                        }
                        else {
                            $html .= $user->user_login;
                        }
                        $html .= '</p>';
                    }
                }
				
				if ( $options['public_facing_content_enabled'] && $pitch->post_content ) {
					// @todo This method doesn't work
					$html .= '<p>' . $pitch->post_content . '</p>';
				}
			
 				$html .= '<div class="meta">';
				
				if ( $options['public_facing_post_status_enabled'] && $post_status ) {
				    $html .= '<p><label>Status:</label> ' . $post_status . '</p>';
				}
				
				
				if ( $assignment_desk->edit_flow_enabled() ) {
					
					// Edit Flow v0.6 and higher offers custom editorial metadata. Otherwise, fall back on old
					if ( version_compare( EDIT_FLOW_VERSION, '0.6', '>=' ) ) {

						$terms = $edit_flow->editorial_metadata->get_editorial_metadata_terms();
						foreach ( $terms as $term ) {
							$form_key = $edit_flow->editorial_metadata->get_postmeta_key( $term );
							$enabled_key = 'public_facing_' . $term->slug . '_enabled';
							
							$saved_value = get_post_meta( $post_id, $form_key, true );					
							if ( $options[$enabled_key] && $saved_value ) {
								$html_value = '';								
								// Give us different inputs based on the metadata type
								switch ( $term_type = $edit_flow->editorial_metadata->get_metadata_type( $term ) ) {
									case 'checkbox':
										$html_value = ( $saved_value ) ? 'Yes' : 'No';
										break;
									case 'date':
										$html_value = date_i18n( get_option( 'date_format' ), $saved_value );
										break;
									case 'location':
										$html_value = $saved_value;
										break;
									case 'paragraph':
										$html_value = $saved_value;
										break;
									case 'text':
										$html_value = $saved_value;
										break;
									case 'user':
										$html_value = get_the_author_meta( 'display_name', $saved_value );
										break;
									default:
										$html_input = '';
										break;
								}
								$html .= '<p><label>' . $term->name . ':</label> ' . $html_value . '</p>';
							}

						}

					} else {
						$description = get_post_meta( $post_id, '_ef_description', true );
						$location = get_post_meta( $post_id, '_ef_location', true );
						$duedate = get_post_meta( $post_id, '_ef_duedate', true );

						if ( $duedate ){
						    $duedate = date_i18n( 'M d, Y', $duedate );
					    }
					    else {
					        $duedate = _('None');
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
					
					} // END - Check if Edit Flow v0.6+
					
					
				} // END - if ( $assignment_desk->edit_flow_enabled() )
				
	
				if ( $options['public_facing_categories_enabled'] ) {
					$categories = get_the_category( $post_id );
					$categories_html = '';
					foreach ( $categories as $category ) {
						$categories_html .= '<a href="' . get_category_link($category->cat_ID) . '">' . $category->name . '</a>, ';
					}
					$html .= '<p><label>Categories:</label> ' . rtrim( $categories_html, ', ' ) . '</p>';
				}
				$tags = get_the_tags( $post_id );
				if ( $options['public_facing_tags_enabled'] && $tags ) {
					$tags_html = '';
					if ( $tags ) {
					    foreach ( $tags as $tag ) {
						    $tags_html .= '<a href="' . get_tag_link($tag->term_id) . '">' . $tag->name . '</a>, ';
					    }
				    }
					$html .= '<p><label>Tags:</label> ' . rtrim( $tags_html, ', ' ) . '</p>';
				}

				$html .= '</div>'; // END - .meta
				
				$html .= $this->get_action_links( $post_id );
				$html .= "</div>";
			
			} // END foreach
			
		} else {
			if ( $options['public_facing_no_pitches_message'] ) {
				$no_pitches_message = $options['public_facing_no_pitches_message'];
			} else {
				$no_pitches_message = _('Sorry, there are currently no pitches listed.');
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
	 * Get the voting, volunteering, and commenting action links if enabled
	 * @param int $post_id The post ID
	 * @return string $action_links_html An HTML string of the action links
	 */ 
	function get_action_links( $post_id ) {
		global $assignment_desk;
		$options = $assignment_desk->public_facing_options;
		$action_links = '';
		if ( $options['public_facing_voting_enabled'] ) {
			$action_links .= $this->voting_button( $post_id ) . ' | ';
		}
		if ( $options['public_facing_volunteering_enabled'] ) {
			$total_volunteers = get_post_meta( $post_id, '_ad_total_volunteers', true );
			$action_links .= '<a href="' . get_permalink( $post_id ) . '#assignment_desk_volunteer_form">Volunteer (';
			if ( !$total_volunteers ) {
				$total_volunteers = 0;
			}
			$action_links .= $total_volunteers . ')</a> | ';
	    }
		if ( $options['public_facing_commenting_enabled'] ) {
			$action_links .= '<a href="' . get_permalink( $post_id ) . '#respond">Comment</a> |';
		}
		if ( $options['public_facing_voting_enabled'] || $options['public_facing_volunteering_enabled'] || $options['public_facing_commenting_enabled'] ) {
			$action_links_html = '<div class="assignment-desk-action-links">';
			$action_links_html .= rtrim( $action_links, ' |' );
			$action_links_html .= '</div>';					
		}
		return $action_links_html;
	}
	
	/**
	 * Prepend vote avatars to the beginning of a post's content
	 * @param string $the_content Content of the post
	 * @return string $the_content Content of the post
	 */ 
	function prepend_voting_to_post( $the_content ) {
		global $post, $assignment_desk;
		
		if ( is_single() && $post->post_status != 'publish' && !isset( $_GET['preview'] ) ) {
			$the_content = $this->show_all_voting_avatars() . $the_content;
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
		if ( is_single() && $post->post_status != 'publish' && !isset( $_GET['preview'] ) ) {
			
			if ( $assignment_desk->edit_flow_enabled() ) {
				global $edit_flow;
				$post_status_object = get_term_by( 'slug', $post->post_status, 'post_status' );
				$post_status = $post_status_object->name;
			} else {
				if ( $post->post_status == 'draft' ) {
					$post_status = 'Draft';
				} else if ( $post->post_status == 'pending' ) {
					$post_status = 'Pending Review';
				}
			}
			
			if ( $options['public_facing_content_enabled'] ) {
				$new_content .= $the_content;
			}
			
			$new_content .= '<div class="meta">';
			
			if ( $options['public_facing_post_status_enabled'] && $post_status ) {
			    $new_content .= '<p><label>Status:</label> ' . $post_status . '</p>';
			}
			
			if ( $assignment_desk->edit_flow_enabled() ) {
				
				// Edit Flow v0.6 and higher offers custom editorial metadata. Otherwise, fall back on old
				if ( version_compare( EDIT_FLOW_VERSION, '0.6', '>=' ) ) {

					$terms = $edit_flow->editorial_metadata->get_editorial_metadata_terms();
					foreach ( $terms as $term ) {
						$form_key = $edit_flow->editorial_metadata->get_postmeta_key( $term );
						$enabled_key = 'public_facing_' . $term->slug . '_enabled';
						
						$saved_value = get_post_meta( $post_id, $form_key, true );					
						if ( $options[$enabled_key] && $saved_value ) {
							$html_value = '';								
							// Give us different inputs based on the metadata type
							switch ( $term_type = $edit_flow->editorial_metadata->get_metadata_type( $term ) ) {
								case 'checkbox':
									$html_value = ( $saved_value ) ? 'Yes' : 'No';
									break;
								case 'date':
									$html_value = date_i18n( get_option( 'date_format' ), $saved_value );
									break;
								case 'location':
									$html_value = $saved_value;
									break;
								case 'paragraph':
									$html_value = $saved_value;
									break;
								case 'text':
									$html_value = $saved_value;
									break;
								case 'user':
									$html_value = get_the_author_meta( 'display_name', $saved_value );
									break;
								default:
									$html_input = '';
									break;
							}
							$new_content .= '<p><label>' . $term->name . ':</label> ' . $html_value . '</p>';
						}

					}

				} else {
					$description = get_post_meta( $post_id, '_ef_description', true );
					$location = get_post_meta( $post_id, '_ef_location', true );
					$duedate = get_post_meta( $post_id, '_ef_duedate', true );

					if ( $duedate ){
					    $duedate = date_i18n( 'M d, Y', $duedate );
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
				
				} // END - Check if Edit Flow v0.6+
				
				
			} // END - if ( $assignment_desk->edit_flow_enabled() )
			
			$new_content .= '</div>'; // END - .meta
			
		} else {
			$new_content = $the_content;
		}
		return $new_content;
		
	}
	
	/**
	 * Appending volunteering functionality to the ending of a post's content
	 */
	function append_actions_to_post( $the_content ) {
		global $post, $assignment_desk, $current_user;
		$public_facing_options = $assignment_desk->public_facing_options;
		
		if ( is_single() && $post->post_status != 'publish' && !isset( $_GET['preview'] ) ) {
			$the_content .= $this->get_action_links( $post->ID );
			
			if ( is_user_logged_in() ) {
				wp_get_current_user();				
				$current_user_type = (int)get_usermeta( $current_user->ID, 'ad_user_type' );
				// Do not equal negative if someone created a new user type on us that
				// hasn't been saved in association with the post
				if ( get_post_meta( $post->ID, "_ad_participant_type_$current_user_type" , true ) != 'off' ) {
					$the_content .= $this->volunteer_form( $post->ID );
				}
			} else if ( $public_facing_options['public_facing_logged_out_message'] ) {
				$the_content .= '<div class="message alert">' . $public_facing_options['public_facing_logged_out_message'] . '</div>';
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
		if ( is_single() && $post->post_status != 'publish' && !isset( $_GET['preview'] ) ) {		
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