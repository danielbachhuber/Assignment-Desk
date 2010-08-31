jQuery(document).ready(function(){
    /**
	 * Initialize Co-Author Plus auto-suggest if it exists
	 */
	if (coauthor_ajax_suggest_link) {
	    
	    jQuery('#ad-pitched-by-search').focus(function(){
	        if ( jQuery(this).val() == 'Pitched by'){
	            jQuery(this).val('');
	        }
	    });
	    
		jQuery('#ad-pitched-by-search').suggest(coauthor_ajax_suggest_link, {
	    	onSelect: function() {
	        	var vals = this.value.split('|');
	        	var author = {};
	        	author.id = jQuery.trim(vals[0]);
	        	author.login = jQuery.trim(vals[1]);
	        	author.name = jQuery.trim(vals[2]);
	        	jQuery('#ad-pitched-by-select').val(author.id);
	        	jQuery(this).val(author.name);
	    	}
		}).keydown(function(e) {
	    	if (e.keyCode == 13) {
	        	return false;
	    	}
		});
	}
    
});