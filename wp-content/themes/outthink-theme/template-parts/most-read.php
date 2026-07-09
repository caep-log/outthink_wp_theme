<?php
$most_read_query = new WP_Query([
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => 4,
    'ignore_sticky_posts' => true,
    'meta_key'            => 'outthink_post_views',
    'orderby'             => 'meta_value_num',
    'order'               => 'DESC',
    'meta_query'          => [
        [
            'key'     => 'outthink_post_views',
            'compare' => 'EXISTS',
        ],
    ],
]);
?>

<section class="most-read-section">
    <h1><?php esc_html_e('Most Read', 'outthink-theme'); ?></h1>

    <?php if ($most_read_query->have_posts()) : ?>
        <div class="most-read-list">
            <?php
            $most_read_index = 1;

            while ($most_read_query->have_posts()) :
                $most_read_query->the_post();

                $views = intval(get_post_meta(get_the_ID(), 'outthink_post_views', true));
                ?>
                <a href="<?php the_permalink(); ?>" class="most-read-item">
                    <span class="most-read-rank"><?php echo esc_html($most_read_index); ?></span>

                    <div class="most-read-content">
                        <h2><?php the_title(); ?></h2>
                        <span><?php echo esc_html(sprintf(_n('%s read', '%s reads', $views, 'outthink-theme'), number_format_i18n($views))); ?></span>
                    </div>
                </a>
                <?php
                $most_read_index++;
            endwhile;
            ?>
        </div>
    <?php endif; ?>
</section>

<?php wp_reset_postdata(); ?>
