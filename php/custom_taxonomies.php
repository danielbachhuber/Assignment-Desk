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
        global $wp_version;
        if (version_compare($wp_version, '3.0', '<')) {
            add_action('admin_print_scripts-edit-tags.php', array(&$this, 'javascript'));
        }
        
		$args = array();
		$labels = array();
		// Register $assignment_taxonomy if it doesn't exist.
		if (!$this->ad_taxonomy_exists($this->assignment_status_label)) {
			$labels = array('name' => 'Assignment Statuses',
							'singular_name' => 'Assignment Status',
							'search_items' => 'Search Assignment Statuses',
							'add_new_item' => 'Add New Assignment Status',													
							);
			$args = array(	'label' => false,
							'labels' => $labels,
							'public' => false,
			                'show_ui' => false,
			                'show_tagcloud' => false,
							'rewrite' => false
							);
			register_taxonomy($this->assignment_status_label, array('post'), $args);
			add_filter('manage_edit-tags_columns', array(&$this, 'manage_tags_columns'));
			add_action('manage_user_type_custom_column', array(&$this, 'handle_user_type_users_column'), 10, 3);
		}

		// Register $user_role_taxonomy if it doesn't exist
		if (!$this->ad_taxonomy_exists($this->user_role_label)) {
		  $args = array('label' => 'User Roles',
		                'public' => true,
		                'show_ui' => false,
		                'show_tagcloud' => false,
						'rewrite' => false
		                );
		  register_taxonomy($this->user_role_label, array('user'), $args);
		}
			
		// Register $user_type_taxonomy if it doesn't exist									
		if (!$this->ad_taxonomy_exists($this->user_type_label)) {
		  $args = array('label' => 'User Types',
		                'public' => true,
		                'show_ui' => false,
		                'show_tagcloud' => false,
						'rewrite' => false
		                );
		  register_taxonomy($this->user_type_label, array('user'), $args);
		  add_action('delete_term_taxonomy', array(&$this, 'handle_user_type_delete'));
		}
      
    }
    
    /** 
     * Called every time the plugin is activated or updated.
     */
    function activate(){ 
        // Nothing to see here.
    }
    
    /**
     * Called only the first time the plugin is activated.
     */
    function activate_once() {
	
		/**
		 * Instantiates the default assignment statuses
		 */
        $default_assignment_labels = array(
            array( 'term' => 'New',
                   'args' => array( 
                       'slug' => 'new',
                       'description' => 'A new pitch that has not been edited.',)
		          ),
		    array( 'term' => 'Approved',
			       'args' => array( 
			           'slug' => 'approved',
                       'description' => 'An editor has approved the pitch.',)
		          ),
		    array( 'term' => 'Rejected',
			       'args' => array( 
			           'slug' => 'rejected',
				       'description' => 'The pitch was not accepted for development.',)
		          ),
		    array( 'term' => 'On hold',
                   'args' => array( 
                       'slug' => 'on-hold',
                       'description' => 'Work on the pitch is on hold.',)
		          ),
			array( 'term' => 'Private',
                   'args' => array( 
                       'slug' => 'private',
                       'description' => 'This assignment should not show up on public-facing views.',)
		          ),
        );
	    foreach ( $default_assignment_labels as $term ){
           wp_insert_term( $term['term'], $this->assignment_status_label, $term['args'] );
	    }
	    
		/**
		 * Instantiates the default user roles, or work users might volunteer to do in a story
		 */
	    $default_user_roles = array(
            array( 'term' => 'Writer',
                   'args' => array( 
                       'slug' => 'writer',
                       'description' => 'Writes for this blog.',)
		          ),
		    array( 'term' => 'Photographer',
			       'args' => array( 
			           'slug' => 'photographer',
                       'description' => 'Takes pictures for the story.',)
		          ),
		    array(  'term' => 'Videographer',
			        'args' => array( 
			            'slug' => 'videographer',
						'description' => 'Shoots video for the story..',)
		          ),
		    array(  'term' => 'Editor',
                    'args' => array( 
                        'slug' => 'editor',
                        'description' => 'Manages the story production.',)
		          ),
            array(  'term' => 'Fact Checker',
                     'args' => array( 
                        'slug' => 'fact-checker',
                        'description' => 'Checks the facts.',)
    		      ),
        );
	    foreach ( $default_user_roles as $term ){
           wp_insert_term( $term['term'], $this->user_role_label, $term['args'] );
	    }
	
		/**
		 * Instantiates the default user types, 
		 */
	    $default_user_types = array(
            array( 'term' => 'Community Contributor',
                   'args' => array( 
                       'slug' => 'community-contributor',
                       'description' => 'Someone from the community that writes for the blog.',)
		          ),
		    array( 'term' => 'Professional Journalist',
			       'args' => array( 
			           'slug' => 'professional-journalist',
                       'description' => 'A professional journalist.',)
		          ),
		    array( 'term' => 'Student Journalist',
			       'args' => array( 
			           'slug' => 'student-journalist',
					   'description' => 'A student who writes for the blog.',)
		          ),
            array( 'term' => 'Local Business Owner',
                   'args' => array( 
                       'slug' => 'business-owner',
                       'description' => 'Owns a local business.',)
		          ),
            array( 'term' => 'High School Student',
                   'args' => array( 
                       'slug' => 'high-school',
                       'description' => 'A local high school student.',)
  		          ),
        );
	    foreach ( $default_user_types as $term ){
           wp_insert_term( $term['term'], $this->user_type_label, $term['args'] );
	    }
	
    }
    
    /**
    * Work around to change the labels on the WordPress custom taxonomy UIs.
	* For WordPress pre-3.0
    * See js/edit_tags.js 
    * @todo Internationalize the labels we are replacing.
    */
    function javascript() {
        wp_enqueue_script('ad-edit-tags-js', ASSIGNMENT_DESK_URL .'js/edit_tags.js', array('jquery'));

        $taxonomy = $_GET['taxonomy'];
        if ( !$taxonomy ) {
			$taxonomy = $_POST['taxonomy'];
		}
        
        if ( $taxonomy ) {
            $title = "";
            $singular = "";
            $vals = array(
                        'user_role' => array('title' => 'User Roles', 'singular' => 'User Role'),
                        'user_type' => array('title' => 'User Types', 'singular' => 'User Type'),
                        'assignment_status' => array('title' => 'Assignment Statuses', 'singular' => 'Assignment status'),
                    );
            echo "<script type='text/javascript'>";
            foreach( $vals[$taxonomy] as $name => $value ){
                echo "var $name = '$value';";
            }
            echo "</script>";
        }
    }

    /**
     * Don't show the post meta_box for the assignment_status taxonomy. 
     * See post.php for the AD post meta_box.
     */
	function remove_assignment_status_post_meta_box() {
		remove_meta_box("tagsdiv-$this->assignment_status_label", 'post', 'side');
	}

	/**
	 * Wrapper for the get_terms method
	 * @param array $args Standard set of get_term() parameters
 	 */
	function get_assignment_statuses( $args = null ) {
		// Ensure our custom statuses get the respect they deserve
		$args['get'] = 'all';
		return get_terms($this->assignment_status_label, $args);
	}
	
	/**
	 * Wrapper for the get_terms method
	 * @param array $args Standard set of get_term() parameters
 	 */
	function get_user_types( $args = null ) {
		// Ensure our custom statuses get the respect they deserve
		$args['get'] = 'all';
		return get_terms($this->user_type_label, $args);
	}
	
	/**
	 * Wrapper for the get_terms method
	 * @param array $args Standard set of get_term() parameters
	 *
 	 */
	function get_user_roles( $args = null ) {
		// Ensure our custom statuses get the respect they deserve
		$args['get'] = 'all';
		return get_terms($this->user_role_label, $args);
	}
	
	/**
	 * Gets permitted user types for a given post
	 * @param int $post_id The ID for the post
	 * @return array $user_types_for_post Permitted user types on post
 	 */
	function get_user_types_for_post( $post_id = null ) {
		$user_types_for_post = array();
		$user_types = $this->get_user_types();	
		$all_participant_types = '';
					
		// If the post hasn't been saved, all user types are on
		// Otherwise, load the user types from custom fields
		if ( !$post_id ) {
			
			$user_types_for_post['display'] = 'All';
			foreach ( $user_types as $user_type ) {
				$user_types_for_post[$user_type->term_id] = 'on';
			}
			return $user_types_for_post;
			
		} else {
	
			foreach ( $user_types as $user_type ) {
				$user_types_for_post[$user_type->term_id] = get_post_meta($post_id, "_ad_participant_type_$user_type->term_id", true);
				// If it's been set before, build the string of permitted types
				// Else, set all of the participant types to 'on'
				if ( $user_types_for_post[$user_type->term_id] == 'on' ) {
					$all_participant_types .= $user_type->name . ', ';
				} else if ($user_types_for_post[$user_type->term_id] == '' || !$user_types_for_post[$user_type->term_id]) {
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
		
	}
	
	/**
	 * Gets permitted user roles for a given post
	 * @param int $post_id The ID for the post
	 * @return array $user_roles_for_post Permitted user roles on post
 	 */
	function get_user_roles_for_post( $post_id = null ) {
		$user_roles_for_post = array();
		$user_roles = $this->get_user_roles();	
		$all_participant_roles = '';
					
		// If the post hasn't been saved, all user roles are on
		// Otherwise, load the user roles from custom fields
		if ( !$post_id ) {
			
			$user_roles_for_post['display'] = 'All';
			foreach ( $user_roles as $user_role ) {
				$user_roles_for_post[$user_role->term_id] = 'on';
			}
			return $user_roles_for_post;
			
		} else {
	
			foreach ( $user_roles as $user_role ) {
				$user_roles_for_post[$user_role->term_id] = get_post_meta($post_id, "_ad_participant_role_status_$user_role->term_id", true);
				// If it's been set before, build the string of permitted roles
				// Else, set all of the participant roles to 'on'
				if ( $user_roles_for_post[$user_role->term_id] == 'on' ) {
					$all_participant_roles .= $user_role->name . ', ';
				} else if ($user_roles_for_post[$user_role->term_id] == '' || !$user_roles_for_post[$user_role->term_id]) {
					$user_roles_for_post[$user_role->term_id] = 'on';
				}
			
			}
		
			if (in_array('off', $user_roles_for_post) && !in_array('on', $user_roles_for_post)) {
				$user_roles_for_post['display'] = 'None';
			} else if ($all_participant_roles == '' || !in_array('off', $user_roles_for_post)) {
				$user_roles_for_post['display'] = 'All';
			} else {
				$user_roles_for_post['display'] = rtrim($all_participant_roles, ', ');
			}
		
			return $user_roles_for_post;
		}
		
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
	 * @return object
	 */
	function get_default_assignment_status( ) {
		global $assignment_desk;
		$options = $assignment_desk->general_options;
		$default_assignment_status = get_term_by( 'id', $options['default_new_assignment_status'], $this->assignment_status_label);
		return $default_assignment_status;
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
	
	/**
	* When we remove a user_type delete all 'ad_user_type' user metadata for user's of that type.
	*/
	function handle_user_type_delete( $tt_id ) {
	    global $wpdb;
	    $term_id = $wpdb->get_var("SELECT term_id FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = $tt_id");
        $wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='ad_user_type' and meta_value = $term_id");
	}
	
	/**
	 * Manage the columns on the edit-tags.php page (the generated UI for custom taxonomies.)
	 * Remove the Posts and Slug columns on the user_type and user_role taxonomies.
	 * Add a column on the user_type UI to show the number of users of that type.
	 */
	function manage_tags_columns($columns){
	    
	    if ( $_GET['taxonomy'] == 'user_type' || $_GET['taxonomy'] == 'user_role' ){
	        unset( $columns['posts'] );
	        unset( $columns['slug'] );
        }
	    if ( $_GET['taxonomy'] == 'user_type' ) {
	        $columns['_ad_users'] = _('Users');
	    }
	    return $columns;
	}
	
	/**
	 * Count the number of users that of a certain user_type.
	 */
	function handle_user_type_users_column( $c, $column_name, $term_id ){
	    global $wpdb;
	    if( $column_name == '_ad_users' ) {
	        return $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->usermeta where meta_key='ad_user_type' and meta_value=$term_id");
	    }
	}
}
    
}