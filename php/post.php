<?php

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
        wp_enqueue_style('ad-fancybox', ASSIGNMENT_DESK_URL . 'js/fancybox/jquery.fancybox-1.3.1.css', false, false, 'all');
    }
    
    /**
     * Adds Assignment Desk javascript to Post/Page edit pages 
     */
    function enqueue_admin_javascript() {
        wp_enqueue_script('ad-post-js', ASSIGNMENT_DESK_URL .'js/post.js', array('jquery', 'suggest'));
    }
    
    /**
    * Print out some global JS variables that we need to compose from PHPH variables so we can use them later.
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
		echo "var wp_admin_url = '$admin_url';";
		echo '</script>';

    }

	/**
    * Print a the meta_box fragment that shows a form to choose the person
    * who pitched the story. 
    *
    * The ID of the person who pitched the story is saved as a
    * custom field under the key _ad_pitched_by_participant.
    *
    * If the person who pitched the story is NOT currently a member of the
    * WP blog we store their email address in the _ad_pitched_by_participant field;
    * 
    * When the post is saved we try to look up the user by email. Maybe
    * they became a member or were assigned a story.
    */
    function display_assignment_info(){
       	global $post, $wpdb, $assignment_desk;
       	
       	if(! current_user_can($assignment_desk->define_editor_permissions)){
       	    return;
       	}
       	
       	$pitched_by = get_post_meta($post->ID, '_ad_pitched_by_participant', true);
       	$pitched_by_user = get_userdata($pitched_by);
     ?>
        <div id="ad-pitched-by-participant" class="misc-pub-section">
            <label for="ad-pitched-by-participant-select">Pitched by:</label>
            <span id="ad-pitched-by-participant-display">
                <a href="<?php echo admin_url(); ?>user-edit.php?user_id=<?php echo $pitched_by; ?> ">
                <?php echo $pitched_by_user->display_name; ?></a>
            </span>
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
	 * @todo Check user editing permissions
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
	
	function display_visibility_info(){
	    global $post, $assignment_desk;
	    if (current_user_can($assignment_desk->define_editor_permissions)){
	?>
	    <div class="misc-pub-section">
	        <label for="ad-private">Private while in progress :</label>
	        <input type="checkbox" name="ad-private" value="1" <?php echo (get_post_meta($post->ID, '_ad_private', true) == "1")? "checked": ""; ?>>
	    </div>
	<?php  
        }
	}

	/**
     * Loren ipsum bitches
	 */ 
    function user_role_select($user_roles){
        echo "<label for='ad-user-role-dropdown'>Role:</label>&nbsp;";
        echo "<select id='ad-user-role-dropdown' name='ad-user-role-dropdown'>";
            foreach($user_roles as $user_role) {
                echo "<option value='{$user_role->term_id}'>{$user_role->name}</option>";
            }
        echo "</select>";
    }

	/**
     * Loren ipsum bitches
     */
    function display_participants() {
        global $assignment_desk, $post, $wpdb;
        
        $user_roles = $assignment_desk->custom_taxonomies->get_user_roles(array('order' => "-name"));

		// Load all existing participants from separate custom fields into
		// one array so that we can list it later
		$all_participants = array();
		$total_participants = 0;
		foreach ( $user_roles as $user_role ) {
			$role_participants = get_post_meta($post->ID, "_ad_participant_role_$user_role->term_id");
			$role_participants = $role_participants[0];
			if (count($role_participants)) {
				$all_participants[$user_role->term_id] = $role_participants;
				$total_participants = $total_participants + count($role_participants);
			}
		}
		
		// Get all of the users in the database
		$all_users = $wpdb->get_results("SELECT * FROM $wpdb->users");

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
		
        if ( count($all_participants) ): ?> 

            <?php foreach ( $user_roles as $user_role ) : ?>
			
			<?php if (count($all_participants[$user_role->term_id])) : ?>
			<div id="ad-user-role-<?php echo $user_role->term_id; ?>-wrap" class="ad-role-wrap">
				<h5><?php echo $user_role->name; ?></h5>
				<ul id="ad-participants-<?php echo "{$user_role->term_id}"; ?>">					
					<?php foreach ($all_participants[$user_role->term_id] as $participant_id => $participant_status) : ?>
						<?php $participant = get_userdata($participant_id);	?>						
						<li id="ad-participant-<?php echo "{$user_role->term_id}-{$participant_id}"; ?>"><input type="hidden" id="ad-participant-<?php echo $participant_id; ?>" name="ad-participant-role-<?php echo $user_role->term_id; ?>[]" value="<?php echo "$participant_id|$participant_status"; ?>" /><?php echo "$participant->user_nicename | " . _($participant_status); ?>
						    <?php if ($participant_status == 'volunteered') : ?>
								<?php $user = get_userdata($participant_id); ?>
						        <a href="#" id="ad-volunteer-<?php echo "$user_role->term_id-$user_role->name-$participant_id-$user->user_nicename"; ?>">assign</a>
						    <?php endif; ?>
						    <a href="#" class="ad-remove-participant" id="ad-remove-participant-<?php echo $user_role->term_id . '-' . $participant_id; ?>">remove</a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>
			
			<?php endforeach; ?>
        <?php else: ?>
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
		$this->display_visibility_info();
        echo '</div></div>';

		echo '<div class="ad-module">';
		echo '<h4 class="toggle">Contributors</h4><div class="inner">';
		$this->display_participants();
		echo '</div></div>';
		
    }

	/**
     * Save Assignment Desk post meta data
	 * @todo Might need a non
    */
    function save_post_meta_box($post_id, $post) {
        global $executed_already, $wpdb, $assignment_desk, $current_user, $ad_user_errors;

		wp_get_current_user();
        
        // if ($executed_already){ return; } else { $executed_already = true; }
        if ($post->post_type == 'revision') { return;}
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
        
        if ($executed_already) { return; }
        
        //if (!wp_verify_nonce($_POST['ad-noncename'], plugin_basename(__FILE__))){
         //   return $post_id;
       // }

        // The user who pitched this story
        if ( current_user_can( $assignment_desk->define_editor_permissions ) ) {
		    update_post_meta($post_id, '_ad_pitched_by_participant', (int)$_POST['ad-pitched-by-participant']);
		    update_post_meta($post_id, '_ad_private', (int)$_POST['ad-private']);
	    }
       
 		// If current user can edit assignment status, let them
		// Otherwise, set to default if contributor
		if (current_user_can($assignment_desk->define_editor_permissions)) {
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
			    if ( $_POST['ad-participant-types'] ) {
			        $participant_types = $_POST['ad-participant-types'];
					if ( in_array($user_type->term_id, $participant_types) ) {
						update_post_meta($post_id, "_ad_participant_type_$user_type->term_id", 'on');
					} else {
						update_post_meta($post_id, "_ad_participant_type_$user_type->term_id", 'off');
					}
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
		if (current_user_can($assignment_desk->define_editor_permissions)) {
			$all_participants = array();
			// For each User Role, save participant ID and status
			foreach ( $user_roles as $user_role ) {
				$raw_role_participants = array();
				$all_role_participants = array();		
				$raw_role_participants = $_POST["ad-participant-role-$user_role->term_id"];
				if ( count($raw_role_participants) ) {
					foreach ($raw_role_participants as $raw_participant) {
						$participant = explode('|', $raw_participant);;
						$user = get_userdata($participant[0]); 
						
						// Check if the user_login is valid.
						if($user){
						    // array(user => "status", user => "status")
						    $all_role_participants[$user->ID] = $participant[1];
						    // array('user' => array(role_id, role_id, ...))
						    $all_participants[$user->ID][] = $user_role->term_id;
						    
						    // Existing user_roles for this user on this post
						    $participant_record = get_post_meta($post_id, "_ad_participant_$user_id", true);
						    if(!$participant_record){ $participant_record = array(); }
						    // check if the user is already assigned to that role
						    // If the term_id for the user_role is NOT in the list of term_ids for this user.
						    if(! in_array($user_role->term_id, $participant_record)){					        
						        $this->send_assignment_email($post_id, $user->ID, $user_role->term_id);
						    }
						}
						else {
						    // Invalid user.
						    // @todo - Store the error somewhere so it can be displayed
						}
					}
				}
				update_post_meta($post_id, "_ad_participant_role_$user_role->term_id", $all_role_participants);
			}
			// Also save the User Roles associated with a row for each participant
			foreach ($all_participants as $participant_id => $user_role_ids) {
				update_post_meta($post_id, "_ad_participant_$participant_id", $user_role_ids);
			}
		}
		
        if($post->post_status == 'publish'){
            $published_status = get_term($assignment_desk->general_options['default_published_assignment_status'],
                                         $assignment_desk->custom_taxonomies->assignment_status_label);
            if($published_status){
                wp_set_object_terms($post->ID, $published_status->slug, $assignment_desk->custom_taxonomies->assignment_status_label);
            }
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
                          '%post_link%',
                          '%display_name%',
                          '%role%',
                          '%dashboard_link%',
                       );
        $replace = array(get_option('blogname'),
                        $post->post_title,  
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