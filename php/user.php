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
    
    }
    
    function add_edit_user_column() {
      
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