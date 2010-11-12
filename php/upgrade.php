<?php

if( !class_exists('ad_upgrade') ) {

/**
* Base class for operations on custom taxonomies
*/
class ad_upgrade {
	
	function __construct() {
		
	}
	
	/**
	 * Run through the upgrade process depending on prior version number
	 */
	function run_upgrade( $previous ) {
		
		if ( version_compare( $previous, '0.8', '<' ) ) $this->upgrade_08();
		
		// @todo Upgrade to version 0.9.2
		
	}
	
	/**
	 * Upgrade to v0.8
	 */
	function upgrade_08() {
		global $assignment_desk, $wpdb;
		
		// Migrate all of the prior vote data to the new custom table we're using
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
		
		// Add a default value for the public-facing logged out message setting
		$public_facing_options = $assignment_desk->public_facing_options;
		$public_facing_options['public_facing_logged_out_message'] = _('Sorry, you must be logged in to vote or volunteer.');
        update_option($assignment_desk->get_plugin_option_fullname('public_facing'), $public_facing_options);
		$assignment_desk->public_facing_options = $public_facing_options;
		
		update_option( $assignment_desk->get_plugin_option_fullname('version'), '0.8' );
	}
	
	/**
	 * Upgrade to v0.9.2
	 */
	function upgrade_092() {
	
		// @todo Migrate options for pitch form on editorial metadata
		
		//update_option( $assignment_desk->get_plugin_option_fullname('version'), '0.9.2' );
		
	}
	
}


}

?>