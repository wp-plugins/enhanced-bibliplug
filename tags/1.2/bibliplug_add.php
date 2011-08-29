<?php

if (!defined('BIBLIPLUG_DIR')) {
	die('Invalid access point.');
}

if (!current_user_can('edit_posts')) {
	wp_die('You do not have sufficient permissions to manage bibliography.');
}

$parent_file = 'bibliplug_manager.php';
$title = 'Add new reference';

$action = 'add';
$nonce_name = 'add_reference';


global $bib_query;
$fields = $bib_query->get_fields_by_type_id();

include (BIBLIPLUG_DIR.'reference_form.php');

?>