<?php
/*
* The Profile Controller.
*/

require_once('utils.php');

class assignment_desk_contributor_controller {

    /**	
	    Dispatch to the view specified by "action"
	*/
	function dispatch(){
	    // Default actions
	    $default_action = 'index';
	    
	    // Map action functions to the minumum role needed to view.
	    $actions = array(
	        'index',
	        'accept_or_decline',
	        'contact_editor',
	        'instructions',
	        'related_content',
	    );

	    // Get action from the request if possible
	    if($_GET['action']){
	        $action = $_GET['action'];
	    }
	    else if ($_POST['action']){
	        $action = $_POST['action'];
	    }
	    
	    // Check that the action is supported by this controller.
	    if(in_array($action, $actions)){
	        // Check that the current_user has the required role or greater.
	        if(current_user_can('edit_posts')){
	            return $this->$action();
            }
	    }
	    
	    // Return the default action
	    if(current_user_can('edit_posts')){
	        return $this->$default_action();
	    }
	    
	    echo 'You should never see this page. WHAT DID YOU DO?';
	}

    /**
        Show the user's index page. This is all of their content.
    */
    function index() {
        
        global $wpdb, $assignment_desk, $current_user;
        
        $messages = array('errors' => array(), 'info' => array());
        
        // Allow editors to look at others' profiles. Regular users can only see their own.
        $user = 0;
        if(current_user_can('editor')){
            
            if(array_key_exists('user_id', $_GET)){
                $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users
                                                        WHERE ID = %d", $_GET['user_id']));
                if(!$user){
                    $messages['errors'][] = "Invalid user! {$_GET['user_id']} is not a valid user id.";
                }
            }
            else if(array_key_exists('user_login', $_GET)){
                $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users
                                                        WHERE user_login = %s", $_GET['user_login']));
                if(!$user){
                    $messages['errors'][] = "Invalid user! {$_GET['user_login']} is not a valid user login.";
                }
            }
            else {
                get_currentuserinfo();
                $user = $current_user;
            }
        }
        // Not an editor. Only show the info for the currently logged in user.
        else {
            get_currentuserinfo();
            $user = $current_user;
        }
        
        // Default active tab
        $active_display = 'assignments';
        
        // Active tab specified in the $_GET['active_display]
        if(array_key_exists('active_display', $_GET)){
            $active_display = $_GET['active_display'];
        }
        
        // Pull up all of the pitches for this user
        $my_pitches = query_posts("meta_key=_ad_pitched_by&meta_value={$user->ID}"); 
        $pitches_count = count($my_pitches);
        
        // Pull up all of the posts assigned to this user
        $my_posts = query_posts(array(
                                'meta_key' =>'_coauthor',
                                'meta_value' =>  $user->ID,
                                'post_status' => 'draft',
                                'orderby'     => 'post_date',
                                'order'     => 'DESC',
                                ));

        $my_posts_count = count($my_posts);
        
        // Pull up all of the posts assigned to this user that they have yet to accept
        $my_posts_pending = $wpdb->get_results("SELECT $wpdb->posts.*  
                                                FROM   $wpdb->postmeta, $wpdb->posts 
                                                WHERE  ($wpdb->posts.ID = $wpdb->postmeta.post_id) AND
                                                       ($wpdb->postmeta.meta_key = '_ad_waiting_for_reply' AND
                                                        $wpdb->postmeta.meta_value = $user->ID)
                                                ORDER BY $wpdb->posts.post_date DESC");

        $my_posts_pending_count = count($my_posts_pending);
        
        if($my_posts_pending_count){
            $active_display = 'pending_assignments';
        }
        
        if ($my_posts_pending_count){
            $messages['errors'][] = "You have assignments waiting for a reply.";
        }
        
        include_once($assignment_desk->templates_path . '/contributor/index.php');
        
	} // end index()
	
	
	/**
	 * Instructions for contributors on how to write a good story.
	 * It needs the post_id to create a link back to the active assignment.
	*/
	function instructions(){
	    global $wpdb, $assignment_desk;
	    $post_id = 0;
	    if(!empty($_GET)){ $post_id = $_GET['post_id']; }
	    // Find the post
	    $post = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID=%d", $post_id));
	    include_once($assignment_desk->templates_path . '/contributor/instructions.php');
	}
	
	/**
	* Show content related to the assignment.
	* It needs the post_id to create a link back to the active assignment.
	*/
	function related_content(){
	    global $wpdb, $assignment_desk;
	    $post_id = 0;
	    if(!empty($_GET)){ $post_id = $_GET['post_id']; }
	    // Find the post
	    $post = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID=%d", $post_id));
	    include_once($assignment_desk->templates_path . '/contributor/related-content.php');
	}
	
	/*
	 * A contributor wants to contact the editor.
	*/
	function contact_editor(){
	    global $wpdb, $assignment_desk;
	    
	    $messages = array('errors' => array(), 'info' => array());
	    
	    if (!empty($_GET)) { 
	        $post_id = $_GET['post_id']; 
	    }
	    
	    // Find the post
	    $post = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID=%d", $post_id));

	    if(!empty($_POST)){
	        // TODO - Who get's this email? The editor that made the assignment?
	        // We should store that as a post-meta field
	        $valid_submission = false;

	        $to = "";
	        $subject = $_POST['subject'];
	        $body = $_POST['body'];

	        if(!$subject){
	            $messages['errors']['subject'] = 'Please provide a subject for your message.';
	        }
	        if(!$body){
	            $messages['errors']['body'] = 'Please provide a message.';
	        }

	        if(!count($messages['errors'])){
	            // TODO - Who to send this to?
	            // $mail_result = wp_mail($to, $subject, $body);
	            $messages['info'] = "Your message was sent to the editor";
	            unset($messages['errors']);
	        }
	    }
	    include_once($assignment_desk->templates_path . '/contributor/contact-editor.php');
	}
	
	/*
	    Contirbutor clicks on a link from the email they get when they're assigned a story.
	    They can accept or reject the story.
	*/
	function accept_or_decline(){
	    global $wpdb, $assignment_desk;
	    
	    global $current_user;
        get_currentuserinfo();

        $is_pending = False;
        $allowed_values = array('accept', 'decline');
        $response = 'reject';
        
        $post;
        $messages = array('errors' => array(), 'info' => array(),);
        
        $x = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE post_title = XXXXXXXXXXX");
        
	    if(!empty($_GET)){
	        $post_id  = $_GET['post_id'];
	        $response = $_GET['response'];
	        
	        $valid_submission = True;
	        if (!in_array($response, $allowed_values)){
	            $messages['errors'][] = 'Invalid response.';
	            $valid_submission = False;
	        }
	        
	        if (!$post_id){
	            $messages['errors'][] = 'No post.';
	            $valid_submission = False;
	        }
	        
	        // check that the user has been assigned this story 
	        // Look up the user ids we're waiting for on this post.
	        $pending_reply_ids = get_post_meta($post_id, 
	                                        ASSIGNMENT_DESK_META_PREFIX . 'waiting_for_reply');
            
            // If the current user is in there they're able to reply.
            foreach($pending_reply_ids as $p_id){
                if($p_id == $current_user->ID){
                    $is_pending = True;
                }
            }
	        
	        if(!$is_pending ){
	            $messages['errors'][] = 'You are not authorized for this action.';
            }
            else {

	            if ($valid_submission && $response == 'accept'){
    	            // delete the pending record for this user.
    	            delete_post_meta($post_id, 
    	                             ASSIGNMENT_DESK_META_PREFIX . 'waiting_for_reply',
    	                             $current_user->ID);
    	            // Add the _coauthor record for this user.
    	            update_post_meta($post_id, '_coauthor', $current_user->ID);
    	            // Mark an event that this user accepted
    	            create_event('post', $post_id, 'post', 'Accepted an assignment.', $current_user->user_login);
	            
    	            // Change the post status to 'Assigned'
    	            $wpdb->update($wpdb->posts,                         // table
    	                            array('post_status' => 'Assigned'), // data
    	                            array('ID' => $post_id));           // where
	            
    	            $post = $wpdb->get_row("SELECT * from $wpdb->posts WHERE ID=$post_id"); 
	            }
	            if ($valid_submission && $response == 'decline') {
    	            // delete the pending record for this user.
    	            delete_post_meta($post_id, 
    	                             ASSIGNMENT_DESK_META_PREFIX . 'waiting_for_reply', 
    	                             $current_user->ID);
    	            // Just in case. TODO - is this necessary?
    	            delete_post_meta($post_id, '_coauthor', $current_user->ID);
    	            // Mark an event that this user declined the assifnment
    	            create_event('post', $post_id, 'post', 'Declined an assignment.', $current_user->user_login);
                }
            }
	    }
	    else {
	        // Since we're using a link from an email we wont be able to POST.
	        return $this->index();
	    }
	    include_once($assignment_desk->templates_path . '/contributor/assignment-reply.php');
	}
} // end assignment_desk_contributor_controller
?>
