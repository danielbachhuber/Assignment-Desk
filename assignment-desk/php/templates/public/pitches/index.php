<?php
/*
Template Name: AD Pitch - Index
*/
?>

<?php // wp_enqueue_script('jquery'); ?>

<?php global $wpdb, $assignment_desk, $current_user; ?>

<?php get_header(); ?> 

<link rel="stylesheet" href="<?php echo $assignment_desk->url ?>css/public.css" />

<script type="text/javascript">
	// Increments/decrements remaining characters
	function charLimit() {
		var limit = 240;
		var chars = document.getElementById('summary').value.length;
		var label = document.getElementById('remaining_chars');
	
		// Change color if over limit
		if (chars > limit) {
			label.style.color = "#d00";
		} else {
			label.style.color = "#aaa";
		}

		label.innerHTML = limit - chars;	
	
		return;
	}
	
	// Enables form submission with text links
	function vote(choice, pid) {
		var form = document.getElementById('vote_form' + pid);
		
		form.vote_choice.value = choice;
		form.submit();
	}
</script>

<div id="content" style="margin: 5px 0; padding: 10px 22px; width:720px">

<?php //include( TEMPLATEPATH . '/js-markers.php' ); ?>

<?php if ($_POST['vote_choice']): ?>
	<p>Thanks for your vote. Your contribution matters.</p>
	
	<?php
		$v = $_POST;
		
		$vote_sql = $wpdb->prepare("INSERT INTO {$assignment_desk->tables['pitch_votes']} 
										(pitch_id, user_id, vote, updated)
									VALUES (%s, %s, %d, now())
									ON DUPLICATE KEY UPDATE vote=%d, updated=now()", 
										$v['pitch_id'], $current_user->ID, $v['vote_choice'], $v['vote_choice']);
										
		$wpdb->query($vote_sql);
	
	?>	
<?php endif; ?>	

<?php if (empty($_GET)): ?>

<div id="pitches" style="width: 450px; float: left">
	
	<h2>Public Story Pitches</h2>

	<?php
	 	// Store table names for convenience / readability
		$pitch_table = $assignment_desk->tables['pitch'];
		$status_table = $assignment_desk->tables['pitchstatus'];
	
		// Get list of pitches
		$pitches = $wpdb->get_results("SELECT pitch_id, headline, summary, created
									   FROM $pitch_table, $status_table
									   WHERE $pitch_table.pitchstatus_id = " . P_APPROVED . "
									   AND $pitch_table.pitchstatus_id = $status_table.pitchstatus_id");									
	?>

	
	<p>There are currently <strong><?php echo $wpdb->num_rows; ?></strong> open pitches.</p>
	
	<?php foreach ($pitches as $pitch): ?>
		<div class="pitch-search-result" id="<?php echo $pitch->pitch_id; ?>" style="border: solid 1px #ddd; margin: 10px; padding:10px; -moz-border-radius:8px">
			
			<h3 class="pitch-search-heading" style="margin-top:0">
			<a><?php echo $pitch->headline; ?></a>

			<?php
				$pid = $pitch->pitch_id;
				$vote_ups = $wpdb->get_var("SELECT COUNT(*) FROM {$assignment_desk->tables['pitch_votes']}
									WHERE pitch_id=$pid AND vote=1");

				$vote_downs = $wpdb->get_var("SELECT COUNT(*) FROM {$assignment_desk->tables['pitch_votes']}
									WHERE pitch_id='$pid' AND vote='-1'");
			?>
			
			<form name="vote_form_<?php echo $pid ?>" id="vote_form<?php echo $pid ?>" action="#" method="POST">
				<span style="float: right; font-size: 12px;"><a href="#" onclick="vote(-1, <?php echo $pid ?>)">&#x25BC; <?php echo $vote_downs ?></a> <a href="#" onclick="vote(1, <?php echo $pid ?>)">&#x25B2; <?php echo $vote_ups ?></a></span>
				<input type="hidden" name="pitch_id" value="<?php echo $pid; ?>" />
				<input type="hidden" id="vote_choice" name="vote_choice" value="" />
			</form>
			
			</h3>
			<span style="height:100px;"><?php echo shorten_ellipses($pitch->summary, 300); ?></span>

			<div style="padding-top: 12px; padding-left: 5px; font-size:11px;">
				<a class="adsk-button" href="detail/?pitch_id=<?php echo $pitch->pitch_id; ?>">Volunteer!</a>
				<span style="float:right;">
					by <a href=""><?php echo $current_user->display_name; ?></a> on <?php echo date('F jS', strtotime($pitch->created)); ?>
				</span>
			</div>

		</div>				
	<?php endforeach; ?>
	
</div>

<div id="side-nav" style="float: right; width: 240px; margin-top: 70px">
	<h3>Get Involved!</h3>
	<p style="margin: 0">Have an idea for a story? <a href="submit">Give us your suggestion!</a></p> 
	
	<h3>Show Only...</h3>
	<form>
		<input type="checkbox" /> This kind of pitch<br />
		<input type="checkbox" /> Another kind of story<br />
		<input type="checkbox" /> Some other kind<br />
	</form>
	
</div>

<?php else: ?>
	
	<h2>Woops, something went wrong!</h2>

<?php endif; ?>	

</div>
	
<?php get_footer(); ?>