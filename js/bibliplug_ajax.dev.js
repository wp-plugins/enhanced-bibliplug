﻿(function($) {
	refreshOrder = function() {
	    var order = $('table#bibliplug-creators tbody').sortable("toArray");
	    for (i = 0; i < order.length; i++) {
	        $('table#bibliplug-creators tbody').children('tr#' + order[i])
	        	.find('input.order-index').val(i);
	    }
	}

	deleteCreator = function () {
		if ($(this).closest('tbody').children().length > 1) {
			var deleted = $(this).closest('tr');
			deleted.find('input.deleted').val(1);
			deleted.hide();
		}
		return false;
	}
	
	refreshDetails = function() {
		var data = {
			bib_id : $("#hiddenbibid").val(),
			new_type_id : $('#bibliplug_ref_type').val()
		};
			
		$.post(ajaxurl + '?action=bibliplug_change_ref_type', data, function(response){
			$("#bibliplug_details_meta_box").empty().append(response);
			$('select#bibliplug_ref_type').change(refreshDetails);
		});
	}

	$(document).ready(function($) {
		
		$('.input-with-hint')
			.focus(function () {
				$(this).next("span").css("background-color", "#FFFF33");
			})
			.blur(function () {
				$(this).next("span").css("background-color", "");
			});
		
		$('select#bibliplug_ref_type').change(refreshDetails);
		
		$('input#add-creator').click(function(){
			var newIndex = Math.floor(Math.random() * 1000);
			var creator_table = $(this).closest('#bibliplug_creators_meta_box').find('table#bibliplug-creators');
			var newRow = creator_table.find('tr').last().clone();
			var position = creator_table.children('tbody').children().length;
			var newOrderIndex = parseInt(newRow.find('input.order-index').val()) + 1;

			newRow.find('input').each(function() {
				$(this).attr('name', $(this).attr('name').replace(/\[-?[0-9]+\]/, '[-' + newIndex + ']')).val('');
			});
			
			newRow.find('select').attr('name', 'creator[-' + newIndex + '][creator_type_id]').val('');
			
			newRow.attr('id', 'creator-row-' + position);
			newRow.find('input.deleted').val(0);
			newRow.find('input.order-index').val(newOrderIndex);
			newRow.find('.delete-creator').click(deleteCreator);
			
			// in case the last row is hidden due to delete
			newRow.show();
			
			creator_table.append(newRow);
		});
		
		$('.delete-creator').click(deleteCreator);
		
		$('table#bibliplug-creators tbody')
			.sortable({
				placeholder : "bibliplug-state-highlight",
				stop : function(event, ui) {
					refreshOrder();
				}
			});
	});

})(jQuery);