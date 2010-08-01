<?php
/*
* The Assignments Controller.
* The entry point for this controller is Admin Menu -> Assignment Desk -> Assignments
*/

require_once('utils.php');

define('ASSIGNMENT_DESK_META_PREFIX', '_ad_');

/**
* Return a list of user_logins to the ajax-enabled text field on the assign view.
* TODO - Use a plugin that can handle more data with the full name as a "preview"
*/
function ajax_user_search(){
	global $wpdb;
       
	// if nonce is not correct it returns -1
	check_ajax_referer("assignment_desk-ajax-nonce");
	
    if (!empty($_GET)){
       $sql = "SELECT ID, user_login FROM $wpdb->users 
               WHERE user_login LIKE '" . $wpdb->escape($_GET['q']) . "%'
               ORDER BY user_login ASC";
       $users = $wpdb->get_results($sql);
       $ret = "";
       
       $sql = "SELECT user_login FROM {$assignment_desk->tables['pitch']}
               WHERE user_login LIKE '" . $wpdb->escape($_GET['q']) . "%'
               ORDER BY user_login ASC";
       $signed_up = $wpdb->get_results($sql);
       
       $users = array_merge($users, $signed_up);
       if($users){
           foreach ($users as $user){
                $ret = "$ret {$user->user_login}|{$user->ID}\n";
           }
       }
       else {
           $ret .= 'Not found';
       }
       echo $ret;
   }
   // Prevent the rest of WP from printing out.
   die();
   }

// AJAX
add_action('wp_ajax_user_search', 'ajax_user_search');
?>
