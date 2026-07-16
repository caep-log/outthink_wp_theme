<?php
$watch_now_query = new WP_Query([
    'post_type'      => 'attachment',
    'post_status'    => 'inherit',
    'post_mime_type' => 'video',
    'posts_per_page' => 4,
    'orderby'        => 'date',
    'order'          => 'DESC',
]);
?>

<section class="watch-now-section">
    <div class="section-header">
        <div>
            <small class="section-label"><?php esc_html_e('Visual Desk', 'outthink-theme'); ?></small>
            <h1><?php esc_html_e('Watch Now', 'outthink-theme'); ?></h1>
        </div>
        <div class="section-tags">
            <span><?php esc_html_e('Video', 'outthink-theme'); ?></span>
            <span><?php esc_html_e('Analysis', 'outthink-theme'); ?></span>
        </div>
    </div>

    <?php if ($watch_now_query->have_posts()) : ?>
        <div class="watch-now-list">
            <?php
            while ($watch_now_query->have_posts()) :
                $watch_now_query->the_post();

                $video_url = wp_get_attachment_url(get_the_ID());

                if (!$video_url) {
                    continue;
                }
                ?>
                <article class="watch-now-item">
                    <video autoplay loop muted playsinline preload="metadata">
                        <source src="<?php echo esc_url($video_url); ?>" type="<?php echo esc_attr(get_post_mime_type(get_the_ID())); ?>">
                    </video>

                    <h2><?php the_title(); ?></h2>
                </article>
                <?php
            endwhile;
            ?>
        </div>
    <?php endif; ?>
</section>

<?php wp_reset_postdata(); ?>
