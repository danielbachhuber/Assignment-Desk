<?php

function create_event($target_type, $target_id, $event_type, $event_description, $user_login){
    global $wpdb, $assignment_desk;
    
    $data = array(
        "target_type" => $target_type,
        "target_id"   => $target_id,
        "event_type"  => $event_type,
        "description" => $event_description,
        "user_login" => $user_login,
        "created"    => date('Y-m-d H:i:s'),
        );
    return $wpdb->insert($assignment_desk->tables['event'], $data);
}

/**
* Fetch the object in the DB that the $event refers to.
*/
function get_object_for_event($event) {
    global $wpdb, $assignment_desk;
    
    // Figure out which AD table to query
    $table = $assignment_desk->tables[$event->target_type];
    $where = $event->target_type . '_id';
    
    if($event->target_type == "assignment" || $event->target_type == "post"){
        $table = "$wpdb->posts";
        $where = "ID";
    }
    
    $obj = 0;
    $query = "SELECT * FROM $table WHERE $where=%d";
    $obj = $wpdb->get_row($wpdb->prepare($query, $event->target_id));
    
    return $obj;
}
?>