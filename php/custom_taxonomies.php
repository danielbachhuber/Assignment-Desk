<?php

if(!class_exists('ad_custom_taxonomy')){
    
require_once(ABSPATH . 'wp-admin/includes/template.php');
/**
* Base class for operations on custom taxonomies
*/
class ad_custom_taxonomies {
  
  
    var $pitch_status_label = 'pitch_status';
    var $user_role_label = 'user_role';
    var $user_type_label = 'user_type';
    
    var $pitch_taxonomy;
    var $user_role_taxonomy;
    var $user_type_taxonomy;

    function __construct() {
        
        // Do nothing yet
        
    }
    
    function init_taxonomies() {
      
      // Register $pitch_taxonomy if it doesn't exist, else generate an object								
		if (!is_taxonomy($this->pitch_status_label)) {
			// @todo Need to label the different text on the view
			  $args = array('label' => 'Pitch Statuses',
			                'public' => true,
			                'show_ui' => false,
			                'show_tagcloud' => false,
			                );
			  register_taxonomy($this->pitch_status_label, array('post'), $args);
			  // @todo check whether this use of remove_meta_box is appropriate
			  remove_meta_box("tagsdiv-$this->pitch_status_label", 'post', 'side');
			}
			
			$default_pitch_labels = array(
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
	    foreach ( $default_pitch_labels as $term ){
	        if (!is_term( $term['term'] ) ) {
              wp_insert_term( $term['term'], $this->pitch_taxonomy, $term['args'] );
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
    
    function edit_taxonomy_page() {
      
      ?>
      
      Hello world
      
      <?php
      
      
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