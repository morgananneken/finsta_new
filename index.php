<?php 
require('CONFIG.php'); 
require_once('includes/functions.php');
require('includes/header.php');
?>
		<main class="content">
			<?php //get up to 20 published posts, newest first
			$result = $DB->prepare( 'SELECT posts.*, categories.*, users.username, users.profile_pic, users.						user_id
									FROM posts, categories, users
									WHERE posts.category_id = categories.category_id
									AND posts.user_id = users.user_id
									AND posts.is_published = 1
									ORDER BY posts.date DESC
									LIMIT 20' );
			//run it
			$result->execute();
			//check if any rows were found
			if( $result->rowCount() >= 1 ){
				//loop it
				while( $row = $result->fetch() ){
					//print_r($row);
					//make variables from the array keys
					extract($row);
			?>
			
			<div class="post">
				<img src="<?php echo $image; ?>" alt="<?php echo $title; ?>">
				<span class="author">
					<img src="<?php echo $profile_pic; ?>" width="50" height="50" alt="<?php echo $username; ?>">
					<?php echo $username; ?>
				</span>
				<h2><?php echo $title; ?></h2>
				<p><?php echo $body; ?></p>

				<span class="category"><?php echo $name; ?></span>
				<span class="comment-count"><?php echo count_comments( $post_id ); ?></span>
				<span class="date"><?php echo time_ago($date); ?></span>
			</div>

			<?php 
				} //endwhile
			}else{
				//no rows found from our query
				echo 'No posts found';
			} ?>

		</main>
<?php 
require('includes/sidebar.php'); 
require('includes/footer.php'); 
?>
		