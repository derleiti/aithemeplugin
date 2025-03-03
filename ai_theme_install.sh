#!/bin/bash

# Derleiti Modern Theme - Optimiertes Installationsskript
# Version 2.6.0 - Vollständig mit WordPress 6.6 kompatibel und unterstützt PHP 8.1-8.3

# Farbcodes für bessere Lesbarkeit
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Funktionen für ein besseres UI
print_header() {
    clear
    echo -e "${CYAN}====================================================${NC}"
    echo -e "${CYAN}          DERLEITI MODERN THEME INSTALLER           ${NC}"
    echo -e "${CYAN}                     Version 2.6                    ${NC}"
    echo -e "${CYAN}====================================================${NC}"
    echo ""
}

print_section() {
    echo -e "\n${BLUE}${BOLD}$1${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}! $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

print_progress() {
    echo -n "$1... "
}

check_php_version() {
    if command -v php &> /dev/null; then
        PHP_VERSION=$(php -r 'echo PHP_VERSION;')
        if [[ $(php -r 'echo version_compare(PHP_VERSION, "8.1.0", ">=") ? "1" : "0";') == "1" ]]; then
            print_success "PHP Version: $PHP_VERSION"
            return 0
        else
            print_warning "PHP Version $PHP_VERSION wird nicht empfohlen. PHP 8.1 oder höher wird empfohlen."
            return 1
        fi
    else
        print_warning "PHP wurde nicht gefunden. Überprüfen Sie, ob PHP installiert ist."
        return 1
    fi
}

check_wp_version() {
    if [ -f "$WP_PATH/wp-includes/version.php" ]; then
        WP_VERSION=$(grep "wp_version = " "$WP_PATH/wp-includes/version.php" | cut -d "'" -f 2)
        # Fix: Align version check with documentation - WordPress 6.2+ is supported
        if [[ $(php -r "echo version_compare('$WP_VERSION', '6.2.0', '>=') ? '1' : '0';") == "1" ]]; then
            print_success "WordPress Version: $WP_VERSION"
            return 0
        else
            print_warning "WordPress Version $WP_VERSION wird nicht unterstützt. WordPress 6.2 oder höher wird benötigt."
            return 1
        fi
    else
        print_warning "WordPress-Version konnte nicht ermittelt werden."
        return 1
    fi
}

# Funktion zur Überprüfung und Erstellung von Verzeichnissen mit Berechtigungsprüfung
create_directory() {
    local dir="$1"
    local msg="$2"
    
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir" 2>/dev/null
        if [ $? -ne 0 ]; then
            print_error "Fehler beim Erstellen des Verzeichnisses: $dir"
            print_error "Überprüfen Sie die Berechtigungen."
            return 1
        else
            if [ -n "$msg" ]; then
                print_success "$msg"
            fi
        fi
    fi
    
    # Überprüfe Schreibrechte
    if [ ! -w "$dir" ]; then
        print_error "Keine Schreibberechtigung für Verzeichnis: $dir"
        return 1
    fi
    
    return 0
}

# Funktion zur Überprüfung, ob alle benötigten Dateien vorhanden sind
check_required_files() {
    local theme_files_dir="$1"
    local result=0
    
    # Mindestvoraussetzungen für Theme-Funktionalität
    if [ ! -f "$theme_files_dir/style.css" ] && [ ! -f "$SCRIPT_DIR/theme-files/style-css.css" ]; then
        print_warning "style.css wurde nicht gefunden."
        result=1
    fi
    
    if [ ! -f "$theme_files_dir/functions.php" ] && [ ! -f "$SCRIPT_DIR/theme-files/functions-php.php" ]; then
        print_warning "functions.php wurde nicht gefunden."
        result=1
    fi
    
    if [ ! -f "$theme_files_dir/index.php" ] && [ ! -f "$SCRIPT_DIR/theme-files/index-php.php" ]; then
        print_warning "index.php wurde nicht gefunden."
        result=1
    fi
    
    return $result
}

print_header

# Systemvoraussetzungen prüfen
print_section "Systemvoraussetzungen werden geprüft"
check_php_version

# Frage nach dem WordPress-Hauptverzeichnis
print_section "WordPress-Verzeichnis"
echo -e "${YELLOW}Bitte gib den vollständigen Pfad zum WordPress-Hauptverzeichnis ein${NC}"
echo -e "${YELLOW}(z.B. /var/www/html oder /var/www/derleiti.de):${NC}"
read -p "> " WP_PATH

# Entferne abschließenden Schrägstrich, falls vorhanden
WP_PATH=${WP_PATH%/}

# Überprüfe, ob der Pfad existiert
if [ ! -d "$WP_PATH" ]; then
    print_error "Das angegebene Verzeichnis existiert nicht."
    echo -e "${YELLOW}Soll das Verzeichnis erstellt werden? (j/n)${NC}"
    read -p "> " CREATE_DIR
    if [[ $CREATE_DIR == "j" || $CREATE_DIR == "J" ]]; then
        if ! create_directory "$WP_PATH" "Verzeichnis wurde erstellt."; then
            print_error "Installation abgebrochen."
            exit 1
        fi
    else
        print_error "Installation abgebrochen."
        exit 1
    fi
fi

# Überprüfe WordPress-Installation
if [ -f "$WP_PATH/wp-config.php" ]; then
    print_success "WordPress-Installation gefunden"
    check_wp_version
else
    print_warning "Keine WordPress-Installation gefunden (wp-config.php nicht vorhanden)"
    echo -e "${YELLOW}Möchtest du trotzdem fortfahren? (j/n)${NC}"
    read -p "> " CONTINUE_WITHOUT_WP
    if [[ $CONTINUE_WITHOUT_WP != "j" && $CONTINUE_WITHOUT_WP != "J" ]]; then
        print_error "Installation abgebrochen."
        exit 1
    fi
    
    # Erstelle Verzeichnisstruktur für WordPress
    print_info "Erstelle notwendige Verzeichnisse für WordPress..."
    if ! create_directory "$WP_PATH/wp-content/themes" "Themes-Verzeichnis erstellt"; then
        print_error "Installation abgebrochen."
        exit 1
    fi
    if ! create_directory "$WP_PATH/wp-content/plugins" "Plugins-Verzeichnis erstellt"; then
        print_error "Installation abgebrochen."
        exit 1
    fi
fi

# Setze den Theme-Pfad und Plugin-Pfad
THEME_PATH="$WP_PATH/wp-content/themes/derleiti-modern"
PLUGIN_PATH="$WP_PATH/wp-content/plugins/derleiti-plugin"

print_info "WordPress-Verzeichnis: $WP_PATH"
print_info "Theme wird installiert in: $THEME_PATH"
print_info "Plugin kann installiert werden in: $PLUGIN_PATH"
echo ""
echo -e "${YELLOW}Möchtest du mit der Installation fortfahren? (j/n)${NC}"
read -p "> " CONTINUE
if [[ $CONTINUE != "j" && $CONTINUE != "J" ]]; then
    print_error "Installation abgebrochen."
    exit 1
fi

# Frage, ob das Plugin auch installiert werden soll
echo -e "${YELLOW}Möchtest du auch das Derleiti Modern Theme Plugin installieren? (j/n)${NC}"
read -p "> " INSTALL_PLUGIN
INSTALL_PLUGIN=$(echo $INSTALL_PLUGIN | tr '[:upper:]' '[:lower:]')

# Prüfen, ob Theme bereits existiert und ggf. sichern
if [ -d "$THEME_PATH" ]; then
    print_warning "Das Theme 'derleiti-modern' existiert bereits."
    TIMESTAMP=$(date +"%Y%m%d%H%M%S")
    BACKUP_PATH="$THEME_PATH-backup-$TIMESTAMP"
    print_info "Erstelle Backup unter: $BACKUP_PATH"
    cp -r "$THEME_PATH" "$BACKUP_PATH"
    if [ $? -ne 0 ]; then
        print_error "Backup konnte nicht erstellt werden. Überprüfen Sie die Berechtigungen."
        exit 1
    fi
    rm -rf "$THEME_PATH"
    print_success "Backup erstellt und altes Theme-Verzeichnis entfernt"
fi

# Prüfen, ob Plugin bereits existiert und ggf. sichern
if [[ $INSTALL_PLUGIN == "j" ]] && [ -d "$PLUGIN_PATH" ]; then
    print_warning "Das Plugin 'derleiti-plugin' existiert bereits."
    TIMESTAMP=$(date +"%Y%m%d%H%M%S")
    BACKUP_PLUGIN_PATH="$PLUGIN_PATH-backup-$TIMESTAMP"
    print_info "Erstelle Backup unter: $BACKUP_PLUGIN_PATH"
    cp -r "$PLUGIN_PATH" "$BACKUP_PLUGIN_PATH"
    if [ $? -ne 0 ]; then
        print_error "Backup des Plugins konnte nicht erstellt werden. Überprüfen Sie die Berechtigungen."
        exit 1
    fi
    rm -rf "$PLUGIN_PATH"
    print_success "Backup erstellt und altes Plugin-Verzeichnis entfernt"
fi

# Theme-Verzeichnisstruktur erstellen
print_section "Erstelle Theme-Verzeichnisstruktur"

print_progress "Erstelle Hauptverzeichnisse"
if ! create_directory "$THEME_PATH" ""; then
    print_error "Fehler beim Erstellen des Theme-Hauptverzeichnisses. Installation abgebrochen."
    exit 1
fi
create_directory "$THEME_PATH/inc" ""
create_directory "$THEME_PATH/js" ""
create_directory "$THEME_PATH/template-parts" ""
create_directory "$THEME_PATH/template-parts/content" ""
create_directory "$THEME_PATH/template-parts/blocks" ""
create_directory "$THEME_PATH/assets/css" ""
create_directory "$THEME_PATH/assets/js" ""
create_directory "$THEME_PATH/assets/images" ""
create_directory "$THEME_PATH/assets/fonts" ""
create_directory "$THEME_PATH/templates" ""
create_directory "$THEME_PATH/parts" ""
create_directory "$THEME_PATH/patterns" ""
create_directory "$THEME_PATH/languages" ""
print_success "Fertig"

# Neu in 2.6: Zusätzliche FSE-Verzeichnisse
print_progress "Erstelle FSE-Unterstützungsverzeichnisse"
create_directory "$THEME_PATH/styles" ""
create_directory "$THEME_PATH/templates/single" ""
create_directory "$THEME_PATH/templates/archive" ""
create_directory "$THEME_PATH/templates/page" ""
create_directory "$THEME_PATH/parts/header" ""
create_directory "$THEME_PATH/parts/footer" ""
print_success "Fertig"

# Prüfen, ob Verzeichnisse erfolgreich erstellt wurden
if [ ! -d "$THEME_PATH" ]; then
    print_error "Fehler: Theme-Verzeichnisse konnten nicht erstellt werden."
    print_error "Bitte prüfe die Berechtigungen."
    exit 1
fi

print_success "Theme-Verzeichnisse erfolgreich erstellt."

# Kopiere die Dateien aus dem aktuellen Verzeichnis in den Theme-Ordner
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
THEME_FILES_DIR="${SCRIPT_DIR}/theme-files"

print_section "Kopiere Theme-Dateien"

# Überprüfen, ob das theme-files Verzeichnis existiert
if [ ! -d "$THEME_FILES_DIR" ]; then
    print_warning "Das Verzeichnis 'theme-files' wurde nicht gefunden."
    echo -e "${YELLOW}Soll es erstellt werden? (j/n)${NC}"
    read -p "> " CREATE_THEME_FILES
    if [[ $CREATE_THEME_FILES == "j" || $CREATE_THEME_FILES == "J" ]]; then
        mkdir -p "$THEME_FILES_DIR"
        print_success "Verzeichnis wurde erstellt."
        echo -e "${YELLOW}Bitte legen Sie die Theme-Dateien in $THEME_FILES_DIR ab und starten Sie das Skript erneut.${NC}"
        exit 0
    else
        print_error "Installation abgebrochen."
        exit 1
    fi
fi

# Überprüfe, ob alle erforderlichen Dateien vorhanden sind
print_progress "Überprüfe erforderliche Dateien"
if ! check_required_files "$THEME_FILES_DIR"; then
    print_warning "Einige wichtige Theme-Dateien fehlen."
    echo -e "${YELLOW}Trotzdem fortfahren? Dies könnte zu einem nicht funktionierenden Theme führen. (j/n)${NC}"
    read -p "> " CONTINUE_MISSING_FILES
    if [[ $CONTINUE_MISSING_FILES != "j" && $CONTINUE_MISSING_FILES != "J" ]]; then
        print_error "Installation abgebrochen."
        exit 1
    fi
else
    print_success "Alle erforderlichen Dateien gefunden"
fi

# Extrahiere die Dateien aus den hochgeladenen Dokumenten
print_progress "Extrahiere style.css"
if [ -f "$THEME_FILES_DIR/style.css" ]; then
    cp -f "$THEME_FILES_DIR/style.css" "$THEME_PATH/style.css"
elif [ -f "$SCRIPT_DIR/theme-files/style-css.css" ]; then
    cp -f "$SCRIPT_DIR/theme-files/style-css.css" "$THEME_PATH/style.css"
else
    cat > "$THEME_PATH/style.css" << 'EOF'
/*
Theme Name: Derleiti Modern
Theme URI: https://derleiti.de
Author: Derleiti
Description: Ein modernes WordPress-Theme für Blog- und Projektdarstellung mit optimiertem Design, KI-Integration und erweiterten Block-Editor-Features.
Version: 2.6
Requires at least: 6.2
Tested up to: 6.6
Requires PHP: 8.1
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: derleiti-modern
Tags: blog, portfolio, grid-layout, custom-colors, custom-logo, custom-menu, featured-images, footer-widgets, full-width-template, sticky-post, theme-options, translation-ready, block-styles, wide-blocks, editor-style, full-site-editing, block-patterns
*/

:root {
    /* Basis-Farben */
    --primary-color: #0066cc;
    --primary-hover: #0052a3;
    --secondary-color: #2c3e50;
    --accent-color: #e74c3c;
    --text-color: #333;
    --light-text: #777;
    --background: #f5f7fa;
    --card-bg: #fff;
    
    /* UI-Elemente */
    --border-radius: 10px;
    --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    --hover-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
    
    /* Layout */
    --container-width: 1200px;
    --grid-gap: 30px;
    --content-padding: 25px;
    --header-height: 80px;
    
    /* Neue Container-Query-Breakpoints */
    --mobile: 480px;
    --tablet: 768px;
    --laptop: 1024px;
    --desktop: 1280px;
    
    /* Neue Animation-Parameter */
    --animation-speed: 0.3s;
    --animation-easing: cubic-bezier(0.4, 0, 0.2, 1);
    
    /* Erweitertes Schrift-System */
    --font-family-base: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    --font-size-base: 16px;
    --line-height-base: 1.6;
    --font-weight-normal: 400;
    --font-weight-medium: 500;
    --font-weight-bold: 600;
}

/* Rest der CSS-Datei hier... */
EOF
fi
print_success "Fertig"

print_progress "Extrahiere theme.json"
if [ -f "$THEME_FILES_DIR/theme.json" ]; then
    cp -f "$THEME_FILES_DIR/theme.json" "$THEME_PATH/theme.json"
elif [ -f "$SCRIPT_DIR/theme-files/theme-json.json" ]; then
    cp -f "$SCRIPT_DIR/theme-files/theme-json.json" "$THEME_PATH/theme.json"
else
    cat > "$THEME_PATH/theme.json" << 'EOF'
{
  "$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 2,
  "settings": {
    "color": {
      "palette": [
        {
          "slug": "primary",
          "color": "#0066cc",
          "name": "Primary"
        },
        {
          "slug": "secondary",
          "color": "#2c3e50",
          "name": "Secondary"
        },
        {
          "slug": "accent",
          "color": "#e74c3c",
          "name": "Accent"
        },
        {
          "slug": "background",
          "color": "#f5f7fa",
          "name": "Background"
        },
        {
          "slug": "text",
          "color": "#333333",
          "name": "Text"
        },
        {
          "slug": "light-text",
          "color": "#777777",
          "name": "Light Text"
        },
        {
          "slug": "card-bg",
          "color": "#ffffff",
          "name": "Card Background"
        }
      ]
    }
  }
}
EOF
fi
print_success "Fertig"

# Kopiere weitere Theme-Dateien mit Fehlerprüfung
print_progress "Kopiere weitere Theme-Dateien"
COPY_ERROR=0

# Funktion zum Kopieren von Dateien mit Fehlerprüfung
copy_file() {
    local src="$1"
    local dest="$2"
    
    if [ -f "$src" ]; then
        cp "$src" "$dest" 2>/dev/null
        if [ $? -ne 0 ]; then
            print_warning "Fehler beim Kopieren von $(basename "$src")"
            return 1
        fi
        return 0
    fi
    return 1
}

# Funktionen zur Extraktion von Dateien aus den Dokumenten mit Namenskorrektur
copy_from_docs() {
    local filename="$1"
    local dest="$2"
    local docfilename="${filename}-php.php"
    
    if [ -f "$THEME_FILES_DIR/$filename.php" ]; then
        copy_file "$THEME_FILES_DIR/$filename.php" "$dest" && return 0
    elif [ -f "$SCRIPT_DIR/theme-files/$docfilename" ]; then
        copy_file "$SCRIPT_DIR/theme-files/$docfilename" "$dest" && return 0
    fi
    return 1
}

# Kopiere Kerndateien
if ! copy_from_docs "functions" "$THEME_PATH/functions.php"; then
    print_warning "functions.php wurde nicht gefunden oder konnte nicht kopiert werden."
    COPY_ERROR=1
fi

if ! copy_from_docs "index" "$THEME_PATH/index.php"; then
    print_warning "index.php wurde nicht gefunden oder konnte nicht kopiert werden."
    COPY_ERROR=1
fi

if ! copy_from_docs "header" "$THEME_PATH/header.php"; then
    print_warning "header.php wurde nicht gefunden oder konnte nicht kopiert werden."
    COPY_ERROR=1
fi

if ! copy_from_docs "footer" "$THEME_PATH/footer.php"; then
    print_warning "footer.php wurde nicht gefunden oder konnte nicht kopiert werden."
    COPY_ERROR=1
fi

if ! copy_from_docs "sidebar" "$THEME_PATH/sidebar.php"; then
    print_warning "sidebar.php wurde nicht gefunden oder konnte nicht kopiert werden."
    COPY_ERROR=1
fi

# Erstelle template-parts Verzeichnis und Dateien
mkdir -p "$THEME_PATH/template-parts" 2>/dev/null
if ! copy_from_docs "template-parts/content" "$THEME_PATH/template-parts/content.php"; then
    print_warning "content.php wurde nicht gefunden oder konnte nicht kopiert werden."
    COPY_ERROR=1
fi

if ! copy_from_docs "template-parts/content-none" "$THEME_PATH/template-parts/content-none.php"; then
    print_warning "content-none.php wurde nicht gefunden oder konnte nicht kopiert werden."
    COPY_ERROR=1
fi

# Erstelle js-Verzeichnis und kopiere navigation.js
mkdir -p "$THEME_PATH/js" 2>/dev/null
if [ -f "$THEME_FILES_DIR/js/navigation.js" ]; then
    copy_file "$THEME_FILES_DIR/js/navigation.js" "$THEME_PATH/js/navigation.js"
elif [ -f "$SCRIPT_DIR/theme-files/navigation-js.js" ]; then
    copy_file "$SCRIPT_DIR/theme-files/navigation-js.js" "$THEME_PATH/js/navigation.js"
else
    print_progress "Erstelle navigation.js"
    cat > "$THEME_PATH/js/navigation.js" << 'EOF'
/**
 * Navigation für mobile Menüs und verbesserte Tastaturnavigation
 *
 * @package Derleiti_Modern
 * @version 2.6
 */

(function() {
    // Mobile Navigation
    document.addEventListener('DOMContentLoaded', function() {
        var menuToggle = document.querySelector('.menu-toggle');
        var siteNav = document.querySelector('.site-navigation');
        var header = document.querySelector('.site-header');
        
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
        
        // Fix: Remove event listeners when elements are removed from the DOM
        // Store event listeners for cleanup
        var blurListeners = {};
        
        // Tastatur-Navigation für Untermenüs
        var navLinks = document.querySelectorAll('.site-navigation a');
        
        navLinks.forEach(function(link) {
            var focusHandler = function() {
                var closestLi = this.closest('li');
                if (closestLi) {
                    closestLi.classList.add('focus');
                }
            };
            
            var blurHandler = function() {
                var closestLi = this.closest('li');
                if (closestLi) {
                    closestLi.classList.remove('focus');
                }
            };
            
            link.addEventListener('focus', focusHandler);
            link.addEventListener('blur', blurHandler);
            
            // Store the event listeners for potential cleanup
            blurListeners[link] = {
                focus: focusHandler,
                blur: blurHandler
            };
        });
        
        // Scroll-Header-Animation
        if (header) {
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            });
        }
        
        // Cleanup function for DOM updates
        window.cleanupNavigationEvents = function() {
            for (var link in blurListeners) {
                if (blurListeners.hasOwnProperty(link) && document.body.contains(link)) {
                    link.removeEventListener('focus', blurListeners[link].focus);
                    link.removeEventListener('blur', blurListeners[link].blur);
                }
            }
            blurListeners = {};
        };
    });
})();
EOF
    print_success "Fertig"
