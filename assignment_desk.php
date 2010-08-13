<?php
/*
Plugin Name: Assignment Desk
Plugin URI: http://code.nyu.edu/projects/show/s20
Description: News pitch and story tools for local news blogs.
Author: Erik Froese, Daniel Bachhuber, Tal Safran
Version: 0.2.1
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
define('ASSIGMENT_DESK_VERSION', '0.2.1');

define('ASSIGNMENT_DESK_DIR_PATH', dirname(__FILE__));
define('ASSIGNMENT_DESK_TEMPLATES_PATH', ASSIGNMENT_DESK_DIR_PATH . '/php/templates');


require_once('php/user.php');
require_once('php/dashboard_widgets.php');
require_once('php/post.php');
require_once('php/settings.php');
require_once('php/manage_posts.php');
require_once('php/custom_taxonomies.php');
require_once('php/public_views.php');

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
        
        public $option_prefix = 'ad_';

		var $options_group = 'assignment_desk_';
		
		var $top_level_page = 'assignment_desk';
		
		public $pitch_form_key = 'assignment-desk-pitch-form';
		public $all_posts_key = 'assignment-desk-all-posts';
		
		// Only WP Editor and above can edit pages
		public $define_editor_permissions = 'edit_pages';
		public $define_admin_permissions = 'manage_options';

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
             * Instantiate all of our classes before running initialization on each!
             * @todo All initialization should be abstracted to an 'init' function instead of in the constructor
             */
            $this->custom_taxonomies = new ad_custom_taxonomies(); 
            $this->user = new ad_user();
			$this->post = new ad_post();
        	$this->manage_posts = new ad_manage_posts();
			$this->settings = new ad_settings();
            $this->public_views = new ad_public_views();
            $this->dashboard_widgets = new ad_dashboard_widgets();

			$this->general_options = get_option($this->get_plugin_option_fullname('general'));

            /**
             * Initialize various bits and pieces of functionality
             * @todo Should these be interchangeable and not have internal dependencies?
             */
            $this->custom_taxonomies->init();
            $this->user->init_user();
        }

		function init() {
			if ( is_admin() ) {
				add_action( 'admin_menu', array(&$this, 'add_admin_menu_items'));
				add_action( 'admin_menu', array(&$this->custom_taxonomies, 'remove_assignment_status_post_meta_box') );
				$this->manage_posts->init();
				$this->dashboard_widgets->init();
				
			} else if (!is_admin()) {
				$this->public_views->init();
			}
			
		}

		/**
		 * Initialize the plugin for the admin 
		 */
		function admin_init() {
			
			$this->add_admin_assets();			
			$this->settings->init();			
			
		}
        
        // Actions that happen only on activate.
        function activate_plugin() {
            //$this->installer->setup_db();
            $this->custom_taxonomies->activate();
        }

		/**
		 * Utility function
		 */
		function get_plugin_option_fullname( $name ) {
			
			return $this->options_group . $name;
			
		}
		
		/**
		 * Check to see if Edit Flow is activated
		 */
		function edit_flow_exists() {
			if ( class_exists('edit_flow') ) {
				return true;
			} else {
				return false;
			}
		}
		
		/**
		 * Check to see if Co-Authors Plus is activated
		 */
		function coauthors_plus_exists() {
			if ( class_exists('coauthors_plus') ) {
				return true;
			} else {
				return false;
			}
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
	     * Adds menu items for the plugin
	     */
      	function add_admin_menu_items() {
      
			/**
	         * Top-level Assignment Desk menu goes to Settings
	         * @permissions Edit posts or higher
	         */
			add_menu_page('Assignment Desk', 'Assignment Desk', 
	                        $this->define_admin_permissions, $this->top_level_page, 
	                        array(&$this->settings, 'general_settings'));
        
	        /**
	         * WordPress taxonomy view for editing Pitch Statuses
	         */
	        add_submenu_page($this->top_level_page, 'Assignment Statuses',
	                        'Assignment Statuses', $this->define_editor_permissions, 'edit-tags.php?taxonomy='.$this->custom_taxonomies->assignment_status_label);
        
	        /**
	         * WordPress taxonomy view for editing User Types
	         */
	        add_submenu_page($this->top_level_page, 'User Types',
	                        'User Types', $this->define_editor_permissions,
	                        'edit-tags.php?taxonomy='.$this->custom_taxonomies->user_type_label);
        
	        /**
	         * WordPress taxonomy view for editing User Roles
	         */
	        add_submenu_page($this->top_level_page, 'User Roles',
	                        'User Roles', $this->define_editor_permissions,
	                        'edit-tags.php?taxonomy='.$this->custom_taxonomies->user_role_label);

    	}
		
  } //End Class
  
} //End if class exists statement

global $assignment_desk;
$assignment_desk = new assignment_desk();

// Core hooks to initialize the plugin
add_action('init', array(&$assignment_desk,'init'));
add_action('admin_init', array(&$assignment_desk,'admin_init'));

// Hook to perform action when plugin activated
register_activation_hook(ASSIGNMENT_DESK_FILE_PATH, array(&$assignment_desk, 'activate_plugin'));