<!-- Generic message display -->
<ul>

<?php if(array_key_exists('errors', $messages)): ?>
    <?php foreach($messages['errors'] as $error): ?>
        <li class="error"><?php echo $error; ?></li>
    <?php endforeach; ?>
<?php endif; ?>

<?php if(array_key_exists('info', $messages)): ?>
    <?php foreach($messages['info'] as $info): ?>
        <li class="updated"><?php echo $info; ?></li>
    <?php endforeach; ?>
<?php endif; ?>

<?php if(array_key_exists('deleted', $messages)): ?>
    <?php foreach($messages['deleted'] as $delete): ?>
        <li class="updated"><?php echo $delete; ?></li>
    <?php endforeach; ?>
<?php endif; ?>

</ul>