<?php

function bibliplug_change_ref_type()
{
	$bib_id = intval($_POST['bib_id']);
	global $bib_query, $bib_template;
	
	if ($bib_id != -1)
	{
		$bib = $bib_query->get_reference(array('id' => $bib_id));
	}
	
	$type_id = intval($_POST['new_type_id']);
	$fields = $bib_query->get_fields_by_type_id($type_id);
	
	$bib_template->print_details_table($bib, $type_id, $fields);
	
	die();
}
function bibliplug_user_extra()
{
	global $current_user;
	if (($_GET['uid'] == $current_user->ID || is_super_admin($current_user->ID)) && is_numeric($_GET['uid']))
	{
		$uid = $_GET['uid'];
	}
	else
	{
		$uid = $current_user->ID;
	}

	// ie9 sometimes cache this result, which is not a desired behavior for this ajax call.
	header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
	$userdata = get_userdata($uid);

	$response = new WP_Ajax_Response();
	$response->add(array(
		'what' => 'name_extra',
		'data' => 
			"<tr>
				<th><label for='middle_name'>" . _('Middle Name') . "</label></th>
				<td><input name='middle_name' type='text' id='middle_name' class='regular-text' value='$userdata->middle_name'/></td>
			</tr>
			<tr>
				<th><label for='prefix'>" . _('Prefix') . "</label></th>
				<td><input name='prefix' type='text' id='prefix' class='regular-text' value='$userdata->prefix' /></td>
			</tr>"
	));

	$response->add(array(
		'what' => 'about',
		'data' =>
			"<tr>
				<th><label for='researcher_id'>ResearcherID</label></th>
				<td>
					<input name='researcher_id' type='text' id='researcher_id' class='regular-text' value='$userdata->researcher_id' /><br />
					<span class='description'>ResearcherID is an unique identifier for scientific authors introduced by <a href='http://thomsonreuters.com/'>Thomson Reuters.</a></span>
				</td>
			</tr>
			<tr>
				<th><label for='dai'>DAI</label></th>
				<td>
					<input name='dai' type='text' id='dai' class='regular-text' value='$userdata->dai' /><br />
					<span class='description'>The Digital Author Identification (DAI) is a unique national number for every author active within the Dutch research system.</span>
				</td>
			</tr>
			<tr>
				<th><label for='affiliation'>Title and Affiliation</label></th>
				<td><textarea id='affiliation' name='affiliation' rows='5'>$userdata->affiliation</textarea></td>
			</tr>
			<tr>
				<th><label for='author_bio'>Author Bio</label></th>
				<td>
					<a href='#' class='plain'>HTML</a>
					<a href='#' class='visual active'>Visual</a>
					<textarea id='author_bio' name='author_bio' rows='5' cols='30'>$userdata->author_bio</textarea>
					<br/>
					<span class='description'>Share a little biographical information to fill out your profile. This may be shown publicly.</span>
				</td>
			</tr>"
	));
	$response->send();
}

function bibliplug_add_connection()
{
	check_admin_referer('bibliplug-zotero-add');

	$connection = array(
		'nick_name' => $_POST['nick-name'],
		'account_id' => $_POST['account-id'],
		'account_type' => strtolower($_POST['account-type']) . 's',
		'private_key' => $_POST['private-key'],
		'collection_name' => $_POST['collection-name']
	);

	if ($connection['collection_name'])
	{
		require_once(BIBLIPLUG_DIR . 'phpZotero/phpZotero.php');
		$zotero = new phpZotero($connection['private_key']);
		
		$result = $zotero->getCollections(
				$connection['account_id'],
				array('content' => 'json'),
				$connection['account_type']);
		if ($zotero->getResponseStatus() == 200)
		{
			$doc = $zotero->getDom($result);
			$xpath = new DOMXPath($doc);
			$xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');
			$entries = $xpath->query("//atom:entry[atom:title='{$connection['collection_name']}']");

			if ($entries->length > 0)
			{
				$collection_id = $entries->item(0)->getElementsByTagNameNS('http://zotero.org/ns/api', 'key')->item(0)->nodeValue;
				$connection['collection_id'] = $collection_id;
			}
			else
			{
				die("Cannot find collection with name '{$connection['collection_name']}'");
			}
		}
		else
		{
			die($result);
		}
	}

	global $bib_query;
	$connection_id = $bib_query->insert_zotero_connection($connection);
	unset($_POST['submit']);
	
	require_once('class-bibliplug-connection-table.php');

	$connection_manager = new Bibliplug_Connection_List_Table();
	$connection['id'] = $connection_id;
	die($connection_manager->display_row((object)$connection));
}

