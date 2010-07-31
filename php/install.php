<?php
/*  Copyright 2010  

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
	
class assignment_desk_install {	
    
	function setup_db() {
	    global $wpdb;
	    $db_version = get_option("assignment_desk_db_version", "0");
	    
	    if(!$db_version || $db_version == "0"){
	        $this->init_db();
        }
        
      $this->init_taxonomies();
    }
    
	function init_db(){
	    global $wpdb, $assignment_desk;
	    
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      
		
		// Events Table
		// @todo Deprecate this. Don't you wish we could use custom post types?
		$event_table = $assignment_desk->tables["event"];
		if($wpdb->get_var("SHOW TABLES LIKE '$event_table'") != $event_table) {
			$sql = "CREATE TABLE $event_table (
                event_id bigint(20) unsigned NOT NULL auto_increment,
                target_type varchar(50) NOT NULL,
                target_id int(11) NOT NULL,
                description varchar(100) NOT NULL,
                user_login varchar(50) NOT NULL,
                created datetime NOT NULL,
                hidden tinyint(1) NOT NULL default '0',
                event_type varchar(25) NOT NULL,
                PRIMARY KEY  (event_id)
                ) DEFAULT CHARSET=latin1;";
			dbDelta($sql);
		}
		update_option("assignment_desk_db_version", "0.1"); 
	}
	
	function init_taxonomies(){
	    global $assignment_desk;
	    
	    $default_user_roles = array(
	        array( 'term' => 'Writer',
				   'args' => array( 'slug' => 'writer',
									'description' => 'The contributor will write the story.',)
			),
			array( 'term' => 'Photographer',
				   'args' => array( 'slug' => 'photographer',
									'description' => 'The contributor will take photos for the story.',)
			),
			array( 'term' => 'Videographer',
				   'args' => array( 'slug' => 'videographer',
									'description' => 'The contributor will shoot video for the story.',)
			),
	    );
	    foreach($default_user_roles as $term){
	        if(!is_term($term['term'])){
	            $assignment_desk->custom_user_roles->insert_term( $term['term'], $term['args'] );
            }
	    }
	    
	    $default_user_types = array(
	        array( 'term' => 'Professional Journalist',
				   'args' => array( 'slug' => 'professional',
									'description' => 'A professional journalist.',)
			),
			array( 'term' => 'Commmunity Contributor',
				   'args' => array( 'slug' => 'communitycontributor',
									'description' => 'Someone from the community that writes for the blog.',)
			),
			array( 'term' => 'Student Journalist',
				   'args' => array( 'slug' => 'studentjournalist',
									'description' => 'A student who writes for the blog.',)
			),
	    );
	    foreach($default_user_types as $term){
	        if(!is_term($term['term'])){
	            $assignment_desk->custom_user_types->insert_term( $term['term'], $term['args'] );
            }
	    }
	    
	    $default_pitch_statuses = array(
	        array( 'term' => 'New',
				   'args' => array( 'slug' => 'new',
									'description' => 'A new pitch that has not been edited.',)
			),
			array( 'term' => 'Approved',
				   'args' => array( 'slug' => 'approved',
									'description' => 'An editor has approved the pitch.',)
			),
			array( 'term' => 'Rejected',
				   'args' => array( 'slug' => 'rejected',
									'description' => 'The pitch was no accepted for development.',)
			),
			array( 'term' => 'On hold',
				   'args' => array( 'slug' => 'onhold',
									'description' => 'Work on the pitch is on hold.',)
			),
	    );
	    
	    foreach($default_pitch_statuses as $term){
	        if(!is_term($term['term'])){
	            $assignment_desk->custom_pitch_statuses->insert_term( $term['term'], $term['args'] );
            }
	    }
	}
}
?>