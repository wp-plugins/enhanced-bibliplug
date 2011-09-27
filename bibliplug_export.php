<?php
if (!defined('BIBLIPLUG_DIR'))
{
	die('Invalid access point.');
}

$title = __('Export references');
$parent_file = 'bibliplug_edit.php';
?>
<div class="wrap">
<?php screen_icon('edit'); ?>
<h2><?php echo esc_html($title); ?></h2>

<h4>Export references to RIS format.</h4>
	<p>For information on RIS format, go <a href="http://www.refman.com/support/risformat_intro.asp">here</a>.</p>

	<form method="post" action="<?php echo admin_url('admin-ajax.php?action=bibliplug_export'); ?>">
		<br>
		<h5>Filter by:</h5>
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
			<input type="hidden" name="action" value="export" />
		</p>
	</form>