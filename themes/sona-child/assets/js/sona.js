(function () {
    var toggle = document.querySelector('.sona-nav-toggle');
    var menu = document.getElementById('sona-nav');
    if (!toggle || !menu) return;
    toggle.addEventListener('click', function () {
        var open = menu.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
})();