function bibliplug_sync_zotero()
{
	global $bib_query;

	// ie9 sometimes cache this result, which is not a desired behavior for this ajax call.
	header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );

	if (isset($_GET['bibliplug_sync_zotero']) && $_GET['bibliplug_sync_zotero'])
	{
		// check if automatic syncronation is turned on.
		if (get_option('bibliplug_zotero_sync_setting') == 'automatic')
		{
			// this is an automatic sync
			$connections = $bib_query->get_zotero_connections();
			$auto_sync = true;

			if (empty($connections))
			{
				die("<p>No connection to sync.</p>");
			}
		}
		else
		{
			die("<p>Please change bibliplug sync schedule to \"automatic\" before using this feature.</p>");
		}
	}
	else if (isset($_GET['id']) && $_GET['id'])
	{		
		$id = $_GET['id'];
		$connection = $bib_query->get_zotero_connection($id);
		
		if (!$connection)
		{
			die("<p>Invalid connection id.</p>");
		}

		$connections = array($connection);
	}
	
	// now try to connect to zotero.
	require_once(BIBLIPLUG_DIR . 'phpZotero/phpZotero.php');
	$message = '';

	foreach($connections as $connection)
	{
		$sync_time = gmdate("Y-m-d\TH:i:s\Z");
		$message .= "<h3>$sync_time: Syncing connection \"$connection->nick_name\"</h3>";
		$zotero = new phpZotero($connection->private_key);
		$headers = array();

		if ($connection->last_updated)
		{
			$connection_last_synced = strtotime($connection->last_updated);
			$headers[] = "If-Modified-Since: $connection->last_updated";
		}
		
		$parameters = array('content' => 'json');

		// for paging through results.
		$start_position = $connection->start;
		$totalResults = 0;
		do
		{
			if ($start_position)
			{
				$parameters['start'] = $start_position;
				$sync_time = $connection->sync_time;
			}
			
			if ($connection->collection_id)
			{
				$xml = $zotero->getCollectionItems(
						$connection->account_id,
						$connection->collection_id,
						$parameters,
						$connection->account_type,
						$headers);
			}
			else
			{
				$xml = $zotero->getItemsTop(
						$connection->account_id,
						$parameters,
						$connection->account_type);
			}
	
			if ($zotero->getResponseStatus() == 304)
			{
				$bib_query->update_zotero_connection($connection->id, array('last_updated' => $sync_time), array('%s'));
				$message .= "<p class='bib_warning'>&nbsp;&nbsp;&nbsp;&nbsp;No change since $connection->last_updated.</p>";
				// skip the do-while loop and continue to the next connection.
				continue 2;
			}
	
			if ($zotero->getResponseStatus() != 200)
			{
				$message .= "<p class='bib_error'>&nbsp;&nbsp;&nbsp;&nbsp;Invalid connection. Please check your connection parameters.</p>$result";
				// skip the do-while loop and continue to the next connection.
				continue 2;
			}
			
			//die($xml);
		
			$doc = $zotero->getDom($xml);
			$xpath = new DOMXPath($doc);
			$xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');
	
			$updated_root = $doc->getElementsByTagName('updated')->item(0)->nodeValue;
	
			// if zotero api getItemTop does not work with If-Modified-Since. Do a manual check.
			if ($connection_last_synced && $connection_last_synced >= strtotime($updated_root))
			{
				$bib_query->update_zotero_connection($connection->id, array('last_updated' => $sync_time), array('%s'));
				$message .= "<p class='bib_warning'>&nbsp;&nbsp;&nbsp;&nbsp;No change since $connection->last_updated.</p>";
				// skip the do-while loop and continue to the next connection.
				continue 2;
			}
			
			if (!$start_position) {
				// first time in do loop.
				$totalResults = $doc->getElementsByTagNameNS('http://zotero.org/ns/api', 'totalResults')->item(0)->nodeValue;
				$message .= "<p><strong>Number of references found in top-level items: $totalResults</strong></p>";
			}
			
			$entries = $xpath->query("//atom:entry");		
			$message .= "<p><strong>Number of references retrieved: $entries->length</strong></p>";
			
			// set up the next page request.
			if ($start_position + $entries->length < $totalResults)
			{
				$start_position += $entries->length;
			}
			else
			{
				// we are done pading. get out of the do-while loop.
				$start_position = 0;
			}
	
			require_once(BIBLIPLUG_DIR . '/format_helper/import_format_helper.php');
			require_once(ABSPATH . WPINC . '/class-json.php');
			$parser_helper = new import_format_helper();
			$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
	
			$authors_format = array('%s', '%s', '%s', '%d', '%d', '%d');
			$style_helper = new display_format_helper();
			$num_added = 0;
			$num_updated = 0;
			$num_deleted = 0;
			$num_skipped = 0;
			$num_errors = 0;
			$result = '';
	
			foreach ($entries as $entry)
			{
				$content_node = $entry->getElementsByTagName('content')->item(0);
				$content = $content_node->nodeValue;
				$etag = $content_node->getAttribute('etag');
				$title = $entry->getElementsByTagName('title')->item(0)->nodeValue;
				$item_key = $entry->getElementsByTagNameNS('http://zotero.org/ns/api', 'key')->item(0)->nodeValue;
				$published = strtotime($entry->getElementsByTagName('published')->item(0)->nodeValue);
				$updated = strtotime($entry->getElementsByTagName('updated')->item(0)->nodeValue);
	
				$add = true;
				if ($connection_last_synced)
				{
					// if this connection has synced before, need to see whether the new reference
					// needs to be add, updated, or ignored.
					if ($updated <= $connection_last_synced)
					{
						// no change. skip through.
						$result .= "<p>No changes for reference '$title'.</p>";
						$num_skipped++;
						continue;
					}
	
					// now we know this reference is either added or updated after last time we synced.
					$add = $published > $connection_last_synced;
				}
	
				$reference_raw = $json->decode($content);
				$deleted = $reference_raw['deleted'];
				$authors = array();
				$field_values = array('zotero_key' => $item_key, 'zotero_etag' => $etag);
				$field_formats = array('%s', '%s');
	
				try
				{
					if ($deleted)
					{
						if ($connection_last_synced)
						{
							$bib_query->delete_bibliography_by_zotero_key($item_key);
							$result .= "<p>&nbsp;&nbsp;&nbsp;&nbsp;Deleted reference '$title'.</p>";
							$num_deleted++;
						}
						else
						{
							$result .= "<p>&nbsp;&nbsp;&nbsp;&nbsp;Skip deleted reference '$title'.</p>";
							$num_skipped++;
						}
					}
					else
					{
						$parser_helper->parse_zotero_entry($reference_raw, $field_values, $field_formats, $authors);
	
						if ($add)
						{
							$bib_id = $bib_query->insert_bibliography($field_values, $field_formats, $authors, $authors_format);
							wp_set_object_terms($bib_id, $connection->nick_name, 'ref_cat');
	
							//$field_values['id'] = $bib_id;
							$result .= "<p>&nbsp;&nbsp;&nbsp;&nbsp;Added reference '$title'.</p>";
							$num_added++;
						}
						else
						{
							$bib = $bib_query->get_reference(array('zotero_key' =>$item_key), array('id'));
	
							if ($bib)
							{
								$bib_id = $bib->id;
								$bib_query->update_bibliography($bib_id, $field_values, $field_formats);
								$bib_query->delete_creators_from_bib_id($bib_id);
								$authors_format[] = '%d';
								foreach ($authors as $author)
								{
									$author['bib_id'] = $bib_id;
									$bib_query->insert_creator($author, $authors_format);
								}
							}
							else
							{
								$bib_id = $bib_query->insert_bibliography($field_values, $field_formats, $authors, $authors_format);
								wp_set_object_terms($bib_id, $connection->nick_name, 'ref_cat');
							}
	
							$result .= "<p>&nbsp;&nbsp;&nbsp;&nbsp;Updated reference '$title'.</p>";
							$num_updated++;
						}
					}
					//$fields = $bib_query->get_fields_by_type_id($field_values['type_id']);
					//print $style_helper->display_chicago_style((object)$field_values, $fields);
				}
				catch(exception $e)
				{
					$error_message = $e->getMessage();
					
					if (BIBLIPLUG_DEBUG)
					{
						$result .= $error_message;
					}
					
					if (strpos($error_message, "Duplicate entry") !== false)
					{
						// Retrieve the duplicate reference, and update ref category instead.						
						if (strpos($error_message, "zotero_key")!== false)
						{
							$bib = $bib_query->get_reference(array('zotero_key' =>$item_key), array('id'));
						}
						else if (strpos($error_message, "unique_hash")!== false)
						{
							$bib = $bib_query->get_reference(array('unique_hash' => $field_values['unique_hash']), array('id'));
						}
						
						if (bib)
						{
							wp_set_object_terms($bib->id, $connection->nick_name, 'ref_cat', true);
							$result .= "<p class='bib_warning'>&nbsp;&nbsp;&nbsp;&nbsp;Notice: Reference '$title' exists in the database. Added category '$connection->nick_name' to reference instead.</p>";
							$num_updated++;
						}
						else
						{
							$result .= "<p class='bib_warning'>&nbsp;&nbsp;&nbsp;&nbsp;Warning: failed to insert reference '$title' because there's a duplicate entry.</p>";
							$num_skipped++;
						}
					}
					else
					{
						$result .= "<p class='bib_error'>&nbsp;&nbsp;&nbsp;&nbsp;Error: failed to " . (($add) ? "insert" : "update") . " reference '$title'.</p>";
						$num_errors++;
					}
				}
			}
		} while($auto_sync && $start_position > 0);

		// now update the last_updated timestamp
		
		if ($start_position > 0)
		{
			$bib_query->update_zotero_connection($connection->id, array('start' => $start_position, 'sync_time' => $sync_time), array('%d', '%s'));
			$message .= "<p class='bib_warning'><strong>There are too many items in this connection that results are broken down to multiple pages.<br/>";
			$message .= "First $start_position items are synchronized. To continue synchronize this connection, click the sync button again.</strong></p>";
		}
		else
		{
			$bib_query->update_zotero_connection($connection->id, array('last_updated' => $sync_time, 'start' => 0, 'sync_time' => ''), array('%s', '%d', '%s'));
			$message .= "<p><strong>Connection '$connection->nick_name' synchronization is finished.</strong></p>";
		}
		
		$message .= "<p>&nbsp;&nbsp;Number of references added: $num_added.</p>";
		$message .= "<p>&nbsp;&nbsp;Number of references updated: $num_updated.</p>";
		$message .= "<p>&nbsp;&nbsp;Number of references deleted: $num_deleted.</p>";
		$message .= "<p>&nbsp;&nbsp;Number of references skipped: $num_skipped.</p>";
		$message .= "<p>&nbsp;&nbsp;Number of references failed: $num_errors.</p>";
		$message .= $result;
	} // foreach($connections as $connection)

	if ($auto_sync || WP_DEBUG || BIBLIPLUG_DEBUG)
	{
		die($message);
	}
	else
	{
		// there should be only one connection to sync in this case.
		$response = new WP_Ajax_Response();
		$response->add(array(
			'what' => 'ts',
			'data' => ($start_position > 0) ? $connection->last_updated : $sync_time
		));
		$response->add(array(
			'what' => 'message',
			'data' => $message
		));
		$response->send();
	}
}

