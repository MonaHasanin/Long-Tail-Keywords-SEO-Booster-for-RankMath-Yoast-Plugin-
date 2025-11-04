<?php
/*
Plugin Name: Long Tail Keywords SEO Booster
Description: توليد كلمات مفتاحية طويلة الذيل تلقائيًا من عنوان ومحتوى المقالات. يدعم Rank Math و Yoast. من إعداد: منى جلال.
Version: 1.6
Author: Mona Jalal
*/

// زر في صفحة المقالة
add_action('add_meta_boxes', function () {
    add_meta_box('ltkg_box', 'Long Tail Keywords Generator', 'ltkg_box_callback', 'post', 'side');
});
function ltkg_box_callback($post) {
    echo '<button type="button" class="button button-primary" id="ltkg-generate-single">توليد الكلمات الطويلة لهذه المقالة</button>';
    echo '<div id="ltkg-response" style="margin-top:10px;"></div>';
    wp_nonce_field('ltkg_nonce_action', 'ltkg_nonce_field');
}

// تحميل سكربتات JS
add_action('admin_enqueue_scripts', function($hook) {
    if (in_array($hook, ['post.php', 'edit.php', 'tools_page_ltkg-bulk'])) {
        wp_enqueue_script('ltkg-script', plugin_dir_url(__FILE__) . 'ltkg.js', ['jquery'], null, true);
        wp_enqueue_script('ltkg-bulk', plugin_dir_url(__FILE__) . 'ltkg-bulk.js', ['jquery'], null, true);
    }
});

// توليد كلمات لمقالة واحدة
add_action('wp_ajax_ltkg_generate_keywords', function() {
    if (!current_user_can('edit_posts') || !check_ajax_referer('ltkg_nonce_action', 'nonce', false)) {
        wp_send_json_error('Unauthorized');
    }

    $post_id = intval($_POST['post_id']);
    $post = get_post($post_id);
    if (!$post) wp_send_json_error('Post not found');

    $text = $post->post_title . '. ' . wp_strip_all_tags($post->post_content);
    $keywords = ltkg_extract_long_tail_phrases($text);

    // Rank Math: كل الجمل
    update_post_meta($post_id, 'rank_math_focus_keyword', implode(', ', $keywords));
    // Yoast: فقط الجملة الأولى
    update_post_meta($post_id, '_yoast_wpseo_focuskw', $keywords[0]);

    wp_send_json_success(['keywords' => $keywords]);
});

// صفحة توليد جماعي
add_action('admin_menu', function () {
    add_submenu_page('tools.php', 'Long Tail Keywords - Bulk', 'Long Tail Keywords - Bulk', 'manage_options', 'ltkg-bulk', 'ltkg_bulk_page');
});
function ltkg_bulk_page() {
    echo '<div class="wrap"><h1>توليد كلمات طويلة الذيل</h1>';
    echo '<button class="button button-primary" id="ltkg-generate-all">لكل المقالات</button> ';
    echo '<button class="button" id="ltkg-generate-today">لمقالات اليوم فقط</button>';
    echo '<div id="ltkg-bulk-response" style="margin-top:15px;"></div></div>';
    wp_nonce_field('ltkg_nonce_action', 'ltkg_nonce_field');
}

// توليد لكل المقالات
add_action('wp_ajax_ltkg_generate_all_posts', function () {
    if (!current_user_can('manage_options') || !check_ajax_referer('ltkg_nonce_action', 'nonce', false)) {
        wp_send_json_error('Unauthorized');
    }

    $posts = get_posts(['post_type' => 'post', 'posts_per_page' => -1, 'post_status' => 'publish']);
    $results = [];

    foreach ($posts as $post) {
        $text = $post->post_title . '. ' . wp_strip_all_tags($post->post_content);
        $keywords = ltkg_extract_long_tail_phrases($text);
        update_post_meta($post->ID, 'rank_math_focus_keyword', implode(', ', $keywords));
        update_post_meta($post->ID, '_yoast_wpseo_focuskw', $keywords[0]);
        $results[] = ['title' => $post->post_title, 'keywords' => $keywords];
    }

    wp_send_json_success($results);
});

