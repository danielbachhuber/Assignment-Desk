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
        	add_action('manage_posts_custom_column', array(&$this, 'handle_ef_due_date_column'), 10, 2);
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
                                    _('_ad_user_type') => __('User Type'),
                                    _('_ad_assignment') => __('Assignment'),
                                );
		if ($assignment_desk->edit_flow_exists()) {
			$custom_fields_to_add[_('_ef_duedate')] = __('Due Date');
		}
        
        foreach ($custom_fields_to_add as $field => $title) {
            $posts_columns["$field"] = $title;
        } 
        return $posts_columns;
    }
    
    function handle_ad_user_type_column( $column_name, $post_id ) {
      global $assignment_desk;
      
      if ( $column_name == __( '_ad_user_type' ) ) {
        
        $post = get_post($post_id);
        $user_type = (int)get_usermeta($post->post_author, $assignment_desk->option_prefix.'user_type', true);
        
        $user_type_taxonomy = get_terms($assignment_desk->custom_taxonomies->user_type_label, array('get'=>'all'));
        
        foreach ( $user_type_taxonomy as $user_type_term ) {
          if ( $user_type == $user_type_term->term_id ) {
            $user_type_term_name = $user_type_term->name;
            break;
          } else {
            $user_type_term_name = 'None assigned';
          }
        }
          
        echo $user_type_term_name;
          
      }
      
    }
    
    /**
      *  Wordpress doens't know how to resolve the column name to a post attribute 
      *  so this function is called with the column name and the id of the post.
    */
    function handle_ef_due_date_column($column_name, $post_id){
        if ( $column_name == __('_ef_duedate') ){
            // Get the due date, format it and echo.
            $due_date = get_post_meta($post_id, '_ef_duedate', true);
            if($due_date){
                echo strftime("%b %d, %Y", $due_date);
            }
            else {
                echo 'None';
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