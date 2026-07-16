<?php

if (!defined('ABSPATH')) {
    exit;
}

const OUTTHINK_NEWS_IMPORT_CRON = 'outthink_news_import_cron';
const OUTTHINK_NEWS_IMPORT_LOCK = 'outthink_news_import_fetching';
const OUTTHINK_NEWS_IMPORT_API_URL = 'https://qqrozvm513.execute-api.us-east-1.amazonaws.com/deploy/api-fetch';
const OUTTHINK_NEWS_IMPORT_MIN_SCORE = 18;
const OUTTHINK_NEWS_IMPORT_LIMIT = 20;
const OUTTHINK_NEWS_IMPORT_INTERVAL = 2 * HOUR_IN_SECONDS;
const OUTTHINK_NEWS_IMPORT_RETRY_INTERVAL = 15 * MINUTE_IN_SECONDS;
const OUTTHINK_NEWS_IMPORT_LAST_ATTEMPT = 'outthink_news_import_last_attempt';
const OUTTHINK_NEWS_IMPORT_LAST_SUCCESS = 'outthink_news_import_last_success';
const OUTTHINK_NEWS_IMPORT_LAST_CREATED = 'outthink_news_import_last_created';

function outthink_news_import_register_meta(): void
{
    register_post_meta('post', 'score', [
        'show_in_rest' => true,
        'single'       => true,
        'type'         => 'number',
    ]);
}

add_action('init', 'outthink_news_import_register_meta');

function outthink_news_import_add_cron_interval(array $schedules): array
{
    $schedules['every_2_hours'] = [
        'interval' => OUTTHINK_NEWS_IMPORT_INTERVAL,
        'display'  => __('Every 2 Hours', 'outthink-theme'),
    ];

    return $schedules;
}

add_filter('cron_schedules', 'outthink_news_import_add_cron_interval');

function outthink_news_import_activate(): void
{
    wp_clear_scheduled_hook(OUTTHINK_NEWS_IMPORT_CRON);

    if (!wp_next_scheduled(OUTTHINK_NEWS_IMPORT_CRON)) {
        wp_schedule_event(time() + MINUTE_IN_SECONDS, 'every_2_hours', OUTTHINK_NEWS_IMPORT_CRON);
    }

    outthink_news_import_fetch_articles();
}

add_action('after_switch_theme', 'outthink_news_import_activate');

function outthink_news_import_ensure_scheduled(): void
{
    if (!wp_next_scheduled(OUTTHINK_NEWS_IMPORT_CRON)) {
        wp_schedule_event(time() + MINUTE_IN_SECONDS, 'every_2_hours', OUTTHINK_NEWS_IMPORT_CRON);
    }
}

add_action('init', 'outthink_news_import_ensure_scheduled');

function outthink_news_import_fetch_if_due(): void
{
    if (wp_doing_cron() || wp_doing_ajax()) {
        return;
    }

    $now = time();
    $last_success = intval(get_option(OUTTHINK_NEWS_IMPORT_LAST_SUCCESS, 0));
    $last_attempt = intval(get_option(OUTTHINK_NEWS_IMPORT_LAST_ATTEMPT, 0));

    if ($last_success && ($now - $last_success) < OUTTHINK_NEWS_IMPORT_INTERVAL) {
        return;
    }

    if ($last_attempt && ($now - $last_attempt) < OUTTHINK_NEWS_IMPORT_RETRY_INTERVAL) {
        return;
    }

    outthink_news_import_fetch_articles();
}

add_action('init', 'outthink_news_import_fetch_if_due', 20);

function outthink_news_import_deactivate(): void
{
    wp_clear_scheduled_hook(OUTTHINK_NEWS_IMPORT_CRON);
    delete_transient(OUTTHINK_NEWS_IMPORT_LOCK);
}

add_action('switch_theme', 'outthink_news_import_deactivate');
add_action(OUTTHINK_NEWS_IMPORT_CRON, 'outthink_news_import_fetch_articles');

