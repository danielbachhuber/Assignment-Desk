<?php

if(!class_exists('ad_custom_taxonomy')){
    
/**
* Base class for operations on custom taxonomies
*/
class ad_custom_taxonomy {
    
    /**
    * Pass the same arguments as you would to the register_taxonomy function.
    * @param string $taxonomy_id The id of the taxonomy
    * @param string $object The object this taxonomy applies to
	* @param array $args Standard arguments for the register_taxonomy function
    */
    function __construct($taxonomy_id, $object, $args){
        $this->taxonomy = $taxonomy_id;
        if(!is_taxonomy($this->taxonomy)){
            register_taxonomy($taxonomy_id, $object, $args); 
        }
    }
    
    /**
	 * Adds a new custom status as a term in the wp_terms table.
	 * Basically a wrapper for the wp_insert_term class.
	 *
	 * The arguments decide how the term is handled based on the $args parameter.
	 * The following is a list of the available overrides and the defaults.
	 *
	 * 'description'. There is no default. If exists, will be added to the database
	 * along with the term. Expected to be a string.
	 *
	 * 'slug'. Expected to be a string. There is no default.
	 *
	 * @param int|string $term The status to add or update
	 * @param array|string $args Change the values of the inserted term
	 * @return array|WP_Error The Term ID and Term Taxonomy ID
	 *
	 */
	function insert_term($term, $args=array()){
		$ret = wp_insert_term( $term, $this->taxonomy, $args );
	} // END: insert_term
	
	function get_taxonomy_id(){
		return $this->taxonomy_id;
	}
}
    
}