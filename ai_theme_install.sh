#!/bin/bash

# Derleiti Modern Theme - Optimiertes Installationsskript
# Version 2.6.1 - Vollständig mit WordPress 6.6 kompatibel und unterstützt PHP 8.1-8.3
# Bugfixes und verbesserte Fehlerbehandlung

# Farbcodes für bessere Lesbarkeit
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Variable für Debug-Modus
DEBUG_MODE=0

# Funktionen für ein besseres UI
print_header() {
    clear
    echo -e "${CYAN}====================================================${NC}"
    echo -e "${CYAN}          DERLEITI MODERN THEME INSTALLER           ${NC}"
    echo -e "${CYAN}                   Version 2.6.1                    ${NC}"
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

print_debug() {
    if [ $DEBUG_MODE -eq 1 ]; then
        echo -e "${MAGENTA}DEBUG: $1${NC}"
    fi
}

# Befehl ausführen und Fehler protokollieren
# $1: Beschreibung
# $2: Befehl
execute_command() {
    print_debug "Ausführen: $2"
    output=$(eval "$2" 2>&1)
    status=$?
    
    if [ $status -ne 0 ]; then
        print_error "$1 fehlgeschlagen: $output"
        return 1
    fi
    
    print_debug "Befehl erfolgreich ausgeführt"
    return 0
}

# Prüfe, ob ein Befehl verfügbar ist
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

check_php_version() {
    if command_exists php; then
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
    
    print_debug "Erstelle Verzeichnis: $dir"
    
    # Überprüfe, ob Verzeichnis bereits existiert
    if [ -d "$dir" ]; then
        print_debug "Verzeichnis existiert bereits: $dir"
        
        # Überprüfe Schreibrechte
        if [ ! -w "$dir" ]; then
            print_error "Keine Schreibberechtigung für existierendes Verzeichnis: $dir"
            return 1
        fi
        
        return 0
    fi
    
    # Erstelle übergeordnete Verzeichnisse, falls nötig
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
    
    # Überprüfe Schreibrechte nach der Erstellung
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

# Prüfe Abhängigkeiten und installiere, falls nötig und möglich
check_dependencies() {
    print_section "Prüfe Abhängigkeiten"
    
    # Prüfe, ob unzip installiert ist (für Zip-Verarbeitung)
    if ! command_exists unzip; then
        print_warning "unzip ist nicht installiert. Es wird empfohlen, es zu installieren."
        print_info "Auf Ubuntu/Debian: sudo apt-get install unzip"
        print_info "Auf CentOS/RHEL: sudo yum install unzip"
        print_info "Auf macOS: brew install unzip"
    else
        print_success "unzip ist installiert"
    fi
    
    # Prüfe, ob curl oder wget installiert ist
    if ! command_exists curl && ! command_exists wget; then
        print_warning "Weder curl noch wget ist installiert. Eine dieser Anwendungen wird für Downloads benötigt."
        print_info "Auf Ubuntu/Debian: sudo apt-get install curl"
        print_info "Auf CentOS/RHEL: sudo yum install curl"
        print_info "Auf macOS: brew install curl"
    elif command_exists curl; then
        print_success "curl ist installiert"
    elif command_exists wget; then
        print_success "wget ist installiert"
    fi
    
    # Prüfe PHP-Version
    check_php_version
    
    return 0
}

# Download-Funktion, die curl oder wget verwendet, je nachdem was verfügbar ist
download_file() {
    local url="$1"
    local destination="$2"
    
    print_debug "Downloading $url to $destination"
    
    if command_exists curl; then
        curl -s -L "$url" -o "$destination"
        return $?
    elif command_exists wget; then
        wget -q "$url" -O "$destination"
        return $?
    else
        print_error "Weder curl noch wget ist verfügbar. Kann Datei nicht herunterladen."
        return 1
    fi
}

# Funktion zum sicheren Kopieren einer Datei mit Fehlerbehandlung
safe_copy() {
    local src="$1"
    local dest="$2"
    local description="$3"
    
    print_debug "Kopiere von $src nach $dest"
    
    # Prüfe, ob Quelldatei existiert
    if [ ! -f "$src" ]; then
        print_warning "$description: Quelldatei nicht gefunden: $src"
        return 1
    fi
    
    # Prüfe, ob Zielverzeichnis existiert und erstelle es, falls nicht
    local dest_dir=$(dirname "$dest")
    if [ ! -d "$dest_dir" ]; then
        mkdir -p "$dest_dir" 2>/dev/null
        if [ $? -ne 0 ]; then
            print_error "$description: Konnte Zielverzeichnis nicht erstellen: $dest_dir"
            return 1
        fi
    fi
    
    # Kopiere Datei
    cp -f "$src" "$dest" 2>/dev/null
    if [ $? -ne 0 ]; then
        print_error "$description: Fehler beim Kopieren"
        return 1
    fi
    
    print_debug "$description erfolgreich kopiert"
    return 0
}

print_header

# Debug-Modus aktivieren, wenn das Argument übergeben wurde
if [ "$1" == "--debug" ]; then
    DEBUG_MODE=1
    print_info "Debug-Modus aktiviert"
fi

# Prüfe Abhängigkeiten
check_dependencies

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
    
    execute_command "Theme-Backup erstellen" "cp -r \"$THEME_PATH\" \"$BACKUP_PATH\""
    if [ $? -ne 0 ]; then
        print_error "Backup konnte nicht erstellt werden. Überprüfen Sie die Berechtigungen."
        exit 1
    fi
    
    execute_command "Altes Theme-Verzeichnis entfernen" "rm -rf \"$THEME_PATH\""
    if [ $? -ne 0 ]; then
        print_error "Altes Theme-Verzeichnis konnte nicht entfernt werden."
        exit 1
    fi
    
    print_success "Backup erstellt und altes Theme-Verzeichnis entfernt"
fi

# Prüfen, ob Plugin bereits existiert und ggf. sichern
if [[ $INSTALL_PLUGIN == "j" ]] && [ -d "$PLUGIN_PATH" ]; then
    print_warning "Das Plugin 'derleiti-plugin' existiert bereits."
    TIMESTAMP=$(date +"%Y%m%d%H%M%S")
    BACKUP_PLUGIN_PATH="$PLUGIN_PATH-backup-$TIMESTAMP"
    print_info "Erstelle Backup unter: $BACKUP_PLUGIN_PATH"
    
    execute_command "Plugin-Backup erstellen" "cp -r \"$PLUGIN_PATH\" \"$BACKUP_PLUGIN_PATH\""
    if [ $? -ne 0 ]; then
        print_error "Backup des Plugins konnte nicht erstellt werden. Überprüfen Sie die Berechtigungen."
        exit 1
    fi
    
    execute_command "Altes Plugin-Verzeichnis entfernen" "rm -rf \"$PLUGIN_PATH\""
    if [ $? -ne 0 ]; then
        print_error "Altes Plugin-Verzeichnis konnte nicht entfernt werden."
        exit 1
    fi
    
    print_success "Backup erstellt und altes Plugin-Verzeichnis entfernt"
fi

# Theme-Verzeichnisstruktur erstellen
print_section "Erstelle Theme-Verzeichnisstruktur"

print_progress "Erstelle Hauptverzeichnisse"
if ! create_directory "$THEME_PATH" ""; then
    print_error "Fehler beim Erstellen des Theme-Hauptverzeichnisses. Installation abgebrochen."
    exit 1
fi

# Erstelle alle erforderlichen Verzeichnisse in einem Durchlauf
THEME_DIRECTORIES=(
    "$THEME_PATH/inc"
    "$THEME_PATH/js"
    "$THEME_PATH/template-parts"
    "$THEME_PATH/template-parts/content"
    "$THEME_PATH/template-parts/blocks"
    "$THEME_PATH/assets/css"
    "$THEME_PATH/assets/js"
    "$THEME_PATH/assets/images"
    "$THEME_PATH/assets/fonts"
    "$THEME_PATH/templates"
    "$THEME_PATH/parts"
    "$THEME_PATH/patterns"
    "$THEME_PATH/languages"
    "$THEME_PATH/styles"
    "$THEME_PATH/templates/single"
    "$THEME_PATH/templates/archive"
    "$THEME_PATH/templates/page"
    "$THEME_PATH/parts/header"
    "$THEME_PATH/parts/footer"
)

for dir in "${THEME_DIRECTORIES[@]}"; do
    if ! create_directory "$dir" ""; then
        print_warning "Konnte Verzeichnis nicht erstellen: $dir"
    fi
done

print_success "Theme-Verzeichnisse erfolgreich erstellt"

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
        execute_command "Erstelle theme-files Verzeichnis" "mkdir -p \"$THEME_FILES_DIR\""
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

# Kopiere Core-Dateien
core_files=(
    "style.css:style-css.css"
    "theme.json:theme-json.json"
    "functions.php:functions-php.php"
    "index.php:index-php.php"
    "header.php:header-php.php"
    "footer.php:footer-php.php"
    "sidebar.php:sidebar-php.php"
)

print_progress "Kopiere Core-Dateien"
COPY_ERROR=0

for file_pair in "${core_files[@]}"; do
    target_file="${file_pair%%:*}"
    source_file="${file_pair##*:}"
    
    # Versuche zuerst die Datei aus dem theme-files Verzeichnis zu kopieren
    if [ -f "$THEME_FILES_DIR/$target_file" ]; then
        if ! safe_copy "$THEME_FILES_DIR/$target_file" "$THEME_PATH/$target_file" "$target_file"; then
            COPY_ERROR=1
        fi
    # Versuche alternativ die umbenannte Datei zu kopieren
    elif [ -f "$THEME_FILES_DIR/$source_file" ]; then
        if ! safe_copy "$THEME_FILES_DIR/$source_file" "$THEME_PATH/$target_file" "$target_file"; then
            COPY_ERROR=1
        fi
    # Für style.css, erstelle eine Standard-Datei wenn keine existiert
    elif [ "$target_file" == "style.css" ]; then
        cat > "$THEME_PATH/style.css" << 'EOF'
/*
Theme Name: Derleiti Modern
Theme URI: https://derleiti.de
Author: Derleiti
Description: Ein modernes WordPress-Theme für Blog- und Projektdarstellung mit optimiertem Design, KI-Integration und erweiterten Block-Editor-Features.
Version: 2.6.1
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
    # Für theme.json, erstelle eine Standard-Datei wenn keine existiert
    elif [ "$target_file" == "theme.json" ]; then
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
    else
        print_warning "$target_file wurde nicht gefunden oder konnte nicht kopiert werden."
        COPY_ERROR=1
    fi
done

# Kopiere template-parts Dateien
print_progress "Kopiere template-parts Dateien"
template_parts=(
    "content.php:content-php.php:template-parts/content.php"
    "content-none.php:content-none-php.php:template-parts/content-none.php"
)

for file_info in "${template_parts[@]}"; do
    target_file="${file_info%%:*}"
    source_file="${file_info#*:}"
    source_file="${source_file%%:*}"
    dest_path="${file_info##*:}"
    
    # Versuche mit verschiedenen Optionen
    if [ -f "$THEME_FILES_DIR/$target_file" ]; then
        if ! safe_copy "$THEME_FILES_DIR/$target_file" "$THEME_PATH/$dest_path" "$dest_path"; then
            COPY_ERROR=1
        fi
    elif [ -f "$THEME_FILES_DIR/$source_file" ]; then
        if ! safe_copy "$THEME_FILES_DIR/$source_file" "$THEME_PATH/$dest_path" "$dest_path"; then
            COPY_ERROR=1
        fi
    else
        print_warning "$dest_path wurde nicht gefunden oder konnte nicht kopiert werden."
        COPY_ERROR=1
    fi
done

# Kopiere JS-Dateien
print_progress "Kopiere JS-Dateien"
if [ -f "$THEME_FILES_DIR/js/navigation.js" ]; then
    if ! safe_copy "$THEME_FILES_DIR/js/navigation.js" "$THEME_PATH/js/navigation.js" "navigation.js"; then
        COPY_ERROR=1
    fi
elif [ -f "$THEME_FILES_DIR/navigation-js.js" ]; then
    if ! safe_copy "$THEME_FILES_DIR/navigation-js.js" "$THEME_PATH/js/navigation.js" "navigation.js"; then
        COPY_ERROR=1
    fi
else
    # Standarddatei erstellen
    print_progress "Erstelle navigation.js"
    cat > "$THEME_PATH/js/navigation.js" << 'EOF'
/**
 * Navigation für mobile Menüs und verbesserte Tastaturnavigation
 *
 * @package Derleiti_Modern
 * @version 2.6.1
 */

(function() {
    'use strict';
    
    // Mobile Navigation
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.querySelector('.menu-toggle');
        const siteNav = document.querySelector('.site-navigation');
        const header = document.querySelector('.site-header');
        
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
        const blurListeners = new Map();
        
        // Tastatur-Navigation für Untermenüs
        const navLinks = document.querySelectorAll('.site-navigation a');
        
        navLinks.forEach(function(link) {
            const focusHandler = function() {
                const closestLi = this.closest('li');
                if (closestLi) {
                    closestLi.classList.add('focus');
                }
            };
            
            const blurHandler = function() {
                const closestLi = this.closest('li');
                if (closestLi) {
                    closestLi.classList.remove('focus');
                }
            };
            
            link.addEventListener('focus', focusHandler);
            link.addEventListener('blur', blurHandler);
            
            // Store the event listeners for potential cleanup
            blurListeners.set(link, {
                focus: focusHandler,
                blur: blurHandler
            });
        });
        
        // Scroll-Header-Animation
        if (header) {
            let lastScrollY = window.scrollY;
            let ticking = false;
            
            window.addEventListener('scroll', function() {
                lastScrollY = window.scrollY;
                
                if (!ticking) {
                    window.requestAnimationFrame(function() {
                        if (lastScrollY > 50) {
                            header.classList.add('scrolled');
                        } else {
                            header.classList.remove('scrolled');
                        }
                        ticking = false;
                    });
                    
                    ticking = true;
                }
            });
        }
        
        // Cleanup function for DOM updates
        window.cleanupNavigationEvents = function() {
            for (const [link, handlers] of blurListeners.entries()) {
                if (document.body.contains(link)) {
                    link.removeEventListener('focus', handlers.focus);
                    link.removeEventListener('blur', handlers.blur);
                }
            }
            blurListeners.clear();
        };
    });
})();
EOF
fi

