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
		global $assignment_desk;
		
		//update_option( $assignment_desk->get_plugin_option_fullname('version'), '0.8' );
	}
	
	
}


}

?>