<?php
/*
* The Pitch Controller.
* The entry point for this controller is Admin Menu -> Assignment Desk -> Pitches
*/

# Assignment Desk
require_once('utils.php');

class assignment_desk_pitch_controller {
    
	/*
	* Count the number of pitches in the database of a certain status.
	* Returns an integer, 0 if none in the DB.
	*/
	function count_pitches_by_status($status_name) {
	    global $wpdb, $assignment_desk;
	    
	    $sql = "SELECT COUNT(*) FROM {$assignment_desk->tables['pitch']}, {$assignment_desk->tables['pitchstatus']}
	            WHERE {$assignment_desk->tables['pitch']}.pitchstatus_id =
	                    {$assignment_desk->tables['pitchstatus']}.pitchstatus_id
	            AND {$assignment_desk->tables['pitchstatus']}.name LIKE %s";
	    $count = $wpdb->get_var($wpdb->prepare($sql, $status_name));
	    if (!$count) {
	        $count = 0;
	    }
	    return $count;
	}
	/*
	* Get the pitched in the databse that have a certain status.
	* sort_by The column to sort by.
	* sort_dir The direction of the sort.
	* start The query start offset.
	* limit Max results to return.
	*/
	function get_pitches_by_status($status_name, $term_id,
	                                $sort_by="created", $sort_dir="DESC", 
	                                $start='0', $limit=0) {
	    global $wpdb, $assignment_desk;
	    if(!$sort_by){
	        $sort_by = "created";
	        $sort_dir = "DESC";
	    }
	    if(!$sort_dir){
	        $sort_dir = "DESC";
	    }
	    $sort_by  = $wpdb->escape($sort_by);
        $sort_dir = $wpdb->escape($sort_dir);
        $start  = $wpdb->escape($start);
        $limit = $wpdb->escape($limit);
        
	    global $wpdb, $assignment_desk;
	    $sql = "SELECT * FROM {$assignment_desk->tables['pitch']}, 
	                          {$assignment_desk->tables['pitchstatus']}
	            WHERE {$assignment_desk->tables['pitch']}.pitchstatus_id = 
	                  {$assignment_desk->tables['pitchstatus']}.pitchstatus_id
	            AND {$assignment_desk->tables['pitchstatus']}.name LIKE '%s' 
	            ";
	    if ($term_id){
	        $sql .= " AND term_id=$term_id ";
	    }
	    $sql .= " ORDER BY $sort_by $sort_dir ";
	    if ($limit){
	        $sql .= " LIMIT $start, $limit";
	    }

	    $results = $wpdb->get_results($wpdb->prepare($sql, $status_name, $sort_by, $sort_dir));
	    return $results;
	}
	/**
	* When an editor clicks on Assignment Desk -> Pitches this is what is called.
	*/
	function dispatch(){
	    // Default actions
	    $action = 'index';
	    
	    // Map action functions to the minumum role needed to view.
	    $actions = array("index", "detail", "empty_trash", "search");

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
	        if(current_user_can('editor')){
	            return $this->$action();
            }
	    }
	    echo 'You should never see this page. WHAT DID YOU DO?';
    }
    
    /**
	* This is the editors main pitch management screen.
	*/
	function index(){
		global $wpdb, $assignment_desk; 
		
		// Fetch all of the pitches from the database.
		$pitch_statuses = $wpdb->get_results("SELECT * FROM {$assignment_desk->tables['pitchstatus']}");
		
		// Store them as an array indexed by name 
	    $statuses = array();
	    foreach($pitch_statuses as $status){
	        $statuses[$status->name] = $status->pitchstatus_id;
	    }
	    
	    // Lookup all category fields of the taxonomy table 
	    $pitch_categories = get_categories();
	    
	    $categories = array();
		foreach($pitch_categories as $category){
			$categories[$category->term_id] = $category->description;
		}
		
		// Display defaults
	    $active_status = 'New';
	    $sort_by = '';
	    $sort_dir = '';
	    $term_id = 0;
	    $result_start = 0;
	    $limit = 10;
	    $messages = array('errors' => array(), 'info' => array());
	    
		//change pitch status approve or reject
		if (!empty($_POST) ) {
		    $_POST      = array_map( 'stripslashes_deep', $_POST );
		    
		    // Form validation
		    $valid_submission = true;
		    
		    $sort_by = $_POST['sort_by'];
		    $sort_dir = $_POST['sort_dir'];
		    $term_id = intval($_POST['term_id']);
		    
		    $active_status = array_key_exists('active_status', $_POST) ? $_POST['active_status'] : "New";
	        $pitch_id = intval($_POST['pitch_id']);
	        
	        if(!$pitch_id){
	            $messages['errors'][] = 'No pitch specificied.';
	            $valid_submission = false;
	        }
	        if(!array_key_exists($_POST['pitchstatus_id'], $statuses)){
	            $messages['errors'][] = 'Invalid status.';
	            $valid_submission = false;
	        }
	        if ($valid_submission) {
    	        $values = array();
    			$where = array('pitch_id' => $pitch_id);
    			$values['pitchstatus_id'] = $statuses[$_POST['pitchstatus_id']];
			
    			$wpdb->update($assignment_desk->tables['pitch'], $values, $where);
    			
    			if($values['pitchstatus_id'] == $statuses['Trash']){
    			    $messages['deleted'][] = 'The pitch was deleted.';
    			}
    			else {
    			    $messages['info'][] = 'Pitch info updated.';
                }
            }
		}
		if (!empty($_GET)){
		    
		    $sort_by = $_GET['sort_by'];
		    $sort_dir = $_GET['sort_dir'];
		    $term_id = intval($_GET['term_id']);
		    
		    if (array_key_exists('active_status', $_GET)){
		       $active_status = $_GET['active_status'];
		    }
		    if (array_key_exists('start', $_GET)){
		       $result_start = $_GET['start'];
		    }
		    if (array_key_exists('limit', $_GET)){
		       $result_start = $_GET['limit'];
		    }
		}
		
		$args = array(
            	  	'type'		  => 'post',
            		'child_of'	  => 0,
            		'orderby'	  => 'name',
            		'order'		  => 'ASC',
            		'hide_empty'  => 0,
            		'hierarchical'=> True  
                );
        $categories = get_categories($args);
		
		$display_statuses = array('New', 'Approved', 'On Hold', 'Lonely', 
		                            'Rejected', 'Assigned', 'Twitter', 'Trash');
		
		$counts = array();
		// Load up the Pitch counts by status		
		foreach($display_statuses as $status){
		    $counts[$status] = $this->count_pitches_by_status($status);
		}
		
		// Pagination
		$pitches = $this->get_pitches_by_status($active_status, $term_id, $sort_by, $sort_dir, $result_start, $limit);
		$last_page = ceil($counts[$active_status] / $limit);

        // This template inherits all the variables in this scope.
        include_once($assignment_desk->templates_path . '/pitch/index.php');
	}
	
	/**
	* Pitch detail view.
	*/
	function detail(){
	    global $wpdb, $assignment_desk;
	    
	    $messages = array('errors' => array(), 'info' => array());

	   	//built in function for categorization
		$args=array(
			  	    'type'          => 'post',
				    'child_of'      => 0,
				    'orderby'       => 'name',
				    'order'         => 'ASC',
				    'hide_empty'    => 0,
					'hierarchical'  => True  
		);
		$categories=get_categories($args);
		
		$pitch_statuses = $wpdb->get_results("SELECT * 
	                                          FROM {$assignment_desk->tables['pitchstatus']}");
	    
	    // Store them as an array indexed by name 
	    $statuses = array();
	    foreach($pitch_statuses as $status){
	        $statuses[$status->name] = $status->pitchstatus_id;
	    }
	    
	    $pitch_id = 0;
	    
	    $assignees     = array();
        $pending_reply = array();
        $signed_up     = array();
        $users         = array();
        $volunteers    = array();
        
	    // If the form was submitted save the data
	    if (!empty($_POST) ) {
	        $_POST    = array_map( 'stripslashes_deep', $_POST );
	        
	        $pitch_id = intval($_POST['pitch_id']);
	        $values = array();
	        $values['headline']       = $_POST['headline'];
	        $values['pitchstatus_id'] = $_POST['pitchstatus_id'];
            $values['term_id']        = intval($_POST['term_id']);
	        $values['summary']        = wp_kses($_POST['summary'], $allowedtags);
	        $values['notes']          = wp_kses($_POST['notes'], $allowedtags);
	        $values['updated']        = date('Y-m-d H:i:s'); // Update the timestamp
			
	        // Update the DB
	        if (empty($pitch_id)){
	            $values['created'] = date('Y-m-d H:i:s');
	            $format = array("%s", "%d", "%d", "%s", "%s", "%s", "%s");
	            $pitch_saved = $wpdb->insert($assignment_desk->tables['pitch'], $values, $format);
	            $pitch_id = $wpdb->insert_id;
            }
            else{
	            $where = array('pitch_id' => $pitch_id);
	            $pitch_saved = $wpdb->update($assignment_desk->tables['pitch'], $values, $where);
            }
	        
	        if ($pitch_saved){
	            $messages['info'][] = "Your changes were saved.";
	        }
	        else {
	            $messages['error'][] = "Your changes were not saved. Please try again later.";
	        }
	    }

	    if (!empty($_GET)){
	        $_GET    = array_map( 'stripslashes_deep', $_GET );
	        $pitch_id = intval($_GET['pitch_id']);
	        
	        if ($pitch_id) {
	            // Fetch the pitch from the DB
	            $pitch = $wpdb->get_row($wpdb->prepare("SELECT * 
	                                            FROM {$assignment_desk->tables['pitch']}
	                                            WHERE pitch_id=%d", $pitch_id));
	            
        	    $exclude_ids = array();
        	    $exclude_id_string = '';
	    
            	// Construct a string of ids seperated by commas
            	if(!count($exclude_ids)) {
            	    $exclude_id_string = '0';
            	}
            	else {
                	$exclude_id_string = join(', ', $exclude_ids);
            	}
    	
            	// Lookup all of the volunteers for this pitch
        	    $volunteers = $wpdb->get_results($wpdb->prepare("SELECT * 
        	                                            FROM {$assignment_desk->tables['pitch_volunteer']}
        	                                            WHERE pitch_id=%d",
        	                                            $pitch->pitch_id));

        	    $signed_up = $wpdb->get_results($wpdb->prepare("SELECT user_login
        	                                            FROM {$assignment_desk->tables['pitch_volunteer']}
        	                                            WHERE pitch_id=NULL",
        	                                            $pitch->pitch_id));
        	    $admins = get_users_by_role('admin');
        	    $editors = get_users_by_role('editor');
        	    $contributors = get_users_by_role('contributor');
	        }
	        else {
	            $pitch = $wpdb->get_row($wpdb->prepare("SELECT * 
	                                            FROM {$assignment_desk->tables['pitch']}
	                                            WHERE pitch_id=-1"));
	            $pitch->submitter_id   = get_current_user_id();
	            $pitch->pitchstatus_id = $pitch_statuses['New'];
	            $pitch->summary        = wp_kses($_GET['summary'], $allowedtags);
                $pitch->headline       = wp_kses($_GET['headline'], $allowedtags);
	        }
	    }
	    
	    if ($pitch->post_id > 0) {
        	$assignees = $assignment_desk->assignment_controller->
	                                        get_assignees($pitch->post_id, '_coauthor');
	        $pending_reply = $assignment_desk->assignment_controller->
	                                        get_assignees($pitch->post_id, 
	                                            ASSIGNMENT_DESK_META_PREFIX . 'waiting_for_reply');
    	}

	    // Fetch the pitch submitter from users table
	    $submitter = $wpdb->get_row("SELECT * 
	                                 FROM {$wpdb->users} 
	                                 WHERE ID = $pitch->submitter_id"); 
	    
	    
        // This template inherits all the variables in this scope.
        include_once($assignment_desk->templates_path . '/pitch/detail.php');
	}

    /*
        Delete all of the pitches marked trash and return to the index. 
    */
	function empty_trash(){
	    global $assignment_desk, $wpdb;
	    	    
	    if(!empty($_POST)){
	        $trash_id = $wpdb->get_var("SELECT pitchstatus_id 
	                                    FROM {$assignment_desk->tables['pitchstatus']}
	                                    WHERE name='Trash'");
	        $wpdb->query(
	                "DELETE
	                 FROM {$assignment_desk->tables['pitch']}
	                 WHERE {$assignment_desk->tables['pitch']}.pitchstatus_id=$trash_id");	        
	    }
	    // Clear array so the so the index function doesn't think this is a post. 
	    $_POST = array();
	    return $this->index();
    }
	
	/**
	    Search for pitches by keyword. Uses MySQL full text indexing.
	*/
	function search(){
	    global $assignment_desk, $wpdb;
	    
	    $messages = array('errors' => array(), 'info' => array());
	    	    
	    if(!empty($_GET) && array_key_exists('q', $_GET)){
	        $search_q = wp_kses($wpdb->escape($_GET['q']), array());
	        $sort_by = "headline";
	        if ($_GET['sort_by']) {
	            $sort_by = $wpdb->escape($_GET['sort_by']) . ' ASC';
            }
	        $pitches = $wpdb->get_results(
	                            "SELECT * ,
	                             MATCH(headline, summary) AGAINST ('$search_q') AS score
	                             FROM {$assignment_desk->tables['pitch']}
	                             WHERE MATCH(headline, summary)
	                             AGAINST ('$search_q') ORDER BY score DESC");

	        $active_status = "Search Results for: $search_q";
	    
    	    $counts = array();
    	    $display_statuses = array('New', 'Approved', 'On Hold', 'Lonely', 'Rejected', 'Assigned');
    		// Load up the Pitch counts by status		
    		foreach($display_statuses as $status){
    		    $counts[$status] = $this->count_pitches_by_status($status);
    		}
    		array_unshift($display_statuses, $active_status);
    		$counts[$active_status] = count($pitches);
            
            // This template inherits all the variables in this scope.
            include_once($assignment_desk->templates_path . '/pitch/index.php');
        }
        return $this->index();
	}
} // End class assignment_desk_assignment_controller
?>