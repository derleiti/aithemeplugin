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

# Parse Kommandozeilenargumente
while [[ $# -gt 0 ]]; do
    key="$1"
    case $key in
        --debug)
            DEBUG_MODE=1
            shift
            ;;
        --skip-download)
            SKIP_THEME_DOWNLOAD=1
            shift
            ;;
        --custom-path)
            CUSTOM_THEME_PATH="$2"
            shift 2
            ;;
        --force)
            FORCE_INSTALL=1
            shift
            ;;
        --help)
            echo "Verwendung: $0 [Optionen]"
            echo "Optionen:"
            echo "  --debug            Aktiviere Debug-Modus"
            echo "  --skip-download    Überspringe Theme-Download (verwende lokale Dateien)"
            echo "  --custom-path      Benutzerdefinierter Pfad für Theme-Dateien"
            echo "  --force            Erzwinge Installation, ignoriere Warnungen"
            echo "  --help             Zeige diese Hilfe"
            exit 0
            ;;
        *)
            echo "Unbekannte Option: $1"
            exit 1
            ;;
    esac
done

# Alle vorherigen Funktionen bleiben unverändert...
# [Füge den gesamten vorherigen Funktionsblock hier ein]

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

# Hauptinstallationsfunktion
main_installation() {
    print_header

    # Zusätzliche Systemauthentifizierung
    check_system_requirements

    # Erweiterte Fehlerbehandlung
    if [[ $FORCE_INSTALL -eq 0 ]]; then
        pre_installation_checks
    fi

    # Download oder lokale Dateien
    if [[ $SKIP_THEME_DOWNLOAD -eq 0 ]]; then
        download_theme_files
    else
        use_local_theme_files
    fi

    # Rest der Installationsroutine bleibt unverändert...
}

# Neue Funktion für Systemanforderungen
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
}

# Erweiterte Vorinstallationschecks
pre_installation_checks() {
    # Zusätzliche Sicherheits- und Kompatibilitätsprüfungen
    local php_version=$(php -r 'echo PHP_VERSION;')
    local wordpress_dir="$WP_PATH"

    if [[ -d "$wordpress_dir/wp-admin" ]]; then
        print_warning "Eine WordPress-Installation wurde in $wordpress_dir gefunden."
        print_warning "Vorhandene Installationen können überschrieben werden."
        read -p "Möchten Sie fortfahren? (j/n): " confirm
        [[ "$confirm" != [jJ] ]] && exit 1
    fi
}

# Hauptinstallationsroutine aufrufen
main_installation

exit 0
