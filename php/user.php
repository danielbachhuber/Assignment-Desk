<?php
if ( !class_exists( 'ad_user' ) ) {
  
/**
 * Class for managing all Assignment Desk user-related views
 */
  class ad_user
  {
  
    function __construct() {
      
    }
    
    function init_user() {
      
      add_action('edit_user_profile', array(&$this, 'profile_options'));
      add_action('show_user_profile', array(&$this, 'profile_options'));
      
      add_action('profile_update', array(&$this, 'save_profile_options'));
    
      add_filter('manage_users_columns', array(&$this, 'add_manage_user_columns'));
      add_action('manage_users_custom_column', array(&$this, 'handle_ad_user_type_column'), 10, 3);
    
    }
    
    function add_manage_user_columns($user_columns) {
      
      $custom_fields_to_add = array(
                                  _('_ad_user_type') => __('User Type'),
                              );
      
      foreach ($custom_fields_to_add as $field => $title) {
          $user_columns["$field"] = $title;
      } 
      return $user_columns;
      
    }
    
    function handle_ad_user_type_column( $empty, $column_name, $user_id ) {
      global $assignment_desk;
      
      if ( $column_name == __( '_ad_user_type' ) ) {
        
        $user_type = (int)get_usermeta($user_id, $assignment_desk->option_prefix.'user_type', true);
        
        $user_type_taxonomy = get_terms($assignment_desk->custom_taxonomies->user_type_label, array('get'=>'all'));
        
        foreach ( $user_type_taxonomy as $user_type_term ) {
          if ( $user_type == $user_type_term->term_id ) {
            $user_type_term_name = $user_type_term->name;
            break;
          } else {
            $user_type_term_name = 'None assigned';
          }
        }
          
        return $user_type_term_name;
          
      }
      
    }
    
    function profile_options() {
      global $profileuser, $assignment_desk;
      
      $user_id = (int)$profileuser->ID;
      
      $user_type = (int)get_usermeta($user_id, $assignment_desk->option_prefix.'user_type', true);
      
      // Need to have 'get'=>'all' so that it will retrieve a custom taxonomy
      $user_type_taxonomy = get_terms($assignment_desk->custom_taxonomies->user_type_label, array('get'=>'all'));
      
      ?>
      <a name="assignment_desk"></a>
      <h3>Assignment Desk Settings</h3>
      
      <table class="form-table">
    		<tr>
    			<th>User Type</th>
    			<td><select id="assignment_desk-user_type" name="assignment_desk-user_type">
    			    <option value="0">- None assigned -</option>
    			  <?php foreach ($user_type_taxonomy as $user_type_term) : ?>
    			    <option value="<?php echo $user_type_term->term_id; ?>"<?php if ($user_type_term->term_id == $user_type) { echo ' selected="selected"'; } ?>><?php echo $user_type_term->name; ?></option>
    			  <?php endforeach; ?>
    			  </select>
    				<p class="setting-description">Indicate whether the user is a
    				<?php foreach ($user_type_taxonomy as $key => $user_type_term) : ?>
    				  <?php 
    				        // @todo Need an "or" if there are only two terms
    				        if ($key >= 2) { break; }
    				        echo $user_type_term->name . ', ';
                    if ($key == 1) { echo 'etc.'; } 
              ?>
    				  
    				<?php endforeach; ?>
    				</p>
    			</td>
    		</tr>
    	</table>
      
      <?php
      
    }
    
    function save_profile_options($user_id) {
      global $assignment_desk;
      
      $user_type = (int)$_POST['assignment_desk-user_type'];
      update_usermeta($user_id, $assignment_desk->option_prefix.'user_type', $user_type);
      
    }
    
  }
  
}



?>