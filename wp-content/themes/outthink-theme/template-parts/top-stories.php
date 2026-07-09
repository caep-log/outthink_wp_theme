<?php
$top_stories_query = new WP_Query([
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => 5,
    'ignore_sticky_posts' => true,
    'meta_key'            => 'score',
    'orderby'             => 'meta_value_num',
    'order'               => 'DESC',
    'date_query'          => [
        [
            'after'     => '1 week ago',
            'inclusive' => true,
        ],
    ],
    'meta_query'          => [
        [
            'key'     => 'score',
            'compare' => 'EXISTS',
        ],
    ],
]);

$story_classes = [
    'top-storie-one',
    'top-storie-two',
    'top-storie-three',
    'top-storie-four',
    'top-storie-five',
];

$indicator_classes = [
    'indicator-one-note',
    'indicator-two-note',
    'indicator-three-note',
    'indicator-four-note',
    'indicator-five-note',
];

$indicator_icons = [
    'bi-trophy',
    'bi-award',
    'bi-award',
    'bi-star',
    'bi-star',
];
?>

<section class="top-stories-section">
    <h1>Top Stories</h1>

    <?php if ($top_stories_query->have_posts()) : ?>
        <div class="top-stories-list">
            <?php
            $story_index = 0;

            while ($top_stories_query->have_posts()) :
                $top_stories_query->the_post();

                $categories = get_the_category();
                $category_name = !empty($categories) ? $categories[0]->name : __('News', 'outthink-theme');
                $image_url = get_the_post_thumbnail_url(get_the_ID(), 'large');

                if (!$image_url) {
                    $image_url = get_post_meta(get_the_ID(), 'newsapi_image_url', true);
                }

                if (!$image_url) {
                    $image_url = get_post_meta(get_the_ID(), 'fifu_image_url', true);
                }
                ?>
                <a href="<?php the_permalink(); ?>" class="<?php echo esc_attr($story_classes[$story_index] ?? 'top-storie-item'); ?>">
                    <div class="<?php echo esc_attr($indicator_classes[$story_index] ?? 'indicator-note'); ?>">
                        <i class="bi <?php echo esc_attr($indicator_icons[$story_index] ?? 'bi-star'); ?>"></i>
                        <span>#<?php echo esc_html($story_index + 1); ?></span>
                    </div>

                    <div class="post-content">
                        <div class="meta-post">
                            <span><?php echo esc_html($category_name); ?></span>
                            <h1><?php the_title(); ?></h1>
                            <span><?php echo esc_html(wp_trim_words(get_the_excerpt(), 24)); ?></span>
                        </div>

                        <?php if ($image_url) : ?>
                            <div class="image-post">
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" />
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php
                $story_index++;
            endwhile;
            ?>
        </div>
    <?php endif; ?>
</section>

<?php wp_reset_postdata(); ?>
