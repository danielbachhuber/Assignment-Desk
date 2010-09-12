jQuery(document).ready(function() {
	/**
	* Replaces the from labels with more descriptive labels.
	* Wordpress 2.9.2 doesn't give us a way to change the taxonomy labels on edit-tags.php
	* Not used in 3.0+
	*
	* The title and singlular variables are set by javascript printed by
	* $assignment_desk->custom_taxonomies->javascript() 
	*/
	if ( typeof title != 'undefined' && typeof singular != 'undefined' ) {
		jQuery('div.tagcloud').remove();
		jQuery('div.wrap h2').replaceWith('<h2>' + title + '</h2>');
		jQuery('div.tagcloud h3').remove();
		jQuery('div.form-wrap h3').replaceWith('<h3 class="sg_selected">' + singular + '</h3>');
		jQuery('label[for=tag-name]').replaceWith('<label for="tag-name">Name</label>');
		jQuery('label[for=tag-slug]').replaceWith('<label for="tag-slug">Slug</label>');
		jQuery('label[for=slug]').replaceWith('<label for="tag-slug">Slug</label>');
		jQuery('#submit').val('Add ' + singular);
		jQuery('p.search-box input[type=submit]').val('Search ' + title);
	}
});