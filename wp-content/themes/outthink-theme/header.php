<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <header class="outthink-header">
        <nav>
            <div class="outthink-header-title">
                <a href="/"><h2><?php bloginfo('name'); ?></h2></a>
            </div>
            <div class="outthink-header-menu">
                <a href="#top-stories">Top Stories</a>
                <a href="#category-news">Artificial Intelligence</a>
                <a href="#most-read">Media Industry</a>
                <a href="#events">Events</a>
            </div>
            <div class="outthink-header-search">
                <?php if (current_user_can('manage_options') && function_exists('outthink_news_import_manual_url')) : ?>
                    <a class="outthink-header-fetch" href="<?php echo esc_url(outthink_news_import_manual_url()); ?>">
                        Fetch notes
                    </a>
                <?php endif; ?>
                <i class="bi bi-search"></i>
            </div>
        </nav>
    </header>
