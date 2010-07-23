<?php 
wp_enqueue_script('jquery');

global $assignment_desk;
include_once($assignment_desk->templates_path . '/inc/messages.php');
?>

<?php if($active_status == 'Twitter'): ?>
    <script type="text/javascript" src="<?php echo $assignment_desk->url . 'js/pitch-twitter.js'; ?>"></script>
    <div style="display:none" id="twitter-hash"><?php echo $assignment_desk->options['assignment_desk_twitter_hash']; ?></div>
<?php endif; ?>

<script type="text/javascript">
    var PITCH_SUMMARY_TRUNCATE_LENGTH = 100;
    jQuery(document).ready(
        function() { 
            jQuery('.pitch-summary')
                .truncate({max_length: PITCH_SUMMARY_TRUNCATE_LENGTH}); 
        }
    );
</script>

<div class="wrap">

<h2>Pitches - <?php echo $active_status; ?>  (<?php echo $counts[$active_status]; ?>)</h2>

<div style="float:right">
    <form method="GET">
        Search Pitches: 
        <input name="q" type="text" size="25">
        <input name="page" value="assignment_desk-pitch" type="hidden">
        <input name="action" value="search" type="hidden">
        <button type="submit" class="button-secondary">Go</button>
    </form>
</div>
<br>

<ul class="subsubsub">
|
<?php foreach($display_statuses as $status): ?>
    <li>
        <a class="<?php if ($active_status == $status) echo 'current'; ?>"
            href="?page=assignment_desk-pitch&active_status=<?php echo $status; ?>">
            <?php echo $status; ?> 
            <?php if($status != 'Twitter'): ?>
                (<?php echo $counts[$status]; ?>)
            <?php endif; ?>
            </a> | 
    </li>
<?php endforeach; ?>
</ul>

<br>

<div class="ad-table-tools">

<?php if ($active_status == 'Trash'): ?>
    <form method="POST" style="display:inline">
        <input type="hidden" name="page" value="assignment_desk-pitch">
        <input type="hidden" name="action" value="empty_trash">
        <button class ="button-secondary" type="submit" value="EmptyTrash">Empty Trash</button>
    </form>
<?php endif; ?>

<form method="GET" style="display:inline">
    <input type="hidden" name="page" value="assignment_desk-pitch">
    <input type="hidden" name="active_status" value="<?php echo $active_status; ?>">
    
    <input type="hidden" name="sort_dir" value="<?php echo "ASC"; ?>">
    
	<label for="ad-pitch-sort" class="ad-label">Sort by:</label>
	<select name="sort_by" id="ad-pitch-sort_by">
		<option value="headline"        <?php echo ($_GET['sort_by'] == "headline")?'selected':''; ?>>Headline</option>
		<option value="created"         <?php echo ($_GET['sort_by'] == "created")?'selected':''; ?>>Date</option>
		<option value="submitter_login" <?php echo ($_GET['sort_by'] == "submitter_login")?'selected':''; ?>>Submitter</option>				
	</select>

    <button class="button-secondary" type="submit" value="sort">Sort</button>
    
    <label for="id_term" class="ad-label">Category:</label>
    <select name="term_id" id="id_term">
    	<option value="">--- Clear ---</option>
    	<?php foreach($categories as $category): ?>
    		<option value="<?php echo $category->term_id; ?>" <?php echo ($_GET['term_id'] == $category->term_id)?'selected':''; ?>> <?php echo $category->name; ?></option>
    	<?php endforeach; ?>
    </select>
    
    <button class="button-secondary" type="submit" value="filter">Filter</button>
</form>

<?php if($last_page > 1): ?>
    <div style="float:right"> Page:  
        <?php for($i=0; $i < $last_page; $i++ ): ?>
            <a href="?page=assignment_desk-pitch&active_status=<?php echo $active_status; ?>&start=<?php echo $i * 10; ?> " > 
                <?php echo $i + 1; ?></a> |
        <?php endfor; ?>
        Showing 10 results per page.
    </div>
<?php endif; ?>
</div>

<br>

