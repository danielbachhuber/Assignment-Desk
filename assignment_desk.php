<?php
/*
Plugin Name: Assignment Desk
Plugin URI: http://code.nyu.edu/projects/show/s20
Description: News pitch and story tools for local news blogs.
Author: Erik Froese, Tal Safran, Daniel Bachhuber
Version: 0.1
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

define('ASSIGNMENT_DESK_FILE_PATH', __FILE__);
define('ASSIGNMENT_DESK_URL', plugins_url(plugin_basename(dirname(__FILE__)) .'/'));
define('ASSIGMENT_DESK_VERSION', '0.1');

define('ASSIGNMENT_DESK_DIR_PATH', dirname(__FILE__));
define('ASSIGNMENT_DESK_TEMPLATES_PATH', ASSIGNMENT_DESK_DIR_PATH . '/php/templates');

// Pitch Statuses
// These should be pulled from the DB.
define('P_APPROVED', 2);

// Install-time functions (DB setup).
include_once('php/install.php');

// Controllers
include_once('php/index-controller.php');
include_once('php/contributor-controller.php');

// Various admin views
include_once('php/dashboard-widgets.php');
include_once('php/post.php');
include_once('php/settings.php');

// Customize the Manage Posts page
require_once('php/manage_posts.php');
// AJAX function for searching users
require_once('php/ajax_user_search.php');
// Custom taxonomies
require_once('php/custom_taxonomies.php');
// Serve public 
require_once('php/public-controller.php');

if (!class_exists('assignment_desk')) {
    
    class assignment_desk {
      
        //This is where the class variables go, don't forget to use @var to tell what they're for
        /** @var string The options string name for this plugin */
        private $options_name = 'assignment_desk_options';
        
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

	    
	      /** 
	       * @var assignment_desk_index_controller $index_controller serves the activity feed views.
	       */
	      public $index_controller;
        
        /**
         * @var assignment_desk_contributor_controller $contributor_controller serves 
         * the contributor profile and assignment views.
         */
        public $contributor_controller;

	      /**
         * @var assignment_desk_dashboard_widgets $dashboard_widgets provides the widget.
         */
        public $dashboard_widgets;
        
        /**
         * @var ad_settings provides all of the settings views
         */
        public $settings;
        
        /**
         * Assignment Desk Constructor
         */        
        function __construct(){
          
            global $wpdb;
            
            // Language Setup
            $locale = get_locale();
            $mo = dirname(__FILE__) . "/languages/" . $this->localizationDomain . "-".$locale.".mo";
            load_textdomain($this->localizationDomain, $mo);
            
            /**
             * Initialize all of our classes
             */
            $this->custom_taxonomies = new ad_custom_taxonomies(); 
            
            // Initialize the options
            $this->getOptions();
            
            $this->custom_taxonomies->init_taxonomies();
            
            // @todo Make all public views just a template tag
            $this->public_controller = new ad_public_controller();

            $this->installer = new assignment_desk_install();
            

            // Build the various admin views and add them to the admin
            $this->build_admin_views();
            add_action('admin_init', array(&$this, 'add_admin_assets'));
            add_action('admin_menu', array(&$this, 'add_admin_menu_items'));
            
			
        }
        
        // Actions that happen only on activate.
        function activate_plugin() {
            $this->installer->setup_db();
            $this->public_controller->flush_rewrite_rules();
        }

        /**
         * Retrieves the plugin options from the database.
         * @return array
         */
        function getOptions() {
            //Don't forget to set up the default options
            if (!$theOptions = get_option($this->options_name)) {
                $theOptions = array('default'=>'options');
                update_option($this->options_name, $theOptions);
            }
            $this->options = $theOptions;
            
            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            //There is no return here, because you should use the $this->options variable!!!
            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        }
        /**
        * Saves the admin options to the database.
        */
        function save_admin_options(){
            return update_option($this->options_name, $this->options);
        }
        
        /**
	    * Adds our CSS to the admin pages
	    */
    	function add_admin_assets() {
    	  
        // Enqueue stylesheets
        wp_enqueue_style('ad-admin-css', ASSIGNMENT_DESK_URL.'css/admin.css', null, ASSIGMENT_DESK_VERSION, 'all');
        
        // Enqueue necessary scripts
        wp_enqueue_script('tiny_mce');
        wp_enqueue_script('wp-ajax-response');
        wp_enqueue_script('jquery-truncator-js', ASSIGNMENT_DESK_URL .'js/jquery.truncator.js', 
                              array('jquery'));
        wp_enqueue_script('jquery-autocomplete-js', ASSIGNMENT_DESK_URL .'js/jquery.autocomplete.min.js', 
                              array('jquery'));        
	    }
	    
	    /**
	     * Builds the various WordPress admin views we need
	     */
	    function build_admin_views() {
	      
	      // Various views we want to instantiate
	      // @todo Refactor dashboard widgets
        // $this->dashboard_widgets = new assignment_desk_dashboard_widgets();
        $this->manage_posts = new assignment_desk_manage_posts();
        $this->settings = new ad_settings();
        
        // We should deprecate these views in favor of more explicit views
        $this->index_controller = new assignment_desk_index_controller();
        $this->contributor_controller = new assignment_desk_contributor_controller();
        
	    }
        
      /**
	     * Adds menu items for the plugin
	     */
      function add_admin_menu_items() {
      
        /**
         * Top-level Assignment Desk menu goes to Settings
         * @permissions Edit posts or higher
         */
    		add_menu_page('Assignment Desk', 'Assignment Desk', 
                        'edit_posts', 'assignment_desk', 
                        array(&$this->settings, 'general_settings'));
        
        /**
         * WordPress taxonomy view for editing Pitch Statuses
         */
        add_submenu_page('assignment_desk', 'Pitch Statuses',
                        'Pitch Statuses', 'edit_posts',
                        'edit-tags.php?taxonomy='.$this->custom_taxonomies->pitch_status_label);
        
        /**
         * WordPress taxonomy view for editing User Types
         */
        add_submenu_page('assignment_desk', 'User Types',
                        'User Types', 'edit_posts',
                        'edit-tags.php?taxonomy='.$this->custom_taxonomies->user_type_label);
        
        /**
         * WordPress taxonomy view for editing User Roles
         */
        add_submenu_page('assignment_desk', 'User Roles',
                        'User Roles', 'edit_posts',
                        'edit-tags.php?taxonomy='.$this->custom_taxonomies->user_role_label);


         /*   // Add "Activity" for contributors and higher.
    		 $activity_page = add_submenu_page('assignment_desk-menu', 'Activity', 'Activity', 
    		                'edit_posts', 
    		                'assignment_desk-index',
    		                array(&$this->index_controller, 'dispatch'));

        // Add "Your Content" for contributors and higher.
    		add_submenu_page('assignment_desk-menu', 'Your Content', 'Your Content', 
                            'edit_posts', 
                            'assignment_desk-contributor',
                            array(&$this->contributor_controller, 'dispatch'));
		
    		// Add Assignments sub-menu for Editors
            $assignments_page = add_submenu_page('assignment_desk-menu', 'Assignments', 
                            'Assignments', 
                            5, 
                            'assignment_desk-assignments',
                            array(&$this->assignment_controller, 'dispatch'));
                            
           */
    	}

		/**
		* This function currently doesn't work. We need to figure out how to link into the edit.php
		* page and pass the post_status=pitch flag.
		*/
		function link_to_pitches(){
			$_GET['post_status'] = 'pitch';
			//include(ABSPATH . 'wp-admin/edit.php');
		}
		
  } //End Class
  
} //End if class exists statement

global $assignment_desk;
$assignment_desk = new assignment_desk();

// Hook to perform action when plugin activated
register_activation_hook(ASSIGNMENT_DESK_FILE_PATH, array(&$assignment_desk, 'activate_plugin'));