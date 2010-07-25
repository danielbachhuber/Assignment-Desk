<?php
if(!class_exists('ad_public_controller')){
	
class ad_public_controller {
	
	function __construct(){
		// Public-facing pages
		add_action('parse_query', array(&$this, 'load_public_template')); 
		add_action('generate_rewrite_rules', array(&$this, 'add_public_facing_pages')); 
	}

	/**
     * Assignment Desk public pages.
     *   
     * Define new rewrite rules.
     * Merge them into the wordpress set.   
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
    * Load a template from the public templates directory.
    * This is a very simple dispatcher. It only works one-level "deep".
    *    
    * 'foo/        => $this->templates_path . '/public/foo/index.php'
    * 'pitches/foo/ => $this->templates_path . '/public/foo/bar.php'
    * 'pitches/foo/bar/ => ERROR
    */
    function load_public_template() {
        global $wp_query, $assignment_desk;
        
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
            include_once($assignment_desk->templates_path . '/public/' . $pagename . '.php');
            // TODO - Get the user back to the home page.
            die();
        }            
    }

    /**
    * Flush the rewrite rules. Only do this when you activate the plugin.
    * http://codex.wordpress.org/Function_Reference/WP_Rewrite
    */
    function flush_rewrite_rules(){
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }
} // END:class ad_public_controller

} // END:if(!class_exists('ad_public_controller'))
?>