# Kopiere README.md, wenn vorhanden
if [ -f "$THEME_FILES_DIR/README.md" ]; then
    safe_copy "$THEME_FILES_DIR/README.md" "$THEME_PATH/README.md" "README.md"
fi

# Zusammenfassung der Kopieroperationen
if [ $COPY_ERROR -eq 1 ]; then
    print_warning "Einige Dateien konnten nicht kopiert werden."
else
    print_success "Alle Dateien wurden erfolgreich kopiert."
fi

# Screenshot erstellen (Platzhalter)
print_progress "Erstelle Platzhalter für screenshot.png"
if command_exists curl; then
    if ! curl -s -o "$THEME_PATH/screenshot.png" "https://via.placeholder.com/1200x900.png?text=Derleiti+Modern+Theme+v2.6.1" 2>/dev/null; then
        print_warning "Konnte keinen Screenshot herunterladen, erstelle leere Datei"
        touch "$THEME_PATH/screenshot.png"
    fi
elif command_exists wget; then
    if ! wget -q -O "$THEME_PATH/screenshot.png" "https://via.placeholder.com/1200x900.png?text=Derleiti+Modern+Theme+v2.6.1" 2>/dev/null; then
        print_warning "Konnte keinen Screenshot herunterladen, erstelle leere Datei"
        touch "$THEME_PATH/screenshot.png"
    fi