function bibliplug_delete_connection()
{
	global $bib_query;
	if (isset($_GET['id']) && $_GET['id'])
	{
		$id = $_GET['id'];
		$connection = $bib_query->get_zotero_connection($id);
	}

	if (!$connection)
	{
		die("<p>Invalid connection id.</p>");
	}

	$term = term_exists($connection->nick_name, 'ref_cat');
	if (!empty($term))
	{
		$references = $bib_query->get_references_by_taxonomy($connection->nick_name, 'ref_cat', false);
		print "<p>". count($references) . "refernece(s) found under nick name \"$connection->nick_name\"</p>";
		$num_deleted = 0;
		$num_skipped = 0;
		if ($references)
		{
			foreach($references as $reference)
			{
				$ref_terms = wp_get_object_terms($reference->id, 'ref_cat');

				if (count($ref_terms) <= 1)
				{
					// this reference only belongs to this category, okay to delete.
					$bib_query->delete_bibliography($reference->id, false);
					print "<p>&nbsp;&nbsp;&nbsp;&nbsp;Deleted \"$reference->title\".</p>";
					$num_deleted++;
				}
				else
				{
					// this reference also belongs to anther category (connection), only delete the connection association.
					$ts = array();
					foreach($ref_terms as $ref_term)
					{
						if ($ref_term->name != $connection->nick_name)
						{
							$ts[] = $ref_term->name;
						}
					}

					wp_set_object_terms($reference->id, $ts, 'ref_cat');
					print "<p>Reference \"$reference->title\" also belongs to category " . implode(", ", $ts) .
							". $connection->nick_name is removed from the reference.</p>";
					$num_skipped++;
				}
			}

			print "<p>Number of reference deleted: $num_deleted.</p>";
			print "<p>Number of reference skipped: $num_skipped.</p>";
		}

		wp_delete_term($term['term_id'], 'ref_cat');
	}

	$bib_query->delete_zotero_connection($connection->id);
	die("<p>Deleted connection '$connection->nick_name'.</p>");
}

