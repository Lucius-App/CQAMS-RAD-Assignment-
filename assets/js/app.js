(function () {
    'use strict';

    var menuButtons = document.querySelectorAll('.menu-toggle');

    menuButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var isOpen = document.body.classList.toggle('nav-open');
            var menuToggle = document.querySelector('.site-header .menu-toggle');

            if (menuToggle) {
                menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            }
        });
    });
})();