else
    print_warning "Konnte keinen Screenshot herunterladen (curl/wget fehlt), erstelle leere Datei"
    touch "$THEME_PATH/screenshot.png"
fi
print_success "Fertig"

# Setze Berechtigungen mit Fehlerbehandlung
print_progress "Setze Berechtigungen"
execute_command "Berechtigungen für Verzeichnisse setzen" "chmod -R 755 \"$THEME_PATH\"" || print_warning "Konnte Berechtigungen für Verzeichnisse nicht setzen"
execute_command "Berechtigungen für Dateien setzen" "find \"$THEME_PATH\" -type f -exec chmod 644 {} \\;" || print_warning "Konnte Berechtigungen für Dateien nicht setzen"
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
        # Erstelle alle erforderlichen Plugin-Verzeichnisse in einem Durchlauf
        PLUGIN_DIRECTORIES=(
            "$PLUGIN_PATH/admin"
            "$PLUGIN_PATH/admin/css"
            "$PLUGIN_PATH/admin/js"
            "$PLUGIN_PATH/admin/views"
            "$PLUGIN_PATH/includes"
            "$PLUGIN_PATH/blocks"
            "$PLUGIN_PATH/blocks/css"
            "$PLUGIN_PATH/blocks/js"
            "$PLUGIN_PATH/blocks/img"
            "$PLUGIN_PATH/templates"
            "$PLUGIN_PATH/js"
            "$PLUGIN_PATH/languages"
        )

        for dir in "${PLUGIN_DIRECTORIES[@]}"; do
            if ! create_directory "$dir" ""; then
                print_warning "Konnte Verzeichnis nicht erstellen: $dir"
            fi
        fi
        
        print_success "Plugin-Verzeichnisstruktur erstellt"
        
        # Kopiere plugin-main.php
        print_progress "Erstelle plugin-main.php"
        if [ -f "$THEME_FILES_DIR/plugin-main.php" ]; then
            if ! safe_copy "$THEME_FILES_DIR/plugin-main.php" "$PLUGIN_PATH/plugin-main.php" "plugin-main.php"; then
                print_warning "Konnte plugin-main.php nicht kopieren"
            else
                print_success "Plugin-Hauptdatei kopiert"
            fi
        else
            # Erstelle Standard plugin-main.php
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

