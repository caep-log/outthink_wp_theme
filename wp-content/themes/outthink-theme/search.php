<?php
get_header();

$search_query = get_search_query();
?>

<main class="search-page category-news-grid-section">

    <section class="search-header news-category-header">
        <small class="section-label">
            <?php esc_html_e('Search', 'outthink-theme'); ?>
        </small>

        <h1>
            <?php
            printf(
                esc_html__('Results for "%s"', 'outthink-theme'),
                esc_html($search_query)
            );
            ?>
        </h1>

        <form
            role="search"
            method="get"
            class="outthink-header-search-form-page"
            action="<?php echo esc_url(home_url('/')); ?>"
        >
            <label class="screen-reader-text" for="outthink-search">
                <?php esc_html_e('Search news', 'outthink-theme'); ?>
            </label>

            <input
                id="outthink-search"
                type="search"
                name="s"
                value="<?php echo esc_attr($search_query); ?>"
                placeholder="<?php esc_attr_e('Search news...', 'outthink-theme'); ?>"
            >

            <button type="submit">
                <i class="bi bi-search" aria-hidden="true"></i>
                <?php esc_html_e('Search', 'outthink-theme'); ?>
            </button>
        </form>
    </section>

    <?php if (have_posts()) : ?>

        <section class="search-results category-news-grid">

            <?php while (have_posts()) : the_post(); ?>

                <?php
                $post_id = get_the_ID();
                $source = get_post_meta($post_id, 'newsapi_source', true);

                $image_url = get_the_post_thumbnail_url($post_id, 'medium_large');

                if (!$image_url) {
                    $image_url = get_post_meta(
                        $post_id,
                        'newsapi_image_url',
                        true
                    );
                }

                if (!$image_url) {
                    $image_url = get_post_meta(
                        $post_id,
                        'fifu_image_url',
                        true
                    );
                }
                ?>

                <article <?php post_class('search-result-card category-news-grid__item'); ?>>

                    <?php if ($image_url) : ?>
                        <a
                            href="<?php the_permalink(); ?>"
                            class="search-result-image"
                            aria-hidden="true"
                            tabindex="-1"
                        >
                            <img
                                src="<?php echo esc_url($image_url); ?>"
                                alt=""
                                loading="lazy"
                            >
                        </a>
                    <?php endif; ?>

                    <div class="search-result-content category-news-grid__info">

                        <div class="search-result-meta category-news-grid__meta">

                            <?php if ($source) : ?>
                                <span>
                                    <?php echo esc_html($source); ?>
                                </span>
                            <?php endif; ?>

                            <time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>">
                                <?php echo esc_html(get_the_date()); ?>
                            </time>

                        </div>

                        <h2 class="category-news-grid__title">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_title(); ?>
                            </a>
                        </h2>

                        <p class="category-news-grid__description">
                            <?php
                            echo esc_html(
                                wp_trim_words(
                                    get_the_excerpt(),
                                    25,
                                    '...'
                                )
                            );
                            ?>
                        </p>

                        <a
                            href="<?php the_permalink(); ?>"
                            class="search-result-link"
                        >
                            <?php esc_html_e('Read story', 'outthink-theme'); ?> →
                        </a>

                    </div>

                </article>

            <?php endwhile; ?>

        </section>

        <nav class="search-pagination" aria-label="<?php esc_attr_e('Search results pages', 'outthink-theme'); ?>">
            <?php
            the_posts_pagination([
                'mid_size'  => 2,
                'prev_text' => '← ' . __('Previous', 'outthink-theme'),
                'next_text' => __('Next', 'outthink-theme') . ' →',
            ]);
            ?>
        </nav>

    <?php else : ?>

        <section class="search-empty">

            <i class="bi bi-search" aria-hidden="true"></i>

            <h2>
                <?php esc_html_e('No stories found', 'outthink-theme'); ?>
            </h2>

            <p>
                <?php esc_html_e(
                    'Try searching for another topic, publisher or keyword.',
                    'outthink-theme'
                ); ?>
            </p>

        </section>

    <?php endif; ?>

</main>

<?php
get_footer();
