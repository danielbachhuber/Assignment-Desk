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
        // 20, 2 = execute our method very late (10 is default) and send 2 args,
        // the second being the post object
        add_action('save_post', array(&$this, 'save_post_meta_box'), 20, 2);
        add_action('edit_post', array(&$this, 'save_post_meta_box'), 20, 2);
        add_action('publish_post', array(&$this, 'save_post_meta_box'), 20, 2);
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
        wp_enqueue_script('ad-post-js', ASSIGNMENT_DESK_URL .'js/post.js', array('jquery', 'suggest'));
    }
    
    /**
    * Print out some global JS variables that we need to compose from PHPH variables so we can use them later.
    */
    function js_vars(){
        // AJAX link used for the autosuggest
        echo '<script type="text/javascript">';
        echo "var coauthor_ajax_suggest_link ='{admin_url()}admin-ajax.php?action=coauthors_ajax_suggest'";
        echo '</script>';
    }   
}

/**
* Read-only post meta_box for contributors.
*/
class ad_contributor_meta_box extends ad_post_meta_box {
    
    /**
    * Print basic pitch information.
    */
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
    
    /**
    * Print the editor link for the user.
    */
    function print_editor_link(){
        global $post;
        // @todo - Pull this from the taxonomy?
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
    
    /**
    * Main method to print out the box on the post.php page.
    */
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

        // @todo - Origin? (community or staff)
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
                                              $assignment_desk->custom_taxonomies->pitch_status_label);
        // Default status is new
        // @todo Use a default setting.
        if(!$current_status){
            $current_status = get_term_by("name", "New", $assignment_desk->custom_taxonomies->pitch_status_label);
        }
        else {
            $current_status = $current_status[0];
        }
        
        if($post->post_status == 'pitch'){
            // List all of the pitch statuses
            $pitch_statuses = get_terms($assignment_desk->custom_taxonomies->pitch_status_label,
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
    * This is shown when the co-authors-plus plugin is NOT active.
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
    
    function user_role_select($user_roles){
        echo "<label>Role</label>";
        echo "<select class='ad-user-role-select'>";
            foreach($user_roles as $user_role) {
                echo "<option value='{$user_role->term_id}'>{$user_role->name}</option>";
            }
        echo "</select>";
    }

    /**
    * Print a form to choose multiple users.
    * Print the current lists of assignees.
    */
    function multiple_assignees() {
        global $assignment_desk, $post, $wpdb;
        
        $user_roles = get_terms($assignment_desk->custom_taxonomies->user_role_label,
                                    array( 'get' => 'all', 'order' => "-name")); 
        ?>
        <div id="ad-assign-form" class="ad-module misc-pub-section">
            <h4>Assign this post to:</h4>
            <label>User</label>
            <input type="text" id="ad-assignee-search" size="30" maxlength="50">
            <br>
        <?php
            if(count($user_roles)) {
                echo $this->user_role_select($user_roles);
            }
            else {
                echo "You don't have any user roles defined. Go";
                echo "<a href='{admin_url()}edit-tags.php?taxonomy=user_role'>here</a>to create some.";
            }
        ?>
            <a id="ad-assign-button" class="button" <?php echo (count($user_roles)? '': 'disabled'); ?>>Add</a>
        </div>
        
        <div style="display:none" id="ad-hidden-user-role-select">
            <?php $this->user_role_select($user_roles); ?>
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
            echo "<div class='ad-module misc-pub-section' id='ad_assignees_role_{$user_role->term_id}'";
            echo ($num_users)? ">" : "style='display:none'>";
            echo "<h4>{$user_role->name}s</h4>";
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
                    echo "<li><input type='hidden' name='_ad-assignees[]' value='{$user->user_login}|{$user_role->term_id}'>{$user->user_nicename}</li>";
                }
            }
            echo "</ul></div>";
        }
        ?>
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
            <h4 id="ad-volunteer-count-wrap">Volunteers (<span id="ad-volunteer-count"><?php echo count($volunteers); ?></span>)</h4>
            <ul>
            <?php foreach($volunteers as $user_login):
                    $user = get_userdatabylogin($user_login);
                ?>
                <li>
                <?php if (!empty($user->user_login)): ?>
                    <div id="ad_volunteer_<?php echo $user->user_login; ?>">
                        <a href="?page=assignment_desk-contributor&user_login=<?php echo $user->user_login; ?>">
                        <?php echo $user->user_login; ?></a>
                        <a class="button"
                                onclick="javascript:return show_volunteer_assign_form('<?php echo $user->user_login; ?>');">Assign</a>
                    </div>
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
    
