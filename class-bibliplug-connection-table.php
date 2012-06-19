<?php

/**
 * Reference (Zotero) Connection Manager List Table class.
 *
 * @package Bibliplug
 * @since 0.2.8
 * @access private
 */
class Bibliplug_Connection_List_Table extends WP_List_Table
{
	function __construct()
	{
		parent::__construct(array(
			'plural' => 'Connections',
		));
	}

	function ajax_user_can()
	{
		return current_user_can('administrator');
	}

	function prepare_items()
	{
		global $bib_query;
		$this->items = $bib_query->get_zotero_connections();
		
		$this->set_pagination_args(array(
			'total_items' => count($this->items),
			'total_pages' => ceil(count($this->items) / 50),
			'per_page' => 50
		));
	}

	function no_items()
	{
		_e('No connections found.');
	}

	function get_bulk_actions()
	{
		return array();
	}

	function get_hidden_columns() {
		return array(
			'collection_id' => 'Collection ID',
			'id' => 'id'
		);
	}
	
	function get_columns()
	{
		return array(
			'nick_name' => 'Nick Name',
			'account_id' => 'Account ID',
			'account_type' => 'Account Type',
			'collection_name' => 'Collection',
			'private_key' => 'Private Key',
			'last_updated' => 'Last Updated',
			'sync' => ''
		);
	}

	function get_sortable_columns()
	{
		/*return array(
			'nick_name' => 'nick_name'
		);*/
		return array();
	}

	function get_column_info()
	{
        $sortable = array();
		foreach ($this->get_sortable_columns() as $id => $data)
		{
			$sortable[$id] = array($data, false);
		}

		return array(
			$this->get_columns(),
			$this->get_hidden_columns(),
			$sortable);
	}
	
	function print_column_headers($with_id = true)
	{
		if ($with_id)
		{
			parent::print_column_headers($with_id);
		}
	}

	function display_rows()
	{
		global $bib_query;
		$alt = 0;
		$columns = $this->get_columns();
		$hidden_columns = $this->get_hidden_columns();
		foreach ($this->items as $row)
		{
			$rowclass = ( $alt++ % 2 ) ? '' : 'alternate';

			$this->display_row($row, $rowclass, $columns, $hidden_columns);
		}
	}
	
	function display_row($row, $rowclass='', $columns=null, $hidden_columns=null)
	{
		if (!$columns)
		{
			$columns = $this->get_columns();
		}
		
		if (!$hidden_columns)
		{
			$hidden_columns = $this->get_hidden_columns();
		}
		
		?><tr id='zotero_account-<?php echo $row->id; ?>' class='<?php echo $rowclass; ?> iedit' valign="top"><?php
				
		foreach ( $columns as $column_name=>$column_display_name ) {
			$class = "class=\"$column_name column-$column_name\"";

			$style = '';
			if ( in_array($column_name, $hidden_columns) ) {
				$style = 'style="display:none;"';
			}

			$attributes = "$class $style";

			switch ($column_name) {

				case 'nick_name':
					echo "<td $attributes>$row->nick_name";
					echo "</td>";
					break;

				case 'private_key':
					echo "<td $attributes>$row->private_key</td>";
					break;

				case 'account_id':
					echo "<td $attributes>$row->account_id</td>";
					break;

				case 'account_type':
					echo "<td $attributes>$row->account_type</td>";
					break;

				case 'collection_name':
					echo "<td $attributes>$row->collection_name</td>";
					break;

				case 'collection_id':
					echo "<td $attributes>$row->collection_id</td>";
					break;

				case 'last_updated':
					echo "<td $attributes>$row->last_updated</td>";
					break;

				case 'sync':
					echo "<td><button class='sync-now submit button' value='$row->id'>Sync</button>";
					echo "<button class='delete submit button' value='$row->id'>Delete</button></td>";
					break;
			}
		}

		echo "</tr>";
	}

}
?>
