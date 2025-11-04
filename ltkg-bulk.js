jQuery(document).ready(function($) {
    $('#ltkg-generate-all').click(function() {
        var nonce = $('#ltkg_nonce_field').val();
        $('#ltkg-bulk-response').html('جاري توليد الكلمات لكل المقالات...');
        $.post(ajaxurl, {
            action: 'ltkg_generate_all_posts',
            nonce: nonce
        }, function(response) {
            if (response.success) {
                let html = '<strong>تم التوليد:</strong><ul>';
                response.data.forEach(item => {
                    html += '<li><strong>' + item.title + '</strong>: ' + item.keywords.join(', ') + '</li>';
                });
                html += '</ul>';
                $('#ltkg-bulk-response').html(html);
            } else {
                $('#ltkg-bulk-response').text('فشل التوليد');
            }
        });
    });

    $('#ltkg-generate-today').click(function() {
        var nonce = $('#ltkg_nonce_field').val();
        $('#ltkg-bulk-response').html('جاري توليد الكلمات لمقالات اليوم...');
        $.post(ajaxurl, {
            action: 'ltkg_generate_today_posts',
            nonce: nonce
        }, function(response) {
            if (response.success) {
                let html = '<strong>تم التوليد:</strong><ul>';
                response.data.forEach(item => {
                    html += '<li><strong>' + item.title + '</strong> (' + item.status + '): ' + item.keywords.join(', ') + '</li>';
                });
                html += '</ul>';
                $('#ltkg-bulk-response').html(html);
            } else {
                $('#ltkg-bulk-response').text('فشل التوليد');
            }
        });
    });
});
