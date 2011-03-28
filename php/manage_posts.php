<?php
if (!class_exists('ad_manage_posts')) {

class ad_manage_posts {
    
    function __construct(){ 
    }

	function init() {
		global $assignment_desk, $pagenow;

		// Only show filtering on edit posts view, not other post types
		if ( $pagenow != 'edit.php' ) {
			return false;
		} else {
			if ( isset( $_GET['post_type'] ) && $_GET['post_type'] != 'post' ) {
				return false;
			}
		}
		
		add_filter('manage_posts_columns', array(&$this, 'add_manage_post_columns'));
		add_action('manage_posts_custom_column', array(&$this, 'handle_ad_assignment_status_column'), 10, 2);
		add_action('manage_posts_custom_column', array(&$this, 'handle_ad_votes_total'), 10, 2);
		
		/* Disabled 11/11/10 until we can find a better way to manage which metadata appears in columns ^DB
		if ($assignment_desk->edit_flow_enabled()) {
        	add_action('manage_posts_custom_column', array(&$this, 'handle_ef_duedate_column'), 10, 2);
			add_action('manage_posts_custom_column', array(&$this, 'handle_ef_location_column'), 10, 2);
		} */
		if ($assignment_desk->coauthors_plus_exists()) {
		    add_action('manage_posts_custom_column', array(&$this, 'handle_ad_participants_column'), 10, 2);
		}
		
		if(current_user_can($assignment_desk->define_editor_permissions)){
		    add_action('manage_posts_custom_column', array(&$this, 'handle_ad_user_type_column'), 10, 2);
    		add_action('manage_posts_custom_column', array(&$this, 'handle_ad_volunteers_column'), 10, 2);
    		add_action('manage_posts_custom_column', array(&$this, 'handle_ad_pitched_by_timestamp_column'), 10, 2);
		}
		
		// Filter by eligible contributor types
        add_action('restrict_manage_posts', array(&$this, 'add_eligible_contributor_type_filter'));
        
		// Sorting
		add_action('restrict_manage_posts', array(&$this, 'add_sortby_option'));
        add_action('parse_query', array(&$this, 'parse_query_sortby'));

        // Add postmeta to the manage_posts query
        add_filter('posts_join', array(&$this, 'posts_join_meta' ));
        // Add the taxonomy tables to the mange_posts query 
        add_filter('posts_join', array(&$this, 'posts_join_taxonomy' ));
        // Filter by eligible contributor_type
        add_filter('posts_where', array(&$this, 'posts_contributor_type_where' ));
        // Workaround to show posts with EF custom statuses when post_status=='all'
        add_filter('posts_where', array(&$this, 'add_ef_custom_statuses_where_all_filter' ), 20);
        
	}
    
    /**
     *   Add columns to the manage posts listing.
     *   Wordpress calls this and passes the list of columns.
    */ 
    function add_manage_post_columns($posts_columns){
		global $assignment_desk;
        // TODO - Specify the column order
        $custom_fields_to_add = array();
        $custom_fields_to_add[_('_ad_assignment_status')] = _('Assignment Status');

		/* Disabled 11/11/10 until we can find a better way to manage which metadata appears in columns ^DB
		if ($assignment_desk->edit_flow_enabled()) {
			$custom_fields_to_add[_('_ef_duedate')] = __('Due Date');
			$custom_fields_to_add[_('_ef_location')] = __('Location');
		} */
		if($assignment_desk->coauthors_plus_exists()){
		    $custom_fields_to_add[_('_ad_participants')] = __('Participants');
		}
		if(current_user_can($assignment_desk->define_editor_permissions)){
		    $custom_fields_to_add[_('_ad_user_type')] = _('Contributor Types');
		    $custom_fields_to_add[_('_ad_pitched_by_timestamp')] = __('Age');
		    $custom_fields_to_add[_('_ad_volunteers')] = __('Volunteers');
	    }
	    $custom_fields_to_add[_('_ad_votes_total')] = _('Votes');

        foreach ($custom_fields_to_add as $field => $title) {
            $posts_columns["$field"] = $title;
        } 
        return $posts_columns;
    }
    
    function handle_ad_user_type_column( $column_name, $post_id ) {
      global $assignment_desk;
      
      if ( $column_name == _( '_ad_user_type' ) ) {
		$participant_types = $assignment_desk->custom_taxonomies->get_user_types_for_post($post_id);
		echo $participant_types['display'];
      }
      
    }
    
    /**
     *  Display the Edit-Flow Due Date custom field in the manage_posts view.
     */
    function handle_ef_duedate_column($column_name, $post_id){
        if ( $column_name == _('_ef_duedate') ){
            // Get the due date, format it and echo.
            $due_date = get_post_meta($post_id, '_ef_duedate', true);
            if($due_date){
                echo strftime("%b %d, %Y", $due_date);
            }
            else {
                _e('None listed');
            }
        }
    }

    /**
     *  Display the Edit-Flow Location custom field in the manage_posts view.
     */
	function handle_ef_location_column($column_name, $post_id){
        if ( $column_name == _('_ef_location') ){
            // Get the due date, format it and echo.
            $location = get_post_meta($post_id, '_ef_location', true);
            if($location){
             	echo $location;
            }
            else {
                _e('None');
            }
        }
    }
    
    /**
     *  Display the assignment_status custom field in the manage_posts view.
     */
    function handle_ad_assignment_status_column($column_name, $post_id){
		global $assignment_desk;
        if ( $column_name == _('_ad_assignment_status') ) {
            $current_status = wp_get_object_terms($post_id, $assignment_desk->custom_taxonomies->assignment_status_label);
			if ($current_status) {
				echo $current_status[0]->name;
			}
			else {
			    _e('None');
			}
        }
    }
    
    /**
     * Show participants who have accepted by role on the manage_posts view.
     */
    function handle_ad_participants_column($column_name, $post_id){
        global $assignment_desk;
        
        if ($column_name == _('_ad_participants') ) {
            $user_roles = $assignment_desk->custom_taxonomies->get_user_roles(array('order' => "-name"));
            $at_least_one_user = false;
            foreach($user_roles as $user_role){
                $participants = get_post_meta($post_id, "_ad_participant_role_$user_role->term_id", true);
                if($participants){
                    $links = '';
                    foreach($participants as $user => $status){
                        if($status == 'accepted'){
                            $at_least_one_user = true;
                            $user = get_userdata($user);
                            $user_name = ($user->display_name)? $user->display_name : $user->user_login;
                            $links .= "<a href='" . admin_url() . "user-edit.php?user_id=$user->ID'>$user_name</a> ";
                        }
                    }
                    // Print the role header and user links
                    if($links){
                        echo "<p><b>$user_role->name:</b> $links </p>";
                    }
                }
            }
            if (!$at_least_one_user) {
                _e('None assigned');
            }
        }
    }
    
    /**
     * Show the age of a post that started as a pitch.
     */
    function handle_ad_pitched_by_timestamp_column($column_name, $post_id){
        global $assignment_desk, $wpdb;
        $volunteers = 0;
        if ( $column_name == _('_ad_pitched_by_timestamp') ) {
            $timestamp = (int)get_post_meta($post_id, '_ad_pitched_by_timestamp', true);
            if ( !$timestamp ) {
                $age = _("Not a pitch");
            }
            else {
                $age = human_time_diff($timestamp, current_time('timestamp'));   
            }
            echo "<span class='_ad_pitched_by_timestamp'>$age</span>";
        } 
    }

    /**
     * Show users who have volunteered for this post.
     */
    function handle_ad_volunteers_column($column_name, $post_id){
        global $assignment_desk, $wpdb;
        $volunteers = 0;
        if ( $column_name == _('_ad_volunteers') ) {
            $volunteers = get_post_meta($post_id, '_ad_total_volunteers', true);
            if ( !$volunteers ) {
                update_post_meta($post_id, '_ad_total_volunteers', 0);
                $volunteers = 0;
            }
            echo "<span class='ad-volunteer-count'>$volunteers</span>";
        } 
    }
    
    /**
     * Show the total votes for this post.
     */
    function handle_ad_votes_total($column_name, $post_id){
        if ( $column_name == _('_ad_votes_total') ) {
            $votes = get_post_meta($post_id, '_ad_votes_total', true);
            if ( !$votes ) {
                $votes = 0;
            }
            echo $votes;
        }
    }
    
    /**
     * Add a dropdown to filter posts by eligible user_types.
     */
    function add_eligible_contributor_type_filter() {
        global $assignment_desk;
        $user_types = $assignment_desk->custom_taxonomies->get_user_types();
    ?>
        <select name='ad-eligible-user-type' id='ad-user-type-select' class='postform'>
            <option value="">Show all eligible types</option>
            <?php 
            foreach($user_types as $user_type){
                echo "<option value='$user_type->term_id'>$user_type->name</option>";
            }
            ?>
        </select>
    <?php
    }
    
    function add_sortby_option(){
        global $assignment_desk;
		if ( isset( $_GET['ad-sortby'] ) ) {
			$sortby_filter = $_GET['ad-sortby'];
		} else {
			$sortby_filter = '';
		}
    ?>
        <label for"ad-sortby-select"><?php _e('Sort by'); ?>:</label>
        <select name='ad-sortby' id='ad-sortby-select' class='postform'>
            <option value="">None</option>
            <option value="_ad_pitched_by_timestamp" <?php echo ( $sortby_filter == '_ad_pitched_by_timestamp')? 'selected': ''?>>Age</option>
            <option value="_ad_votes_total" <?php echo ( $sortby_filter == '_ad_votes_total' )? 'selected': ''?>>Votes</option>
            <option value="_ad_total_volunteers" <?php echo ( $sortby_filter == '_ad_total_volunteers')? 'selected': ''?>>Volunteers</option>
        </select>
        <input type="hidden" name="ad-sortby-reverse" value="<?php echo ($_GET['ad-sortby-reverse'])? '': '1'; ?>">
<?php
    }
    
    function parse_query_sortby( $query ){
        global $pagenow;
        if (is_admin() && $pagenow == 'edit.php' && isset($_GET['ad-sortby'])  && $_GET['ad-sortby'] !='None')  {
            $order = ($_GET['ad-sortby-reverse'])? 'ASC': 'DESC';
            $query->query_vars['orderby'] = 'meta_value';
            $query->query_vars['meta_key'] = $_GET['ad-sortby'];
            $query->query_vars['order'] = $order;
        }
    }
    
    /**
     * join the posts SQL query on the postmeta table.
     */
    function posts_join_meta($join){
        global $wpdb, $pagenow;
        if(is_admin() && $pagenow == 'edit.php'){
            if ( isset( $_GET['ad-eligible-user-type'] ) ){
                $join .= " LEFT JOIN $wpdb->postmeta ON($wpdb->posts.ID = $wpdb->postmeta.post_id) ";
            }
        }
        return $join;
    }
    
    /**
     * Join the posts SQL quert to the term/taxonomy tables.
     * Added in order to query by assignment status.
     */
    function posts_join_taxonomy($join){
        global $wpdb, $pagenow;
        if(is_admin() && $pagenow == 'edit.php'){
            if( isset( $_GET['ad-assignment-status'] ) ) {
                $join .= "LEFT JOIN $wpdb->term_relationships ON($wpdb->posts.ID = $wpdb->term_relationships.object_id)
                          LEFT JOIN $wpdb->term_taxonomy ON($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)
                          LEFT JOIN $wpdb->terms ON($wpdb->term_taxonomy.term_id = $wpdb->terms.term_id)";
            }
        }
        return $join;
    }
    
    /**
     * Workaround for edit-flow (as of v0.5.2)
     * Query for all custom post_statuses if filtering for 'all'
     * @todo - When edit-flow includes this check the edit-flow version and enable/disable
     */ 
    function add_ef_custom_statuses_where_all_filter($where){
        global $wpdb, $assignment_desk;
        
		if ( !$assignment_desk->edit_flow_enabled() ) {
			return $where;
		} else {
			global $edit_flow;
		}
		
        if( isset( $_GET['post_status'] ) && ( $_GET['post_status'] == 'all' || $_POST['post_status'] == 'all' ) ) {			
    		$custom_statuses = $edit_flow->custom_status->get_custom_statuses();
    		//insert custom post_status where statements into the existing the post_status where statements - "post_status = publish OR"
    		//the search string
    		$search_string = $wpdb->posts.".post_status = 'publish' OR ";

    		//build up the replacement string
    		$replace_string = $search_string;
    		foreach($custom_statuses as $status) {
    			$replace_string .= $wpdb->posts.".post_status = '".$status->slug."' OR "; 
    		}

    		$where = str_replace($search_string, $replace_string, $where);
		}
		return $where;
    }
    
    /**
    * Modify the SQL where clause to include posts where that type is eligible.
    */
    function posts_contributor_type_where( $where ){
        global $assignment_desk, $wpdb, $pagenow;
        if(is_admin() && $pagenow == 'edit.php'){
            if( isset( $_GET['ad-eligible-user-type'] ) ){
                $where .= " AND $wpdb->postmeta.meta_key = '_ad_participant_type_{$_GET['ad-eligible-user-type']}'
                            AND $wpdb->postmeta.meta_value = 'on' ";
            }
        }
        return $where;
    }

}

} // end if(!class_exists)
?>