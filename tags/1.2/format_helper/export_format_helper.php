<?php

require_once(BIBLIPLUG_DIR . 'bibliplug_util.php');

class export_format_helper
{

	public function write_ris_reference($ref)
	{
		global $bib_query;
		$eof = PHP_EOL;

		$result = 'TY - ' . $this->get_ris_type_name($ref->type_id) . $eof;

		$creators = $bib_query->get_creators($ref->id);
		foreach ($creators as $creator)
		{
			if ($creator->creator_type_id == 3)
			{
				$result .= 'ED - ';
			} else if ($creator->is_secondary)
			{
				$result .= 'A2 - ';
			} else
			{
				$result .= 'AU - ';
			}

			$result .= print_name($creator, false, true) . $eof;
		}

		$fields = $bib_query->get_fields_by_type_id($ref->type_id);
		$misc_index = 1;
		foreach ($fields as $field)
		{
			$field_name = $field->internal_name;
			$field_value = str_replace(array("\r\n", "\n", "\r", "\'"), array(" ", " ", " ", "'"), $ref->$field_name);

			if ($field_value)
			{
				$print_field_value = true;
				switch ($field_name)
				{
					case 'title':
						switch ($ref->type_id)
						{
							case 1:
								$result .= 'BT';
								break;
							case 3:
								$result .= 'CT';
								break;
							default:
								$result .= 'TI';
								break;
						}
						break;
					case 'publication_title':
						switch ($ref->type_id)
						{
							case 1:
								// book should never have a publication title
								break;
							case 2:
								$result .= 'JF';
								break;
							case 3:
								$result .= 'BT';
								break;
							default:
								$result .= 'T2';
								break;
						}
						break;
					case 'publish_year':
						if ($ref->publish_date)
						{
							$result .= 'Y1';
						} else
						{
							$result .= 'PY';
						}
						break;
					case 'publish_date':
						$result .= 'Y2';
						break;
					case 'conference_name':
					case 'meeting_name':
					case 'series_title':
						$result .= 'T3';
						break;
					case 'publisher':
						$result .= 'PB';
						break;
					case 'journal_abbreviation':
						$result .= 'JA'; // maybe JO
						break;
					case 'notes':
						$result .= 'N1'; // maybe RP
						break;
					case 'abstract':
						$result .= 'N2';
						break;
					case 'place':
						$result .= 'AD';
						break;
					case 'city_of_publication':
						$result .= 'CY';
						break;
					case 'ISSN':
					case 'ISBN':
						$result .= 'SN';
						break;
					case 'issue':
						$result .= 'IS'; // also CP
						break;
					case 'start_page':
						$result .= 'SP';
						break;
					case 'end_page':
						$result .= 'EP';
						break;
					case 'url':
						$result .= 'UR';
						break;
					case 'link1':
						$result .= 'L1';
						break;
					case 'link2':
						$result .= 'L2';
						break;
					case 'link3':
						$result .= 'L3';
						break;
					case 'volume':
						$result .= 'VL';
						break;
					case 'keywords':
						$print_field_value = false;
						$keywords = explode(",", $field_value);
						foreach ($keywords as $keyword)
						{
							$result .= 'KW - ' . trim($keyword) . $eof;
						}
						break;
					case 'access_date':
						$print_field_value = false;
						$result .= 'M' . $misc_index . ' - Accessed by ' . $field_value . $eof;
						$misc_index++;
						break;
					default:
						$print_field_value = false;
						$result .= 'M' . $misc_index . ' - ' . $field->name . ': ' . $field_value . $eof;
						$misc_index++;
						break;
				}

				if ($print_field_value)
				{
					$result .= ' - ' . $field_value . $eof;
				}
			}
		}

		$result .= 'ER - ' . $eof . $eof;

		return $result;
	}

