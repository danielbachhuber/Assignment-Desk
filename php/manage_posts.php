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
        add_action('manage_posts_custom_column', array(&$this, 'handle_ad_assignment_status_column'), 10, 2);
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
                                    _('_ad_assignment_status') => __('Assignment Status'),
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
        
		$participant_types = $assignment_desk->custom_taxonomies->get_user_types_for_post($post_id);
		
		echo $participant_types['display'];
          
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
    
    function handle_ad_assignment_status_column($column_name, $post_id){
		global $assignment_desk;
        if ( $column_name == __('_ad_assignment_status') ) {
            $current_status = wp_get_object_terms($post_id, $assignment_desk->custom_taxonomies->assignment_status_label);
			if ($current_status) {
				echo $current_status[0]->name;
			}
        }
    }
}

} // end if(!class_exists)
?>