<?php
get_header();

while (have_posts()) :
    the_post();

    $post_id = get_the_ID();
    $categories = get_the_category($post_id);
    $category_name = !empty($categories) ? $categories[0]->name : __('News', 'outthink-theme');
    $category_ids = wp_list_pluck($categories, 'term_id');
    $post_tags = get_the_tags($post_id);
    $image_url = get_the_post_thumbnail_url($post_id, 'large');
    $original_source = get_post_meta($post_id, 'newsapi_source', true);
    $original_url = get_post_meta($post_id, 'newsapi_url', true);
    $original_published = get_post_meta($post_id, 'newsapi_published', true);
    $original_date = $original_published && strtotime($original_published)
        ? wp_date('F j, Y', strtotime($original_published))
        : get_the_date('F j, Y');
    $share_url = get_permalink($post_id);
    $share_title = get_the_title($post_id);

    if (!$image_url) {
        $image_url = get_post_meta($post_id, 'newsapi_image_url', true);
    }

    if (!$image_url) {
        $image_url = get_post_meta($post_id, 'fifu_image_url', true);
    }

    if (!$image_url) {
        $upload_dir = wp_upload_dir();
        $image_url = trailingslashit($upload_dir['baseurl']) . '2026/08/reading-news.jpg';
    }

    $related_query_args = [
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => 3,
        'post__not_in'        => [$post_id],
        'ignore_sticky_posts' => true,
    ];

    if (!empty($category_ids)) {
        $related_query_args['category__in'] = $category_ids;
    }

    $related_query = new WP_Query($related_query_args);

    $interest_query = new WP_Query([
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => 3,
        'post__not_in'        => [$post_id],
        'ignore_sticky_posts' => true,
        'meta_key'            => 'score',
        'orderby'             => 'meta_value_num',
        'order'               => 'DESC',
        'meta_query'          => [
            [
                'key'     => 'score',
                'compare' => 'EXISTS',
            ],
        ],
    ]);
    ?>

    <main class="single-news-main">
        <article id="post-<?php the_ID(); ?>" <?php post_class('single-news-article'); ?>>
            <header class="single-news-header">
                <div class="single-news-kicker">
                    <span><?php echo esc_html($category_name); ?></span>
                </div>

                <h1><?php the_title(); ?></h1>

                <?php if (has_excerpt()) : ?>
                    <p><?php echo esc_html(get_the_excerpt()); ?></p>
                <?php endif; ?>

                <?php if ($original_source || $original_url) : ?>
                    <p class="single-news-original-source">
                        <?php if ($original_url) : ?>
                            <a href="<?php echo esc_url($original_url); ?>" target="_blank" rel="noopener noreferrer">
                        <?php endif; ?>
                        <?php
                        printf(
                            esc_html__('Originally reported by %1$s · %2$s', 'outthink-theme'),
                            esc_html($original_source ?: __('the original source', 'outthink-theme')),
                            esc_html($original_date)
                        );
                        ?>
                        <?php if ($original_url) : ?></a><?php endif; ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($post_tags)) : ?>
                    <div class="article-tags">
                        <?php foreach (array_slice($post_tags, 0, 5) as $post_tag) : ?>
                            <small><?php echo esc_html($post_tag->name); ?></small>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </header>

            <?php if ($image_url) : ?>
                <figure class="single-news-image">
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" />
                </figure>
            <?php endif; ?>

            <div class="single-news-content">
                <?php
                $content = apply_filters('the_content', get_the_content());
                $continue_label = sprintf(
                    'Continue reading at %s [ Read original article ↗ ]',
                    $original_source ?: __('the original source', 'outthink-theme')
                );
                $content = str_replace(
                    ['Read the full article here', 'Read full article'],
                    esc_html($continue_label),
                    $content
                );
                echo $content;
                ?>
            </div>

            <div class="single-news-share" aria-label="<?php esc_attr_e('Share this article', 'outthink-theme'); ?>">
                <span><?php esc_html_e('Share', 'outthink-theme'); ?></span>
                <a href="<?php echo esc_url(add_query_arg('u', $share_url, 'https://www.facebook.com/sharer/sharer.php')); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Share on Facebook', 'outthink-theme'); ?>">
                    <i class="bi bi-facebook" aria-hidden="true"></i>
                </a>
                <a href="<?php echo esc_url(add_query_arg(['text' => $share_title, 'url' => $share_url], 'https://twitter.com/intent/tweet')); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Share on X', 'outthink-theme'); ?>">
                    <i class="bi bi-twitter-x" aria-hidden="true"></i>
                </a>
                <a href="<?php echo esc_url(add_query_arg(['mini' => 'true', 'url' => $share_url, 'title' => $share_title], 'https://www.linkedin.com/shareArticle')); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Share on LinkedIn', 'outthink-theme'); ?>">
                    <i class="bi bi-linkedin" aria-hidden="true"></i>
                </a>
            </div>
        </article>

        <?php if ($interest_query->have_posts()) : ?>
            <section class="related-news-section">
                <div class="section-header">
                    <div>
                        <small class="section-label"><?php esc_html_e('Editor Signal', 'outthink-theme'); ?></small>
                        <h1><?php esc_html_e('You May Also Like', 'outthink-theme'); ?></h1>
                    </div>
                    <div class="section-tags">
                        <span><?php esc_html_e('Recommended', 'outthink-theme'); ?></span>
                        <span><?php esc_html_e('High Score', 'outthink-theme'); ?></span>
                    </div>
                </div>

                <div class="related-news-grid">
                    <?php
                    while ($interest_query->have_posts()) :
                        $interest_query->the_post();
                        ?>
                        <a href="<?php the_permalink(); ?>" class="related-news-card">
                            <span><?php echo esc_html(get_the_date()); ?></span>
                            <h2><?php the_title(); ?></h2>
                            <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 16)); ?></p>
                        </a>
                        <?php
                    endwhile;
                    ?>
                </div>
            </section>
            <?php wp_reset_postdata(); ?>
        <?php endif; ?>

        <?php if ($related_query->have_posts()) : ?>
            <section class="related-news-section">
                <div class="section-header">
                    <div>
                        <small class="section-label"><?php esc_html_e('Keep Reading', 'outthink-theme'); ?></small>
                        <h1><?php esc_html_e('Related Notes', 'outthink-theme'); ?></h1>
                    </div>
                    <div class="section-tags">
                        <span><?php echo esc_html($category_name); ?></span>
                        <span><?php esc_html_e('Context', 'outthink-theme'); ?></span>
                    </div>
                </div>

                <div class="related-news-grid">
                    <?php
                    while ($related_query->have_posts()) :
                        $related_query->the_post();
                        ?>
                        <a href="<?php the_permalink(); ?>" class="related-news-card">
                            <span><?php echo esc_html(get_the_date()); ?></span>
                            <h2><?php the_title(); ?></h2>
                            <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 16)); ?></p>
                        </a>
                        <?php
                    endwhile;
                    ?>
                </div>
            </section>
            <?php wp_reset_postdata(); ?>
        <?php endif; ?>
    </main>
    <?php
endwhile;

get_footer();
