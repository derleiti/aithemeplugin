/**
 * Navigation für mobile Menüs und verbesserte Tastaturnavigation
 *
 * @package Derleiti_Modern
 * @version 2.2
 */

(function() {
    // Mobile Navigation
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
            
            if (menuToggle.getAttribute('aria-expanded') === 'true') {
                menuToggle.setAttribute('aria-expanded', 'false');
            } else {
                menuToggle.setAttribute('aria-expanded', 'true');
            }
        });
        
        // Tastatur-Navigation für Untermenüs
        var navLinks = document.querySelectorAll('.site-navigation a');
        
        navLinks.forEach(function(link) {
            link.addEventListener('focus', function() {
                var closestLi = this.closest('li');
                if (closestLi) {
                    closestLi.classList.add('focus');
                    
                    // Bei Verlust des Fokus die Klasse entfernen
                    link.addEventListener('blur', function() {
                        closestLi.classList.remove('focus');
                    });
                }
            });
        });
    });
})();
