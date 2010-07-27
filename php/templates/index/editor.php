<div id="ad-left-column" class="wrap">

<h2>Activity Feed</h2>

<div id="ad-editor-activity-feed">

<?php if(!$event_count): ?>
    <h3> No activity at this time. </h3>
<?php endif; ?>

<div id="ad-event-filters">
    <form method="GET">
        <input type="hidden" name="page" value="assignment_desk-index">

        <input type="checkbox" name="event-filter[]" id="event-filter-pitch" value="pitch" onclick="javascript:this.form.submit();"
            <?php if (in_array('pitch', $show_types)) echo 'checked';?>> Pitches |
        <input type="checkbox" name="event-filter[]" id="event-filter-draft" value="draft" onclick="javascript:this.form.submit();"
            <?php if (in_array('draft', $show_types)) echo 'checked';?>> Drafts  | 
        <input type="checkbox" name="event-filter[]" id="event-filter-assignment" value="assignment" onclick="javascript:this.form.submit();"
            <?php if (in_array('assignment', $show_types)) echo 'checked';?>> Assignments
        <button onclick="reload_with_filters()">Show</button>
    </form>
</div>

<?php foreach ($events as $event): ?>
    <?php  
    if ($event->target_type == 'pitch') {
        $pitch = get_object_for_event($event);
        if ($pitch != NULL) {
    ?>
        <div class="ad-editor-activity-item">
            <?php echo get_avatar($event->user_login, 48); ?>
            <span class="ad-event-description"> <?php echo $event->description; ?> </span>
            <br>
            <span class="ad-pitch-summary">
                <a href="?page=assignment_desk-pitch&action=detail&pitch_id=<?php echo $pitch->pitch_id; ?>">
                    <?php echo shorten_ellipses($pitch->summary, 150); ?>
                </a>
            </span>
            <span class="ad-event-date">
                <?php echo human_time_diff(strtotime($event->created)); ?> ago
            </span>
        </div>
        <?php
        }
    }
        
    else if ($event->target_type == 'post') {
        $post = get_object_for_event($event);
        if($post != NULL){
    ?>
        <div class="ad-editor-activity-item">
            <span class="ad-event-description"> <?php echo $event->description; ?> </span>
            <br>
            <span class="ad-post-title"> 
                <a href="post.php?action=edit&post=<?php echo $post->ID; ?>"> <?php echo $post->post_title; ?> </a>
            </span>
            <span class="ad-event-date">
                <?php echo human_time_diff(strtotime($event->created)); ?> ago
            </span>
        </div>
            
    <?php    
        }
    }
    ?>
    <br>
<?php endforeach; ?>
</div>


</div> <!-- end div#ad-left-column -->