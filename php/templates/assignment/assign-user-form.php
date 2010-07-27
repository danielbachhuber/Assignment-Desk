<form method="GET">
    
    <input type="hidden" name="page" value="assignment_desk-assignments">
    <input type="hidden" name="action" value="editor_assign">
    <input type="hidden" name="post_id" value="<?php echo $post->ID; ?>">
    
    <h3>Assign to:</h3>
    <div id="user_login_text">
        <input type="text" name="user_login_text" is="id_user_login_text" size="25" id="user-autocomplete">
        <button type="submit" value="Assign">Continue</button>
    </div>

    <div id="user_login_select">
        <h3>Or choose from a list:</h3>
    	<select id="id_user_login_select" name="user_login_select">
    		<option value="">--- Volunteers ---</option>
            <?php user_select_options($volunteers); ?>

            <option value="">--- Admins ---</option> 
            <?php user_select_options($admins); ?>
            
            <option value="">--- Contributors ---</option> 
            <?php user_select_options($contributors); ?>
            
            <option value="">--- Editors ---</option> 
            <?php user_select_options($editors); ?>
            
            <option value="">--- Signed Up ---</option>
            <?php user_select_options($signed_up); ?>
        </select>
    
        <br>
        <button type="submit" value="Assign">Continue</button>
    </div>
        
</form>