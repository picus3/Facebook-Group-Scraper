<?php
require_once('connect.inc.php');
require_once('FBLogin.php');
require_once('variables.php');
error_reporting(E_ERROR);
use Facebook\FacebookSession;
use Facebook\GraphUser;
use Facebook\FacebookRequestException;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;



/*
	Checks if Post is in Database 
	Returns Boolean
	Dies if Querey Fails in Excuting
*/
function is_post($post_id) {
$query = "SELECT id FROM posts WHERE id ='$post_id'";
	if ($query_run = mysql_query($query)) {
		if (mysql_num_rows($query_run) == 0) {
			return false;
		}
		else {
			return true;
		}
	}
	else {
		echo mysql_error();
		die('<hr>Error in Checkig if Post exsists');
	}
}

/*
	Checks if Comment is in Database 
	Returns Boolean
	Dies if Querey Fails in Excuting
*/
function is_comment($comment_id) {
$query = "SELECT id FROM comments WHERE id ='$comment_id'";
	if ($query_run = mysql_query($query)) {
		if (mysql_num_rows($query_run) == 0) {
			return false;
		}
		else {
			return true;
		}
	}
	else {
		echo mysql_error();
		die('<hr>Error in Checkig if Post exsists');
	}
}


/*
	Gets The Proper Querey ADD a post to Database
	Returns String
*/
function GetQueryToAddPost($post_object, $session) {
	$id = mysql_real_escape_string($post_object->getProperty('id'));
	$type = $post_object->getProperty('type');
	$created_time = convert_time($post_object->getProperty('created_time'));
	$updated_time = convert_time($post_object->getProperty('updated_time'));
	$poster = $post_object->getProperty('from');
		$poster = $poster->getProperty('name');	
	$message = mysql_real_escape_string($post_object->getProperty('message'));
	$description = mysql_real_escape_string($post_object->getProperty('description'));
	$link = mysql_real_escape_string($post_object->getProperty('link'));
	$picture = mysql_real_escape_string($post_object->getProperty('picture'));
	$source = mysql_real_escape_string($post_object->getProperty('source'));
	$total_likes = GetTotalLikes($id, $session); 	//Gets Total Number of Likes
	$name = mysql_real_escape_string($post_object->getProperty('name'));
	$query_string = "INSERT INTO posts VALUES('$id','$type','$created_time','$updated_time','$poster','$message','$description','$link','$picture','$source','$total_likes', '$name')";
	return $query_string;
}

/*
	Gets Array with All Comments 
	Returns Array of comment GraphObjects
*/
function GetCommentsArrayFromPost($post_id, $session) {
	$response_comments = (new FacebookRequest( $session,
 		 	'GET',
  			'/'.$post_id.'/comments',
  			array (
 				'summary' => true,
   				'filter' => 'toplevel',
			 )
			))->execute();
	$object_comments = $response_comments->getGraphObject();
	$object_comments_array = $object_comments->getPropertyAsArray('data');
	return $object_comments_array;
}

/*
	Gets Query to ADD comment to comment table 
	Returns string
*/
function GetQueryToAddCommnet($comment_object, $post_id) {
	$id = mysql_real_escape_string($comment_object->getProperty('id'));
	$created_time = convert_time($comment_object->getProperty('created_time'));
	$poster = $comment_object->getProperty('from');
		$poster = $poster->getProperty('name');	
	$message = mysql_real_escape_string($comment_object->getProperty('message'));
	$parent_post_id = $post_id;
	$like_count = $comment_object->getProperty('like_count');
	$query_string = "INSERT INTO comments VALUES('$id','$message','$created_time','$poster','$like_count','$parent_post_id')";
	return $query_string;
}

/*
	Gets Total Number of Likes for Post or Comment
	Returns Number of likes
*/
function GetTotalLikes($post_id, $sesh) {
	$response_likes = (new FacebookRequest( $sesh,'GET','/'.$post_id.'/likes', array('summary'=>true)))->execute();
	$object_likes = $response_likes->getGraphObject();
	$total_likes = $object_likes->getProperty('summary');
	$total_likes = $total_likes->getProperty('total_count');
	return $total_likes;
}

/*
	Runs A querey 
	Returns ONE Result
	Dies if it Fails
*/
function ExcuteQuery ($query_to_be_excuted) {
	if ($query_run = mysql_query($query_to_be_excuted)) {
		return @mysql_result($query_run, 0);
	}
	else {
		echo mysql_error();
		die('<hr> Querey Failed'.$query_to_be_excuted);
	}
}

