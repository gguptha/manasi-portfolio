</main>
<footer class="site-footer">
    <p class="footer-name"><?= e(setting('photographer_name', setting('site_name', 'Manasi'))) ?></p>
    <?php if (setting('contact_email')): ?>
        <a class="footer-email" href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a>
    <?php endif; ?>
    <div class="footer-links">
        <?php if (setting('instagram_url')): ?>
            <a href="<?= e(setting('instagram_url')) ?>" target="_blank" rel="noopener">Instagram</a>
        <?php endif; ?>
        <?php if (setting('youtube_url')): ?>
            <a href="<?= e(setting('youtube_url')) ?>" target="_blank" rel="noopener">YouTube</a>
        <?php endif; ?>
    </div>
    <p class="footer-copy"><?= e(setting('footer_text', 'All images remain copyright of the photographer.')) ?></p>
</footer>
<script src="<?= e(asset('js/main.js')) ?>"></script>
</body>
</html>
