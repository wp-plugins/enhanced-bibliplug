jQuery(document).ready(function($) {
	$('input.schema').click(function(){
		var message = "You are about to change the database schema.\nMake sure you have backed up your data.\n\n'Cancel' to stop, 'OK' to delete";
		if (confirm(message)) {
			return true;
		}
		return false;
	});
});