// Debug-Modus (nur für Entwicklung)
define('DERLEITI_DEBUG', false);

/**
 * Initialisierung des Plugins
 */
function derleiti_plugin_init(): void {
    // Lade Textdomain für Übersetzungen
    load_plugin_textdomain('derleiti-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Lade Komponenten
    $includes_dir = DERLEITI_PLUGIN_PATH . 'includes/';
    
    // Hauptkomponenten
    $core_files = [
        'class-derleiti-admin.php',
        'class-derleiti-blocks.php',
        'class-derleiti-ai-integration.php',
        'class-derleiti-tools.php'
    ];
    
    // Optionale neue Komponenten
    $optional_files = [
        'class-derleiti-performance.php',
        'class-derleiti-analytics.php',
        'class-derleiti-compatibility.php'
    ];
    
    // Lade Hauptkomponenten
    foreach ($core_files as $file) {
        $filepath = $includes_dir . $file;
        if (file_exists($filepath)) {
            require_once $filepath;
        }
    }
    
    // Lade optionale Komponenten
    foreach ($optional_files as $file) {
        $filepath = $includes_dir . $file;
        if (file_exists($filepath)) {
            require_once $filepath;
        }
    }

    // Initialisiere Klassen wenn vorhanden
    if (class_exists('Derleiti_Admin')) {
        $admin = new Derleiti_Admin();
        $admin->init();
    }

    if (class_exists('Derleiti_Blocks')) {
        $blocks = new Derleiti_Blocks();
        $blocks->init();
    }

    if (class_exists('Derleiti_AI_Integration')) {
        $ai = new Derleiti_AI_Integration();
        $ai->init();
    }

    if (class_exists('Derleiti_Tools')) {
        $tools = new Derleiti_Tools();
        $tools->init();
    }

    if (class_exists('Derleiti_Performance')) {
        $performance = new Derleiti_Performance();
        $performance->init();
    }

    if (class_exists('Derleiti_Analytics')) {
        $analytics = new Derleiti_Analytics();
        $analytics->init();
    }

    if (class_exists('Derleiti_Compatibility')) {
        $compatibility = new Derleiti_Compatibility();
        $compatibility->init();
    }

    // Hooks für Entwicklermodus
    if (DERLEITI_DEBUG) {
        add_action('admin_footer', 'derleiti_debug_info');
        add_action('wp_footer', 'derleiti_debug_info');
    }
}
add_action('plugins_loaded', 'derleiti_plugin_init');

/**
 * Plugin-Aktivierung
 */
function derleiti_plugin_activate(): void {
    // Überprüfe WordPress-Version
    if (version_compare(get_bloginfo('version'), '6.2', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Dieses Plugin erfordert WordPress 6.2 oder höher.', 'derleiti-plugin'));
    }

    // Überprüfe PHP-Version
    if (version_compare(PHP_VERSION, '8.1', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Dieses Plugin erfordert PHP 8.1 oder höher.', 'derleiti-plugin'));
    }

    // Erstelle notwendige DB-Tabellen
    global $wpdb;
    $table_name = $wpdb->prefix . 'derleiti_settings';

    // Überprüfe, ob die Tabelle bereits existiert
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

    if (!$table_exists) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_name varchar(255) NOT NULL,
            setting_value longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY setting_name (setting_name)
            ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    // Standardeinstellungen speichern
    $default_settings = array(
        'ai_enabled' => 1,
        'dark_mode' => 'auto',
        'layout_builder' => 1,
        'performance_optimization' => 1,
        'seo_features' => 1,
        'analytics_integration' => 0,
        'ai_provider' => 'openai',
        'ai_models' => array(
            'text' => 'gpt-4',
            'image' => 'dall-e-3'
        ),
        'version' => DERLEITI_PLUGIN_VERSION
    );

    foreach ($default_settings as $name => $value) {
        $existing_value = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM $table_name WHERE setting_name = %s",
            $name
        ));

        // Nur einfügen, wenn der Wert noch nicht existiert
        if ($existing_value === null) {
            $wpdb->replace(
                $table_name,
                array(
                    'setting_name' => $name,
                    'setting_value' => is_array($value) ? serialize($value) : $value,
                      'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s')
            );
        }
    }

    // Erstelle Verzeichnisse für Cache
    $cache_dir = WP_CONTENT_DIR . '/cache/derleiti-plugin';
    if (!file_exists($cache_dir)) {
        wp_mkdir_p($cache_dir);
    }

    // Setze Capability für Administratoren
    $role = get_role('administrator');
    if ($role) {
        $role->add_cap('manage_derleiti_plugin');
    }

    // Setze Aktivierungs-Flag für Willkommensnachricht
    set_transient('derleiti_plugin_activated', true, 5);

    // Aktualisiere Permalinks
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'derleiti_plugin_activate');

/**
 * Plugin-Deaktivierung
 */
function derleiti_plugin_deactivate(): void {
    // Lösche temporäre Daten
    $cache_dir = WP_CONTENT_DIR . '/cache/derleiti-plugin';

    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '/*');
        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }

    // Transients leeren
    delete_transient('derleiti_plugin_cache');

    // Alle Plugin-spezifischen Transients löschen
    global $wpdb;
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_derleiti_%'");
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_derleiti_%'");

    // Entferne Capability
    $role = get_role('administrator');
    if ($role) {
        $role->remove_cap('manage_derleiti_plugin');
    }

    // Aktualisiere Permalinks
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'derleiti_plugin_deactivate');

