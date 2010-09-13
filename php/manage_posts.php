<?php
if (!class_exists('ad_manage_posts')) {

class ad_manage_posts {
    
    function __construct(){ 
    }

	function init() {
		global $assignment_desk, $pagenow;
		add_filter('manage_posts_columns', array(&$this, 'add_manage_post_columns'));
		add_action('manage_posts_custom_column', array(&$this, 'handle_ad_assignment_status_column'), 10, 2);
		add_action('manage_posts_custom_column', array(&$this, 'handle_ad_votes_total'), 10, 2);
		
		if ($assignment_desk->edit_flow_exists()) {
        	add_action('manage_posts_custom_column', array(&$this, 'handle_ef_duedate_column'), 10, 2);
			add_action('manage_posts_custom_column', array(&$this, 'handle_ef_location_column'), 10, 2);
		}
		if ($assignment_desk->coauthors_plus_exists()) {
		    add_action('manage_posts_custom_column', array(&$this, 'handle_ad_participants_column'), 10, 2);
		}
		
		if(current_user_can($assignment_desk->define_editor_permissions)){
		    add_action('manage_posts_custom_column', array(&$this, 'handle_ad_user_type_column'), 10, 2);
    		add_action('manage_posts_custom_column', array(&$this, 'handle_ad_volunteers_column'), 10, 2);
		}
		
		// Filter by eligible contributor types
        add_action('restrict_manage_posts', array(&$this, 'add_eligible_contributor_type_filter'));
        
		// Sorting
		add_action('restrict_manage_posts', array(&$this, 'add_sortby_option'));
        add_action('parse_query', array(&$this, 'parse_query_sortby'));

        // Filter by assignment status
        add_action('restrict_manage_posts', array(&$this, 'add_assignment_status_filter'));

        // Add postmeta to the manage_posts query
        add_filter('posts_join', array(&$this, 'posts_join_meta' ));
        // Add the taxonomy tables to the mange_posts query 
        add_filter('posts_join', array(&$this, 'posts_join_taxonomy' ));
        // Filter by eligible contributor_type
        add_filter('posts_where', array(&$this, 'posts_contributor_type_where' ));
        // Workaround to show posts with EF custom statuses when post_status=='all'
        add_filter('posts_where', array(&$this, 'add_ef_custom_statuses_where_all_filter' ), 20);
        // Filter by assignment_status
        add_filter('posts_where', array(&$this, 'add_ad_assignment_statuses_where' ), 20);
        
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

		if ($assignment_desk->edit_flow_exists()) {
			$custom_fields_to_add[_('_ef_duedate')] = __('Due Date');
			$custom_fields_to_add[_('_ef_location')] = __('Location');
		}
		if($assignment_desk->coauthors_plus_exists()){
		    $custom_fields_to_add[_('_ad_participants')] = __('Participants');
		}
		if(current_user_can($assignment_desk->define_editor_permissions)){
		    $custom_fields_to_add[_('_ad_user_type')] = _('Contributor Types');
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
      *  Wordpress doesn't know how to resolve the column name to a post attribute 
      *  so this function is called with the column name and the id of the post.
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
     * Add the assignment_status to the manage_posts view.
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
                            $links .= "<a href='" . admin_url() . "/user-edit.php?user_id=$user->ID'>$user_name</a> ";
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
    
    /**
     * Add links to filter posts by assignment_status.
     */
    function add_assignment_status_filter() {
        global $assignment_desk;
        $assignment_statuses = $assignment_desk->custom_taxonomies->get_assignment_statuses();
    
        echo "<div><ul class='subsubsub'>";
        
        $class = '';
        if ( ! $_GET['ad-assignment-status'] ) {
            $class = 'current';
        }
        $status_links = array("<a href='?' class='$class'>All</a>");
        foreach($assignment_statuses as $assignment_status){
            $class = '';
            if($_GET['ad-assignment-status'] == $assignment_status->term_id){
                $class = 'current';
            }
            $status_links[]= "<a href='?ad-assignment-status=$assignment_status->term_id' class='$class'>{$assignment_status->name}</a>";            
        }
        echo implode(' | ', $status_links) . "</div> <br style='clear:both'>";
    }
    
    function add_sortby_option(){
        global $assignment_desk;
    ?>
        <label for"ad-sortby-select"><?php _e('Sort by'); ?>:</label>
        <select name='ad-sortby' id='ad-sortby-select' class='postform'>
            <option value="">None</option>
            <option value="votes" <?php echo ($_GET['ad-sortby'] == 'votes')? 'selected': ''?>>Votes</option>
            <option value="volunteers" <?php echo ($_GET['ad-sortby'] == 'volunteers')? 'selected': ''?>>Volunteers</option>
            <?php if( $assignment_desk->edit_flow_exists() ): ?>
                <option value="_ef_duedate" <?php echo ($_GET['ad-sortby'] == '_ef_duedate')? 'selected': ''?>>Due Date</option>
            <?php endif; ?>
        </select>
        <input type="hidden" name="ad-sortby-reverse" value="<?php echo ($_GET['ad-sortby-reverse'])? '': '1'; ?>">
<?php
    }
    
    function parse_query_sortby( $query ){
        global $pagenow;
        if (is_admin() && $pagenow == 'edit.php' && isset($_GET['ad-sortby'])  && $_GET['ad-sortby'] !='None')  {
            $orderby = 'meta_value';
            $meta_key = '';
            $oder = '';
            switch($_GET['ad-sortby']){
                case 'votes':
                    $order = ($_GET['ad-sortby-reverse'])? 'ASC': 'DESC';
                    $meta_key = '_ad_votes_total';
                    break;
                case 'volunteers':
                    $order = ($_GET['ad-sortby-reverse'])? 'ASC': 'DESC';
                    $meta_key = '_ad_total_volunteers';
                    break;
                case '_ef_duedate':
                    $order = ($_GET['ad-sortby-reverse'])? 'DESC': 'ASC';
                    $meta_key = '_ef_duedate';
                    break;
            }
            if ( $meta_key ) {
                $query->query_vars['orderby'] = $orderby;
                $query->query_vars['meta_key'] = $meta_key;
                $query->query_vars['order'] = $order;
            }
        }
    }
    
    /**
     * join the posts SQL query on the postmeta table.
     */
    function posts_join_meta($join){
        global $wpdb, $pagenow;
        if(is_admin() && $pagenow == 'edit.php'){
            if($_GET['ad-eligible-user-type']){
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
            if($_GET['ad-assignment-status']){
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
     * @todo - Check the edit-flow version and enable when <= 0.5.2
     */ 
    function add_ef_custom_statuses_where_all_filter($where){
        global $wpdb, $edit_flow;
        
        if($_GET['post_status'] == 'all' || $_POST['post_status'] == 'all') {			
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
            if($_GET['ad-eligible-user-type']){
                $where .= " AND $wpdb->postmeta.meta_key = '_ad_participant_type_{$_GET['ad-eligible-user-type']}'
                            AND $wpdb->postmeta.meta_value = 'on' ";
            }
        }
        return $where;
    }
    
    /**
     * Modify the where SQL clause to filter by assignment_status 
     */
    function add_ad_assignment_statuses_where($where){
        global $assignment_desk, $wpdb, $pagenow;
        if(is_admin() && $pagenow == 'edit.php'){
            if($_GET['ad-assignment-status']){
                $where .= " AND $wpdb->terms.term_id = {$_GET['ad-assignment-status']}";
            }
        }
        return $where;
    }
}

} // end if(!class_exists)
?>