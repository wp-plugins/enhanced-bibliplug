(function(a){sync_zotero=function(){a("div#sync_progress").show();a("div#sync_result").empty();var b=a(this).val();a.get(ajaxurl+"?action=bibliplug_sync_zotero&id="+b,null,function(d){a("div#sync_progress").hide();var c=wpAjax.parseAjaxResponse(d);if(!c){a("div#sync_result").append(d);return}a("tr#zotero_account-"+b).children("td.last_updated").text(c.responses[0].data);a("div#sync_result").append(c.responses[1].data)})};delete_connection=function(){if(confirm("You are about to delete the connection and local copies of the references in this connection.")){a("div#sync_progress").show();a("div#sync_result").empty();var b=a(this).val();a.get(ajaxurl+"?action=bibliplug_delete_connection&id="+b,null,function(c){a("div#sync_progress").hide();a("div#sync_result").append(c);a("tr#zotero_account-"+b).remove()})}}})(jQuery);jQuery(document).ready(function(a){a("div#sync_progress").hide();a("button.sync-now").click(sync_zotero);a("button.delete").click(delete_connection);a("#submit").click(function(){var b=a(this).parents("form");if(!validateForm(b)){return false}a.post(ajaxurl+"?action=bibliplug_add_connection",b.serialize(),function(e){a("table.Connections").find("tr.no-items").remove();var d=a(e);var c=a("table.Connections").find("tr").last();if(!c.hasClass("alternate")){d.addClass("alternate")}c.after(d);a("button.sync-now").click(sync_zotero);a("button.delete").click(delete_connection);a('input[type="text"]:visible, textarea:visible',b).val("")});return false})});