/**
 * Plugin-Deinstallation
 */
function derleiti_plugin_uninstall(): void {
    // Tabellen entfernen, wenn angefordert
    $remove_data = get_option('derleiti_remove_data_on_uninstall', false);

    if ($remove_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';

        // Tabelle löschen
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

        // Optionen löschen
        delete_option('derleiti_remove_data_on_uninstall');

        // Weitere Plugin-spezifische Optionen löschen
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'derleiti_%'");
    }
}
register_uninstall_hook(__FILE__, 'derleiti_plugin_uninstall');

// Shortcodes und API-Endpunkte

/**
 * Shortcode für Plugin-Features
 */
function derleiti_features_shortcode($atts): string {
    $atts = shortcode_atts(array(
        'feature' => 'layout',
        'id' => '',
        'class' => '',
        'options' => '',
    ), $atts, 'derleiti_feature');

    $options = [];
    if (!empty($atts['options'])) {
        $option_pairs = explode(',', $atts['options']);
        foreach ($option_pairs as $pair) {
            $option = explode(':', $pair);
            if (count($option) === 2) {
                $key = trim($option[0]);
                $value = trim($option[1]);
                $options[$key] = $value;
            }
        }
    }

    ob_start();

    $id_attr = !empty($atts['id']) ? ' id="' . esc_attr($atts['id']) . '"' : '';
    $class_attr = !empty($atts['class']) ? ' class="' . esc_attr($atts['class']) . '"' : '';
    $feature = sanitize_key($atts['feature']);

    echo '<div' . $id_attr . $class_attr . ' data-derleiti-feature="' . esc_attr($feature) . '">';

    switch ($feature) {
        case 'layout':
            if (file_exists(DERLEITI_PLUGIN_PATH . 'templates/layout-builder.php')) {
                include DERLEITI_PLUGIN_PATH . 'templates/layout-builder.php';
            } else {
                esc_html_e('Layout-Builder-Template nicht gefunden', 'derleiti-plugin');
            }
            break;
            
        case 'ai':
            if (file_exists(DERLEITI_PLUGIN_PATH . 'templates/ai-content.php')) {
                include DERLEITI_PLUGIN_PATH . 'templates/ai-content.php';
            } else {
                esc_html_e('KI-Content-Template nicht gefunden', 'derleiti-plugin');
            }
            break;
            
        case 'gallery':
            if (file_exists(DERLEITI_PLUGIN_PATH . 'templates/enhanced-gallery.php')) {
                include DERLEITI_PLUGIN_PATH . 'templates/enhanced-gallery.php';
            } else {
                esc_html_e('Gallery-Template nicht gefunden', 'derleiti-plugin');
            }
            break;
            
        case 'analytics':
            if (file_exists(DERLEITI_PLUGIN_PATH . 'templates/analytics.php')) {
                include DERLEITI_PLUGIN_PATH . 'templates/analytics.php';
            } else {
                esc_html_e('Analytics-Template nicht gefunden', 'derleiti-plugin');
            }
            break;
            
        default:
            esc_html_e('Feature nicht gefunden', 'derleiti-plugin');
    }

    echo '</div>';

    return ob_get_clean();
}
add_shortcode('derleiti_feature', 'derleiti_features_shortcode');

/**
 * Füge REST API Endpunkte hinzu
 */
