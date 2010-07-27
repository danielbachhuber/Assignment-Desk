<?php
if (!class_exists('assignment_desk_post_meta')) {

class assignment_desk_post_meta {
    
   function __construct() {
		// Set up metabox and related actions
		add_action('admin_menu', array(&$this, 'add_post_meta_box'));
		add_action('save_post', array(&$this, 'save_post_meta_box'));
		add_action('edit_post', array(&$this, 'save_post_meta_box'));
		add_action('publish_post', array(&$this, 'save_post_meta_box'));
	}
	
	/**
	 * Adds Edit Flow meta_box to Post/Page edit pages 
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
		    $this->add_css();
		    $this->add_javascript();
		}
	}
	
	function add_css(){
	    // Enqueue the ad_post_meta.css
	    wp_enqueue_style('ad-post-meta-style', ASSIGNMENT_DESK_URL.'css/post_meta.css', false, false, 'all');
	    wp_enqueue_style('ad-fancybox', ASSIGNMENT_DESK_URL . 'js/fancybox/jquery.fancybox-1.3.1.css', false, false, 'all');
	}
	
	function add_javascript(){
	    wp_enqueue_script('jquery-fancybox-js', ASSIGNMENT_DESK_URL .'js/fancybox/jquery.fancybox-1.3.1.pack.js', 
                    array('jquery'));
        wp_enqueue_script('ad-post-js', ASSIGNMENT_DESK_URL .'js/post.js', array('jquery'));
	}
	
	function post_meta_box(){
	    global $post, $edit_flow;
		$user = wp_get_current_user();
		
		if(current_user_can('editor')){
		    $this->read_write_post_meta_box();
		}
		else {
		    $this->read_only_post_meta_box();
		}
	}
	
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
	
	function read_write_post_meta_box(){
	    global $post, $wpdb, $edit_flow, $assignment_desk;
	    
	    $this->print_pitch_info();
	    ?>
	    
		<div class="ad-module misc-pub-section">
		
		    <h4>Assign this post to:</h4>
		    <form>
		        <label for="ad_user_search_text">User</label>
		        <input type="text" name="ad_user_search" id="ad_user_search_text" size="30" maxlength="50" value="Search...">
		        <br>
		        
		        <label for="ad_assigned_role_select">Role</label>
		        <select name="ad_assigned_role" id="ad_assigned_role_select">
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
	    
	    // Print the current Assignees (if any)
	    
	    // Print the volunteers (if any and came from a pitch)
	    $volunteers = array();

?>	    
	    <div class="ad-module misc-pub-section">
            <h4>Volunteers (<?php echo count($volunteers); ?>)</h4>
            <ul>
            <?php foreach($volunteers as $user): ?>
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
<?php
	}
	
	function read_only_post_meta_box($pitch = null){
	    global $post, $edit_flow, $assignment_desk;
	    
	    // Show the due date
	    
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

$assignment_desk_post_meta = new assignment_desk_post_meta();

} // end if(!class_exists)
?>