<table class="widefat post">
    <thead>
    	<tr>
    		<th id="headline" class="manage-column column-title" >Headline</th>
    		<th id="summary" class="manage-column column-title" width="40%">Summary</th>
    		<?php if($active_status == "New"): ?>
			    <th id="volunteers" class="manage-column column-title">Volunteers</th>
			<?php endif; ?>
			<?php if($active_status == "Assigned"): ?>
			    <th id="author" class="manage-column column-title">Author</th>
			<?php endif; ?>
    		<th id="category" class="manage-column column-title">Category</th>
    		<th id="date" class="manage-column column-title">Date</th>
    		<th id="actions" class="manage-column column-title" width="15%">Actions</th>
    	</tr>
    </thead>
    <tbody id="pitch-tbody">
    
    <?php $style_index = 0; ?>
    <?php if (!$counts[$active_status] && $active_status != 'Twitter') : ?>
        <tr><td colspan="5"><h3> No pitches found. </h3></td></tr>
    <?php endif; ?>
    <?php if($active_status == 'Twitter'): ?>
        <tr><td colspan="5"><img src="<?php echo $assignment_desk->url . '/img/spin.gif'; ?>">
        <h3>Loading tweets for <?php echo $assignment_desk->options['assignment_desk_twitter_hash']; ?></h3>
        </td></tr>
    <?php endif; ?>
    
    <?php foreach($pitches as $pitch): ?>
    <tr class="<?php  if ($style_index % 2) echo 'alternate'; ?>">
        <td id="headline">    
		    <a href="admin.php?page=assignment_desk-pitch&action=detail&pitch_id=<?php echo $pitch->pitch_id; ?>">
		    <?php echo shorten_ellipses(stripslashes($pitch->headline), 50); ?></a> 
        </td>
        
        <td>
		    <a href="admin.php?page=assignment_desk-pitch&action=detail&pitch_id=<?php echo $pitch->pitch_id; ?>">
		        <div class="pitch-summary"><?php echo stripslashes($pitch->summary); ?></div>
		    </a>
        </td>

        <?php if($active_status == "New"): ?>
    		<td style="text-align: center">
    			<?php echo count_pitch_volunteers($pitch->pitch_id); ?>
    		</td>
    	<?php endif; ?>
    	
    	<?php if($active_status == "Assigned"): ?>
    		<td style="text-align: center">
    			<?php foreach(get_post_meta($pitch->post_id, 'ad_waiting_for_reply') as $user_id){
    			        $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users WHERE ID=%d", $user_id));
    			        echo 'hi' . $user->user_login . ', ';
    			    }
    			?>
    			<?php foreach(get_post_meta($pitch->post_id, '_coauthor') as $user_id){
    			        $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users WHERE ID=%d", $user_id));
    			        echo $user->user_login;
    			    }
    			?>
    		</td>
    	<?php endif; ?>
        
        <td id="category">	
		    <?php display_pitch_categories($pitch); ?>
        </td>

        <td id="date" width="15%">		
		    <?php echo human_time_diff(strtotime($pitch->created)); ?> ago
        </td>
        <td id="button">
        	<form method="POST">
        		<input type="hidden" name="pitch_id" value="<?php echo $pitch->pitch_id; ?>">

            <?php if($pitch->pitchstatus_id == $statuses['New']): ?>

        		<button class ="button-secondary" type="submit" name="pitchstatus_id" value="Approved">Approve</button>
        		<button class ="button-secondary" type="submit" name="pitchstatus_id" value="Rejected">Reject</button>
        		<button class ="button-secondary" type="submit" name="pitchstatus_id" value="Trash">Delete</button>

            <?php elseif($pitch->pitchstatus_id == $statuses['Approved']): ?>        

        		<button class ="button-secondary" type="submit" name="pitchstatus_id" value="Rejected">Reject</button>
        		<button class ="button-secondary" type="submit" name="pitchstatus_id" value="On Hold">Hold</button>
        		<button class ="button-secondary" type="submit" name="pitchstatus_id" value="Trash">Delete</button>

            <?php elseif($pitch->pitchstatus_id == $statuses['Rejected']): ?>   

        		<button class ="button-secondary" type="submit" name="pitchstatus_id" value="Approved">Approve</button>
        		<button class ="button-secondary" type="submit" name="pitchstatus_id" value="On Hold">Hold</button>

            <?php elseif($pitch->pitchstatus_id == $statuses['On Hold']): ?>

        		<button class ="button-secondary" type="submit" name="pitchstatus_id" value="Approved">Approve</button>		
        		<button class ="button-secondary" type="submit" name="pitchstatus_id" value="Rejected">Reject</button>

            <?php endif; ?>
        	</form>
        </td>        
    </tr>
    
    <?php
        $style_index++;
        endforeach; 
    ?>
    </tbody>
<!-- Table footer -->

	<tfoot>
        <tr>
			<th id="headline">Headline</th>
			<th id="summary">Summary</th>
			<?php if($active_status == "New"): ?>
			    <th id="volunteers">Volunteers</th>
			<?php endif; ?>
			<?php if($active_status == "Assigned"): ?>
			    <th id="author" class="manage-column column-title">Author</th>
			<?php endif; ?>
			<th id="category">Category</th>
			<th id="Date">Date</th>
			<th>Actions</th>
		</tr>
	</tfoot>
	
</table>

</div> <!-- end wrap -->