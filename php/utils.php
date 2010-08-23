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

/**
 * Get a list of posts that do not have assignees.
 */
if (!function_exists('get_inprogress_posts')){
function get_inprogress_posts(){
    global $assignment_desk, $wpdb;
    $inprogress_posts = array();
    $completed_status = get_term($assignment_desk->general_options['default_published_assignment_status'], 
                                   $assignment_desk->custom_taxonomies->assignment_status_label);
    return $wpdb->get_results("SELECT * FROM $wpdb->posts
                                LEFT JOIN $wpdb->term_relationships ON($wpdb->posts.ID = $wpdb->term_relationships.object_id)
                                LEFT JOIN $wpdb->term_taxonomy ON($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)
                                LEFT JOIN $wpdb->terms ON($wpdb->term_taxonomy.term_id = $wpdb->terms.term_id)
                                WHERE $wpdb->posts.post_type = 'post' 
                                AND $wpdb->posts.post_status != 'publish' 
                                AND $wpdb->term_taxonomy.taxonomy = '{$assignment_desk->custom_taxonomies->assignment_status_label}'
                                AND $wpdb->terms.term_id != {$completed_status->term_id}
                                ORDER BY $wpdb->posts.post_date DESC");
}
}

?>