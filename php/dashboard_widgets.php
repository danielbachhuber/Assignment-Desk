<?php

require_once(ASSIGNMENT_DESK_DIR_PATH . "/php/utils.php");

/*
* This class manages the widgets that appear in the Wordpress Administration Dashboard.
* The assignment_desk class holds an instance of this class as a member.
*/
class ad_dashboard_widgets {
    
    function init(){
        add_action('wp_dashboard_setup', array(&$this, 'add_dashboard_widget'));
        add_action('admin_init', array(&$this, 'respond_to_story_invite'));
    }
       
    function add_dashboard_widget () {
        global $assignment_desk, $current_user;
        wp_add_dashboard_widget('ad_assignments', 'Assignment Desk', array(&$this, 'widget'));
    }

    /**
     * Return the number of objects associated with this status
     * $status is a term
     */
	function count_posts_by_assignment_status($status){
		global $assignment_desk, $wpdb;
		$count = $wpdb->get_var($wpdb->prepare("SELECT count FROM $wpdb->term_taxonomy 
												WHERE taxonomy = '%s' AND term_id = %d", 
												$assignment_desk->custom_taxonomies->assignment_status_label,
												$status->term_id));
		$count = $count ? $count : 0;
		return $count;
	}
	
	function count_user_posts_by_assignment_status($status){
	    global $current_user, $wpdb, $assignment_desk;
	    get_currentuserinfo();
	    
	    $counts = array();
	    
	    if ( ! $assignment_desk->coauthors_plus_exists() ){
            return $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts
                                    LEFT JOIN $wpdb->term_relationships ON($wpdb->posts.ID = $wpdb->term_relationships.object_id)
                                    LEFT JOIN $wpdb->term_taxonomy ON($wpdb->term_taxonomy.term_taxonomy_id = $wpdb->term_relationships.term_taxonomy_id)
                                    WHERE $wpdb->posts.post_author = '$current_user->ID' 
                                     AND $wpdb->posts.post_status != 'publish'
                                     AND $wpdb->posts.post_status != 'inherit' 
                                     AND $wpdb->posts.post_status != 'trash'
             						 AND $wpdb->posts.post_status != 'auto-draft'
                                     AND $wpdb->term_taxonomy.taxonomy = '$assignment_desk->custom_taxonomies->assignment_status_label'
                                     AND $wpdb->term_taxonomy.term_id = $status->term_id ");
	    }
	    else {
	        $count = 0;
	        $posts = $wpdb->get_results("SELECT * FROM $wpdb->posts 
	                                LEFT JOIN $wpdb->term_relationships ON($wpdb->posts.ID = $wpdb->term_relationships.object_id)
                                    LEFT JOIN $wpdb->term_taxonomy ON($wpdb->term_taxonomy.term_taxonomy_id = $wpdb->term_relationships.term_taxonomy_id)
                                    LEFT JOIN $wpdb->terms ON($wpdb->terms.term_id = $wpdb->term_taxonomy.term_id)
                	                WHERE $wpdb->posts.post_status != 'publish'
                                      AND $wpdb->posts.post_status != 'inherit' 
                                      AND $wpdb->posts.post_status != 'trash'
                    				  AND $wpdb->posts.post_status != 'auto-draft'
                    				  AND $wpdb->term_taxonomy.taxonomy = 'author'
                                      AND $wpdb->terms.name = '$current_user->user_login'");
            foreach ( $posts as $post ){
                $post_assignment_status = wp_get_object_terms($post->ID, $assignment_desk->custom_taxonomies->assignment_status_label);
                if ( $post_assignment_status && $post_assignment_status[0]->term_id == $status->term_id ){
                    $count++;
                }
            }
            return $count;
	    }
	    
	}
   
    function widget() {
        global $assignment_desk, $current_user, $wpdb;

        get_currentuserinfo();
        $assignment_statuses = $assignment_desk->custom_taxonomies->get_assignment_statuses();
        
		if($_REQUEST['ad-dashboard-editor-messages']){
            foreach($_REQUEST['ad-dashboard-assignment-messages'] as $messages){
                echo "<div class='message info'>$message</div>";
            }
        }
        ?>
        <p class="sub">By assignment status</p>
        <div class="table">
        <table>
            <tbody>
            <?php 
            if ( current_user_can($assignment_desk->define_editor_permissions) ) { 
                foreach ( $assignment_statuses as $assignment_status ) {
                    $count = $this->count_posts_by_assignment_status($assignment_status);
                    if ( $count ) {
                        $url = admin_url() . "/edit.php?ad-assignment-status=$assignment_status->term_id";
                        echo "<tr><td class='b'><a href='$url'>" . $count . "</a></td>";
                        echo "<td class='b t'><a href='$url'>$assignment_status->name</a></td></tr>";
                    }
                }
                $this_month_url = admin_url() . '/edit.php?post_status=publish&monthnum=' . date('M');
                $q = new WP_Query( array('post_status' => 'publish', 'monthnum' => date('M')));
                echo "<tr><td class='b'><a href='$this_month_url'>$q->found_posts</a></td>";
                echo "<td class='b t'><a href='$this_month_url'>" . _('Published this month', 'assignment-desk') . "</a></td></tr>";
            }
            else {
                foreach ( $assignment_statuses as $assignment_status ) {
                    $count = $this->count_user_posts_by_assignment_status($assignment_status);
                    if ( $count ) {
                        $url = admin_url() . "/edit.php?ad-assignment-status=$assignment_status->term_id";
                        echo "<tr><td class='b'><a href='$url'>" . $count . "</a></td>";
                        echo "<td class='b t'><a href='$url'>$assignment_status->name</a></td></tr>";
                    }
                }
            }
                ?>
            </tbody>
        </table>
        </div>
        
        <br>
<?php
       
        $pending_posts = array();

        // Find all of the posts this user participates in.
        $participant_posts = $wpdb->get_results("SELECT post_id FROM $wpdb->postmeta 
                                                  WHERE meta_key = '_ad_participant_{$current_user->ID}'
                                                  ORDER BY post_id");
        
        if ( ! $participant_posts ){
            $participant_posts = array();
        }
        
        $roles = $assignment_desk->custom_taxonomies->get_user_roles();
        $max_pending = 5;
        
        foreach($participant_posts as $post ){
            foreach($roles as $user_role){
                // Get all of the roles this user has for this post
                $participant_record = get_post_meta($post->post_id, "_ad_participant_role_$user_role->term_id", true);
                if($participant_record) {
                    foreach ($participant_record as $user_id => $status ){
                        if( $user_id == $current_user->ID && $status == 'pending' ){
                           $pending_posts[] = array($post->post_id, $user_role);
                           $max_pending--;
                        }
                    }
                }
            }
            if(!$max_pending){
                break;
            }
        }
        $count_pending = count($pending_posts);
        if ( $count_pending ) {
            echo "<p class='sub'>$count_pending pending assignment";
            echo ($count_pending != 1)? 's': ''; 
            echo "</p>";
            echo "<div class='table'><table><tbody>";
            foreach($pending_posts as $pending) {
                $post = get_post($pending[0]);
                echo "<tr>";
                echo "<td>{$post->post_title} | {$pending[1]->name}</td>";
                echo "<td><a class='button' href='" . admin_url() . "index.php?participant_response=accepted&post_id=$post->ID&role_id={$pending[1]->term_id}'>Accept</a> ";
                echo "<a class='button' href='" . admin_url() . "index.php?participant_response=declined&post_id=$post->ID&role_id={$pending[1]->term_id}'>Decline</a></td>";
                echo "</tr>";  
            }
            echo "</table></div>";
        }
   }
   
   function respond_to_story_invite(){
       global $current_user, $assignment_desk, $coauthors_plus, $user_ID;
       
       $response = $_GET['participant_response'];
       $post_id = (int)$_GET['post_id'];
       $role_id = (int)$_GET['role_id'];
          
       get_currentuserinfo();
       if (!$current_user->ID || $user_ID != $post_id) {
           $_REQUEST['ad-dashboard-assignment-messages'][] = _('Unauthorized assignment response. This is fishy.');
       }
       $_REQUEST['ad-dashboard-assignment-messages'] = array();
       
       if ($response && $post_id && $role_id){
           $participant_record = get_post_meta($post_id, "_ad_participant_role_$role_id", true);
           // This will not evaluate to true unless the user is currently pending for this role on this post.
           if($participant_record && $participant_record[$current_user->ID] == 'pending'){
               $participant_record[$current_user->ID] = $response;
               if($response == 'accepted'){
                   $_REQUEST['ad-dashboard-assignment-messages'][] = _('Thank you.');
                   // Add as a co-author
                   if($assignment_desk->coauthors_plus_exists()){
                       $coauthors_plus->add_coauthors($post_id, array($current_user->user_login), true);
                   }
                   $user_participant = get_post_meta($post_id, "_ad_participant_$current_user->ID", true);
                   if(!$user_participant){
                       $user_participant = array();
                   }
                   $user_participant[] = $role_id;
                   update_post_meta($post_id, "_ad_participant_$current_user->ID", $user_participant);
               }
               else if($response == 'declined'){
                   $_REQUEST['ad-dashboard-assignment-messages'][] = _('Sorry!');
               }
           }
           update_post_meta($post_id, "_ad_participant_role_$role_id", $participant_record);
       }
   }
}
?>
