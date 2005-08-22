<?php

/**
 * Fetch all of the participants for a post.
 * Optionally filter for user who's role record of a certain status.
 * valid statuses are 'pending', 'volunteer', 'accepted', 'rejected'
 */
if (!function_exists('get_participants')){
function get_participants($post_id, $status_filter = array()){
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
 */
if (!function_exists('count_participants')){
function count_participants($post_id, $status_filter = array()){
    global $assignment_desk, $wpdb;
    $count = 0;
    foreach ( get_participants($post_id, $status_filter) as $role_participants ){
        $count += count($role_participants);
    }
    return $count;
}
}

/**
 * Get a list of posts that do not have assignees.
 */
if (!function_exists('get_unassigned_posts')){
function get_unassigned_posts(){
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
		        if(!count_participants($post->ID)){
		            $unassigned[] = $post;
		        }
	        }
        }
    }
    return $unassigned;
}
}

?>