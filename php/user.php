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
      add_filter('manage_users_custom_column', array(&$this, 'handle_ad_user_total_words_column'), 10, 3);
      add_filter('manage_users_custom_column', array(&$this, 'handle_ad_user_average_words_column'), 10, 3);
      add_filter('manage_users_custom_column', array(&$this, 'handle_ad_user_type_column'), 10, 3);
      
    }
    
    function add_manage_user_columns($user_columns) {
      
      $custom_fields_to_add = array(
                                  _('_ad_user_type') => __('User Type'),
                                  _('_ad_user_total_words') => __('Total Words'),
                                  _('_ad_user_average_words') => __('Average Words'),
                              );
      
      foreach ($custom_fields_to_add as $field => $title) {
          $user_columns[$field] = $title;
      } 
      return $user_columns;
      
    }
    
    function handle_ad_user_type_column($default, $column_name, $user_id ) {
      global $assignment_desk;
      
      if ( $column_name == __( '_ad_user_type' ) ) {
        $user_type_term_name = 'None assigned';
        $user_type = (int)get_usermeta($user_id, $assignment_desk->option_prefix.'user_type', true);        
        $term = get_term($user_type, $assignment_desk->custom_taxonomies->user_type_label);
        if($term->name){
            $user_type_term_name = $term->name;
        }
        return $user_type_term_name; 
      }
      
    }
    
    function handle_ad_user_average_words_column($default, $column_name, $user_id){
      if ( $column_name == __( '_ad_user_average_words' ) ) {
        return $this->average_words($user_id); 
      }
    }
    
    function handle_ad_user_total_words_column($default, $column_name, $user_id){
      if ( $column_name == __( '_ad_user_total_words' ) ) {
        return $this->count_total_words($user_id);
      }      
    }
    
    /**
    * Returns the sum of the number of words in any published post where 
    * user_id is a coauthor and an accepted participant in the writer role.
    */
    function count_total_words($user_id){
        global $assignment_desk, $wpdb;
        
        $total_words = 0;
        $user = get_userdata($user_id);
        // Get post ID's and participant records the post is published
        $post_id_results = $wpdb->get_results("SELECT $wpdb->posts.ID as post_id
                                        FROM $wpdb->posts LEFT JOIN $wpdb->postmeta 
                                                                ON ($wpdb->posts.ID = $wpdb->postmeta.post_id)
                                        WHERE $wpdb->posts.post_status = 'publish'
                                        AND $wpdb->postmeta.meta_key = '_ad_participant_$user->ID'", ARRAY_N);
        $post_ids = array();
        if(!$post_id_results){
            return  0;
        }
        foreach($post_id_results as $p){
            $post_ids[] = $p[0];
        }
        $writer_role = get_term_by('name', _('Writer'), $assignment_desk->custom_taxonomies->user_role_label);
        // Of all the posts where this user is a participant, which have writers associated with them?
        $participant_records = $wpdb->get_results("SELECT * FROM $wpdb->postmeta
                                                    WHERE post_id IN (" . implode(', ', $post_ids) . ")
                                                    AND meta_key = '_ad_participant_role_$writer_role->term_id'");
        if(!$writer_role){
            return 0;
        }

        // Accumulate post ids where this user is in the Writer role and they've accepted the assignment.
        $posts_to_consider = array();
        foreach($participant_records as $record){
            $roles = maybe_unserialize($record->meta_value);
            if('accepted' == $roles[$user->user_login]){
                $posts_to_consider[]= $record->post_id;
            }
        }
        
        // Add up all of the word counts we stashed in the postmeta during save_post
        foreach($posts_to_consider as $post_id){
             $total_words += (int)get_post_meta($post_id, '_ad_word_count', true);
        }
        return $total_words;
    }
    
    /** 
    * Returns the average words per post for this user
    */
    function average_words($user_id){
        $num_posts = get_usernumposts($user_id);
        if($num_posts){
            return $this->count_total_words($user_id) / $num_posts;
        }
        return 0;
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