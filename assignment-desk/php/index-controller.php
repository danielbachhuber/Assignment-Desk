<?php
/*
* The Assignment Desk Controller.
* The entry point for this controller is Admin Menu -> Assignment Desk
*
* When executed this shows an overall activity listing for the Assignment Desk.
*
* It also serves as a base class for other controllers. 
*/

require_once('utils.php');
require_once('event.php');


class assignment_desk_index_controller {
	
	function dispatch() {
	    if (current_user_can('editor')) {
	        return $this->editor_index();
	    }
	    else if (current_user_can('contributor')) {
	        return $this->contributor_index();
	    }
    }
    
    /*
	* Front page when an Controller shows up.
	*/
	function contributor_index() {
	    global $wpdb, $assignment_desk, $current_user;
	    get_currentuserinfo();

	    // Pull up any open stories the user has been assigned.
	    $events = $wpdb->get_results(
	                $wpdb->prepare("SELECT * FROM {$assignment_desk->tables['event']}
	                                WHERE hidden=0 
	                                AND user_login=%s
	                                AND target_type='post',
	                                AND event_type='assigned'", $current_user->user_login)
	              );
	    
	    include_once($assignment_desk->templates_path . '/index/contributor.php');
	}
	
	/*
	* Front page when an Editor shows up.
	*/
	function editor_index() {
	    global $wpdb, $assignment_desk, $current_user;
	    get_currentuserinfo();
	    
	    // Check which types of events the editor wants to see.
	    $show_types = array();
	    if(!empty($_GET) && array_key_exists('event-filter', $_GET)){
	        $show_types = $_GET['event-filter'];
	    }
	    
	    // The Editor has not selected any filters.
	    if(empty($show_types)){
	        $show_types = array('pitch', 'draft', 'assignment');
    	    // Pull up the activity feed.
    	    $events = $wpdb->get_results("SELECT * FROM {$assignment_desk->tables['event']}
    	                                WHERE hidden=0
                                        ORDER BY created DESC LIMIT 20");
        
    	    $event_count = $wpdb->get_var("SELECT count(*) 
    	                                    FROM {$assignment_desk->tables['event']}
    	                                    WHERE hidden=0");
	    }
	    else {
	        
	        $event_sql_where = " WHERE hidden=0 AND (";
	        
	        if(in_array('pitch', $show_types)){
	            $event_sql_where .= "target_type='pitch' OR ";
	        }
	        if(in_array('draft', $show_types)){
	            $event_sql_where .= "(target_type='post' AND event_type='draft') OR";
	        }
	        if(in_array('assignment', $show_types)){
	            $event_sql_where .= "(target_type='post' AND (event_type='assigned' OR 
	                                                            event_type='accepted' OR 
	                                                            event_type='rejected')) OR ";
	        }
                                        
            $event_sql = "SELECT * FROM {$assignment_desk->tables['event']}" . $event_sql_where . " 0) ORDER BY created DESC LIMIT 20"; 
            $event_count_sql = "SELECT COUNT(*) FROM {$assignment_desk->tables['event']}" . $event_sql_where . " 0) ORDER BY created DESC LIMIT 20"; 
            
            $events = $wpdb->get_results($event_sql);
            $event_count = $wpdb->get_results($event_count_sql);
	    }
	    
	    include_once($assignment_desk->templates_path . '/index/editor.php');
    }
}
?>