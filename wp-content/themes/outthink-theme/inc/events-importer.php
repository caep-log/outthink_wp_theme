<?php

if (!defined('ABSPATH')) {
    exit;
}

const OUTTHINK_EVENTS_IMPORT_CRON = 'outthink_events_import_cron';
const OUTTHINK_EVENTS_IMPORT_LOCK = 'outthink_events_import_fetching';
const OUTTHINK_EVENTS_IMPORT_API_URL = 'https://ne405b29o8.execute-api.us-east-1.amazonaws.com/prod/news';
const OUTTHINK_EVENTS_IMPORT_LIMIT = 20;
const OUTTHINK_EVENTS_IMPORT_INTERVAL = 2 * HOUR_IN_SECONDS;
const OUTTHINK_EVENTS_IMPORT_RETRY_INTERVAL = 15 * MINUTE_IN_SECONDS;
const OUTTHINK_EVENTS_IMPORT_LAST_ATTEMPT = 'outthink_events_import_last_attempt';
const OUTTHINK_EVENTS_IMPORT_LAST_SUCCESS = 'outthink_events_import_last_success';
const OUTTHINK_EVENTS_IMPORT_LAST_CREATED = 'outthink_events_import_last_created';

function outthink_events_import_add_cron_interval(array $schedules): array
{
    if (!isset($schedules['every_2_hours'])) {
        $schedules['every_2_hours'] = [
            'interval' => OUTTHINK_EVENTS_IMPORT_INTERVAL,
            'display'  => __('Every 2 Hours', 'outthink-theme'),
        ];
    }

    return $schedules;
}

add_filter('cron_schedules', 'outthink_events_import_add_cron_interval');

function outthink_events_import_activate(): void
{
    wp_clear_scheduled_hook(OUTTHINK_EVENTS_IMPORT_CRON);

    if (!wp_next_scheduled(OUTTHINK_EVENTS_IMPORT_CRON)) {
        wp_schedule_event(time() + MINUTE_IN_SECONDS, 'every_2_hours', OUTTHINK_EVENTS_IMPORT_CRON);
    }

    outthink_events_import_fetch_events();
}

add_action('after_switch_theme', 'outthink_events_import_activate');

function outthink_events_import_ensure_scheduled(): void
{
    if (!wp_next_scheduled(OUTTHINK_EVENTS_IMPORT_CRON)) {
        wp_schedule_event(time() + MINUTE_IN_SECONDS, 'every_2_hours', OUTTHINK_EVENTS_IMPORT_CRON);
    }
}

add_action('init', 'outthink_events_import_ensure_scheduled');

function outthink_events_import_fetch_if_due(): void
{
    if (wp_doing_cron() || wp_doing_ajax()) {
        return;
    }

    $now = time();
    $last_success = intval(get_option(OUTTHINK_EVENTS_IMPORT_LAST_SUCCESS, 0));
    $last_attempt = intval(get_option(OUTTHINK_EVENTS_IMPORT_LAST_ATTEMPT, 0));

    if ($last_success && ($now - $last_success) < OUTTHINK_EVENTS_IMPORT_INTERVAL) {
        return;
    }

    if ($last_attempt && ($now - $last_attempt) < OUTTHINK_EVENTS_IMPORT_RETRY_INTERVAL) {
        return;
    }

    outthink_events_import_fetch_events();
}

add_action('init', 'outthink_events_import_fetch_if_due', 22);

function outthink_events_import_deactivate(): void
{
    wp_clear_scheduled_hook(OUTTHINK_EVENTS_IMPORT_CRON);
    delete_transient(OUTTHINK_EVENTS_IMPORT_LOCK);
}

add_action('switch_theme', 'outthink_events_import_deactivate');
add_action(OUTTHINK_EVENTS_IMPORT_CRON, 'outthink_events_import_fetch_events');

