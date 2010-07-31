<?php
if (!class_exists('assignment_desk_manage_posts')) {

class assignment_desk_manage_posts {
    
    function __construct(){
        // Add Filters
        add_filter('manage_posts_columns', array(&$this, 'add_manage_post_columns'));
        add_filter('manage_posts_columns', array(&$this, 'remove_manage_post_columns'));
        add_action('manage_posts_custom_column', array(&$this, 'handle_ef_due_date_column'), 10, 2);
        add_action('manage_posts_custom_column', array(&$this, 'handle_ad_assignment_column'), 10, 2);
    }
    
    /**
     *   Add columns to the manage posts listing.
     *   Wordpress calls this and passes the list of columns.
    */ 
    function add_manage_post_columns($posts_columns){
        // TODO - Specify the column order
        $custom_fields_to_add = array(
                                    _('_ef_duedate') => __('Due Date'),
                                     _('_ad_assignment') => __('Assignment'),
                                );
        
        foreach ($custom_fields_to_add as $field => $title) {
            $posts_columns["$field"] = $title;
        } 
        return $posts_columns;
    }
    
    /**
     *  Remove columns from the manage posts listing.
     *  Wordpress calls this and passes the list of columns.
    */
    function remove_manage_post_columns($posts_columns){
        unset($posts_columns['date']);
        unset($posts_columns['tags']);
    
        return $posts_columns;
    }
    
    /**
      *  Wordpress doens't know how to resolve the column name to a post attribute 
      *  so this function is called with the column name and the id of the post.
    */
    function handle_ef_due_date_column($column_name, $id){
        if($column_name == __('_ef_duedate')){
            // Get the due date, format it and echo.
            $due_date = get_post_meta($id, '_ef_duedate', true);
            if($due_date){
                echo strftime("%b %d, %Y", $due_date);
            }
            else {
                echo 'None';
            }
        }
    }
    
    function handle_ad_assignment_column($column_name, $id){
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