/*
	converts a time from ISO 888- to a timestamp 
	Returns timestamp
*/
function convert_time($time){
	$timestamp = strtotime($time);
	return $timestamp;
}

/*
	Checks if Post needs updating in Database
	Returns Boolean
*/
function is_post_uptodate ($post_id, $time) {
	$query = "SELECT updated_time FROM posts WHERE id='$post_id'";
	$last_update_time = ExcuteQuery($query);
	if ($last_update_time == $time) {
		return true;
	}
	else {
		return false;	
	}
}

/*
	Perferms the Necessary Features to update post 
	Return NULL
*/
function updateInfo ($post_id, $session) {
	//Update Number of Likes 
	$total_likes = GetTotalLikes($post_id, $session);
	$query= "UPDATE posts SET total_likes=$total_likes WHERE id='$post_id'";
	ExcuteQuery($query);
	//Updates Comments
	$comments_array = GetCommentsArrayFromPost($post_id, $session);
	foreach ($comments_array as $comment) { 									//Adds each Comment to Array
		$comment_id = $comment->getProperty('id');
		if(!is_comment($comment_id)) {
			$query = GetQueryToAddCommnet($comment, $post_id); 			//Returns the query to add comment
			ExcuteQuery($query); 										//Inserts Comments to Comment Database
		}
	}

}

/*
	Gets Graph Obejct from a Command
	Returns GraphObject of a FEED
*/

function getGraphObject($next_call, $sesh) {
	$response = (new FacebookRequest($sesh, 'GET', $next_call))->execute();
	return $response->getGraphObject();
}

///////////////////////////////////////////////
////MAIN: LOADS GROUP FEED INTO SQL DATABASE///
///////////////////////////////////////////////

///////////////////////////////////////////
//FIRST TRY USE COUNTER ONLY DO ONE POST///
///////////////////////////////////////////


///////////////////////////////////////////
///////////Tables in Databse///////////////
///posts(Table)
/*
id
type
created_time
updated_time
poster
message
description
link
picture
source
total_likes
*/
///comments(Table)
/*
id
message
created_time
poster
like_count
parent_post_id
*/
///////////////////////////////////////////

$next = '/'.$groupid.'/feed';
$count = 0;

while($next != NULL)   { 														// Use Pagiation to get to end of group,
	$object = getGraphObject($next, $session);								//Gets Object from Next Page //'Start' will give First Page					
	$post_array = $object->getPropertyAsArray('data');						//Gets an Array with all the posts


	foreach ($post_array as $post) { 										//Looks at each Post in the Array
		ob_flush();
		echo '<hr>New Post:';
		$id = $post->getProperty('id'); echo $id;							//Gets Post ID
		if (!(is_post($id))) { 												//Checks if it is already in Database //If Post Does not Exit Add Contents to Thing
			$query = GetQueryToAddPost($post, $session); 					//Returns the query to add post
			ExcuteQuery($query); 											//Inserts the Post to Post Database
			$comments_array = GetCommentsArrayFromPost($id, $session); 		//Looks at Comments
			foreach ($comments_array as $comment) { 						//Adds each Comment to Array
				$query = GetQueryToAddCommnet($comment, $id); 				//Returns the query to add comment
				ExcuteQuery($query); 										//Inserts Comments to Comment Database
			}
		}	
		else {
			echo 'Already Stored post';
			$update_time = convert_time($post->getProperty('updated_time')); //Gets Times it Was Last Updated
			if (!is_post_uptodate($id, $update_time)) {						 //Checks if its up to date
				updateInfo($id, $session);									 //Updates Info in Post Table, and Comment Table
				echo ': POST UPDATE';
			}
			else {
				echo ': POST UPTODATE';
			}
		}
	}	
	$paging = $object->getProperty('paging'); 								//Gets the next Page to load
	if ($paging != NULL) { 
		$next_url = $paging->getProperty('next');
		if ($next != NULL) {	
			$next = substr($next_url, 31);											//Will be Null if there is no more
			$time = substr($next_url, 304, 10);
		}
		echo '<hr><strong>'.date('m/d/Y', $time),'</strong><hr>';
	}
	else {
		$next = $paging;
	}
}

echo '<hr><hr><hr><strong>Succesfully Finished</strong>';
?>


