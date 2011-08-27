<?php

/*
  Plugin Name: Enhanced BibliPlug
  Plugin URI: http://ep-books.ehumanities.nl/semantic-words/enhanced-bibliplug
  Description: Collaborative bibliography management for WordPress.
  Version: 1.0
  Author: Zuotian Tatum, Clifford Tatum
 */

/*  Copyright 2011  Zuotian Tatum  (email : zuotiantatum@live.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if (!defined('BIBLIPLUG_VERSION'))
{
	define('BIBLIPLUG_VERSION', '1.0');
}

if (!defined('BIBLIPLUG_DIR'))
{
	define('BIBLIPLUG_DIR', ABSPATH . 'wp-content/plugins/enhanced-bibliplug/');
}

global $wpdb;

if (!class_exists('bibliplug_query'))
{
	require_once(BIBLIPLUG_DIR . 'bibliplug_query.php');
}

if (!class_exists('display_format_helper'))
{
	require_once(BIBLIPLUG_DIR . 'format_helper/display_format_helper.php');
}

if (!class_exists('bibliplug_template'))
{
	require_once(BIBLIPLUG_DIR . 'bibliplug_template.php');
}

if (!isset($bib_query))
{
	$bib_query = new bibliplug_query();
}

if (!isset($bib_template))
{
	$bib_template = new bibliplug_template();
}

register_activation_hook(__FILE__, 'bibliplug_activation');
add_action('admin_menu', 'bibliplug_menu');
add_action('admin_head', 'remove_edit_menu');
add_shortcode('bibliplug', 'bibliplug_shortcode_handler');
add_shortcode('bibliplug_authors', 'bibliplug_authors_shortcode_handler');

// enable categories and tags for references.
add_action('init', 'bibliplug_init', 0);

// add css and js to wp non-admin page header
add_action('admin_init', 'bibliplug_admin_init');
add_action('wp_head', 'bibliplug_head');

// this filter will remove any additional contact fields such AIM from the profile page
add_filter('user_contactmethods', 'bibliplug_contact_filter');

// this function will use ajax callback to add additional fields for the profile page.
add_action('wp_ajax_bibliplug_user_extra', 'bibliplug_user_extra');

// these functions will use ajax callback to add/sync/delete zotero connections.
add_action('wp_ajax_bibliplug_sync_zotero', 'bibliplug_sync_zotero');
add_action('wp_ajax_bibliplug_add_connection', 'bibliplug_add_connection');
add_action('wp_ajax_bibliplug_delete_connection', 'bibliplug_delete_connection');

// this function will export references.
add_action('wp_ajax_bibliplug_export', 'bibliplug_export');

// this function will use ajax callback to change reference details form on type change.
add_action('wp_ajax_bibliplug_change_ref_type', 'bibliplug_change_ref_type');

// the function will update added fields.
add_action('personal_options_update', 'bibliplug_user_edit');
add_action('edit_user_profile_update', 'bibliplug_user_edit');

// the function will change user's display_name to first last format.
add_action('user_register', 'bibliplug_user_display_name');
add_action('profile_update', 'bibliplug_user_display_name');


/* ---------------------------------------------------------------------
 * function implementations.
 * ------------------------------------------------------------------- */

// plugin activation
function bibliplug_activation()
{
	// here we should create tables needed for this plug in
	// cannot use global $bib_query here.
	$bib_query = new bibliplug_query();
	$bib_query->refresh_schema();

	// update db version
	$option_name = 'bibliplug_db_version';
	if (BIBLIPLUG_VERSION != get_option($option_name))
	{
		update_option($option_name, BIBLIPLUG_VERSION);
	}

	// add page option
	add_option('bibliplug_page_size', 25);
	add_option('bibliplug_last_name_format', 'english');
}

// add plugin admin menu
function bibliplug_menu()
{
	// contributor or above can see this menu
	add_menu_page('Bibliography', 'Bibliography', 1, 'enhanced-bibliplug/bibliplug_manager.php');
	add_submenu_page('enhanced-bibliplug/bibliplug_manager.php', 'Manage', 'Manager', 1, 'enhanced-bibliplug/bibliplug_manager.php');
	add_submenu_page('enhanced-bibliplug/bibliplug_manager.php', 'Edit', 'Edit', 1, 'enhanced-bibliplug/bibliplug_edit.php');
	add_submenu_page('enhanced-bibliplug/bibliplug_manager.php', 'Add New', 'Add New', 1, 'enhanced-bibliplug/bibliplug_add.php');
	add_submenu_page('enhanced-bibliplug/bibliplug_manager.php', 'Import', 'Import', 8, 'enhanced-bibliplug/bibliplug_import.php');
	add_submenu_page('enhanced-bibliplug/bibliplug_manager.php', 'Export', 'Export', 1, 'enhanced-bibliplug/bibliplug_export.php');
	add_submenu_page('enhanced-bibliplug/bibliplug_manager.php', 'Zotero Connector', 'Zotero Connector', 8, 'enhanced-bibliplug/bibliplug_zotero.php');

	// only admin can see the option setting page
	add_options_page('Bibliplug Options', 'BibliPlug', 8, 'bibliplug/bibliplug_options.php');
}