function bibliplug_export() 
{
	if (!current_user_can('edit_posts'))
	{
		wp_die('You do not have sufficient permissions to manage bibliography.');
	}

	if (isset($_POST['action']) && $_POST['action'] == 'export')
	{
		global $bib_query;
		if (!defined(BIB_LAST_NAME_FORMAT))
		{
			define('BIB_LAST_NAME_FORMAT', get_option('bibliplug_last_name_format'));
		}

		$file_name = 'references';

		if (isset($_POST['last_name']) && $_POST['last_name'] != "")
		{
			$last_name = $_POST['last_name'];
			$file_name .= "-$last_name";
		}

		if (isset($_POST['first_name']) && $_POST['first_name'] != "")
		{
			$first_name = $_POST['first_name'];
			$file_name .= "-$first_name";
		}


		if (isset($_POST['year']) && $_POST['year'] != "0")
		{
			$year = $_POST['year'];
			$file_name .= "-$year";
		}

		if (isset($_POST['type']) && $_POST['type'] != "All")
		{
			$type = $_POST['type'];
			$file_name .= "-" . str_replace(' ', '-', $type);
		}

		header("Content-Disposition: attachment; filename=$file_name.txt");

		if (!class_exists('export_format_helper'))
		{
			require_once(BIBLIPLUG_DIR . 'format_helper/export_format_helper.php');
		}

		$export_format_helper = new export_format_helper();

		foreach ($bib_query->get_references($last_name, $first_name, $year, $type) as $reference)
		{
			echo $export_format_helper->write_ris_reference($reference);
		}
	}
		
	die();
}
?>
