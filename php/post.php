<?php

if (!class_exists('ad_post')) {

/**
* Post meta_box base class for the assignment desk.
*/
class ad_post {
    
   function __construct() {
        
		// @todo Move to assignment_desk class
		$this->init();
    }

	function init() {
		
		// Set up metabox and related actions
        add_action('admin_menu', array(&$this, 'add_post_meta_box'));
        // 20, 2 = execute our method very late (10 is default) and send 2 args,
        // the second being the post object
        add_action('save_post', array(&$this, 'save_post_meta_box'), 9, 2);
        add_action('edit_post', array(&$this, 'save_post_meta_box'), 9, 2);
        add_action('publish_post', array(&$this, 'save_post_meta_box'), 9, 2);
		
		$this->enqueue_admin_css();
		$this->enqueue_admin_javascript();	
		add_action( 'admin_print_scripts', array(&$this, 'javascript_variables') );
		
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
        // AJAX link used for the autosuggest
		if ($assignment_desk->coauthors_plus_exists()) {
			$admin_url = admin_url();
			echo '<script type="text/javascript">';
	        echo "var coauthor_ajax_suggest_link ='{$admin_url}admin-ajax.php?action=coauthors_ajax_suggest';";
	        echo '</script>';
		}

    }


	/**
    * Print a the meta_box fragment that shows a form to choose the person
    * who pitched the story. 
    *
    * The ID of the person who pitched the story is saved as a
    * custom field under the key _ad_pitched_by.
    *
    * If the person who pitched the story is NOT currently a member of the
    * WP blog we store their email address in the _ad_pitched_by field;
    * 
    * When the post is saved we try to look up the user by email. Maybe
    * they became a member or were assigned a story.
    */
    function display_assignment_info(){
       	global $post, $wpdb;
        echo "<div id='ad-assignment-detail' class='misc-pub-section'>";
        
        $pitched_by = get_post_meta($post->ID, '_ad_pitched_by', true);
        $users = $wpdb->get_results("SELECT ID, user_nicename
                                     FROM $wpdb->users");
        echo "<label>Pitched by:</label>";
        
        // Try to resolve the user email to a user
        if (is_email($pitched_by)){
            $user = $wpdb->get_row($wpdb->prepare("SELECT ID, user_nicename 
                                                    FROM $wpdb->users 
                                                    WHERE user_email = %s", $pitched_by));
            if ($user){
                $pitched_by = $user->ID;
            }
            else {
                echo $pitched_by;
            }
        }
        // Only display a form if 
        if(!is_email($pitched_by)){
            echo "<select name='_ad_pitched_by'>";
            foreach($users as $user){
                echo "<option value='{$user->ID}'";
                if ($user->ID == $pitched_by){
                    echo ' selected';
                }
                echo ">{$user->user_nicename}</option>";
            }
            echo "</select>";
        }

        // @todo - Origin? (community or staff)
        echo '</div>';
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

		if (current_user_can($assignment_desk->define_editor_permissions)) {
			echo '&nbsp;<a id="ad-edit-assignment-status" class="hide-if-no-js" href="#assignment-status">Edit</a>';
			// List all of the assignment statuses
			$assignment_statuses = get_terms($assignment_desk->custom_taxonomies->assignment_status_label,
	                                        array( 'get' => 'all'));
			echo '<div id="ad-assignment-status-select" class="hide-if-js">';
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
		
		$participant_types = $assignment_desk->custom_taxonomies->get_user_types_for_post($post->ID);
		$user_types = $assignment_desk->custom_taxonomies->get_user_types();
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
     * Print a form to choose the user.
     * This is shown when the co-authors-plus plugin is NOT active.
     */
    function display_author() {
        global $wpdb, $post;
        
        $users = $wpdb->get_results("SELECT ID, user_nicename FROM $wpdb->users");
        echo "<div class='misc-pub-section'>";
        echo "<label for='ad_author'>Author:</label>";
        echo "<select id='ad_author' name='ad_author'>";
        foreach($users as $user){
            echo "<option value='{$user->ID}' ";
            if($user->ID == $post->post_author){
                echo "selected";
            }
            echo ">{$user->user_nicename}</option>";
        }
        echo "</select>";
        echo "</div>";
    }

	/**
     * Loren ipsum bitches
	 */ 
    function user_role_select($user_roles){
        echo "<label for='ad-user-role-select'>Role</label>&nbsp;";
        echo "<select id='ad-user-role-select'>";
            foreach($user_roles as $user_role) {
                echo "<option value='{$user_role->term_id}'>{$user_role->name}</option>";
            }
        echo "</select>";
    }

	/**
     * Print a form to choose multiple users.
     * Print the current lists of assignees.
     */
    function display_multiple_assignees() {
        global $assignment_desk, $post, $wpdb;
        
        $user_roles = get_terms($assignment_desk->custom_taxonomies->user_role_label,
                                    array( 'get' => 'all', 'order' => "-name")); 
        ?>
		<div id="ad-assign-form">
			<?php if ( count($user_roles) ) : ?>
			<div class="misc-pub-section">
            <label for="ad-assignee-search">User</label> <input type="text" id="ad-assignee-search" name="ad-assignee-search" size="20" maxlength="50"><br />
			<?php echo $this->user_role_select($user_roles); ?>
			<a id="ad-assign-button" class="button">Add</a>
			</div>
			<?php
	        foreach($user_roles as $user_role){
	            $num_users = 0;
	            // Lookup all users assigned to this post with this role.
	            $user_logins = get_post_meta($post->ID, "_ad_assignees_role_{$user_role->term_id}", true);
	            // @todo - HACK HACK HACK. get_post_meta should return either an array or an empty string
	            if(!is_array($user_logins) && count($user_logins) == 1){
	                $user_logins = array();
	            }
	            $num_users = count($user_logins);

	            // Only show the div if there are assignees for that role
	            echo "<div id='ad_assignees_role_{$user_role->term_id}'";
	            echo ($num_users)? ">" : "style='display:none'>";
	            echo "<h5>{$user_role->name}s</h5>";
	            echo "<ul id='ad_assignees_role_{$user_role->term_id}'>";
	            if($num_users){
	                $user_sql = "SELECT ID, user_login, user_nicename
	                            FROM $wpdb->users 
	                            WHERE 0 OR ";
	                for($i = 0; $i < $num_users - 1; $i++){
	                    $user_sql .= " user_login='{$user_logins[$i]}' OR ";
	                }
	                $num_users--;
	                $user_sql .= " user_login='{$user_logins[$num_users]}'";

	                $users = $wpdb->get_results($user_sql);
	                if(!is_array($users)){
	                    $users = array($users);
	                }
	                foreach($users as $user){
	                    echo "<li><input type='hidden' id='ad-assignees' name='ad-assignees[]' value='{$user->user_login}|{$user_role->term_id}' />{$user->user_nicename}</li>";
	                }
	            }
	            echo "</ul></div>";
	        }
	        ?>
			
			<?php else : ?>
				<div class="message alert">You haven't defined any user roles yet. Get started by <a href='<?php echo admin_url(); ?>edit-tags.php?taxonomy=<?php echo $assignment_desk->custom_taxonomies->user_role_label; ?>' target='_blank'>defining one or more roles</a>.</div>
			<?php endif; ?>
		</div>
	<?php
    }

	/**
    * Print the list of participants.
    */
    function display_participants() {
        global $assignment_desk, $post;
        // Print the participants (if any and came from a pitch)
        $participants = get_post_meta($post->ID, '_ad_participants', false);

        if(count($participants)): ?>      
        <div class="ad-module misc-pub-section">
            <h4 id="ad-participants-count-wrap">Contributors (<span id="ad-participants-count"><?php echo count($participants); ?></span>)</h4>
            <ul>
            <?php foreach($participants as $user_login):
                    $user = get_userdatabylogin($user_login);
                ?>
                <li>
                <?php if (!empty($user->user_login)): ?>
                    <div id="ad_participants_<?php echo $user->user_login; ?>">
                        <?php echo $user->user_login; ?>
                        <a class="button"
                                onclick="javascript:return show_participant_assign_form('<?php echo $user->user_login; ?>');">Assign</a>
                    </div>
                <?php else: ?>
                    <?php echo $user->user_nicename; ?> 
                <?php endif; ?>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
        <?php else: ?>
			<div class="message info">
				No contributors have been assigned to this post.
			</div>
        <?php endif; ?>
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
        echo '</div></div>';

		echo '<div class="ad-module">';
		echo '<h4 class="toggle">Contributors</h4><div class="inner">';
        if ($assignment_desk->coauthors_plus_exists()){
            $this->display_multiple_assignees();
        	$this->display_participants();
        }
        else {
            $this->display_author();
        }
		echo '</div></div>';
		
    }

	/**
    * Save Assignment Desk post meta data
    */
    function save_post_meta_box($post_id, $post) {
        global $executed_already, $wpdb, $assignment_desk, $current_user;

		wp_get_current_user();
        
        // if ($executed_already){ return; } else { $executed_already = true; }
        if ($post->post_type == 'revision') { return;}
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
        
        if ($executed_already) { return; }
        
        //if (!wp_verify_nonce($_POST['ad-noncename'], plugin_basename(__FILE__))){
         //   return $post_id;
       // }

        // The user who pitched this story

		update_post_meta($post_id, '_ad_pitched_by', (int)$_POST['_ad_pitched_by']);
       
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
		if (current_user_can($assignment_desk->define_editor_permissions)) {
			foreach ($user_types as $user_type) {
				if ( in_array($user_type->term_id, $_POST['ad-participant-types']) ) {
					update_post_meta($post_id, "_ad_participant_type_$user_type->term_id", 'on');
				} else {
					update_post_meta($post_id, "_ad_participant_type_$user_type->term_id", 'off');
				}
			}
		} else {
			foreach ($user_types as $user_type) {
				$participant_type_state = get_post_meta($post_id, "_ad_participant_type_$user_type->term_id", true);
				if ( $participant_type_state != 'on' && $participant_type_state != 'off' ) {
					update_post_meta($post_id, "_ad_participant_type_$user_type->term_id", 'on');
				}
			}
		}

        // Single post author. Change the author.
        if($_POST['ad_author']){
            $author_id = $_POST['ad_author'];
            if(is_email($author_id)){
                // New User from the community.
                // @todo - Add a setting to enable/disable community membership.
                $author = _ad_create_user($author_id, $author_id, $_POST['_ad_author_nicename']);
                $author_id = $author->id;
            }
            // Assigned author is currently a WP User.
            else {
                $author = $wpdb->get_row(
                            $wpdb->prepare("SELECT * FROM $wpdb->users WHERE ID=%d", (int)$author_id));
                $author_id = $author->ID;
            }
            // Check if the user is not already the author
            if ($post['post_author'] != $author_id){
                // Make them the author, change the post status, and fire off the email
                $wpdb->update($wpdb->posts,
                        array('post_author' => $author_id, 'post_status' => __('waiting for reply')),
                        array('ID' => $post_id),
                        array('%d', '%s'),
                        array('%d'));
                $this->send_assignment_email($post_id, $author_id);
            }
        }

        // Multiple post authors using co-authors-plus
        if ($assignment_desk->coauthors_plus_exists()) {
            global $coauthors_plus;
        
            $participants = get_post_meta($post_id, '_ad_participants', false);
            $assignees = array();
            $role_users = array();
            // Users and their associated roles are sent over as username|rolename    
            // Split into an associative array.
            foreach ($_POST['ad-assignees'] as $user_and_role){
                $split = explode('|', $user_and_role);
                $user = $split[0];
                $role = $split[1];
                $assignees[] = $user;
                $role_users[$role][] = $user;
                delete_post_meta($post_id, '_ad_participants', $user);
            }
            //print_r($assignees);
            // Save the list of coauthors
            // $coauthors_plus->add_coauthors($post_id, $assignees);
            $_POST['coauthors'] = $assignees;
                    
            // Store each role as an array of usernames in the postmeta
            $post_is_waiting = false;
            foreach (array_keys($role_users) as $role){
                // check if the user is already in this role for this post.
                $previous_role_users = get_post_meta($post_id, "_ad_assignees_role_$role", true);
                if (!is_array($previous_role_users)){
                    $previous_role_users = array($previous_role_users, );
                }
                foreach ($role_users[$role] as $username){
                    // Never assigned before. Send email and record that we're waiting for their reply/
                    if(!in_array($username, $previous_role_users)){
                        $post_is_waiting = true;
                        $this->send_assignment_email($post_id, $username);
                        update_post_meta($post_id, '_ad_waiting_for_reply', $username);
                    }
                }
                update_post_meta($post_id, "_ad_assignees_role_$role", $role_users[$role]);
            }
        }
        $executed_already = 1;
    }
    
    function send_assignment_email($post_id, $username){
        // Get the template from the settings
        // Fill it out
        // Send it off
    }

}

} // end if(!class_exists)
?>