<?php
/*
Add or remove a follow when a user clicks follow on a user profile
Handler file for the follow/unfollow interaction
this file never leaves the server! it updates the DB and sends back an updated UI for the heart and count
*/
//dependencies
require('../CONFIG.php');
require_once('../includes/functions.php');

$logged_in_user = check_login();

//clean all data
$to_user_id = clean_int($_REQUEST['to']);
$from_user_id = $logged_in_user['user_id'];

if( ! $to_user_id ){
    //TODO - test this - it should prevent a $to from being zero
    exit('error');
}

//check to see if this follow already exists
$result = $DB->prepare('SELECT * FROM follows
                        WHERE to_user_id = :to
                        AND from_user_id = :from
                        LIMIT 1');
$result->execute(array(
                'to' => $to_user_id,
                'from' => $from_user_id
                ));
if($result->rowCount()){
    //it already exists. remove it
    $query = 'DELETE FROM follows
                WHERE to_user_id = :to
                AND from_user_id = :from
                LIMIT 1';
}else{
    //add the follow
    $query = 'INSERT INTO follows
                ( to_user_id, from_user_id, date )
                VALUES
                ( :to, :from, now() )';
}

//run the resulting query
$result = $DB->prepare($query);
$result->execute(array(
    'to' => $to_user_id,
    'from' => $from_user_id
    ));
//update the interface
follows_interface( $to_user_id );