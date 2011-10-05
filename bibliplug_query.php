<?php

// Utility class to handle most common bibliplug queries.
class bibliplug_query {

	/* bibliplug schema tables */
	private $types_table;
	private $fields_table;
	private $typefields_table;
	private $creatortypes_table;
	private $typecreatortypes_table;
	
	/* bibliplug data tables */
	private $bibliography_table;
	private $creators_table;
	private $zoteroconnections_table;

	public function bibliplug_query()
	{
		global $wpdb;
		$this->types_table = $wpdb->prefix . 'bibliplug_types';
		$this->fields_table = $wpdb->prefix . 'bibliplug_fields';
		$this->typefields_table = $wpdb->prefix . 'bibliplug_typefields';
		$this->creatortypes_table = $wpdb->prefix . 'bibliplug_creatortypes';
		$this->typecreatortypes_table = $wpdb->prefix . 'bibliplug_typecreatortypes';
		$this->bibliography_table = $wpdb->prefix . 'bibliplug_bibliography';
		$this->creators_table = $wpdb->prefix . 'bibliplug_creators';
		$this->zoteroconnections_table = $wpdb->prefix . 'bibliplug_zoteroconnections';
	}

	private function run_results_query($query) {
		global $wpdb;
		return $wpdb->get_results($query);
	}
	
	private function run_var_query($query) {
		global $wpdb;
		return $wpdb->get_var($query);
	}
	
	private function run_row_query($query, $format=OBJECT) {
		global $wpdb;
		return $wpdb->get_row($query, $format);
	}
	
	public function get_distinct_years() {
		$query = "SELECT distinct publish_year
			FROM $this->bibliography_table
			ORDER BY publish_year DESC";
		return $this->run_results_query($query);
	}
	
	public function get_distinct_types() {
		$query = "SELECT distinct t.name
			FROM $this->bibliography_table as b
			INNER JOIN $this->types_table AS t ON b.type_id = t.id
			ORDER BY t.name ASC";
		return $this->run_results_query($query);
	}
	
	
	public function get_types() {
		$query = "SELECT *
			FROM $this->types_table ORDER BY name";
		return $this->run_results_query($query);
	}
	
	public function get_type_row_by_id($type_id) {
		$query = "SELECT *
			FROM $this->types_table as t
			WHERE t.id = $type_id";
		return $this->run_row_query($query);
	}
	
	public function get_zotero_connection($id)
	{
		$query = "SELECT *
			FROM $this->zoteroconnections_table as z
			WHERE z.id = $id";
		return $this->run_row_query($query);
	}

	public function get_zotero_connections()
	{
		$query = "SELECT *
			FROM $this->zoteroconnections_table";
		return $this->run_results_query($query);
	}

	public function get_fields_by_type_id($type_id=1) {
		$query = 'SELECT f.internal_name, f.name, f1.name AS mapped_name
			FROM '.$this->typefields_table.' AS tf
			INNER JOIN '.$this->fields_table.' AS f ON tf.field_id = f.id
			LEFT OUTER JOIN '.$this->fields_table.' AS f1 ON tf.display_field_id = f1.id
			WHERE
				tf.type_id = '.$type_id.'
			ORDER BY
				tf.order_index';
		// print '<div class = "query"><font color=blue>get_fields_by_type_id</font><br>'.$query.'</div>';
		return $this->run_results_query($query);
	}
	
	public function get_creator_types_by_type_id($type_id=1) {
		$query = 'SELECT c.id, c.name
			FROM '.$this->creatortypes_table.' as c
			INNER JOIN '.$this->typecreatortypes_table.' as tc ON c.id = tc.creatortype_id
			WHERE tc.type_id = '.$type_id.'
			ORDER BY tc.order_index';
		// print '<div class = "query"><font color=red>get_creator_types_by_type_id</font><br>'.$query.'</div>';
		return $this->run_results_query($query);
	}

	public function get_creator_types_by_bib_id($bib_id=0) {
		if ($bib_id)
		{
			$query = 'SELECT c.id, c.name
				FROM '.$this->creatortypes_table.' as c
				INNER JOIN '.$this->typecreatortypes_table.' as tc ON c.id = tc.creatortype_id
				INNER JOIN '.$this->bibliography_table.' as b ON tc.type_id = b.type_id
				WHERE b.id = '.$bib_id.'
				ORDER BY tc.order_index';
			// print '<div class = "query"><font color=red>get_creator_types_by_bib_id</font><br>'.$query.'</div>';
			return $this->run_results_query($query);
		}
		else
		{
			return $this->get_creator_types_by_type_id();
		}
	}
	
