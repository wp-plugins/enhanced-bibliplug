<?php

if (!defined('BIBLIPLUG_DIR')) 
{
	die('Invalid access point.');
}

$title = __('Import / Export references');
$message = null;

?>
<div class="wrap">
    <?php screen_icon('tools'); ?>
    <h2><?php echo esc_html( $title ); ?></h2>
    <br class="clear" />

    <?php
    if (isset($_GET['action']) && $_GET['action'] == 'upload-ris')
    {
        if (!current_user_can('administrator'))
        {
            wp_die('You are not allowed to import files.');
        }

        wp_referer_field('bibliplug-import');

        if (!class_exists("import_format_helper"))
        {
            require_once(BIBLIPLUG_DIR.'/format_helper/import_format_helper.php');
        }

        $file_name = $_FILES['risfile']['name'];
        if ($file_name)
        {
            $ris_file = fopen($_FILES['risfile']['tmp_name'], 'r');
        }

        if ($ris_file)
        {
            require_once(BIBLIPLUG_DIR . '/format_helper/ris_file_helper.php');
        }
        else
        {
            $message = "You must select a file to import";
        }

    }
    ?>

    <?php if ( $message ) { ?>
    <div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
    <?php } ?>


    <br class="clear" />
    <h3>Import references in RIS format only.</h3>
    <form method="post" enctype="multipart/form-data"
        action="<?php echo admin_url('admin.php?page=enhanced-bibliplug/bibliplug_import.php&action=upload-ris'); ?>">
        <?php wp_nonce_field('bibliplug-import'); ?>
        <label class="screen-reader-text" for="risfile">Bibliography file</label>
        <input type="file" id="risfile" name="risfile" />
        <input type="submit" class="button" value="Import Now" />
    </form>
    <p>For example, if you are using EndNote or other reference managemnt software, when exporting your reference,
        choose "RIS" as the export fromat. This will typically result in a .txt file.</p>

    <br class="clear" />
    <br class="clear" />

    <h3>Export references to RIS format.</h3>

    <form method="POST" action="<?php echo admin_url('admin-ajax.php?action=bibliplug_export'); ?>">
        <h4>Filter by:</h4>
        <table>
            <tr>
                <td>Author last name</td>
                <td><input name="last_name" /></td>
            </tr>
            <tr>
                <td>Author first name</td>
                <td><input name="first_name" /></td>
            </tr>
            <tr>
                <td>Year</td>
                <td><select id ="year" name="year">
                    <option value="0">-- All --</option>
                    <?php foreach ($bib_query->get_distinct_years() as $year) {
                    if ($year->publish_year) {
                        echo "<option value=\"$year->publish_year\">$year->publish_year</option>";
                    }
                }?>
                </select></td>
            </tr>
            <tr>
                <td>Reference Type</td>
                <td><select id ="type" name="type">
                    <option value="All">-- All --</option>
                    <?php foreach ($bib_query->get_types() as $type) {
                    echo "<option value=\"$type->name\">" . ucfirst($type->name) . "</option>";
                } ?>
                </select></td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="submit" class="button" value="Export" />
        </p>
    </form>

    <br class="clear" />
    <br class="clear" />
    <br class="clear" />
    <hr>
    <p>For information on RIS format, go <a href="http://www.refman.com/support/risformat_intro.asp">here</a>.</p>