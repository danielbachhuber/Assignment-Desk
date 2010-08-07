<?php
if(!class_exists('ad_public_controller')){
	
class ad_public_views {
	
	function __construct() { 
	
	}
	
	function init() {
		
		// Run save_pitch_form() at WordPress initialization
		$message = $this->save_pitch_form();
		
		if ( $message ) {
			// @todo Add a message to top of form if exists
		}
		
		add_filter('the_content', array(&$this, 'filter_show_public_pages') );
		add_filter('the_content', array(&$this, 'show_pitch_form') );
	}
	
	function show_pitch_form($the_content) {
		global $assignment_desk;
		
		$edit_flow_exists = false;
		if (class_exists('edit_flow')) {
			global $edit_flow;
			$edit_flow_exists = true;
		}
		$options = get_option($assignment_desk->get_plugin_option_fullname('general'));
		
		$template_tag = '<!--assignment-desk-pitch-form-->';

		$pitch_form = '';
		
		$pitch_form .= '<form method="post" id="assignment_desk_pitch_form">'
					. '<fieldset><label for="assignment_desk_title">Title</label>'
					. '<input type="text" id="assignment_desk_title" name="assignment_desk_title" /></fieldset>';
					
		$pitch_form .= '<fieldset><label for="assignment_desk_tags">Tags</label>'
					. '<input type="text" id="assignment_desk_tags" name="assignment_desk_tags" /></fieldset>';
					
		$pitch_form .= '<fieldset>'
					. '<input type="submit" value="Submit Pitch" id="assignment_desk_submit" name="assignment_desk_submit" /></fieldset>';					
					
		$pitch_form .= '</form>';

		$the_content = str_replace($template_tag, $pitch_form, $the_content);
		return $the_content;
	}
	
	function save_pitch_form() {
		global $assignment_desk;

		$edit_flow_exists = false;
		if (class_exists('edit_flow')) {
			global $edit_flow;
			$edit_flow_exists = true;
		}
		
		$options = get_option($assignment_desk->get_plugin_option_fullname('general'));
		
		// @todo Check for a nonce
		// @todo Sanitize all of the fields
		
		if ($_POST['assignment_desk_submit']) {
		
			$new_pitch = array();
			$new_pitch['post_title'] = $_POST['assignment_desk_title'];
			$new_pitch['post_content'] = '';
			if ($edit_flow_exists) {
				//$status_name = $edit_flow->custom_status->ef_get_status_name('id', $options['default_workflow_status']);
				$default_status = get_term_by('term_id', $options['default_workflow_status'], 'post_status');
				$new_pitch['post_status'] = $default_status->slug;
			} else {
				$new_pitch['post_status'] = 'draft';
			}
			$post_id = wp_insert_post($new_pitch);
			var_dump($post_id);
		}
		
		return null;
		
	}
	
	
	/*
	* Replace an html comment <!--assignment-desk-all-stories-> with ad public pages.
	*/
	function filter_show_public_pages($the_content){
		global $wpdb, $assignment_desk;
	  
		$tag = '<!--assignment-desk-all-stories-->';
		$start = strpos($the_content, $tag);
		$my_content  = $the_content;
        if ($start){
            $before_ad = substr($the_content, 0, $start);
            $after_ad = substr($the_content, $start + strlen($tag), strlen($the_content));
            $ad = $this->public_content();
            $my_content = $before_ad . $ad . $after_ad;
        }
        
        return $my_content;
	}
	
	function public_content(){
	    return 'Im public yo.';
	}
} // END:class ad_public_controller

} // END:if(!class_exists('ad_public_controller'))
?>