	public function get_the_creator_type_id($type_id) {
		$query = 'SELECT c.id 
				     FROM '.$this->creatortypes_table.' as c,
					  	   '.$this->typecreatortypes_table.' as tc
				     WHERE c.id = tc.creatortype_id AND tc.type_id = '.$type_id.' AND tc.order_index = 0';
		// print '<div class = "query"><font color=green>get_the_creator_type_id</font><br>'.$query.'</div>';
		return $this->run_var_query($query);
	}
	
	public function get_reference($where=array(), $select=array(), $format=OBJECT) {
		
		if (empty($select))
		{
			$view = "*";
		}
		else 
		{			
			$view = "`" . implode("`, `", $select) . "`";			
		}
		
		$query = "SELECT $view FROM $this->bibliography_table";
		
		if (!empty($where))
		{
			foreach($where as $name =>$value)
			{
				$conditions[] = "`$name` = '$value'";
			}
			
			$query .= ' WHERE ' . join(' AND ', $conditions);
		}
		
		return $this->run_row_query($query, $format);
	}

	public function get_references_by_taxonomy($tax_name, $tax_type, $full_data=true)
	{
		global $wpdb;
		$query = "SELECT " . (($full_data) ? 'b.*' : 'b.id, b.title') ."
			FROM $this->bibliography_table as b
			INNER JOIN $wpdb->term_relationships as tr on b.id = tr.object_id
			INNER JOIN $wpdb->term_taxonomy as tt on tr.term_taxonomy_id = tt.term_taxonomy_id
			INNER JOIN $wpdb->terms as t on tt.term_id = t.term_id
			WHERE tt.taxonomy = '$tax_type' AND t.name = '$tax_name'";
		// print $query;
		return $this->run_results_query($query);
	}
	
	public function get_references($last_name='', $first_name ='', $year='', $type='', $tax_name='', $tax_type='')
	{
		global $wpdb;
		$query = 'SELECT d.*';
		
		if (get_option('bibliplug_last_name_format') == 'english')
		{
			$query .= ', CASE WHEN c.prefix IS NULL THEN c.last_name ELSE CONCAT(c.prefix, c.last_name) END AS ln';
		} 
		else
		{
			$query .= ', c.last_name AS ln';
		}
		
		$query .= ' FROM '.$this->bibliography_table.' AS d';
		$query .= ' INNER JOIN '.$this->types_table.' AS t ON d.type_id = t.id';
		$query .= ' INNER JOIN '.$this->creators_table.' AS c ON c.bib_id = d.id AND c.order_index = 0';

		if ($last_name || $first_name)
		{
			$query .= ' INNER JOIN '.$this->creators_table." AS c1 ON c1.bib_id = d.id";
			if ($last_name) {
				$conditions[] = "c1.last_name = '$last_name'";
			}
				
			if ($first_name) {
				$conditions[] = "c1.first_name = '$first_name'";
			}
		}

		if ($tax_name && $tax_type)
		{
			$query .= " INNER JOIN $wpdb->term_relationships as tr on d.id = tr.object_id";
			$query .= " INNER JOIN $wpdb->term_taxonomy as tt on tr.term_taxonomy_id = tt.term_taxonomy_id";
			$query .= " INNER JOIN $wpdb->terms as wt on tt.term_id = wt.term_id";

			$conditions[] = "tt.taxonomy = '$tax_type' AND wt.name = '$tax_name'";
		}
		
		if ($type)
		{
			// publications is a super set.
			if (strcasecmp($type, 'publications') == 0)
			{
				$conditions[] = "t.name <> 'conference paper'";
				$conditions[] = "t.name <> 'presentation'";
			} 
			else if (strcasecmp($type, 'presentations') == 0)
			{
				$conditions[] = "(t.name = 'conference paper' OR t.name = 'presentation')";
			} 
			else
			{
				$conditions[] = "t.name = '$type'";
			}
		}
		
		if ($year)
		{
			$conditions[] = "d.publish_year = '$year'";
		}
		
		if ($conditions)
		{
			$query .= ' WHERE ';
			$query .= join(' AND ', $conditions);
		}
				
		if ($last_name || $first_name)
		{
			$query .= ' GROUP BY d.id';
			$query .= ' ORDER BY d.publish_year DESC, ln, d.type_id';
		} 
		else
		{
			$query .= ' ORDER BY ln, d.publish_year DESC, d.type_id';
		}
		
		// print '<div class = "query">'.$query.'</div>';
		return $this->run_results_query($query);
	}
	
