<?php

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
 * Get a list of posts that are in progress.
 * Returns unpublished posts that do not have an assignment_status of completed 
 */
if (!function_exists('ad_get_inprogress_posts')){
function ad_get_inprogress_posts(){
    global $assignment_desk, $wpdb;
    $completed_status = get_term($assignment_desk->general_options['default_published_assignment_status'], 
                                   $assignment_desk->custom_taxonomies->assignment_status_label);
   $exclude_status = -1;
   if(!is_wp_error($completed_status)){
       $exclude_status = $completed_status->term_id;
   }
    return $wpdb->get_results("SELECT * FROM $wpdb->posts
                                LEFT JOIN $wpdb->term_relationships ON($wpdb->posts.ID = $wpdb->term_relationships.object_id)
                                LEFT JOIN $wpdb->term_taxonomy ON($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)
                                LEFT JOIN $wpdb->terms ON($wpdb->term_taxonomy.term_id = $wpdb->terms.term_id)
                                WHERE $wpdb->posts.post_type = 'post' 
                                AND $wpdb->posts.post_status != 'publish'
                                AND $wpdb->posts.post_status != 'trash'
                                AND $wpdb->term_taxonomy.taxonomy = '{$assignment_desk->custom_taxonomies->assignment_status_label}'
                                AND $wpdb->terms.term_id != {$exclude_status}
                                ORDER BY $wpdb->posts.post_date DESC");
}
}

/**
 * Get a list of posts that should be displayed to the public for feedback.
 * Returns unpublished posts that do not have an assignment_status of completed and are not private.
 */
if (!function_exists('get_public_feedback_posts')){
function get_public_feedback_posts(){
    global $assignment_desk, $wpdb;
    $include_statuses = implode(',', $assignment_desk->public_facing_options['public_facing_assignment_statuses']);
    if ( ! $include_statuses ) {
        $include_statuses = -1;
    }
    $results = $wpdb->get_results("SELECT * FROM $wpdb->posts
                                LEFT JOIN $wpdb->postmeta ON($wpdb->posts.ID = $wpdb->postmeta.post_id)
                                LEFT JOIN $wpdb->term_relationships ON($wpdb->posts.ID = $wpdb->term_relationships.object_id)
                                LEFT JOIN $wpdb->term_taxonomy ON($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)
                                LEFT JOIN $wpdb->terms ON($wpdb->term_taxonomy.term_id = $wpdb->terms.term_id)
                                WHERE $wpdb->posts.post_type = 'post' 
                                AND $wpdb->posts.post_status != 'publish'
                                AND $wpdb->posts.post_status != 'trash'
                                AND $wpdb->term_taxonomy.taxonomy = '{$assignment_desk->custom_taxonomies->assignment_status_label}'
                                AND $wpdb->terms.term_id IN ({$include_statuses})
                                ORDER BY $wpdb->posts.post_date DESC");
    if(!$results){
        $results = array();
    }
    return $results;
}
}

?>