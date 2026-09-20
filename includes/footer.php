</main>
<footer class="site-footer">
    <div class="footer-inner">
        <div>
            <p class="footer-name"><?= e(setting('photographer_name', setting('site_name', 'Manasi'))) ?></p>
            <p class="footer-copy"><?= e(setting('footer_text', 'All images remain copyright of the photographer.')) ?></p>
        </div>
        <div class="footer-links">
            <?php if (setting('instagram_url')): ?>
                <a href="<?= e(setting('instagram_url')) ?>" target="_blank" rel="noopener">Instagram</a>
            <?php endif; ?>
            <?php if (setting('youtube_url')): ?>
                <a href="<?= e(setting('youtube_url')) ?>" target="_blank" rel="noopener">YouTube</a>
            <?php endif; ?>
            <?php if (setting('contact_email')): ?>
                <a href="mailto:<?= e(setting('contact_email')) ?>">Contact</a>
            <?php endif; ?>
        </div>
    </div>
</footer>
<script src="<?= e(asset('js/main.js')) ?>"></script>
</body>
</html>
