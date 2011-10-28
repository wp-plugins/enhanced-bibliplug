<?php

require_once(BIBLIPLUG_DIR.'bibliplug_util.php');

class import_format_helper {

	public function parse_ris_fields($tag, $data, &$type, &$field_values, &$field_formats, &$authors) {
		global $bib_query;
		switch ($tag) {	
			case 'TY':
				$type = $this->get_type_from_ris_type($data);
				$field_values['type_id'] = $type->id;
				$field_formats[] = '%d';		
				break;
				
			case 'T1':
			case 'CT':
				$field_values['title'] = rtrim($data, '.');
				$field_formats[] = '%s';
				break;
			
			case 'TI':
			case 'T2':
				if ($type->name == 'book section') {
					$field_values['publication_title'] = rtrim($data, '.');
				} else {
					$field_values['title'] = rtrim($data, '.');
				}
				$field_formats[] = '%s';
				break;
			
			case 'BT':
				if ($type->name == 'book') {
					$field_values['title'] = rtrim($data, '.');
				} else {
					$field_values['publication_title'] = rtrim($data, '.');
				}
				$field_formats[] = '%s';
				break;
							
			case 'T3':
				if ($type->name == 'conference paper' || $type->name == 'presentation') {
					$field_values['conference_name'] = rtrim($data, '.');
				} else {
					$field_values['series_title'] = rtrim($data, '.');
				}
				$field_formats[] = '%s';
				break;
				
			case 'A1':
			case 'A2':
			case 'AU':
			case 'ED':
				$first_name = '';
				$last_name = '';
				$middle_name = '';
				
				$toks = explode(',', $data, 2);
				if ($toks[0] != '') {				// last, first middle
					$last_name = $toks[0];
					$sub_toks = explode(' ', trim($toks[1]), 2);
					$first_name = $sub_toks[0];
					$middle_name = $sub_toks[1];
				} else {							// first middle last
					list($first_name, $middle_name, $last_name) = split(' ', $data);
					if (!$last_name) {
						$last_name = $middle_name;
					}
				}
				
				$creator_type_id = 1;
				if ($tag == 'ED') {
					$creator_type_id = 3;
				} else {
					// get the primary creator type.
					$creator_type_id = $bib_query->get_the_creator_type_id($type->id);
				}
				
				$authors[] = array ('first_name' => trim($first_name), 
									'last_name' => trim($last_name),
									'middle_name' => trim(str_replace('.', '', $middle_name)),
									'is_secondary' => (($tag == 'A2') ? 1 : 0),
									'order_index' => count($authors) + 1,
									'creator_type_id' => $creator_type_id);
				break;
			
			case 'KW':
				$keywords = $field_values['keywords'];
				if ($keywords) {
					$field_values['keywords'] = $keywords .', '.$data;
				} else {
					$field_values['keywords'] = $data;
					$field_formats[] = '%s';
				}
				break;
				
			case 'PB':
				$field_values['publisher'] = $data;
				$field_formats[] = '%s';
				break;
			
			case 'Y1':
			case 'PY':
				list($year, $month, $day) = split('/', $data);
				if ($month) {
					if (!$day) {
						$day = '01';
					}
					
					$field_values['publish_date'] = $year.'-'.$month.'-'.$day;
					$field_formats[] = '%s';
				}
				$field_values['publish_year'] = $year;
				$field_formats[] = '%d';
				break;
			
			case 'Y2':
				$field_values['publish_date'] = $data;
				$field_formats[] = '%s';
				break;
				
			case 'JF':
				$field_values['publication_title'] = $data;
				$field_formats[] = '%s';
				break;
			
			case 'JO':
			case 'JA':
				$field_values['journal_abbreviation'] = $data;
				$field_formats[] = '%s';
				break;
			
			case 'RP': // reprint status			
			case 'M1':
			case 'M2':
			case 'M3':
			case 'N1':
				if ($field_values['notes']) {
					$field_values['notes'] .= '; '.$data;
				} else {
					$field_values['notes'] = $data;
					$field_formats[] = '%s';
				}
				break;
			
			case 'N2':
				$field_values['abstract'] = $data;
				$field_formats[] = '%s';
				break;
			
			case 'AD':
				$place = $field_values['place'];
				if ($place) {
					$field_values['place'] = $place.' '.$data;
				} else {
					$field_values['place'] = $data;
					$field_formats[] = '%s';
				}
				break;
			
			case 'CY':
				$field_values['city_of_publication'] = $data;
				$field_formats[] = '%s';
				break;
				
			case 'SN':
				if ($type->name == 'book_section') {
					$field_values['ISSN'] = $data;
				} else {
					$field_values['ISBN'] = $data;
				}
				$field_formats[] = '%s';
				break;
			
			case 'CP':
			case 'IS':
				$field_values['issue'] = $data;
				$field_formats[] = '%s';
				break;
			
			case 'SP':
				list($start, $end) = split('-', $data);
				$field_values['start_page'] = $start;
				$field_formats[] = '%d';
				if ($end) {
					$field_values['end_page'] = $end;
					$field_formats[] = '%d';
				}
				break;
			
			case 'EP':
				$field_values['end_page'] = $data;
				$field_formats[] = '%d';
				break;
			
			case 'UR':
				$field_values['url'] = validate_url($data);
				$field_formats[] = '%s';
				break;
				
			case 'L1':
				$field_values['link1'] = validate_url($data);
				$field_formats[] = '%s';
				break;
			
			case 'L2':
				$field_values['link2'] = validate_url($data);
				$field_formats[] = '%s';
				break;
			
			case 'L3':
				$field_values['link3'] = validate_url($data);
				$field_formats[] = '%s';
				break;

			case 'VL':
				$field_values['volume'] = $data;
				$field_formats[] = '%d';
				break;

			case 'ID':
				// do nothing.
				break;
			
			default:
				echo('<strong>Unhandled tag "'.$tag.'":</strong> '.$data.' for reference "'.$field_values['title'].'"</br>');
		}
	}

