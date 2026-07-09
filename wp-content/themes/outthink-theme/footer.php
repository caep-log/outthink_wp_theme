<?php

?>
<footer>
    <div class="menu-footer">
        <div class="website-info-footer">
            <a href="/"><h2><?php bloginfo('name'); ?></h2></a>
        </div>
        <div class="website-sections-footer">
            <h4>Sections</h4>
            <a href="">Top Stories</a>
            <a href="">AI Strategy & Tech Advice</a>
            <a href="">Media Industry</a>
            <a href="">Media Industry Advice & Weekly Brief</a>
        </div>
        <div class="website-connect-footer">
            <h4>Connect</h4>
            <a href="">Twitter/X</a>
            <a href="">LinkedIn</a>
            <a href="">Newsletter</a>
            <a href="">RSS Feed</a>
        </div>
    </div>
    <div class="footer-copyright">
        <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?> All Rights reserved.</p>
        <div>
            <a href="">Privacy policy</a>
            <a href="">Terms of service</a>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>