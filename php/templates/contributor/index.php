<?php
    // See assignment-desk/php/contributor-controller.php 
    // index()
?>

<div class="wrap">

<?php include_once($assignment_desk->templates_path . '/inc/messages.php'); ?>

<h2>Your Content -
    <?php switch($active_display){
        case 'pitch':               echo 'Pitches'; break;
        case 'pending_assignments': echo 'Pending Asignments'; break;
        case 'assignments':         echo 'Assignments'; break;
      }?>

</h2>

<ul class="subsubsub">
    <li>
        <a href="?page=assignment_desk-contributor&active_display=assignments&user_id=<?php echo $user->ID; ?>" 
            class="<?php if($active_display == 'assignments') echo 'current'; ?>" >
            Current Assignments</a> | </li>
        <a href="?page=assignment_desk-contributor&active_display=pending_assignments&user_id=<?php echo $user->ID; ?>"
            class="<?php if($active_display == 'pending_assignments') echo 'current'; ?>" >
            Awaiting Your Response</a> | </li>
        <a href="?page=assignment_desk-contributor&active_display=pitch&user_id=<?php echo $user->ID; ?>" 
            class="<?php if($active_display == 'pitch') echo 'current'; ?>">
            Your Story Pitches</a></li>
    </li>
</ul>
<?php if ($active_display == 'pitch'): ?>
<table class="widefat post">
    <thead>
    	<tr>
    	    <th id="headline" class="manage-column column-title">Headline</th>
    		<th id="summary"  class="manage-column column-title">Summary</th>
    		<th id="category" class="manage-column column-title">Category</th>
    		<th id="Date"     class="manage-column column-title">Date</th>
    	</tr>
    </thead>
    <tbody>
    
    <?php if(!$pitches_count): ?>
        <tr><td colspan="4"> <h3> We don't have any pitches on record for you. </h3> </td></tr>
    <?php endif; ?>
    
    <?php foreach($pitches as $pitch): ?>
    <tr class="<?php  if ($style_index % 2) echo 'alternate'; ?>">
        <td> <?php echo shorten_ellipses($pitch->headline, 50); ?> </td>
        <td> <?php echo shorten_ellipses($pitch->summary, 150); ?> </td>
        <td> <?php display_pitch_categories($pitch); ?> </td>
        <td width="15%"> <?php echo human_time_diff(strtotime($pitch->created)); ?> ago </td>   
    </tr>
    <?php
        $style_index++;
        endforeach; 
    ?>
    </tbody>
<!-- Table footer -->

	<tfoot>
        <tr><th id="category">Category</th>
			<th id="headline">Headline</th>
			<th id="summary">Summary</th>
			<th id="Date">Date</th></tr>
	</tfoot>
</table>


<?php elseif($active_display == 'assignments'): ?>
<table class="widefat post">
    <thead>
    	<tr>
    	    <th id="headline" class="manage-column column-title">Title</th>
    		<th id="category" class="manage-column column-title">Category</th>
    		<th id="due-date" class="manage-column column-title">Due Date</th>
    	</tr>
    </thead>
    <tbody>
    
    <?php if(!$my_posts_count): ?>
        <tr><td colspan="4"> <h3> We don't have any assignments on record for you. </h3> </td></tr>
    <?php endif; ?>
    
    <?php foreach($my_posts as $post): ?>
    <tr class="<?php  if ($style_index % 2) echo 'alternate'; ?>">
        <td>
            <a href="post.php?action=edit&post=<?php echo $post->ID; ?>">
                <?php echo shorten_ellipses($post->post_title, 100); ?></a> </td>
        <td> 
            <?php 
                $categories = get_the_category($post->ID);
                if($categories){
    	            foreach($categories as $category) { 
                		echo $category->cat_name . ' ';
                	}	
                }
            	else {
            	    echo 'None';
            	}
            ?>
        </td>
        <td width="15%"> <?php echo format_ef_due_date($post->ID); ?></td>   
    </tr>
    <?php
        $style_index++;
        endforeach; 
    ?>
    </tbody>
	<tfoot>
        <tr><th>Title</th>
			<th>Category</th>
			<th>Due Date</th></tr>
	</tfoot>
</table>

<?php elseif($active_display == 'pending_assignments'): ?>
<table class="widefat post">
    <thead>
    	<tr>
    	    <th class="manage-column column-title">Title</th>
    		<th class="manage-column column-title">Category</th>
    		<th class="manage-column column-title">Date</th>
    		<th class="manage-column column-title">Actions</th>
    	</tr>
    </thead>
    <tbody>
    
    <?php if(!$my_posts_pending_count): ?>
        <tr><td colspan="4"> <h3> We don't have any pending assignments on record for you. </h3> </td></tr>
    <?php endif; ?>
    
    <?php foreach($my_posts_pending as $post): ?>
    <tr class="<?php  if ($style_index % 2) echo 'alternate'; ?>">
        <td> <?php echo shorten_ellipses($post->post_title, 50); ?> </td>
        <td> 
            <?php 
                $categories = get_the_category($post->ID);
                if($categories){
    	            foreach($categories as $category) { 
                		echo $category->cat_name . ' ';
                	}	
                }
            	else {
            	    echo 'None';
            	}
            ?>
        </td>
        <td width="15%"> <?php echo human_time_diff(strtotime($post->post_date)); ?> ago </td>   
        <td>
            <form method="GET" action="admin.php">
                <input name="page" type="hidden" value="assignment_desk-contributor">
                <input name="action" type="hidden" value="accept_or_decline">
                <input name="post_id" type="hidden" value="<?php echo $post->ID; ?>">
                <button type="submit" name="response" value="accept"> Accept </button>
                <button type="submit" name="response" value="decline"> Decline </button>
            </form>
        </td>
    </tr>
    <?php
        $style_index++;
        endforeach; 
    ?>
    </tbody>
	<tfoot>
        <tr><th>Title</th>
			<th>Category</th>
			<th>Date</th>
			<th>Actions</th></tr>
	</tfoot>
</table>

<?php endif ?>

Current and past content for <?php echo $user->user_login; ?>

</div> <!-- end wrap -->