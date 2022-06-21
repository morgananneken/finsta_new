<?php 
/**
 * Get a human-friendly version of a datestamp
 * @param  string $date any date string
 * @return string       nice-looking date
 */
function convert_date( $date = 'today' ){
	$output = new DateTime( $date );
	return $output->format( 'F jS' );
}

/**
 * convert a date into the "time ago"
 * @param  string  $datetime 
 * @param  boolean $full     whether to break down the hours, minutes, seconds
 * @link https://stackoverflow.com/questions/1416697/converting-timestamp-to-time-ago-in-php-e-g-1-day-ago-2-days-ago
 */
function time_ago($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}


/**
 * Count approved comments and any 
 */
function count_comments( $id ){
    global $DB;
    $result = $DB->prepare('SELECT COUNT(*) AS total
                    FROM comments
                    WHERE post_id = ?
                    AND is_approved = 1');
    //run it and bind the data to the placeholders
    $result->execute(array($id));
    //check it
    if($result->rowCount()){
        //loop it
        while( $row = $result->fetch() ){
            return $row['total'];
        }
    }
}

/**
 * Display the feedback after a typical form submission
 * @param string $message The feedback message for the user
 * @param string $class The CSS class for the feedback div - use 'error' or 'success'
 * @param array $list   The list of error issues
 * @return mixed HTML output
 */
function show_feedback( &$message, &$class = 'error', $list = array() ){
    if( isset( $message ) ){ ?>
        <div class="feedback <?php echo $class; ?>">
            <h4><?php echo $message; ?></h4>
            <?php 
            if( ! empty($list) ){ 
                echo '<ul>';
                foreach ( $list AS $item ) {
                    echo "<li>$item</li>";
                }
                echo '</ul>';
            }
            ?>
        </div>
    <?php }
}
/**
 * sanitize a string input by removing all tags and trimming leftover white space
 * @param string $dirty with the untrusted data
 * @return string the sanitized string
 */
function clean_string( $dirty ){
    return trim( strip_tags( $dirty ) );
}

function clean_int( $dirty ){
    return filter_var( $dirty, FILTER_SANITIZE_NUMBER_INT );
}

function clean_boolean( $dirty ){
    if($dirty){
        return 1;
    }else{
        return 0;
    }
}

/**
* displays sql query information including the computed parameters.
* Silent unless DEBUG MODE is set to 1 in config.php
* @param [statement handler] $sth -  any PDO statement handler that needs troubleshooting
*/
function debug_statement($sth){
    if( DEBUG_MODE ){
        echo '<pre>';
        $info = debug_backtrace();
        echo '<b>Debugger ran from ' . $info[0]['file'] . ' on line ' . $info[0]['line'] . '</b><br><br>';
        $sth->debugDumpParams();
        echo '</pre>';
    }
}

/**
 * Helper function to make <select> dropdowns sticky
 * @param  mixed $thing1 
 * @param  mixed $thing2 
 * @return string         the 'selected' attribute for HTML
 */
function selected( $thing1, $thing2 ){
    if( $thing1 == $thing2 ){
        echo 'selected';
    }
}

/**
 * Helper function to make input checkboxes "sticky"
 * @param  mixed $thing1 
 * @param  mixed $thing2 
 * @return string         the 'checked' attribute for HTML
 */
function checked( $thing1, $thing2 ){
    if( $thing1 == $thing2 ){
        echo 'checked';
    }
}

/**
 * Output a class on a form input that triggered an error
 * @param  string $field the name of the field we're checking
 * @param  array  $list  the list of all errors on the form
 * @return string        css class 'field-error'
 */
function field_error( $field, $list = array() ){
    if( isset( $list[$field] ) ){
        echo 'field-error';
    }
}

/**
 * check to see if the viewer is logged in
 * @return array|bool false if not logged in, array of all user data if they are logged in
 */

function check_login(){
    global $DB;
    //if the cookie is valid, turn it into session data
    if(isset($_COOKIE['access_token']) AND isset($_COOKIE['user_id'])){
        $_SESSION['access_token'] = $_COOKIE['access_token'];
        $_SESSION['user_id'] = $_COOKIE['user_id'];
    }

   //if the session is valid, check their credentials
   if( isset($_SESSION['access_token']) AND isset($_SESSION['user_id']) ){
        //check to see if these keys match the DB     

       $data = array(
        'access_token' =>$_SESSION['access_token'],
       );

        $result = $DB->prepare(
            "SELECT * FROM users
                WHERE  access_token = :access_token
                LIMIT 1");
        $result->execute( $data );
       
        if($result->rowCount() > 0){
            //token found. confirm the user_id
            $row = $result->fetch();
            if( password_verify( $row['user_id'], $_SESSION['user_id'] ) ){
                //success! return all the info about the logged in user
                return $row;
            }else{
                return false;
            }
          
        }else{
            return false;
        }
    }else{
        //not logged in
        return false;
    }
}

function show_profile_pic($src, $alt = 'Profile Picture', $size = 50){
    if( $src == '' ){
        $src = ROOT_URL . '/images/default_user.png';
    }
    ?>
    <img src="<?php echo $src ?>" alt="<?php echo $alt ?>" width="<?php echo $size ?>" height="<?php echo $size ?>">
    <?php
}



/**
 * Displays HTML dropdown input of all categories in alpha order
 * @return mixed HTML the <select> populated with <options>s
 */
function category_dropdown( $default = 0 ){
    global $DB;
    $result = $DB->prepare('SELECT * FROM categories ORDER BY name ASC');
    $result->execute();
    if( $result->rowCount() ){
        echo '<select name="category_id">';
        echo '<option>Chose a Category</option>';
        while( $row = $result->fetch() ){
            extract($row);
            if( $default == $category_id ){
                $atts = 'selected';
            }else{
                $atts = '';
            }
            echo "<option value='$category_id' $atts>$name</option>";
        }
        echo '</select>';
    }
}


/**
 * display any posts image at any known size
 * @param string $unique the unique string identifier of the image. stored as "image" in the DB
 * @param string $size small, medium(default), large
 * @param string $alt alt text
 * @return mixed html HTML <img> tag
 */
function show_post_image( $unique, $size = 'medium', $alt = '' ){
    $url = "uploads/$unique" . "_" . "$size.jpg";
    echo "<img src='$url' alt='$alt' class='post-image $size'>";
}


function edit_post_button( $post_id = 0, $post_author = 0 ){
    global $logged_in_user;
    //if the logged in person is the author, show an edit button
    if( $logged_in_user AND $logged_in_user['user_id'] == $post_author ){
        echo "<a href='edit-post.php?post_id=$post_id' class='button button-outline'>Edit</a>";
    }
}

/**
 * Count the likes on any post
 * @param int $post_id
 * @return string "X likes"
 */
function count_likes( $post_id ){
    global $DB;
    $result = $DB->prepare('SELECT count(*) AS total_likes
                            FROM likes
                            WHERE post_id = ?');
    $result->execute( array( $post_id ) );
    $row = $result->fetch();
    extract($row);
    //return the count with good grammar
    return $total_likes == 1 ? '1 Like' : "$total_likes Likes";
}


function like_interface( $post_id, $user_id = 0 ){
    global $DB;
    //is the viewer logged in?
    if($user_id){
        //does the user like this post?
        $result = $DB->prepare('SELECT * FROM likes
                                WHERE user_id = ?
                                AND post_id = ?
                                LIMIT 1');
        $result->execute(array( $user_id, $post_id ) );
        if($result->rowCount() ){
            $class = 'you-like';
        }else{
            $class = 'not-liked';
        }
    }//end if logged in
    ?>
    <span class="like-interface">
        <span class="<?php echo $class; ?>">
            <span class="heart-button" data-postid="<?php echo $post_id; ?>">❤</span>
            <?php echo count_likes( $post_id ); ?>
        </span>
    </span>
    <?php
}


/**
 * Count the number of times this user appears in the "to" field
 */
function count_followers( $user_id ){
    global $DB;
    $result = $DB->prepare('SELECT COUNT(*) as total
                            FROM follows
                            WHERE to_user_id = ?
                            ');
    $result->execute( array($user_id) );
    $row = $result->fetch();
    extract($row);
    echo $total == 1 ? '1 Follower' : "$total Followers";
}

/**
 * Count the number of times this user appears in the "from" field
 */
function count_following( $user_id ){
    global $DB;
    $result = $DB->prepare('SELECT COUNT(*) as total
                            FROM follows
                            WHERE from_user_id = ?
                            ');
    $result->execute( array($user_id) );
    $row = $result->fetch();
    extract($row);
    echo "$total Following";
}

function follows_interface( $profile_id ){
    global $logged_in_user;
    global $DB;
    //does the logged in user already follow this profile?
    $result = $DB->prepare('SELECT * FROM follows
                            WHERE to_user_id = :to
                            AND from_user_id = :from
                            LIMIT 1');
    $result->execute( array(
                    'to' => $profile_id,
                    'from' =>$logged_in_user['user_id'],
                    ) );
    if($result->rowCount()){
        //already following
        $label = 'Unfollow';
        $class = 'button-outline';
    }else{
        //not yet following
        $label = 'Follow';
        $class = 'button';
    }
    ?>
    <div class="item"><?php count_followers($profile_id); ?></div>
    <div class="item"><?php count_following($profile_id); ?></div>
    <?php if( $logged_in_user AND $logged_in_user['user_id'] != $profile_id ){ ?>
    <div class="item"><button class="follow-button <?php echo $class; ?>" data-to="<?php echo $profile_id; ?>">
    <?php echo $label; ?></button></div>
    <?php
    }//end if logged in 
}
