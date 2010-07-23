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

$root	= dirname( __FILE__ ) . '/../../..';

if( file_exists( $root . '/wp-load.php' ) )
{
		// WP 2.6
		require_once( $root . '/wp-load.php' );
} else {
		// Pre 2.6
		require_once( $root . '/wp-config.php' );
}

require_once( dirname( __FILE__) . '/pitchvote_inc.php' );

function CVDoValidateInput($input)
{
	/*
		We have this extra abstraction on the input fields because during testing, we identified
		at least one other plugin that was using the same variable names and which was stealing
		our input.
	*/
	$fields = array(
		'vc_comment' => 'commentID',
		'vc_vote' => 'vote',
	);
	
	$arr = array();
	
	foreach($fields as $inputKey => $newKey)
		if(empty($input[$inputKey]))
			return false;
		else
			$arr[$newKey] = (int)$input[$inputKey];
	
	if( !$arr['commentID'] )
		return false;
	
	if( $arr['vote'] < -1 || $arr['vote'] > 1)
		return fase;
	
	return $arr;
}

function _CVAddVote($commentID, $userID, $vote, $ip, $time)
{
	global $wpdb;
	
	$cvTable = $wpdb->prefix . 'pitchvote';
	
	if( $userID )
		$userQuery = array('userID = %d', $userID);
	else
		$userQuery = array('ip = INET_ATON(%s)', $ip);
	
	$wpdb->query( "LOCK TABLE $cvTable WRITE" );
	
	$alreadyVoted = $wpdb->get_var( $wpdb->prepare("SELECT count(*) FROM $cvTable WHERE commentID = %d  AND $userQuery[0]", $commentID, $userQuery[1]));
	
	if( !$alreadyVoted ) {
		$wpdb->query( $wpdb->prepare("INSERT INTO $cvTable (
			commentID, userID, vote, ip, voteTime
		) VALUES (
			%d, %d, %d, INET_ATON(%s), %d
		)",
		$commentID, $userID, $vote, $ip, $time ));
	}
	
	$wpdb->query( 'UNLOCK TABLES' );
	
	return !$alreadyVoted;
}

function CVDoAddVote($userID, $ip)
{
	$options = CVGetOptions();
	
	$response = array(
		'mesage' => '',
		'votes' => 0,
	);
	
	if( !empty( $_POST ))
		$input = CVDoValidateInput( $_POST );
	else
		$input = false;
	
	if( !$input ) 
		$response['message'] = 'Invalid input.';
	else {
		$commentID = $input['commentID'];
		
		if( $options['require_login'] && !$userID )
			$response['message'] = 'You must login to vote.';
		else {
			$voteRecorded = _CVAddVote( $commentID, $userID, $input['vote'], $ip, time() );
			
			if( !$voteRecorded )
				$response['message'] = 'You already voted.';
			else {
				$response['message'] = 'Vote recorded. Thank you.';
			}
		}
		
		if( $options['display_rating'] )
			$response['votes'] = CVGetCommentVote( $commentID );
	}
	
	//Don't want to require PHP 5.2 quite yet, even though everyone should be using it by now.
	//echo json_encode( $response );
	echo <<< EOT
{"message":"$response[message]","votes":$response[votes]}
EOT;
	exit;
}

CVDoAddVote( $user_ID, $_SERVER['REMOTE_ADDR'] );
				
?>
