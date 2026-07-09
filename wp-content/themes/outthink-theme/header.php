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
                <a href="/"><h3><?php bloginfo('name'); ?></h3></a>
            </div>
            <div class="outthink-header-menu">
                <a href="">Top Stories</a>
                <a href="">Artificial Intelligence</a>
                <a href="">Media Industry</a>
                <a href="">Events</a>
            </div>
            <div class="outthink-header-search">
                <i class="bi bi-search"></i>
            </div>
        </nav>
    </header>
