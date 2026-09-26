(function () {
    var toggle = document.querySelector('.nav-toggle');
    if (!toggle) return;
    toggle.addEventListener('click', function () {
        var open = document.body.classList.toggle('nav-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    });
})();

(function () {
    var lightbox = document.querySelector('.video-lightbox');
    if (!lightbox) return;
    var stage = lightbox.querySelector('.video-lightbox-stage');
    var closeBtn = lightbox.querySelector('.video-lightbox-close');

    function playUrl(url) {
        if (url.indexOf('autoplay=') !== -1) return url;
        return url + (url.indexOf('?') === -1 ? '?' : '&') + 'autoplay=1';
    }

    function closePlayer() {
        if (lightbox.hidden) return;
        lightbox.hidden = true;
        document.body.classList.remove('video-open');
        stage.innerHTML = '';
        if (document.fullscreenElement) {
            document.exitFullscreen().catch(function () {});
        }
    }

    function openPlayer(button) {
        var embed = button.getAttribute('data-embed') || '';
        var file = button.getAttribute('data-file') || '';
        var title = button.getAttribute('data-title') || 'Video';
        stage.innerHTML = '';
        if (embed) {
            var iframe = document.createElement('iframe');
            iframe.src = playUrl(embed);
            iframe.title = title;
            iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen';
            iframe.setAttribute('allowfullscreen', '');
            stage.appendChild(iframe);
        } else if (file) {
            var video = document.createElement('video');
            video.controls = true;
            video.autoplay = true;
            video.playsInline = true;
            video.src = file;
            stage.appendChild(video);
            video.play().catch(function () {});
        } else {
            return;
        }
        lightbox.hidden = false;
        document.body.classList.add('video-open');
        if (lightbox.requestFullscreen) {
            lightbox.requestFullscreen().catch(function () {});
        }
    }

    document.querySelectorAll('.video-poster').forEach(function (button) {
        button.addEventListener('click', function () {
            openPlayer(button);
        });
    });
    closeBtn.addEventListener('click', closePlayer);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closePlayer();
    });
    document.addEventListener('fullscreenchange', function () {
        if (!document.fullscreenElement && !lightbox.hidden) closePlayer();
    });
})();
