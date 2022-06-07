<?php
//define all the vars
$errors = array();
$username = '';
$password = '';
$email = '';
$policy = '';
//process the registration if it as submitted
if( isset( $_POST['did_register'] ) ){
    //sanitize everything
    $username = clean_string( $_POST['username'] );
    $password = clean_string( $_POST['password'] );
    $email = filter_var( $_POST['email'], FILTER_SANITIZE_EMAIL );
    //if policy isnt checked, set it to 0
    if( ! isset($_POST['policy']) OR $_POST['policy'] != 1 ){
        $policy = 0;
    }else{
        $policy = 1;
    }
    //validate
    $valid = true;
    //username too short or too long
    if( strlen($username) < USERNAME_MIN OR strlen($username) > USERNAME_MAX ){
        $valid = false;
        $errors['username'] = 'Username must be between ' . USERNAME_MIN . ' and ' . USERNAME_MAX . ' characters';
    }else{
        //username already taken
        $result = $DB->prepare('SELECT username
                                FROM users
                                WHERE username = ?
                                LIMIT 1');
        $result->execute( array( $username ) );
        //if one row found, the username is taken
        if( $result->rowCount() ){
            $valid = false;
            $errors['username'] = 'That username is already taken. Try another.';
        }
    }//end username checks
    
    //invalid email
    if( ! filter_var($email, FILTER_VALIDATE_EMAIL ) ){
        $valid = false;
        $errors['email'] = 'Invalid Email';
    }else{
        //email already registered
        $result = $DB->prepare('SELECT email
                                FROM users
                                WHERE email = ?
                                LIMIT 1');
        $result->execute( array( $email ) );
        if( $result->rowCount() ){
            $valid = false;
            $errors['email'] = 'That email is already registered. Try logging in.';
        }
    }//end email checks

    //password too short
    if( strlen( $password ) < PASSWORD_MIN ){
        $valid = false;
        $errors['password'] = 'Your password is too short. Make one at lease ' . PASSWORD_MIN . ' characters long.';
    }
    //policy unchecked
    if( ! $policy ){
        $valid = false;
        $errors['policy'] = 'You must agree to our Terms of Service to sign up.';
    }
    //if valid, add them to the DB
    if( $valid ){
        $result = $DB->prepare( 'INSERT INTO users
                                ( username, password, email, is_admin, join_date )
                                VALUES
                                ( :username, :hashpass, :email, 0, NOW() )' );
        $hashed_pass = password_hash( $password, PASSWORD_DEFAULT );
        $result->execute( array(
                            'username' => $username,
                            'hashpass' => $hashed_pass,
                            'email' => $email
                         ) );
        //check the query
        if( $result->rowCount() ){
            $feedback = 'Success. You are now a Finsta member.';
            $feedback_class = 'success';
        }else{
            $feedback = 'Error creating account.';
            $feedback_class = 'error';
        }
    }else{
        $feedback = 'There were errros with your registration. Fix the following:';
        $feedback_class = 'error';
    }
    //handle feedback
}//end if did register