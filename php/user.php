<?php
if ( !class_exists( 'ad_user' ) ) {
  
/**
 * Class for managing all Assignment Desk user-related views
 */
  class ad_user
  {
  
    function __construct() {
      
    }
    
    function init() {
      global $assignment_desk;
      
      add_action('edit_user_profile', array(&$this, 'profile_options'));
      add_action('show_user_profile', array(&$this, 'profile_options'));
      add_action('show_user_profile', array(&$this, 'profile_statistics'));
      
      add_action('profile_update', array(&$this, 'save_profile_options'));
    
      add_filter('manage_users_columns', array(&$this, 'manage_user_columns'));
      add_filter('manage_users_custom_column', array(&$this, 'handle_ad_user_total_words_column'), 10, 3);
      add_filter('manage_users_custom_column', array(&$this, 'handle_ad_user_average_words_column'), 10, 3);
      add_filter('manage_users_custom_column', array(&$this, 'handle_ad_user_pitches_count_column'), 10, 3);
      
      if ( isset($assignment_desk->public_facing_options['public_facing_volunteering_enabled'])  && $assignment_desk->public_facing_options['public_facing_volunteering_enabled'] == 'on' ) {
        add_filter('manage_users_custom_column', array(&$this, 'handle_ad_user_volunteer_count_column'), 10, 3);
      }
      add_filter('manage_users_custom_column', array(&$this, 'handle_ad_user_type_column'), 10, 3);
    }
    
    /**
     * Add custom columns to the manage_users view.
     * @return array The columns
     */
    function manage_user_columns($user_columns) {
      global $assignment_desk;
      $custom_fields_to_add = array(
                                  _('_ad_user_type') => __('User Type'),
                                  _('_ad_user_total_words') => __('Total Words'),
                                  _('_ad_user_average_words') => __('Average Words'),
                                  _('_ad_user_pitch_count') => __('Pitches'),
                              );
      if ( $assignment_desk->public_facing_options['public_facing_volunteering_enabled'] == 'on' ) {
        $custom_fields_to_add[_('_ad_user_volunteer_count')] = __('Volunteered');
      }
      
      foreach ($custom_fields_to_add as $field => $title) {
          $user_columns[$field] = $title;
      } 
      return $user_columns;
    }
    
    /**
     * Filter for displaying the user type custom column
     * @return string The content of the cell
     */
    function handle_ad_user_type_column( $default, $column_name, $user_id ) {
      global $assignment_desk;
      
      if ( $column_name == __( '_ad_user_type' ) ) {
        $user_type_term_name = __('None assigned');
        $user_type = (int)get_usermeta($user_id, $assignment_desk->option_prefix.'user_type', true);        
        $term = get_term($user_type, $assignment_desk->custom_taxonomies->user_type_label);
        if($term->name){
            $user_type_term_name = $term->name;
        }
        return $user_type_term_name; 
      }
      return $default;
    }
    
    /**
     * Filter for displaying the average words custom column
     * @param string $default The content of the cell
     * @param string $column_name The name of the column
     * @param int $user_id The ID of the user
     * @return string the content of the cell
     */
    function handle_ad_user_average_words_column( $default, $column_name, $user_id ) {
      if ( $column_name == __( '_ad_user_average_words' ) ) {
        return $this->average_words($user_id); 
      }
      return $default;
    }
    
    /** 
    * Returns the average words per post for this user
    * @return int The average words per post for the user
    */
    function average_words( $user_id ) {
        $num_posts = get_usernumposts($user_id);
        if($num_posts){
            return $this->total_words($user_id) / $num_posts;
        }
        return 0;
    }
    
    /**
     * Filter for displaying the total words custom column
     * @param string $default The content of the cell
     * @param string $column_name The name of the column
     * @param int $user_id The ID of the user
     * @return string The content of the cell
     */
    function handle_ad_user_total_words_column( $default, $column_name, $user_id ) {
      if ( $column_name == __( '_ad_user_total_words' ) ) {
        return $this->total_words($user_id);
      }
      return $default;
    }
    
    /**
    * Returns the sum of the number of words in any published post where 
    * user_id is a coauthor and an accepted participant in the writer role.
    * @param int $user_id The ID of the user
    * @return int The total words for all of the user's posts.
    */
    function total_words($user_id){
        global $assignment_desk, $wpdb;
        
        $total_words = 0;
        $user = get_userdata($user_id);
        // Get post ID's where this user is a participant
        $participant_posts = $wpdb->get_results("SELECT $wpdb->posts.ID
                                                 FROM $wpdb->posts 
                                                     LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id)
                                                 WHERE $wpdb->posts.post_status = 'publish'
                                                     AND $wpdb->posts.post_type = 'post' 
                                                     AND $wpdb->postmeta.meta_key = '_ad_participant_$user->ID'", ARRAY_N);
        $post_ids = array();
        if ( $participant_posts ) {
            foreach($participant_posts as $p){
                $post_ids[] = $p[0];
            }
        }
        
        // @todo - Make this configurable.
        $writer_role = get_term_by('name', _('Writer'), $assignment_desk->custom_taxonomies->user_role_label);
        // Of all the posts where this user is a participant, which have writers associated with them?
        $participant_records = array();
        if ( $post_ids ) {
            $participant_records = $wpdb->get_results("SELECT * FROM $wpdb->postmeta
                                                        WHERE post_id IN (" . implode(', ', $post_ids) . ")
                                                            AND meta_key = '_ad_participant_role_{$writer_role->term_id}'");
        }

        if(!$writer_role){
            return 0;
        }

        // Accumulate post ids where this user is in the Writer role and they've accepted the assignment.
        $posts_to_consider = array();
        foreach ( $participant_records as $record ) {
            $roles = maybe_unserialize($record->meta_value);
            if ( 'accepted' == $roles[$user->user_login] ) {
                $posts_to_consider[]= $record->post_id;
            }
        }
        
        $author_posts = $wpdb->get_results("SELECT ID FROM $wpdb->posts 
                                              WHERE post_status = 'publish' 
                                                AND post_type = 'post'
                                                AND post_author = $user->ID");
        
        foreach( $author_posts as $post ) {
            $posts_to_consider[]= $post->ID;
        }

        error_log('posts ' . $posts_to_consider);
        
        // Add up all of the word counts we stashed in the postmeta during save_post
        foreach( $posts_to_consider as $post_id ) {
            $words = (int)get_post_meta($post_id, '_ad_word_count', true);
            if ( !$words ) {
                $words = str_word_count($wpdb->get_var("SELECT post_content FROM $wpdb->posts WHERE ID=$post_id"));
            }
            $total_words += $words;
        }
        return $total_words;
    }
    
    /**
     * Filter for displaying the volunteer count custom column
     * @param string $default The content of the cell
     * @param string $column_name The name of the column
     * @param int $user_id The ID of the user
     * @return string The content of the cell
     */
    function handle_ad_user_volunteer_count_column( $default, $column_name, $user_id ) {
        global $assignment_desk, $wpdb;
        if ( $column_name == __( '_ad_user_volunteer_count' ) ) {
            return $this->volunteer_count($user_id);
        }
        return $default;
    }
    
    /**
     * Get the number of times a user has volunteered for a post.
     * @param int $user_id The ID of the user
     * @return int The number of times a user has volunteered for a post.
     */
    function volunteer_count( $user_id ) {
        $count = 0;
        $volunteered_for = get_usermeta($user_id, '_ad_volunteer');
        if($volunteered_for){
            $count = count($volunteered_for);
        }
        return $count;
    }
    
    /**
     * Filter for displaying the pitch count custom column
     * @param string $default The content of the cell
     * @param string $column_name The name of the column
     * @param int $user_id The ID of the user
     * @return string The content of the cell
     */
    function handle_ad_user_pitches_count_column( $default, $column_name, $user_id ) {
        global $assignment_desk, $wpdb;
        if ( $column_name == __( '_ad_user_pitch_count' ) ) {
            return $this->pitch_count($user_id);
        }
        return $default;
    }
    
    /**
     * Get the number of posts the user has pitched.
     * @param int $user_id The ID of the user
     * @return int The number of posts the user has pitched
     */
    function pitch_count( $user_id ) {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key='_ad_pitched_by' AND meta_value='$user_id'");
        if(!$count){
            $count = 0;
        }
        return $count;
    }
    
    /**
     * Add custom fields to the user profile form.
     */
    function profile_options() {
      global $profileuser, $assignment_desk;
      
      $user_id = (int)$profileuser->ID;
      $user_type = (int)get_usermeta($user_id, $assignment_desk->option_prefix.'user_type', true);
      $current_user_type_term = get_term($user_type, $assignment_desk->custom_taxonomies->user_type_label);
      ?>      
      <a name="assignment_desk"></a>
      <h3>Assignment Desk Settings</h3>
      <table class="form-table">
      	<tr>
      	  <th>User Type</th>		
      <?php 
      if ( current_user_can($assignment_desk->define_editor_permissions) ){
          // Need to have 'get'=>'all' so that it will retrieve a custom taxonomy
          $user_type_taxonomy = get_terms($assignment_desk->custom_taxonomies->user_type_label, array('get'=>'all'));
      ?>
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
<?php
      }
      else {
        echo "<td>{$current_user_type_term->name}</td>";
      }
?>
      </tr>
    </table>
<?php
    }
    
    function profile_statistics() {
      global $profileuser, $assignment_desk;
?>
      <h3>Statistics</h3>
      <table class="form-table">
        <tr>
      	  <th>Average Words</th><td><?php echo $this->average_words($profileuser->ID); ?></td>
      	</tr>
      	<tr>
      	  <th>Total Words</th><td><?php echo $this->total_words($profileuser->ID); ?></td>
      	</tr>
      	<tr>
      	  <th>Pitches</th><td><?php echo $this->pitch_count($profileuser->ID); ?></td>
      	</tr>
        <tr>
      	  <th>Volunteered</th><td><?php echo $this->volunteer_count($profileuser->ID); ?></td>
      	</tr>
      </table>
<?php
    }

    /**
     * Update the custom data in the user profile form.
     * @param int $user_id The ID of the user
     */
    function save_profile_options($user_id) {
      global $assignment_desk;
      
      if ( current_user_can($assignment_desk->define_editor_permissions) ) {
          $user_type = (int)$_POST['assignment_desk-user_type'];
          update_usermeta($user_id, $assignment_desk->option_prefix.'user_type', $user_type);
      }
    }
  }
}
?>