function outthink_news_import_fetch_articles(): bool
{
    if (get_transient(OUTTHINK_NEWS_IMPORT_LOCK)) {
        return false;
    }

    set_transient(OUTTHINK_NEWS_IMPORT_LOCK, true, MINUTE_IN_SECONDS);
    update_option(OUTTHINK_NEWS_IMPORT_LAST_ATTEMPT, time(), false);

    $response = wp_remote_post(OUTTHINK_NEWS_IMPORT_API_URL, [
        'timeout' => 25,
        'headers' => [
            'User-Agent'   => 'WordPress-Outthink-Theme/1.0',
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode([
            'typeFetch' => 'news',
        ]),
    ]);

    if (is_wp_error($response)) {
        delete_transient(OUTTHINK_NEWS_IMPORT_LOCK);
        error_log('Outthink news import error: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (empty($data['data']) || !is_array($data['data'])) {
        delete_transient(OUTTHINK_NEWS_IMPORT_LOCK);
        error_log('Outthink news import error: empty API response');
        return false;
    }

    $articles = outthink_news_import_normalize_articles($data['data']);
    $articles = outthink_news_import_unique_articles($articles);

    usort($articles, static function (array $a, array $b): int {
        return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
    });

    $articles = array_slice($articles, 0, OUTTHINK_NEWS_IMPORT_LIMIT);

    $created_count = 0;

    foreach ($articles as $article) {
        if ($article['score'] < OUTTHINK_NEWS_IMPORT_MIN_SCORE) {
            continue;
        }

        if (outthink_news_import_article_exists($article)) {
            continue;
        }

        $post_id = outthink_news_import_create_post($article);

        if ($post_id) {
            $created_count++;
        }
    }

    update_option(OUTTHINK_NEWS_IMPORT_LAST_SUCCESS, time(), false);
    update_option(OUTTHINK_NEWS_IMPORT_LAST_CREATED, $created_count, false);

    delete_transient(OUTTHINK_NEWS_IMPORT_LOCK);
    return true;
}

function outthink_news_import_manual_url(): string
{
    return wp_nonce_url(
        admin_url('admin-post.php?action=outthink_news_import_manual'),
        'outthink_news_import_manual'
    );
}

function outthink_news_import_handle_manual_fetch(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You are not allowed to run this import.', 'outthink-theme'));
    }

    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'outthink_news_import_manual')) {
        wp_die(__('Invalid request.', 'outthink-theme'));
    }

    $news_imported = outthink_news_import_fetch_articles();
    $events_imported = function_exists('outthink_events_import_fetch_events') ? outthink_events_import_fetch_events() : false;
    $imported = ($news_imported || $events_imported) ? '1' : '0';
    $redirect = remove_query_arg('outthink_news_imported', wp_get_referer() ?: home_url('/'));

    wp_safe_redirect(add_query_arg('outthink_news_imported', $imported, $redirect));
    exit;
}

add_action('admin_post_outthink_news_import_manual', 'outthink_news_import_handle_manual_fetch');

function outthink_news_import_normalize_articles(array $items): array
{
    $articles = [];

    foreach ($items as $item) {
        if (empty($item['url']) || empty($item['title'])) {
            continue;
        }

        $articles[] = [
            'title'       => sanitize_text_field($item['title']),
            'url'         => esc_url_raw($item['url']),
            'description' => wp_kses_post($item['content'] ?? $item['description'] ?? ''),
            'publishedAt' => sanitize_text_field($item['publishedAt'] ?? ''),
            'source'      => outthink_news_import_extract_source($item),
            'image'       => !empty($item['image']) ? esc_url_raw($item['image']) : '',
            'score'       => intval($item['score'] ?? 0),
            'category'    => outthink_news_import_extract_category($item),
        ];
    }

    return $articles;
}

