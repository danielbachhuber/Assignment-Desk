<?php
/*
Plugin Name: Assignment Desk
Plugin URI: http://openassignment.org/
Description: News pitch and story tools for local news blogs.
Author: Daniel Bachhuber, Erik Froese
Version: 0.9.5
Author URI: http://openassignment.org/
*/   
   
/*  Copyright 2010-2011

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

define( 'ASSIGNMENT_DESK_ROOT' , dirname(__FILE__) );	
define( 'ASSIGNMENT_DESK_FILE_PATH' , ASSIGNMENT_DESK_ROOT . '/' . basename(__FILE__) );
define( 'ASSIGNMENT_DESK_URL', plugins_url(plugin_basename(dirname(__FILE__)) .'/') );
define( 'ASSIGMENT_DESK_VERSION', '0.9.5' );

require_once( ASSIGNMENT_DESK_ROOT . '/' . 'php/user.php');
require_once( ASSIGNMENT_DESK_ROOT . '/' . 'php/dashboard_widgets.php');
require_once( ASSIGNMENT_DESK_ROOT . '/' . 'php/post.php');
require_once( ASSIGNMENT_DESK_ROOT . '/' . 'php/settings.php');
require_once( ASSIGNMENT_DESK_ROOT . '/' . 'php/manage_posts.php');
require_once( ASSIGNMENT_DESK_ROOT . '/' . 'php/custom_taxonomies.php');
require_once( ASSIGNMENT_DESK_ROOT . '/' . 'php/public_views.php');
require_once( ASSIGNMENT_DESK_ROOT . '/' . 'php/upgrade.php');

if (!class_exists('assignment_desk')) {
    
    class assignment_desk {
      
        //This is where the class variables go, don't forget to use @var to tell what they're for
        /** @var string The options string name for this plugin */
        private $options_name = 'assignment_desk_options';
        
        /** @var string $localizationDomain Domain used for localization */
        private $localizationDomain = "assignment_desk";
        
        /** @var the URL to this plugin */
        public $url = ASSIGNMENT_DESK_URL;

        public $option_prefix = 'ad_';

		var $options_group = 'assignment_desk_';
		var $pitch_form_options_group = 'assignment_desk_pitch_form';
		var $public_facing_options_group = 'assignment_desk_public_facing';	
		
		var $top_level_page = 'assignment_desk';
		var $pitch_form_settings_page = 'assignment_desk_pitch_form_settings';
		var $public_facing_settings_page = 'assignment_desk_public_facing_settings';
		
		public $pitch_form_key = 'assignment-desk-pitch-form';
		public $all_posts_key = 'assignment-desk-all-posts';
		
		// Only WP Editor and above can edit pages
		public $define_editor_permissions = 'edit_pages';
		public $define_admin_permissions = 'manage_options';
        
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
             */
            $this->custom_taxonomies = new ad_custom_taxonomies(); 
            $this->user = new ad_user();
			$this->post = new ad_post();
        	$this->manage_posts = new ad_manage_posts();
			$this->settings = new ad_settings();
            $this->public_views = new ad_public_views();
            $this->dashboard_widgets = new ad_dashboard_widgets();
			$this->upgrade = new ad_upgrade();

			/**
			 * Store form messages
			 */
			$_REQUEST['assignment_desk_messages'] = array();
			
			/**
			 * Provide an easy way to access Assignment Desk settings w/o using a nasty method every time
			 */
			$this->general_options = get_option($this->get_plugin_option_fullname('general'));
			$this->pitch_form_options = get_option($this->get_plugin_option_fullname('pitch_form'));
			$this->public_facing_options = get_option($this->get_plugin_option_fullname('public_facing'));
			
			/**
			 * Create any custom tables we need
			 */
			$this->votes_table_name = $wpdb->prefix . 'ad_votes';	
			$this->create_tables();
			
			// Core hooks to initialize the plugin
			add_action( 'init', array(&$this,'init') );
			add_action( 'admin_init', array(&$this,'admin_init') );
			
        }

		/**
         * Initialize various bits and pieces of functionality
         */
		function init() {

			$this->custom_taxonomies->init();
            $this->user->init();
			
			// Only load admin-specific functionality in the admin
			if ( is_admin() ) {
				add_action( 'admin_menu', array(&$this, 'add_admin_menu_items'));
				add_action( 'admin_menu', array(&$this->custom_taxonomies, 'remove_assignment_status_post_meta_box'));
				$this->manage_posts->init();
				$this->dashboard_widgets->init();
				$this->post->init();
			} else if ( !is_admin() ) {
				$this->public_views->init();
			}
			
		}

		/**
		 * Initialize the plugin for the admin. 
		 */
		function admin_init() {	
			
		    // Registering settings requires the WP admin to be set up
			$this->settings->init();
			$this->add_admin_assets();
			
			$saved_version = get_option( $this->get_plugin_option_fullname('version') );
			// Only run the upgrade methods if the saved version is less than the declared version
			if ( version_compare( $saved_version, ASSIGMENT_DESK_VERSION, '<') ) {
				$this->upgrade->run_upgrade( $saved_version );
			}
		}
        
        /**
        * Check to see whether this is the first time AD has been activated.
        * Call the activate() or activate_once() method of each component that has work to do at
        * activation time.
        */
        function activate_plugin() {
            
            $this->custom_taxonomies->init();
            $this->custom_taxonomies->activate();

            // This is the first time we've ever activated the plugin.
            if ( $this->general_options['ad_installed_once'] != 'on' ) {
                // Custom Taxonomies
                $this->custom_taxonomies->activate_once();
                $this->settings->setup_defaults();
                
                // Update the settings so we don't go through the install-time routines on upgrade/re-activation
                $this->general_options = get_option($this->get_plugin_option_fullname('general'));
                $this->general_options['ad_installed_once'] = 'on';
                update_option($this->get_plugin_option_fullname('general'), $this->general_options);
            }

        }

		/**
		 * Create any custom tables we may need if they don't already exist
		 */
		function create_tables() {
			global $wpdb;

			// Insert a new voting table if the table doesn't already exist
			if ( $wpdb->get_var("SHOW TABLES LIKE '$this->votes_table_name'") != $this->votes_table_name ) {
				$query = "CREATE TABLE $this->votes_table_name (
							id int(11) unsigned NOT NULL AUTO_INCREMENT,
							post_id int(11) DEFAULT NULL,
							user_id int(11) DEFAULT NULL,							
							last_updated timestamp NULL DEFAULT CURRENT_TIMESTAMP,
							PRIMARY KEY  id (id)
						);";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				$returned = dbDelta($query);
			}
			
		}

		/**
		 * Get the full name of a plugin options group
		 */
		function get_plugin_option_fullname( $name ) {
			return $this->options_group . $name;
		}
		
		/**
		 * edit_flow_enabled()
		 * Helper method checks to see if Edit Flow is activated
		 *
		 * @param string $functionality Functionality to check to see if enabled
		 * @return bool $enabled Whether it's enabled or not
		 */
		function edit_flow_enabled( $functionality = null ) {
			
			$enabled = false;
			if ( !class_exists( 'edit_flow' ) ) {
				return $enabled;
			} else {
				global $edit_flow;
			}
			
			switch( $functionality ) {
				case 'custom_post_statuses':
					if ( version_compare( EDIT_FLOW_VERSION, '0.6.1', '>=' ) && function_exists( 'post_type_supports' ) ) {
						$enabled = post_type_supports( 'post', 'ef_custom_statuses' );
					} else {
						$enabled = (bool) $edit_flow->get_plugin_option( 'custom_statuses_enabled' );
					}
					break;
				case null:
					$enabled = class_exists( 'edit_flow' );
					break;
				default:
					$enabled = false;
					break;
			}
			return $enabled;
			
		} // END edit_flow_enabled()
		
		/**
		 * edit_flow_custom_post_statuses_enabled()
		 * Helper method checks to see whether Edit Flow's custom post statuses are enabled
		 *
		 * @return bool $custom_post_statuses_enabled Whether they're enabled or not
		 */
		function edit_flow_custom_post_statuses_enabled() {
			
		} // END edit_flow_custom_post_statuses_enabled()
		
		/**
		 * Helper method checks to see if Co-Authors Plus is activated
		 */
		function coauthors_plus_exists() {
			return class_exists('coauthors_plus');
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
			wp_enqueue_script('ad-admin-js', ASSIGNMENT_DESK_URL .'js/admin.js', 
	                             array('jquery'), ASSIGMENT_DESK_VERSION);

	    }

      	/**
	     * Adds menu items for the plugin
	     */
      	function add_admin_menu_items() {
      
			/**
	         * Top-level Assignment Desk menu goes to Settings
	         * @permissions Edit posts or higher
	         */
			add_menu_page( 'Assignment Desk', 'Assignment Desk', 
	                        $this->define_admin_permissions, $this->top_level_page, 
	                        array(&$this->settings, 'general_settings'));
	
			/**
	         * Pitch Form settings page
	         */
			add_submenu_page( $this->top_level_page, 'Pitch Form',
							'Pitch Form', $this->define_admin_permissions,
							$this->pitch_form_settings_page, array(&$this->settings, 'pitch_form_settings'));
							
			/**
	         * Public-Facing settings page
	         */
			add_submenu_page( $this->top_level_page, 'Public-Facing',
							'Public-Facing', $this->define_admin_permissions,
							$this->public_facing_settings_page, array(&$this->settings, 'public_facing_settings'));
        
	        /**
	         * WordPress taxonomy view for editing Pitch Statuses
	         */
	        add_submenu_page( $this->top_level_page, 'Assignment Statuses',
	                        'Assignment Statuses', $this->define_editor_permissions, 'edit-tags.php?taxonomy='.$this->custom_taxonomies->assignment_status_label);
        
	        /**
	         * WordPress taxonomy view for editing User Types
	         */
	        add_submenu_page( $this->top_level_page, 'User Types',
	                        'User Types', $this->define_editor_permissions,
	                        'edit-tags.php?taxonomy='.$this->custom_taxonomies->user_type_label);
        
	        /**
	         * WordPress taxonomy view for editing User Roles
	         */
	        add_submenu_page( $this->top_level_page, 'User Roles',
	                        'User Roles', $this->define_editor_permissions,
	                        'edit-tags.php?taxonomy='.$this->custom_taxonomies->user_role_label);

    	}
		
  } //End Class
  
} //End if class exists statement

global $assignment_desk;
$assignment_desk = new assignment_desk();

// Hook to perform action when plugin activated
register_activation_hook( ASSIGNMENT_DESK_FILE_PATH, array(&$assignment_desk, 'activate_plugin') );