	public function parse_zotero_entry($data, &$field_values, &$field_formats, &$authors)
	{
		foreach($data as $key => $value)
		{
			if ($value)
			{
				switch($key)
				{
					case 'itemType':
						$field_values['type_id'] = $this->get_type_id_from_zotero_type($value);
						$field_formats[] = '%d';
						
						if ($field_values['type_id'] == 0 && BIBLIPLUG_DEBUG)
						{
							print "<p>Warning: type '$value' for reference '{$data['title']}' is not support. Mapped to 'Other'.</p>";
						}
						break;

					case 'numPages':
					case 'pages':
						list($start, $end) = split('-', $value);
						$field_values['start_page'] = $start;
						$field_formats[] = '%d';
						if ($end) {
							$field_values['end_page'] = $end;
							$field_formats[] = '%d';
						}
						break;

					case 'date':
						$date_details = date_parse($value);
						/*print $data['title']."<br/>: '$value'";
						print_r($date_details);
						print "<br/>";*/
						if ($date_details['year'])
						{
							$field_values['publish_year'] = $date_details['year'];
							$field_formats[] = '%s';
						}
						else if (is_numeric($value))
						{
							$field_values['publish_year'] = $value;
							$field_formats[] = '%s';
						}

						$field_values['publish_date'] = $value;
						$field_formats[] = '%s';

						break;

					case 'tags':

						foreach($value as $tag_array)
						{
							$tags[] = $tag_array['tag'];
						}
						
						$field_values['keywords'] = implode(', ', $tags);
						$field_formats[] = '%s';
						break;

					case 'url':
						if ($data['itemType'] == 'webpage')
						{
							$field_values['url'] = $value;
						}
						else
						{
							$field_values['link1'] = $value;
						}

						$field_formats[] = '%s';
						break;
					
					case 'creators':
						foreach($value as $creator)
						{
							$creator_type_id = $this->get_creator_type_id_from_zotero_data($creator['creatorType']);
							list($first_name, $middle_name) = split(' ', $creator['firstName']);
							$last_name = $creator['lastName'];

							$authors[] = array ('first_name' => trim($first_name),
									'last_name' => trim($last_name),
									'middle_name' => trim(str_replace('.', '', $middle_name)),
									'is_secondary' => 0,
									'order_index' => count($authors) + 1,
									'creator_type_id' => $creator_type_id);
						}
						break;

					default:
						$field_name = $this->get_bibliplug_zotero_field_mapping($key);
						if ($field_name)
						{
							$field_values[$field_name] = $value;
							$field_formats[] = is_field_numeric($field_name) ? '%d' : '%s';
						}
						else
						{
							if ($field_values['notes']) {
								$field_values['notes'] .= "; $key - $value";
							} else {
								$field_values['notes'] = "$key - $value";
								$field_formats[] = '%s';
							}
							
							if (BIBLIPLUG_DEBUG)
							{
								print "<p>Warning: Unsupported field '$key' with value '$value' for reference '{$data['title']}' is added to notes.</p>";
							}
						}
						break;
				}
			}
			//print "$name: $value</br>";
		}
	}
	