function outthink_news_import_extract_source(array $item): string
{
    if (!empty($item['source']) && is_array($item['source'])) {
        return sanitize_text_field($item['source']['name'] ?? '');
    }

    return sanitize_text_field($item['source'] ?? '');
}

function outthink_news_import_extract_category(array $item): string
{
    if (!empty($item['category'])) {
        return sanitize_text_field($item['category']);
    }

    if (!empty($item['categories']) && is_array($item['categories'])) {
        return sanitize_text_field(reset($item['categories']));
    }

    $source = outthink_news_import_extract_source($item);

    if ($source !== '') {
        return $source;
    }

    return __('News', 'outthink-theme');
}

function outthink_news_import_unique_articles(array $articles): array
{
    $unique = [];

    foreach ($articles as $article) {
        $unique[$article['url']] = $article;
    }

    return array_values($unique);
}

function outthink_news_import_article_exists(array $article): bool
{
    global $wpdb;

    $exists_by_url = $wpdb->get_var($wpdb->prepare(
        "SELECT pm.post_id FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = 'newsapi_url'
        AND pm.meta_value = %s
        AND p.post_status NOT IN ('trash', 'auto-draft')
        LIMIT 1",
        $article['url']
    ));

    if ($exists_by_url) {
        return true;
    }

    $exists_by_title = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts}
        WHERE post_title = %s
        AND post_status NOT IN ('trash', 'auto-draft')
        LIMIT 1",
        $article['title']
    ));

    return !empty($exists_by_title);
}

function outthink_news_import_create_post(array $article): int
{
    $post_id = wp_insert_post([
        'post_title'   => $article['title'],
        'post_name'    => sanitize_title($article['title']),
        'post_content' => outthink_news_import_prepare_content($article),
        'post_status'  => 'publish',
        'post_author'  => get_current_user_id() ?: 1,
        'post_type'    => 'post',
    ]);

    if (is_wp_error($post_id) || !$post_id) {
        error_log('Outthink news import error creating post: ' . $article['title']);
        return 0;
    }

    $category_id = outthink_news_import_get_category_id($article);

    if ($category_id) {
        wp_set_post_categories($post_id, [$category_id]);
    }

    update_post_meta($post_id, 'score', $article['score']);
    update_post_meta($post_id, 'newsapi_url', $article['url']);
    update_post_meta($post_id, 'newsapi_source', $article['source']);
    update_post_meta($post_id, 'newsapi_published', $article['publishedAt']);

    if (!empty($article['image'])) {
        update_post_meta($post_id, 'newsapi_image_url', $article['image']);
        update_post_meta($post_id, 'fifu_image_url', $article['image']);
        update_post_meta($post_id, 'fifu_image_alt', $article['title']);
    }

    return intval($post_id);
}

function outthink_news_import_get_category_id(array $article): int
{
    $category_name = $article['category'] ?: __('News', 'outthink-theme');
    $category_slug = sanitize_title($category_name);
    $category = get_category_by_slug($category_slug);

    if ($category) {
        return intval($category->term_id);
    }

    $term = wp_insert_term($category_name, 'category', [
        'slug' => $category_slug,
    ]);

    if (is_wp_error($term)) {
        $term_id = $term->get_error_data('term_exists');
        if ($term_id) {
            return intval($term_id);
        }

        return 0;
    }

    return intval($term['term_id']);
}

function outthink_news_import_prepare_content(array $article): string
{
    $content = '';
    $description = trim(wp_strip_all_tags($article['description']));

    if ($description !== '') {
        $description = preg_replace('/\[\+\d+\s+chars?\]/', '', $description);
        $first_period = strpos($description, '. ');

        if ($first_period !== false) {
            $description = substr($description, 0, $first_period + 1);
        }

        $content .= '<p>' . esc_html(trim($description)) . ' Read more, link below.</p>';
    }

    $content .= '<p><a href="' . esc_url($article['url']) . '" target="_blank" rel="noopener noreferrer"><strong>Read the full article here</strong></a></p>';

    return $content;
}
