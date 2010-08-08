<?php
if (!class_exists('ad_manage_posts')) {

class ad_manage_posts {
    
    function __construct(){
        // Add Filters
        
    }

	function init() {
		global $assignment_desk;
		add_filter('manage_posts_columns', array(&$this, 'add_manage_post_columns'));
        add_action('manage_posts_custom_column', array(&$this, 'handle_ad_user_type_column'), 10, 2);
		if ($assignment_desk->edit_flow_exists()) {
        	add_action('manage_posts_custom_column', array(&$this, 'handle_ef_duedate_column'), 10, 2);
			add_action('manage_posts_custom_column', array(&$this, 'handle_ef_location_column'), 10, 2);
		}
        add_action('manage_posts_custom_column', array(&$this, 'handle_ad_assignment_column'), 10, 2);
	}
    
    /**
     *   Add columns to the manage posts listing.
     *   Wordpress calls this and passes the list of columns.
    */ 
    function add_manage_post_columns($posts_columns){
		global $assignment_desk;
        // TODO - Specify the column order
        $custom_fields_to_add = array(
                                    _('_ad_user_type') => __('Contributor Types'),
                                    _('_ad_assignment') => __('Assignment'),
                                );
		if ($assignment_desk->edit_flow_exists()) {
			$custom_fields_to_add[_('_ef_duedate')] = __('Due Date');
			$custom_fields_to_add[_('_ef_location')] = __('Location');
		}
        
        foreach ($custom_fields_to_add as $field => $title) {
            $posts_columns["$field"] = $title;
        } 
        return $posts_columns;
    }
    
    function handle_ad_user_type_column( $column_name, $post_id ) {
      global $assignment_desk;
      
      if ( $column_name == __( '_ad_user_type' ) ) {
        
		$participant_types = array();
        $user_types = $assignment_desk->custom_taxonomies->get_user_types();
		foreach ( $user_types as $user_type ) {
			$participant_types[$user_type->term_id] = get_post_meta($post_id, "_ad_participant_type_$user_type->term_id", true);
			// If it's been set before, build the string of permitted types
			// Else, set all of the participant types to 'on'
			if ( $participant_types[$user_type->term_id] == 'on' ) {
				$all_participant_types .= $user_type->name . ', ';
			} else if ($participant_types[$user_type->term_id] == '') {
				$participant_types[$user_type->term_id] = 'on';
			}

		}
		if ($all_participant_types == '' || !in_array('off', $participant_types)) {
			$all_participant_types = 'All';
		}
		
		echo rtrim($all_participant_types, ', ');
          
      }
      
    }
    
    /**
      *  Wordpress doesn't know how to resolve the column name to a post attribute 
      *  so this function is called with the column name and the id of the post.
    */
    function handle_ef_duedate_column($column_name, $post_id){
        if ( $column_name == __('_ef_duedate') ){
            // Get the due date, format it and echo.
            $due_date = get_post_meta($post_id, '_ef_duedate', true);
            if($due_date){
                echo strftime("%b %d, %Y", $due_date);
            }
            else {
                echo 'None listed';
            }
        }
    }

	function handle_ef_location_column($column_name, $post_id){
        if ( $column_name == __('_ef_location') ){
            // Get the due date, format it and echo.
            $location = get_post_meta($post_id, '_ef_location', true);
            if($location){
             	echo $location;
            }
            else {
                echo 'None listed';
            }
        }
    }
    
    function handle_ad_assignment_column($column_name, $post_id){
        if($column_name == __('_ad_assignment')){
            echo 'Placeholder';
            /* Print out Assignment Metadata
               - Editor 
               - Authors
            */
        }
    }
}

} // end if(!class_exists)
?>