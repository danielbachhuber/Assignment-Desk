<?php
/*
* The Assignments Controller.
* The entry point for this controller is Admin Menu -> Assignment Desk -> Assignments
*/

# Wordpress 
require_once(ABSPATH . WPINC . '/registration.php');

# Edit-Flow
require_once(ABSPATH . PLUGINDIR . '/edit-flow/php/util.php');

# Assignment Desk
require_once('index-controller.php');
require_once('utils.php');

define(ASSIGNMENT_DESK_META_PREFIX, '_ad_');

class assignment_desk_assignment_controller {
    	
	/**	
	    Dispatch to the view specified by action HTTP parameter.
	*/
	function dispatch(){
	    // Default action
	    $action = 'index';
	    
	    // Map action functions to the minumum role needed to view.
	    $actions = array("editor_assign",
            	         "search_user_ajax");

	    // Get action from the request if possible
	    if($_GET['action']){
	        $action = $_GET['action'];
	    }
	    else if ($_POST['action']){
	        $action = $_POST['action'];
	    }
	    
	    // Check that the action is supported by this controller.
	    if (in_array($action, $actions)){
	        if(current_user_can('editor')){
	            return $this->$action();
            }
	    }
	    echo 'You should never see this page. WHAT DID YOU DO?';
	}
	
	/* ========================== Utility functions ======================== */
	
	function create_user($user_login, $user_nicename, $user_email){
	    global $wpdb;
	    
	    $userdata = array();
        $userdata['user_login'] = $user_login;
        $userdata['user_nicename'] = $user_nicename;
        $userdata['user_email'] = $user_email;
        // TODO - Talk to the NYTimes people about whether or not a password has to be set
        // in order to work with their authentication.
        $userdata['user_pass'] = strrev($user_login);
        // TODO - Add a setting for the default user role
        $userdata['role'] = 'contributor';
        $user_id = wp_insert_user($userdata);
        
        $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users
                                               WHERE ID=%d", $user_id));

        // Add usermeta marking them as community member
        update_usermeta($user_id, ASSIGNMENT_DESK_META_PREFIX . "origin", 'community');
        
        /* 
         TODO - Ask the times if we can get email addresses for nytimes 
         logins. Users may volunteer with their nytimes id but we wont have 
         an email unless nytimes id is email
         */
         return $user;
	}
	/**
	    Lookup assignees for a post
	    $meta_key is either  _coauthor or _ad_witing_for_reply
	*/
    public function get_assignees($post_id, $meta_key){
	    global $wpdb;
	
	    $assignees = array();    
        $assigned_ids = get_post_meta($post_id, $meta_key);
        
        if($assigned_ids){   
    	    $assignee_ids_str = "";
            foreach($assigned_ids as $a_id){
                $assignee_ids_str .= "$a_id ,";
            }
            // Strip off the last comma
            $assignee_sql = substr($assignee_sql, 0, strlen($assignee_sql) - 1);
            $assignee_sql = "SELECT * FROM $wpdb->users WHERE ID IN($assignee_ids_str)"; 
            $assignees = $wpdb->get_results($assignee_sql);
        }
        return $assignees;
	}
	
	/** 
	    Look for a user to assign a story in three places:
	    1. The $wpdb->users table
	    2. The $assignment_desk->tables['pitch_volunteer'] table to see if they volunteered for this pitch
	    3. The $assignment_desk->tables['pitch_volunteer'] table to see if they signed up to be a contirbutor
	*/
	private function lookup_user($user_login, $pitch_id, &$volunteer){
	    global $wpdb, $assignment_desk;
	    
	    $user = 0;
	    // Are they a WP user?
        $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->users}
                                                WHERE user_login=%s", $user_login));
        $volunteer = False;
        
