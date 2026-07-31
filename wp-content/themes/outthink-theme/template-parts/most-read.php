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

<section id="most-read" class="most-read-section">
    <div class="section-header">
        <div>
            <small class="section-label"><?php esc_html_e('Reader Signal', 'outthink-theme'); ?></small>
            <h1><?php esc_html_e('Most Read', 'outthink-theme'); ?></h1>
        </div>
        <div class="section-tags">
            <span><?php esc_html_e('Popular', 'outthink-theme'); ?></span>
            <span><?php esc_html_e('Briefs', 'outthink-theme'); ?></span>
        </div>
    </div>

    <div class="most-read-grid">
        <?php if ($most_read_query->have_posts()) : ?>
            <?php
            $most_read_index = 1;

            while ($most_read_query->have_posts()) :
                $most_read_query->the_post();

                $views = intval(get_post_meta(get_the_ID(), 'outthink_post_views', true));
                $post_tags = get_the_tags();
                ?>
                <a href="<?php the_permalink(); ?>" class="most-read-list">
                    <article class="most-read-item">
                        <span class="most-read-rank"><?php echo esc_html($most_read_index); ?></span>

                        <div class="most-read-content">
                            <h2><?php the_title(); ?></h2>
                            <span>
                                <i class="bi bi-eye"></i>
                                <?php echo esc_html(sprintf(_n('%s read', '%s reads', $views, 'outthink-theme'), number_format_i18n($views))); ?>
                            </span>

                            <?php if (!empty($post_tags)) : ?>
                                <div class="article-tags">
                                    <?php foreach (array_slice($post_tags, 0, 2) as $post_tag) : ?>
                                        <small><?php echo esc_html($post_tag->name); ?></small>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                </a>
                <?php
                $most_read_index++;
            endwhile;
            ?>
        <?php endif; ?>
    </div>
</section>

<?php wp_reset_postdata(); ?>
