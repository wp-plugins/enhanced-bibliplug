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
				$creators = array((object) array('id' => 0, 'order_index' => 0));
			}
			
			foreach ($creators as $creator)  { 
				?><tr id="creator-row-<?php echo $creator->order_index; ?>">
					<input type="hidden" class="order-index" name="creator[<?php echo $creator->id; ?>][order_index]" value="<?php echo $creator->order_index; ?>" />
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
						<span class="delete-creator"><img src="<?php echo admin_url('images/no.png'); ?>" alt="x" /></span>
					</td>
				</tr><?php 
			}
			?></tbody>
		</table>
		<p><input type="button" class="button" id="add-creator" value="add-creator" /></p>
		<p class="description">Drag and drop creators to change the order.</p>
		<?php
		
	}
	
	public function print_details_table($bib, $type_id, $fields) {
		global $bib_query;
	
		?><table class="form-table" id="bibliplug-details">
			<tr valign="top">
				<th scope="row"><label for="type_id">Type</label></th>
				<td><select id ="bibliplug_ref_type"name="type_id" >
				<?php foreach ($bib_query->get_types() as $type) {
					$attr = ($type->id == $type_id) ? '" selected="selected' : '';
					?><option value="<?php echo $type->id.$attr; ?>"><?php echo ucfirst($type->name); ?></option><?php
				} ?>
				</select></td>
			</tr>
			
			<?
			if ($fields) {
				foreach ($fields as $field) {
					$field_name = $field->internal_name;
					$field_display_name = ($field->mapped_name) ? $field->mapped_name : $field->name;
					$field_value = stripslashes($bib->$field_name); 
			?>
			<tr valign="top">
				<th scope="row"><label for="<? echo $field_name; ?>"><? echo ucfirst($field_display_name); ?></label></th>
				<td colspan="3">
				<? switch ($field_name) {
					case 'title':
						?><textarea name="<? echo $field_name; ?>" rows="4" id="<? echo $field_name; ?>" class='textarea'><? echo esc_attr($field_value); ?></textarea><?
						break;
					case 'abstract':
					case 'notes':
						?><textarea name="<? echo $field_name; ?>" rows="4" id="<? echo $field_name; ?>" class='textarea'><? echo esc_attr($field_value); ?></textarea><?
						break;
					
					case 'start_page':
					case 'end_page':
					case 'volume':
					case 'number_of_volumes':
						?><input name="<? echo $field_name; ?>" type="text" id="<? echo $field_name; ?>" value="<? echo $field_value ? esc_attr($field_value) : ''; ?>" class="short"/><?
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
						?><input name="<? echo $field_name; ?>" type="text" id="<? echo $field_name; ?>" value="<? echo esc_attr($field_value); ?>" class="short"/><?
						break;
					
					case 'keywords':
						$hint = 'separate multiple keywords with commas';
						?><input name="<? echo $field_name; ?>" type="text" id="<? echo $field_name; ?>" value="<? echo esc_attr($field_value); ?>" class="input-with-hint long"/><span class="bibliplug-hint"><? echo $hint; ?></span><?
						break;
					case 'link1':
						$hint = 'link full text document to reference title';
						?><input name="<? echo $field_name; ?>" type="text" id="<? echo $field_name; ?>" value="<? echo esc_attr($field_value); ?>" class="input-with-hint long"/><span class="bibliplug-hint"><? echo $hint; ?></span><?
						break;
					case 'link2':
						$hint = 'link full text html webpage to title';
						?><input name="<? echo $field_name; ?>" type="text" id="<? echo $field_name; ?>" value="<? echo esc_attr($field_value); ?>" class="input-with-hint long"/><span class="bibliplug-hint"><? echo $hint; ?></span><?
						break;
					case 'link3':
						$hint = 'link source/information page to secondary title';
						?><input name="<? echo $field_name; ?>" type="text" id="<? echo $field_name; ?>" value="<? echo esc_attr($field_value); ?>" class="input-with-hint long"/><span class="bibliplug-hint"><? echo $hint; ?></span><?
						break;
					
					case 'url':
						$hint = 'enter link here to make URL visible in reference';
						?><input name="<? echo $field_name; ?>" type="text" id="<? echo $field_name; ?>" value="<? echo esc_attr($field_value); ?>" class="input-with-hint long"/><span class="bibliplug-hint"><? echo $hint; ?></span><?
						break;
					
					default:
						?><input name="<? echo $field_name; ?>" type="text" id="<? echo $field_name; ?>" value="<? echo esc_attr($field_value); ?>" class="long"/><?
						break;
				} ?>
				</td>
			</tr>
		<? }
		} ?>
		</table><?
	}
}
?>