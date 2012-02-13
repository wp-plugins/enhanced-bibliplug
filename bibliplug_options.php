<?php

if (!current_user_can('manage_options'))
	wp_die(__('You do not have sufficient permissions to manage options for this blog.'));

$title = 'Bibliplug options';
$parent_file = 'options-general.php';

global $bib_query;

if (isset($_POST['clear-reference']) && $_POST['clear-reference']) {
	check_admin_referer('bibliplug_options');
	$bib_query->create_data_tables(true);
	unset($_POST['clear-reference']);
}

if (isset($_POST['upgrade-schema']) && $_POST['upgrade-schema']) {
	check_admin_referer('bibliplug_options');
	$bib_query->upgrade_schema();
	update_option('bibliplug_db_version', BIBLIPLUG_VERSION);
	unset($_POST['upgrade-schema']);
}

if (isset($_POST['update']) && $_POST['update']) {
	check_admin_referer('bibliplug_options');
	if (isset($_POST['page_size']) && $_POST['page_size']) {
		update_option('bibliplug_page_size', $_POST['page_size']);
	}
	
	if (isset($_POST['last_name_format']) && $_POST['last_name_format']) {
		update_option('bibliplug_last_name_format', $_POST['last_name_format']);
	}

	if (isset($_POST['sync_setting']) && $_POST['sync_setting']) {
		update_option('bibliplug_zotero_sync_setting', $_POST['sync_setting']);
	}
	
	if (isset($_POST['debug_setting'])) {
		update_option('bibliplug_debug', $_POST['debug_setting']);
	}

    if (isset($_POST['extra_links'])) {
        update_option('bibliplug_extra_links', $_POST['extra_links']);
    }
}

$is_english = get_option('bibliplug_last_name_format') == 'english';
$is_automatic = get_option('bibliplug_zotero_sync_setting') == 'automatic';
$is_debug = get_option('bibliplug_debug') != '0';
$show_extra_links = get_option('bibliplug_extra_links', '1') == '1';
?>

<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php echo esc_html( $title ); ?></h2>

	<form class="biblioplug_option" action="<?php admin_url("admin.php?page=bibliplug/bibliplug_options"); ?>" method="post">
		<?php wp_nonce_field('bibliplug_options'); ?>
		<h3>Version</h3>
		<p>
			<?php printf('Your bibliplug database version is %s.', get_option('bibliplug_db_version')); ?>
			<input type="submit" name="upgrade-schema" class="button-primary schema" value="Upgrade schema" />
		</p>

		<h3>Data size</h3>
		<p>
			<?php printf('Your bibliplug database has %d references.  ', $bib_query->get_rows()); ?>
			<input type="submit" name="clear-reference" class="button-primary schema" value="Delete references" />
		</p>

		<h3>Options</h3>
		<p>The number of references shown on the admin edit page.
			<input type="text" name="page_size" value="<?php echo get_option('bibliplug_page_size'); ?>" />
		</p>

		<p>Last name display format:
			<input type="radio" name="last_name_format" value="english" <?php echo $is_english ? 'checked' : ''; ?> /> English
			<input type="radio" name="last_name_format" value="dutch" <?php echo $is_english ? '' : 'checked'; ?>/> Dutch
		</p>
        <p>Show extra links next to references:
            <select name="extra_links">
                <option value="1" <?php echo $show_extra_links ? 'selected' : ''; ?>>True</option>
                <option value="0" <?php echo $show_extra_links ? '' : 'selected'; ?>>False</option>
            </select>
            <br/>
            <span class="description">Show extra link icons right after each reference.</span>
        </p>
		<p>Debug bibliplug:
			<select name="debug_setting">
				<option value="0" <?php echo $is_debug ? '' : 'selected'; ?>>False</option>
				<option value="1" <?php echo $is_debug ? 'selected' : ''; ?>>True</option>
			</select>
			<br/>
			<span class="description">This is helpful to debug databse errors and zotero synchronization issues.</span>
		</p>

		<p>&nbsp;</p>
		<p>Update scheduling:
			<select name="sync_setting">
				<option value="automatic" <?php echo $is_automatic ? 'selected' : ''; ?> >Automatic</option>
				<option value="manual" <?php echo $is_automatic ? '' : 'selected'; ?> >Manual</option>
			</select>
		</p>
		<span class="description">
			<p>You need to use a cron job for automatic synchronization. For example, inserting the following line in your crontab:</p>
			<pre>	<code>*/30 * * * * /usr/local/bin/curl --silent <?php echo get_bloginfo('url') . '?bibliplug_sync_zotero=1'; ?></code></pre>
			<p>will send requests to your site every 30 minutes and check for updates on all zotero connections.</p>
		</span>

		<div class="clear"><br></div>
		<p>
			<input type="submit" name="update" class="button-primary" value="Update options" />
		</p>

	</form>
<?php
/*
// Print out fields for each bib type.
global $wpdb;
  
$type_names = $bib_query->get_types();
foreach($type_names as $type) {
	echo '<h3>'.$type->name.'</h3>';
	echo "<table class='form_table' border='1'>";
	$creator_names = $bib_query->get_creator_types($type->id);
 	foreach ($creator_names as $creator) {
		echo '<tr>';
 		echo "<td>".$creator->name.'</td>';
 		echo '</tr>';
 	}
	
	

	$field_names = $bib_query->get_fields_by_type_id($type->id); 
 	foreach ($field_names as $field) {
 		echo '<tr>';
 		echo "<td>".$field->name.'</td>';
  		if ($mapped_name) {
  			echo '<td>  =>  '.$mapped_name.'</td>'; 
  		}
  		else {
  			echo '<td></td>';
  		}
 		echo '</tr>';
 	}
 	echo '</table>';
}
*/
?>