function remove_edit_menu()
{
	// hide bibliplug_edit.php in the submenu
	global $submenu;
	unset($submenu['enhanced-bibliplug/bibliplug_manager.php'][1]);
}

// add css to non-admin page header
function bibliplug_head()
{
	echo '<link type="text/css" href="' . plugins_url('/enhanced-bibliplug/css/bibliplug.css') .'" rel="Stylesheet" />' . PHP_EOL;
}

function bibliplug_admin_init()
{
	global $pagenow;
	$subpage = $_GET['page'];
	$js_suffix = SCRIPT_DEBUG ? "dev.js" : "js";
	if ($pagenow == 'admin.php')
	{
		if ($subpage == 'enhanced-bibliplug/bibliplug_add.php' || $subpage == 'enhanced-bibliplug/bibliplug_edit.php')
		{
			wp_enqueue_script('postbox');
			wp_enqueue_script('bibliplug_ajax', plugins_url("/enhanced-bibliplug/js/bibliplug_ajax.$js_suffix"), array('jquery', 'jquery-ui-sortable', 'quicktags', 'postbox'));
			wp_enqueue_style('bibliplug_admin_css', plugins_url('/enhanced-bibliplug/css/bibliplug-admin.css'), 'css');
			wp_enqueue_style('bibliplug_css', plugins_url('/enhanced-bibliplug/css/bibliplug.css'), 'css');
		}
		else if ($subpage == 'enhanced-bibliplug/bibliplug_manager.php')
		{
			wp_enqueue_style('bibliplug_admin_css', plugins_url('/enhanced-bibliplug/css/bibliplug-admin.css'), 'css');
		}
		else if ($subpage == 'enhanced-bibliplug/bibliplug_zotero.php')
		{
			wp_enqueue_style('bibliplug_admin_css', plugins_url('/enhanced-bibliplug/css/bibliplug-admin.css'), 'css');
			wp_enqueue_script('bibliplug_zotero_js', plugins_url("/enhanced-bibliplug/js/bibliplug_zotero.$js_suffix"), array('jquery', 'wp-ajax-response'));
		}
	}
	else if ($pagenow == 'profile.php' || $pagenow == 'user-edit.php')
	{
		wp_enqueue_script('bibliplug_profile', plugins_url("/enhanced-bibliplug/js/profile.$js_suffix"), array('jquery', 'wp-ajax-response'));
		wp_enqueue_script('tiny_mce');
		wp_enqueue_style('bibliplug_admin_css', plugins_url('/enhanced-bibliplug/css/bibliplug-admin.css'), 'css');
		add_action('admin_print_footer_scripts', 'wp_tiny_mce', 25);
	}
	else if ($pagenow == 'options-general.php' && $subpage == 'enhanced-bibliplug/bibliplug_options.php')
	{
		wp_enqueue_script('bibliplug_js', plugins_url('/enhanced-bibliplug/js/bibliplug.js'), array('jquery'));
	}
}

// add shortcode support in posts and pages.
function bibliplug_shortcode_handler($atts)
{
	global $bib_query;

	if (!defined(BIB_LAST_NAME_FORMAT))
	{
		define('BIB_LAST_NAME_FORMAT', get_option('bibliplug_last_name_format'));
	}

	extract(shortcode_atts(array('id' => '0',
				'last_name' => '',
				'first_name' => '',
				'year' => '',
				'type' => '',
				'category' => '',
				'tag' => '',
				'displayHeader' => 'false'), $atts));

	$refs = array();
	if ($id != '0')
	{
		$refs = array($bib_query->get_reference($id));
	}
	else
	{
		if ($category)
		{
			//$refs = $bib_query->get_referencesby_taxonomy($category, 'ref_cat');
			$tax_name = $category;
			$tax_type = 'ref_cat';
		}
		else if ($tag)
		{
			//$refs = $bib_query->get_references_by_taxonomy($tag, 'ref_tag');
			$tax_name = $tag;
			$tax_type = 'ref_tag';
		}

		$refs = $bib_query->get_references($last_name, $first_name, $year, $type, $tax_name, $tax_type);
	}

	$style_helper = new display_format_helper();
	$result = '';

	foreach ($refs as $ref)
	{
		$fields = $bib_query->get_fields_by_type_id($ref->type_id);
		$result .= $style_helper->display_chicago_style($ref, $fields);
	}

	return $result;
}

