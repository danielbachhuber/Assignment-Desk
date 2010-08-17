<?php

if ( !class_exists( 'ad_settings' ) ){
  
/**
 * Class for managing all Assignment Desk settings views
 */
  class ad_settings
  {
  
    function __construct() {
      
    }

	function init() {
		global $assignment_desk;
		
		register_setting( $assignment_desk->options_group, $assignment_desk->get_plugin_option_fullname('general'), array(&$this, 'assignment_desk_validate') );
		
		add_settings_section( 'story_pitches', 'Story Pitches', array(&$this, 'story_pitches_setting_section'), $assignment_desk->top_level_page );
		add_settings_field( 'pitch_form_enabled', 'Enable pitch forms', array(&$this, 'pitch_form_enabled_option'), $assignment_desk->top_level_page, 'story_pitches' );
		add_settings_field( 'default_new_assignment_status', 'Default assignment status', array(&$this, 'default_new_assignment_status_option'), $assignment_desk->top_level_page, 'story_pitches' );
		add_settings_field( 'default_workflow_status', 'Default workflow status', array(&$this, 'default_workflow_status_option'), $assignment_desk->top_level_page, 'story_pitches' );
		add_settings_field( 'pitch_form_elements', 'Pitch form elements', array(&$this, 'pitch_form_elements_option'), $assignment_desk->top_level_page, 'story_pitches' );
		
		add_settings_section( 'assignment_management', 'Assignment Management', array(&$this, 'assignment_management_setting_section'), $assignment_desk->top_level_page );
		add_settings_field( 'assignment_email_notifications_enabled', 'Enable assignment email notifications', array(&$this, 'assignment_email_notifications_enabled_option'), $assignment_desk->top_level_page, 'assignment_management' );
		add_settings_field( 'assignment_email_template_subject', 'Subject template for notifications', array(&$this, 'assignment_email_template_subject_option'), $assignment_desk->top_level_page, 'assignment_management' );

		add_settings_field( 'assignment_email_template', 'Template for notifications', array(&$this, 'assignment_email_template_option'), $assignment_desk->top_level_page, 'assignment_management' );
		
		add_settings_section( 'public_facing_views', 'Public-Facing Views', array(&$this, 'public_facing_views_setting_section'), $assignment_desk->top_level_page );
		add_settings_field( 'public_facing_elements', 'Public-facing elements', array(&$this, 'public_facing_elements_option'), $assignment_desk->top_level_page, 'public_facing_views' );
		add_settings_field( 'public_facing_functionality', 'Public-facing functionality', array(&$this, 'public_facing_functionality_option'), $assignment_desk->top_level_page, 'public_facing_views' );		
		
		add_settings_section( 'miscellaneous', 'Miscellaneous', array(&$this, 'miscellaneous_setting_section'), $assignment_desk->top_level_page );
		add_settings_field( 'google_maps_api_key', 'Google Maps API key', array(&$this, 'google_maps_api_key_option'), $assignment_desk->top_level_page, 'miscellaneous' );
		
	}
	
	function story_pitches_setting_section() {
		global $assignment_desk;
		echo "Add an Assignment Desk pitch form to any page or post by adding <code>&#60;!--$assignment_desk->pitch_form_key--&#62;</code> where you'd it to appear.";
	}
	
	function pitch_form_enabled_option() {
		global $assignment_desk;
		$options = $assignment_desk->general_options;
		
		echo '<input id="pitch_form_enabled" name="assignment_desk_general[pitch_form_enabled]" type="checkbox"';
		if ($options['pitch_form_enabled']) {
			echo ' checked="checked"';
		}
		echo ' />';
	}
	
	function default_new_assignment_status_option() {
		global $assignment_desk;
		$options = $assignment_desk->general_options;
		$assignment_statuses = $assignment_desk->custom_taxonomies->get_assignment_statuses();
		if (count($assignment_statuses)) {
			echo '<select id="default_new_assignment_status" name="assignment_desk_general[default_new_assignment_status]">';
			foreach ($assignment_statuses as $assignment_status) {
				echo "<option value='$assignment_status->term_id'";
				if ($options['default_new_assignment_status'] == $assignment_status->term_id) {
					echo ' selected="selected"';
				}
				echo ">$assignment_status->name</option>";
	 		}
			echo '</select>';
		} else {
			echo "No statuses set. Please <a href='" . get_bloginfo('url') . "/wp-admin/edit-tags.php?taxonomy=".$assignment_desk->custom_taxonomies->assignment_status_label . "'>create at least one assignment status</a>.";
		}
	}
	
	/**
	 * Define post status for newly submitted pitches
	 * @requires Edit Flow
	 */
	function default_workflow_status_option() {
		global $assignment_desk;
		if (class_exists('edit_flow')) {
			global $edit_flow;
			$options = $assignment_desk->general_options;
			$post_statuses = $edit_flow->custom_status->get_custom_statuses();
			echo '<select id="default_workflow_status" name="assignment_desk_general[default_workflow_status]">';
			foreach ($post_statuses as $post_status) {
				echo "<option value='$post_status->term_id'";
				if ($options['default_workflow_status'] == $post_status->term_id) {
					echo ' selected="selected"';
				}
				echo ">$post_status->name</option>";
 			}
			echo '</select><br />';
			echo '<span class="description">Indicate the status in your workflow a new story pitch should be given.';
		} else {
			echo 'Please enable Edit Flow to define custom workflow statuses. Without Edit Flow, new pitches will be saved with a post status of "draft"';
		}
		
	}
	
	/**
	 * Enable/disable data elements on pitch form
	 */
	function pitch_form_elements_option() {
		global $assignment_desk;
		if ($assignment_desk->edit_flow_exists()) {
			global $edit_flow;
		}
		$options = $assignment_desk->general_options;
		echo '<ul>';
		// Title
		echo '<li><input type="checkbox" disabled="disabled" checked="checked" />&nbsp;<label for="pitch_form_title">Title</label></li>';
		// Description
		if ($assignment_desk->edit_flow_exists()) {
			echo '<li><input id="pitch_form_description_enabled" name="assignment_desk_general[pitch_form_description_enabled]" type="checkbox"';
			if ($options['pitch_form_description_enabled']) {
				echo ' checked="checked"';
			}
			echo ' />&nbsp;<label for="pitch_form_description_enabled">Description</label></li>';
		} else {
				echo '<li>Please enable Edit Flow to allow description field.</li>';
		}
		// Categories
		echo '<li><input id="pitch_form_categories_enabled" name="assignment_desk_general[pitch_form_categories_enabled]" type="checkbox"';
		if ($options['pitch_form_categories_enabled']) {
			echo ' checked="checked"';
		}
		echo ' />&nbsp;<label for="pitch_form_categories_enabled">Categories</label></li>';
		// Tags
		echo '<li><input id="pitch_form_tags_enabled" name="assignment_desk_general[pitch_form_tags_enabled]" type="checkbox"';
		if ($options['pitch_form_tags_enabled']) {
			echo ' checked="checked"';
		}
		echo ' />&nbsp;<label for="pitch_form_tags_enabled">Tags</label></li>';
		// Volunteer
		echo '<li><input id="pitch_form_volunteer_enabled" name="assignment_desk_general[pitch_form_volunteer_enabled]" type="checkbox"';
		if ($options['pitch_form_volunteer_enabled']) {
			echo ' checked="checked"';
		}
		echo ' />&nbsp;<label for="pitch_form_volunteer_enabled">Volunteer</label></li>';
		// Due date
		if ($assignment_desk->edit_flow_exists()) {
			echo '<li><input id="pitch_form_duedate_enabled" name="assignment_desk_general[pitch_form_duedate_enabled]" type="checkbox"';
			if ($options['pitch_form_duedate_enabled']) {
				echo ' checked="checked"';
			}
			echo ' />&nbsp;<label for="pitch_form_duedate_enabled">Due Date</label></li>';
		} else {
				echo '<li>Please enable Edit Flow to allow due date field.</li>';
		}
		// Location
		if ($assignment_desk->edit_flow_exists()) {
			echo '<li><input id="pitch_form_location_enabled" name="assignment_desk_general[pitch_form_location_enabled]" type="checkbox"';
			if ($options['pitch_form_location_enabled']) {
				echo ' checked="checked"';
			}
			echo ' />&nbsp;<label for="pitch_form_location_enabled">Location</label></li>';
			
		} else {
			echo '<li>Please enable Edit Flow to allow location field.</li>';
		}
		echo '</ul>';
	}
	
	function assignment_management_setting_section() {
		global $assignment_desk;
	}
	
	function assignment_email_notifications_enabled_option() {
		global $assignment_desk;
		$options = $assignment_desk->general_options;
		
		echo '<input id="assignment_email_notifications_enabled" name="assignment_desk_general[assignment_email_notifications_enabled]" type="checkbox"';
		if ($options['assignment_email_notifications_enabled']) {
			echo ' checked="checked"';
		}
		echo ' />';
	}
	
	function assignment_email_template_subject_option() {
    		global $assignment_desk;
    		$options = $assignment_desk->general_options;
    		?>
    		<input id="assignment_email_template_subject" name="assignment_desk_general[assignment_email_template_subject]" size="60" maxlength="60" 
    		        value="<?php echo $options['assignment_email_template_subject']; ?>"><br>
    <?php
    }
    	
	function assignment_email_template_option() {
		global $assignment_desk;
		$options = $assignment_desk->general_options;
		
		echo '<textarea id="assignment_email_template" name="assignment_desk_general[assignment_email_template]" rows="8" cols="60">';
		echo $options['assignment_email_template'];
		echo '</textarea><br />';
		echo '<span class="description">Use tokens like %title%, %location% and %dashboard_link%</span>';
	}
	
	function public_facing_views_setting_section() {
		global $assignment_desk;
		echo "Enable public access to pitches and stories in progress by dropping <code>&#60;!--$assignment_desk->all_posts_key--&#62;</code> in a page.";
	}
	
	function public_facing_elements_option() {
		global $assignment_desk;
		if ($assignment_desk->edit_flow_exists()) {
			global $edit_flow;
		}
		$options = $assignment_desk->general_options;
		echo '<ul>';
		// Title
		echo '<li><input type="checkbox" disabled="disabled" checked="checked" />&nbsp;<label for="public_facing_title">Title</label></li>';
		// Description
		if ($assignment_desk->edit_flow_exists()) {
			echo '<li><input id="public_facing_description_enabled" name="assignment_desk_general[public_facing_description_enabled]" type="checkbox"';
			if ($options['public_facing_description_enabled']) {
				echo ' checked="checked"';
			}
			echo ' />&nbsp;<label for="public_facing_description_enabled">Description</label></li>';
		} else {
				echo '<li>Please enable Edit Flow to allow description field.</li>';
		}
		// Due date
		if ($assignment_desk->edit_flow_exists()) {
			echo '<li><input id="public_facing_duedate_enabled" name="assignment_desk_general[public_facing_duedate_enabled]" type="checkbox"';
			if ($options['public_facing_duedate_enabled']) {
				echo ' checked="checked"';
			}
			echo ' />&nbsp;<label for="public_facing_duedate_enabled">Due Date</label></li>';
		} else {
				echo '<li>Please enable Edit Flow to allow due date field.</li>';
		}
		// Location
		if ($assignment_desk->edit_flow_exists()) {
			echo '<li><input id="public_facing_location_enabled" name="assignment_desk_general[public_facing_location_enabled]" type="checkbox"';
			if ($options['public_facing_location_enabled']) {
				echo ' checked="checked"';
			}
			echo ' />&nbsp;<label for="public_facing_location_enabled">Location</label></li>';
			
		} else {
			echo '<li>Please enable Edit Flow to allow location field.</li>';
		}
		// Categories
		echo '<li><input id="public_facing_categories_enabled" name="assignment_desk_general[public_facing_categories_enabled]" type="checkbox"';
		if ($options['public_facing_categories_enabled']) {
			echo ' checked="checked"';
		}
		echo ' />&nbsp;<label for="public_facing_categories_enabled">Categories</label></li>';
		// Tags
		echo '<li><input id="public_facing_tags_enabled" name="assignment_desk_general[public_facing_tags_enabled]" type="checkbox"';
		if ($options['public_facing_tags_enabled']) {
			echo ' checked="checked"';
		}
		echo ' />&nbsp;<label for="public_facing_tags_enabled">Tags</label></li>';
		echo '</ul>';
	}
	
	function public_facing_functionality_option() {
		global $assignment_desk;
		if ($assignment_desk->edit_flow_exists()) {
			global $edit_flow;
		}
		$options = $assignment_desk->general_options;
		echo '<ul>';
		// Volunteer
		echo '<li><input id="public_facing_volunteering_enabled" name="assignment_desk_general[public_facing_volunteering_enabled]" type="checkbox"';
		if ($options['public_facing_volunteering_enabled']) {
			echo ' checked="checked"';
		}
		echo ' />&nbsp;<label for="public_facing_volunteering_enabled">Volunteering</label></li>';
		// Voting
		echo '<li><input id="public_facing_voting_enabled" name="assignment_desk_general[public_facing_voting_enabled]" type="checkbox"';
		if ($options['public_facing_voting_enabled']) {
			echo ' checked="checked"';
		}
		echo ' />&nbsp;<label for="public_facing_voting_enabled">Voting</label></li>';
		// Commenting
		echo '<li><input id="public_facing_commenting_enabled" name="assignment_desk_general[public_facing_commenting_enabled]" type="checkbox"';
		if ($options['public_facing_commenting_enabled']) {
			echo ' checked="checked"';
		}
		echo ' />&nbsp;<label for="public_facing_commenting_enabled">Commenting</label></li>';
		echo '</ul>';
	}
	
	function google_maps_api_key_option() {
		global $assignment_desk;
		$options = $assignment_desk->general_options;
		echo '<input type="text" id="google_maps_api_key" name="assignment_desk_general[google_maps_api_key]" value="';
		echo $options['google_maps_api_key'];
		echo '"/>';
		
	}
	
	function miscellaneous_setting_section() {
	    
	}
	
	/**
	 * Validation for all of our form elements
	 */
	function assignment_desk_validate($input) {
		
		// @todo Should we validate all elements?
		
		$input['default_new_assignment_status'] = (int)$input['default_new_assignment_status'];
		$input['google_maps_api_key'] = wp_kses($input['google_maps_api_key'], $allowedtags);
		return $input;
	}
    
    function general_settings() {
		global $wpdb, $assignment_desk;

		$msg = null;
		if ( array_key_exists( 'updated', $_GET ) && $_GET['updated']=='true' ) { 
			$msg = __('Settings Saved', 'assignment-desk');
		}

    
?>                                   
	<div class="wrap">
		<div class="icon32" id="icon-options-general"><br/></div>
		
		<?php if($msg) : ?>
			<div class="updated fade" id="message">
				<p><strong><?php echo $msg ?></strong></p>
			</div>
		<?php endif; ?>
		
		<h2><?php _e('Assignment Desk Settings', 'assignment-desk') ?></h2>
		
			<form action="options.php" method="post">
				
				<?php settings_fields( $assignment_desk->options_group ); ?>
				<?php do_settings_sections( $assignment_desk->top_level_page ); ?>
				
				<p class="submit"><input name="submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" /></p>
				
			</form>
	</div>


<?php
      
      
    }
  
  }
  
  
}


?>