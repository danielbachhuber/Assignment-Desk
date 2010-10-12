<?php

if( !class_exists('ad_upgrade') ) {

/**
* Base class for operations on custom taxonomies
*/
class ad_upgrade {
	
	function __construct() {
		
	}
	
	function run_upgrade( $previous ) {
		
		if ( $previous < 0.8) $this->upgrade_08();
		
	}
	
	function upgrade_08() {
		global $assignment_desk, $wpdb;
		
		$query = "SELECT * FROM $wpdb->postmeta WHERE meta_key='_ad_votes_all';";
		$results = $wpdb->get_results( $query );
		if ( $results ) {
			foreach ( $results as $result ) {
				$meta_value = unserialize( $result->meta_value );
				foreach ( $meta_value as $key => $user_id ) {
					$assignment_desk->public_views->update_user_vote_for_post( $result->post_id, $user_id );
				}
				$query = "DELETE FROM $wpdb->postmeta WHERE meta_key='_ad_votes_all' AND post_id=$result->post_id;";
				$wpdb->get_results( $query );
			}
		}
		
		update_option( $assignment_desk->get_plugin_option_fullname('version'), '0.8' );
	}
	
	
}


}

?>