function derleiti_plugin_register_rest_routes(): void {
    register_rest_route('derleiti-plugin/v1', '/settings', array(
        'methods' => 'GET',
        'callback' => 'derleiti_plugin_get_settings',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));

    register_rest_route('derleiti-plugin/v1', '/settings', array(
        'methods' => 'POST',
        'callback' => 'derleiti_plugin_update_settings',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
        'args' => array(
            'nonce' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return wp_verify_nonce($param, 'derleiti-rest-nonce');
                },
            ),
        ),
    ));

    // Neuer Endpunkt für System-Informationen
    register_rest_route('derleiti-plugin/v1', '/system-info', array(
        'methods' => 'GET',
        'callback' => 'derleiti_plugin_get_system_info',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));

    // Neuer Endpunkt für Cache-Löschen
    register_rest_route('derleiti-plugin/v1', '/clear-cache', array(
        'methods' => 'POST',
        'callback' => 'derleiti_plugin_clear_cache_endpoint',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
        'args' => array(
            'nonce' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return wp_verify_nonce($param, 'derleiti-rest-nonce');
                },
            ),
        ),
    ));
}
add_action('rest_api_init', 'derleiti_plugin_register_rest_routes');
EOF
            print_success "Plugin-Hauptdatei erstellt"
        fi
        
        # Erstelle Admin-Klasse
        print_progress "Erstelle Admin-Klasse"
        mkdir -p "$PLUGIN_PATH/includes"
        if [ -f "$THEME_FILES_DIR/admin-class.php" ]; then
            if ! safe_copy "$THEME_FILES_DIR/admin-class.php" "$PLUGIN_PATH/includes/class-derleiti-admin.php" "Admin-Klasse"; then
                print_warning "Konnte Admin-Klasse nicht kopieren"
            else
                print_success "Admin-Klasse kopiert"
            fi
        else
            # Erstelle Standard-Admin-Klasse
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
        
        // Enqueue Admin-Skripte und Styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Dashboard Widget hinzufügen
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        
        // Plugin-Aktionslinks hinzufügen
        add_filter('plugin_action_links_derleiti-plugin/plugin-main.php', array($this, 'add_plugin_action_links'));
        
        // Admin-Notices für Plugin-Updates und Tipps
        add_action('admin_notices', array($this, 'display_admin_notices'));
        
        // Metaboxen für Projekte und Beiträge hinzufügen
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // Speichern der Metabox-Daten
        add_action('save_post', array($this, 'save_meta_box_data'));
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
        
        // Untermenü für Theme-Einstellungen
        add_submenu_page(
            'derleiti-plugin',
            __('Theme-Einstellungen', 'derleiti-plugin'),
            __('Theme-Einstellungen', 'derleiti-plugin'),
            'manage_options',
            'derleiti-plugin',
            array($this, 'display_main_admin_page')
        );
        
        // Untermenü für Layout-Builder
        add_submenu_page(
            'derleiti-plugin',
            __('Layout-Builder', 'derleiti-plugin'),
            __('Layout-Builder', 'derleiti-plugin'),
            'manage_options',
            'derleiti-layout',
            array($this, 'display_layout_page')
        );
        
        // Untermenü für KI-Funktionen
        add_submenu_page(
            'derleiti-plugin',
            __('KI-Integration', 'derleiti-plugin'),
            __('KI-Integration', 'derleiti-plugin'),
            'manage_options',
            'derleiti-ai',
            array($this, 'display_ai_page')
        );
        
        // Untermenü für Design-Tools
        add_submenu_page(
            'derleiti-plugin',
            __('Design-Tools', 'derleiti-plugin'),
            __('Design-Tools', 'derleiti-plugin'),
            'manage_options',
            'derleiti-design',
            array($this, 'display_design_page')
        );
        
        // Untermenü für Hilfe und Dokumentation
        add_submenu_page(
            'derleiti-plugin',
            __('Hilfe', 'derleiti-plugin'),
            __('Hilfe', 'derleiti-plugin'),
            'manage_options',
            'derleiti-help',
            array($this, 'display_help_page')
        );
    }
    
    /**
     * Hauptadmin-Seite anzeigen
     */
    public function display_main_admin_page() {
        include DERLEITI_PLUGIN_PATH . 'admin/views/main-page.php';
    }
    
    /**
     * Layout-Builder-Seite anzeigen
     */
    public function display_layout_page() {
        include DERLEITI_PLUGIN_PATH . 'admin/views/layout-page.php';
    }
    
    /**
     * KI-Integrations-Seite anzeigen
     */
    public function display_ai_page() {
        include DERLEITI_PLUGIN_PATH . 'admin/views/ai-page.php';
    }
    
    /**
     * Design-Tools-Seite anzeigen
     */
    public function display_design_page() {
        include DERLEITI_PLUGIN_PATH . 'admin/views/design-page.php';
    }
    
    /**
     * Hilfe-Seite anzeigen
     */
    public function display_help_page() {
        include DERLEITI_PLUGIN_PATH . 'admin/views/help-page.php';
    }
    
    /**
     * Lade Admin-Skripte und Styles
     */
    public function enqueue_admin_assets($hook) {
        // Nur auf Plugin-Seiten laden
        if (strpos($hook, 'derleiti') === false) {
            return;
        }
        
        // Admin CSS
        wp_enqueue_style(
            'derleiti-admin-styles',
            DERLEITI_PLUGIN_URL . 'admin/css/admin-styles.css',
            array(),
            DERLEITI_PLUGIN_VERSION
        );
        
        // Admin JavaScript
        wp_enqueue_script(
            'derleiti-admin-scripts',
            DERLEITI_PLUGIN_URL . 'admin/js/admin-scripts.js',
            array('jquery', 'wp-api'),
            DERLEITI_PLUGIN_VERSION,
            true
        );
        
        // Lokalisiere Skript mit Übersetzungen und AJAX-URL
        wp_localize_script(
            'derleiti-admin-scripts',
            'derleitiPluginData',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'restUrl' => esc_url_raw(rest_url('derleiti-plugin/v1/')),
                'nonce' => wp_create_nonce('wp_rest'),
                'strings' => array(
                    'saveSuccess' => __('Einstellungen gespeichert!', 'derleiti-plugin'),
                    'saveError' => __('Fehler beim Speichern der Einstellungen.', 'derleiti-plugin'),
                    'confirmReset' => __('Möchten Sie wirklich alle Einstellungen zurücksetzen?', 'derleiti-plugin')
                )
            )
        );
        
        // Medien-Uploader-Scripts
        wp_enqueue_media();
        
        // Color Picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }
    
    /**
     * Dashboard-Widget hinzufügen
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'derleiti_dashboard_widget',
            __('Derleiti Theme Status', 'derleiti-plugin'),
            array($this, 'display_dashboard_widget')
        );
    }
    
    /**
     * Dashboard-Widget anzeigen
     */
    public function display_dashboard_widget() {
        include DERLEITI_PLUGIN_PATH . 'admin/views/dashboard-widget.php';
    }
    
    /**
     * Plugin-Aktionslinks hinzufügen
     */
    public function add_plugin_action_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=derleiti-plugin') . '">' . __('Einstellungen', 'derleiti-plugin') . '</a>',
            '<a href="' . admin_url('admin.php?page=derleiti-help') . '">' . __('Hilfe', 'derleiti-plugin') . '</a>'
        );
        
        return array_merge($plugin_links, $links);
    }
    
    /**
     * Admin-Notices anzeigen
     */
    public function display_admin_notices() {
        // Überprüfe, ob Benachrichtigungen ausgeblendet wurden
        $hidden_notices = get_user_meta(get_current_user_id(), 'derleiti_hidden_notices', true);
        if (!is_array($hidden_notices)) {
            $hidden_notices = array();
        }
        
        // Überprüfe, ob das Theme installiert ist
        $current_theme = wp_get_theme();
        if ($current_theme->get('TextDomain') !== 'derleiti-modern' && !in_array('theme_missing', $hidden_notices)) {
            ?>
            <div class="notice notice-warning is-dismissible" data-notice-id="theme_missing">
                <p>
                    <?php _e('Das Derleiti Plugin funktioniert am besten mit dem Derleiti Modern Theme. <a href="themes.php">Jetzt aktivieren</a> oder <a href="#" class="derleiti-dismiss-notice" data-notice="theme_missing">Diese Nachricht ausblenden</a>.', 'derleiti-plugin'); ?>
                </p>
            </div>
            <?php
        }
        
        // Überprüfe auf ausstehende Theme-Updates
        if (function_exists('derleiti_check_theme_updates')) {
            $updates = derleiti_check_theme_updates();
            if ($updates && !in_array('theme_update', $hidden_notices)) {
                ?>
                <div class="notice notice-info is-dismissible" data-notice-id="theme_update">
                    <p>
                        <?php _e('Eine neue Version des Derleiti Modern Themes ist verfügbar. <a href="themes.php">Jetzt aktualisieren</a> oder <a href="#" class="derleiti-dismiss-notice" data-notice="theme_update">Diese Nachricht ausblenden</a>.', 'derleiti-plugin'); ?>
                    </p>
                </div>
                <?php
            }
        }
        
        // JavaScript für Dismiss-Funktionalität
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.derleiti-dismiss-notice').on('click', function(e) {
                e.preventDefault();
                var noticeId = $(this).data('notice');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'derleiti_dismiss_notice',
                        notice: noticeId,
                        nonce: '<?php echo wp_create_nonce('derleiti_dismiss_notice'); ?>'
                    }
                });
                
                $(this).closest('.notice').remove();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Metaboxen hinzufügen
     */
    public function add_meta_boxes() {
        // Metabox für Projekte
        add_meta_box(
            'derleiti_project_options',
            __('Projekt-Optionen', 'derleiti-plugin'),
            array($this, 'render_project_metabox'),
            'project',
            'side',
            'default'
        );
        
        // Metabox für Beiträge und Seiten
        add_meta_box(
            'derleiti_post_options',
            __('Derleiti Optionen', 'derleiti-plugin'),
            array($this, 'render_post_metabox'),
            array('post', 'page'),
            'side',
            'default'
        );
    }
    
    /**
     * Projekt-Metabox rendern
     */
    public function render_project_metabox($post) {
        // Nonce für Sicherheit
        wp_nonce_field('derleiti_project_metabox', 'derleiti_project_nonce');
        
        // Vorhandene Werte abrufen
        $project_url = get_post_meta($post->ID, '_derleiti_project_url', true);
        $project_client = get_post_meta($post->ID, '_derleiti_project_client', true);
        $project_year = get_post_meta($post->ID, '_derleiti_project_year', true);
        
        // Ausgabe der Felder
        ?>
        <p>
            <label for="derleiti_project_url"><?php _e('Projekt-URL:', 'derleiti-plugin'); ?></label>
            <input class="widefat" type="url" id="derleiti_project_url" name="derleiti_project_url" value="<?php echo esc_url($project_url); ?>">
        </p>
        <p>
            <label for="derleiti_project_client"><?php _e('Kunde:', 'derleiti-plugin'); ?></label>
            <input class="widefat" type="text" id="derleiti_project_client" name="derleiti_project_client" value="<?php echo esc_attr($project_client); ?>">
        </p>
        <p>
            <label for="derleiti_project_year"><?php _e('Jahr:', 'derleiti-plugin'); ?></label>
            <input class="widefat" type="number" id="derleiti_project_year" name="derleiti_project_year" min="1900" max="2100" value="<?php echo esc_attr($project_year); ?>">
        </p>
        <?php
    }
    
    /**
     * Beitrags-/Seiten-Metabox rendern
     */
    public function render_post_metabox($post) {
        // Nonce für Sicherheit
        wp_nonce_field('derleiti_post_metabox', 'derleiti_post_nonce');
        
        // Vorhandene Werte abrufen
        $enable_ai = get_post_meta($post->ID, '_derleiti_enable_ai', true);
        $custom_css = get_post_meta($post->ID, '_derleiti_custom_css', true);
        $sidebar_position = get_post_meta($post->ID, '_derleiti_sidebar_position', true);
        
        // Standard-Seitenleiste
        if (empty($sidebar_position)) {
            $sidebar_position = 'right';
        }
        
        // Ausgabe der Felder
        ?>
        <p>
            <input type="checkbox" id="derleiti_enable_ai" name="derleiti_enable_ai" value="1" <?php checked($enable_ai, '1'); ?>>
            <label for="derleiti_enable_ai"><?php _e('KI-Features aktivieren', 'derleiti-plugin'); ?></label>
        </p>
        <p>
            <label for="derleiti_sidebar_position"><?php _e('Seitenleisten-Position:', 'derleiti-plugin'); ?></label>
            <select id="derleiti_sidebar_position" name="derleiti_sidebar_position" class="widefat">
                <option value="right" <?php selected($sidebar_position, 'right'); ?>><?php _e('Rechts', 'derleiti-plugin'); ?></option>
                <option value="left" <?php selected($sidebar_position, 'left'); ?>><?php _e('Links', 'derleiti-plugin'); ?></option>
                <option value="none" <?php selected($sidebar_position, 'none'); ?>><?php _e('Keine Seitenleiste', 'derleiti-plugin'); ?></option>
            </select>
        </p>
        <p>
            <label for="derleiti_custom_css"><?php _e('Benutzerdefiniertes CSS:', 'derleiti-plugin'); ?></label>
            <textarea id="derleiti_custom_css" name="derleiti_custom_css" class="widefat" rows="5"><?php echo esc_textarea($custom_css); ?></textarea>
        </p>
        <?php
    }
    
    /**
     * Metabox-Daten speichern
     */
    public function save_meta_box_data($post_id) {
        // Überprüfe Autorisierung
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Projekt-Metabox speichern
        if (isset($_POST['derleiti_project_nonce']) && wp_verify_nonce($_POST['derleiti_project_nonce'], 'derleiti_project_metabox')) {
            // Projekt-URL
            if (isset($_POST['derleiti_project_url'])) {
                update_post_meta($post_id, '_derleiti_project_url', esc_url_raw($_POST['derleiti_project_url']));
            }
            
            // Projekt-Kunde
            if (isset($_POST['derleiti_project_client'])) {
                update_post_meta($post_id, '_derleiti_project_client', sanitize_text_field($_POST['derleiti_project_client']));
            }
            
            // Projekt-Jahr
            if (isset($_POST['derleiti_project_year'])) {
                update_post_meta($post_id, '_derleiti_project_year', intval($_POST['derleiti_project_year']));
            }
        }
        
        // Post/Page-Metabox speichern
        if (isset($_POST['derleiti_post_nonce']) && wp_verify_nonce($_POST['derleiti_post_nonce'], 'derleiti_post_metabox')) {
            // KI-Features
            $enable_ai = isset($_POST['derleiti_enable_ai']) ? '1' : '0';
            update_post_meta($post_id, '_derleiti_enable_ai', $enable_ai);
            
            // Seitenleisten-Position
            if (isset($_POST['derleiti_sidebar_position'])) {
                update_post_meta($post_id, '_derleiti_sidebar_position', sanitize_text_field($_POST['derleiti_sidebar_position']));
            }
            
            // Benutzerdefiniertes CSS
            if (isset($_POST['derleiti_custom_css'])) {
                update_post_meta($post_id, '_derleiti_custom_css', wp_strip_all_tags($_POST['derleiti_custom_css']));
            }
        }
    }
}
EOF
            print_success "Admin-Klasse erstellt"
        fi
        
        # Kopiere AI-Integration-Klasse, falls verfügbar
        if [ -f "$THEME_FILES_DIR/ai-integration-class.php" ]; then
            if ! safe_copy "$THEME_FILES_DIR/ai-integration-class.php" "$PLUGIN_PATH/includes/class-derleiti-ai-integration.php" "AI-Integration-Klasse"; then
                print_warning "Konnte AI-Integration-Klasse nicht kopieren"
            else
                print_success "AI-Integration-Klasse kopiert"
            fi
        fi
        
        # Kopiere Blocks-Klasse, falls verfügbar
        if [ -f "$THEME_FILES_DIR/blocks-class.php" ]; then
            if ! safe_copy "$THEME_FILES_DIR/blocks-class.php" "$PLUGIN_PATH/includes/class-derleiti-blocks.php" "Blocks-Klasse"; then
                print_warning "Konnte Blocks-Klasse nicht kopieren"
            else
                print_success "Blocks-Klasse kopiert"
            fi
        fi
        
        # Erstelle Admin-View-Template
        print_progress "Erstelle Admin-View-Template"
        if ! create_directory "$PLUGIN_PATH/admin/views" ""; then
            print_warning "Konnte admin/views Verzeichnis nicht erstellen"
        else
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
            print_success "Admin-View-Template erstellt"
            
            # Erstelle Platzhalter für die Tabs
            for tab in general ai blocks tools advanced; do
                touch "$PLUGIN_PATH/admin/views/${tab}-tab.php"
            done
        fi
        
        # Setze Berechtigungen für das Plugin
        print_progress "Setze Berechtigungen für das Plugin"
        execute_command "Berechtigungen für Plugin-Verzeichnisse setzen" "chmod -R 755 \"$PLUGIN_PATH\"" || print_warning "Konnte Berechtigungen für Plugin-Verzeichnisse nicht setzen"
        execute_command "Berechtigungen für Plugin-Dateien setzen" "find \"$PLUGIN_PATH\" -type f -exec chmod 644 {} \\;" || print_warning "Konnte Berechtigungen für Plugin-Dateien nicht setzen"
        
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
    
    # Debug-Information für Fehlerbehebung, wenn Debug-Modus aktiviert ist
    if [ $DEBUG_MODE -eq 1 ]; then
        echo -e "\n${MAGENTA}Debug-Informationen:${NC}"
        echo -e "${MAGENTA}Skript-Verzeichnis: $SCRIPT_DIR${NC}"
        echo -e "${MAGENTA}Theme-Dateien-Verzeichnis: $THEME_FILES_DIR${NC}"
        echo -e "${MAGENTA}WordPress-Verzeichnis: $WP_PATH${NC}"
        echo -e "${MAGENTA}Theme-Verzeichnis: $THEME_PATH${NC}"
        
        if [[ $INSTALL_PLUGIN == "j" ]]; then
            echo -e "${MAGENTA}Plugin-Verzeichnis: $PLUGIN_PATH${NC}"
        fi
        
        echo -e "${MAGENTA}Theme-Dateien im Theme-Dateien-Verzeichnis:${NC}"
        ls -la "$THEME_FILES_DIR" 2>/dev/null || echo -e "${MAGENTA}Kann Inhalt nicht anzeigen.${NC}"
        
        echo -e "${MAGENTA}Theme-Verzeichnis-Inhalt:${NC}"
        ls -la "$THEME_PATH" 2>/dev/null || echo -e "${MAGENTA}Kann Inhalt nicht anzeigen.${NC}"
    fi
fi