	public function get_references_by_page($page, $user_id=0, $search='', $orderby='', $order='') 
	{
		
		$query = 'SELECT d.id, d.title, d.publish_year AS year, d.type_id, t.name AS type';
		
		if (get_option('bibliplug_last_name_format') == 'english') 
		{
			$query .= ', CASE WHEN c.prefix IS NULL THEN c.last_name ELSE CONCAT(c.prefix, c.last_name) END AS ln';
		} 
		else 
		{
			$query .= ', c.last_name AS ln';
		}
		
		$query .= ' FROM '.$this->bibliography_table.' AS d';
		$query .= ' INNER JOIN '.$this->types_table.' AS t ON d.type_id = t.id';
		$query .= ' LEFT OUTER JOIN '.$this->creators_table.' AS c ON c.bib_id = d.id AND c.order_index =1';
		
		if ($user_id || $search)
		{
			$query .= ' INNER JOIN '.$this->creators_table.' AS c1';
			$conditions[] = 'd.id = c1.bib_id';
		}
		
	 	if ($user_id) 
        {
			$user_info = get_userdata($user_id);
			$conditions[] = 'c1.first_name = "'.$user_info->first_name.'"';
			$conditions[] = 'c1.last_name = "'.$user_info->last_name.'"';
		}
		
		if ($search) 
        {		
			global $wpdb;
			$s = $wpdb->escape($search);
			$conditions[] = '(
				c1.first_name LIKE "%'.$s.'%" OR
				c1.last_name LIKE "%'.$s.'%" OR
				t.name LIKE "%'.$s.'%" OR
				d.title LIKE "%'.$s.'%" OR
				d.publication_title LIKE "%'.$s.'%" OR
				d.publish_year LIKE "%'.$s.'%" OR
				d.keywords LIKE "%'.$s.'%")';
		}
		
		if ($conditions) 
        {
			$query .= ' WHERE ';
			$query .= join(' AND ', $conditions);
			$query .= ' GROUP BY d.id';
		}
		
        if ($orderby)
        {
            $query .= " ORDER BY $orderby $order";
        }
        else
        {
            $query .= ' ORDER BY ln, d.publish_year DESC';
        }        
		
		if ($page)
        {
			$limit = get_option('bibliplug_page_size');
			$query .= ' LIMIT '.(($page - 1) * $limit).', '.$limit;
		}
		
