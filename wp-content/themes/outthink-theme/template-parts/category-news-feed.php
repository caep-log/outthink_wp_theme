<?php
$template_args = $args ?? get_query_var('category_news_grid_args', []);

$grid_args = wp_parse_args(
    is_array($template_args) ? $template_args : [],
    [
        'title'         => '',
        'category_slug' => '',
    ]
);

$selected_category = null;

if (!empty($grid_args['category_slug'])) {
    $selected_category = get_category_by_slug(sanitize_title($grid_args['category_slug']));
}

if (!$selected_category instanceof WP_Term) {
    $queried_object = get_queried_object();

    if ($queried_object instanceof WP_Term && 'category' === $queried_object->taxonomy) {
        $selected_category = $queried_object;
    }
}

$query_args = [
    'post_type'      => 'post',
    'posts_per_page' => 8,
    'offset'         => 5,
];

if ($selected_category instanceof WP_Term) {
    $query_args['tax_query'] = [
        [
            'taxonomy' => 'category',
            'field'    => 'term_id',
            'terms'    => [$selected_category->term_id],
        ],
    ];
}

$section_title = $grid_args['title'];

if (empty($section_title) && $selected_category instanceof WP_Term) {
    $section_title = $selected_category->name;
}

if (empty($section_title)) {
    $section_title = 'More Stories';
}

$category_news = new WP_Query($query_args);

if ($category_news->have_posts()) :
    $category_posts = $category_news->posts;
?>

<section class="category-news-grid-section">
    <div class="news-category-header">
        <h3><?php echo esc_html($section_title); ?></h3>
    </div>

    <div class="category-news-grid">
        <?php foreach (array_slice($category_posts, 0, 8) as $grid_post) : ?>
            <article class="category-news-grid__item">
                <div class="category-news-grid__info">
                    <h2 class="category-news-grid__title">
                        <a href="<?php echo esc_url(get_permalink($grid_post)); ?>">
                            <?php echo esc_html(get_the_title($grid_post)); ?>
                        </a>
                    </h2>

                    <p class="category-news-grid__description">
                        <?php echo esc_html(get_the_excerpt($grid_post)); ?>
                    </p>

                    <div class="category-news-grid__meta">
                        <small>
                            By: <?php echo esc_html(get_the_author_meta('display_name', $grid_post->post_author)); ?>
                        </small>
                        <small>
                            <?php echo esc_html(get_the_date('', $grid_post)); ?>
                        </small>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
        <?php get_template_part('template-parts/ads'); ?>
    </div>
</section>

<?php
    wp_reset_postdata();
endif;
?>
