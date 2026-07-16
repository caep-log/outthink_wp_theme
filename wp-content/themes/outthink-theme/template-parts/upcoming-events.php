<?php
$upcoming_events_query = new WP_Query([
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => 4,
    'ignore_sticky_posts' => true,
    'category_name'       => 'events',
    'orderby'             => 'date',
    'order'               => 'DESC',
]);
?>

<section class="upcoming-events-section">
    <div class="section-header">
        <div>
            <small class="section-label"><?php esc_html_e('Agenda', 'outthink-theme'); ?></small>
            <h1><?php esc_html_e('Upcoming Events', 'outthink-theme'); ?></h1>
        </div>
        <div class="section-tags">
            <span><?php esc_html_e('Events', 'outthink-theme'); ?></span>
            <span><?php esc_html_e('Industry', 'outthink-theme'); ?></span>
        </div>
    </div>

    <?php if ($upcoming_events_query->have_posts()) : ?>
        <div class="upcoming-events-list">
            <?php
            while ($upcoming_events_query->have_posts()) :
                $upcoming_events_query->the_post();

                $image_url = get_the_post_thumbnail_url(get_the_ID(), 'medium_large');
                $post_tags = get_the_tags();

                if (!$image_url) {
                    $image_url = get_post_meta(get_the_ID(), 'newsapi_image_url', true);
                }

                if (!$image_url) {
                    $image_url = get_post_meta(get_the_ID(), 'fifu_image_url', true);
                }
                ?>
                <a href="<?php the_permalink(); ?>" class="upcoming-event-item">
                    <?php if ($image_url) : ?>
                        <div class="upcoming-event-image">
                            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" />
                        </div>
                    <?php endif; ?>

                    <div class="upcoming-event-content">
                        <span><?php echo esc_html(get_the_date()); ?></span>
                        <h2><?php the_title(); ?></h2>
                        <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20)); ?></p>

                        <?php if (!empty($post_tags)) : ?>
                            <div class="article-tags">
                                <?php foreach (array_slice($post_tags, 0, 2) as $post_tag) : ?>
                                    <small><?php echo esc_html($post_tag->name); ?></small>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php
            endwhile;
            ?>
        </div>
    <?php endif; ?>
</section>

<?php wp_reset_postdata(); ?>
