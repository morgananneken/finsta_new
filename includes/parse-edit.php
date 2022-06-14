<?php
$errors = array();

//which post are we trying to edit?
//URL will look like edit-post.php?post_id=x
if(  isset($_GET['post_id']) ){
    $post_id = filter_var($_GET['post_id'], FILTER_SANITIZE_NUMBER_INT);
}else{
    exit('No post to edit');
}


//parse the form if they hit submit
if( isset( $_POST['did_edit'] ) ){
    //sanitize
$title = clean_string( $_POST['title'] );
$body = clean_string( $_POST['body'] );
$category_id = clean_int( $_POST['category_id'] );
$allow_comments = clean_boolean( $_POST['allow_comments'] );
$is_published = clean_boolean( $_POST['is_published'] );
    //validate
$valid = true;
    //title blank or longer than 50
    if( '' == $title OR strlen($title) > 50 ){
        $valid = false;
        $errors['title'] = 'Create a title between 1 &ndash; 50 characters long.';
    }
    //body longer than 500
    if( strlen($body) > 500 ){
        $valid = false;
        $errors['body'] = 'Post caption must be shorter than 500 characters long.';
    }
    //category must be positive integer
    if( $category_id < 1 ){
        $valid = false;
        $errors['category_id'] = 'Chose a valid category for your post.';
    }
    //if valid, update the post in the DB
    if($valid){
        $result = $DB->prepare('UPDATE posts
                                SET
                                title        = :title,
                                body         = :body,
                                category_id  = :category_id,
                                allow_comments = :allow_comments,
                                is_published = :is_published

                                WHERE post_id = :post_id
                                AND user_id = :user_id
                                LIMIT 1');
        $result->execute(array(
                            'title' => $title,
                            'body' => $body,
                            'category_id' => $category_id,
                            'allow_comments' => $allow_comments,
                            'is_published' => $is_published,
                            'post_id' => $post_id,
                            'user_id' => $logged_in_user['user_id']
                        ));
        if($result->rowCount()){
            //success
            $feedback = 'Changes successfully saved.';
            $feedback_class = 'success';
        }else{
            //error - no changes made
            $feedback = 'No changes made to this post.';
            $feedback_class = 'info';
        }
    }else{
        $feedback = 'Couldn\'t save your ppost. Please fix the following:';
        $feedback_class = 'error';
    }
    //handle feedback
}//end if did edit


//is the viewer of the page the author of this post? (if so, grab all the info to fill the form)
$result = $DB->prepare('SELECT * FROM posts
                        WHERE post_id = :post_id
                        AND user_id = :user_id
                        LIMIT 1');
$result->execute(array(
            'post_id' => $post_id,
            'user_id' => $logged_in_user['user_id'],
            ));
if( $result->rowCount() ){
    $row = $result->fetch();
    //set up the variables to pre-fill the form
    extract($row);
}else{
    //security! you aren't the author of this post
    exit('You are not allowed to edit this post!');
}