        // Are they a volunteer for this pitch?
        if (!$user){
            $user = $wpdb->get_row($wpdb->prepare("SELECT user_login, user_email, user_nicename  
                                                    FROM {$assignment_desk->tables['pitch_volunteer']}
                                                    WHERE
                                                        pitch_id = %d AND
                                                        user_login = %s", $pitch_id, $user_login));
            $volunteer = True;
        }
        
        // Did they just sign up to be a contributor? NULL pitch
        if (!$user){
            $user = $wpdb->get_row($wpdb->prepare("SELECT user_login, user_email, user_nicename  
                                                    FROM {$assignment_desk->tables['pitch_volunteer']}
                                                    WHERE
                                                        pitch_id = NULL AND
                                                        user_login = %s", $user_login));
            $volunteer = True;
        }
        return $user;
	}
	
	/** 
	    Extract the Edit-Flow due date post meta_key-data from a POST 
	    and save it in the post_metadata table 
	*/
	private function update_due_date($http_post, $post_id){
	    // Get the assignment due date and save it to the Edit-Flow custom field
		$duedate_month  = esc_html($http_post['ef_duedate_month']);
		$duedate_day    = (int)$http_post['ef_duedate_day'];
		$duedate_year   = (int)$http_post['ef_duedate_year'];
		$duedate        = strtotime($duedate_month . ' ' . $duedate_day . ', ' . $duedate_year);
		update_post_meta($post_id, '_ef_duedate', $duedate);
	}
	
	/**
	* Assign a post to a user. Marks the post meta and post status as 'Waiting for Reply'
	* @param $post_id The id of the post.
	* @param $post_id The user id of the assignee.
	* @param $role_id The id of the term from the $assignment_desk->custom_user_roles.
	*/
	function assign_post($post_id, $user_id, $role_id){
		
		$post = get_post($post_id);
		$user = get_userdata($user_id);
		
		if (!post || !$user){
			return false;
		}
		add_post_meta($post_id, "_ad_waiting_for_reply", array($user_id, $role_id));
		$post['post_status'] = __('Waiting for Reply')
		return true;
	}

	/**
	    Assign a pitch to a user. 
	*/
	function editor_assign(){
	    global $wpdb, $assignment_desk;
	    
	    $messages = array('errors' => array(), 'info' => array(),);
	    
		// Fetch all of the pitch statusesfrom the database.
		$pitch_statuses = $wpdb->get_results("SELECT * 
		                                      FROM {$assignment_desk->tables['pitchstatus']}");	
	
		// Store them as an array indexed by name 
	    $statuses = array();
	    foreach($pitch_statuses as $status){
	        $statuses[$status->name] = $status->pitchstatus_id;
	    }
	
	    $pitch_id = 0;
	    $post_id = 0;
	    
	    $pitch = 0;
	    $post = 0;
	    
	    $disable_form = False;
	    $show_user_form = False;
	    
	    // This is the initial page load FROM the pitch/editor-detail.php page.
	    // They select a user to assign to and click continue.
	    if (!empty($_GET)) {
	        $pitch_id = intval($_GET['pitch_id']);
	        
	        // Fetch the pitch from the DB
	        $pitch = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$assignment_desk->tables['pitch']}
	                                                WHERE pitch_id=%d", $pitch_id));
	        $user_login = $_GET['user_login_select'];
	        if(!empty($_GET['user_login_text'])){
	            $user_login = $_GET['user_login_text'];
	        }
            
            $volunteer = False;
            $user = $this->lookup_user($user_login, $pitch_id, $volunteer);
            
	        if (!$user){
	            $messages['errors'][] = '$user_login is not a valid user.';
	            $show_user_form = True;
	            $disable_form = True;
	        }
	                                                

	        // See if this has already been assigned to this user.
	        if ($pitch->post_id && $user){
                $pending = get_post_meta($pitch->post_id, '_ad_waiting_for_reply');
                $coauthor = get_post_meta($pitch->post_id, '_coauthor');
                
                // This user is already assigned to the post associated with this pitch.
                if(in_array($user->ID, $pending) || in_array($user->ID, $coauthor)){
                    $disable_form = True;
                    $show_user_form = True;
                    // Display an error message back to the user.
                    $messages["errors"][] = "</h4> <b>$user_login</b> is already assigned to this story. 
                                            The form has been disabled.</h4>";
                }
            }
        }
        
        // When the editor clicks the assign button on the assignment/assign.php page.
        if(!empty($_POST)) { 
            $_POST      = array_map( 'stripslashes_deep', $_POST );

	        $pitch_id   = intval($_POST['pitch_id']);
	        $post_id    = intval($_POST['post_id']);
	        $user_login = $_POST['user_login'];
	        
	        // Is the user being assigned a pitch just a volunteer?
	        // If so we need to create them
	        $volunteer = False;

	        // Save the pitch.
	        $values = array();
            $values['pitchstatus_id'] = $statuses['Assigned'];
            $values['updated']        = date('Y-m-d H:i:s'); // Update the timestamp

	        // Update the Pitch
	        $where = array('pitch_id' => $pitch_id);
	        $wpdb->update($assignment_desk->tables['pitch'], $values, $where);

	        // Fetch the pitch from the DB
	        $pitch = $wpdb->get_row($wpdb->prepare("SELECT * 
	                                                FROM {$assignment_desk->tables['pitch']}
	                                                WHERE pitch_id=%d", $pitch_id));

            $user = $this->lookup_user($user_login, $pitch_id, &$volunteer);
            // User not found the DB, create one.
            if($volunteer){
                $user = $this->create_user($user->user_login, $user->user_nicename, $user->user_email);
            }
            
            // Get the origin of the user. Either a community member or staff (staff is NULL)
            $user_origin = get_usermeta($user->ID, ASSIGNMENT_DESK_META_PREFIX . 'origin');
            if (!$user_origin){
                $user_origin = 'staff';
                update_usermeta($user->ID, ASSIGNMENT_DESK_META_PREFIX . 'origin', 'staff');
            }

            if(!$pitch->post_id){
                // Create a new post.
                $new_post = array(
                    'post_title' => $pitch->headline,
                    'post_content' => $pitch->summary,
                    'post_status' => 'Waiting for reply',
                    'post_date' => date('Y-m-d H:i:s'),
                    'post_author' => $user->ID,
                    'post_type' => 'post',
                    'post_category' => array(0)
                );
                $post_id = wp_insert_post($new_post);

                // Attach the post to the pitch.
                $wpdb->update($assignment_desk->tables['pitch'],        // table
                                array('post_id' => $post_id),           // values 
                                array('pitch_id' => $pitch->pitch_id)); // where
                $pitch->post_id = $post_id;

                // Make the post aware of what pitch it came from.
                update_post_meta($post_id, ASSIGNMENT_DESK_META_PREFIX . 'pitch_id', $pitch->pitch_id);

                // Mark the assignment with the user's origin (Either staff or community)
                update_post_meta($post_id, ASSIGNMENT_DESK_META_PREFIX . 'origin', $user_origin);

                $this->update_due_date($_POST, $post_id);

                // Indicate that we are waiting for the reply.
                update_post_meta($post_id, ASSIGNMENT_DESK_META_PREFIX . 'waiting_for_reply', $user->ID);
                $messages["info"][] = "The pitch assignment was assigned to $user_login.";
                
                // Throw an event that a user was assigned a draft.
                create_event('assignment', $post_id, 'assigned', 'Asigned a story', $user->user_login);

    			$email_body = $_POST['email_body'];

    			// Replace the link placeholders in the email since we won't have the post_id until AFTER we save it! 
    			// Broken up so the lines aren't too long
    			$link = admin_url('admin.php');
    			$link .= "?page=assignment_desk-contributor&action=accept_or_decline&post_id=$post_id&response=";
    			$email_body = str_replace("AD_ACCEPT_URL", $link . 'accept', $email_body);
    			$email_body = str_replace("AD_DECLINE_URL", $link . 'decline', $email_body);

                // Send out an email to the user.
                $headers = "From: East Village Local <noreply@{$_SERVER['SERVER_NAME']}>\r\n";
                $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

                $mail_result = wp_mail($user->user_email, 'A new assignment for you.', $email_body, $headers);
                // Display a message back to the user.
                if ($mail_result){
                    $messages['info'][] = "An email was sent to $user_login and we're awaiting their reply.";
                }
                else {
                    $messages['errors'][] = "There was an error sending the email to $user_login.";
                }
            }
        }
        $assignees     = $this->get_assignees($post_id, '_coauthor');
        $pending_reply = $this->get_assignees($post_id, ASSIGNMENT_DESK_META_PREFIX . 'waiting_for_reply');
        
        include_once($assignment_desk->templates_path . '/assignment/assign.php');
	}
	
	/*
	    Return a list of user_logins to the ajax-enabled text field on the assign view.
	        TODO - Use a plugin that can handle more data with the full name as a "preview"
	*/
	function ajax_user_search(){
        global $wpdb;
        
        // if nonce is not correct it returns -1
		check_ajax_referer("assignment_desk-ajax-nonce");
		
        if (!empty($_GET)){
            $sql = "SELECT ID, user_login FROM $wpdb->users 
                    WHERE user_login LIKE '" . $wpdb->escape($_GET['q']) . "%'
                    ORDER BY user_login ASC";
            $users = $wpdb->get_results($sql);
            $ret = "";
            
            $sql = "SELECT user_login FROM {$assignment_desk->tables['pitch']}
                    WHERE user_login LIKE '" . $wpdb->escape($_GET['q']) . "%'
                    ORDER BY user_login ASC";
            $signed_up = $wpdb->get_results($sql);
            
            $users = array_merge($users, $signed_up);
            if($users){
                foreach ($users as $user){
                     $ret = "$ret {$user->user_login}|{$user->ID}\n";
                }
            }
            else {
                $ret .= 'Not found';
            }
            echo $ret;
        }
        die();
    }
}
?>