	public function get_rdf_tripple($ref, $creators)
	{
		global $bib_query;
		$triple['rdf:type'] = array($this->get_fabio_type($ref->type_id));

		foreach($creators as $creator)
		{
			$triple['dcterms:creator'][] = print_name($creator, true);
		}
		
		$fields = $bib_query->get_fields_by_type_id($ref->type_id);

		foreach ($fields as $field)
		{
			$field_name = $field->internal_name;
			$field_value = str_replace(
					array("\r\n", "\n", "\r", "\'"), array("<br /> ", "<br /> ", "<br />", "'"), $ref->$field_name);

			if ($field_value)
			{
				switch ($field_name)
				{
					case 'title':
						$term = 'dcterms:title';
						break;
					case 'publish_year':
						$term = 'fabio:hasPublicationYear';
						break;
					case 'publish_date':
						$term = 'prism:publicationDate';
						break;
					case 'abstract':
						$term = 'fabio:abstract';
						break;
					case 'ISSN':
						$term = 'prism:issn';
						break;
					case 'ISBN':
						$term = 'prism:isbn';
						break;
					case 'DOI':
						$term = 'prism:doi';
						break;
					case 'access_date':
						$term = 'fabio:hasAccessDate';
						break;
					case 'url':
					case 'link1':
					case 'link2':
					case 'link3':
						$term = 'fabio:hasURL';
						break;
					case 'start_page':
						$term = 'prism:startingPage';
						break;
					case 'end_page':
						$term = 'prism:endingPage';
						break;
					case 'place':
					case 'city_of_publication': // TODO
						$term = 'frbr:place';
						break;

					case 'publisher':
						$term = 'dcterms:publisher';
						break;
					case 'publication_title':
					case 'series_title':
						$term = 'frbr:partOf';
						break;

					case 'short_title':
						$term = 'fabio:hasShortTitle';
						break;
					case 'keywords':
						$term = 'prism:keywords';
						break;

					case 'issue':
						$term = 'prism:issueIdentifier';
						break;
					case 'volume':
						$term = 'prism:volume';
						break;
					case 'number_of_volumes':
						$term = 'prism:hasVolumeCount';
						break;
					case 'edition':
						$term = 'prism:edition';
						break;

					// Ignore
					case 'notes':
					case 'journal_abbreviation':
						$term = null;
						break;

					case 'conference_name':
					case 'meeting_name':
						$term = 'frbr:event';
						break;
					
					default:
						$term = 'rdf:UNKNOWN-' . $field_name;
						break;
				}

				if ($term)
				{
					$triple[$term] = $field_value;
				}
			}
		}

		return $triple;
	}

	private function get_fabio_type($type_id)
	{
		$base_uri = "http://purl.org/spar/fabio/";
		switch ($type_id)
		{
			case 1:
				$term = 'Book';
				break;
			case 2:
				$term = 'JournalArticle';
				break;
			case 3:
				$term = 'BookChapter';
				break;
			case 4:
				$term = 'Presentation';
				break;
			case 5:
				$term = 'ConferencePaper';
				break;
			case 6:
				$term = 'Thesis';
				break;
			case 7:
				$term = 'Blog';
				break;
			case 8:
				$term = 'ReportDocument';
				break;
			case 9:
				$term = 'WebPage';
				break;
			case 10:
				$term = 'ComputerProgram';
				break;
			case 11:
				$term = 'MovingImage';
				break;
			default:
				throw new exception("Invalid fabio type: type_id = $type_id.");
		}

		return $base_uri . $term;
	}

	private function get_ris_type_name($type_id)
	{
		switch ($type_id)
		{
			case 1:
				return 'BOOK';
			case 2:
				return 'JOUR';
			case 3:
				return 'CHAP';
			case 4:
				return 'PRES'; //TODO
			case 5:
			case 13:
				return 'CONF';
			case 6:
				return 'THES';
			case 7:
				return 'BLOG';
			case 8:
				return 'RPT';
			case 9:
				return 'WEBSITE';
			case 10:
				return 'COMP';
			case 11:
				return 'VIDEO';
			case 12:
				return 'ELEC';
			default:
				return $type_id;
			//throw new exception ("Invalid ris type: type_id = $type_id.");
		}
	}

}

?>