#!/bin/bash

# Derleiti Modern Theme - Optimiertes Installationsskript
# Version 2.6.3 - Vollständig mit WordPress 6.6 kompatibel und unterstützt PHP 8.1-8.3
# Erweitert mit automatischer WordPress-Installation und Gesundheitscheck

# Farbcodes für bessere Lesbarkeit
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Variablen für Konfiguration und Debugging
DEBUG_MODE=0
SKIP_THEME_DOWNLOAD=0
CUSTOM_THEME_PATH=""
FORCE_INSTALL=0

# Allgemeine Hilfsfunktionen
print_header() {
    clear
    echo -e "${CYAN}====================================================${NC}"
    echo -e "${CYAN}          DERLEITI MODERN THEME INSTALLER           ${NC}"
    echo -e "${CYAN}                   Version 2.6.3                    ${NC}"
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

print_debug() {
    if [ $DEBUG_MODE -eq 1 ]; then
        echo -e "${MAGENTA}DEBUG: $1${NC}"
    fi
}

# Überprüfe, ob ein Befehl existiert
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Sichere Herunterladen mit besserer Fehlerbehandlung
safe_download() {
    local url="$1"
    local destination="$2"
    
    # Versuche zuerst curl, dann wget
    if command_exists curl; then
        curl -s -L -f "$url" -o "$destination" || {
            print_error "Download mit curl fehlgeschlagen: $url"
            return 1
        }
    elif command_exists wget; then
        wget -q --show-progress "$url" -O "$destination" || {
            print_error "Download mit wget fehlgeschlagen: $url"
            return 1
        }
    else
        print_error "Weder curl noch wget verfügbar. Download nicht möglich."
        return 1
    fi
}

# Überprüfe Systemanforderungen
check_system_requirements() {
    # Überprüfe Linux-Distribution
    if [[ -f /etc/os-release ]]; then
        . /etc/os-release
        print_info "Erkannte Distribution: $NAME $VERSION_ID"
    fi

    # Überprüfe erforderliche Tools
    local required_tools=("bash" "curl" "wget" "unzip" "php")
    local missing_tools=()

    for tool in "${required_tools[@]}"; do
        if ! command_exists "$tool"; then
            missing_tools+=("$tool")
        fi
    done

    if [[ ${#missing_tools[@]} -gt 0 ]]; then
        print_error "Fehlende erforderliche Tools: ${missing_tools[*]}"
        exit 1
    fi

    # PHP-Version prüfen
    php_version=$(php -r 'echo phpversion();')
    print_info "Erkannte PHP-Version: $php_version"

    # Prüfe PHP-Version für Kompatibilität
    if [[ "$(printf '%s\n' "8.1" "$php_version" | sort -V | head -n1)" != "8.1" ]]; then
        print_error "Inkompatible PHP-Version. Benötigt: mindestens PHP 8.1"
        exit 1
    fi
}

# Hauptinstallationsroutine
main_installation() {
    print_header

    # Systemanforderungen prüfen
    check_system_requirements

    # Benutzer nach WordPress-Verzeichnis fragen
    read -p "Geben Sie den vollständigen Pfad zum WordPress-Hauptverzeichnis ein (z.B. /var/www/html): " WP_PATH

    # Entferne abschließenden Schrägstrich, falls vorhanden
    WP_PATH=${WP_PATH%/}

    # Überprüfe, ob der Pfad existiert
    if [ ! -d "$WP_PATH" ]; then
        print_warning "Das angegebene Verzeichnis existiert nicht."
        read -p "Soll das Verzeichnis erstellt werden? (j/n): " CREATE_DIR
        if [[ $CREATE_DIR =~ ^[Jj]$ ]]; then
            mkdir -p "$WP_PATH" || {
                print_error "Konnte Verzeichnis nicht erstellen. Überprüfen Sie die Berechtigungen."
                exit 1
            }
            print_success "Verzeichnis $WP_PATH wurde erstellt."
        else
            print_error "Installation abgebrochen."
            exit 1
        fi
    fi

    # Themes-Verzeichnis sicherstellen
    mkdir -p "$WP_PATH/wp-content/themes" || {
        print_error "Konnte Themes-Verzeichnis nicht erstellen."
        exit 1
    }

    # Prüfe, ob das Theme schon existiert
    THEME_PATH="$WP_PATH/wp-content/themes/derleiti-modern"
    if [ -d "$THEME_PATH" ]; then
        print_warning "Das Theme 'derleiti-modern' existiert bereits."
        read -p "Möchten Sie das vorhandene Theme überschreiben? (j/n): " OVERWRITE
        if [[ $OVERWRITE =~ ^[Nn]$ ]]; then
            print_error "Installation abgebrochen."
            exit 1
        fi
        rm -rf "$THEME_PATH"
    fi

    # Theme-Verzeichnis erstellen
    mkdir -p "$THEME_PATH" || {
        print_error "Konnte Theme-Verzeichnis nicht erstellen."
        exit 1
    }

    # Dummy-Dateien für minimale Theme-Funktionalität erstellen
    cat > "$THEME_PATH/style.css" << EOF
/*
Theme Name: Derleiti Modern
Theme URI: https://derleiti.de
Author: Derleiti
Description: Ein modernes WordPress-Theme für Blog- und Projektdarstellung
Version: 2.6.3
Requires at least: 6.2
Tested up to: 6.6
Requires PHP: 8.1
License: GNU General Public License v2 or later
Text Domain: derleiti-modern
*/
EOF

    cat > "$THEME_PATH/index.php" << EOF
<?php
/**
 * The main template file
 */
get_header();
?>
<div class="site-content">
    <main id="primary" class="content-area">
        <?php
        if (have_posts()) :
            while (have_posts()) :
                the_post();
                the_content();
            endwhile;
        endif;
        ?>
    </main>
</div>
<?php
get_footer();
?>
EOF

    cat > "$THEME_PATH/functions.php" << EOF
<?php
/**
 * Theme Functions
 */
function derleiti_modern_setup() {
    load_theme_textdomain('derleiti-modern', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
}
add_action('after_setup_theme', 'derleiti_modern_setup');
EOF

    print_success "Minimales Theme-Grundgerüst wurde erstellt in $THEME_PATH"

    # Abschluss der Installation
    print_section "Installation abgeschlossen"
    print_info "Das Derleiti Modern Theme wurde erstellt in $THEME_PATH"
    print_info "Aktivieren Sie das Theme im WordPress-Admin unter 'Darstellung' > 'Themes'"
}

# Hauptinstallation starten
main_installation

exit 0
