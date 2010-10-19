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
	 * Get the most recent unpublished posts for the current user
	 * @author danielbachhuber
	 * @return object $the_posts All of the posts
	 */
	function get_user_upcoming_posts( $args = null ) {
		global $current_user, $wpdb, $assignment_desk;
		
	    get_currentuserinfo();
			
		$defaults = array(
						'user_id' => $current_user->ID,
						'user_login' => $current_user->user_login,
						'showposts' => 5,
					);
		if ( $args ) {
			$args = array_merge( $defaults, $args );
		} else {
			$args = $defaults;
		}
		
		if ( $assignment_desk->coauthors_plus_exists() ){
            $the_posts = $wpdb->get_results("SELECT * FROM $wpdb->posts 
	                                LEFT JOIN $wpdb->term_relationships ON($wpdb->posts.ID = $wpdb->term_relationships.object_id)
                                    LEFT JOIN $wpdb->term_taxonomy 
                                        ON($wpdb->term_taxonomy.term_taxonomy_id = $wpdb->term_relationships.term_taxonomy_id)
                                    LEFT JOIN $wpdb->terms ON($wpdb->terms.term_id = $wpdb->term_taxonomy.term_id)
                	                WHERE $wpdb->posts.post_status != 'publish'
                                      AND $wpdb->posts.post_status != 'inherit' 
                                      AND $wpdb->posts.post_status != 'trash'
                    				  AND $wpdb->posts.post_status != 'auto-draft'
                    				  AND $wpdb->term_taxonomy.taxonomy = 'author'
                                      AND $wpdb->terms.name = '{$args['user_login']}' LIMIT {$args['showposts']};");
		} else {
	        $the_posts = $wpdb->get_var("SELECT * FROM $wpdb->posts
                                    LEFT JOIN $wpdb->term_relationships ON($wpdb->posts.ID = $wpdb->term_relationships.object_id)
                                    LEFT JOIN $wpdb->term_taxonomy 
                                        ON($wpdb->term_taxonomy.term_taxonomy_id = $wpdb->term_relationships.term_taxonomy_id)
                                    WHERE $wpdb->posts.post_author = '{$args['user_id']}' 
                                     AND $wpdb->posts.post_status != 'publish'
                                     AND $wpdb->posts.post_status != 'inherit' 
                                     AND $wpdb->posts.post_status != 'trash'
             						 AND $wpdb->posts.post_status != 'auto-draft'
                                     AND $wpdb->term_taxonomy.taxonomy = '{$assignment_desk->custom_taxonomies->assignment_status_label}'
                                     AND $wpdb->term_taxonomy.term_id = $status->term_id LIMIT {$args['showposts']};");
	    }
	
		return $the_posts;
		
	}
   
    /**
    * Display the Assignment Desk dashboard widget
	* @todo Historical data
	* @todo $_GET implementation
	* @todo AJAX implementation
    */
    function widget() {
        global $assignment_desk, $current_user, $wpdb;

        get_currentuserinfo();
        $assignment_statuses = $assignment_desk->custom_taxonomies->get_assignment_statuses();

		$pending_posts = array();
		$upcoming_posts = array();
		$max_pending = 5;
		$max_upcoming = 5;

		// Find all of the posts this user participates in.
		$participant_posts = $wpdb->get_results("SELECT * FROM $wpdb->postmeta 
													WHERE meta_key = '_ad_participant_{$current_user->ID}'
													ORDER BY post_id LIMIT 20;");

		if ( !$participant_posts ){
			$participant_posts = array();
		}

		$roles = $assignment_desk->custom_taxonomies->get_user_roles();

		// 
		foreach ( $participant_posts as $post ) {
			foreach ( $roles as $user_role ) {
				// Get all of the roles this user has for this post
				$participant_record = get_post_meta($post->post_id, "_ad_participant_role_$user_role->term_id", true);
				if ( $participant_record ) {
					foreach ( $participant_record as $user_id => $status ) {
						if ( $user_id == $current_user->ID && $status == 'pending' && $max_pending ) {
							$pending_posts[] = array($post->post_id, $user_role);
							$max_pending--;
							$max_upcoming--;
	                    } else if ( $user_id == $current_user->ID && $status == 'accepted' && $max_upcoming ) {
								$upcoming_posts[$post->post_id]['roles'] = $user_role;
								$max_upcoming--;
						}
					}
				}
			}
		}
		echo "<div id='assignment-desk-post-list'>";
        if ( $pending_posts ) {
            foreach ( $pending_posts as $pending ) {
				echo "<div id='pending-assignment-{$pending[0]}' class='pending post assignment-desk-item'>";
                $post = get_post($pending[0]);
				if ( $assignment_desk->edit_flow_exists() ) {
					$post_status_object = get_term_by( 'slug', $post->post_status, 'post_status' );
					$post_status = $post_status_object->name;
				} else {
					if ( $pitch->post_status == 'draft' ) {
						$post_status = 'Draft';
					} else if ( $pitch->post_status == 'pending' ) {
						$post_status = 'Pending Review';
					}
				}
                echo "<h4><a href='" . admin_url() . "post.php?action=edit&post={$post->ID}'>{$post->post_title}</a> <span class='pending'>[{$post_status}]</span></h4>";
				$summary = get_post_meta($post->ID, '_ef_description', true);
                if ( !$summary ) {
					if ( $post->excerpt ) {
						$summary = $post->post_excerpt;
					} else if ( $post->post_content ) {
						$summary = substr( $post->post_content, 0, 155 ) . ' ...';
					}
                }
				if ( $summary ) {
					echo "<p class='summary'>$summary</p>";
				}
				// All of the relevant editorial metadata
				$description = get_post_meta( $post->ID, '_ef_description', true );
				$location = get_post_meta( $post->ID, '_ef_location', true );
				$duedate = get_post_meta( $post->ID, '_ef_duedate', true );
				$duedate = date_i18n( 'M d, Y', $duedate );
				if ( $description || $duedate || $location ) {
				    echo '<p class="meta">';
				}
				echo '<span class="ad-role">Role: ' . $pending[1]->name . '</span>&nbsp;&nbsp;&nbsp;';
				if ( $duedate ) {
				    echo '<span class="duedate">Due date: ' . $duedate . '</span>&nbsp;&nbsp;&nbsp;';	
				}
				if ( $location ) {
				    echo '<span class="location">Location: ' . $location . '</span> ';	
				}
				if ( $description || $duedate || $location ) {
				    echo '</p>';
				}
                echo "<p class='row-actions'>";	
				echo "<input type='hidden' class='assignment_desk_post_id' name='assignment_desk_post_id' value='{$post->ID}' />";
				echo "<input type='hidden' class='assignment_desk_role_id' name='assignment_desk_role_id' value='{$pending[1]->term_id}' />";
				echo "<a class='assignment_desk_response assignment_desk_accept' href='" . admin_url() . "?action=assignment_desk_accept&post_id={$post->ID}&role_id={$pending[1]->term_id}'>Accept</a> | <a class='assignment_desk_response assignment_desk_decline' href='" . admin_url() . "?action=assignment_desk_decline&post_id={$post->ID}&role_id={$pending[1]->term_id}'>Decline</a></p>";

				echo '</div>';
            }
        }
		if ( $upcoming_posts ) {
            foreach ( $upcoming_posts as $post_id => $roles ) {
				echo "<div id='post-{$pending[0]}' class='accepted post assignment-desk-item'>";
                $post = get_post( $post_id );
				if ( $assignment_desk->edit_flow_exists() ) {
					$post_status_object = get_term_by( 'slug', $post->post_status, 'post_status' );
					$post_status = $post_status_object->name;
				} else {
					if ( $pitch->post_status == 'draft' ) {
						$post_status = 'Draft';
					} else if ( $pitch->post_status == 'pending' ) {
						$post_status = 'Pending Review';
					}
				}
                echo "<h4><a href='" . admin_url() . "post.php?action=edit&post={$post->ID}'>{$post->post_title}</a> <span class='accepted'>[{$post_status}]</span></h4>";
				$summary = get_post_meta($post->ID, '_ef_description', true);
                if ( !$summary ) {
					if ( $post->excerpt ) {
						$summary = $post->post_excerpt;
					} else if ( $post->post_content ) {
						$summary = substr( $post->post_content, 0, 155 ) . ' ...';
					}
                }
				if ( $summary ) {
					echo "<p class='summary'>$summary</p>";
				}
				// All of the relevant editorial metadata
				$description = get_post_meta( $post->ID, '_ef_description', true );
				$location = get_post_meta( $post->ID, '_ef_location', true );
				$duedate = get_post_meta( $post->ID, '_ef_duedate', true );
				$duedate = date_i18n( 'M d, Y', $duedate );
				if ( $description || $duedate || $location ) {
				    echo '<p class="meta">';
				}
				echo '<span class="ad-roles">Role(s): ';
				$all_roles = '';
				foreach ( $roles as $role ) {
					$all_roles .= $role->name;
				}
				echo rtrim( $all_roles, ', ' ) . '</span>&nbsp;&nbsp;&nbsp;';
				if ( $duedate ) {
				    echo '<span class="ef-duedate">Due date: ' . $duedate . '</span>&nbsp;&nbsp;&nbsp;';	
				}
				if ( $location ) {
				    echo '<span class="ef-location">Location: ' . $location . '</span> ';	
				}
				if ( $description || $duedate || $location ) {
				    echo '</p>';
				}
				echo '</div>';
            }
        }
		echo "</div>";

     	$historical = '';
		$counts = array();
		$total_unpublished_assignments = 0;
        
		foreach ( $assignment_statuses as $assignment_status ) {
			if ( current_user_can($assignment_desk->define_editor_permissions) ) {
				// Count all posts with a certain status
				$counts[$assignment_status->term_id] = $this->count_posts_by_assignment_status($assignment_status);
			} else {
				// Count all posts that this user can edit with a certain status
				$counts[$assignment_status->term_id] = $this->count_user_posts_by_assignment_status($assignment_status);
			}
			$total_unpublished_assignments += $counts[$assignment_status->term_id];
		}
		foreach ( $assignment_statuses as $assignment_status ) {
				$url = admin_url() . "edit.php?ad-assignment-status=$assignment_status->term_id";
 				$historical .= "$assignment_status->name: <a href='$url'>" . $counts[$assignment_status->term_id] . "</a>, ";
		}
		$historical .= rtrim( $historical, ', ' );
		// @todo Month view
		if ( current_user_can($assignment_desk->define_editor_permissions) ) {
			$this_month_url = admin_url() . 'edit.php?post_status=publish&monthnum=' . date('M');
			$q = new WP_Query( array('post_status' => 'publish', 'monthnum' => date('M')));
			echo "<tr><td class='b'><a href='$this_month_url'>$q->found_posts</a></td>";
			echo "<td class='b t'><a href='$this_month_url'>" . __('Published this month') . "</a></td></tr>";
		}
		echo '<p class="historical">' . $historical . '<a class="button textright" href="edit.php?author=' . $current_user->ID .'">View all</a></p></div>';
       
   }
   
   	/**
     * Confirm or decline a story assignment.
     */
	function respond_to_story_invite(){
		global $current_user, $assignment_desk, $coauthors_plus, $user_ID;
       
		$active_request = false;
		$response_message = false;
		if ( isset($_POST['action']) && isset($_POST['post_id']) && isset($_POST['role_id']) ) {
			$active_request = 'ajax';
			$action = $_POST['action'];
			$post_id = (int)$_POST['post_id'];
			$role_id = (int)$_POST['role_id'];
		} else if ( isset($_GET['action']) && isset($_GET['post_id']) && isset($_GET['role_id']) ) {
			$active_request = 'normal';
			$action = $_GET['action'];
			$post_id = (int)$_GET['post_id'];
			$role_id = (int)$_GET['role_id'];
		}

		$_REQUEST['ad-dashboard-assignment-messages'] = array();
       
		if ( $active_request ) {
	
			if ( !is_user_logged_in() ) {
				$response_message = 'auth_error';
			}
			get_currentuserinfo();
	
			$participant_record = get_post_meta( $post_id, "_ad_participant_role_$role_id", true );

			// Are we waiting for a response from this user for this post/role?
			if ( $participant_record && $participant_record[$current_user->ID] == 'pending' ) {

				if ( $action == 'assignment_desk_accept' ) {
					$participant_record[$current_user->ID] = 'accepted';
					update_post_meta($post_id, "_ad_participant_role_$role_id", $participant_record);
					// Add as a coauthor
					if ( $assignment_desk->coauthors_plus_exists() ) {
						$coauthors_plus->add_coauthors($post_id, array($current_user->user_login), true);
					}
					// Add as author
					else {
						wp_update_post(array( 'ID' => $post_id, $author => $current_user->user_login ));
					}
					$user_participant = get_post_meta($post_id, "_ad_participant_$current_user->ID", true);
					if ( !$user_participant ) {
						$user_participant = array();
					}
					$user_participant[] = $role_id;
					update_post_meta($post_id, "_ad_participant_$current_user->ID", $user_participant);
					$response_message = 'accepted';
				} else if ( $action == 'assignment_desk_decline' ) {
					$participant_record[$current_user->ID] = 'declined';
					update_post_meta($post_id, "_ad_participant_role_$role_id", $participant_record);
					$response_message = 'declined';					
               }
           }

		}

		if ( $active_request == 'ajax' ) {
			die( $response_message );
		} else {
			return $response_message;
		}

		
   }
}
?>
