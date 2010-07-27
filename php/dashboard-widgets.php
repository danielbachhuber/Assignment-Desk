<?php

/*
* This class manages the widgets that appear in the Wordpress Administration Dashboard.
* The assignment_desk class holds an instance of this class as a member.
*/
class assignment_desk_dashboard_widgets {
       
    /*
     * Call add_dashboard_widgets when the dashboard is being constructed.
     */
    function __construct () {
        add_action('wp_dashboard_setup', array(&$this, 'add_dashboard_widgets'));
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
    }

	function count_pitches($status){
		global $assignment_desk, $wpdb;
		$term_id = $wpdb->get_var("SELECT term_id FROM $wpdb->terms WHERE name=$status");
		$count = $wpdb->get_var($wpdb->prepare("SELECT count FROM $wpdb->term_taxonomy 
													WHERE taxonomy = %s AND term_id = %d", 
													$assignment_desk->custom_pitch_statuses->get_taxonomy_id(),
													$term_id));
		$count = $count ? $count : 0;
		return $count;
	}
   
    function editor_overview_widget() {
        global $assignment_desk, $current_user, $wpdb;

		$new_pitches_count = $this->count_pitches('New');
		$approved_post_count = $this->count_pitches('Approved');
?>
<div class="table">
<table>
    <thead><tr><td colspan="2">Pitches</td></tr></thead>
    <tbody>
        <tr>
            <td class="label">
                <a href="admin.php?page=assignment_desk-pitch&active_status=New" class="ad-new-count">
                    <?php echo $new_pitches_count ?></a>
            </td>
            <td class="t"><a href="admin.php?page=assignment_desk-pitch&active_status=New">New</a></td>
        </tr>
        <tr>
            <td class="label">
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
}
?>