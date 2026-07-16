<?php 

if (!defined('ABSPATH')) {
    exit;
}

function mi_theme_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');

    register_nav_menus([
        'primary' => 'Menú Principal',
    ]);
}

add_action('after_setup_theme', 'mi_theme_setup');

function mi_theme_assets(): void
{
    wp_enqueue_style(
        'mi-theme-style',
        get_stylesheet_uri(),
        [],
        wp_get_theme()->get('Version')
    );
}

add_action('wp_enqueue_scripts', 'mi_theme_assets');

function mytheme_enqueue_assets() {
    wp_enqueue_style(
        'bootstrap-icons',
        'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css',
        [],
        '1.13.1'
    );
}
add_action('wp_enqueue_scripts', 'mytheme_enqueue_assets');

function outthink_track_post_views(): void
{
    if (!is_single() || get_post_type() !== 'post') {
        return;
    }

    $post_id = get_queried_object_id();
    $views = intval(get_post_meta($post_id, 'outthink_post_views', true));

    update_post_meta($post_id, 'outthink_post_views', $views + 1);
}

add_action('wp', 'outthink_track_post_views');

require_once get_template_directory() . '/inc/news-importer.php';
require_once get_template_directory() . '/inc/events-importer.php';
