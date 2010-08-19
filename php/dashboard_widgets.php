<?php

/*
* This class manages the widgets that appear in the Wordpress Administration Dashboard.
* The assignment_desk class holds an instance of this class as a member.
*/
class ad_dashboard_widgets {
    
    function init(){
        add_action('wp_dashboard_setup', array(&$this, 'add_dashboard_widgets'));
        add_action('admin_init', array(&$this, 'respond_to_story_invite'));
    }
       
    function add_dashboard_widgets () {
        global $assignment_desk, $current_user;
           
        // If the current user is a Editor or greater, show the editor_overview_widget
        if ($current_user->has_cap('publish_posts')) {
                   
           // Add the editor_overview_widget widget, if enabled
           // if ($assignment_desk->get_plugin_option('editor_overview_widget_enabled')) {
               wp_add_dashboard_widget('ad_editor_overview', 'Story Pitches', array(&$this, 'editor_overview_widget'));
           // }
        }
        
        wp_add_dashboard_widget('ad_assignments', 'Assignments', array(&$this, 'assignments_widget'));
    }

	function count_pitches($status){
		global $assignment_desk, $wpdb;
		$term_id = $wpdb->get_var("SELECT term_id FROM $wpdb->terms WHERE name=$status");
		$count = $wpdb->get_var($wpdb->prepare("SELECT count FROM $wpdb->term_taxonomy 
													WHERE taxonomy = %s AND term_id = %d", 
													$assignment_desk->custom_taxonomies->assignment_status_label,
													$term_id));
		$count = $count ? $count : 0;
		return $count;
	}
   
    function editor_overview_widget() {
        global $assignment_desk, $current_user, $wpdb;

		$new_pitches_count = $this->count_pitches('New');
		$approved_post_count = $this->count_pitches('Approved');
		
		if($_REQUEST['ad-dashboard-editor-messages']){
            foreach($_REQUEST['ad-dashboard-assignment-messages'] as $messages){
                echo "<div class='message info'>$message</div>";
            }
        }
?>
<div class="table">
<table>
    <tbody>
        <tr>
            <td class="b">
                <a href="admin.php?page=assignment_desk-pitch&active_status=New" class="ad-new-count">
                    <?php echo $new_pitches_count ?></a>
            </td>
            <td class="t"><a href="admin.php?page=assignment_desk-pitch&active_status=New">New</a></td>
        </tr>
        <tr>
            <td class="b">
                <a href="admin.php?page=assignment_desk-pitch&active_status=New" class="ad-approved-count">
                    <?php echo $approved_post_count ?></a>
            </td>
            <td class="t"><a href="admin.php?page=assignment_desk-pitch&active_status=New">Approved</a></td>
        </tr>
    </tbody>
</table>
</div>
<div class="inside">&raquo; <a href="?page=assignment_desk-index">Go to Assignment Desk landing page.</a></div>

<?php
    }
   
    function assignments_widget(){
        global $assignment_desk, $wpdb, $current_user;
       
        get_currentuserinfo();
        $pending_posts = array();
        
        if($_REQUEST['ad-dashboard-assignment-messages']){
            foreach($_REQUEST['ad-dashboard-assignment-messages'] as $message){
                echo "<div class='message info'>$message</div>";
            }
        }
        // Find all of the posts this user participates in.
        $participant_posts = $wpdb->get_results("SELECT post_id FROM $wpdb->postmeta 
                                                  WHERE meta_key = '_ad_participant_{$current_user->user_login}'
                                                  ORDER BY post_id");
        
        if ( ! $participant_posts ){
            $participant_posts = array();
        }
        
        $roles = $assignment_desk->custom_taxonomies->get_user_roles();
        
        foreach($participant_posts as $post ){
            foreach($roles as $user_role){
                // Get all of the roles this user has for this post
                $participant_record = get_post_meta($post->post_id, "_ad_participant_role_$user_role->term_id", true);
                if($participant_record) {
                    foreach ($participant_record as $user_login => $status ){
                        if( $user_login == $current_user->user_login && $status == 'pending' ){
                            $pending_posts[] = array($post->post_id, $user_role);
                        }
                    }
                }
            }
        }
        $count_pending = count($pending_posts);
?>
<p class="sub"><?php echo $count_pending; ?> pending assignment<?php echo ($count_pending != 1)? 's': ''?>.</p>
<?php foreach($pending_posts as $pending): ?>
    <div>
        <?php $post = get_post($pending[0]); ?>
        <?php 
        echo "{$post->post_title} | {$pending[1]->name}";
        echo " <a class='button' href='" . admin_url() . "index.php?participant_response=accepted&post_id=$post->ID&role_id={$pending[1]->term_id}'>Accept</a> ";
        echo "<a class='button' href='" . admin_url() . "index.php?participant_response=declined&post_id=$post->ID&role_id={$pending[1]->term_id}'>Decline</a>";  
        ?>
    </div>
<?php endforeach; ?>

<div class="inside">&raquo; <a href="?page=assignment_desk-index">Go to Assignment Desk landing page.</a></div>
<?php
   }
   
   function respond_to_story_invite(){
       global $current_user, $assignment_desk, $coauthors_plus, $user_ID;
       
       $response = $_GET['participant_response'];
       $post_id = (int)$_GET['post_id'];
       $role_id = (int)$_GET['role_id'];
          
       get_currentuserinfo();
       if ('' == $user_ID || $user_ID != $post_id) {
           $_REQUEST['ad-dashboard-assignment-messages'][] = _('Unauthorized assignment response. This is fishy.');
       }
       
       $_REQUEST['ad-dashboard-assignment-messages'] = array();
       
       if ($response && $post_id && $role_id){
           $participant_record = get_post_meta($post_id, "_ad_participant_role_$role_id", true);
           // This will not evaluate to true unless the user is currently pending for this role on this post.
           if($participant_record && $participant_record[$current_user->user_login] == 'pending'){
               $participant_record[$current_user->user_login] = $response;
               if($response == 'accepted'){
                   $_REQUEST['ad-dashboard-assignment-messages'][] = _('Thank you.');
                   // Add as a co-author
                   if($assignment_desk->coauthors_plus_exists()){
                       $coauthors_plus->add_coauthors($post_id, array($current_user->user_login), true);
                   }
               }
               else if($response == 'declined'){
                   $_REQUEST['ad-dashboard-assignment-messages'][] = _('Sorry!.');
               }
           }
           update_post_meta($post_id, "_ad_participant_role_$role_id", $participant_record);
       }
   }
}
?>