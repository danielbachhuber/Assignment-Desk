/**
* Wordpress 2.9.2 doesn't give us a way to change the taxonomy labels on edit-tags.php
* This just goes around and replaces them.s
*/
function ad_replace_tags(){
	if(typeof title != 'undefined' && typeof singular != 'undefined'){
		jQuery('div.wrap h2').replaceWith('<h2>' + title + '</h2>');
		jQuery('div.tagcloud h3').remove();
		jQuery('div.form-wrap h3').replaceWith('<h3 class="sg_selected"> + ' + singular.toLowerCase() + '</h3>');
		jQuery('label[for=tag-name]').replaceWith('<label for="tag-name">' + singular + ' name</label>');
		jQuery('label[for=tag-slug]').replaceWith('<label for="tag-slug">' + singular + ' slug</label>');
		jQuery('label[for=slug]').replaceWith('<label for="tag-slug">' + singular + ' slug</label>');
		jQuery('#submit').val('Add ' + singular);
		jQuery('p.search-box input[type=submit]').val('Search ' + title);
	}
}

jQuery(document).ready(function() {
	ad_replace_tags();
});