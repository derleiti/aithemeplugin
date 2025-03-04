(function() {
    // Mobile Navigation und verbesserte Tastaturnavigation
    document.addEventListener('DOMContentLoaded', function() {
        var menuToggle = document.querySelector('.menu-toggle');
        var siteNav = document.querySelector('.site-navigation');

        if (!menuToggle || !siteNav) {
            return;
        }

        // Nur für mobile Geräte das Menü anfangs verstecken
        // Fix: Prüfe Bildschirmbreite vor dem Verstecken
        if (window.innerWidth <= 768) {
            siteNav.classList.add('nav-hidden');
        }

        menuToggle.addEventListener('click', function() {
            menuToggle.classList.toggle('toggled');
            siteNav.classList.toggle('toggled');
            siteNav.classList.toggle('nav-hidden');

            // Toggle aria-expanded Attribut
            var expanded = menuToggle.getAttribute('aria-expanded') === 'true';
            menuToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        });

        // Behandle Bildschirmgrößenänderungen
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                // Entferne nav-hidden Klasse auf Desktop
                siteNav.classList.remove('nav-hidden');
            } else if (!menuToggle.classList.contains('toggled')) {
                // Füge nav-hidden Klasse auf Mobilgeräten hinzu, wenn nicht getoggelt
                siteNav.classList.add('nav-hidden');
            }
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