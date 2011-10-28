<?php

require_once(BIBLIPLUG_DIR . 'bibliplug_util.php');

class display_format_helper
{

	public function display_chicago_style($bib, $fields)
	{
		if (!$bib)
		{
			return;
		}

		global $bib_query;
		$type = $bib_query->get_type_row_by_id($bib->type_id);
		$result = "<div class='reference $type->internal_name'>";

		//$result .= "$type->name:<br>";

		// print out primary creators.
		$creators = $bib_query->get_primary_creators($bib->id, $bib->type_id);

		if ($creators)
		{
			$result .= '<span id="authors">' . $this->print_names($creators, false) . '</span>';
			$connector = '.';
		}

		foreach ($fields as $field)
		{
			$field_name = $field->internal_name;
			$field_value = stripslashes($bib->$field_name);

			if ($field_value)
			{
				// print_r($field);
				if ($field->mapped_name)
				{
					$field_display_name = $field->mapped_name;
				} else
				{
					$field_display_name = $field->name;
				}

				switch ($field_display_name)
				{
					case 'title':

						if (preg_match('/[^.?!]$/', $field_value))
						{
							$field_value .= '.';
						}

						// not a book or report
						$quote = ($type->id != 1 && $type->id != 8) ? '"' : '';

						$link = ($bib->link1) ? $bib->link1 : $bib->link2;
						if ($link)
						{
							$result .= "$connector <span id='$field_name'>$quote<a href='$link'>$field_value</a>$quote</span>";
						} else
						{
							$result .= "$connector <span id='$field_name'>$quote$field_value$quote</span>";
						}

						if ($type->id == 1)
						{
							$editors = $bib_query->get_editors($bib->id);
							if ($editors)
							{
								$result .= ' <span id="editors">Edited by ' . $this->print_names($editors, true) . '</span>';
							}
						}
						$connector = '';
						break;

					case 'start page':
						$end_page = $bib->end_page;
						if ($end_page && $end_page > $field_value)
						{
							$field_value .= '-' . $end_page;
						}

						$result .= "</span>$connector <span id='pages'>$field_value</span>";
						$connector = '.';
						break;

					case 'book title':
						if ($type->id == 3)
						{ // book section
							$result .= '</span> In ';
						} else
						{
							$result .= '</span> ';
						}

						$link = $bib->link3;
						if ($link)
						{
							$result .= "<span id='book_title'><a href='$link'>$field_value</a>,</span>";
						} else
						{
							$result .= "<span id='book_title'>$field_value,</span>";
						}

						$editors = $bib_query->get_editors($bib->id);
						if ($editors)
						{
							$result .= ' edited by <span id="editors">' . $this->print_names($editors, true) . '</span>';
						}

						$connector = ',';
						break;

					case 'year':
					case 'date':
					case 'place':
						$result .= "$connector <span id='$field_name'>$field_value</span>";
						$connector = '.';
						break;

					case 'meeting name':
					case 'conference name':
						$result .= "$connector <span id='$field_name'>";

						$link = $bib->link3;
						if ($link)
						{
							$result .= "<a href='$link'>";
						}

						if ($type->id == 13)
						{ // conference proceedings
							$result .= "Proceeding of: ";
						} else if ($type->id == 5)
						{ // conference paper
							$result .= "Paper presented at ";
						}

						$result .= $field_value;

						if ($link)
						{
							$result .= "</a>";
						}

						$result .= '</span>';
						$connector = '.';
						break;

					case 'issue':
						$result .= " <span id='$field_name'>($field_value)</span>";
						$connector = ':';
						break;

					case 'blog title':
					case 'journal title':
					case 'publication title':
						$link = $bib->link3;
						if ($link)
						{
							$result .= "$connector <span id='$field_name'><a href='$link'>$field_value</a></span>";
						} else
						{
							$result .= "$connector <span id='$field_name'>$field_value</span>";
						}
						$connector = ',';
						break;

					case 'city of publication':
						$result .= "$connector <span id='$field_name'>$field_value</span>";
						$connector = ':';
						break;

					case 'series title':
					case 'series':
					case 'series number':
					case 'volume':
					case 'number of volumes':
					case 'publisher':
					case 'edition':
						$result .= "$connector <span id='$field_name'>$field_value</span>";
						$connector = ',';
						break;
					
					case 'url':
						$result .= "$connector <span id='$field_name'><a href='$field_value'>$field_value</a>";
						if ($bib->access_date)
						{
							$result .= " (accessed {$bib->access_date})";
						}

						$result .= '</span>';
						$connector = '.';
						break;

					case 'access date':
					case 'end page':
					case 'year':
						// access date is displayed with url.
						// end page should be always displayed with start_page.
						break;
				}
			}
		}

		if ($result[-1] != '.')
		{
			$result .= '.';
		}

		$result .= '</div><p></p>';

		return $result;
	}

	public function print_names($creators, $first_name_first)
	{
		$creator_count = count($creators);
		$i = 0;
		$result = '';

		foreach ($creators as $creator)
		{
			if ($i == 0)
			{
				// do nothing
			} else if ($i == 1 && $creator_count == 2)
			{
				$result .= ' and ';
			} else if ($i == ($creator_count - 1))
			{
				$result .= ', and ';
			} else
			{
				$result .= ', ';
			}

			if ($creator->user_id)
			{
				$result .= '<a href="' . get_author_posts_url($creator->user_id) . '">';
			}

			$result .= print_name($creator, $first_name_first || $i != 0);

			if ($creator->user_id)
			{
				$result .= '</a>';
			}
			$i++;
		}

		return $result;
	}

}

?>
