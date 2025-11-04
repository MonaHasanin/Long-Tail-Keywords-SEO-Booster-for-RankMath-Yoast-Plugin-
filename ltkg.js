jQuery(document).ready(function($) {
    $('#ltkg-generate-single').click(function() {
        var post_id = $('#post_ID').val();
        var nonce = $('#ltkg_nonce_field').val();
        $('#ltkg-response').text('جارٍ التوليد...');
        $.post(ajaxurl, {
            action: 'ltkg_generate_keywords',
            post_id: post_id,
            nonce: nonce
        }, function(response) {
            if (response.success) {
                $('#ltkg-response').html('<strong>تم التوليد:</strong><br>' + response.data.keywords.join('<br>'));
            } else {
                $('#ltkg-response').text('فشل التوليد');
            }
        });
    });
});
