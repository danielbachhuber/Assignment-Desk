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
    if ( $assignment_desk->edit_flow_enabled() ) {
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

	$to_select = "$wpdb->posts.*";
    if ( isset($args['count']) and $args['count'] ){
        $to_select = "COUNT(*) as count";
    }
  
    $query = "SELECT $to_select FROM ($wpdb->posts, $wpdb->term_relationships";
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
	
    // Only use LIMIT and OFFSET if we're not counting all the posts.
    if ( !$args['count']) {
        $query .= " LIMIT " . $args['showposts'];
        $offset = 0;
        $page = (int)$args['page'];
        // Compute offset if we're beyond page 1.
        if( $page > 1 ){
            $offset = ($args['page'] - 1) * $args['showposts'];
        }
        $query .= " OFFSET " . $offset . ";";
    }

	$results = $wpdb->get_results( $query );
	
    if ( !$results ) {
        $results = false;
    }
    return $results;
}
}

if ( !function_exists('ad_count_all_public_posts')){
function ad_count_all_public_posts($args){
    $args['count'] = true;
    $results = ad_get_all_public_posts($args);
    if ( $results ){
        return $results[0]->count;
    }
    else {
        return 0;
    }
}
}

if ( !class_exists('ad_paginator') ){

/**
 * Handle pagination for the all public pitches page.
 */
class ad_paginator {

    /**
     * @param array $args An array of args to sent to ad_get_all_public_posts
     * @param int $total_pitches The number of pitches eligible to be shown on the public page.
     */
    function __construct( $args, $total_pitches ) {
        if ( !isset($args['page']) ) {
            $page = 1;
            if ( $_GET['page'] ){
                $page = $_GET['page'];
            }
            $args['page'] = (int)$page;
        }

        if ( !isset($args['showposts']) ) {
            $showposts = 10;
            if ( isset($_GET['showposts']) ){
                $showposts = $_GET['showposts'];
            }
            $args['showposts'] = (int)$showposts;
        }

        $this->args = $args;
        $this->total_pitches = $total_pitches * 1.0;
    }

    /**
     * Create a link for the pagination navigation.
     * @param int $page The page index.
     * @param string $text Optional text to put in the link. If not present it defaults to $page.
     */
    function make_link( $page, $text=false ) {
        // Sanitize inputs
        if( !$text ){ $text = $page; }
        $page = max(1, $page);

        $link = '';
        if ( $this->args['page'] == (int)$page ) {  
            $link = " <span class='ad-pagination-current'>$text</span>";
        }
        else {          
            // Parse the QUERY_STRING into an array.
            $query_variables = array();
            parse_str($_SERVER['QUERY_STRING'], $query_variables);
            $desintation = '';
            // If permalinks are enabled add a /page/$page/
            if ( get_option('permalink_structure') ) {
                $uri = $_SERVER['REDIRECT_URL'];
                $page_pos_in_permalink = strpos($uri, '/page/');
                $desintation = "{$uri}page/$page/";
                if ( $page_pos_in_permalink ) {
                    $before = substr($uri, 0, $page_pos_in_permalink + 6);
                    $after = substr($uri, $page_pos_in_permalink + 7);
                    $desintation = $before . $page . $after;
                }
            }
            // No permalinks. Add page to the query_string
            else {
                $query_variables['page'] = $page;
            }
            // Add showposts and flatten the $query_variables into a QUERY_STRING
            $query_variables['showposts'] = $this->args['showposts'];
            $desintation .= '?' . http_build_query($query_variables);
            $link = " <a class='ad-pagination-link' href='$desintation'>$text</a>";
        }
        return $link;
    }

    /**
     * @return HTML for the pagination navigation
     */
    function navigation() {
        $nav_html = "<div class='ad-pagination-links'>";
        $pages = ceil($this->total_pitches / $this->args['showposts']);
        if ( $pages > 1 ){
            $nav_html .= _('Page') . ' ';
            if ( $this->args['page'] > 1 ) {
                $nav_html .= $this->make_link($this->args['page'] - 1, '&lt; ' . _('Previous'));
            }
        }
        $start = 1;
        if ( $pages > 1 ){
            for ($i = $start; $i <= $pages; $i++ ){
                $nav_html .= $this->make_link($i);
            }
        }
        if ( $this->args['page'] < $pages ) {
            $nav_html .= $this->make_link($this->args['page'] + 1, _('Next') . ' &gt;');
        }

        $nav_html .= "</div>";
        return $nav_html;
    }
}
}
?>