<h2>Assignments</h2>

<?php if(count($active_assignments) > 0): ?>
    <h3>Open Assignments</h3>

    <?php foreach($active_assignments as $post): ?>
		
		<?php 
			foreach((get_the_category($post->ID)) as $category) { 
	    		echo '[' . $category->cat_name . ']';
			}	
			if (!get_the_category($post->ID)) echo '[]';
		?>
        
		<a href="?page=assignment_desk-contributor&action=assignment_edit&post_id=<?php echo $post->ID ?>">
        <?php echo $post->post_title ?></a>
        <br>
    <?php endforeach; ?>
<?php else: ?>
    <h3>You don't have any assignments right now.</h3>
<?php endif; ?>