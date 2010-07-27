<?php
    global $assignment_desk;

    wp_tiny_mce( false , // true makes the editor "teeny"
	    array( "editor_selector" => "wysiwyg-editor" ) // All textareas with the wysiwyg-editor CLASS will get fancy.
    );
?>

<div id="ad-left-column" class="wrap">

<?php include_once($assignment_desk->templates_path . '/inc/messages.php')  ?>

<div id="breadcrumbs">
    <a href="admin.php?page=assignment_desk-pitch">Pitches</a> &gt;
    <a href="admin.php?page=assignment_desk-pitch&action=detail&post_id<?php echo $post->ID; ?>">
        <?php echo shorten_ellipses($post->post_title, 25) ?> </a> &gt;
    Assign to <?php echo $user_login; ?>
</div>

<?php if($post): ?>
<div "form-container">

    <form method="POST">
    
        <div style="float:right">
            <?php include(ABSPATH . '/wp-content/plugins/edit-flow/php/templates/duedate_form.php'); ?>
            <script type="text/javascript">
                jQuery('#ef_duedate-edit').slideDown(300);
            </script>
        </div>
        
        <h2>Pitch: <?php echo $post->post_title; ?></h2>
        <h3>Assigning to <?php echo ($user->user_nicename)? $user->user_nicename: $user->user_login; ?></h3>

        <input type="hidden" name="post_id" value="<?php echo $post->ID; ?>">
	
        <?php if($user_login): ?>
            <h3>Email to send to <?php echo $user_login; ?></h3>
        <?php else: ?>
            <h3> No user selected! </h3>
        <?php endif; ?>

        <!-- The page in the admin section -->
        <input type="hidden" name="page" value="assignment_desk-assignments">
        
        <!-- The action for the assignments controller. -->
        <input type="hidden" name="action" value="editor_assign">
                
		<!-- The user assigned to this pitch -->
        <input type="hidden" name="user_login" value="<?php echo $user_login; ?>">
        
        <textarea class="wysiwyg-editor" name="email_body" rows="20" cols="80">
        <?php include($assignment_desk->templates_path .'/assignment/assigned-email-message.php'); ?></textarea>
        
        <br>
        
        <input type="submit" value="Assign" <?php if($disable_form) echo 'disabled';?> >
    </form>
</div>
<?php else: ?>
    <h3> No pitch selected. </h3>
<?php endif; ?>

</div>

<div id="ad-right-column">

    <?php if($assignees): ?>
        <div class="ad-module">
            <h2>Assignees</h2>
            <ul>
            <?php foreach($assignees as $user): ?>
                <li> <a href="?page=assignment_desk-contributor&user_id=<?php echo $user->ID; ?>">
                        <?php echo $user->user_login; ?></a>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php endif ?>
    
    <?php if($pending_reply): ?>
        <div class="ad-module">
            <h2>Waiting for reply</h2>
            <ul>
            <?php foreach($pending_reply as $user): ?>
                <li> <a href="?page=assignment_desk-contributor&user_id=<?php echo $user->ID; ?>">
                        <?php echo $user->user_login; ?></a>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php 
    if($show_user_form){
        include('assign-user-form.php');
    }
    ?>
</div>