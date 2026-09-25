(function () {
    var toggle = document.querySelector('.nav-toggle');
    var header = document.querySelector('.site-header');

    if (toggle) {
        toggle.addEventListener('click', function () {
            var open = document.body.classList.toggle('nav-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        });
    }

    if (header && document.body.classList.contains('page-home')) {
        var onScroll = function () {
            header.classList.toggle('is-solid', window.scrollY > 40);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }
})();
