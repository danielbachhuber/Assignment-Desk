<?php
    global $assignment_desk;

    wp_tiny_mce( false , // true makes the editor "teeny"
	    array( "editor_selector" => "wysiwyg-editor" ) // All textareas with the wysiwyg-editor CLASS will get fancy.
    );
?>

<div id="ad-breadcrumbs">
    <a href="admin.php?page=assignment_desk-contributor&active_display=assignments">Assignments </a> &gt;
    <a href="post.php?action=edit&post=<?php echo $post->ID ?>">
        <?php echo shorten_ellipses($post->post_title, 25) ?></a> &gt
    Contact the Editor
</div>

<div id="ad-left-column">

<h2>Contact the Editor</h2>

<?php include_once($assignment_desk->templates_path . '/inc/messages.php')  ?>

<form method="POST">
    <input type="hidden" name="action" value="contact_editor">
    <input type="hidden" name="post_id" value="<?php echo $post->ID; ?>">

    <label for="ad-subject">Subject:</label>
    <input id="ad-subject" type="text" name="subject" size="75" maxlength="75" value="<?php echo $subject ?>">
    <br><br>

    <label for="ad-body">Your message:</label>
    <textarea id="ad-body" class="wysiwyg-editor" name="body" rows="15" cols="65"><?php echo $body ?></textarea>

    <button value="submit">Send</button>
</div> <!-- end div#ad-left-column -->

<div id="ad-right-column">
    <button value="submit">Send</button>
</div>

</form>