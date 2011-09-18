<?php

if (!defined('BIBLIPLUG_DIR')) {
	die('Invalid access point.');
}

if (!current_user_can('edit_posts')) {
	wp_die('You do not have sufficient permissions to manage bibliography.');
}

$m = 0;

if (!empty($_POST)) {	
	$is_add = true;
	if (isset($_POST['action'])) {
		$action = $_POST['action'];
	}

	if (isset($_POST['bib_id'])) {
		$bib_id = (int) $_POST['bib_id'];
	}

	if ($action == 'edit') {
		$bib = $bib_query->get_reference(array('id' => $bib_id));
		$is_add = false;

		if (!$bib) {
			wp_die("Reference not found.");
		}
	}

	$creators = $_POST['creator'];
	$creator_format = array('%d', '%d', '%s', '%s', '%s', '%s');

	$data['type_id'] = (int) $_POST['type_id'];
	$data_format[] = '%d';

	$data['peer_reviewed'] = isset($_POST['peer_reviewed'])? 1 : 0;
	$data_format[] = '%d';

	$post_fields = $bib_query->get_fields_by_type_id($data['type_id']);
	foreach ($post_fields as $field) {
		$field_name = $field->internal_name;
		$value = $_POST[$field_name];

		if (is_field_numeric($field_name))
		{
			$data[$field_name] = empty($value) ? 0 : (int) $value;
			$data_format[] = '%d';
		}
		else
		{
			if ($value && ($field_name == 'link1' || $field_name == 'link2' || $field_name == 'link3' || $field_name == 'url')) {
				$value = validate_url($value);
			}

			$data[$field_name] = $value;
			$data_format[] = '%s';
		}
	}

	//print_r($data);

	try
	{
		switch ($action)
		{
			case 'edit':
				check_admin_referer('edit_reference');
				$bib_query->update_bibliography($bib_id, $data, $data_format);

				foreach ($creators as $creator_id => $creator)
				{
					if ($creator_id > 0)
					{
						if (!$creator['first_name'] && !$creator['last_name'] && !$creator['prefix'] && !$creator['middle_name'])
						{
							$creator['id'] = $creator_id;
							$creator['bib_id'] = $bib_id;
							$bib_query->delete_creator($creator);
						}
						else
						{
							$old_creator = $bib_query->get_creator($creator_id);
							if ($old_creator != $creator)
							{
								$bib_query->update_creator($creator_id, $creator, $creator_format);
							}
						}
					}
					else
					{
						if ($creator['first_name'] || $creator['last_name'] || $creator['prefix'] || $creator['middle_name']) {
							// adding a new creator to an existing reference.
							$creator['bib_id'] = $bib_id;
							$creator_format[] = '%d';
							$bib_query->insert_creator($creator, $creator_format);
						}
					}
				}
				$m = 2;
				break;
			case 'add':
				check_admin_referer('add_reference');
				$bib_id = $bib_query->insert_bibliography($data, $data_format, $creators, $creator_format);
				$m = 1;
				break;
		}

		// now update its categories and tags
		$categories = array_filter($_POST['tax_input']['ref_cat']);
		$categories = array_map('intval', $categories);
		$categories = array_unique($categories);
		wp_set_object_terms($bib_id, $categories, 'ref_cat');

		$tags = $_POST['tax_input']['ref_tag'];
		wp_set_object_terms($bib_id, $tags, 'ref_tag');
	}
	catch (exception $e)
	{
		if (strpos($e->getMessage(), "Duplicate entry") !== false)
		{
			$m = 3;
		}
		else
		{
			if (BIBLIPLUG_DEBUG)
			{
				print $e->getMessage();
			}
			
			$m = 4;
		}
	}
	
	unset($_POST);
}

$parent_file = 'bibliplug_manager.php';
$submenu_file = 'bibliplug_manager.php';
$title = 'Edit your reference';
$action = 'edit';
$nonce_name = 'edit_reference';

// if add didn't succeed, no need to set up bib.
if (!$is_add || $m == 1 || $m == 2)
{
	if (isset($_GET['id']) && $_GET['id']) {
		$bib_id = (int) $_GET['id'];
	}
	
	$bib = $bib_query->get_reference(array('id' => $bib_id));
	
	if (!$bib) {
		wp_die("Reference '$bib_id' not found.");
	}
	
	$fields = $bib_query->get_fields_by_type_id($bib->type_id);
	
	// the post category meta box will try to look for a "ID" field.
	$bib->ID = $bib->id;
	$post_ID = $bib->id;
}
else 
{
	$action = 'add';
	$nonce_name = 'add_reference';
	$fields = $bib_query->get_fields_by_type_id();
}

include (BIBLIPLUG_DIR.'reference_form.php');

?>