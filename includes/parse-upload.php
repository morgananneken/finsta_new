<?php
$errors = array();
//if the user submitted the form
if( isset($_POST['did_upload']) ){
	//upload configuration 
	//this directory must exist and be writable
	$target_directory = 'uploads/';
    //smallest allowed image size
    $min_size = 600;

	$sizes = array(
		'small' 	=> 150,
		'medium'	=> 300,
		'large'		=> 600,
	);

	//grab the image that they uploaded
	$uploadedfile = $_FILES['uploadedfile']['tmp_name'];

	//validate
	$valid = true;

	//get the dimensions of the image
	list( $width, $height ) = getimagesize( $uploadedfile );

	//does the image contain pixels?
	if( $width < $min_size OR $height < $min_size ){
		//NOT A BIG ENOUGH IMAGE
		$valid = false;
		$errors['size'] = 'Your image does not meet the minimum size requirements. Images must be at least 600 pixels wide.';
	}

	//if valid, process and resize the image
	if($valid){

		//get the filetype
		$filetype = $_FILES['uploadedfile']['type'];

		switch( $filetype ){
			case 'image/jpg':
			case 'image/jpeg':
			case 'image/pjpeg':
				$src = imagecreatefromjpeg( $uploadedfile );
			break;

			case 'image/gif':
				$src = imagecreatefromgif( $uploadedfile );
			break;

			case 'image/png':
				//todo: increase resources on the server
				$src = imagecreatefrompng( $uploadedfile );
			break;
		}

		//unique string for the final file name
		$unique_name = sha1( microtime() );

		//do the resizing
		foreach( $sizes AS $size_name => $pixels ){
			//square crop calculations -  landscape or portrait
			if( $width > $height ){
				//landscape
				$offset_x = floor( ( $width - $height ) / 2) ;
				$offset_y = 0;
				$crop_size = $height;
			}else{
				//portrait or square
				$offset_x = 0;
				$offset_y = floor( ( $height - $width ) / 2);
				$crop_size = $width;
			}
			//create a new blank canvas of the desired size
			$tmp_canvas = imagecreatetruecolor( $pixels, $pixels );

			//scale down and align the original onto the tmp canvas
			//dst_image, src_image, dst_x, dst_y, src_x, src_y, dst_w, dst_h, src_w, src_h
			imagecopyresampled( $tmp_canvas, $src, 0, 0, $offset_x, $offset_y, $pixels, $pixels, $crop_size, $crop_size );

			//save it into the correct directory
			//something like 	uploads/fdkuhfdghjkfdg_small.jpg
			$filepath = $target_directory . $unique_name . '_' . $size_name . '.jpg';

			$did_save = imagejpeg( $tmp_canvas, $filepath, 70 );

		}//end foreach size

		//clean up old resources
		imagedestroy($src);
		imagedestroy($tmp_canvas);


		// Add post to Database
        if( $did_save ){
            $result = $DB->prepare('INSERT INTO posts
                                    ( image, user_id, category_id, title, body, date, is_published, allow_comments )
                                    VALUES
                                    ( :image, :user, 0, "", "", NOW(), 0, 0 )
                                    ');
            $result->execute( array(
                                'image' => $unique_name ,
                                'user' => $logged_in_user['user_id']

                            ) );
            //checked to see if it worked
            if( $result->rowCount() ){
                //success go to step 2
                $feedback = 'Success';
                $feedback_class = 'success';
            }else{
                $feedback = 'Your post could not be saved';
                $feedback_class = 'error';
            }
        }else{
            $feedback = 'Your image could not be saved.';
            $feedback_class = 'error';
        }

	}//end if valid
	else{
		$feedback = 'There was a problem uploading your image, fix the following:';
        $feedback_class = 'error';
	}

}//end upload parser