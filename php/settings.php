<?php

if ( !class_exists( 'ad_settings' ) ){
  
/**
 * Class for managing all Assignment Desk settings views
 */
  class ad_settings
  {
  
    function __construct() {
      global $assigment_desk;
      
    }
    
    function general_settings() {
      global $wpdb, $assignment_desk;
      
      if($_POST['assignment_desk_save']){
          if (! wp_verify_nonce($_POST['_wpnonce'], 'assignment_desk-update-options') ) {
              die('Whoops! There was a problem with the data you posted. Please go back and try again.'); 
          }
          $this->options['google_api_key'] = wp_kses($_POST['google_api_key'], $allowedtags);
          $this->options['assignment_desk_twitter_hash']   = wp_kses($_POST['assignment_desk_twitter_hash'], $allowedtags);

          if(substr($this->options['assignment_desk_twitter_hash'], 0, 1) != "#"){
              echo '<div class="">Please enter a valid twitter hash.</div>';
          }
          else {
              $this->save_admin_options();
              echo '<div class="updated"><p>Success! Your changes were sucessfully saved!</p></div>';
          }
      }
?>                                   
      <div class="wrap">
      <h2>Assignment Desk Settings</h2>
      
      <p>@todo Setup options:
      <ul>
        <li>Enable pitch statuses</li>
        <li>Enable user types (community member, NYU student, NYT Reporter, Editor, etc.)</li>
        <li>Enable user roles (photographer, writer, copy editor, etc.)</li>
      </p>

      <form method="post" id="assignment_desk_options">
      <?php wp_nonce_field('assignment_desk-update-options'); ?>
      <table width="100%" cellspacing="2" cellpadding="5" class="form-table"> 
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
          <p><input class="button-primary" type="submit" id="assignment_desk_save" name="assignment_desk_save" value="Save Changes" /></p>
          
      </form>
<?php
      
      
    }
  
  }
  
  
}


?>