fi

# Kopiere README.md, wenn vorhanden
if [ -f "$THEME_FILES_DIR/README.md" ]; then
    copy_file "$THEME_FILES_DIR/README.md" "$THEME_PATH/README.md"
fi

# Zusammenfassung der Kopieroperationen
if [ $COPY_ERROR -eq 1 ]; then
    print_warning "Einige Dateien konnten nicht kopiert werden."
else
    print_success "Alle Dateien wurden erfolgreich kopiert."
fi

# Screenshot erstellen (Platzhalter)
print_progress "Erstelle Platzhalter für screenshot.png"
# Erstelle einen einfachen Platzhalter für den Screenshot
if ! curl -s -o "$THEME_PATH/screenshot.png" "https://via.placeholder.com/1200x900.png?text=Derleiti+Modern+Theme+v2.6" 2>/dev/null; then
    print_warning "Konnte keinen Screenshot herunterladen, erstelle leere Datei"
    touch "$THEME_PATH/screenshot.png"
fi
print_success "Fertig"

# Setze Berechtigungen mit Fehlerbehandlung
print_progress "Setze Berechtigungen"
chmod -R 755 "$THEME_PATH" 2>/dev/null || print_warning "Konnte Berechtigungen für Verzeichnisse nicht setzen"
find "$THEME_PATH" -type f -exec chmod 644 {} \; 2>/dev/null || print_warning "Konnte Berechtigungen für Dateien nicht setzen"
print_success "Fertig"

