<?php

if (!function_exists('ad_format_ef_duedate')){
function ad_format_ef_duedate($duedate){
    $formatted = '';
    if($duedate) {
    	$duedate_month = date('M', $duedate);
    	$duedate_day = date('j', $duedate);
    	$duedate_year = date('Y', $duedate);
    	$formatted = "$duedate_month/$duedate_day/$duedate_year";
    } 
    return $formatted;
}
}
/**
 * Fetch all of the participants for a post.
 * Optionally filter for user who's role record of a certain status.
 * Valid statuses are 'pending', 'volunteer', 'accepted', 'rejected'
 */
if (!function_exists('ad_get_participants')){
function ad_get_participants($post_id, $status_filter = array()){
    global $assignment_desk, $wpdb;
    $participants = array();
    $user_roles = $assignment_desk->custom_taxonomies->get_user_roles(array());
    foreach( $user_roles as $user_role ) {
        $participants[$user_role->term_id] = array();
        $the_participants = get_post_meta($post_id, "_ad_participant_role_$user_role->term_id", true);
        if( $status_filter ) {
            $participants[$user_role->term_id] = array();
            foreach( $the_participants as $participant_record ) {
                if( in_array( $participant_record[1], $status_filter ) ) {
                    $participants[$user_role->term_id][] = $participant_record;
                }
            }
        }
        else {
            $participants[$user_role->term_id] = $the_participants;
        }
    }
    return $participants;
}
}

/**
 * Count all of the participants for a post
 * Optionally filter for user who's role record of a certain status.
 * Valid statuses are 'pending', 'volunteer', 'accepted', 'rejected'
 */
if (!function_exists('ad_count_participants')){
function ad_count_participants($post_id, $status_filter = array()){
    global $assignment_desk, $wpdb;
    $count = 0;
    foreach ( ad_get_participants($post_id, $status_filter) as $role_participants ){
        $count += count($role_participants);
    }
    return $count;
}
}

/**
 * Get a list of posts that do not have assignees.
 */
if (!function_exists('ad_get_unassigned_posts')){
function ad_get_unassigned_posts(){
    global $assignment_desk, $wpdb;
    $unassigned = array();
    if ( $assignment_desk->edit_flow_exists() ) {
        global $edit_flow;
        $ef_statuses = $edit_flow->custom_status->get_custom_statuses();
        $status_slugs = array();
        foreach ( $ef_statuses as $ef_status ) {
            $status_slugs[]= $ef_status->slug;
        }
        $args = array();
        $args['post_status'] = implode(',', $status_slugs);
    
        $all_posts = get_posts($args);
        if ($all_posts) {
		    foreach ($all_posts as $post) {
		        if(!ad_count_participants($post->ID)){
		            $unassigned[] = $post;
		        }
	        }
        }
    }
    return $unassigned;
}
}

/**
 * Get a list of posts that should be displayed to the public for feedback.
 * Returns unpublished posts that do not have an assignment_status of completed and are not private.
 */
if ( !function_exists('ad_get_all_public_posts') ) {
function ad_get_all_public_posts( $args = null ) {
    global $assignment_desk, $wpdb;
    $public_assignment_statuses = implode(',', $assignment_desk->public_facing_options['public_facing_assignment_statuses']);
    if ( ! $public_assignment_statuses ) {
        $public_assignment_statuses = -1;
    }

	$defaults = array(
				'user_types' => 'all',
				'sort_by' => 'post_date',
				'post_status' => 'all',
				'showposts' => 10,
				'page' => 0,
				'post_id' => null
				);
				
	$args = array_merge( $defaults, $args );

	$query = "SELECT $wpdb->posts.* FROM ($wpdb->posts, $wpdb->term_relationships";
	if ( $args['user_types'] != 'all' ) {
		$query .= ", $wpdb->postmeta";
	}
	$query .= ")";
	
	// Join the postmeta table so we can sort by the meta_value column restricted to '_ef_duedate'
	if ( $args['sort_by'] == 'due_date' ) {
		$query .= " LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '_ef_duedate')";		
	}
	// Join the postmeta table so we can sort by the meta_value column restricted to '_ad_votes_total'
	if ( $args['sort_by'] == 'ranking' ) {
		$query .= " LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '_ad_votes_total')";		
	}
	// Join the postmeta table so we can sort by the meta_value column restricted to '_ad_votes_total'
	if ( $args['sort_by'] == 'volunteers' ) {
		$query .= " LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '_ad_total_volunteers')";		
	}
	
	$query .= " WHERE $wpdb->posts.post_type = 'post' 
						AND $wpdb->posts.post_status != 'publish'
						AND $wpdb->posts.post_status != 'trash'
						AND $wpdb->posts.post_status != 'auto-draft'
						AND $wpdb->posts.post_status != 'inherit'";
						
	if ( $args['post_id'] ) {
		$query .= $wpdb->prepare( " AND $wpdb->posts.ID = %s", $args['post_id'] );
	}
			
	// Only return posts that the user has specified as public assignments		
	$query .= " AND ( $wpdb->posts.ID = $wpdb->term_relationships.object_id
							AND $wpdb->term_relationships.term_taxonomy_id IN ({$public_assignment_statuses}) )";
	
	if ( $args['post_status'] != 'all' ) {
		$query .= $wpdb->prepare(" AND $wpdb->posts.post_status = '%s'", $args['post_status']);
	}
	
	if ( $args['user_types'] != 'all' ) {
		// @todo This may need to be sanitized to protect against SQL injections
		$user_types = $args['user_types'];
		$query .= " AND ( $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '_ad_participant_type_$user_types' AND $wpdb->postmeta.meta_value = 'on' )";
	}

	if ( $args['sort_by_reverse'] ) {
		$sort_by = 'DESC';
	} else {
		$sort_by = 'ASC';
	}
	switch ( $args['sort_by'] ) {
		case 'post_date':
			$query .= " ORDER BY $wpdb->posts.post_date $sort_by";
			break;
		case 'ranking':
			$query .= " ORDER BY $wpdb->postmeta.meta_value $sort_by";
			break;
		case 'volunteers':
			$query .= " ORDER BY $wpdb->postmeta.meta_value $sort_by";
			break;
		case 'due_date':
			if ( $args['sort_by_reverse'] ) {
				$query .= " ORDER BY $wpdb->postmeta.meta_value DESC";
			} else {
				$query .= " ORDER BY $wpdb->postmeta.meta_value ASC";
			}
			break;
		default:
			break;
	}
	
	$query .= " LIMIT " . $args['showposts'];
	
	$offset = $args['page'] * $args['showposts'];
	$query .= " OFFSET " . $offset . ";";

	$results = $wpdb->get_results( $query );
	
    if ( !$results ) {
        $results = false;
    }
    return $results;
}
}

?>