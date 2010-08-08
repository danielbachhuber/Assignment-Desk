<?php

if(!class_exists('ad_custom_taxonomies')){
    
/**
* Base class for operations on custom taxonomies
*/
class ad_custom_taxonomies {
  
    var $assignment_status_label = 'assignment_status';
    var $user_role_label = 'user_role';
    var $user_type_label = 'user_type';
    
    var $assignment_taxonomy;
    var $user_role_taxonomy;
    var $user_type_taxonomy;

    function __construct() {
        
        // Do nothing yet
        
    }
    
    function init() {
      
		$args = array();
		$labels = array();
		// Register $assignment_taxonomy if it doesn't exist, else generate an object								
		if (!$this->ad_taxonomy_exists($this->assignment_status_label)) {
			// @todo Need to label the different text on the view
			$labels = array('name' => 'Assignment Statuses',
							'singular_name' => 'Assignment Status',
							'search_items' => 'Search Assignment Statuses',
							'add_new_item' => 'Add New Assignment Status',													
							);
			$args = array(	'label' => false,
							'labels' => $labels,
							'public' => false,
			                'show_ui' => true,
			                'show_tagcloud' => false,
							);
			register_taxonomy($this->assignment_status_label, array('post'), $args);
		}
			
		$default_assignment_labels = array(
        array(  'term' => 'New',
                 'args' => array( 
                       'slug' => 'new',
                       'description' => 'A new pitch that has not been edited.',)
		          ),
		    array(  'term' => 'Approved',
			          'args' => array( 
			                'slug' => 'approved',
                       'description' => 'An editor has approved the pitch.',)
		          ),
		    array(  'term' => 'Rejected',
			          'args' => array( 
			                'slug' => 'rejected',
								      'description' => 'The pitch was no accepted for development.',)
		          ),
		    array(  'term' => 'On hold',
                 'args' => array( 
                       'slug' => 'on-hold',
                       'description' => 'Work on the pitch is on hold.',)
		          ),
    );
	    
	    // @todo Ensure these are getting added on initialization
	    foreach ( $default_assignment_labels as $term ){
	        if (!is_term( $term['term'] ) ) {
              wp_insert_term( $term['term'], $this->assignment_taxonomy, $term['args'] );
          }
	    }
			
		// Register $user_role_taxonomy if it doesn't exist									
		if (!$this->ad_taxonomy_exists($this->user_role_label)) {
		  // @todo Need to label the different text on the view
		  $args = array('label' => 'User Roles',
		                'public' => true,
		                'show_ui' => false,
		                'show_tagcloud' => false,
		                );
		  register_taxonomy($this->user_role_label, array('user'), $args);
		}
			
		// Register $user_type_taxonomy if it doesn't exist									
		if (!$this->ad_taxonomy_exists($this->user_type_label)) {
		  // @todo Need to label the different text on the view
		  $args = array('label' => 'User Types',
		                'public' => true,
		                'show_ui' => false,
		                'show_tagcloud' => false,
		                );
		  register_taxonomy($this->user_type_label, array('user'), $args);
		}
      
    }

	function remove_assignment_status_post_meta_box() {
		remove_meta_box("tagsdiv-$this->assignment_status_label", 'post', 'side');
	}

	/**
	 * Wrapper for the get_terms method
	 * @param array|string $args Standard set of get_term() parameters
	 *
 	 */
	function get_assignment_statuses( $args = null ) {
		// Ensure our custom statuses get the respect they deserve
		$args['get'] = 'all';
		return get_terms($this->assignment_status_label, $args);
	}
	
	/**
	 * Wrapper for the get_terms method
	 * @param array|string $args Standard set of get_term() parameters
	 *
 	 */
	function get_user_types( $args = null ) {
		// Ensure our custom statuses get the respect they deserve
		$args['get'] = 'all';
		return get_terms($this->user_type_label, $args);
	}
	
	
	
	function get_user_types_for_post( $post_id = null ) {
		
		$user_types_for_post = array();
		// Post hasn't been saved yet I guess...
		if ( !$post_id ) {
			$user_types_for_post['display'] = 'All';
			return $user_types_for_post;
		}
	
		$all_participant_types = '';
		
		$user_types = $this->get_user_types();
		foreach ( $user_types as $user_type ) {
			$user_types_for_post[$user_type->term_id] = get_post_meta($post_id, "_ad_participant_type_$user_type->term_id", true);
			// If it's been set before, build the string of permitted types
			// Else, set all of the participant types to 'on'
			if ( $user_types_for_post[$user_type->term_id] == 'on' ) {
				$all_participant_types .= $user_type->name . ', ';
			} else if ($user_types_for_post[$user_type->term_id] == '') {
				$user_types_for_post[$user_type->term_id] = 'on';
			}
			
		}
		
		if (in_array('off', $user_types_for_post) && !in_array('on', $user_types_for_post)) {
			$user_types_for_post['display'] = 'None';
		} else if ($all_participant_types == '' || !in_array('off', $user_types_for_post)) {
			$user_types_for_post['display'] = 'All';
		} else {
			$user_types_for_post['display'] = rtrim($all_participant_types, ', ');
		}
		
		return $user_types_for_post;
		
	}
	
	/**
	 * Wrapper for determining whether taxonomy exists
	 * @param string $taxonomy
	 */
	function ad_taxonomy_exists($taxonomy) {
		if ( function_exists( 'taxonomy_exists' ) ) {
			return taxonomy_exists( $taxonomy );
		} else {
			return is_taxonomy( $taxonomy );
		}
	}
	
	/**
	 * Get default assignment status
	 * @return string
	 */
	function get_default_assignment_status( ) {
		global $assignment_desk;
		$options = $assignment_desk->general_options;
		return get_term_by( 'id', $options['default_new_assignment_status'], $this->assignment_status_label);
	}
    
    /**
	 * Adds a new custom status as a term in the wp_terms table.
	 * Basically a wrapper for the wp_insert_term class.
	 *
	 * The arguments decide how the term is handled based on the $args parameter.
	 * The following is a list of the available overrides and the defaults.
	 *
	 * 'description'. There is no default. If exists, will be added to the database
	 * along with the term. Expected to be a string.
	 *
	 * 'slug'. Expected to be a string. There is no default.
	 *
	 * @param int|string $term The status to add or update
	 * @param array|string $args Change the values of the inserted term
	 * @return array|WP_Error The Term ID and Term Taxonomy ID
	 *
	 */
	function insert_term($term, $args=array()){
		$ret = wp_insert_term( $term, $this->taxonomy, $args );
	} // END: insert_term
	
	function get_taxonomy_id(){
		return $this->taxonomy_id;
	}
}
    
}