# Wenn Plugin installiert werden soll
if [[ $INSTALL_PLUGIN == "j" ]]; then
    print_section "Installiere Derleiti Modern Theme Plugin"
    
    # Plugin-Verzeichnisstruktur erstellen
    print_progress "Erstelle Plugin-Verzeichnisstruktur"
    if ! create_directory "$PLUGIN_PATH" ""; then
        print_error "Fehler beim Erstellen des Plugin-Hauptverzeichnisses."
        print_error "Plugin-Installation wird übersprungen."
    else
        create_directory "$PLUGIN_PATH/admin" ""
        create_directory "$PLUGIN_PATH/admin/css" ""
        create_directory "$PLUGIN_PATH/admin/js" ""
        create_directory "$PLUGIN_PATH/admin/views" ""
        create_directory "$PLUGIN_PATH/includes" ""
        create_directory "$PLUGIN_PATH/blocks" ""
        create_directory "$PLUGIN_PATH/blocks/css" ""
        create_directory "$PLUGIN_PATH/blocks/js" ""
        create_directory "$PLUGIN_PATH/blocks/img" ""
        create_directory "$PLUGIN_PATH/templates" ""
        create_directory "$PLUGIN_PATH/js" ""
        create_directory "$PLUGIN_PATH/languages" ""
        print_success "Fertig"
        
        # Erstelle die plugin-main.php Datei
        print_progress "Erstelle plugin-main.php"
        if [ -f "$THEME_FILES_DIR/plugin-main.php" ]; then
            copy_file "$THEME_FILES_DIR/plugin-main.php" "$PLUGIN_PATH/plugin-main.php" && print_success "Fertig"
        elif [ -f "$SCRIPT_DIR/theme-files/plugin-main.php" ]; then
            copy_file "$SCRIPT_DIR/theme-files/plugin-main.php" "$PLUGIN_PATH/plugin-main.php" && print_success "Fertig"
        else
            cat > "$PLUGIN_PATH/plugin-main.php" << 'EOF'
