<?php

function validate_url($data) {
	if($data && substr_compare($data, "http",  0, 4, true)) {
		return 'http://'.$data;
	} else {
		return $data;
	}
}

function is_field_numeric($field_name) {
	switch($field_name) {
		case 'id':
		case 'type_id':
		case 'volume':
		case 'number_of_volumes':
		case 'start_page':
		case 'end_page':
		case 'peer_reviewed':
			return true;
		default:
			return false;
	}
}

function print_name($creator, $first_name_first, $include_middle_name=false) {

	$first_name = $creator->first_name;
	$last_name = $creator->last_name;
	
	if ($include_middle_name && $creator->middle_name) {
		$first_name .= ' '.$creator->middle_name;
	}
	
	if ($creator->prefix) {
		if (BIB_LAST_NAME_FORMAT == 'english') {
			$last_name = $creator->prefix.' '.$last_name;
		} else {
			$first_name = $first_name.' '.$creator->prefix;
		}
	}

	if ($first_name)
	{
		if ($first_name_first) {
			return $first_name.' '.$last_name;
		} else {
			return $last_name.', '.$first_name;
		}
	}
	
	return $last_name;
}

?>
