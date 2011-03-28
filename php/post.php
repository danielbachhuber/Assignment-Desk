<?php

require_once('utils.php');

if (!class_exists('ad_post')) {

/**
* Post meta_box base class for the assignment desk.
*/
class ad_post {

	function init() {
		
		// Set up metabox and related actions
        add_action('admin_menu', array(&$this, 'add_post_meta_box'));
        // 20, 2 = execute our method very late (10 is default) and send 2 args,
        // the second being the post object
        add_action('save_post', array(&$this, 'save_post_meta_box'), 9, 2);
        add_action('edit_post', array(&$this, 'save_post_meta_box'), 9, 2);
        add_action('publish_post', array(&$this, 'save_post_meta_box'), 9, 2);
        
        add_action('save_post', array($this, 'zero_sort_by_counts'), 9, 2);
        // Word counting for user stats
        add_action('save_post', array(&$this, 'save_post_word_count'), 9, 2);
		
		$this->enqueue_admin_css();
		$this->enqueue_admin_javascript();	
		add_action( 'admin_print_scripts', array(&$this, 'javascript_variables') );
		
		add_action('wp_ajax_user_check', array(&$this, 'ajax_user_check'));
	}
    
    /**
     * Adds Assignment Desk meta_box to Post/Page edit pages 
     */
    function add_post_meta_box() {
        global $assignment_desk;
        
        if (function_exists('add_meta_box')) {
            add_meta_box('assignment-desk', 
                            __('Assignment Desk', 'assignment-desk'), 
                            array(&$this, 'post_meta_box'), 
                            'post', 
                            'side', 
                            'high');
        }
    }
    
    /**
     * Adds Assignment Desk CSS to Post/Page edit pages 
     */
    function enqueue_admin_css(){
        // Enqueue the ad_post_meta.css
        wp_enqueue_style('ad-post-meta-style', ASSIGNMENT_DESK_URL.'css/post.css', false, false, 'all');

    }
    
    /**
     * Adds Assignment Desk javascript to Post/Page edit pages 
     */
    function enqueue_admin_javascript() {
        wp_enqueue_script('ad-post-js', ASSIGNMENT_DESK_URL .'js/post.js', array('jquery', 'suggest'));
    }
    
    /**
    * Print out some global JS variables.
    */
    function javascript_variables(){
		global $assignment_desk;
		$admin_url = admin_url();
		
        // AJAX link used for the autosuggest
        echo '<script type="text/javascript">';
		if ($assignment_desk->coauthors_plus_exists()) {	
	        echo "var coauthor_ajax_suggest_link='{$admin_url}admin-ajax.php?action=coauthors_ajax_suggest'; ";
		} else {
			echo "var coauthor_ajax_suggest_link = '';";
		}
		
		echo "var assignment_desk_no_user_selected = '" . _('No user selected') . "';";
        echo "var assignment_desk_already_added = '" . _('has already been added as a') . "';" ;
        echo "var assignment_desk_invalid_user = '" . _('is not a valid user.') . "';" ;
		echo "var wp_admin_url = '$admin_url';";
		echo '</script>';
    }

	/**
    * Print a the meta_box fragment that shows a form to choose the person
    * who pitched the story. 
    *
    * The ID of the person who pitched the story is saved as a
    * custom field under the key _ad_pitched_by_participant.
    */
    function display_assignment_info(){
       	global $post, $wpdb, $assignment_desk;
       	
       	if( !current_user_can($assignment_desk->define_editor_permissions) ){
       	    return;
       	}
       	
       	$pitched_by = get_post_meta( $post->ID, '_ad_pitched_by_participant', true );
		$pitched_by_user = get_userdata( $pitched_by );
       	$pitched_by_timestamp = get_post_meta( $post->ID, '_ad_pitched_by_timestamp', true );
		
     ?>
        <div id="ad-pitched-by-participant" class="misc-pub-section">
            <label for="ad-pitched-by-participant-select">Pitched by:</label>
            <span id="ad-pitched-by-participant-display">
        <?php
            if( $pitched_by ) { 
                echo "<a href='" . admin_url() . "user-edit.php?user_id=$pitched_by'>$pitched_by_user->display_name</a>";
            }
            else {
                _e('None');
            }
        ?></span><?php if ( $pitched_by_timestamp ) : ?><span class="ad-pitched-by-timestamp">, <?php echo human_time_diff( $pitched_by_timestamp, current_time('timestamp') ); ?> ago</span><?php endif; ?>
            <a id="ad-edit-pitched-by-participant" class="hide-if-no-js" href="#pitched-by-participant">Edit</a>

            <div id="ad-pitched-by-participant-select" class="hide-if-js">        
                <?php $users = $wpdb->get_results("SELECT ID, user_nicename FROM $wpdb->users"); ?>
                <select name="ad-pitched-by-participant" id="ad-pitched-by-participant" class="hide-if-no-js">
                    <option value="">---</option>
                    <?php foreach($users as $user) {
                        echo "<option value='$user->ID'";
                        if ($user->ID == $pitched_by) echo ' selected';
                        echo ">$user->user_nicename</option>";
                    } ?>
                </select>
                <a id="ad-save-pitched-by-participant" class="hide-if-no-js button" href="#pitched-by-participant">OK</a>&nbsp;
        	    <a id="ad-cancel-pitched-by-participant" class="hide-if-no-js" href="#pitched-by-participant">Cancel</a>
    	    </div>
    	</div>
<?php
    }

	/**
     * Print the assignment status form.
     * If there is no status the assignment is the default
	 * If the post is not in the assignment status don't show the form.
     */
    function display_assignment_status(){
        global $post, $wpdb, $assignment_desk, $current_user;

		wp_get_current_user();

		echo '<div class="misc-pub-section">';
		
        echo '<label for="ad-assignment-status">Status:</label>&nbsp;';
        // What is the status of this Assignment?
        $current_status = wp_get_object_terms($post->ID,
                                              $assignment_desk->custom_taxonomies->assignment_status_label);
        
		// Default assignment status is defined in Assignment Desk Settings
        if ( !$current_status ) {
            $current_status = $assignment_desk->custom_taxonomies->get_default_assignment_status();
        } else {
			$current_status = $current_status[0];
        }

		echo '<span id="ad-assignment-status-display">' . $current_status->name . '</span>';

		if (current_user_can($assignment_desk->define_editor_permissions) ) {
		    echo '&nbsp;<a id="ad-edit-assignment-status" class="hide-if-no-js" href="#assignment-status">Edit</a>';
		    echo '<div id="ad-assignment-status-select" class="hide-if-js">';
		    $assignment_statuses = $assignment_desk->custom_taxonomies->get_assignment_statuses();
		    if (count($assignment_statuses)) {
    			// List all of the assignment statuses
    			echo "<select id='ad-assignment-status' name='ad-assignment-status'>";
    			foreach ( $assignment_statuses as $assignment_status ) {
    				echo "<option value='{$assignment_status->term_id}'";
    				if ( $assignment_status->term_id == $current_status->term_id ) {
    					echo " selected='selected'";
    				}
    				echo ">{$assignment_status->name}</option>";
    			}
    			echo "</select>&nbsp;";
    			echo '<a id="ad-save-assignment-status" class="hide-if-no-js button" href="#assignment-status">OK</a>&nbsp;';
    			echo '<a id="ad-cancel-assignment-status" class="hide-if-no-js" href="#assignment-status">Cancel</a>';
    		}
			else {
			    echo '<span id="ad-assignment-statuses-display">None defined</span>';
			    echo "<a href=" . admin_url() . "edit-tags.php?taxonomy=" . $assignment_desk->custom_taxonomies->assignment_status_label . " target='_blank'>Create</a>";
		    }
			echo '</div>';
		}	
		
		echo '</div>';
		
    }

	/**
	 * Print allowed participant types
	 * Editor and above can change the permitted participant types
	 */
	function display_participant_types() {
		global $post, $wpdb, $assignment_desk, $current_user;
		
		wp_get_current_user();
		
		$user_types = $assignment_desk->custom_taxonomies->get_user_types();		
		$participant_types = $assignment_desk->custom_taxonomies->get_user_types_for_post($post->ID);
		?>
		<div class="misc-pub-section">
			<label for="ad-participant-types">Contributor types:</label>
		<?php if (count($user_types)) : ?>
			<span id="ad-participant-types-display"><?php echo $participant_types['display']; ?></span> 
		<?php if (current_user_can($assignment_desk->define_editor_permissions)) : ?>
			<a id="ad-edit-participant-types" class='hide-if-no-js' href='#participant-types'>Edit</a>
			<div id="ad-participant-types-select" class="hide-if-js">
				<ul>
				<?php foreach( $user_types as $user_type ) : ?>
					<li><input type="checkbox" id="ad-participant-type-<?php echo $user_type->term_id; ?>" name="ad-participant-types[]" value="<?php echo $user_type->term_id; ?>"<?php if ( $participant_types[$user_type->term_id] == 'on') { echo ' checked="checked"'; } ?> />&nbsp;<label for="ad-participant-type-<?php echo $user_type->term_id; ?>"><?php echo $user_type->name; ?></label></li> 
				<?php endforeach; ?>
				</ul>
				<p><a id="save-ad-participant-types" class="hide-if-no-js button" href="#participant-types">OK</a>
				<a id="cancel-ad-participant-types" class="hide-if-no-js" href="#participant-types">Cancel</a></p>
			</div>
		<?php endif; ?>
		<?php else : ?>
			<span id="ad-participant-types-display">None defined</span> 
			<a href='<?php echo admin_url(); ?>edit-tags.php?taxonomy=<?php echo $assignment_desk->custom_taxonomies->user_role_label; ?>' target='_blank'>Create</a>
		<?php endif; ?>
		</div>
		<?php 
	
	}
	
	/**
	 * Print selected volunteering roles
	 * Editor and above can change the permitted participant types
	 */
	function display_participant_roles() {
		global $post, $wpdb, $assignment_desk, $current_user;
		
		wp_get_current_user();
		
		$user_roles = $assignment_desk->custom_taxonomies->get_user_roles();		
		$participant_roles = $assignment_desk->custom_taxonomies->get_user_roles_for_post($post->ID);
		?>
		<div class="misc-pub-section">
			<label for="ad-participant-roles">Contributor roles:</label>
		<?php if (count($user_roles)) : ?>
			<span id="ad-participant-roles-display"><?php echo $participant_roles['display']; ?></span> 
		<?php if (current_user_can($assignment_desk->define_editor_permissions)) : ?>
			<a id="ad-edit-participant-roles" class='hide-if-no-js' href='#participant-roles'>Edit</a>
			<div id="ad-participant-roles-select" class="hide-if-js">
				<ul>
				<?php foreach( $user_roles as $user_role ) : ?>
					<li><input type="checkbox" id="ad-participant-role-<?php echo $user_role->term_id; ?>" name="ad-participant-roles[]" value="<?php echo $user_role->term_id; ?>"<?php if ( $participant_roles[$user_role->term_id] == 'on') { echo ' checked="checked"'; } ?> />&nbsp;<label for="ad-participant-role-<?php echo $user_role->term_id; ?>"><?php echo $user_role->name; ?></label></li> 
				<?php endforeach; ?>
				</ul>
				<p><a id="save-ad-participant-roles" class="hide-if-no-js button" href="#participant-roles">OK</a>
				<a id="cancel-ad-participant-roles" class="hide-if-no-js" href="#participant-roles">Cancel</a></p>
			</div>
		<?php endif; ?>
		<?php else : ?>
			<span id="ad-participant-roles-display">None defined</span> 
			<a href='<?php echo admin_url(); ?>edit-tags.php?taxonomy=<?php echo $assignment_desk->custom_taxonomies->user_role_label; ?>' target='_blank'>Create</a>
		<?php endif; ?>
		</div>
		<?php
	}

	/**
     * Loren ipsum bitches
	 */ 
    function user_role_select($user_roles){
        echo "<label for='ad-participant-role-dropdown'>Role:</label>&nbsp;";
        echo "<select id='ad-participant-role-dropdown' name='ad-participant-role-dropdown'>";
            foreach($user_roles as $user_role) {
                echo "<option value='{$user_role->term_id}'>{$user_role->name}</option>";
            }
        echo "</select>";
    }

	/**
     * Show the form to add a new participant and current list of participants.
     * If coauthors-plus is enabled show a text box with auto-completion for users.
     * If coauthors-plus is not enabled we show a list of users in a select box.
     */
    function display_participants() {
        global $assignment_desk, $post, $wpdb;
        
        $user_roles = $assignment_desk->custom_taxonomies->get_user_roles(array('order' => "-name"));

		if ( count($user_roles) && current_user_can($assignment_desk->define_editor_permissions)) :
			echo '<div id="ad-assign-form" class="misc-pub-section">';
            echo '<label>Select user:</label>&nbsp;';
			// Use auto-suggest if Co-Authors Plus exists
			// Otherwise, use a dropdown with all users
			if ( $assignment_desk->coauthors_plus_exists() ) {
			    echo '<input type="hidden" id="ad-assignee-search-user_id" name="ad-assignee-search-user_id">';
				echo '<input type="text" id="ad-assignee-search" name="ad-assignee-search" size="20" maxlength="50"><br />';
			} else {
				echo "<select id='ad-assignee-dropdown' name='ad-assignee-dropdown'>";
				$all_users = $wpdb->get_results("SELECT * FROM $wpdb->users");
				foreach ( $all_users as $user ) {
					echo "<option value='{$user->ID}'>{$user->user_nicename}</option>";
				}
				echo "</select><br />";
			}
			echo $this->user_role_select($user_roles); ?>
				<a id="ad-assign-button" class="button" href="#assign-participant">Add</a>
			</div>	
		<?php elseif (current_user_can($assignment_desk->define_editor_permissions)) : ?>
				<div class="message alert">You haven't defined any user roles yet. Get started by <a href='<?php echo admin_url(); ?>edit-tags.php?taxonomy=<?php echo $assignment_desk->custom_taxonomies->user_role_label; ?>' target='_blank'>defining one or more roles</a>.</div>
		<?php endif; ?>
		
		<div id="ad-participants-wrap">
        
        <?php 
        $total_participants = 0;
        foreach ( $user_roles as $user_role ){
            $role_participants = get_post_meta($post->ID, "_ad_participant_role_{$user_role->term_id}", true);
            if (is_array($role_participants) && count($role_participants) ) {
                $total_participants += count($role_participants);
                echo "<div id='ad-participant-role-{$user_role->term_id}-wrap' class='ad-role-wrap'>";
                echo "<h5> $user_role->name </h5>";
                foreach ($role_participants as $participant_id => $participant_status) {
				    $participant = get_userdata((int)$participant_id);
				    echo "<p id='ad-participants-{$user_role->term_id}-{$participant->ID}'>";
					// assignment-desk specific actions
                    if ( current_user_can($assignment_desk->define_editor_permissions) ) {
                        echo "<span class='ad-participant-buttons'>";
                        if ( $participant_status == 'volunteered' ){
                            echo " <button class='button ad-assign-participant-button' name='ad-participant-assign[]' value='{$user_role->term_id}|{$user_role->name}|{$participant->ID}|{$participant->user_nicename}'>Assign</button>";
                        }
                        echo " <button class='button ad-remove-participant-button' name='ad-participant-remove[]' value='{$user_role->term_id}|{$participant->ID}'>Remove</button></span>";
				        echo "$participant->user_nicename (" . _($participant_status) . ')';
                    }
                    else {
			            echo "$participant->user_nicename (" . _($participant_status) . ')';
                    }
                    echo "</p>";
				}
                echo "</div>";
            }
        }			
        if( !$total_participants ): ?>
			<div id="ad-no-participants" class="message info">
				No contributors have volunteered or been assigned to this post.
			</div>
        <?php endif; ?>
		</div>
	<?php
    }

	/**
    * Launch the Assignment Desk post_meta_box.
    */
    function post_meta_box(){
        global $assignment_desk, $post;

        echo '<div id="ad-error-messages" style="display:none" class="error"></div>';

        echo '<div class="ad-module">';
		echo '<h4 class="toggle">Details</h4><div class="inner">';
        $this->display_assignment_info();
		$this->display_assignment_status();
		$this->display_participant_types();
		$this->display_participant_roles();		
        echo '</div></div>';

		echo '<div class="ad-module">';
		echo '<h4 class="toggle">Contributors</h4><div class="inner">';
		$this->display_participants();
		echo '</div></div>';
		
    }

	/**
     * Save Assignment Desk post meta data
    */
    function save_post_meta_box($post_id, $post) {
        global $executed_already, $wpdb, $assignment_desk, $current_user, $ad_user_errors;

		wp_get_current_user();
        
        // if ($executed_already){ return; } else { $executed_already = true; }
        if ($post->post_type == 'revision') { return;}
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
        
        if ($executed_already) { return; }

        // The user who pitched this story
        if ( current_user_can( $assignment_desk->define_editor_permissions ) ) {
			if ( isset( $_POST['ad-pitched-by-participant'] ) ) {
		    	update_post_meta($post_id, '_ad_pitched_by_participant', (int)$_POST['ad-pitched-by-participant']);
			}
			// Save the pitch time if it doesn't already exist
			if ( !get_post_meta( $post_id, '_ad_pitched_by_timestamp', true ) ) {
				update_post_meta($post_id, '_ad_pitched_by_timestamp', date_i18n('U'));
			}
	    }
       
 		// If current user can edit assignment status, let them
		// Otherwise, set to default if contributor
		if ( current_user_can( $assignment_desk->define_editor_permissions ) && isset( $_POST['ad-assignment-status'] ) ) {
			wp_set_object_terms($post_id, (int)$_POST['ad-assignment-status'], $assignment_desk->custom_taxonomies->assignment_status_label);
		} else {
			$current_status = wp_get_object_terms($post_id, $assignment_desk->custom_taxonomies->assignment_status_label);
			if (!$current_status) {
				$new_status = $assignment_desk->custom_taxonomies->get_default_assignment_status();
				wp_set_object_terms($post_id, (int)$new_status->term_id, $assignment_desk->custom_taxonomies->assignment_status_label);
			}
		}
		
		// If the current user can edit participant types, allow them to do so
		// Otherwise, set all participant types to 'on' if they're unset
		$user_types = $assignment_desk->custom_taxonomies->get_user_types();
		// Only editors can update the participant types on an assignment
		if ( current_user_can( $assignment_desk->define_editor_permissions ) ) {
			foreach ($user_types as $user_type) {
			    $participant_types = array();
				// If $_POST['ad-participant-types'] isn't set, then we have no contributor types
			    if ( isset( $_POST['ad-participant-types'] ) ) {
			        $participant_types = $_POST['ad-participant-types'];
					if ( in_array($user_type->term_id, $participant_types) ) {
						update_post_meta($post_id, "_ad_participant_type_$user_type->term_id", 'on');
					} else {
						update_post_meta($post_id, "_ad_participant_type_$user_type->term_id", 'off');
					}
			    } else {
					update_post_meta($post_id, "_ad_participant_type_$user_type->term_id", 'off');
				}
			}
		} else {
			foreach ( $user_types as $user_type ) {
				$participant_type_state = get_post_meta($post_id, "_ad_participant_type_$user_type->term_id", true);
				if ( $participant_type_state != 'on' && $participant_type_state != 'off' ) {
					update_post_meta($post_id, "_ad_participant_type_$user_type->term_id", 'on');
				}
			}
		}
		
		$user_roles = $assignment_desk->custom_taxonomies->get_user_roles();
		// If the current user can edit participant types, allow them to do so
		// Otherwise, set all participant types to 'on' if they're unset
		// Only editors can update the participant types on an assignment
		if ( current_user_can( $assignment_desk->define_editor_permissions ) ) {
			foreach ($user_roles as $user_role) {
			    $participant_roles = array();
				// If $_POST['ad-participant-roles'] isn't set, then we have no contributor roles
			    if ( isset( $_POST['ad-participant-roles'] ) ) {
			        $participant_roles = $_POST['ad-participant-roles'];
					if ( in_array($user_role->term_id, $participant_roles) ) {
						update_post_meta($post_id, "_ad_participant_role_status_$user_role->term_id", 'on');
					} else {
						update_post_meta($post_id, "_ad_participant_role_status_$user_role->term_id", 'off');
					}
			    } else {
					update_post_meta($post_id, "_ad_participant_role_status_$user_role->term_id", 'off');
				}
			}
		} else {
			foreach ( $user_roles as $user_role ) {
				$participant_role_state = get_post_meta($post_id, "_ad_participant_role_status_$user_role->term_id", true);
				if ( $participant_role_state != 'on' && $participant_role_state != 'off' ) {
					update_post_meta($post_id, "_ad_participant_role_status_$user_role->term_id", 'on');
				}
			}
		}
				
		if (current_user_can($assignment_desk->define_editor_permissions)) {
			$all_volunteer_ids = array();
			// For each User Role, save participant ID and status
			foreach ( $user_roles as $user_role ) {
				$role_participants[$user_role->term_id] = get_post_meta($post_id, "_ad_participant_role_$user_role->term_id", true);
			}
			if ( ! $role_participants ){
			    $role_participants = array();
			}
			// Remove a participant from a post
    		if ( isset( $_POST['ad-participant-remove'] ) ) {
    			if (!is_array($_POST['ad-participant-remove'])){
		            $_POST['ad-participant-remove'] = array($_POST['ad-participant-remove']);
		        }
		        foreach ( $_POST['ad-participant-remove'] as $remove ){
    		        $pieces = explode('|', $remove);
    		        $role_id = (int)$pieces[0];
    		        $user_id = (int)$pieces[1];
    		        
    		        if ( $role_id && $user_id ) {
        		        // Remove from the post participants
        		        unset($role_participants[$role_id][$user_id]);
    		        
        		        // Remove corresponding user record
        		        $user_participant = get_post_meta($post_id, "_ad_participant_$user_id", true);
        		        if ( $user_participant and is_array($user_participant) ){
        		            unset($user_participant[$role_id]);
                            update_post_meta($post_id, "_ad_participant_$user_id", $user_participant);
                            delete_usermeta($user_id, '_ad_volunteer', $post_id);
        		        }
    		        }
		        }
		    }
		    
		    // Assign a participant to a post
			if ( isset( $_POST['ad-participant-assign'] ) ) {
		        if (!is_array($_POST['ad-participant-assign'])){
		            $_POST['ad-participant-assign'] = array($_POST['ad-participant-assign']);
		        }
		        foreach ( $_POST['ad-participant-assign'] as $assign ){
    		        $pieces = explode('|', $assign);
    		        $role_id = (int)$pieces[0];
    		        $user_id = (int)$pieces[1];
    		        
    		        // Add the user to the post with the pending status and send email. 
    		        $role_participants[$role_id][$user_id] = 'pending';
    		        $this->send_assignment_email($post_id, $user_id, $role_id);
    		        
    		        // Add the role to the corresponding user record for this post
    		        $user_participant = get_post_meta($post_id, "_ad_participant_$user_id", true);
    		        if ( !$user_participant or !is_array($user_participant) ){
    		            $user_participant = array();
    		        }
		            $user_participant[] = $role_id;
		            update_post_meta($post_id, "_ad_participant_$user_id", $user_participant);
		        }
		    }
    		
    		// Update the coauthors
    		if ( $assignment_desk->coauthors_plus_exists() ) {
    			global $coauthors_plus;
    			
    			// Scan participants for those that accepted
    			$coauthors = array();
    			foreach ( $user_roles as $user_role ) {
    			    if ( !$role_participants[$user_role->term_id] ) {
    			        continue;
    			    }
        		    foreach ( $role_participants[$user_role->term_id] as $user_id => $status ) {	
        			    if ( $status == 'accepted' ) {
        			        $user = get_userdata($user_id);
        			        $coauthors[] = $user->user_login;
        			    }
        		    }
    		    }
    		    $coauthors = array_unique($coauthors);
    		    
    		    // If no coauthors assign it to the current user
    		    if ( empty($coauthors) ) { 
    		        $coauthors[] = $current_user->user_login;
    		    }
				// Merge the values we want to add to Co-Authors so we don't lose the data in the Co-Authors form
        		$_POST['coauthors'] = array_merge( $_POST['coauthors'], $coauthors );
			}

            // Update the participants for this role 
			foreach ( $user_roles as $user_role ) {
    		    update_post_meta($post_id, "_ad_participant_role_{$user_role->term_id}", $role_participants[$user_role->term_id]);
		    }

            // Update the number of unique volunteers
    		$all_volunteer_ids = array();
    		foreach ( $user_roles as $user_role ) {
    		    if ( ! $role_participants[$user_role->term_id] ) {
    		        continue;
    		    }
    		    foreach ( $role_participants[$user_role->term_id] as $user_id => $status ) {	
    			    if ( $status == 'volunteered' ) {
    			        $all_volunteer_ids[] = $user_id;
    			    }
    		    }
		    }
			$all_volunteer_ids = array_unique($all_volunteer_ids);
			$volunteers = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key='_ad_volunteer' AND meta_value=$post_id");
            update_post_meta($post_id, '_ad_total_volunteers', $volunteers);
		}

		// Prevent this function from being called twice for a post during a single request.
		$executed_already = true;
    }
    
    /**
     * We need to store a zero vote and volunteer count so sorting by a meta_value count works correctly. 
     * Without the 0 meta_value in the db posts will not show up when sorting.
     */
    function zero_sort_by_counts($post_id, $post){
	    $total_votes = get_post_meta($post_id, '_ad_votes_total', true);
	    if ( !$total_votes ) {
	        update_post_meta($post_id, '_ad_votes_total', 0);
	    }
	    
	    $total_volunteers = get_post_meta($post_id, '_ad_total_volunteers', true);
	    if ( !$total_volunteers ) {
	        update_post_meta($post_id, '_ad_total_volunteers', 0);
	    }
	}
    
    /**
     * Store the word count as post metadata. 
     * Avoid having to count the words when generating user stats.
     */
    function save_post_word_count($post_id, $post){
        update_post_meta($post_id, '_ad_word_count', str_word_count($post->post_content));
    }
    
    /**
    * Fill out the template for the email a user receives when they're assigned a story.
    * Then send the email.
    */
    function send_assignment_email($post_id, $user_id, $role_id){
        global $assignment_desk;
        
        if ($assignment_desk->general_options['assignment_email_notifications_enabled'] != 'on'){
            return;
        }
        
        $post = get_post($post_id);
        $user = get_userdata($user_id);
        $role = get_term($term_id, $assignment_desk->custom_taxonomies->user_role_label);
        // Get the template from the settings
        $email_template = $assignment_desk->general_options['assignment_email_template'];
        $subject = $assignment_desk->general_options['assignment_email_template_subject'];
        
        $search = array(  '%blogname%',
                          '%title%', 
                          '%excerpt%',
                          '%duedate%',
                          '%description%',
                          '%post_link%',
                          '%display_name%',
                          '%role%',
                          '%dashboard_link%',
                       );
        $replace = array(get_option('blogname'),
                        $post->post_title,
                        $post->post_excerpt,
                        ad_format_ef_duedate(get_post_meta($post_id, '_ef_duedate', true)),
                        get_post_meta($post_id, '_ef_description', true),
                        get_permalink($post_id),
                        $user->display_name,
                        $role->name,
                        admin_url(),
                    );
        // Fill it out
        $email_template = str_replace($search, $replace, $email_template);
        $subject = str_replace($search, $replace, $subject);
        // Send it off
        wp_mail($user->user_email, $subject, $email_template);
    }
    
    /**
     * Very simple ajax call to validate a user by login.
     */
    function ajax_user_check(){
        global $current_user, $assignment_desk;
        
        get_currentuserinfo();
		
		if(current_user_can($assignment_desk->define_editor_permissions)) {
            if($_GET['q']){
                $user = get_userdata((int)$_GET['q']);
            
                if($user){
                    echo $user->ID;
                }
                else {
                    echo '0';
                }
            }
        }
        die();
    }

}

} // end if(!class_exists)
?>