function outthink_events_import_fetch_events(): bool
{
    if (get_transient(OUTTHINK_EVENTS_IMPORT_LOCK)) {
        return false;
    }

    set_transient(OUTTHINK_EVENTS_IMPORT_LOCK, true, MINUTE_IN_SECONDS);
    update_option(OUTTHINK_EVENTS_IMPORT_LAST_ATTEMPT, time(), false);

    $response = wp_remote_post(OUTTHINK_EVENTS_IMPORT_API_URL, [
        'timeout' => 25,
        'headers' => [
            'User-Agent'   => 'WordPress-Outthink-Theme/1.0',
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode([
            'typeFetch' => 'events',
        ]),
    ]);

    if (is_wp_error($response)) {
        delete_transient(OUTTHINK_EVENTS_IMPORT_LOCK);
        error_log('Outthink events import error: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    $raw_events = outthink_events_import_extract_response_items(is_array($data) ? $data : []);

    if (empty($raw_events)) {
        delete_transient(OUTTHINK_EVENTS_IMPORT_LOCK);
        error_log('Outthink events import error: empty API response');
        return false;
    }

    $events = outthink_events_import_normalize_events($raw_events);
    $events = outthink_events_import_unique_events($events);
    $events = array_slice($events, 0, OUTTHINK_EVENTS_IMPORT_LIMIT);
    $created_count = 0;

    foreach ($events as $event) {
        if (outthink_events_import_event_exists($event)) {
            continue;
        }

        $post_id = outthink_events_import_create_post($event);

        if ($post_id) {
            $created_count++;
        }
    }

    update_option(OUTTHINK_EVENTS_IMPORT_LAST_SUCCESS, time(), false);
    update_option(OUTTHINK_EVENTS_IMPORT_LAST_CREATED, $created_count, false);

    delete_transient(OUTTHINK_EVENTS_IMPORT_LOCK);
    return true;
}

function outthink_events_import_extract_response_items(array $data): array
{
    if (!empty($data['events']) && is_array($data['events'])) {
        return $data['events'];
    }

    if (!empty($data['data']) && is_array($data['data'])) {
        return $data['data'];
    }

    return [];
}

function outthink_events_import_normalize_events(array $items): array
{
    $events = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $title = sanitize_text_field($item['title'] ?? '');

        if ($title === '') {
            continue;
        }

        $events[] = [
            'title'       => $title,
            'description' => wp_kses_post($item['description'] ?? $item['content'] ?? ''),
            'url'         => outthink_events_import_extract_url($item),
            'image'       => outthink_events_import_extract_image($item),
            'location'    => outthink_events_import_extract_location($item),
            'date'        => outthink_events_import_normalize_event_date($item),
        ];
    }

    return $events;
}

function outthink_events_import_extract_url(array $item): string
{
    if (!empty($item['link'])) {
        return esc_url_raw($item['link']);
    }

    if (!empty($item['url'])) {
        return esc_url_raw($item['url']);
    }

    return '';
}

function outthink_events_import_extract_image(array $item): string
{
    if (!empty($item['thumbnail'])) {
        return esc_url_raw($item['thumbnail']);
    }

    if (!empty($item['image'])) {
        return esc_url_raw($item['image']);
    }

    return '';
}

function outthink_events_import_extract_location(array $item): string
{
    $location = $item['address'] ?? $item['location'] ?? '';

    if (is_array($location)) {
        return sanitize_text_field(implode(', ', array_filter($location)));
    }

    return sanitize_text_field($location);
}

function outthink_events_import_normalize_event_date(array $item): string
{
    if (!empty($item['date']) && is_array($item['date'])) {
        if (!empty($item['date']['start_date'])) {
            return sanitize_text_field($item['date']['start_date']);
        }

        if (!empty($item['date']['day']) && !empty($item['date']['month'])) {
            return sanitize_text_field($item['date']['day'] . ' ' . $item['date']['month'] . ' ' . date('Y'));
        }
    }

    $raw_date = $item['start_date'] ?? $item['publishedAt'] ?? $item['event_date'] ?? $item['date'] ?? '';

    if (!is_string($raw_date) || $raw_date === '') {
        return '';
    }

    $timestamp = strtotime($raw_date);

    if ($timestamp === false) {
        return sanitize_text_field($raw_date);
    }

    return date('F j, Y', $timestamp);
}

function outthink_events_import_unique_events(array $events): array
{
    $unique = [];

    foreach ($events as $event) {
        $key = $event['url'] ?: sanitize_title($event['title'] . '-' . $event['date']);
        $unique[$key] = $event;
    }

    return array_values($unique);
}

function outthink_events_import_event_exists(array $event): bool
{
    global $wpdb;

    if (!empty($event['url'])) {
        $exists_by_url = $wpdb->get_var($wpdb->prepare(
            "SELECT pm.post_id FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = 'outthink_event_url'
            AND pm.meta_value = %s
            AND p.post_status NOT IN ('trash', 'auto-draft')
            LIMIT 1",
            $event['url']
        ));

        if ($exists_by_url) {
            return true;
        }
    }

    $exists_by_title = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts}
        WHERE post_title = %s
        AND post_status NOT IN ('trash', 'auto-draft')
        LIMIT 1",
        $event['title']
    ));

    return !empty($exists_by_title);
}

