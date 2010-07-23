<?php
/**
Template Name: Pitch Detail
 */

get_header(); 

global $wpdb, $assignment_desk;

function get_pitch($pitch_id){
    global $wpdb, $assignment_desk;
    return $wpdb->get_row($wpdb->prepare(
                    "SELECT * 
                     FROM {$assignment_desk->tables['pitch']}
                     WHERE pitch_id=%d", $pitch_id));
}

$messages = array(  'errors'      => array(),
	                'form_errors' => array(),
	            );
	            
$user_nicename = "";
$user_email    = "";
$reason        = "";
$pitch_id = 0;
$pitch = 0;

$valid_submission = true;
$successful_volunteer = false;

if(!empty($_GET)){
    $pitch_id = intval($_GET['pitch_id']);
    $pitch = get_pitch($pitch_id);

	// Pull user's info if available
	global $current_user;
	get_currentuserinfo();
}

if(!empty($_POST)){
    $_POST = array_map('stripslashes_deep', $_POST );
    
    $pitch_id = intval($_GET['pitch_id']);
    $pitch = get_pitch($pitch_id);
    
 	$user_nicename = wp_kses($_POST['user_nicename'], $allowedtags);
	$user_email    = wp_kses($_POST['user_email'], $allowedtags);
	$reason        = wp_kses($_POST['reason'], $allowedtags);

	
	if (empty($user_nicename)){
	    $messages['form_errors']['user_nicename'] = 'Full name is required.';
	    $valid_submission = False;
	}
	
	// Email specified?
	if (empty($user_email)){
	    $messages['form_errors']['user_email'] = 'Email is required.';
	    $valid_submission = False;
	}
	else {
	    if(!is_email($user_email)){
	        $messages['form_errors']['user_email'] = 'Invalid email.';
	        $valid_submission = False;
	    }
	}
	
	// Is this a valid user?
	// TODO - Get nytimes.com info
	$user = $wpdb->get_row($wpdb->prepare("SELECT * 
                                            FROM $wpdb->users
                                            WHERE user_login=%s",
                                            $user_login));
	$user_search = $user_email;
	if($user){
	    $user_search = $user->user_login;
	}
	
	$exist = $wpdb->get_row($wpdb->prepare("SELECT * 
                                            FROM {$assignment_desk->tables['pitch_volunteer']}
                                            WHERE user_email=%s AND pitch_id=%d", 
                                            $user_search, $pitch->pitch_id));
										
	// Has this person volunteered for the story already?
	if (!empty($exist)){
		$messages['errors'][] = 'You already volunteered to write this story. Thanks!';	
		$valid_submission = False;
	}
	
	if ($valid_submission){
	    $wpdb->insert( $assignment_desk->tables['pitch_volunteer'],
	                array( 'pitch_id' => $pitch->pitch_id,
	                       'user_nicename' => $user_nicename,
	                       'user_email' => $user_email,
							'reason' => $reason
	                    ),
	                array("%d", "%s", "%s", "%s")
	        );
	    if ($wpdb->insert_id >= 0){
	        $successful_volunteer = True;
	    }
	    else {
	        $messages['errors'][] = 'There was an error saving a record to the DB. Please try again later.';
	    }
	}
}
?>

<div id="content" class="narrowcolumn" role="main">    

<?php if ($successful_volunteer): ?>

    <h2>Thanks for signing up!</h2>

<?php else: ?>
	
    <ul>
    <?php foreach($messages['errors'] as $error): ?>
        <li class="ad-error"> <?php echo $error; ?> </li>
    <?php endforeach; ?>
    </ul>
    
    <div>
        <h2>Volunteer to write "<?php echo $pitch->headline; ?>"</h2>
        <p><?php echo $pitch->summary; ?></p>
    </div>
    
    <form method="POST">
        <input name="pitch_id" type="hidden" value="<?php echo $pitch->pitch_id; ?>">
		<p>
			<h3><label for="full_name">Full Name</label><?php echo $messages['form_errors']['user_nicename']; ?></h3>
			<input name="user_nicename" type="text" value="<?php echo $current_user->display_name; ?>">
		</p>
		
		<p>
			<h3><label for="email">E-mail address</label> <?php echo $messages['form_errors']['user_email']; ?></h3>
			<input name="user_email" type="email" value="<?php echo $current_user->user_email; ?>" />
       	</p>

		<p>
       		<h3>Why would you like to write this story?</h3>
       		<textarea name="reason" rows="5" cols="50"><?php echo stripslashes($reason); ?></textarea>
			<input type="submit" name="Volunteer" value="Volunteer"/>	 
		</p>
    </form>
    
<?php endif; ?>

</div>


<?php get_sidebar(); ?>

<?php get_footer(); ?>