function bibliplug_authors_shortcode_handler($atts)
{
	global $bib_query;
	$default_attrs = array(
		'id' => 0,
		'format' => 'list'
	);

	extract(shortcode_atts($default_attrs, $atts));
	$id = intval($id);

	if ($id)
	{
		$author_ids = array((object) array('ID' => $id));
	}
	else
	{
		$author_ids = $bib_query->get_wp_author_ids();
	}

	$result = "<table " . (($format == 'list') ? "class= 'bibliplug-authors'" : "") . ">";

	foreach ($author_ids as $author)
	{
		$curauth = get_userdata($author->ID);

		if ($format == 'list')
		{
			$result .= "<tr><td><a href='" . get_author_posts_url($curauth->ID) . "'>" . get_avatar($curauth->ID, 120) . "</a></td>";
			$result .= "<td><b><a href='" . get_author_posts_url($curauth->ID) . "'>$curauth->display_name</b></a><br/>" . str_replace("\n", "<br/>", $curauth->affiliation) . "</td><tr>";
		}
		else if ($format == 'mini')
		{
			$result .= "<a href='" . get_author_posts_url($curauth->ID) . "'>" . get_avatar($curauth->ID, 120) . "</a>";
		}
		else if ($format == 'profile')
		{
			$result .= "<tr><td>";
			$result .= "	<h2>$curauth->display_name</h2>";
			$result .= "	<div class='bibliplug-author_avatar'><a href='" . get_author_posts_url($curauth->ID) . "'>" . get_avatar($curauth->ID, 120) . "</a></div>";
			$result .= "	<div class='bibliplug-author_details'>";
			$result .= "		<p><strong>" . str_replace("\n", "<br/>", $curauth->affiliation) . "</strong></p>";
			$result .= "		<p><a href='mailto:$curauth->user_email'>Contact</a>";
			
			if ($curauth->user_url)
			{
				$result .= " | <a href='$curauth->user_url'>Website</a>";
			}

			$result .= "		</p>";
			$result .= "		<p>$curauth->author_bio</p>";
			$result .= "	</div>";
			$result .= "</td></tr>";
		}
		else
		{
			$result .= "Can not regconize format '$format'.";
		}
	}


	$result .= "</table>";
	return $result;
}

function bibliplug_user_edit()
{
	global $user_id;
	if (isset($_POST['affiliation']))
	{
		update_user_meta($user_id, 'affiliation', $_POST['affiliation']);
	}

	if (isset($_POST['middle_name']))
	{
		update_user_meta($user_id, 'middle_name', $_POST['middle_name']);
	}

	if (isset($_POST['prefix']))
	{
		update_user_meta($user_id, 'prefix', $_POST['prefix']);
	}

	if (isset($_POST['author_bio']))
	{
		update_user_meta($user_id, 'author_bio', $_POST['author_bio']);
	}

	if (isset($_POST['researcher_id']))
	{
		update_user_meta($user_id, 'researcher_id', $_POST['researcher_id']);
	}

	if (isset($_POST['dai']))
	{
		update_user_meta($user_id, 'dai', $_POST['dai']);
	}
}

function bibliplug_contact_filter($contact)
{
	return array();
}

function bibliplug_user_display_name($ID)
{
	global $wpdb;
	$user_data = get_userdata($ID);
	$display_name = $user_data->first_name . ' ' . $user_data->last_name;
	$wpdb->update($wpdb->users, compact('display_name'), compact('ID'));
}

// enable categories and tags for references.
function bibliplug_init()
{
	// this is from an automatic update cron job.
	if ($_GET['bibliplug_sync_zotero'])
	{
		bibliplug_sync_zotero();
	}
	
	// Add new taxonomy for reference, make it hierarchical (like categories)
	$labels = array(
		'name' => _x('Reference categories', 'taxonomy general name'),
		'singular_name' => _x('Reference category', 'taxonomy singular name'),
		'search_items' => __('Search reference categories'),
		'all_items' => __('All reference categories'),
		'parent_item' => __('Parent reference category'),
		'parent_item_colon' => __('Parent reference category:'),
		'edit_item' => __('Edit reference category'),
		'update_item' => __('Update reference category'),
		'add_new_item' => __('Add new reference category'),
		'new_item_name' => __('New reference category'),
		'menu_name' => __('Reference category'),
	);

	register_taxonomy('ref_cat', array('reference'), array(
		'hierarchical' => true,
		'labels' => $labels,
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => array('slug' => 'ref_cat'),
	));

	// Add new taxonomy, NOT hierarchical (like tags)
	$labels = array(
		'name' => _x('Reference tags', 'taxonomy general name'),
		'singular_name' => _x('Reference tag', 'taxonomy singular name'),
		'search_items' => __('Search reference tags'),
		'popular_items' => __('Popular reference tags'),
		'all_items' => __('All reference tags'),
		'parent_item' => null,
		'parent_item_colon' => null,
		'edit_item' => __('Edit reference tag'),
		'update_item' => __('Update reference tag'),
		'add_new_item' => __('Add new reference tag'),
		'new_item_name' => __('New reference tag Name'),
		'separate_items_with_commas' => __('Separate reference tags with commas'),
		'add_or_remove_items' => __('Add or remove reference tags'),
		'choose_from_most_used' => __('Choose from the most used reference tags'),
		'menu_name' => __('Reference tags'),
	);

	register_taxonomy('ref_tag', 'reference', array(
		'hierarchical' => false,
		'labels' => $labels,
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => array('slug' => 'reg_tag'),
	));
}

include('bibliplug_ajax.php');
?>