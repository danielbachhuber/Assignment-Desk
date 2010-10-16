<?php

require_once("utils.php");

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
	
	/**
	 * Count the unpublished posts assigned to the current user.
	 * Users coauthors if enabled.
	 * @param term $status The term from the assignment_status taxonomy.
	 * @return int the number of unpublished posts of that assignment_status assigned to the current user
	 */
	function count_user_posts_by_assignment_status( $status ) {
	    global $current_user, $wpdb, $assignment_desk;
	    get_currentuserinfo();
	 
	    $count = 0;
	    // Query for all the unpublished posts where $current_user is a coauthor.
	    // Then tally up the count for the status.
	    if ( $assignment_desk->coauthors_plus_exists() ){
            $posts = $wpdb->get_results("SELECT * FROM $wpdb->posts 
	                                LEFT JOIN $wpdb->term_relationships ON($wpdb->posts.ID = $wpdb->term_relationships.object_id)
                                    LEFT JOIN $wpdb->term_taxonomy 
                                        ON($wpdb->term_taxonomy.term_taxonomy_id = $wpdb->term_relationships.term_taxonomy_id)
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
	    }
	    else {
	        // Slightly easier without coauthors.
	        // Just query for the count.
	        $count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts
                                    LEFT JOIN $wpdb->term_relationships ON($wpdb->posts.ID = $wpdb->term_relationships.object_id)
                                    LEFT JOIN $wpdb->term_taxonomy 
                                        ON($wpdb->term_taxonomy.term_taxonomy_id = $wpdb->term_relationships.term_taxonomy_id)
                                    WHERE $wpdb->posts.post_author = '$current_user->ID' 
                                     AND $wpdb->posts.post_status != 'publish'
                                     AND $wpdb->posts.post_status != 'inherit' 
                                     AND $wpdb->posts.post_status != 'trash'
             						 AND $wpdb->posts.post_status != 'auto-draft'
                                     AND $wpdb->term_taxonomy.taxonomy = '{$assignment_desk->custom_taxonomies->assignment_status_label}'
                                     AND $wpdb->term_taxonomy.term_id = $status->term_id ");
	    }
	    return $count;
	    
	}
   
    /**
    * Display the Assignment Desk dashboard widget.
    */
    function widget() {
        global $assignment_desk, $current_user, $wpdb;

        get_currentuserinfo();
        $assignment_statuses = $assignment_desk->custom_taxonomies->get_assignment_statuses();

		if ( $_REQUEST['ad-dashboard-assignment-messages'] ) {
            foreach( $_REQUEST['ad-dashboard-assignment-messages'] as $message ) {
                echo "<div class='message info'>$message</div>";
            }
        }
        ?>
        <p class="sub"><?php _e('By assignment status'); ?></p>
        <div class="table">
        <table>
            <tbody>
            <?php
            $counts = array();
            $total_unpublished_assignments = 0;
            
            /* If the $current_user is editor or higher display the total count for each assignment_status across the whole blog.
             * If they don't have editor permissions show the number of posts assigned to the $current_user.
             */

            foreach ( $assignment_statuses as $assignment_status ) {
                if ( current_user_can($assignment_desk->define_editor_permissions) ) {
                    // Count all posts with a certain status
                    $counts[$assignment_status->term_id] = $this->count_posts_by_assignment_status($assignment_status);
                }
                else {
                    // Count all posts that this user can edit with a certain status
                    $counts[$assignment_status->term_id] = $this->count_user_posts_by_assignment_status($assignment_status);
                }
                $total_unpublished_assignments += $counts[$assignment_status->term_id];
            }
            if ( $total_unpublished_assignments ) {
                foreach ( $assignment_statuses as $assignment_status ) {
                    // if ( $counts[$assignment_status->term_id] ) {
                        $url = admin_url() . "edit.php?ad-assignment-status=$assignment_status->term_id";
                        echo "<tr><td class='b'><a href='$url'>" . $counts[$assignment_status->term_id] . "</a></td>";
                        echo "<td class='b t'><a href='$url'>$assignment_status->name</a></td></tr>";
                    // }
                }
            }
            else {
                echo "<tr><td>" . _('No assigned stories.') . "</td><td></td></tr>";
            }
            ?>
            </tbody>
        </table>
        </div>

        <?php if ( current_user_can($assignment_desk->define_editor_permissions) ) : ?>
        <p class="sub"><?php _e('Historical'); ?></p>
        <div class="table">
        <table>
            <tbody>
<?php
                $this_month_url = admin_url() . 'edit.php?post_status=publish&monthnum=' . date('M');
                $q = new WP_Query( array('post_status' => 'publish', 'monthnum' => date('M')));
                echo "<tr><td class='b'><a href='$this_month_url'>$q->found_posts</a></td>";
                echo "<td class='b t'><a href='$this_month_url'>" . __('Published this month') . "</a></td></tr>";
?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
<?php
       
        $pending_posts = array();

        // Find all of the posts this user participates in.
        $participant_posts = $wpdb->get_results("SELECT post_id FROM $wpdb->postmeta 
                                                  WHERE meta_key = '_ad_participant_{$current_user->ID}'
                                                  ORDER BY post_id");
        
        if ( !$participant_posts ){
            $participant_posts = array();
        }
        
        $roles = $assignment_desk->custom_taxonomies->get_user_roles();
        $max_pending = 5;
        
        foreach( $participant_posts as $post ){
            foreach( $roles as $user_role ) {
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
            echo "<br>";
            echo "<p class='sub'>$count_pending pending assignment";
            echo ($count_pending != 1)? 's': ''; 
            echo "</p>";
            echo "<div class='table'><table><tbody>";
            foreach($pending_posts as $pending) {
                $post = get_post($pending[0]);
                echo "<tr><form action='' method='POST'>";
                echo "<td>{$post->post_title} | {$pending[1]->name}</td>";
				echo "<input type='hidden' name='post_id' value='$post->ID' />";
				echo "<input type='hidden' name='role_id' value='{$pending[1]->term_id}' />";				
                echo "<td><input type='submit' name='assignment_desk_response' class='button' value='Accept' /> ";
                echo "<input type='submit' name='assignment_desk_response' class='button' value='Decline' />";
                
                echo "<a onclick=\"javascript:jQuery('#ad-{$post->ID}-summary').slideToggle();\">Details</a></td>";
                echo "</form></tr>";
                echo "<tr><td colspan='2'>";
                echo "<div id='ad-{$post->ID}-summary' style='display:none'>";

                $summary = get_post_meta($post->ID, '_ef_description', true);
                if ( !$summary && $post->post_excerpt ) {
                    $summary = $post->post_excerpt;
                }
                if ( !$summary && $post->post_content ) {
                    $summary = $post->post_content;
                }
                echo "<p>" . _('Summary') . ": $summary</p>";
                
                if ( $assignment_desk->edit_flow_exists() ){
                    $duedate = get_post_meta($post->ID, '_ef_duedate', true);
                    if ( $duedate ) {
                        $duedate = ad_format_ef_duedate($duedate);
                    }
                    else {
                        $duedate = _('None assigned');
                    }
                    echo "<p>" . _('Due Date ') . ": $duedate</p>";
                }
                echo "</div></td></tr>";
            }
            echo "</table></div>";
        }
   }
   
   /**
    * Confirm or decline a story assignment.
    */
   function respond_to_story_invite(){
       global $current_user, $assignment_desk, $coauthors_plus, $user_ID;
       
       $response = $_POST['assignment_desk_response'];
       $post_id = (int)$_POST['post_id'];
       $role_id = (int)$_POST['role_id'];
          
       get_currentuserinfo();
       if ( !$current_user->ID || !$user_ID || !$post_id || !$role_id ) {
           $_REQUEST['ad-dashboard-assignment-messages'][] = _('Unauthorized assignment response. This is fishy.');
       }
       $_REQUEST['ad-dashboard-assignment-messages'] = array();
       
       if ( $response && $post_id && $role_id ) {
           $participant_record = get_post_meta($post_id, "_ad_participant_role_$role_id", true);

           // Are we waiting for a response from this user for this post/role?
           if ( $participant_record && $participant_record[$current_user->ID] == 'pending' ) {
               $participant_record[$current_user->ID] = $response;

               if ( $response == 'Accept' ) {
                   // Add as a coauthor
                   if ( $assignment_desk->coauthors_plus_exists() ) {
                       $coauthors_plus->add_coauthors($post_id, array($current_user->user_login), true);
                   }
                   // Add as author
                   else {
                       wp_update_post(array( 'ID' => $post_id, $author => $current_user->user_login ));
                   }
                   $_REQUEST['ad-dashboard-assignment-messages'][] = _('Thank you.');
                   $user_participant = get_post_meta($post_id, "_ad_participant_$current_user->ID", true);
                   if ( !$user_participant ) {
                       $user_participant = array();
                   }
                   $user_participant[] = $role_id;
                   update_post_meta($post_id, "_ad_participant_$current_user->ID", $user_participant);
               } else if( $response == 'Decline' ) {
                   $_REQUEST['ad-dashboard-assignment-messages'][] = _("Sorry.");
               }
           }
           update_post_meta($post_id, "_ad_participant_role_$role_id", $participant_record);
       }
   }
}
?>
