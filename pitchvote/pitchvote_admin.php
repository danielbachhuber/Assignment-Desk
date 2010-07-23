<?php
/*  Copyright 2008-2009 The Bivings Group (email : pitchvote@bivings.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once( dirname( __FILE__) . '/pitchvote_inc.php' );

if( $_POST[ 'save_options' ] > 0 )
{
	if( $_POST[ 'threshold' ] < 0 )
		$_POST[ 'threshold' ]	= -$_POST[ 'threshold' ];

	foreach( CVGetOptions() as $key => $value )
	{
		if( isset( $_POST[ $key ] ) )
			update_option( 'pitchvote_' . $key, stripslashes( $_POST[ $key ] ) );
	}
}

$pitchvoteOptions = CVGetOptions();

function makeRadioButton($name, $label, $value, $checked)
{
	if( $checked )
		$checked = 'checked';
	else
		$checked = '';
	
	return "<label><input type=\"radio\" name=\"$name\" value=\"$value\" $checked /> $label</label>";
}

?>
	
<div class="wrap">
	<h2>Pitch Vote Admin</h2>

	<form action="options-general.php?page=pitchvote/pitchvote_admin.php" method="post">
		<?php wp_nonce_field( 'update-options' ); ?>
		
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Display Rating?</th>
				<td>
				<?php
					echo makeRadioButton('display_rating', 'Yes', 1, $pitchvoteOptions['display_rating'] );
					echo ' &nbsp; ';
					echo makeRadioButton('display_rating', 'No', 0, !$pitchvoteOptions['display_rating'] );
				?>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row">Require Login?</th>
				<td>
				<?php
					echo makeRadioButton('requier_login', 'Yes', 1, $pitchvoteOptions['require_login'] );
					echo ' &nbsp; ';
					echo makeRadioButton('requier_login', 'No', 0, !$pitchvoteOptions['require_login'] );
				?>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row">Threshold</th>
				<td><input type="text" name="threshold" value="<?php echo round( $pitchvoteOptions[ "threshold" ] ); ?>" size="3" /> <i>Negative rating at which comment will collapse. 0 = do not collapse</i></td>
			</tr>

		</table>

		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="save_options" value="1" />

		<p class="submit">
			<input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />
		</p>
	</form>
</div>
