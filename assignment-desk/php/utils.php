<?php

require_once('user_search.php');

/**
* If the $str is longer $length return the first $length characters plus '...'
*/
function shorten_ellipses($str, $length){
	if(strlen($str) > $length){
        return substr($str, 0, $length) . '...';
    }
    else {
        return $str;
    }
}

/**
    Display the categories associated with a pitch.
    Utility method for the templates.
*/
function display_pitch_categories($pitch){
    if( $pitch->term_id ){
        $args=array('style'=>none, 'hide_empty'=> 0,'include'=> $pitch->term_id); 
        echo wp_list_categories($args); 
    }
    else {
        echo "None";
    }
}

function count_pitch_volunteers($pitch_id) {
	global $assignment_desk, $wpdb;
	
    $pitch_volunteer = $assignment_desk->tables['pitch_volunteer'];
    $sql = "SELECT COUNT(*) FROM $pitch_volunteer WHERE pitch_id = $pitch_id";

    return $wpdb->get_var($sql);	
}

function get_users_by_role($role){
    $user_query_vars = array(
	                'role' => $role,
	                'order_by' => 'user_login',
	                'return_fields' => '*'
	);
	$search = new AD_User_Query($user_query_vars);
	return $search->get_results();
}

/* 
	Format the Edit-Flow due date post meta-data for display 
*/
function format_ef_due_date($post_id) {
    $duedate = get_post_meta($post_id, '_ef_duedate');
	$duedate = absint( $duedate[0] );
	if($duedate) {
		$duedate_month = date('M', $duedate);
		$duedate_day = date('j', $duedate);
		$duedate_year = date('Y', $duedate);
	} else {
        $duedate_month = date('M');
        $duedate_day = date('j');
        $duedate_year = date('Y');	
	}
	return "$duedate_month $duedate_day, $duedate_year";
}
?>