function outthink_events_import_create_post(array $event): int
{
    $post_id = wp_insert_post([
        'post_title'   => $event['title'],
        'post_name'    => sanitize_title($event['title']),
        'post_content' => outthink_events_import_prepare_content($event),
        'post_status'  => 'publish',
        'post_author'  => get_current_user_id() ?: 1,
        'post_type'    => 'post',
    ]);

    if (is_wp_error($post_id) || !$post_id) {
        error_log('Outthink events import error creating post: ' . $event['title']);
        return 0;
    }

    $category_id = outthink_events_import_get_category_id();

    if ($category_id) {
        wp_set_post_categories($post_id, [$category_id]);
    }

    update_post_meta($post_id, 'outthink_event_date', $event['date']);
    update_post_meta($post_id, 'outthink_event_location', $event['location']);
    update_post_meta($post_id, 'outthink_event_url', $event['url']);

    if (!empty($event['image'])) {
        update_post_meta($post_id, 'outthink_event_image_url', $event['image']);
        update_post_meta($post_id, 'newsapi_image_url', $event['image']);
        update_post_meta($post_id, 'fifu_image_url', $event['image']);
        update_post_meta($post_id, 'fifu_image_alt', $event['title']);
    }

    return intval($post_id);
}

function outthink_events_import_get_category_id(): int
{
    $category = get_category_by_slug('events');

    if ($category) {
        return intval($category->term_id);
    }

    $term = wp_insert_term(__('Events', 'outthink-theme'), 'category', [
        'slug' => 'events',
    ]);

    if (is_wp_error($term)) {
        $term_id = $term->get_error_data('term_exists');
        return $term_id ? intval($term_id) : 0;
    }

    return intval($term['term_id']);
}

function outthink_events_import_prepare_content(array $event): string
{
    $content = '';
    $description = trim(wp_strip_all_tags($event['description']));

    if ($event['date'] !== '') {
        $content .= '<p><strong>' . esc_html__('Event date:', 'outthink-theme') . '</strong> ' . esc_html($event['date']) . '</p>';
    }

    if ($event['location'] !== '') {
        $content .= '<p><strong>' . esc_html__('Location:', 'outthink-theme') . '</strong> ' . esc_html($event['location']) . '</p>';
    }

    if ($description !== '') {
        $content .= '<p>' . esc_html($description) . '</p>';
    }

    if ($event['url'] !== '') {
        $content .= '<p><a href="' . esc_url($event['url']) . '" target="_blank" rel="noopener noreferrer"><strong>' . esc_html__('View event details', 'outthink-theme') . '</strong></a></p>';
    }

    return $content;
}
