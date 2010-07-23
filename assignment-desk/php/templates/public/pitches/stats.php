<?php
/**
Template Name: Pitch-statistics
*/
get_header();
?>
<?php
// Define global Variables
global $posts;
?>

<div id = "content" class ="narrowcolumn" role ="main" >   

<p>Post Filter </p>
<select name = "post-filter" >
	<option value = "ALL" > ALL </option>
	<option value = "NYU" > NYU </option>
	<option value ="Community"> Community </option>
</select>
<h2>Below are the pitches that have been published </h2>
 <?php
// Get all the posts of type of Post
 $publishedposts = get_posts('post_type=post&post_status=publish&orderby=title&order=ASC');
 foreach($publishedposts as $post):
    setup_postdata($post);
 ?>
 <h2><a href="<?php the_permalink(); ?>" id="post-<?php the_ID(); ?>"><?php the_title(); ?></a></h2>
 Submitted by
<?php $i = new CoAuthorsIterator();
print $i->count() == 1 ? 'Author: ' : 'Authors: ';
$i->iterate();
the_author();
while($i->iterate()){
    print $i->is_last() ? ' and ' : ', ';
}
     ?>
 
on <?php the_date(); ?>
 <?php the_content(); ?>

 <?php endforeach; ?>
</div>


<?php get_footer();
get_sidebar();
 ?>