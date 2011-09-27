<?php

/**
 * Reference Manager List Table class.
 *
 * @package Bibliplug
 * @since 0.2.8
 * @access private
 */
class Bibliplug_Reference_List_Table extends WP_List_Table
{

	var $per_page;

	function __construct()
	{

		$this->per_page = get_option('bibliplug_page_size');

		parent::__construct(array(
			'plural' => 'References',
		));
	}

	function ajax_user_can()
	{
		global $post_type_object;
		return current_user_can($post_type_object->cap->edit_posts);
	}

	function prepare_items()
	{
		global $bib_query, $current_user, $search, $page, $orderby, $order;
		get_currentuserinfo();

		if (!$current_user->has_cap('administrator'))
		{
			$current_user_id = $current_user->ID;
			// otherwise, use 0 for editors.
		}

		$total_items = $bib_query->get_rows($current_user_id, $search);
		
		$this->set_pagination_args(array(
			'total_items' => $total_items,
			'total_pages' => ceil($total_items / $this->per_page),
			'per_page' => $this->per_page
		));

		$this->items = $bib_query->get_references_by_page($page, $current_user_id, $search, $orderby, $order);
	}

	function no_items()
	{
		_e('No references found.');
	}

	function get_bulk_actions()
	{
		return array();
	}
	
	function get_columns()
	{
		return array(
			'cb' => '<input type="checkbox" />',
			'title' => 'Title',
			'author' => 'Author',
			'type' => 'Type',
			'year' => 'Year'
		);
	}

	function get_sortable_columns()
	{
		return array(
			'title' => 'title',
			'author' => 'ln',
			'type' => 'type',
			'year' => 'year'
		);
	}

	function get_column_info()
	{
		foreach ($this->get_sortable_columns() as $id => $data)
		{
			$sortable[$id] = array($data, false);
		}

		return array(
			$this->get_columns(),
			array(),
			$sortable);
	}

	function display_rows()
	{
		global $bib_query, $page;
		$alt = 0;
		$is_english = get_option('bibliplug_last_name_format') == 'english';
		foreach ($this->items as $ref)
		{
			$rowclass = ( $alt++ % 2 ) ? '' : 'alternate';

			$edit_link = admin_url("admin.php?page=enhanced-bibliplug/bibliplug_edit.php&id=$ref->id");
			$title = stripslashes($ref->title);
			?><tr id='reference-<?php echo $ref->id; ?>' class='<?php echo $rowclass; ?> iedit' valign="top"><?php
			$columns = $this->get_columns();
			foreach ($columns as $column_name => $column_display_name)
			{
				$class = "class=\"$column_name column-$column_name\"";

				switch ($column_name)
				{

					case 'cb':
						?><th scope="row" class="check-column"><input type="checkbox" name="reference[]" value="<?php echo $ref->id; ?>" /></th><?php
						break;

					case 'title':
						$attributes = 'class="post-title column-title" ';
						?><td <?php echo $attributes ?>><strong><a class="row-title" href="<?php echo $edit_link; ?>" title="<?php echo esc_attr(sprintf(__('Edit &#8220;%s&#8221;'), $title)); ?>"><? echo $title ?></a></strong><?
						$actions = array();
						$actions['edit'] = '<a href="' . $edit_link . '" title="' . esc_attr(__('Edit this reference')) . '">' . __('Edit') . '</a>';
						$actions['delete'] = '<a class="submitdelete" title="Delete this reference." href="' . wp_nonce_url("admin.php?page=enhanced-bibliplug/bibliplug_manager.php&action=delete&bib_id=$ref->id&paged=$page", 'delete-bib_' . $ref->id) . '" onclick="if(confirm(\'' . esc_js(sprintf("You are about to delete reference '%s'\n 'Cancel' to stop, 'OK' to delete.", $title)) . '\')) { return true;}return false;">Delete</a>';
						$action_count = count($actions);
						$i = 0;
						echo '<div class="row-actions">';
						foreach ($actions as $action => $link)
						{
							++$i;
							$sep = ($i == $action_count) ? '' : ' | ';
							echo "<span class='$action'>$link$sep</span>";
						}
						echo '</div>';
						echo '</td>';
						break;

					case 'author':
						echo '<td ' . $class . '>';
						$creators = $bib_query->get_primary_creators($ref->id, $ref->type_id);
						if (!empty($creators))
						{
							$out = array();
							foreach ($creators as $creator)
							{
								if ($is_english && $creator->prefix)
								{
									$out[] = $creator->first_name . ' ' . $creator->prefix . ' ' . $creator->last_name;
								} else
								{
									$out[] = $creator->first_name . ' ' . $creator->last_name;
								}
							}
							echo join(', ', $out);
						}
						echo '</td>';
						break;

					case 'year':
						echo '<td ' . $class . '>';
						echo $ref->year;
						echo '</td>';
						break;

					case 'type':
						echo '<td ' . $class . '>';
						echo $ref->type;
						echo '</td>';
						break;
				}
			}

			echo "</tr>";
		}
	}

}
?>