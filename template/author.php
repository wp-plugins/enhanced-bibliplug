<?php get_header(); ?>

<div id="container">
	<div id="content" role="main">
	<?php 
		$curauth = get_userdata($wp_query->query_vars['author']);

        if (!defined('BIB_LAST_NAME_FORMAT' ))
        {
            define('BIB_LAST_NAME_FORMAT', get_option('bibliplug_last_name_format'));
        }

        $display_name = print_name($curauth, true, true);

		echo "<div id='archive_intro' class='author_bio'>";
		echo "<h1><strong>$display_name</strong></h1>";
		echo "	<div class='bibliplug-author_avatar'>".get_avatar($curauth->ID, 120)."</div>";
		echo "	<div class='bibliplug-author_details format_text entry-content'>";
		echo "		<p><strong>".str_replace("\n", "<br/>", $curauth->affiliation)."</strong></p>";
		echo "		<p><a href='mailto:$curauth->user_email'>Email</a>";
		if ($curauth->user_url) {
			echo " | <a href='$curauth->user_url'>Website</a>";
		}
		echo "		</p>";
		echo "		<p>$curauth->author_bio</p>";
		echo "	</div>";
		echo "</div>";

		echo "<div class='bibliplug-author_rest post_box'>";
		$publications = do_shortcode("[bibliplug last_name='$curauth->last_name' first_name='$curauth->first_name' type='publications']");
		if ($publications) {
			echo "<div class='headline_area'><h2 class='entry-title'>Publications</h2></div>";
			echo "<div class='format_text entry-content'>$publications</div>";
		}

		$presentations = do_shortcode("[bibliplug last_name='$curauth->last_name' first_name='$curauth->first_name' type='presentations']");
		if ($presentations) {
			echo "<div class='headline_area'><h2 class='entry-title'>Presentations</h2></div>";
			echo "<div class='format_text entry-content'>$presentations</div>";
		}
		echo "</div>";
	?>
	</div>
</div>

<?php get_sidebar(); ?>
<?php get_footer(); ?>