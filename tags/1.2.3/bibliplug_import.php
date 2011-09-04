<?php

if (!defined('BIBLIPLUG_DIR')) 
{
	die('Invalid access point.');
}

$title = __('Import references');
$parent_file = 'bibliplug_edit.php';

?>
<div class="wrap">
<?php screen_icon('edit'); ?>
<h2><?php echo esc_html( $title ); ?></h2>

<?php

if (isset($_GET['action'])) 
{
	
	wp_referer_field('bibliplug-import');
	
	if (!class_exists("import_format_helper")) 
	{
		require_once(BIBLIPLUG_DIR.'/format_helper/import_format_helper.php');
	}

	global $current_user, $parser_helper, $bib_query, $wpdb;
	get_currentuserinfo();
	if ($current_user->user_level < 8) 
	{
		wp_die('You are not allowed to import files.');
	}
	
	$file_name = $_FILES['risfile']['name'];
	if ($file_name) 
	{
		$ris_file = fopen($_FILES['risfile']['tmp_name'], 'r');
	}
	
	if ($ris_file) 
	{
		$paser_helper = new import_format_helper();
		$authors = array();
		$field_values = array();
		$field_formats = array();
		$authors_format = array('%s', '%s', '%s', '%d', '%d', '%d');
		$type = NULL;
		$first = true;
		$error = false;
		$count = 0;
		
		print '<div class="clear"><p></div>';
		
		while(!feof($ris_file)) 
		{
			$line = trim(fgets($ris_file));
			
			if ($first) 
			{
				// remove BOM for UTF-8 files. This should not be neccessary for 
				// PHP 6.0 or later.
				$line = str_replace("\xef\xbb\xbf", '', $line);
				$first = false;
			}
			
			if ($line != '') 
			{
				$toks = explode('-', $line, 2);
				$tag = trim($toks[0]);
				$data = trim($toks[1]);
				
				if ($tag == 'ER') 
				{
					if ($error) 
					{
						print $wpdb->prepare($line);
						print '</div>';
						$error = false;
					} 
					else 
					{
						try
						{
							// End of a bibliography entry. Ready for insert.
							if ($field_values['title'] != '')
							{
								$bib_query->insert_bibliography($field_values, $field_formats, $authors, $authors_format);								
								$count++;					
							}
							else 
							{
								print '<strong>Found a reference without title. Skipped.</strong></br>';
							}
							
						}
						catch (exception $e)
						{
							print '<strong>Failed to insert "'.$field_values['title'].'"</strong></br>';
							$exception_message = $e->getMessage();
							
							if (strpos($exception_message, "Duplicate entry") !== false)
							{
								print 'Duplicate entry. There is already a reference with the same type, title, and conference/meeting name.';
							}
							else
							{
								print $exception_message;
							}
							
							print '</br>';
						}
						
						// Re-initialize variables.
						$field_values= array();
						$field_formats = array();
						$authors = array();
						$type = NULL;
					}
				} 
				else if ($tag) 
				{
					if ($error) 
					{
						print $wpdb->prepare($line);
						print '<br/>';
					} 
					else
					{
						try 
						{
							// keep appending fields.
							$key_value = $paser_helper->parse_ris_fields($tag, $data, $type, $field_values, $field_formats, $authors);
						} 
						catch (exception $e) 
						{
							$error = true;
							print '<div class="bib_error">';
							print '<strong>'.$wpdb->prepare($e->getMessage()).'</strong>';
							print '<br/>';
							print $wpdb->prepare($line);
							print '<br/>';
						}
					}
				}
			}
		} // end of while
		
		print '<div class="clear"></div>';
		print '<p>Number of references successfully imported: '.$count.'.</p>';

	} 
	else 
	{
		print '<div class="clear"></div>';
		print '<p>You must select a file to import.</p>';
	}
}
?>


<br class="clear" />
	<h4>Import references in RIS format only.</h4>
<form method="post" enctype="multipart/form-data" 
	action="<?php echo admin_url('admin.php?page=bibliplug/bibliplug_import.php&action=upload-ris'); ?>">
	<?php wp_nonce_field('bibliplug-import'); ?>		
	<label class="screen-reader-text" for="risfile">Bibliography file</label>
	<input type="file" id="risfile" name="risfile" />
	<input type="submit" class="button" value="Import Now" />
</form>
<p>For example, if you are using EndNote or other reference managemnt software, when exporting your reference, choose "RIS" as the export fromat. This will typically result in a .txt file.</p>
<p>For information on RIS format, go here: <a href="http://www.refman.com/support/risformat_intro.asp">http://www.refman.com/support/risformat_intro.asp</a></p>
