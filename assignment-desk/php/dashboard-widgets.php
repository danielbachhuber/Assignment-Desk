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
               wp_add_dashboard_widget('ad_editor_overview', 'Assignment Desk', array(&$this, 'editor_overview_widget'));
           // }
        }    
    }
   
    function editor_overview_widget() {
        global $assignment_desk, $current_user, $wpdb;
        
        $new_post_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) 
                                                        FROM {$assignment_desk->tables['pitch']} 
                                                        WHERE pitchstatus_id=1;"));
        if (!$new_post_count){
            $new_post_count = 0;
        }
        
        $approved_post_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) 
                                                            FROM {$assignment_desk->tables['pitch']} 
                                                            WHERE pitchstatus_id=2;"));
        if (!$new_post_count){
            $approved_post_count = 0;
        }
        
        $assignment_draft_count = 0;
        $assignment_overdue_count = 0;
?>

<table>
    <thead><tr><td colspan="2">Pitches</td></tr></thead>
    <tbody>
        <tr>
            <td class="label">
                <a href="admin.php?page=assignment_desk-pitch&active_status=New" class="ad-new-count">
                    <?php echo $new_post_count ?></a>
            </td>
            <td class="t"><a href="admin.php?page=assignment_desk-pitch&active_status=New">New</a></td>
        </tr>
        <tr>
            <td class="label">
                <a href="admin.php?page=assignment_desk-pitch&active_status=New" class="ad-approved-count">
                    <?php echo $new_post_count ?></a>
            </td>
            <td class="t"><a href="admin.php?page=assignment_desk-pitch&active_status=New">Approved</a></td>
        </tr>
    </tbody>
</table>
<br>
<table>
    <thead><tr><td colspan="2">Assignments</td></tr></thead>
    <tbody>
        <tr>
            <td class="label">
                <a href="admin.php?page=assignment_desk-assignments&active_status=Draft" class="ad_assignment-count">
                    <?php echo $assignment_draft_count ?></a>
            </td>
            <td class="t"><a href="admin.php?page=assignment_desk-assignments&active_status=Draft">With Drafts</a></td>
        </tr>
        <tr>
            <td class="label">
                <a href="admin.php?page=assignment_desk-assignments&active_status=Assigned" class="ad-assigned-count">
                    <?php echo $assignment_overdue_count ?></a>
            </td>
            <td class="t"><a href="admin.php?page=assignment_desk-assignments&active_status=Assigned">Assigned</a></td>
        </tr>
    </tbody>
</table>

<div class="inside">&raquo; <a href="?page=assignment_desk-index">Go to Assignment Desk landing page.</a></div>

<?php
   }
}
?>