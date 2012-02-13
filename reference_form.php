<?php

if ($m) {
	switch ($m) {
		case 1:
			$message = 'New reference added.';
			break;
		case 2:
			$message = 'Reference updated.';
			break;
		case 3:
			$message = 'Duplicate entry. There is already a reference with the same type, title, and conference/meeting name.';
			break;
		case 4:
			$message = 'Action failed';
			break;
	}
}

if (!defined(BIB_LAST_NAME_FORMAT)) {
	define('BIB_LAST_NAME_FORMAT', get_option('bibliplug_last_name_format'));
}

// enable categories and tags for references.
if (!function_exists('post_categories_meta_box'))
{
	require_once(ABSPATH.'wp-admin/includes/meta-boxes.php');
}

wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );

add_meta_box('creatersdiv', 'Creators', 'bibliplug_creators_meta_box', 'bibliplug', 'normal', 'core');
add_meta_box('detailsdiv', 'Details', 'bibliplug_details_meta_box', 'bibliplug', 'normal', 'core');
add_meta_box('previewdiv', 'Preview', 'bibliplug_preview_meta_box', 'bibliplug', 'side', 'core');
add_meta_box('extralinksdiv', 'Extra links', 'bibliplug_extra_links_meta_box', 'bibliplug', 'side', 'core');
add_meta_box('updatediv', 'Save changes', 'bibliplug_save_meta_box', 'bibliplug', 'side', 'core');

add_meta_box('categorydiv-ref_cat', 'Reference categories', 'post_categories_meta_box', 'bibliplug', 'side', 'low', array('taxonomy' => 'ref_cat'));
add_meta_box('tagsdiv-ref_tag', 'Reference Tags', 'post_tags_meta_box', 'bibliplug', 'side', 'low', array('taxonomy' => 'ref_tag'));

function bibliplug_creators_meta_box() {
	echo '<div id="bibliplug_creators_meta_box">';

	global $bib_query, $bib, $bib_template;
	if ($bib) {
		$creators = $bib_query->get_creators($bib->id);
		$creator_types = $bib_query->get_creator_types_by_type_id($bib->type_id);
	} else {
		$creators = array();
		$creator_types = $bib_query->get_creator_types_by_type_id();
	}

	echo $bib_template->print_creator_list($creators, $creator_types);
	echo '</div>';
}

function bibliplug_details_meta_box()
{
	echo "<div id='bibliplug_details_meta_box'>";
	global $bib, $bib_template, $fields;
	$type_id = $bib ? $bib->type_id : 1;
	$bib_template->print_details_table($bib, $type_id, $fields);
	echo '</div>';
}

function bibliplug_preview_meta_box()
{
	global $bib, $fields;
	echo '<div id="bibliplug_preview">';

	if ($bib) {
		$style_helper = new display_format_helper();
		echo $style_helper->display_chicago_style($bib, $fields);
  	} else {
  		echo 'No preview available.';
  	}

  	echo '</div>';
}

function bibliplug_extra_links_meta_box()
{
    echo '<div id="bibliplug_extra_links">';
    global $bib, $bib_template;
    $bib_template->print_extra_links($bib);
    echo '</div>';
}

function bibliplug_save_meta_box($bib) {
	?><div class="bibliography">
	<p><label for="peer_reviewed">Peer reviewed </label><input type="checkbox" name="peer_reviewed" <?php echo $bib->peer_reviewed ? "checked='yes'" : ""; ?> value="peer_reviewed" /></p>
	<p class="submit">
	<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
	</p></div><?php
}
?>

<div class="wrap">
<?php screen_icon('edit'); ?>
<h2><?php echo esc_html( $title ); ?></h2>

<?php if ( $message ) { ?>
<div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
<?php } ?>

<form name="bibliplug_form" method="post" action="<?php echo admin_url('admin.php?page=enhanced-bibliplug/bibliplug_edit.php&id=' . $bib->id); ?>">
<?php wp_nonce_field($nonce_name); ?>
<input type="hidden" id="hiddenbibid" name="bib_id" value='<?php echo ($bib) ? $bib->id : -1; ?>' />
<input type="hidden" id="hiddenaction" name="action" value='<?php echo $action; ?>' />
<div id="poststuff" class="metabox-holder has-right-sidebar">
	<div id="side-info-column" class="inner-sidebar">
		<?php do_meta_boxes('bibliplug', 'side', $bib); ?>
	</div>
	<div id="post-body">
		<div id="post-body-content">
			<?php do_meta_boxes('bibliplug', 'normal', $bib); ?>
		</div>
	</div>

<br class="clear" />
</div>
</form>
