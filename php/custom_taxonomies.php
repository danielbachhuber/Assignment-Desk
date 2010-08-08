<?php

if(!class_exists('ad_custom_taxonomy')){
    
require_once(ABSPATH . 'wp-admin/includes/template.php');
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
      
      // Register $pitch_taxonomy if it doesn't exist, else generate an object								
		if (!is_taxonomy($this->assignment_status_label)) {
			// @todo Need to label the different text on the view
			  $args = array('label' => 'Assignment Statuses',
			                'public' => true,
			                'show_ui' => false,
			                'show_tagcloud' => false,
			                );
			  register_taxonomy($this->assignment_status_label, array('post'), $args);
			  // @todo check whether this use of remove_meta_box is appropriate
			  remove_meta_box("tagsdiv-$this->assignment_status_label", 'post', 'side');
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
			if (!is_taxonomy($this->user_role_label)) {
			  // @todo Need to label the different text on the view
			  $args = array('label' => 'User Roles',
			                'public' => true,
			                'show_ui' => false,
			                'show_tagcloud' => false,
			                );
			  register_taxonomy($this->user_role_label, array('user'), $args);
			}
			
			// Register $user_type_taxonomy if it doesn't exist									
			if (!is_taxonomy($this->user_type_label)) {
			  // @todo Need to label the different text on the view
			  $args = array('label' => 'User Types',
			                'public' => true,
			                'show_ui' => false,
			                'show_tagcloud' => false,
			                );
			  register_taxonomy($this->user_type_label, array('user'), $args);
			}
      
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