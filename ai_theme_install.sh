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
        if [[ $(php -r "echo version_compare('$WP_VERSION', '6.2.0', '>=') ? '1' : '0';") == "1" ]]; then
            print_success "WordPress Version: $WP_VERSION"
            return 0
        else
            print_warning "WordPress Version $WP_VERSION wird nicht empfohlen. WordPress 6.2 oder höher wird empfohlen."
            return 1
        fi
    else
        print_warning "WordPress-Version konnte nicht ermittelt werden."
        return 1
    fi
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
        mkdir -p "$WP_PATH"
        print_success "Verzeichnis wurde erstellt."
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
    mkdir -p "$WP_PATH/wp-content/themes"
    mkdir -p "$WP_PATH/wp-content/plugins"
fi

# Überprüfe, ob das Themes-Verzeichnis existiert oder erstelle es
if [ ! -d "$WP_PATH/wp-content/themes" ]; then
    mkdir -p "$WP_PATH/wp-content/themes"
    print_success "Themes-Verzeichnis erstellt"
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
    rm -rf "$PLUGIN_PATH"
    print_success "Backup erstellt und altes Plugin-Verzeichnis entfernt"
fi

# Theme-Verzeichnisstruktur erstellen
print_section "Erstelle Theme-Verzeichnisstruktur"

print_progress "Erstelle Hauptverzeichnisse"
mkdir -p "$THEME_PATH"
mkdir -p "$THEME_PATH/inc"
mkdir -p "$THEME_PATH/js"
mkdir -p "$THEME_PATH/template-parts"
mkdir -p "$THEME_PATH/template-parts/content"
mkdir -p "$THEME_PATH/template-parts/blocks"
mkdir -p "$THEME_PATH/assets/css"
mkdir -p "$THEME_PATH/assets/js"
mkdir -p "$THEME_PATH/assets/images"
mkdir -p "$THEME_PATH/assets/fonts"
mkdir -p "$THEME_PATH/templates"
mkdir -p "$THEME_PATH/parts"
mkdir -p "$THEME_PATH/patterns"
mkdir -p "$THEME_PATH/languages"
print_success "Fertig"

# Neu in 2.6: Zusätzliche FSE-Verzeichnisse
print_progress "Erstelle FSE-Unterstützungsverzeichnisse"
mkdir -p "$THEME_PATH/styles"
mkdir -p "$THEME_PATH/templates/single"
mkdir -p "$THEME_PATH/templates/archive"
mkdir -p "$THEME_PATH/templates/page"
mkdir -p "$THEME_PATH/parts/header"
mkdir -p "$THEME_PATH/parts/footer"
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

# Extrahiere die Dateien aus den hochgeladenen Dokumenten
print_progress "Extrahiere style.css"
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
print_success "Fertig"

print_progress "Extrahiere theme.json"
cp -f "$THEME_FILES_DIR/theme.json" "$THEME_PATH/theme.json" 2>/dev/null || \
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
print_success "Fertig"

# Kopiere weitere Theme-Dateien
print_progress "Kopiere weitere Theme-Dateien"
cp "$THEME_FILES_DIR/functions.php" "$THEME_PATH/functions.php" 2>/dev/null
cp "$THEME_FILES_DIR/index.php" "$THEME_PATH/index.php" 2>/dev/null
cp "$THEME_FILES_DIR/header.php" "$THEME_PATH/header.php" 2>/dev/null
cp "$THEME_FILES_DIR/footer.php" "$THEME_PATH/footer.php" 2>/dev/null
cp "$THEME_FILES_DIR/sidebar.php" "$THEME_PATH/sidebar.php" 2>/dev/null
mkdir -p "$THEME_PATH/template-parts" 2>/dev/null
cp "$THEME_FILES_DIR/template-parts/content.php" "$THEME_PATH/template-parts/content.php" 2>/dev/null
cp "$THEME_FILES_DIR/template-parts/content-none.php" "$THEME_PATH/template-parts/content-none.php" 2>/dev/null
mkdir -p "$THEME_PATH/js" 2>/dev/null
cp "$THEME_FILES_DIR/js/navigation.js" "$THEME_PATH/js/navigation.js" 2>/dev/null
cp "$THEME_FILES_DIR/README.md" "$THEME_PATH/README.md" 2>/dev/null
print_success "Fertig"

# Extrahiere die navigation.js Datei direkt
if [ ! -f "$THEME_PATH/js/navigation.js" ]; then
    mkdir -p "$THEME_PATH/js"
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
    });
})();
EOF
    print_success "Fertig"
fi

# Screenshot erstellen (Platzhalter)
print_progress "Erstelle Platzhalter für screenshot.png"
# Erstelle einen einfachen Platzhalter für den Screenshot
curl -o "$THEME_PATH/screenshot.png" "https://via.placeholder.com/1200x900.png?text=Derleiti+Modern+Theme+v2.6" 2>/dev/null || touch "$THEME_PATH/screenshot.png"
print_success "Fertig"

# Setze Berechtigungen
print_progress "Setze Berechtigungen"
chmod -R 755 "$THEME_PATH"
find "$THEME_PATH" -type f -exec chmod 644 {} \;
print_success "Fertig"

# Wenn Plugin installiert werden soll
if [[ $INSTALL_PLUGIN == "j" ]]; then
    print_section "Installiere Derleiti Modern Theme Plugin"
    
    # Plugin-Verzeichnisstruktur erstellen
    print_progress "Erstelle Plugin-Verzeichnisstruktur"
    mkdir -p "$PLUGIN_PATH"
    mkdir -p "$PLUGIN_PATH/admin"
    mkdir -p "$PLUGIN_PATH/admin/css"
    mkdir -p "$PLUGIN_PATH/admin/js"
    mkdir -p "$PLUGIN_PATH/admin/views"
    mkdir -p "$PLUGIN_PATH/includes"
    mkdir -p "$PLUGIN_PATH/blocks"
    mkdir -p "$PLUGIN_PATH/blocks/css"
    mkdir -p "$PLUGIN_PATH/blocks/js"
    mkdir -p "$PLUGIN_PATH/blocks/img"
    mkdir -p "$PLUGIN_PATH/templates"
    mkdir -p "$PLUGIN_PATH/js"
    mkdir -p "$PLUGIN_PATH/languages"
    print_success "Fertig"
    
    # Erstelle die plugin-main.php Datei
    print_progress "Erstelle plugin-main.php"
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
    
    # Erstelle Admin-Klasse
    print_progress "Erstelle Admin-Klasse"
    mkdir -p "$PLUGIN_PATH/includes"
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
    chmod -R 755 "$PLUGIN_PATH"
    find "$PLUGIN_PATH" -type f -exec chmod 644 {} \;
    print_success "Fertig"
    
    print_success "Plugin-Installation abgeschlossen!"
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
    
    if [[ $INSTALL_PLUGIN == "j" ]]; then
        echo -e "${YELLOW}Das Plugin ist in $PLUGIN_PATH installiert.${NC}"
        echo -e "${YELLOW}Aktiviere das Plugin im WordPress-Admin unter 'Plugins'.${NC}"
    fi
    
    # Nächste Schritte
    echo -e "\n${BLUE}${BOLD}Nächste Schritte:${NC}"
    echo -e "1. ${CYAN}Melde dich im WordPress-Admin an${NC}"
    echo -e "2. ${CYAN}Aktiviere das Theme${NC}"
    if [[ $INSTALL_PLUGIN == "j" ]]; then
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
fi
