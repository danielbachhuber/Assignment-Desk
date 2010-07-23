<?php
    global $assignment_desk;

    wp_tiny_mce( false , // true makes the editor "teeny"
	    array( "editor_selector" => "wysiwyg-editor" ) // All textareas with the wysiwyg-editor CLASS will get fancy.
    );
    
    function user_select_options($users){
        foreach ($users as $user){
	        echo "<option value=\"{$user->user_login}\">{$user->user_login}</option>";
        }
    }
    
?>

<script type="text/javascript">
    // Hide the user select.
    jQuery(document).ready(
        function (){
            jQuery('div#user_login_select').hide();
        }
    );
    // AJAX-enable the user_search
    jQuery(document).ready(
        function() {
            var nonce = "<?php echo wp_create_nonce ('assignment_desk-ajax-nonce'); ?>"
            var admin_ajax_url = "<?php echo admin_url('admin-ajax.php'); ?>";
            jQuery("#user-autocomplete").autocomplete(admin_ajax_url, {
                                                        'extraParams': { 
                                                            'action': 'user_search',
                                                            '_wpnonce': nonce, 
                                                            'delay': 100,
                                                            }
                                                        }
                                                        );
        }
    );
</script>

<div id="ad-left-column" class="wrap">

<?php if(!$pitch): ?>
    <h2>Pitch not found. <a href="?page=assignment_desk-pitch">Go back.</a></h2> 
<?php else: ?>

<?php include_once($assignment_desk->templates_path . '/inc/messages.php')  ?>

<div id="breadcrumbs">
    <a href="?page=assignment_desk-index">Assignment Desk</a> &gt;
    <a href="?page=assignment_desk-pitch">Pitches</a> &gt;
    Detail
</div>

<h2>Pitch Detail</h2>

<form method="POST">    
    <input type="hidden" name="pitch_id" value="<?php echo $pitch->pitch_id; ?>">
    <label for="ad-pitch-submitter_id" class="ad-label">
        Submitted by: <?php echo $submitter->user_login; ?></label>
	<br><br>
    <label for="ad-pitch-pitchstatus_id" class="ad-label">Status:</label>
    <select name="pitchstatus_id" id="ad-pitch-pitchstatus_id">
        <?php foreach($pitch_statuses as $status): ?>
        <option value="<?php echo $status->pitchstatus_id;?>" 
            <?php if($pitch->pitchstatus_id == $status->pitchstatus_id) { echo " selected"; } ?>>
            <?php echo $status->name;?>
        </option>
        <?php endforeach; ?>
    </select>
    <br><br>
	
    <label for="ad-pitch-categories" class="ad-label">Category:</label>
    <select name="term_id" id="ad-pitch-term_id">
		<option value="space">--- None ---</option>
        <?php foreach($categories as $category): ?>
            <option value="<?php echo $category->term_id;?>" 
                <?php if($pitch->term_id == $category->term_id) { echo " selected"; } ?>>
                <?php echo $category->name ;?>
            </option>
        <?php endforeach; ?>
    </select>
	<br><br>
	    
    <label for="ad-pitch-headline" class="ad-label">Headline:</label>
    <input type="text" id="ad-pitch-headline" name="headline" size="50" 
           value="<?php echo $pitch->headline; ?>">
    <br><br>
    
    <label for="ad-pitch-summary" class="ad-label">Summary:</label>
    <textarea class="wysiwyg-editor" id="ad-pitch-summary" name="summary" 
              value="<?php echo $pitch->summary; ?>"> <?php echo $pitch->summary; ?></textarea>
    <br><br>
    
    <label for="ad-pitch-notes" class="ad-label">
        Editor's Notes (Not visible to contributors):</label>
    <br>
    <textarea class="wysiwyg-editor" id="ad-pitch-notes" name="notes" 
              rows="7" cols="60"><?php echo $pitch->notes; ?></textarea>
    <br>
    
    <input type="submit" value="Save">
    
    </form>    
</div>

<div id="ad-right-column">

    <?php if ($pitch->post_id): ?>
    <div class="ad-module">
        <a href="post.php?action=edit&post=<?php echo $pitch->post_id; ?>"> View the post for this pitch. </a>
    </div>
    <?php endif; ?>

    <div class="ad-module">
        <h2>Assignees (<?php echo count($assignees); ?>)</h2>
        <ul>
        <?php foreach($assignees as $user): ?>
            <li> <a href="?page=assignment_desk-contributor&user_id=<?php echo $user->ID; ?>">
                    <?php echo $user->user_login; ?></a>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>
    
    <div class="ad-module">
        <h2>Waiting for reply (<?php echo count($pending_reply); ?>)</h2>
        <ul>
        <?php foreach($pending_reply as $user): ?>
            <li>
               <?php if (!empty($user->user_login)): ?>
                    <a href="?page=assignment_desk-contributor&user_login=<?php echo $user->user_login; ?>">
                        <?php echo $user->user_login; ?></a>
                <?php else: ?>
                    <?php echo $user->user_nicename; ?> 
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>


    <div class="ad-module">
        <h2>Volunteers (<?php echo count($volunteers); ?>)</h2>    
        <ul>
        <?php foreach($volunteers as $user): ?>
            <li>
                <?php if (!empty($user->user_login)): ?>
                    <a href="?page=assignment_desk-contributor&user_login=<?php echo $user->user_login; ?>">
                        <?php echo $user->user_login; ?></a>
                        <form method="GET" style="display:inline">
                            <input type="hidden" name="page" value="assignment_desk-assignments">
                            <input type="hidden" name="action" value="editor_assign">
                            <input type="hidden" name="pitch_id" value="<?php echo $pitch->pitch_id; ?>">
                            <input type="hidden" name="user_login_text" value="<?php echo $user->user_login ?>">
                            <button>Assign</button>
                        </form>
                <?php else: ?>
                    <?php echo $user->user_nicename; ?> 
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>
    
    <?php include($assignment_desk->templates_path . '/assignment/assign-user-form.php'); ?>
    
<?php endif; ?>