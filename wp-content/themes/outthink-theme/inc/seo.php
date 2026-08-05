<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * SEO metadata for Outthink articles.
 */
function outthink_seo_head(): void
{
    if (!is_single() || get_post_type() !== 'post') {
        return;
    }

    $post_id = get_queried_object_id();

    $title = get_the_title($post_id);
    $permalink = get_permalink($post_id);
    $description = get_the_excerpt($post_id);

    if (!$description) {
        $description = wp_trim_words(
            wp_strip_all_tags(get_post_field('post_content', $post_id)),
            30,
            '...'
        );
    }

    $image = get_the_post_thumbnail_url($post_id, 'large');

    if (!$image) {
        $image = get_post_meta($post_id, 'newsapi_image_url', true);
    }

    $source = get_post_meta($post_id, 'newsapi_source', true);
    $original_url = get_post_meta($post_id, 'newsapi_url', true);
    $original_date = get_post_meta($post_id, 'newsapi_published', true);

    /*
     * Basic SEO
     */
    echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";

    echo '<link rel="canonical" href="' . esc_url($permalink) . '">' . "\n";

    /*
     * Open Graph
     */
    echo '<meta property="og:type" content="article">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($permalink) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";

    if ($image) {
        echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
    }

    /*
     * Twitter / X
     */
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";

    if ($image) {
        echo '<meta name="twitter:image" content="' . esc_url($image) . '">' . "\n";
    }

    /*
     * Article Schema
     */
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',

        'headline' => $title,

        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => $permalink,
        ],

        'datePublished' => get_the_date(DATE_W3C, $post_id),
        'dateModified' => get_the_modified_date(DATE_W3C, $post_id),

        'publisher' => [
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => home_url('/'),
        ],
    ];

    if ($description) {
        $schema['description'] = $description;
    }

    if ($image) {
        $schema['image'] = [$image];
    }

    /*
     * Information about the original source.
     */
    if ($source || $original_url) {
        $schema['isBasedOn'] = [
            '@type' => 'CreativeWork',
        ];

        if ($source) {
            $schema['isBasedOn']['publisher'] = [
                '@type' => 'Organization',
                'name' => $source,
            ];
        }

        if ($original_url) {
            $schema['isBasedOn']['url'] = $original_url;
        }

        if ($original_date) {
            $timestamp = strtotime($original_date);

            if ($timestamp) {
                $schema['isBasedOn']['datePublished'] = wp_date(
                    DATE_W3C,
                    $timestamp
                );
            }
        }
    }

    echo '<script type="application/ld+json">';
    echo wp_json_encode(
        $schema,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    echo '</script>' . "\n";
}

add_action('wp_head', 'outthink_seo_head', 5);