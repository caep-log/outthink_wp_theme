<?php
$top_stories_bento_query = new WP_Query([
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

$bento_classes = [
    'bento-story-one',
    'bento-story-two',
    'bento-story-three',
    'bento-story-four',
    'bento-story-five',
];

$bento_labels = [
    __('Gold Note', 'outthink-theme'),
    __('Silver Note', 'outthink-theme'),
    __('Bronze Note', 'outthink-theme'),
    __('Honorable Mention', 'outthink-theme'),
    __('Honorable Mention', 'outthink-theme'),
];
?>

<section class="top-stories-bento-section">
    <div class="section-header">
        <div>
            <small class="section-label"><?php esc_html_e('Ranked Intelligence', 'outthink-theme'); ?></small>
            <h1><?php esc_html_e('Top 5 Notes', 'outthink-theme'); ?></h1>
        </div>
        <div class="section-tags">
            <span><?php esc_html_e('Trending', 'outthink-theme'); ?></span>
            <span><?php esc_html_e('Editorial Picks', 'outthink-theme'); ?></span>
        </div>
    </div>

    <?php if ($top_stories_bento_query->have_posts()) : ?>
        <div class="top-stories-bento-grid">
            <?php
            $bento_index = 0;

            while ($top_stories_bento_query->have_posts()) :
                $top_stories_bento_query->the_post();

                $categories = get_the_category();
                $category_name = !empty($categories) ? $categories[0]->name : __('News', 'outthink-theme');
                $post_tags = get_the_tags();
                $image_url = get_the_post_thumbnail_url(get_the_ID(), 'large');

                if (!$image_url) {
                    $image_url = get_post_meta(get_the_ID(), 'newsapi_image_url', true);
                }

                if (!$image_url) {
                    $image_url = get_post_meta(get_the_ID(), 'fifu_image_url', true);
                }
                ?>
                <a href="<?php the_permalink(); ?>" class="top-stories-bento-card <?php echo esc_attr($bento_classes[$bento_index] ?? 'bento-story-item'); ?>">
                    <?php if ($image_url) : ?>
                        <div class="bento-story-image">
                            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" />
                        </div>
                    <?php endif; ?>

                    <div class="bento-story-content">
                        <small><?php echo esc_html($bento_labels[$bento_index] ?? __('Top Note', 'outthink-theme')); ?></small>
                        <span><?php echo esc_html($category_name); ?></span>
                        <h1><?php the_title(); ?></h1>
                        <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), $bento_index < 3 ? 18 : 10)); ?></p>

                        <?php if (!empty($post_tags)) : ?>
                            <div class="article-tags">
                                <?php foreach (array_slice($post_tags, 0, $bento_index < 3 ? 3 : 2) as $post_tag) : ?>
                                    <small><?php echo esc_html($post_tag->name); ?></small>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php
                $bento_index++;
            endwhile;
            ?>
        </div>
    <?php endif; ?>
</section>

<?php wp_reset_postdata(); ?>
