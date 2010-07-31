<?php

if (!function_exists('_ad_create_user')){
	function _ad_create_user($user_login, $user_nicename, $user_email){
	    global $wpdb;

	    $userdata = array();
	    $userdata['user_login'] = $user_login;
	    $userdata['user_nicename'] = $user_nicename;
	    $userdata['user_email'] = $user_email;
	    // TODO - Talk to the NYTimes people about whether or not a password has to be set
	    // in order to work with their authentication.
	    $userdata['user_pass'] = strrev($user_login);
	    // TODO - Add a setting for the default user role
	    $userdata['role'] = 'contributor';
	    $user_id = wp_insert_user($userdata);

	    $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users
	                                           WHERE ID=%d", $user_id));
	    // Add usermeta marking them as community member
	    update_usermeta($user_id, "_ad_origin", 'community');

	    /* 
	     TODO - Ask the times if we can get email addresses for nytimes 
	     logins. Users may volunteer with their nytimes id but we wont have 
	     an email unless nytimes id is email
	     */
	     return $user;
	}
}
if (!class_exists('assignment_desk_post_meta')) {
	
require_once(ABSPATH . 'wp-includes/pluggable.php');

/**
* Post meta_box base class for the assignment desk.
*/
class ad_post_meta_box {
    
   function __construct() {
		// Set up metabox and related actions
		add_action('admin_menu', array(&$this, 'add_post_meta_box'));
		add_action('save_post', array(&$this, 'save_post_meta_box'));
		add_action('edit_post', array(&$this, 'save_post_meta_box'));
		add_action('publish_post', array(&$this, 'save_post_meta_box'));
	}
	
	/**
	 * Adds Assignment Desk meta_box to Post/Page edit pages 
	 */
	function add_post_meta_box() {
		global $assignment_desk;
		
		if (function_exists('add_meta_box')) {
			add_meta_box('assignment-desk', 
			                __('Assignment Desk', 'assignment-desk'), 
			                array(&$this, 'meta_box'), 
			                'post', 
			                'side', 
			                'high');
		    $this->add_css();
		    $this->add_javascript();
		}
	}
	
	/**
	 * Adds Assignment Desk CSS to Post/Page edit pages 
	 */
	function add_css(){
	    // Enqueue the ad_post_meta.css
	    wp_enqueue_style('ad-post-meta-style', ASSIGNMENT_DESK_URL.'css/post_meta.css', false, false, 'all');
	    wp_enqueue_style('ad-fancybox', ASSIGNMENT_DESK_URL . 'js/fancybox/jquery.fancybox-1.3.1.css', false, false, 'all');
	}
	
	/**
	 * Adds Assignment Desk javascript to Post/Page edit pages 
	 */
	function add_javascript(){
	    wp_enqueue_script('jquery-fancybox-js', ASSIGNMENT_DESK_URL .'js/fancybox/jquery.fancybox-1.3.1.pack.js', 
                    array('jquery'));
        wp_enqueue_script('ad-post-js', ASSIGNMENT_DESK_URL .'js/post.js', array('jquery'));
	}
}

/**
* Read-only post meta_box for contributors.
*/
class ad_contributor_meta_box extends ad_post_meta_box {
	
	function print_pitch_info(){
	    global $post;
	    echo '<div id="ad-pitch-info" class="ad-module misc-pub-section">';
	    echo '<h4><a id="toggle-ad-pitch-detail">Pitch</a></h4>';
	    echo "<div id='ad-pitch-detail'>";
	    
        $pitched_by = get_post_meta($post->ID, '_ad_pitched_by');
	    if(count($pitched_by)) {
	        foreach($pitched_by as $pitcher_id){
	            $user = get_userdata((int)$pitcher_id);
	            echo "<p>Pitched by: {$user->nicename} </p>";
	        }
	    }
	    // TODO - Origin? (community or staff)
	    echo '</div>';
        echo '</div>';
	}
	
	function print_editor_link(){
	    global $post;
	    $editor_id = get_post_meta($post->ID, '_ad_editor', true);
	    if($editor_id){
	        $editor = get_userdata($editor_id);
	        $contact_url = "?page=assignment_desk-contributor&action=contact_editor&post_id={$post->ID}>";
	        $editor_name = $editor->nice_name;
	        echo "Editor: <a href='$contact_url'>$editor_name</a>";
        }
        else {
	        echo "Editor: None";
	    }
	}
	
	function meta_box($pitch = null){
	    global $post, $edit_flow, $assignment_desk;
	    
	    // Link to contact the editor
	    $this->print_editor_link();
	    
	    // Other links ?>
	    <ul class="plain">
            <li><a href="#ad-instructions" class="fancybox">Story Instructions</a>
                <div style="display:none"> <div id="ad-instructions">
                    <?php include_once($assignment_desk->templates_path . '/contributor/instructions.php'); ?>
                </div></div>
            </li>
            <li><a class="fancybox" href="#ad-related-content">Related Content</a>
                <div style="display:none"> <div id="ad-related-content">
                    <?php include_once($assignment_desk->templates_path . '/contributor/related-content.php'); ?>
                </div></div>

            </li>
        </ul>
<?php
        $this->print_pitch_info();
	}

	function save_post_meta_box(){
	}
}

/**
* Read-write post meta_box for editors
*/
class ad_editor_post_meta_box extends ad_post_meta_box {
	
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
	function pitch_info(){
		global $post, $wpdb;
	    echo '<h4><a id="toggle-ad-pitch-detail">Pitch</a></h4>';
	    echo "<div id='ad-pitch-detail'>";
	    
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

	    // TODO - Origin? (community or staff)
	    echo '</div>';
	}
	
	/**
	* Print the pitch status form.
	* If there is no status the pitch is New.
	* If the post is not in the pitch status don't show the form.
	*/
	function pitch_status(){
		global $post, $wpdb, $assignment_desk;

		echo '<label>Pitch Status:</label>';
		// What is the status of this Pitch?
		$current_status = wp_get_object_terms($post->ID,
											  $assignment_desk->custom_pitch_statuses->taxonomy);
		// Default status is new
		// TODO - Use a default setting.
		if(!$current_status){
			$current_status = get_term_by("name", "New", $assignment_desk->custom_pitch_statuses->taxonomy);
		}
		else {
			$current_status = $current_status[0];
		}
		
		if($post->post_status == 'pitch'){
			// List all of the pitch statuses
			$pitch_statuses = get_terms($assignment_desk->custom_pitch_statuses->taxonomy,
										array( 'get' => 'all'));
			echo "<select name='_ad_pitch_status'>";
			foreach($pitch_statuses as $status){
				echo "<option value='{$status->term_id}' ";
				if($status->term_id == $current_status->term_id){
					echo "selected";
				}
				echo ">{$status->name}</option>";
			}
			echo "</select>";
		}
	}
	
	/**
	* Print a form to choose the user.
	* This is shown when the co-authors plugin is NOT active.
	*/
	function author(){
		global $wpdb, $post;
		
		$users = $wpdb->get_results("SELECT ID, user_nicename FROM $wpdb->users");
		echo "<div class='ad-module misc-pub-section'>";
		echo "<label>Author:</label>";
		echo "<select name='_ad_author'>";
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
	* Print a form to choose multiple users.
	* Print the current lists of assignees.
	*/
	function multiple_assignees() {
		?>
		<div class="ad-module misc-pub-section">
		    <h4>Assign this post to:</h4>
		    <form>
		        <label for="ad_user_search_text">User</label>
		        <input type="text" name="_ad_user_search" id="ad_user_search_text" size="30" maxlength="50" value="Search...">
		        <br>
		        
		        <label for="ad_assigned_role_select">Role</label>
		        <select name="_ad_assigned_role" id="ad_assigned_role_select">
		            <option value="writer">Writer</option>
		            <option value="photographer">Photographer</option>
		        </select>
		        <button class="button" value="submit">Assign</button>
		    </form>
		</div>
		
		<div class="ad-module misc-pub-section">
			<h4>Users assigned to this post </h4>
			<ul class="user-list">
			    <li>Tommy Tester (Writer)</li>
			    <li>Wendy Writer (Photographer)</li>
			</ul>
		</div>
<?php
	}
	
	/**
	* Print the list of volunteers.
	*/
	function volunteers(){
		global $post;
		// Print the volunteers (if any and came from a pitch)
	    $volunteers = get_post_meta($post->ID, '_ad_volunteer', false);

		if(count($volunteers)): ?>	    
	    <div class="ad-module misc-pub-section">
            <h4>Volunteers (<?php echo count($volunteers); ?>)</h4>
            <ul>
            <?php foreach($volunteers as $user_id):
					$user = get_userdata((int)$user_id);
				?>
                <li>
                <?php if (!empty($user->user_login)): ?>
                    <a href="?page=assignment_desk-contributor&user_login=<?php echo $user->user_login; ?>">
                    <?php echo $user->user_login; ?></a>
                    <form method="GET" style="display:inline">
                        <input type="hidden" name="page" value="assignment_desk-assignments">
                        <input type="hidden" name="action" value="editor_assign">
                        <input type="hidden" name="pitch_id" value="<?php echo $pitch->pitch_id; ?>">
                        <input type="hidden" name="user_login_text" value="<?php echo $user->user_login ?>">
                        <button>Assign</button>
                    </form>
                <?php else: ?>
                    <?php echo $user->user_nicename; ?> 
                <?php endif; ?>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
		<?php else: ?>
			<i>No Volunteers</i>
		<?php endif; ?>
<?php
	}
	
	/**
	* Launch the Assignment Desk post meta_box.
	*/
	function meta_box(){
	    global $post;
	
		echo '<input type="hidden" name="_ad_noncename" id="_ad_noncename" value="' . 
				    wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
	    
		echo '<div id="ad-pitch-info" class="ad-module misc-pub-section">';
	    $this->pitch_info();
		if($post->post_status == __('pitch')){
			$this->pitch_status();
		}
		echo '</div>';
	
		if (is_plugin_active('co-authors/co-authors.php')){
		 	$this->multiple_assignees();
		}
		else {
			$this->author();
		}

		$this->volunteers();
	}
	
	/**
	* Save Assignment Desk post meta data
	*/
	function save_post_meta_box($post_id) {
		global $wpdb, $assignment_desk;
		
		if (!wp_verify_nonce($_POST['_ad_noncename'], plugin_basename(__FILE__))){
			return $post_id;
		}
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		    return $post_id;
		}
		
		$post = query_posts('p=' . $post_id);
		
		// The user who pitched this story.
		if($_POST['_ad_pitched_by']){
			update_post_meta($post_id, '_ad_pitched_by', (int)$_POST['_ad_pitched_by']);
		}
		
		// The status of the story if it is still a pitch
		if($_POST['_ad_pitch_status'] && $post->status == __('pitch')){
			wp_set_object_terms($post_id,
								(int)$_POST['_ad_pitch_status'],
								$assignment_desk->custom_pitch_statuses->taxonomy);
		}
		
		// Single post author
		if($_POST['_ad_author']){
			$author_id = $_POST['_ad_author'];
			if(is_email($author_id)){
				// New User from the community.
				// TODO - Add a setting to enable/disable community membership.
				$author = _ad_create_user($author_id, $author_id, $_POST['_ad_author_nicename']);
				$author_id = $author->id;
			}
			// Current user id
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
	}
	
	function send_assignment_email($post_id, $author_id){
		// Get the template from the settings
		// Fill it out
		// Send it off
	}
}

$user = wp_get_current_user();
$assignment_desk_post_meta = null;

if(current_user_can(5)){
	$assignment_desk_post_meta = new ad_editor_post_meta_box();
}
else {
	$assignment_desk_post_meta = new ad_contributor_meta_box();
}

} // end if(!class_exists)
?>