        if (is_plugin_active('co-authors-plus/co-authors.php')){
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
    function save_post_meta_box($post_id, $post) {
        
        global $executed_already, $wpdb, $assignment_desk;
        
        // if ($executed_already){ return; } else { $executed_already = true; }
        if ($post->post_type == 'revision') { return;}
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
        
        if ($executed_already) { return; }
        
        if (!wp_verify_nonce($_POST['_ad_noncename'], plugin_basename(__FILE__))){
            return $post_id;
        }

        // The user who pitched this story.
        if($_POST['_ad_pitched_by']){
            update_post_meta($post_id, '_ad_pitched_by', (int)$_POST['_ad_pitched_by']);
        }
        
        // The status of the story if it is still a pitch
        if($_POST['_ad_pitch_status'] && $post->status == __('pitch')){
            wp_set_object_terms($post_id,
                                (int)$_POST['_ad_pitch_status'],
                                $assignment_desk->custom_taxonomies->pitch_status_label);
        }

        // Single post author. Change the author.
        if($_POST['_ad_author']){
            $author_id = $_POST['_ad_author'];
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
        if($_POST['_ad-assignees']){
            global $coauthors_plus;
        
            $volunteers = get_post_meta($post_id, '_ad_volunteer', false);
            $assignees = array();
            $role_users = array();
            // Users and their associated roles are sent over as username|rolename    
            // Split into an associative array.
            foreach($_POST['_ad-assignees'] as $user_and_role){
                $split = explode('|', $user_and_role);
                $user = $split[0];
                $role = $split[1];
                $assignees[] = $user;
                $role_users[$role][] = $user;
                delete_post_meta($post_id, '_ad_volunteer', $user);
            }
            //print_r($assignees);
            // Save the list of coauthors
            $coauthors_plus->add_coauthors($post_id, $assignees);
                    
            // Store each role as an array of usernames in the postmeta
            $post_is_waiting = false;
            foreach(array_keys($role_users) as $role){
                // check if the user is already in this role for this post.
                $previous_role_users = get_post_meta($post_id, "_ad_assignees_role_$role", true);
                if(!is_array($previous_role_users)){
                    $previous_role_users = array($previous_role_users, );
                }
                foreach($role_users[$role] as $username){
                    // Never assigned before. Send email and record that we're waiting for their reply/
                    if(!in_array($username, $previous_role_users)){
                        $post_is_waiting = true;
                        $this->send_assignment_email($post_id, $username);
                        update_post_meta($post_id, '_ad_waiting_for_reply', $username);
                    }
                }
                update_post_meta($post_id, "_ad_assignees_role_$role", $role_users[$role]);
            }
            if($post_is_waiting){
                $wpdb->update($wpdb->posts,
                        array('post_status' => __('waiting for reply')),
                        array('ID' => $post_id),
                        array('%s'),
                        array('%d'));
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

$user = wp_get_current_user();
$assignment_desk_post_meta = null;

if(current_user_can('edit_posts')){
    $assignment_desk_post_meta = new ad_editor_post_meta_box();
}
else {
    $assignment_desk_post_meta = new ad_contributor_meta_box();
}

// Add necessary JS variables
add_action('admin_print_scripts', array(&$assignment_desk_post_meta, 'js_vars') );

} // end if(!class_exists)
?>