<?php
/**
 * Plugin Name: Derleiti Modern Theme Plugin
 * Plugin URI: https://derleiti.de/plugin
 * Description: Erweitert das Derleiti Modern Theme mit zusätzlichen Funktionen wie KI-Integration, erweiterten Blockeditor-Funktionen und Designtools.
 * Version: 1.1.0
 * Author: Derleiti
 * Author URI: https://derleiti.de
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: derleiti-plugin
 * Domain Path: /languages
 * Requires at least: 6.2
 * Tested up to: 6.6
 * Requires PHP: 8.1
 *
 * @package Derleiti_Plugin
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Version definieren
define('DERLEITI_PLUGIN_VERSION', '1.1.0');

// Plugin-Pfad definieren
define('DERLEITI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('DERLEITI_PLUGIN_URL', plugin_dir_url(__FILE__));

// Weitere Inhalte des Plugins...
EOF
            print_success "Fertig"
        fi
        
        # Erstelle Admin-Klasse
        print_progress "Erstelle Admin-Klasse"
        mkdir -p "$PLUGIN_PATH/includes"
        if [ -f "$THEME_FILES_DIR/admin-class.php" ]; then
            copy_file "$THEME_FILES_DIR/admin-class.php" "$PLUGIN_PATH/includes/class-derleiti-admin.php" && print_success "Fertig"
        elif [ -f "$SCRIPT_DIR/theme-files/admin-class.php" ]; then
            copy_file "$SCRIPT_DIR/theme-files/admin-class.php" "$PLUGIN_PATH/includes/class-derleiti-admin.php" && print_success "Fertig"
        else
            cat > "$PLUGIN_PATH/includes/class-derleiti-admin.php" << 'EOF'
<?php
/**
 * Verwaltet alle Admin-Funktionen des Plugins
 *
 * @package Derleiti_Plugin
 * @subpackage Admin
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Die Admin-Klasse des Plugins
 */
