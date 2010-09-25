<?php

if ( !class_exists( 'ad_settings' ) ){
  
/**
 * Class for managing all Assignment Desk settings views
 */
  class ad_settings
  {
  
    function __construct() {
      
    }

    /**
     * Register all of the settings and sections with the Wordpress Settings API
     */
	function init() {
		global $assignment_desk;
		
		register_setting( $assignment_desk->options_group, $assignment_desk->get_plugin_option_fullname('general'), array(&$this, 'assignment_desk_validate') );
		
		/* General */
		add_settings_section( 'general', 'General', array(&$this, 'general_setting_section'), $assignment_desk->top_level_page );
		add_settings_field( 'default_new_assignment_status', 'Default assignment status', array(&$this, 'default_new_assignment_status_option'), $assignment_desk->top_level_page, 'general' );
		add_settings_field( 'default_workflow_status', 'Default workflow status', array(&$this, 'default_workflow_status_option'), $assignment_desk->top_level_page, 'general' );
		
		/* Assignment Management */
		add_settings_section( 'assignment_management', 'Assignment Management', array(&$this, 'assignment_management_setting_section'), $assignment_desk->top_level_page );
		add_settings_field( 'assignment_email_notifications_enabled', 'Enable assignment email notifications', array(&$this, 'assignment_email_notifications_enabled_option'), $assignment_desk->top_level_page, 'assignment_management' );
		add_settings_field( 'assignment_email_template_subject', 'Subject template for notifications', array(&$this, 'assignment_email_template_subject_option'), $assignment_desk->top_level_page, 'assignment_management' );
		add_settings_field( 'assignment_email_template', 'Template for notifications', array(&$this, 'assignment_email_template_option'), $assignment_desk->top_level_page, 'assignment_management' );
		
		register_setting( $assignment_desk->pitch_form_options_group, $assignment_desk->get_plugin_option_fullname('pitch_form') );
		
		/* Pitch form */
		add_settings_section( 'story_pitches', 'Story Pitches', array(&$this, 'story_pitches_setting_section'), $assignment_desk->pitch_form_settings_page );
		add_settings_field( 'pitch_form_enabled', 'Enable pitch forms', array(&$this, 'pitch_form_enabled_option'), $assignment_desk->pitch_form_settings_page, 'story_pitches' );
		add_settings_field( 'pitch_form_elements', 'Pitch form elements', array(&$this, 'pitch_form_elements_option'), $assignment_desk->pitch_form_settings_page, 'story_pitches' );
		add_settings_field( 'pitch_form_success_message', 'Success message', array(&$this, 'pitch_form_success_message_option'), $assignment_desk->pitch_form_settings_page, 'story_pitches' );			
		
		register_setting( $assignment_desk->public_facing_options_group, $assignment_desk->get_plugin_option_fullname('public_facing') );
		
		/* Public-facing */
		add_settings_section( 'public_facing_views', 'Public-Facing Views', array(&$this, 'public_facing_views_setting_section'), $assignment_desk->public_facing_settings_page );
		add_settings_field( 'public_facing_assignment_statuses[]', 'Public-facing assignment statuses', array(&$this, 'public_facing_assignment_statuses'), $assignment_desk->public_facing_settings_page, 'public_facing_views' );			
		add_settings_field( 'public_facing_filtering', 'Public-facing filtering', array(&$this, 'public_facing_filtering_option'), $assignment_desk->public_facing_settings_page, 'public_facing_views' );
		add_settings_field( 'public_facing_elements', 'Public-facing elements', array(&$this, 'public_facing_elements_option'), $assignment_desk->public_facing_settings_page, 'public_facing_views' );
		add_settings_field( 'public_facing_functionality', 'Public-facing functionality', array(&$this, 'public_facing_functionality_option'), $assignment_desk->public_facing_settings_page, 'public_facing_views' );	
		add_settings_field( 'public_facing_no_pitches_message', 'Message to show if no pitches', array(&$this, 'public_facing_no_pitches_message_option'), $assignment_desk->public_facing_settings_page, 'public_facing_views' );	
			
	}
	
	/**
	 * Define all of the default settings.
	 */
	function setup_defaults() {
        global $assignment_desk, $wpdb;
        $options = $assignment_desk->general_options;
        
        if ( $assignment_desk->edit_flow_exists() ) {
            global $edit_flow;
            $default_workflow_status = get_term_by('slug', $edit_flow->options['custom_status_default_status'],
                                                    $edit_flow->custom_status->status_taxonomy);
            $options['default_workflow_status'] = $default_workflow_status->term_id;
        }
        // @todo - Why does get_term_by not work during activation?
        // $new_status = get_term_by('slug', 'new', $assignment_desk->custom_taxonomies->assignment_status_label);
        $new_status = $wpdb->get_results("SELECT t.*, tt.* 
                                          FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id 
                                          WHERE tt.taxonomy = '{$assignment_desk->custom_taxonomies->assignment_status_label}' 
                                          AND t.slug = 'new' LIMIT 1");
        $new_status = $new_status[0];
        $options['default_new_assignment_status']          = $new_status->term_id;
        $options['assignment_email_notifications_enabled'] = true;
        $options['assignment_email_template_subject']      = _("[%blogname%] You've been assigned to %title%");
        $options['assignment_email_template'] =
_(
"Hello %display_name%,
 
You've been assigned to the story %title%.
Please login to %dashboard_link% to accept or decline.

Thanks
Blog Editor");

        update_option($assignment_desk->get_plugin_option_fullname('general'), $options);
         
        // Public facing defaults
        $public_facing_options = $assignment_desk->public_facing_options;
        $approved_status = $wpdb->get_results("SELECT t.*, tt.* 
                                           FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id 
                                           WHERE tt.taxonomy = '{$assignment_desk->custom_taxonomies->assignment_status_label}' 
                                           AND t.slug = 'approved' LIMIT 1");
        $approved_status = $approved_status[0];
        $public_facing_options['public_facing_assignment_statuses'] = array($approved_status->term_id);

        $public_facing_options['public_facing_assignment_status_enabled'] = true;
        $public_facing_options['public_facing_description_enabled'] = true;
        $public_facing_options['public_facing_duedate_enabled'] = true;
        $public_facing_options['public_facing_location_enabled'] = true;
        $public_facing_options['public_facing_categories_enabled'] = true;
        $public_facing_options['public_facing_tags_enabled'] = true;
        
        $public_facing_options['public_facing_filtering_post_status_enabled'] = true;
        $public_facing_options['public_facing_filtering_participant_type_enabled'] = true;
        $public_facing_options['public_facing_filtering_sort_by_enabled'] = true;

        $public_facing_options['public_facing_volunteering_enabled'] = true;
        $public_facing_options['public_facing_voting_enabled'] = true;
        $public_facing_options['public_facing_commenting_enabled'] = true;
        $public_facing_options['public_facing_no_pitches_message'] = _('No stories right now.');
        update_option($assignment_desk->get_plugin_option_fullname('public_facing'), $public_facing_options);
         
        // Pitch form defaults
        $pitch_form_options = $assignment_desk->pitch_form_options;
        $pitch_form_options['pitch_form_enabled']             = true;
        $pitch_form_options['pitch_form_description_enabled'] = true;
        $pitch_form_options['pitch_form_categories_enabled']  = true;
        $pitch_form_options['pitch_form_tags_enabled']        = true;
        $pitch_form_options['pitch_form_duedate_enabled']     = true;
        $pitch_form_options['pitch_form_location_enabled']    = true;
        $pitch_form_options['pitch_form_volunteer_enabled']   = true;

        update_option($assignment_desk->get_plugin_option_fullname('pitch_form'), $pitch_form_options);
         
    }
	
	function default_new_assignment_status_option() {
		global $assignment_desk;
		$options = $assignment_desk->general_options;
		$assignment_statuses = $assignment_desk->custom_taxonomies->get_assignment_statuses();
		if ( count($assignment_statuses) ) {
			echo '<select id="default_new_assignment_status" name="' . $assignment_desk->get_plugin_option_fullname('general') . '[default_new_assignment_status]">';
			foreach ( $assignment_statuses as $assignment_status ) {
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
			echo '<select id="default_workflow_status" name="' . $assignment_desk->get_plugin_option_fullname('general') . '[default_workflow_status]">';
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
	
	
    function general_setting_section() {
		global $assignment_desk;
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
		echo '<input id="assignment_email_template_subject"'
		 	. 'name="assignment_desk_general[assignment_email_template_subject]"'
			. 'size="60" maxlength="60" value="' . $options['assignment_email_template_subject'] . '">';
    }
    	
	function assignment_email_template_option() {
		global $assignment_desk;
		$options = $assignment_desk->general_options;
		
		echo '<textarea id="assignment_email_template" name="assignment_desk_general[assignment_email_template]" rows="8" cols="60">';
		echo $options['assignment_email_template'];
		echo '</textarea>';
		echo '<p class="description">' . 
		    _('Template supports the following tokens') . 
		    ': %blogname%, %title%, %excerpt%, %description%, %duedate%, %role%, %display_name%, %location%, %post_link%, and %dashboard_link%.</p>';
	}
		
	function story_pitches_setting_section() {
		global $assignment_desk;
		echo "Add an Assignment Desk pitch form to any page or post by adding <code>&#60;!--$assignment_desk->pitch_form_key--&#62;</code> where you'd it to appear.";
	}
	
	function pitch_form_enabled_option() {
		global $assignment_desk;
		$options = $assignment_desk->pitch_form_options;
		echo '<input id="pitch_form_enabled" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_enabled]" type="checkbox"';
		if ($options['pitch_form_enabled']) {
			echo ' checked="checked"';
		}
		echo ' />';
	}
	
	/**
	 * Enable/disable data elements on pitch form
	 */
	function pitch_form_elements_option() {
		global $assignment_desk;
		if ($assignment_desk->edit_flow_exists()) {
			global $edit_flow;
		}
		$options = $assignment_desk->pitch_form_options;
		echo '<ul class="ad_elements">';
		// Title
		echo '<li><span class="field"><input type="checkbox" disabled="disabled" checked="checked" />&nbsp;<label for="pitch_form_title">Title</label></span>';
		echo '<span class="copy"><label for="pitch_form_title_label">Label</label>';
		echo '<input id="pitch_form_title_label" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_title_label]" type="text" value="'
			. $options['pitch_form_title_label'] . '" size="15" />';
		echo '<label for="pitch_form_title_description">Description</label>';
		echo '<input id="pitch_form_title_description" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_title_description]" type="text" value="'
			. $options['pitch_form_title_description'] . '" size="35" />';	
		echo '</span></li>';
		// Description
		if ($assignment_desk->edit_flow_exists()) {
			echo '<li><span class="field"><input id="pitch_form_description_enabled" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_description_enabled]" type="checkbox"';
			if ($options['pitch_form_description_enabled']) {
				echo ' checked="checked"';
			}
			echo ' />&nbsp;<label for="pitch_form_description_enabled">Description</label></span>';
			echo '<span class="copy';
			if ( !$options['pitch_form_description_enabled'] ) {
				echo ' hidden';
			}
			echo '"><label for="pitch_form_description_label">Label</label>';
			echo '<input id="pitch_form_description_label" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_description_label]" type="text" value="'
				. $options['pitch_form_description_label'] . '" size="15" />';
			echo '<label for="pitch_form_description_description">Description</label>';
			echo '<input id="pitch_form_description_description" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_description_description]" type="text" value="'
				. $options['pitch_form_description_description'] . '" size="35" />';	
			echo '</span></li>';
		} else {
				echo '<li>Please enable Edit Flow to allow description field.</li>';
		}
		// Categories
		echo '<li><span class="field"><input id="pitch_form_categories_enabled" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_categories_enabled]" type="checkbox"';
		if ($options['pitch_form_categories_enabled']) {
			echo ' checked="checked"';
		}
		echo ' />&nbsp;<label for="pitch_form_categories_enabled">Categories</label></span>';
		echo '<span class="copy';
		if ( !$options['pitch_form_categories_enabled'] ) {
			echo ' hidden';
		}
		echo '"><label for="pitch_form_categories_label">Label</label>';
		echo '<input id="pitch_form_categories_label" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_categories_label]" type="text" value="'
			. $options['pitch_form_categories_label'] . '" size="15" />';
		echo '<label for="pitch_form_categories_description">Description</label>';
		echo '<input id="pitch_form_categories_description" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_categories_description]" type="text" value="'
			. $options['pitch_form_categories_description'] . '" size="35" />';	
		echo '</span></li>';
		// Tags
		echo '<li><span class="field"><input id="pitch_form_tags_enabled" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_tags_enabled]" type="checkbox"';
		if ( $options['pitch_form_tags_enabled'] ) {
			echo ' checked="checked"';
		}
		echo ' />&nbsp;<label for="pitch_form_tags_enabled">Tags</label></span>';
		echo '<span class="copy';
		if ( !$options['pitch_form_tags_enabled'] ) {
			echo ' hidden';
		}
		echo '"><label for="pitch_form_tags_label">Label</label>';
		echo '<input id="pitch_form_tags_label" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_tags_label]" type="text" value="'
			. $options['pitch_form_tags_label'] . '" size="15" />';
		echo '<label for="pitch_form_tags_description">Description</label>';
		echo '<input id="pitch_form_tags_description" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_tags_description]" type="text" value="'
			. $options['pitch_form_tags_description'] . '" size="35" />';	
		echo '</span></li>';
		// Due date
		if ($assignment_desk->edit_flow_exists()) {
			echo '<li><span class="field"><input id="pitch_form_duedate_enabled" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_duedate_enabled]" type="checkbox"';
			if ($options['pitch_form_duedate_enabled']) {
				echo ' checked="checked"';
			}
			echo ' />&nbsp;<label for="pitch_form_duedate_enabled">Due Date</label></span>';
			echo '<span class="copy';
			if ( !$options['pitch_form_duedate_enabled'] ) {
				echo ' hidden';
			}
			echo '"><label for="pitch_form_duedate_label">Label</label>';
			echo '<input id="pitch_form_duedate_label" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_duedate_label]" type="text" value="'
				. $options['pitch_form_duedate_label'] . '" size="15" />';
			echo '<label for="pitch_form_duedate_description">Description</label>';
			echo '<input id="pitch_form_duedate_description" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_duedate_description]" type="text" value="'
				. $options['pitch_form_duedate_description'] . '" size="35" />';	
			echo '</span></li>';
		} else {
				echo '<li>Please enable Edit Flow to allow due date field.</li>';
		}
		// Location
		if ($assignment_desk->edit_flow_exists()) {
			echo '<li><span class="field"><input id="pitch_form_location_enabled" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_location_enabled]" type="checkbox"';
			if ($options['pitch_form_location_enabled']) {
				echo ' checked="checked"';
			}
			echo ' />&nbsp;<label for="pitch_form_location_enabled">Location</label></span>';
			echo '<span class="copy';
			if ( !$options['pitch_form_location_enabled'] ) {
				echo ' hidden';
			}
			echo '"><label for="pitch_form_location_label">Label</label>';
			echo '<input id="pitch_form_location_label" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_location_label]" type="text" value="'
				. $options['pitch_form_location_label'] . '" size="15" />';
			echo '<label for="pitch_form_location_description">Description</label>';
			echo '<input id="pitch_form_location_description" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_location_description]" type="text" value="'
				. $options['pitch_form_location_description'] . '" size="35" />';	
			echo '</span></li>';
			
		} else {
			echo '<li>Please enable Edit Flow to allow location field.</li>';
		}
		// Volunteer
		echo '<li><span class="field"><input id="pitch_form_volunteer_enabled" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_volunteer_enabled]" type="checkbox"';
		if ( $options['pitch_form_volunteer_enabled'] ) {
			echo ' checked="checked"';
		}
		echo ' />&nbsp;<label for="pitch_form_volunteer_enabled">Volunteer</label></span>';
		echo '<span class="copy';
		if ( !$options['pitch_form_volunteer_enabled'] ) {
			echo ' hidden';
		}
		echo '"><label for="pitch_form_volunteer_label">Label</label>';
		echo '<input id="pitch_form_volunteer_label" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_volunteer_label]" type="text" value="'
			. $options['pitch_form_volunteer_label'] . '" size="15" />';
		echo '<label for="pitch_form_volunteer_description">Description</label>';
		echo '<input id="pitch_form_volunteer_description" name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_volunteer_description]" type="text" value="'
			. $options['pitch_form_volunteer_description'] . '" size="35" />';
		echo '</span></li>';
		echo '</ul>';
	}
	
	function pitch_form_success_message_option() {
		global $assignment_desk;
		$options = $assignment_desk->pitch_form_options;
		echo '<textarea id="pitch_form_success_message"'
		 	. 'name="' . $assignment_desk->get_plugin_option_fullname('pitch_form') . '[pitch_form_success_message]"'
			. ' cols="45" rows="4">' . $options['pitch_form_success_message'] . '</textarea>';
		echo '<p class="description">'
			. _('Optional: Enter a custom success message')
			. '</p>';
		echo '<p class="description">' . 
		    _('Message supports the following tokens') . 
		    ': %post_link%, %title%, %description%, %duedate%, and %location%.</p>';
	}
	
	function public_facing_views_setting_section() {
		global $assignment_desk;
		echo "Enable public access to pitches and stories in progress by dropping <code>&#60;!--$assignment_desk->all_posts_key--&#62;</code> in a page.";
	}
	
	/**
	 * Admin can choose which assignment statuses will be visible on the public-facing views. 
	 */
	function public_facing_assignment_statuses() {
	    global $assignment_desk;
	    $options = $assignment_desk->public_facing_options;
	    $public_statuses = $options['public_facing_assignment_statuses'];
	    if ( !is_array($public_statuses) ) {
	        $public_statuses = array((int)$public_statuses);
	    }
	    echo "<label>" . _("Posts of the following assignment statuses will be displayed on the public facing views (if enabled)") . ":</label>";
	    echo "<ul>";
	    foreach ($assignment_desk->custom_taxonomies->get_assignment_statuses() as $assignment_status){
	        echo "<li>";
	        echo "<input type='checkbox' id='ad-status-{$assignment_status->term_id}' value='{$assignment_status->term_id}' " .
	                     'name="' . $assignment_desk->get_plugin_option_fullname('public_facing') . '[public_facing_assignment_statuses][]"';
	        if ( in_array($assignment_status->term_id, $public_statuses) ) {
	            echo ' checked="checked" ';
	        } 
	        echo '>';
	        echo " <label for='ad-status-{$assignment_status->term_id}'>$assignment_status->name</label></li>";
	    }
	    echo "</ul>";
	}
	
	function public_facing_filtering_option() {
		global $assignment_desk;
		$options = $assignment_desk->public_facing_options;
		// Filter by post status
		echo '<input id="public_facing_filtering_post_status_enabled" type="checkbox" '
			. 'name="' . $assignment_desk->get_plugin_option_fullname('public_facing')
			. '[public_facing_filtering_post_status_enabled]"';
		if ( $options['public_facing_filtering_post_status_enabled'] ) {
			echo ' checked="checked"';
		}
	 	echo ' />&nbsp;<label for="public_facing_filtering_post_status_enabled">Post status</label>&nbsp;';
		// Filter by participant type
		echo '<input id="public_facing_filtering_participant_type_enabled" type="checkbox" '
			. 'name="' . $assignment_desk->get_plugin_option_fullname('public_facing')
			. '[public_facing_filtering_participant_type_enabled]"';
		if ( $options['public_facing_filtering_participant_type_enabled'] ) {
			echo ' checked="checked"';
		}
	 	echo ' />&nbsp;<label for="public_facing_filtering_participant_type_enabled">Contributor type</label>&nbsp;';
		// Sort by
		echo '<input id="public_facing_filtering_sort_by_enabled" type="checkbox" '
			. 'name="' . $assignment_desk->get_plugin_option_fullname('public_facing')
			. '[public_facing_filtering_sort_by_enabled]"';
		if ( $options['public_facing_filtering_sort_by_enabled'] ) {
			echo ' checked="checked"';
		}
	 	echo ' />&nbsp;<label for="public_facing_filtering_sort_by_enabled">Sort by</label>';
		echo '<p class="description">Indicate the different ways the user can filter posts.';
	}

	function public_facing_elements_option() {
		global $assignment_desk;
		if ($assignment_desk->edit_flow_exists()) {
			global $edit_flow;
		}
		$options = $assignment_desk->public_facing_options;
		echo '<ul>';
		// Title
		echo '<li><input type="checkbox" disabled="disabled" checked="checked" />&nbsp;<label for="public_facing_title">Title</label></li>';
		// Content
		echo '<li><input id="public_facing_content_enabled" name="' . $assignment_desk->get_plugin_option_fullname('public_facing') . '[public_facing_content_enabled]" type="checkbox"';
		if ($options['public_facing_content_enabled']) {
			echo ' checked="checked"';
		}
		echo ' />&nbsp;<label for="public_facing_content_enabled">Content</label></li>';
		// Assignment Status
		echo '<li><input id="public_facing_assignment_status_enabled" name="' . $assignment_desk->get_plugin_option_fullname('public_facing') . '[public_facing_assignment_status_enabled]" type="checkbox"';
		if ($options['public_facing_assignment_status_enabled']) {
			echo ' checked="checked"';
		}
		echo ' />&nbsp;<label for="public_facing_assignment_status_enabled">Assignment Status</label></li>';		
		// Description
		if ($assignment_desk->edit_flow_exists()) {
			echo '<li><input id="public_facing_description_enabled" name="' . $assignment_desk->get_plugin_option_fullname('public_facing') . '[public_facing_description_enabled]" type="checkbox"';
			if ($options['public_facing_description_enabled']) {
				echo ' checked="checked"';
			}
			echo ' />&nbsp;<label for="public_facing_description_enabled">Description</label></li>';
		} else {
				echo '<li>Please enable Edit Flow to allow description field.</li>';
		}
		// Due date
		if ($assignment_desk->edit_flow_exists()) {
			echo '<li><input id="public_facing_duedate_enabled" name="' . $assignment_desk->get_plugin_option_fullname('public_facing') . '[public_facing_duedate_enabled]" type="checkbox"';
			if ($options['public_facing_duedate_enabled']) {
				echo ' checked="checked"';
			}
			echo ' />&nbsp;<label for="public_facing_duedate_enabled">Due Date</label></li>';
		} else {
				echo '<li>Please enable Edit Flow to allow due date field.</li>';
		}
		// Location
		if ($assignment_desk->edit_flow_exists()) {
			echo '<li><input id="public_facing_location_enabled" name="' . $assignment_desk->get_plugin_option_fullname('public_facing') . '[public_facing_location_enabled]" type="checkbox"';
			if ($options['public_facing_location_enabled']) {
				echo ' checked="checked"';
			}
			echo ' />&nbsp;<label for="public_facing_location_enabled">Location</label></li>';
			
		} else {
			echo '<li>Please enable Edit Flow to allow location field.</li>';
		}
		// Categories
		echo '<li><input id="public_facing_categories_enabled" name="' . $assignment_desk->get_plugin_option_fullname('public_facing') . '[public_facing_categories_enabled]" type="checkbox"';
		if ($options['public_facing_categories_enabled']) {
			echo ' checked="checked"';
		}
		echo ' />&nbsp;<label for="public_facing_categories_enabled">Categories</label></li>';
		// Tags
		echo '<li><input id="public_facing_tags_enabled" name="' . $assignment_desk->get_plugin_option_fullname('public_facing') . '[public_facing_tags_enabled]" type="checkbox"';
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
		$options = $assignment_desk->public_facing_options;
		echo '<ul class="ad_elements">';
		// Volunteer
		echo '<li><input id="public_facing_volunteering_enabled" name="' . $assignment_desk->get_plugin_option_fullname('public_facing') . '[public_facing_volunteering_enabled]" type="checkbox"';
		if ($options['public_facing_volunteering_enabled']) {
			echo ' checked="checked"';
		}
		echo ' />&nbsp;<label for="public_facing_volunteering_enabled">Volunteering</label></li>';
		// Voting
		echo '<li><span class="field"><input id="public_facing_voting_enabled" name="' . $assignment_desk->get_plugin_option_fullname('public_facing') . '[public_facing_voting_enabled]" type="checkbox"';
		if ( $options['public_facing_voting_enabled'])  {
			echo ' checked="checked"';
		}
		echo ' />&nbsp;<label for="public_facing_voting_enabled">Voting</label></span>';
		echo '<span class="copy';
		if ( !$options['public_facing_voting_enabled'] ) {
			echo ' hidden';
		}
		echo '"><label for="public_facing_voting_button">Button text</label>';
		echo '<input id="public_facing_voting_button" name="' . $assignment_desk->get_plugin_option_fullname('public_facing') . '[public_facing_voting_button]" type="text" value="'
			. $options['public_facing_voting_button'] . '" size="15" />';
		echo '</span></li>';
		// Commenting
		echo '<li><input id="public_facing_commenting_enabled" name="' . $assignment_desk->get_plugin_option_fullname('public_facing') . '[public_facing_commenting_enabled]" type="checkbox"';
		if ($options['public_facing_commenting_enabled']) {
			echo ' checked="checked"';
		}
		echo ' />&nbsp;<label for="public_facing_commenting_enabled">Commenting</label></li>';
		echo '</ul>';
	}
	
	function public_facing_no_pitches_message_option() {
		global $assignment_desk;
		$options = $assignment_desk->public_facing_options;
		echo '<input id="public_facing_no_pitches_message"'
		 	. 'name="' . $assignment_desk->get_plugin_option_fullname('public_facing') . '[public_facing_no_pitches_message]"'
			. ' size="60" maxlength="120" value="' . $options['public_facing_no_pitches_message'] . '">';
	}
	
	/**
	 * Validation for all of our form elements
	 */
	function assignment_desk_validate($input) {

		// @todo Should we validate all settings elements?

		$input['default_new_assignment_status'] = (int)$input['default_new_assignment_status'];
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
	
	function pitch_form_settings() {
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
		
		<h2><?php _e('Pitch Form Settings', 'assignment-desk') ?></h2>
		
			<form action="options.php" method="post">
				
				<?php settings_fields( $assignment_desk->pitch_form_options_group ); ?>
				<?php do_settings_sections( $assignment_desk->pitch_form_settings_page ); ?>
				
				<p class="submit"><input name="submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" /></p>
				
			</form>
	</div>

<?php

    }

	function public_facing_settings() {
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
		
		<h2><?php _e('Public-Facing Settings', 'assignment-desk') ?></h2>
		
			<form action="options.php" method="post">
				
				<?php settings_fields( $assignment_desk->public_facing_options_group ); ?>
				<?php do_settings_sections( $assignment_desk->public_facing_settings_page ); ?>
				
				<p class="submit"><input name="submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" /></p>
				
			</form>
	</div>

<?php
      
      
    }

  }

}


?>