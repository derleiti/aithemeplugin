(function() {
    // Mobile Navigation und verbesserte Tastaturnavigation
    document.addEventListener('DOMContentLoaded', function() {
        var menuToggle = document.querySelector('.menu-toggle');
        var siteNav = document.querySelector('.site-navigation');

        if (!menuToggle || !siteNav) {
            return;
        }

        // Verstecke das Menü zu Beginn
        siteNav.classList.add('nav-hidden');

        menuToggle.addEventListener('click', function() {
            menuToggle.classList.toggle('toggled');
            siteNav.classList.toggle('toggled');
            siteNav.classList.toggle('nav-hidden');

            // Toggle aria-expanded Attribut
            var expanded = menuToggle.getAttribute('aria-expanded') === 'true';
            menuToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        });

        // Tastaturnavigation: Füge für jeden Link sowohl focus- als auch blur-Events hinzu
        var navLinks = document.querySelectorAll('.site-navigation a');

        navLinks.forEach(function(link) {
            link.addEventListener('focus', function() {
                var closestLi = this.closest('li');
                if (closestLi) {
                    closestLi.classList.add('focus');
                }
            });
            link.addEventListener('blur', function() {
                var closestLi = this.closest('li');
                if (closestLi) {
                    closestLi.classList.remove('focus');
                }
            });
        });
    });
})();