	private function get_type_from_ris_type($data) {
		global $bib_query;
		switch ($data) {
			case 'BOOK':
				return $bib_query->get_type_row_by_id(1);
			
			case 'EJOUR':
			case 'JOUR':
				return $bib_query->get_type_row_by_id(2);
				
			case 'CHAP':
				return $bib_query->get_type_row_by_id(3);
			
			case 'PRES':
				return $bib_query->get_type_row_by_id(4);
				
			case 'CONF':
				return $bib_query->get_type_row_by_id(5);
				
			case 'THES':
				return $bib_query->get_type_row_by_id(6);
			
			case 'BLOG':
				return $bib_query->get_type_row_by_id(7);
			
			case 'RPRT':
			case 'RPT':
				return $bib_query->get_type_row_by_id(8);
			
			case 'WEBSITE':
				return $bib_query->get_type_row_by_id(9);
				
			case 'COMP':
				return $bib_query->get_type_row_by_id(10);
				
			case 'VIDEO':
				return $bib_query->get_type_row_by_id(11);
				
			case 'ELEC':
				return $bib_query->get_type_row_by_id(12);
				
			default:
				return $bib_query->get_type_row_by_id(0);
		}
	}


	private function get_type_id_from_zotero_type($type_name)
	{
		switch ($type_name)
		{
			case 'book':
				return 1;

			case 'journalArticle':
				return 2;

			case 'bookSection':
				return 3;

			case 'presentation':
				return 4;

			case 'conferencePaper':
				return 5;

			case 'thesis':
				return 6;

			case 'blogPost':
				return 7;

			case 'report':
				return 8;

			case 'webpage':
				return 9;

			case 'computerProgram':
				return 10;

			case 'videoRecording':
				return 11;

			case 'note':
			case 'snapshot':
			case 'attachment':
				throw new exception("$type_name is ignored.");
				
			default:
				return 0;
		}
	}

	private function get_bibliplug_zotero_field_mapping($name)
	{
		switch($name)
		{
			case 'abstractNote':
				return 'abstract';
			case 'seriesNumber':
				return 'series_number';
			case 'numberofVolumes':
				return 'number_of_volumes';
			case 'shortTitle':
				return 'short_title';
			case 'accessDate':
				return 'access_date';
			case 'callNumber':
				return 'call_number';
			case 'publicationTitle':
			case 'bookTitle':
			case 'forumTitle':
			case 'proceedingsTitle':
			case 'blogTitle':
				return 'publication_title';
			case 'seriesTitle':
				return 'series_title';
			case 'journalAbbreviation':
				return 'journal_abbreviation';
			case 'institution':
			case 'studio':
				return 'place';
			case 'runningTime':
				return 'running_time';
			case 'conferenceName':
				return 'conference_name';

			// these should be processed eariler.
			case 'itemType':
			case 'date':
			case 'numPages':
			case 'pages':
			case 'creators':
			case 'tags':
			case 'url':
				return null;

			// not supported.
			case 'language':
			case 'archive':
			case 'archiveLocation':
			case 'libraryCatalog':
			case 'rights':
			case 'extra':
			case 'seriesText':
			case 'reportNumber':
			case 'reportType':
			case 'websiteTitle':
			case 'websiteType':
			case 'videoRecordingFormat':
				return null;
			default:
				return $name;
		}
	}

	private function get_creator_type_id_from_zotero_data($value)
	{
		switch($value)
		{
			case 'author':
				return 1;
			case 'contributor':
				return 2;
			case 'editor':
				return 3;
			case 'director':
				return 8;
			default:
				if (BIBLIPLUG_DEBUG) 
				{
					print "<p>Warning: unhandled creator type '$value'. Mapped to default creator type.</p>";
				}
				return 1;
		}
	}
}
?>