// توليد لمقالات اليوم (منشورة + مجدولة + مسودات)
add_action('wp_ajax_ltkg_generate_today_posts', function () {
    if (!current_user_can('manage_options') || !check_ajax_referer('ltkg_nonce_action', 'nonce', false)) {
        wp_send_json_error('Unauthorized');
    }

    $today = date('Y-m-d');
    $posts = get_posts([
        'post_type' => 'post',
        'posts_per_page' => -1,
        'post_status' => ['publish', 'future', 'draft'],
        'date_query' => [[
            'after' => $today . ' 00:00:00',
            'before' => $today . ' 23:59:59',
            'inclusive' => true,
        ]],
    ]);

    $results = [];
    foreach ($posts as $post) {
        $text = $post->post_title . '. ' . wp_strip_all_tags($post->post_content);
        $keywords = ltkg_extract_long_tail_phrases($text);
        update_post_meta($post->ID, 'rank_math_focus_keyword', implode(', ', $keywords));
        update_post_meta($post->ID, '_yoast_wpseo_focuskw', $keywords[0]);
        $results[] = ['title' => $post->post_title, 'status' => $post->post_status, 'keywords' => $keywords];
    }

    wp_send_json_success($results);
});

// توليد تلقائي يوميًا (WP-Cron)
register_activation_hook(__FILE__, function () {
    if (!wp_next_scheduled('ltkg_daily_keyword_generation')) {
        wp_schedule_event(strtotime('00:00:00'), 'daily', 'ltkg_daily_keyword_generation');
    }
});
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('ltkg_daily_keyword_generation');
});
add_action('ltkg_daily_keyword_generation', function () {
    $today = date('Y-m-d');
    $posts = get_posts([
        'post_type' => 'post',
        'posts_per_page' => -1,
        'post_status' => ['publish', 'future', 'draft'],
        'date_query' => [[
            'after' => $today . ' 00:00:00',
            'before' => $today . ' 23:59:59',
            'inclusive' => true,
        ]],
    ]);

    foreach ($posts as $post) {
        $text = $post->post_title . '. ' . wp_strip_all_tags($post->post_content);
        $keywords = ltkg_extract_long_tail_phrases($text);
        update_post_meta($post->ID, 'rank_math_focus_keyword', implode(', ', $keywords));
        update_post_meta($post->ID, '_yoast_wpseo_focuskw', $keywords[0]);
    }
});

// دالة استخراج الكلمات الطويلة الذيل
function ltkg_extract_long_tail_phrases($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $text);
    $words = preg_split('/\s+/', $text);
    $stop_words = array_merge(ltkg_arabic_stopwords(), ltkg_english_stopwords());

    $phrases = [];
    $length = 3;
    for ($i = 0; $i < count($words) - $length + 1; $i++) {
        $chunk = array_slice($words, $i, $length);
        $filtered = array_filter($chunk, fn($w) => !in_array($w, $stop_words));
        if (count($filtered) >= 2) {
            $phrases[] = implode(' ', $chunk);
        }
    }

    $frequency = array_count_values($phrases);
    arsort($frequency);
    return array_slice(array_keys($frequency), 0, 5);
}

function ltkg_arabic_stopwords() {
    return ['في','من','على','إلى','عن','ما','لا','لم','لن','إن','أن','هذا','هذه','ذلك','كانت','كان','هو','هي','هم','كما','قد','أي','كل','أو','بل','ثم','إذا','بين','بعد','قبل','حتى','مع','نحن'];
}

function ltkg_english_stopwords() {
    return ['the','and','for','are','but','not','you','with','this','that','have','from','they','will','their','would','there','what','about','which','when','make','can','has','was','his','her','how','our'];
}
?>