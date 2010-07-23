<?php //TODO: Need to move the js into a seperate file, since a lot is duplicated ?>
<div id="ef_duedate">
	<label for="ef_duedate_month"><?php _e('Due Date:', 'edit-flow') ?></label>
	<span id="ef_duedate-display"><?php
		if ($duedate != null) {
			echo $duedate_month . ' ' . $duedate_day . ', ' . $duedate_year;
		} else {
			_e('None assigned', 'edit-flow');
		} ?></span>&nbsp;
	<a href="#ef-metadata" onclick="jQuery(this).hide();
		jQuery('#ef_duedate-edit').slideDown(300);
		return false;" id="ef_duedate-edit_button"><?php _e('Edit', 'edit-flow') ?></a>
	<div id="ef_duedate-edit" style="display:none;">
		<select id="ef_duedate_month" name="ef_duedate_month">
			<?php $months = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'June', 'July', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'); ?>
			<?php foreach( $months as $month ) : ?>
				<option <?php if ($duedate_month == $month) echo 'selected="selected"'; ?>><?php echo $month ?></option>
			<?php endforeach; ?>
		</select>
		<input type="text" id="ef_duedate_day" name="ef_duedate_day" value="<?php echo $duedate_day; ?>" size="2" maxlength="2" autocomplete="off" />,
		<input type="text" id="ef_duedate_year" name="ef_duedate_year" value="<?php echo $duedate_year; ?>" size="4" maxlength="4" autocomplete="off" />
		<br />
		<a href="#ef-metadata" class="button" onclick="jQuery('#ef_duedate-edit').slideUp(300);
			var duedate_month = jQuery('#ef_duedate_month').val();
	 		var duedate_day = jQuery('#ef_duedate_day').val();
			var duedate_year = jQuery('#ef_duedate_year').val();
			var duedate = duedate_month + ' ' + duedate_day + ', ' + duedate_year;
			jQuery('#ef_duedate-display').text(duedate).show();
			jQuery('#ef_duedate-edit_button').show();
			return false;"><?php _e('OK', 'edit-flow') ?></a>&nbsp;
		<a href="#ef-metadata" onclick="jQuery('#ef_duedate-edit').slideUp(300);
			var duedate_full = jQuery('#ef_duedate-display').text();
			if (duedate_full != 'None assigned') {
				var month = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'June', 'July', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'];
				var duedate = new Date(duedate_full);
				var duedate_month = duedate.getMonth();
				var duedate_day = duedate.getDate();
				var duedate_year = duedate.getFullYear();
				jQuery('#ef_duedate_month').val(month[duedate_month]);
				jQuery('#ef_duedate_day').val(duedate_day);
				jQuery('#ef_duedate_year').val(duedate_year);
			}
			jQuery('#ef_duedate-edit_button').show();
			return false;"><?php _e('Cancel', 'edit-flow') ?></a>
	</div>
</div>