jQuery(function ($) {
	$("input#nickname").closest('tr').hide();
	$("select#display_name").closest('tr').hide();
	$("input#first_name").closest('tr').after($('table#bibliplug_author_name_extra').find('tr'));
	$("textarea#description").closest('tr').replaceWith($("table#bibliplug_author_description_extra").find('tr'));
});