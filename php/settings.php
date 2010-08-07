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
		add_settings_field( 'default_new_pitch_status', 'Default pitch status', array(&$this, 'default_new_pitch_status_option'), $assignment_desk->top_level_page, 'story_pitches' );
		add_settings_field( 'default_workflow_status', 'Default workflow status', array(&$this, 'default_workflow_status_option'), $assignment_desk->top_level_page, 'story_pitches' );
		add_settings_field( 'pitch_form_elements', 'Pitch form elements', array(&$this, 'pitch_form_elements_option'), $assignment_desk->top_level_page, 'story_pitches' );
				
		
		add_settings_section( 'public_facing_views', 'Public-Facing Views', array(&$this, 'public_facing_views_setting_section'), $assignment_desk->top_level_page );
		
		add_settings_section( 'miscellaneous', 'Miscellaneous', array(&$this, 'miscellaneous_setting_section'), $assignment_desk->top_level_page );
		
	}
	
	function story_pitches_setting_section() {
		global $assignment_desk;
		echo "Add an Assignment Desk pitch form to any page or post by adding &#60;!--$assignment_desk->pitch_form_key--&#62; where you'd like the text.";
	}
	
	function default_new_pitch_status_option() {
		global $assignment_desk;
		$options = get_option($assignment_desk->get_plugin_option_fullname('general'));
		$pitch_statuses = $assignment_desk->custom_taxonomies->get_pitch_statuses();
		echo '<select id="default_new_pitch_status" name="assignment_desk_general[default_new_pitch_status]">';
		foreach ($pitch_statuses as $pitch_status) {
			echo "<option value='$pitch_status->term_id'";
			if ($options['default_new_pitch_status'] == $pitch_status->term_id) {
				echo ' selected="selected"';
			}
			echo ">$pitch_status->name</option>";
 		}
		echo '</select>';
	}
	
	/**
	 * Define post status for newly submitted pitches
	 * @requires Edit Flow
	 */
	function default_workflow_status_option() {
		global $assignment_desk;
		if (class_exists('edit_flow')) {
			global $edit_flow;
			$options = get_option($assignment_desk->get_plugin_option_fullname('general'));
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
		$edit_flow_exists = false;
		if (class_exists('edit_flow')) {
			global $edit_flow;
			$edit_flow_exists = true;
		}
	}
	
	function public_facing_views_setting_section() {
		echo "Enable public access to pitches and stories in progress by dropping &#60;!--assignment-desk-all-stories--&#62; in a page.";
	}
	
	/**
	 * Validation for all of our form elements
	 */
	function assignment_desk_validate($input) {
		
		// @todo Should we validate all elements?
		
		$input['default_new_pitch_status'] = (int)$input['default_new_pitch_status'];
		$input['google_api_key'] = wp_kses($input['google_api_key'], $allowedtags);
		$input['twitter_hash'] = wp_kses($input['twitter_hash'], $allowedtags);
		return $input;
	}
    
    function general_settings() {
		global $wpdb, $assignment_desk;

		$msg = null;
		if ( array_key_exists( 'updated', $_GET ) && $_GET['updated']=='true' ) { 
			$msg = __('Settings Saved', 'assignment-desk');
		}
      
       
    
          $this->options['display_approved_pitches']     = $_POST['display_approved_pitches'];
          $this->options['public_pitch_voting']          = $_POST['public_pitch_voting'];
          $this->options['public_pitch_comments']        = $_POST['public_pitch_comments'];

    
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
      
      <p>@todo Setup options:
      <ul>
        <li>Enable pitch statuses</li>
        <li>Enable user types (community member, NYU student, NYT Reporter, Editor, etc.)</li>
        <li>Enable user roles (photographer, writer, copy editor, etc.)</li>
      </p>
      
      <p>
          In order to show the posts with the status "pitch" you need to create at least one Wordpress page or post with &lt;-- assignment-desk-public --&gt; tag in it. The Assignment Desk will find this tag and display public pitch pages. See the settings below for more control of what the public can do.
      </p>




	<table class="form-table">
	
		
        <tr valign="top"> 
          <th scope="row"><?php _e('Display Approved pitches to the public:', $this->localizationDomain); ?></th> 
          <td><input type="checkbox" name="display_approved_pitches" val="1" checked="<?php echo ($this->options['display_approved_pitches'] == '1')? 'checked':'' ;?>"></td>
        </tr>
        
        <tr valign="top"> 
          <th scope="row"><?php _e('Enable public voting on pitches:', $this->localizationDomain); ?></th> 
          <td><input type="checkbox" name="public_pitch_voting" val="1" checked="<?php echo ($this->options['public_pitch_voting'] == '1')? 'checked':'' ;?>"></td>
        </tr>
        
        <tr valign="top"> 
          <th scope="row"><?php _e('Enable tip comments on public pitches:', $this->localizationDomain); ?></th> 
          <td><input type="checkbox" name="public_pitch_comments" val="1" checked="<?php echo ($this->options['public_pitch_comments'] == '1')? 'checked':'' ;?>"></td>
        </tr>
        
        <tr valign="top"> 
          <th scope="row"><?php _e('Google API Key:', $this->localizationDomain); ?></th> 
          <td><input name="google_api_key" type="text" size="100" 
                      value="<?php echo $this->options['google_api_key'] ;?>"></td>
        </tr>
        <tr valign="top"> 
          <th scope="row"><?php _e('Twitter Hash:', $this->localizationDomain); ?></th> 
                  <td><input name="assignment_desk_twitter_hash" type="text" size="25" 
                      value="<?php echo $this->options['assignment_desk_twitter_hash'] ;?>"></td>
              </tr>                    <tr>
        
              </tr>
          </table>


<?php
      
      
    }
  
  }
  
  
}


?>