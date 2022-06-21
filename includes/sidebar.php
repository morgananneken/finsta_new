<aside class="sidebar">

	<?php 
	$result = $DB->prepare('SELECT profile_pic, username, user_id
		FROM users
		ORDER BY join_date DESC
		LIMIT 5');
	$result->execute();
	if( $result->rowCount() ){ ?>
		<section class="users">
			<h2>Newest Users</h2>
			<ul>
				<?php while($row = $result->fetch()){ 
					extract( $row ); ?>
					<li class="user">
						<a href="profile.php?user_id=<?php echo $user_id; ?>">
						<?php show_profile_pic( $profile_pic, $username ); ?>
						</a>
					</li>
				<?php } //end while ?>
			</ul>
		</section>
	<?php } //end if users ?>


	<?php 
	$result = $DB->prepare('SELECT categories.*, COUNT(*) AS	total
							FROM posts, categories
							WHERE posts.category_id = categories.category_id
							GROUP BY posts.category_id');
	$result->execute();
	if( $result->rowCount() ){ ?>
		<section class="categories">
			<h2>Categories</h2>
			<ul>
				<?php 
				while( $row = $result->fetch() ){
					extract($row);
					echo "<li>$name ($total posts)</li>";
				 } 
				?>
			</ul>
		</section>
	<?php } ?>

		<?php 
	$result = $DB->prepare('SELECT * 
							FROM tags
							ORDER BY RAND()
							LIMIT 20');
	$result->execute();
	if( $result->rowCount() ){ ?>
		<section class="tags">
			<h2>Tags</h2>
			<ul>
				<?php 
				while( $row = $result->fetch() ){
					extract($row);
					echo "<li>$name</li>";
				 } 
				?>
			</ul>
	
		</section>
	<?php } ?>	

</aside>