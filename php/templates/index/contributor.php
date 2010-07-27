<div id="ad-left-column" class="wrap">

<h2>Activity Feed</h2>

<?php if(!count($events)): ?>
    <h3>No activity at this time.</h3>
<?php endif; ?>

<?php foreach ($events as $event): ?>
    <div class="ad-editor-activity-item">
        <?php  
        echo get_avatar($event->user_login, 48);
        
        if ($event->target_type == 'post' || $event->target_type == 'assignment'):
            $assignment = get_object_for_event($event);
        ?>
            <span class="ad-event-description"> <?php echo $event->description; ?> </span>
            <br>
            <span class="ad-post-title"> 
                <a href="admin.php?page=assignment_desk-contributor&action=assignment_edit&post_id=<?php echo $assignment->ID; ?>"> <?php echo $assignment->post_title; ?> </a>
            </span>
        <?php endif; ?>
        
        <span class="ad-event-date">
             <?php echo human_time_diff(strtotime($event->created)); ?> ago
        </span>
    </div>
    <br>
<?php endforeach; ?>
</div>