<?php

class bibliplug_template {

	public function print_creator_list($creators, $creator_types)
	{
		?><table id="bibliplug-creators">
			<thead>
				<tr>
					<th>Type</th>
					<th>First Name</th>
					<th>Middle Name</th>
					<th>Prefix</th>
					<th>Last Name</th>
					<th></th>
				</tr>
			</thead>
			<tbody><?php
			if (!$creators)
			{
				// creator an empty place holder.
				$creators = array((object) array('id' => 0, 'order_index' => 1, 'first_name' => '', 'middle_name' => '',
                                                 'prefix' => '', 'last_name' => '', 'creator_type_id' => 1));
			}

			foreach ($creators as $creator)  {
				?><tr id="creator-row-<?php echo $creator->order_index; ?>">
					<input type="hidden" class="order-index" name="creator[<?php echo $creator->id; ?>][order_index]" value="<?php echo $creator->order_index; ?>" />
					<input type="hidden" class="deleted" name="creator[<?php echo $creator->id; ?>][deleted]" value="0" />
					<td>
						<select name="creator[<?php echo $creator->id; ?>][creator_type_id]"><?php
							foreach ($creator_types as $type) {
								$attr = ($type->id == $creator->creator_type_id) ? 'selected="selected"' : '';
								?><option value="<?php echo $type->id; ?>" <?php echo $attr; ?>><?php echo ucfirst($type->name); ?></option><?php
							}
						?></select>
					</td>
					<td>
						<input type="text" name="creator[<?php echo $creator->id; ?>][first_name]" value="<?php echo $creator->first_name; ?>" />
					</td>
					<td>
						<input type="text" name="creator[<?php echo $creator->id; ?>][middle_name]" value="<?php echo $creator->middle_name; ?>" />
					</td>
					<td>
						<input type="text" name="creator[<?php echo $creator->id; ?>][prefix]" value="<?php echo $creator->prefix; ?>" />
					</td>
					<td>
						<input type="text" name="creator[<?php echo $creator->id; ?>][last_name]" value="<?php echo $creator->last_name; ?>" />
					</td>
					<td>
						<button class="delete-creator">delete</button>
					</td>
				</tr><?php
			}
			?></tbody>
		</table>
		<p><input type="button" class="button" id="add-creator" value="add-creator" /></p>
		<p class="description">Drag and drop creators to change the order.</p>
		<?php
	}

	public function print_details_table($bib, $type_id, $fields) 
	{
		global $bib_query;

		?><table class="form-table" id="bibliplug-details">
			<tr valign="top">
				<th scope="row"><label for="type_id">Type</label></th>
				<td><select id ="bibliplug_ref_type"name="type_id" >
				<?php foreach ($bib_query->get_types() as $type) {
					$attr = ($type->id == $type_id) ? '" selected="selected' : '';
					?><option value="<?php echo $type->id . $attr; ?>"><?php echo ucfirst($type->name); ?></option><?php
				} ?>
				</select></td>
			</tr>

			<?php
			if ($fields) {
				foreach ($fields as $field) {
					$field_name = $field->internal_name;
					$field_display_name = ($field->mapped_name) ? $field->mapped_name : $field->name;
                    $field_value = $bib ? stripslashes($bib->$field_name) : "";
			?>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $field_name; ?>"><?php echo ucfirst($field_display_name); ?></label></th>
				<td colspan="3">
				<?php switch ($field_name) {
					case 'title':
						?><textarea name="<?php echo $field_name; ?>" rows="4" id="<?php echo $field_name; ?>" class='textarea'><?php echo esc_attr($field_value); ?></textarea><?php
						break;
					case 'abstract':
					case 'notes':
						?><textarea name="<?php echo $field_name; ?>" rows="4" id="<?php echo $field_name; ?>" class='textarea'><?php echo esc_attr($field_value); ?></textarea><?php
						break;

					case 'start_page':
					case 'end_page':
					case 'volume':
					case 'number_of_volumes':
						?><input name="<?php echo $field_name; ?>" type="text" id="<?php echo $field_name; ?>" value="<?php echo $field_value ? esc_attr($field_value) : ''; ?>" class="short"/><?php
						break;

					case 'publish_year':
					case 'series':
					case 'edition':
					case 'ISBN':
					case 'ISSN':
					case 'publish_date':
					case 'access_date':
					case 'volume':
					case 'issue':
					case 'series_number':
						?><input name="<?php echo $field_name; ?>" type="text" id="<?php echo $field_name; ?>" value="<?php echo esc_attr($field_value); ?>" class="short"/><?php
						break;

					case 'keywords':
						$hint = 'separate multiple keywords with commas';
						?><input name="<?php echo $field_name; ?>" type="text" id="<?php echo $field_name; ?>" value="<?php echo esc_attr($field_value); ?>" class="input-with-hint long"/>
						  <br/><span class="description"><?php echo $hint; ?></span><?php
						break;
					case 'link1':
						$hint = 'link full text document to reference title';
						?><input name="<?php echo $field_name; ?>" type="text" id="<?php echo $field_name; ?>" value="<?php echo esc_attr($field_value); ?>" class="input-with-hint long"/>
                          <br/><span class="description"><?php echo $hint; ?></span><?php
						break;
					case 'link2':
						$hint = 'link full text html webpage to title';
						?><input name="<?php echo $field_name; ?>" type="text" id="<?php echo $field_name; ?>" value="<?php echo esc_attr($field_value); ?>" class="input-with-hint long"/>
                          <br/><span class="description"><?php echo $hint; ?></span><?php
						break;
					case 'link3':
						$hint = 'link source/information page to secondary title';
						?><input name="<?php echo $field_name; ?>" type="text" id="<?php echo $field_name; ?>" value="<?php echo esc_attr($field_value); ?>" class="input-with-hint long"/>
                          <br/><span class="description"><?php echo $hint; ?></span><?php
						break;

					case 'url':
						$hint = 'enter link here to make URL visible in reference';
						?><input name="<?php echo $field_name; ?>" type="text" id="<?php echo $field_name; ?>" value="<?php echo esc_attr($field_value); ?>" class="input-with-hint long"/>
                          <br/><span class="description"><?php echo $hint; ?></span><?php
						break;

					default:
						?><input name="<?php echo $field_name; ?>" type="text" id="<?php echo $field_name; ?>" value="<?php echo esc_attr($field_value); ?>" class="long"/><?php
						break;
				} ?>
				</td>
			</tr>
		<?php }
		} ?>
		</table><?php
	}

    public function print_extra_links($bib)
    {
        ?><table class="form-table" id="bibliplug-extra-links">
            <tr valign="top">
                <th scope="row"><label for="presentation_link">Presentation link</label></th>
                <td colspan="3">
                    <input type="text" name="presentation_link" id="presentation_link" value="<?php echo ($bib) ? esc_attr($bib->presentation_link) : ''; ?>" class="long" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="video_link">Video link</label></th>
                <td colspan="3">
                    <input type="text" name="video_link" id="video_link" value="<?php echo ($bib) ? esc_attr($bib->video_link) : ''; ?>" class="long" />
                </td>
            </tr>
        </table><?php
    }
}
?>