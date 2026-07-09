<?php
?>
<footer>
    <div class="menu-footer">
        <div class="website-info-footer">
            <a href="/"><h2><?php bloginfo('name'); ?></h2></a>
            <small>A weekly briefing that helps media leaders think ahead of the industry.</small>
            <span>
                Curating the most impactful stories at the intersection of<br>
                Artificial Intelligence and the Media Industry. Designed for<br>
                media leaders who think ahead.
            </span>
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