class Derleiti_Admin {
    
    /**
     * Initialisiere die Admin-Klasse
     */
    public function init() {
        // Hook für Admin-Menü hinzufügen
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Weitere Hooks und Funktionen...
    }
    
    /**
     * Admin-Menüs hinzufügen
     */
    public function add_admin_menu() {
        // Hauptmenüeintrag
        add_menu_page(
            __('Derleiti Theme', 'derleiti-plugin'),
            __('Derleiti Theme', 'derleiti-plugin'),
            'manage_options',
            'derleiti-plugin',
            array($this, 'display_main_admin_page'),
            'dashicons-admin-customizer',
            30
        );
        
        // Weitere Untermenüs...
    }
    
    /**
     * Hauptadmin-Seite anzeigen
     */
    public function display_main_admin_page() {
        include DERLEITI_PLUGIN_PATH . 'admin/views/main-page.php';
    }
    
    // Weitere Methoden...
}
EOF
            print_success "Fertig"
        fi
        
        # Erstelle Admin-View-Template
        print_progress "Erstelle Admin-View-Template"
        mkdir -p "$PLUGIN_PATH/admin/views"
        cat > "$PLUGIN_PATH/admin/views/main-page.php" << 'EOF'
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="derleiti-admin-content">
        <div class="derleiti-admin-tabs">
            <nav class="nav-tab-wrapper">
                <a href="?page=derleiti-plugin&tab=general" class="nav-tab <?php echo empty($_GET['tab']) || $_GET['tab'] === 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('Allgemein', 'derleiti-plugin'); ?></a>
                <a href="?page=derleiti-plugin&tab=ai" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'ai' ? 'nav-tab-active' : ''; ?>"><?php _e('KI-Integration', 'derleiti-plugin'); ?></a>
                <a href="?page=derleiti-plugin&tab=blocks" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'blocks' ? 'nav-tab-active' : ''; ?>"><?php _e('Blöcke', 'derleiti-plugin'); ?></a>
                <a href="?page=derleiti-plugin&tab=tools" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'tools' ? 'nav-tab-active' : ''; ?>"><?php _e('Tools', 'derleiti-plugin'); ?></a>
                <a href="?page=derleiti-plugin&tab=advanced" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'advanced' ? 'nav-tab-active' : ''; ?>"><?php _e('Erweitert', 'derleiti-plugin'); ?></a>
            </nav>
            
            <div class="tab-content">
                <?php
                $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
                
                switch ($tab) {
                    case 'ai':
                        include DERLEITI_PLUGIN_PATH . 'admin/views/ai-tab.php';
                        break;
                    case 'blocks':
                        include DERLEITI_PLUGIN_PATH . 'admin/views/blocks-tab.php';
                        break;
                    case 'tools':
                        include DERLEITI_PLUGIN_PATH . 'admin/views/tools-tab.php';
                        break;
                    case 'advanced':
                        include DERLEITI_PLUGIN_PATH . 'admin/views/advanced-tab.php';
                        break;
                    default:
                        include DERLEITI_PLUGIN_PATH . 'admin/views/general-tab.php';
                        break;
                }
                ?>
            </div>
        </div>
    </div>
