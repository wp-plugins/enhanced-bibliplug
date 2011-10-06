<?php

if (!defined('BIBLIPLUG_DIR')) {
	die('Invalid access point.');
}

require_once('class-bibliplug-reference-table.php');

$title = 'Manage your references';
$this_file = $parent_file = 'bibliplug_manager.php';


global $bib_query;

if (isset($_GET['action'])) {
	$action = $_GET['action'];
	
	if ($action == 'delete') {
		$bib_id = (int) $_GET['bib_id'];
		check_admin_referer('delete-bib_' . $bib_id);
		$bib_query->delete_bibliography($bib_id);
	}
}

if (isset($_GET['paged'])) {
	$page = $_GET['paged'];
} else {
	$page = 1;
}

if(isset($_GET['s'])) {
	$search = $_GET['s'];
}

if (isset($_GET['orderby']))
{
    $orderby = $_GET['orderby'];
}

if (isset($_GET['order']))
{
    $order = $_GET['order'];
}

$bib_manager = new Bibliplug_Reference_List_Table();
$bib_manager->prepare_items();
?>

<div class="wrap">
<?php screen_icon('edit'); ?>
<h2><?php
	echo esc_html($title);
	if ($search) 
    {
		printf('<span class="subtitle">Search results for &#8220;%s&#8221;</span>', esc_html(stripslashes($search)));
	}
?></h2>

<form id="references-filter" action="" method="get">
	<input type="hidden" name="page" value="enhanced-bibliplug/bibliplug_manager.php" />
	<?php $bib_manager->search_box('Search references', 'reference'); ?>
	<?php $bib_manager->display(); ?>
</form>