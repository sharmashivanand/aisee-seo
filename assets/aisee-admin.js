jQuery(document).ready(function ($) { //wrapper
    	if( $('#_aisee_focuskw_suggestions').length == 0 ){
	return;
	}
	$('#_aisee_focuskw_suggestions').autocomplete({
        minChars: 1,
        source: function (term, suggest) {
            var promise = googleSuggest();
            returnSearch = function (term, choices) {
                suggest(choices);
            }
            jQuery.when(promise).then(function (data) {
                term = term.toString().toLowerCase();
                var result = [];
                jQuery.each(data[1], function (item, value) {
                    var stripedValue = value[0].replace(/<[^>]+>/g, '');
                    result.push(stripedValue);
                })
                returnSearch(term, result);
            })
        },
        classes: {
            "ui-autocomplete": "highlight"
        }
    }).autocomplete( "widget" ).addClass( "_aisee_focuskw_suggestions" ).removeClass( "ui-widget ui-front ui-menu ui-widget-content" );
    function googleSuggest(returnSearch) {
        var term = jQuery('#_aisee_focuskw_suggestions').val();
        var service = {
            youtube: { client: 'youtube', ds: 'yt' },
            books: { client: 'books', ds: 'bo' },
            products: { client: 'products-cc', ds: 'sh' },
            news: { client: 'news-cc', ds: 'n' },
            images: { client: 'img', ds: 'i' },
            web: { client: 'hp', ds: '' },
            recipes: { client: 'hp', ds: 'r' }
        };
        var promise = jQuery.ajax({
            url: 'https://clients1.google.com/complete/search',
            dataType: 'jsonp',
            data: {
                q: term,
                pws: '0',
                gl: aisee.gl,
                nolabels: 't',
                client: service.web.client,
                ds: service.web.ds
            }
        })
        return promise
    };
    $('#_aisee_aisee_tag_cloud').click(function(e) {
        e.preventDefault();
        $(this).addClass('aisee-btn-loading');
        aisee_tag_cloud = {
            aisee_tag_cloud_nonce: aisee.aisee_tag_cloud_nonce,
            action: "aisee_tag_cloud",
            drop_percentage : $('#_aisee_drop_percentage').val(),
            trim : $('#_aisee_trim_length').val(),
            cachebust: Date.now(),
            post_id : aisee.post_id,
        };
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: aisee_tag_cloud,
            success: function (res) {
                console.dir(res);
                $('#_aisee_aisee_tag_cloud_response').html(res);
                $('#_aisee_aisee_tag_cloud').removeClass('aisee-btn-loading');
                if(res.hasOwnProperty('success') && res.success == true && res.hasOwnProperty('data') && res.data.length) {
                    $('#_aisee_aisee_tag_cloud_response').html(res.data);
                    console.log(res.data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $('#_aisee_aisee_tag_cloud_response').html(errorThrown);
            },
        });
    });
});
