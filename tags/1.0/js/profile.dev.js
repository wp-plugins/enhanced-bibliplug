jQuery(function ($) {
	$("input#nickname").parent().parent().hide();
	$("select#display_name").parent().parent().hide();
	$("textarea#description").parent().parent().hide();

	var user_id = $.getQueryString('user_id');
	$.get(ajaxurl + "?action=bibliplug_user_extra&uid=" + user_id, null, function (r) {
		var res = wpAjax.parseAjaxResponse(r);

		if (typeof(res) == 'object')
		{
			$("input#first_name").parent().parent().after(res.responses[0].data);
			$("textarea#description").parent().parent().before(res.responses[1].data);
		}

		if (typeof (tinyMCE) == "object" && typeof (tinyMCE.execCommand) == "function") {
			tinyMCE.execCommand("mceAddControl", false, "author_bio");
		}

		$(".visual").live("click", function () {
			$(this).addClass("active");
			$(".plain").removeClass("active");

			tinyMCE.execCommand('mceAddControl', false, 'author_bio');
			return false;
		});

		$(".plain").live("click", function () {
			$(this).addClass("active");
			$(".visual").removeClass("active");

			tinyMCE.execCommand('mceRemoveControl', false, 'author_bio');
			return false;
		});
	});
});

(function ($) {
    $.extend({      
        getQueryString: function (name) {
            function parseParams() {
                var params = {},
                    e,
                    a = /\+/g,  // Regex for replacing addition symbol with a space
                    r = /([^&=]+)=?([^&]*)/g,
                    d = function (s) { return decodeURIComponent(s.replace(a, " ")); },
                    q = window.location.search.substring(1);

                while (e = r.exec(q))
                    params[d(e[1])] = d(e[2]);

                return params;
            }

            if (!this.queryStringParams)
                this.queryStringParams = parseParams(); 

            return this.queryStringParams[name];
        }
    });
})(jQuery);
