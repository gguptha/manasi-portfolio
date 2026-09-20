(function () {
    var input = document.querySelector('input[name="image"]');
    if (!input) return;
    input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        if (!file) return;
        var aside = document.querySelector('.media-form aside, .preview');
        var img = document.querySelector('.preview');
        if (!img) {
            img = document.createElement('img');
            img.className = 'preview';
            if (aside) aside.prepend(img);
        }
        img.src = URL.createObjectURL(file);
    });
})();