		// print($query);
		return $this->run_results_query($query);
	}
	
	public function get_rows($user_id=0, $search='') {
		global $wpdb;
		$query = 'SELECT COUNT(DISTINCT d.id)
				    FROM '.$this->bibliography_table.' AS d';
		
		if ($user_id || $search) {
			$query .= ', '.$this->creators_table.' as c1, '.$this->types_table.' AS t';
			$conditions[] = 'd.id = c1.bib_id';
			$conditions[] = 'd.type_id = t.id';
		}
		
	 	if ($user_id) {
			$user_info = get_userdata($user_id);
			$conditions[] = 'c1.first_name = "'.$user_info->first_name.'"';
			$conditions[] = 'c1.last_name = "'.$user_info->last_name.'"';
		}
		
		if ($search) {
			$s = $wpdb->escape($search);
			$conditions[] = '(
				c1.first_name LIKE "%'.$s.'%" OR
				c1.last_name LIKE "%'.$s.'%" OR
				t.name LIKE "%'.$s.'%" OR
				d.title LIKE "%'.$s.'%" OR
				d.publication_title LIKE "%'.$s.'%" OR
				d.publish_year LIKE "%'.$s.'%" OR
				d.keywords LIKE "%'.$s.'%")';
		}
		
		if ($conditions) {
			$query .= ' WHERE ';
			$query .= join(' AND ', $conditions);
		}
		
		// print($query);
		$this->found_rows = $wpdb->get_var($query);
		return $this->found_rows;
	}

	public function get_creators($bib_id, $creator_type_id=0) {
		global $wpdb;
		$query = 'SELECT c.*, u.id AS user_id
				    FROM '.$this->creators_table.' AS c
					LEFT OUTER JOIN '.$wpdb->users.' AS u ON u.display_name = CONCAT(c.first_name, " ", c.last_name)
				    WHERE c.bib_id = '.$bib_id;
		
		if ($creator_type_id) {
			$query .= ' AND c.creator_type_id = '.$creator_type_id;
			$query .= ' ORDER BY c.order_index';
		} else {
			$query .= ' ORDER BY c.order_index, c.creator_type_id';
		}
		
		// print '<div class = "query"><font color=green>get_creators</font><br>'.$query.'</div>';
		return $this->run_results_query($query);
	}
	
	public function get_editors($bib_id) {
		return $this->get_creators($bib_id, 3);
	}
	
	public function get_primary_creators($bib_id, $type_id) {
		global $wpdb;
		$query = 'SELECT first_name, prefix, last_name, u.id AS user_id
					FROM '.$this->creators_table.' AS c
					INNER JOIN '.$this->typecreatortypes_table.' AS tc ON c.creator_type_id = tc.creatortype_id
					LEFT OUTER JOIN '.$wpdb->users.' AS u ON u.display_name = CONCAT(c.first_name, " ", c.last_name)
					WHERE tc.type_id = '.$type_id.' AND c.bib_id = '.$bib_id.' AND tc.order_index = 0
				    ORDER BY c.order_index';
		// print '<div class = "query"><font color=red>get_primary_creators</font><br>'.$query.'</div>';
		return $this->run_results_query($query);
	}
		
	public function get_creator($creator_id) {
		$query = 'SELECT order_index, creator_type_id, first_name, middle_name, prefix, last_name
					FROM '.$this->creators_table.'
					WHERE id = '.$creator_id;
		return $this->run_row_query($query, ARRAY_A);
	}
	
	public function delete_bibliography($bib_id, $clean_tax=true) {
		global $wpdb;
		if ($clean_tax)
		{
			wp_delete_object_term_relationships($bib_id, array('ref_tag', 'ref_cat'));
		}
		$query = 'DELETE FROM '.$this->bibliography_table.'
				    WHERE id = '.$bib_id;
		$wpdb->query($query);
	}

	public function delete_bibliography_by_zotero_key($zotero_key)
	{
		global $wpdb;
		wp_delete_object_term_relationships($bib_id, array('ref_tag', 'ref_cat') );
		$query = 'DELETE FROM '.$this->bibliography_table.'
				    WHERE zotero_key = "'.$zotero_key .'"';
		$wpdb->query($query);
	}

	public function delete_zotero_connection($connection_id)
	{
		global $wpdb;
		$query = "DELETE FROM $this->zoteroconnections_table
			WHERE id = $connection_id";
		$wpdb->query($query);
	}
	
	public function delete_creator($creator) {
		global $wpdb;
		$query = 'DELETE FROM '.$this->creators_table.'
				    WHERE id = '.$creator['id'];
		$wpdb->query($query);

		// now fix any creator_author index that's greater than the one just deleted.
		$query = 'UPDATE '.$this->creators_table.'
					SET order_index = order_index -1
					WHERE bib_id = '.$creator['bib_id'].' AND order_index > '.$creator['order_index'];
		$wpdb->query($query);
	}

	public function delete_creators_from_bib_id($bib_id)
	{
		global $wpdb;
		$wpdb->query("DELETE FROM $this->creators_table WHERE bib_id = $bib_id");
	}
	
	public function insert_bibliography(&$field_values, &$field_formats, $creators, $creators_formats) {
		global $wpdb;
		$this->fields_setup($field_values, $field_formats);
		if ($wpdb->insert($this->bibliography_table, $field_values, $field_formats))
		{
			$insert_id = $wpdb->insert_id;
			$creators_formats[] = '%d';	
			// now add authors related to this entry.
			foreach ($creators as $creator) {
				$creator['bib_id'] = $insert_id;
				$wpdb->insert($this->creators_table, $creator, $creators_formats);
			}
		} 
		else
		{
			/*print '<strong>Failed to insert "'.$field_values['title'].'"</strong></br>';
			$wpdb->print_error();
			print '</br>';*/
			throw new exception($wpdb->last_error);
		}
		
		return $insert_id;
	}
	
	public function insert_creator($field_values, $field_formats) {
		global $wpdb;
		if (!$wpdb->insert($this->creators_table, $field_values, $field_formats))
		{
			//print $wpdb->error;
			throw new exception($wpdb->last_error);
		}

		return $wpdb->insert_id;
	}

	public function insert_zotero_connection($connection)
	{
		global $wpdb;
		if (!$wpdb->insert($this->zoteroconnections_table, $connection))
		{
			throw new exception($wpdb->last_error);
		}
		
		return $wpdb->insert_id;
	}
	
	public function update_bibliography($bib_id, &$field_values, &$field_formats) {
		global $wpdb;
		$this->fields_setup($field_values, $field_formats);
		if (!$wpdb->update($this->bibliography_table, $field_values, array('id' => $bib_id), $field_formats, array('%d'))) {
			/*print '<strong>Failed to update "'.$field_values['title'].'"</strong></br>';
			$wpdb->print_error();
			print '</br>';*/
			if ($wpdb->last_error)
			{
				throw new exception($wpdb->last_error);
			}
		}
	}
	
	public function update_creator($creator_id, $field_values, $field_formats) {
		global $wpdb;
		if (!$wpdb->update($this->creators_table, $field_values, array('id' => $creator_id), $field_formats, array('%d'))) {
			/*print '<strong>Failed to update "'.$field_values['first_name'].' '.$field_values['last_name'].'"</strong></br>';
			$wpdb->print_error();
			print '</br>';*/
			throw new exception($wpdb->last_error);
		}
	}

	public function update_zotero_connection($connection_id, $field_values, $field_formats)
	{
		global $wpdb;
		if (!$wpdb->update($this->zoteroconnections_table, $field_values, array('id' => $connection_id), $field_formats, array('%d')))
		{
			//throw new exception($wpdb->last_error);
			print $wpdb->print_error();
		}
	}
	
	public function get_wp_author_ids() {
		global $wpdb;
		$query = "SELECT u.ID 
			FROM $wpdb->users AS u
			INNER JOIN $wpdb->usermeta AS um1 ON u.id = um1.user_id AND um1.meta_key = '{$wpdb->prefix}capabilities'
			INNER JOIN $wpdb->usermeta AS um2 ON u.id = um2.user_id AND um2.meta_key = 'last_name'
			where um1.meta_value LIKE '%author%' OR um1.meta_value LIKE '%editor%'
			ORDER BY um2.meta_value";
		return $wpdb->get_results($query);
	}
	
	public function create_data_tables($drop=false) {	
		global $wpdb;
		
		if ($drop) {			
			$drop_table = 'DROP TABLE IF EXISTS '.$this->creators_table.';';
			$wpdb->query($drop_table);
			$drop_table = 'DROP TABLE IF EXISTS '.$this->bibliography_table.';';
			$wpdb->query($drop_table);
		}
		
		// we are going to split data schema of a bibliography into two tables, 
		// one will be used to store its creators, the other will be used to store everything else.
		// now we are going to create the table that stores all bibliography data except for its creators.
		$create_table = 'CREATE TABLE IF NOT EXISTS '.$this->bibliography_table.' (
			id int UNSIGNED NOT NULL AUTO_INCREMENT,
			type_id int UNSIGNED,
			title varchar(255),
			publication_title varchar(255),
			series varchar(255),
			series_number varchar(32),
			volume int,
			number_of_volumes int,
			issue varchar(32),
			edition varchar(32),
			start_page int,
			end_page int,
			publisher varchar(255),
			publish_date varchar(64),
			publish_year varchar(32),
			short_title varchar(255),
			access_date varchar(32),
			call_number varchar(64),
			section varchar(32),
			ISBN varchar(64),
			ISSN varchar(64),
			DOI varchar(64),
			sub_type varchar(64),
			place varchar(255),	
			conference_name varchar(255),
			journal_abbreviation varchar(64),
			abstract text,
			notes text,
			keywords text,
			running_time varchar(32),
			series_title varchar(255),
			url varchar(2048),
			link1 varchar(2048),
			link2 varchar(2048),
			link3 varchar(2048),
			city_of_publication varchar(255),
			peer_reviewed tinyint(1) UNSIGNED DEFAULT 1,
			zotero_key varchar(64) UNIQUE,
			zotero_etag varchar(64),
			unique_hash varchar(128) UNIQUE,
			PRIMARY KEY (id),
			FOREIGN KEY (type_id) REFERENCES '.$this->types_table.'(id)
			);';
		$wpdb->query($create_table);
	
		// finally, the table with all bibliography creators.
		$create_table = 'CREATE TABLE IF NOT EXISTS '.$this->creators_table.' (
			id int UNSIGNED NOT NULL AUTO_INCREMENT,
			bib_id int UNSIGNED NOT NULL,
			creator_type_id int UNSIGNED NOT NULL,
			first_name varchar(64) NOT NULL,
			last_name varchar(64) NOT NULL,
			middle_name varchar(64),
			prefix varchar(64),
			order_index int UNSIGNED,
			is_secondary tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			FOREIGN KEY (bib_id) REFERENCES '.$this->bibliography_table.'(id) ON DELETE CASCADE,
			FOREIGN KEY (creator_type_id) REFERENCES '.$this->creatortypes_table.'(id)
			);';
		$wpdb->query($create_table);
	}
		
	public function upgrade_schema()
	{
		global $wpdb;
		$this->refresh_schema();
		$version = get_option('bibliplug_db_version', BIBLIPLUG_VERSION);
		if (BIBLIPLUG_VERSION != $version)
		{
			if ($version == '1.1')
			{
				$wpdb->query("ALTER TABLE $this->bibliography_table CHANGE COLUMN `link1` `link1` varchar(2048);");
				$wpdb->query("ALTER TABLE $this->bibliography_table CHANGE COLUMN `link2` `link2` varchar(2048);");
				$wpdb->query("ALTER TABLE $this->bibliography_table CHANGE COLUMN `link3` `link3` varchar(2048);");
				$wpdb->query("ALTER TABLE $this->bibliography_table CHANGE COLUMN `url` `url` varchar(2048);");
				$wpdb->query("ALTER TABLE $this->bibliography_table CHANGE COLUMN `series` `series` varchar(255);");
				
				$wpdb->query("ALTER TABLE $this->zoteroconnections_table ADD `start` int;");
				$wpdb->query("ALTER TABLE $this->zoteroconnections_table ADD `sync_time` varchar(32);");
			}
			else if ($version == '1.2' || $version == '1.2.1')
			{
				// this is used if bibliography data is not dropped during upgrade.
				$wpdb->query("ALTER TABLE $this->bibliography_table ADD `unique_hash` varchar(128) UNIQUE;");
				
				// these two fields are missing if user didn't upgrade from 1.0 to 1.1.
				$wpdb->query("ALTER TABLE $this->zoteroconnections_table ADD `start` int;");
				$wpdb->query("ALTER TABLE $this->zoteroconnections_table ADD `sync_time` varchar(32);");
			}
		}
		
		update_option('bibliplug_db_version', BIBLIPLUG_VERSION);
	}
	
	private function refresh_schema($delete_data=false)
	{
		global $wpdb;
		if ($delete_data) 
		{
			$drop_table = "DROP TABLE IF EXISTS $this->creators_table;";
			$wpdb->query($drop_table);
			$drop_table = "DROP TABLE IF EXISTS $this->bibliography_table;";
			$wpdb->query($drop_table);
			$drop_table = "DROP TABLE IF EXISTS $this->zoteroconnections_table;";
			$wpdb->query($drop_table);
		}
		
		$wpdb->query("SET foreign_key_checks = 0;");
		
		$drop_table = "DROP TABLE IF EXISTS $this->typecreatortypes_table;";
		$wpdb->query($drop_table);
		$drop_table = "DROP TABLE IF EXISTS $this->creatortypes_table;";
		$wpdb->query($drop_table);
		$drop_table = "DROP TABLE IF EXISTS $this->typefields_table;";
		$wpdb->query($drop_table);
		$drop_table = "DROP TABLE IF EXISTS $this->fields_table;";
		$wpdb->query($drop_table);
		$drop_table = "DROP TABLE IF EXISTS $this->types_table;";
		$wpdb->query($drop_table);
		
		$this->create_db();
		
		$wpdb->query("SET foreign_key_checks = 1;");
	}
	
	
	private function create_db()
	{
		global $wpdb;
		
		// table for bibliography types.
		$create_table = 'CREATE TABLE IF NOT EXISTS '.$this->types_table.' (
			id int UNSIGNED NOT NULL,
			name varchar(32) NOT NULL,
			internal_name varchar(32) NOT NULL,
			PRIMARY KEY (id),
			UNIQUE (name)
			);';
		$wpdb->query($create_table);
		
		// table for bibliography fields.
		$create_table = 'CREATE TABLE IF NOT EXISTS '.$this->fields_table.' (
			id int UNSIGNED NOT NULL,
			name varchar(32) NOT NULL,
			internal_name varchar(32) NOT NULL,
			PRIMARY KEY (id),
			UNIQUE (name)
			);';
		$wpdb->query($create_table);
		
		// table to map type and fields
		$create_table = 'CREATE TABLE IF NOT EXISTS '.$this->typefields_table.' (
			type_id int UNSIGNED NOT NULL,
			field_id int UNSIGNED NOT NULL,
			display_field_id int UNSIGNED,
			order_index tinyint UNSIGNED,
			PRIMARY KEY (type_id, field_id),
			FOREIGN KEY (type_id) REFERENCES '.$this->types_table.'(id),
			FOREIGN KEY (field_id) REFERENCES '.$this->fields_table.'(id)
			);';
		$wpdb->query($create_table);
		
		// table for creator types
		$create_table = 'CREATE TABLE IF NOT EXISTS '.$this->creatortypes_table.' (
			id int UNSIGNED NOT NULL,
			name varchar(32) NOT NULL,
			PRIMARY KEY (id),
			UNIQUE (name)
			);';
		$wpdb->query($create_table);
		
		// table to map type and creator types.
		$create_table = 'CREATE TABLE IF NOT EXISTS '.$this->typecreatortypes_table.' (
			type_id int UNSIGNED NOT NULL,
			creatortype_id int UNSIGNED NOT NULL,
			order_index int UNSIGNED, 
			PRIMARY KEY (type_id, creatortype_id),
			FOREIGN KEY (type_id) REFERENCES '.$this->types_table.'(id),
			FOREIGN KEY (creatortype_id) REFERENCES '.$this->creatortypes_table.'(id)
			);';
		$wpdb->query($create_table);

		// table to track zotero accounts and their synchronization status.
		$create_table = 'CREATE TABLE IF NOT EXISTS '.$this->zoteroconnections_table.' (
			id int UNSIGNED NOT NULL AUTO_INCREMENT,
			nick_name varchar(32),
			account_id varchar(32) NOT NULL,
			account_type varchar(32) NOT NULL,
			private_key varchar(32) NOT NULL,
			collection_id varchar(32),
			collection_name varchar(256),
			last_updated varchar(32),
			start int,
			sync_time varchar(32),
			PRIMARY KEY (id)
			);';
		$wpdb->query($create_table);
		
		// now insert data
		$this->populate_schema();
		
		$this->create_data_tables();
	}

	private function populate_schema()
	{
		global $wpdb;
		$data_source = fopen(BIBLIPLUG_DIR.'insert.txt', 'r');
		if ($data_source) {
			while(!feof($data_source)) {
				$record = fgets($data_source);

				// skip empty lines and comments.
				if (trim($record) == '' || substr($record,0,2) == '//') {
					continue;
				}

				$insert_query = sprintf($record, $wpdb->prefix);
				$wpdb->query($insert_query);
			}
		}
		fclose($data_source);
	}
	
	private function fields_setup(&$field_values, &$field_formats)
	{
		if (!$field_values['title'])
		{
			// print_r($field_values);
			throw new exception('Title cannot be empty.');
		}
		
		$hash_value = $field_values['type_id'] . '-' . md5($field_values['title']);
		
		if ($field_values['publication_title'])
		{
			$hash_value .= '-' . md5($field_values['publication_title']);
		}
		
		if ($field_values['conference_name'])
		{
			$hash_value .= '-' . md5($field_values['conference_name']);
		}
		
		$field_values['unique_hash'] = $hash_value;
		$field_formats[] = '%s';
	}
}
?>