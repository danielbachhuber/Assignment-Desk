<?php
    //wp_enqueue_script('jquery');
	global $wpdb, $assignment_desk; 
    get_header();
?>

<link rel="stylesheet" href="<?php echo $assignment_desk->url ?>css/public.css" />

<div id="content" class="narrowcolumn" role="main">
    <h2>Examples of pitches</h2>
    <div>
        <a href="../submit/">Submit a pitch</a> |
        <a href="../guidelines/">Editorial guidelines</a> |
        <a href="../howto/">How to write a good pitch.</a>
    </div>
</div>

<?php get_footer(); ?>