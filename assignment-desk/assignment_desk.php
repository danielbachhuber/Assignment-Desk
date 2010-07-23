<?php
/*
Plugin Name: Assignment Desk
Plugin URI: http://code.nyu.edu/projects/show/s20
Description: News pitch and story tools for local news blogs.
Author: Erik Froese, Tal Safran
Version: 1.0
Author URI: 
*/   
   
/*  Copyright 2010  

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define(ASSIGNMENT_DESK_FILE_PATH, __FILE__);
define(ASSIGNMENT_DESK_URL, plugins_url(plugin_basename(dirname(__FILE__)) .'/'));

define(ASSIGNMENT_DESK_DIR_PATH, dirname(__FILE__));
define(ASSIGNMENT_DESK_TEMPLATES_PATH, ASSIGNMENT_DESK_DIR_PATH . '/php/templates');

define(EDIT_FLOW_URL, plugins_url(plugin_basename('edit-flow') .'/'));

// Pitch Statuses
// These should be pulled from the DB.
define(P_APPROVED, 2);

// Install-time functions (DB setup).
include_once('php/install.php');

// Controllers
include_once('php/index-controller.php');
include_once('php/assignment-controller.php');
include_once('php/contributor-controller.php');
include_once('php/pitch-controller.php');

// Widgets
include_once('php/dashboard-widgets.php');

// Customize the Manage Posts page
require_once('php/manage_posts.php');
// Customize the Post edit page
require_once('php/post.php');
// Custom taxonomies
require_once('php/custom_taxonomies.php');

if (!class_exists('assignment_desk')) {
    
    class assignment_desk {
        //This is where the class variables go, don't forget to use @var to tell what they're for
        /** @var string The options string name for this plugin */
        private $optionsName = 'assignment_desk_options';
        
        /** @var string $localizationDomain Domain used for localization */
        private $localizationDomain = "assignment_desk";

        /** @var string $pluginurlpath The path to this plugin */
        public $this_plugin_path = ASSIGNMENT_DESK_DIR_PATH;
        
        /** @var the URL to this plugin */
        public $url = ASSIGNMENT_DESK_URL;

        /** @var string templates_path The path to the templates directory. */
        public $templates_path = ASSIGNMENT_DESK_TEMPLATES_PATH;

        /** @var string $table_prefix The prefix for this plugin's DB tables. */
        public $table_prefix = 'ad_';

        /** @var array $options Stores the options for this plugin. */
        public $options = array();

        /** @var array $tables stores DB table short name => full name. */
        public $tables;

        /** @var assignment_desk_install $installer handles install-time tasks. */
        public $installer;

	    // Controllers
	    /** @var assignment_desk_index_controller $index_controller serves the activity feed views. */
	    public $index_controller;

	    /**
        * @var assignment_desk_pitch_controller $index_controller serves the editor's pitch management views.
        */
        public $pitch_controller;

        /**
        * @var assignment_desk_assignment_controller $assignment_controller serves the editor's assignment-management views.
        */
        public $assignment_controller;
        
        /**
        * @var assignment_desk_contributor_controller $contributor_controller serves the contributor profile and assignment views.
        */
        public $contributor_controller;

	    // Widgets
	    /**
        * @var assignment_desk_dashboard_widgets $dashboard_widgets provides the widget.
        */
	    public $dashboard_widgets;
        
        /**
        *  Constructor
        */        
        function __construct(){
            global $wpdb;
            
            // Language Setup
            $locale = get_locale();
            $mo = dirname(__FILE__) . "/languages/" . $this->localizationDomain . "-".$locale.".mo";
            load_textdomain($this->localizationDomain, $mo);
            
            // Initialize the options
            $this->getOptions();
            
            // Database table names.
            $this->tables = array(
                'pitchstatus'       => $wpdb->prefix . $this->table_prefix . "pitchstatus",
                'pitch'             => $wpdb->prefix . $this->table_prefix . "pitch",
                'pitch_volunteer'   => $wpdb->prefix . $this->table_prefix . "pitch_volunteer",
				'pitch_votes'		=> $wpdb->prefix . $this->table_prefix . 'pitch_votes',
                'event'             => $wpdb->prefix . $this->table_prefix . "event"
            );

            $this->installer = new assignment_desk_install();
            
            // Controllers for WP Admin views.
            $this->index_controller       = new assignment_desk_index_controller();
            $this->assignment_controller  = new assignment_desk_assignment_controller();
            $this->pitch_controller       = new assignment_desk_pitch_controller();
            $this->contributor_controller = new assignment_desk_contributor_controller();

	        // Widgets
            $this->dashboard_widgets     = new assignment_desk_dashboard_widgets();
            
            $this->custom_user_types = new ad_custom_taxonomy('user_type', 'user', 
                                                                array( 'hierarchical' => false, 
                                                                        'label' => __('User Contributor Level'))
                                                            );
            $this->custom_user_roles = new ad_custom_taxonomy('user_post_role', 'user', 
                                                                array( 'label' => __('User Post Role'),)
                                                            );
            $this->custom_pitch_statuses = new ad_custom_taxonomy('pitch_status', 'post', 
                                                                array('label' => __('Pitch Statuses'),)
                                                                );        
        }
        
        // Actions that happen only on activate.
        function activate_plugin() {
            $this->installer->setup_db();
            $this->flush_rewrite_rules();
        }
        
        /**
            Assignment Desk public pages.
            
            Define new rewrite rules.
            Merge them into the wordpress set.
            
        */
        function add_public_facing_pages() {
            // echo 'RULES';
            global $wp_rewrite;
            $url_bases = array("pitches", "community", );
            $new_rules = array();
            
            foreach($url_bases as $base){
                $new_rules["/$base/*$"] = "index.php?";
            }
            if(!$wp_rewrite->rules){
                $wp_rewrite->rules = $new_rules;
            }
            else {
                $wp_rewrite->rules = array_merge($new_rules, $wp_rewrite->rules); 
            }
        }
        
        /**
            Load a template from the public templates directory.
            
            This is a very simple dispatcher. It only works one-level "deep".
            
            'foo/        => $this->templates_path . '/public/foo/index.php'
            'pitches/foo/ => $this->templates_path . '/public/foo/bar.php'
            'pitches/foo/bar/ => ERROR
        */
        function load_public_template() {
            global $wp_query;
            
            // Add to here to define other top-level directories.
            $static_base_urls = array('pitches', 'community');
            $pagename = get_query_var("pagename");
            // Extract the base and subpage.
            $pagename_modules = explode('/', $pagename);
            $pagename_base = $pagename_modules[0];
            
            // No sub-page specified.
            if(count($pagename_modules) == 1){
                $pagename .= '/index';
            }
            
            // If its in the $static_base_urls load the template.
            if(in_array($pagename_base, $static_base_urls)){
                include_once($this->templates_path . '/public/' . $pagename . '.php');
                // TODO - Get the user back to the home page.
                die();
            }            
        }
        /**
        *    Flush the rewrite rules. Only do this when you activate the plugin.
        *    http://codex.wordpress.org/Function_Reference/WP_Rewrite
        */
        function flush_rewrite_rules(){
            global $wp_rewrite;
            $wp_rewrite->flush_rules();
        }  
        
        /**
        * Retrieves the plugin options from the database.
        * @return array
        */
        function getOptions() {
            //Don't forget to set up the default options
            if (!$theOptions = get_option($this->optionsName)) {
                $theOptions = array('default'=>'options');
                update_option($this->optionsName, $theOptions);
            }
            $this->options = $theOptions;
            
            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            //There is no return here, because you should use the $this->options variable!!!
            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        }
        /**
        * @desc Saves the admin options to the database.
        */
        function save_admin_options(){
            return update_option($this->optionsName, $this->options);
        }
        
        /**
	    * @desc Adds our CSS to the admin pages
	    */
    	function add_admin_css() {
    	    echo "<link rel='stylesheet' id='ad-admin-css'  
    	            href='" . ASSIGNMENT_DESK_URL . "css/admin.css' type='text/css' media='all' />";
	    }
	    
	    /**
	    * @desc Adds our JS to the admin pages
	    */
    	function add_admin_js() {
    	    wp_enqueue_script('tiny_mce');
            wp_enqueue_script('wp-ajax-response');
            wp_enqueue_script('jquery-truncator-js', ASSIGNMENT_DESK_URL .'js/jquery.truncator.js', 
                                array('jquery'));
            wp_enqueue_script('jquery-autocomplete-js', ASSIGNMENT_DESK_URL .'js/jquery.autocomplete.min.js', 
                                array('jquery'));
            wp_enqueue_script('edit_flow-post_comments-js', EDIT_FLOW_URL.'js/post_comment.js', 
                                array('jquery'));
	    }
        
        /**
	    * @desc Adds menu items for the plugin
	    */
    	function add_admin_menu_items() {
    	    // This is the button in the admin menu.
    		add_menu_page('Assignment Desk', 'Assignment Desk', 
                            'edit_posts', 
                            'assignment_desk-menu', array(&$this->index_controller, 'dispatch'));

            // Add "Activity" for contributors and higher.
    		$activity_page = add_submenu_page('assignment_desk-menu', 'Activity', 'Activity', 
    		                'edit_posts', 
    		                'assignment_desk-index',
    		                array(&$this->index_controller, 'dispatch'));

            // Add "Your Content" for contributors and higher.
    		add_submenu_page('assignment_desk-menu', 'Your Content', 'Your Content', 
                            'edit_posts', 
                            'assignment_desk-contributor',
                            array(&$this->contributor_controller, 'dispatch'));

    		// Add Pitches sub-menu for Editors.
            $pitches_page = add_submenu_page('assignment_desk-menu', 'Pitches', 'Pitches', 
                            'editor', 
                            'assignment_desk-pitch',
                            array(&$this->pitch_controller, 'dispatch'));
            /* Using registered $pitches_page handle to hook script load */
            add_action('admin_print_scripts-' . $pitches_page, array(&$this, 'add_admin_js'));
		
    		// Add Assignments sub-menu for Editors
            $assignments_page = add_submenu_page('assignment_desk-menu', 'Assignments', 
                            'Assignments', 
                            'editor', 
                            'assignment_desk-assignments',
                            array(&$this->assignment_controller, 'dispatch'));
                            
            /* Using registered $assignments_page handle to hook script load */
            add_action('admin_print_scripts-' . $assignments_page, array(&$this, 'add_admin_js'));
                            
            // Add Settings sub-menu page for Editors
		    add_submenu_page('assignment_desk-menu', 'Settings', 'Settings', 
		                    'editor',
		                    'assignment_desk-settings', 
		                    array(&$this, 'admin_settings_page'));
    	}
        
        /**
        * @desc Adds the Settings link to the plugin activate/deactivate page
        */
        function filter_plugin_actions($links, $file) {
           // If your plugin is under a different top-level menu than Settiongs 
           // (IE - you changed the function above to something other than add_options_page)
           // Then you're going to want to change options-general.php below to the name of your 
           // top-level page
           $settings_link = '<a href="options-general.php?page=';
           $settings_link .= basename(__FILE__) . '">' . __('Settings') . '</a>';
           array_unshift( $links, $settings_link ); // before other links

           return $links;
        }

        
        /**
        * Adds settings/options page
        */
        function admin_settings_page() { 
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
                        <th colspan=2><input type="submit" name="assignment_desk_save" value="Save" /></th>
                    </tr>
                </table>
            </form>
<?php
        }
  } //End Class
} //End if class exists statement

global $assignment_desk;
$assignment_desk = new assignment_desk();

// Hook to perform action when plugin activated
register_activation_hook(ASSIGNMENT_DESK_FILE_PATH, array(&$assignment_desk, 'activate_plugin'));

add_action('admin_menu', array(&$assignment_desk, 'add_admin_menu_items'));
add_action('admin_print_styles', array(&$assignment_desk, 'add_admin_css'));

// Public-facing pages
add_action('parse_query', array(&$assignment_desk, 'load_public_template')); 
add_action('generate_rewrite_rules', array(&$assignment_desk, 'add_public_facing_pages')); 

// AJAX
add_action('wp_ajax_user_search', array(&$assignment_desk->assignment_controller, 'ajax_user_search'));