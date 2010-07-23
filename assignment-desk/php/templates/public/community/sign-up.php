<?php

/**
Community members who want to express interest in writing for the blog can leave their information.
We store the record in the pitch_volunteer table with a NULL pitch_id. 
*/

get_header();

global $wpdb, $assignment_desk;

$messages = array(  'errors'      => array(), // Gernal messages.
	                'form_errors' => array(), // Field-specific errors.
	            );
	            
$user_nicename = "";
$user_email    = "";
$reason        = "";
$tos           = "";
$valid_submission = True;
$successful_signup = False;

if (!empty($_POST)) {

	// Get all the field values
 	$user_nicename = wp_kses($_POST['user_nicename'], $allowedtags);
	$user_email    = wp_kses($_POST['user_email'], $allowedtags);
	$reason        = wp_kses($_POST['reason'], $allowedtags);
 	$tos           = wp_kses($_POST['tos'], $allowedtags);
 	
	if (empty($user_nicename)){
	    $messages['form_errors']['user_nicename'] = 'Full name is required.';
	    $valid_submission = False;
	}
	
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
	if (empty($tos)){
	    $messages['form_errors']['tos'] = 'Please agree to the Terms of Service.';
	    $valid_submission = False;
	}
	$exist = $wpdb->get_row($wpdb->prepare("SELECT * 
                                            FROM {$assignment_desk->tables['pitch_volunteer']}
                                            WHERE user_login=%s", $user_email));
	if (!empty($exist)){
		$messages['errors'][] = 'You are already signed up!';	
		$valid_submission = False;
	}
	
	// If it passes all the validations , add the user to volunteer table
	if ($valid_submission){
		$wpdb->insert($assignment_desk->tables['pitch_volunteer'], 
					    array('user_login' => $user_email,
						      'user_nicename' => $user_nicename,
						      'user_email' => $user_email,
						      'reason' => $reason,
						      ),
						array('%s', '%s', '%s', '%s'));
			
		echo 'inserted ' . $wpdb->insert_id . '_id';
        if ($wpdb->insert_id > 0){
            $successful_signup = True;
            echo 'successful_signup';
        }
        else {
            $messages['errors'][] = 'There was an error adding you to the database';
            echo 'No!';
        }
	}
}
?>

<div id="content" class="narrowcolumn" role="main">

<?php if ($successful_signup): ?>
    Thanks for signing up.
<?php else: ?>
    <ul>
    <?php foreach($messages['errors'] as $error): ?>
        <li class="ad-error"> <?php echo $error; ?> </li>
    <?php endforeach; ?>
    </ul>
    
    <h2>Register for TheLocal</h2>

    <form method="POST" id="register_user">
        <table name="register_user">
            <tr> <td colspan="2"><?php echo $messages['form_errors']['user_nicename']; ?></td> </tr>
        	<tr>
        		<th scope="row"><label for="full_name">Full Name</label></th>
        		<td ><input name="user_nicename" type="text" value="<?php echo $user_nicename; ?>"></td>
        	</tr>
        	<tr> <td colspan="2"><?php echo $messages['form_errors']['user_email']; ?></td> </tr>
        	<tr>
        		<th scope="row"><label for="email">E-mail</label></th>
        		<td><input name="user_email" type="email" value="<?php echo $user_email; ?>" /></td>
        	</tr>
        	<tr>
        		<td colspan="2"><br>Why Would you like to write?</td>
        	</tr>
        	<tr>	
        		<td colspan="2"><textarea name="reason" rows="5" cols="37"><?php echo $reason; ?></textarea></td>
        	</tr>
        	<tr> <td colspan="2"><?php echo $messages['form_errors']['tos']; ?></td> </tr>
        	<tr>
        		<th> <a href="/community/tos/">Terms of Service </a></th>
        		<td> <input type="checkbox" name="tos" value="terms_services" <?php echo ($tos)? "checked": ""; ?>> Accept</td>
        	</tr>
        	<tr>
        		<td> <input type ="submit" name ="Register" value="Register"/> </td>
        	</tr>	 
        </table>
    </form>
<?php endif; ?>
</div>
<?php get_footer(); ?>