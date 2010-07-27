<?php
/*
Template Name: AD Pitch - Submit
*/
?>

<?php
	global $wpdb, $assignment_desk, $current_user, $user_ID; 
	require_once($assignment_desk->this_plugin_path . '/php/event.php');
	
    get_header();
 ?>

<link rel="stylesheet" href="<?php echo $assignment_desk->url ?>css/public.css" />

<script type="text/javascript">
	var charlimit = 240;

	function update_charlimit() {
		var chars = document.getElementById('summary').value.length;
		var remaining_chars = document.getElementById('remaining_chars');
		
		// Change color if over limit.
		if (chars > charlimit) {
			remaining_chars.style.color = "#d00";
		} else {
			remaining_chars.style.color = "#aaa";
		}

		remaining_chars.innerHTML = charlimit - chars;	
		
		return;
	}
	
	function validate_chars() {
		var valid = true;
		var chars = document.getElementById('summary').value.length;
		
		if (chars > charlimit) {
			document.getElementById('charlimit_over').style.display = 'inline';
			valid = false;
		}
		
		return valid;			
	}
	
</script>

<?php 
    get_currentuserinfo();
    
    $category_args = array(
    	  	'type'		=> 'post',
    		'child_of'	=> 0,
    		'orderby'	=> 'name',
    		'order'		=> 'ASC',
    		'hide_empty'=> 0,
    		'hierarchical'=> True  
    );
    $categories = get_categories($category_args);
    
    $messages = array('errors' => array(), 'info' => array(), 'form_errors' => array());
    
    // Get wp user id and status number of 'new posts' (can change)
    $user_email = $current_user->user_email;
    $user_nicename = $current_user->user_nicename;

    if (!empty($_POST)){
        
        $valid_submission = True;
        
        $_POST = array_map( 'stripslashes_deep', $_POST );
        // Shortcut for POST params
    	$p = $_POST;
        
        $user_login = '';
        $user_email = '';
        $user_nicename = '';
        if ($user_ID){
            $user_login = $current_user->user_login;
            $user_email = $current_user->user_email;
            $user_nicename = $current_user->user_nicename;
        }
        else {
            $user_login = $_POST['user_email'];
            $user_email = $_POST['user_email'];
            $user_nicename = $_POST['user_nicename'];
            
            if(!is_email($user_email)){
	            $messages['form_errors']['user_email'] = 'Invalid email.';
	            $valid_submission = False;
	        }
        }
    	$pitch_submitted = False;

        $summary  = $p['who']   . '<br>';
        $summary .= $p['where'] . '<br>';
        $summary .= $p['what']  . '<br>';
        $summary .= $p['why']   . '<br>';
        
        if(strlen($summary) < 20){
            $messages['errors'][] = 'The pitch is too short.';
        }

        if (!count($messages['errors'])){
            
            $new_post = array(
                    'post_title' => $p['headline'],
                    'post_content' => $summary,
                    'post_status' => 'Pitch',
                    'post_date' => date('Y-m-d H:i:s'),
                    'post_author' => $user->ID,
                    'post_type' => 'post',
                    'post_category' => array($p['term_id'])
                );
            $pitch_id = wp_insert_post($new_post);
            
            // Mark this post as pitched by the logged in user.
            update_post_meta($pitch_id, '_ad_pitched_by', $user_login);

        	if ($pitch_id){
        		// Create an event
				create_event('pitch', $pitch_id, 'new', 'A new pitch was submitted', $user_login);
				
				if ($p['volunteer']){
        		    // Add the user to the list of volunteers.
            		update_post_meta($pitch_id, '_ad_volunteer', $user_login);
        		}
        	    
        	    $_POST = array();
            }
            else {
                $messages['errors'][] = 'Your pitch submission did not go through due to some error in our system. Please try again later.';
            }
        }
    }
?>

<div id="content" class="narrowcolumn" role="main">

<?php if($pitch_id): ?>
	<h2>Thank you!</h2>
	<p>Your story idea will be reviewed by our editors.</p>
	
    <?php if ($p['volunteer']): ?>
		<p>We see you have expressed interest in writing this story yourself. Excellent! Our editors will be in contact with you if the story pitch is approved.</p>
    <?php endif; ?>
<?php endif; ?>

<?php include($assignment_desk->templates_path . '/inc/messages.php'); ?>

<h2>Submit a pitch</h2>
<div>
    <a href="/pitches/examples/">Examples of pitches</a> |
    <a href="/pitches/guidelines/">Editorial guidelines</a> |
    <a href="/pitches/howto/">How to write a good pitch.</a>
</div>

<form method="POST" id="pitch-submit" onsubmit="return validate_chars();">
<table class="form-table">
	<tr>
		<td>
		    <label for="category">Category</label>
		    <select name="term_id" id="term_id">
			<option value="space">--- None ---</option>
	        <?php foreach($categories as $category): ?>
	        <option value="<?php echo $category->term_id;?>">
	        	<?php echo $category->name ;?>
	        </option>
	        <?php endforeach; ?>
	    </select>
		</td>
	</tr>	
	<tr>
		<td>
		    <label for="headline">Title</label> <br>
		    <input type="text" name="headline" id="headline" size="45" value="<?php echo $_POST['headline']; ?>"> <br>
		    What would be a good title for this story?
		</td>
	</tr>			
	<tr>
		<td>
			<label for="id_who">Who</label><br>
			<textarea name="who" id="id_who" onkeyup="update_charlimit();" rows="3" cols="44"><?php echo $_POST['who']; ?></textarea><br />
			Who is this story about?
		</td>
	</tr>
	<tr>
		<td>
			<label for="id_what">Where</label><br>
			<textarea name="where" id="id_where" rows="3" cols="44"><?php echo $_POST['where']; ?></textarea><br>
			What part of the East Village did this story occur.
		</td>
	</tr>
	<tr>
		<td>
			<label for="id_what">What</label><br>
			<textarea name="what" id="id_what" rows="3" cols="44"><?php echo $_POST['what']; ?></textarea><br>
			Briefly explain your story idea.
		</td>
	</tr>	
	<tr>
		<td>
			<label for="id_why">Why</label><br>
			<textarea name="why" id="id_why" rows="3" cols="44"><?php echo $_POST['why']; ?></textarea><br>
			Why do you feel so passionately about this story.
		</td>
	</tr>
	<tr>
		<td>
			<input type="checkbox" name="volunteer" id="volunteer" value="true" checked="<?php echo ($p['volunteer'])? 'checked': '';?>">
			<label for="volunteer">I'm interested in writing this story.</label>
		</td>	
	</tr>
	
	<tr> <td><?php echo $messages['form_errors']['user_nicename']; ?></td> </tr>
    <tr>
        <td>
            <label>Full Name</label><br>
            <input name="user_nicename" size="35" type="<?php echo ($user_nicename)? 'hidden': 'text'; ?>" value="<?php echo $user_nicename; ?>">
        </td>
    </tr>
    <tr> <td><?php echo $messages['form_errors']['user_email']; ?></td> </tr>
    <tr>
        <td>
		    <label>E-mail</label><br>
		    <input name="user_email" size="50" type="<?php echo ($user_email)? 'hidden': 'text'; ?>" value="<?php echo $user_email; ?>">
		</td>
	</tr>
	
	<tr>
		<th></th>
		<td> <input type="submit" value="Submit" /> </td>
	</tr>
</table>

</form>

</div>

<?php get_footer(); ?>
