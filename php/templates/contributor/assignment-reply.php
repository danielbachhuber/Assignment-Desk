<?php global $assignment_desk; ?>

<div class="wrap">

<?php include_once($assignment_desk->templates_path . '/inc/messages.php')  ?>

<?php if($is_pending): ?>
    <h2>Thank you for your reply!</h2>

    <ul class="subsubsub">
        <li><a href="?page=assignment_desk-contributor">Your Content</a> |
        <li>
            <a href="post.php?action=edit&post=<?php echo $post->ID; ?>"> 
            Edit </a> | 
        </li>
        <li><a href="?page=assignment_desk-contributor&action=instructions&post_id=<?php echo $post->ID; ?>">
        Story Instructions</a> | </li>
        <li><a href="?page=assignment_desk-contributor&action=related_content&post_id=<?php echo $post->ID; ?>">Related Content</a></li>
    </ul>
    </div>

    <br>
    <br>

    <div id="ad-left-column">

    <?php if ($response == 'accept'): ?>
        Thanks for accepting the story.<br>
    
        <p> <?php echo $post->post_title; ?> </p>
    
        <p> [ Getting started stuff ] </p>
    <?php endif; ?>

    <?php if($response == 'decline'): ?>
        We're sorry you declined. Oh well.
    <?php endif; ?>
<?php endif; ?>

</div>