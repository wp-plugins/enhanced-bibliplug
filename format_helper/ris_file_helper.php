<?php

global $bib_query, $wpdb;

$parser_helper = new import_format_helper();
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
        // remove BOM for UTF-8 files. This should not be necessary for
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
                    $key_value = $parser_helper->parse_ris_fields($tag, $data, $type, $field_values, $field_formats, $authors);
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

?>