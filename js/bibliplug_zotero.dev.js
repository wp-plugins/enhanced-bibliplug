(function($) {
	sync_zotero = function() {
		$('div#sync_progress').show();
		$('div#sync_result').empty();
		var id = $(this).val();
		$.get(ajaxurl + '?action=bibliplug_sync_zotero&id=' + id, null, function(response) {
			var r = wpAjax.parseAjaxResponse(response);
			$('div#sync_progress').hide();
			$('tr#zotero_account-' + id).children('td.last_updated').text(r.responses[0].data);
			$('div#sync_result').append(r.responses[1].data);
		});
	}

	delete_connection = function() {
		$('div#sync_progress').show();
		$('div#sync_result').empty();
		var id = $(this).val();
		$.get(ajaxurl + '?action=bibliplug_delete_connection&id=' + id, null, function(response){
			$('div#sync_progress').hide();
			$('div#sync_result').append(response);
			$('tr#zotero_account-' + id).remove();
		});
	}

})(jQuery);


jQuery(document).ready(function($) {
	$('div#sync_progress').hide();

	$('button.sync-now').click(sync_zotero);
	$('button.delete').click(delete_connection);

	$('#submit').click(function(){
        var form = $(this).parents('form');

        if (!validateForm(form))
		{
            return false;
		}

		$.post(ajaxurl + '?action=bibliplug_add_connection', form.serialize(), function(response){
			var newRow = $(response);
			var lastRow = $('table.Connections').find('tr').last();
			if (!lastRow.hasClass('alternate')) {
				newRow.addClass('alternate');
			}
			lastRow.after(newRow);
			
			$('button.sync-now').click(sync_zotero);
			$('button.delete').click(delete_connection);
			$('input[type="text"]:visible, textarea:visible', form).val('');
		});

		return false;
	});
});