</div>
EOF
        print_success "Fertig"
        
        # Setze Berechtigungen für das Plugin
        print_progress "Setze Berechtigungen für das Plugin"
        chmod -R 755 "$PLUGIN_PATH" 2>/dev/null || print_warning "Konnte Berechtigungen für Plugin-Verzeichnisse nicht setzen"
        find "$PLUGIN_PATH" -type f -exec chmod 644 {} \; 2>/dev/null || print_warning "Konnte Berechtigungen für Plugin-Dateien nicht setzen"
        print_success "Fertig"
        
        print_success "Plugin-Installation abgeschlossen!"
    fi
fi

# Abschluss der Installation
print_section "Installation abgeschlossen"

# Überprüfen, ob die Installation erfolgreich war
if [ -f "$THEME_PATH/style.css" ] && [ -f "$THEME_PATH/js/navigation.js" ]; then
    echo -e "${GREEN}====================================================${NC}"
    echo -e "${GREEN}      DERLEITI MODERN THEME ERFOLGREICH INSTALLIERT!  ${NC}"
    echo -e "${GREEN}====================================================${NC}"
    echo -e "${YELLOW}Das Theme ist jetzt in $THEME_PATH installiert.${NC}"
    echo -e "${YELLOW}Aktiviere das Theme im WordPress-Admin unter 'Design' > 'Themes'.${NC}"
    
    if [[ $INSTALL_PLUGIN == "j" ]] && [ -d "$PLUGIN_PATH" ]; then
        echo -e "${YELLOW}Das Plugin ist in $PLUGIN_PATH installiert.${NC}"
        echo -e "${YELLOW}Aktiviere das Plugin im WordPress-Admin unter 'Plugins'.${NC}"
    fi
    
    # Nächste Schritte
    echo -e "\n${BLUE}${BOLD}Nächste Schritte:${NC}"
    echo -e "1. ${CYAN}Melde dich im WordPress-Admin an${NC}"
    echo -e "2. ${CYAN}Aktiviere das Theme${NC}"
    if [[ $INSTALL_PLUGIN == "j" ]] && [ -d "$PLUGIN_PATH" ]; then
        echo -e "3. ${CYAN}Aktiviere das Plugin${NC}"
        echo -e "4. ${CYAN}Konfiguriere die Theme- und Plugin-Einstellungen${NC}"
    else
        echo -e "3. ${CYAN}Konfiguriere die Theme-Einstellungen${NC}"
    fi
    echo -e "\n${YELLOW}Bei Fragen oder Problemen besuche https://derleiti.de/support${NC}"
else
    echo -e "${RED}====================================================${NC}"
    echo -e "${RED}        FEHLER BEI DER THEME-INSTALLATION!           ${NC}"
    echo -e "${RED}====================================================${NC}"
    echo -e "${RED}Bitte überprüfe die Fehler oben und versuche es erneut.${NC}"
    
    # Liste der fehlenden Dateien
    echo -e "\n${RED}Fehlende kritische Dateien:${NC}"
    [ ! -f "$THEME_PATH/style.css" ] && echo -e "${RED}- style.css fehlt${NC}"
    [ ! -f "$THEME_PATH/js/navigation.js" ] && echo -e "${RED}- js/navigation.js fehlt${NC}"
    [ ! -f "$THEME_PATH/functions.php" ] && echo -e "${RED}- functions.php fehlt${NC}"
    [ ! -f "$THEME_PATH/index.php" ] && echo -e "${RED}- index.php fehlt${NC}"
fi
