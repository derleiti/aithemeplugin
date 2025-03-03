#!/bin/bash

# Derleiti Modern Theme - Optimiertes Installationsskript
# Version 2.6.2 - Vollständig mit WordPress 6.6 kompatibel und unterstützt PHP 8.1-8.3
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

# Variable für Debug-Modus
DEBUG_MODE=0

# Funktionen für ein besseres UI
print_header() {
    clear
    echo -e "${CYAN}====================================================${NC}"
    echo -e "${CYAN}          DERLEITI MODERN THEME INSTALLER           ${NC}"
    echo -e "${CYAN}                   Version 2.6.2                    ${NC}"
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

# Bestimme das Skript-Verzeichnis
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

print_header

# Debug-Modus aktivieren, wenn das Argument übergeben wurde
if [ "$1" == "--debug" ]; then
    DEBUG_MODE=1
    print_info "Debug-Modus aktiviert"
fi

# Prüfe Abhängigkeiten
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

# Lade WordPress-Hilfsfunktionen
WP_UTILS_PATH="$SCRIPT_DIR/theme-files/wp-utils.sh"

if [ ! -f "$WP_UTILS_PATH" ]; then
    print_warning "WordPress-Hilfsdatei nicht gefunden: $WP_UTILS_PATH"
    print_info "Erstelle WordPress-Hilfsdatei..."

    # Kopiere die WordPress-Hilfsdatei aus dem Repository oder erstelle sie
    if [ ! -d "$SCRIPT_DIR/theme-files" ]; then
        mkdir -p "$SCRIPT_DIR/theme-files"
    fi

    # Template für die WordPress-Hilfsfunktionen
    cat > "$WP_UTILS_PATH" << 'EOF'
#!/bin/bash

# WordPress Utility-Funktionen für das Derleiti Theme-Installationsskript

# WordPress-Version prüfen
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

# WordPress-Gesundheitscheck
check_wp_health() {
    print_section "WordPress Gesundheitscheck"

    # Prüfe essentielle WordPress-Dateien
    essential_files=(
        "$WP_PATH/wp-load.php"
        "$WP_PATH/wp-includes/version.php"
        "$WP_PATH/wp-admin/admin.php"
        "$WP_PATH/index.php"
        "$WP_PATH/wp-config.php"
    )

    missing_files=0
    for file in "${essential_files[@]}"; do
        if [ ! -f "$file" ]; then
            print_warning "Fehlende essentielle WordPress-Datei: $file"
            missing_files=$((missing_files + 1))
        fi
    done

    # Prüfe essentielle WordPress-Verzeichnisse
    essential_dirs=(
        "$WP_PATH/wp-admin"
        "$WP_PATH/wp-includes"
        "$WP_PATH/wp-content"
    )

    missing_dirs=0
    for dir in "${essential_dirs[@]}"; do
        if [ ! -d "$dir" ]; then
            print_warning "Fehlendes essentielles WordPress-Verzeichnis: $dir"
            missing_dirs=$((missing_dirs + 1))
        fi
    done

    # Prüfe Berechtigungen für wp-content
    if [ -d "$WP_PATH/wp-content" ] && [ ! -w "$WP_PATH/wp-content" ]; then
        print_warning "wp-content Verzeichnis ist nicht beschreibbar"
        return 1
    fi

    # Prüfe, ob WordPress funktioniert, indem die Version abgerufen wird
    if [ -f "$WP_PATH/wp-includes/version.php" ]; then
        WP_VERSION=$(grep "wp_version = " "$WP_PATH/wp-includes/version.php" | cut -d "'" -f 2)
        if [ -z "$WP_VERSION" ]; then
            print_warning "WordPress-Version konnte nicht ermittelt werden"
            return 1
        else
            print_success "WordPress Version: $WP_VERSION"
        fi
    else
        print_warning "WordPress-Versionsdatei nicht gefunden"
        return 1
    fi

    # Prüfe Datenbankverbindung mit wp-config.php-Werten
    if [ -f "$WP_PATH/wp-config.php" ]; then
        # Extrahiere Datenbank-Zugangsdaten aus wp-config.php
        DB_NAME=$(grep "DB_NAME" "$WP_PATH/wp-config.php" | cut -d "'" -f 4)
        DB_USER=$(grep "DB_USER" "$WP_PATH/wp-config.php" | cut -d "'" -f 4)
        DB_PASSWORD=$(grep "DB_PASSWORD" "$WP_PATH/wp-config.php" | cut -d "'" -f 4)
        DB_HOST=$(grep "DB_HOST" "$WP_PATH/wp-config.php" | cut -d "'" -f 4)

        # Prüfe, ob mysql-Befehl verfügbar ist
        if command_exists mysql; then
            print_info "Teste Datenbankverbindung..."
            if mysql -u"$DB_USER" -p"$DB_PASSWORD" -h"$DB_HOST" -e "USE $DB_NAME" >/dev/null 2>&1; then
                print_success "Datenbankverbindung erfolgreich"
            else
                print_warning "Konnte keine Verbindung zur Datenbank herstellen"
                return 1
            fi
        else
            print_info "MySQL-Client nicht gefunden, überspringe Datenbankverbindungstest"
        fi
    else
        print_warning "WordPress-Konfigurationsdatei nicht gefunden"
        return 1
    fi

    # Wenn bis hierhin keine größeren Probleme aufgetreten sind
    if [ $missing_files -eq 0 ] && [ $missing_dirs -eq 0 ]; then
        print_success "WordPress-Installation ist gesund"
        return 0
    else
        print_warning "WordPress-Installation hat Probleme"
        return 1
    fi
}

# WordPress installieren
install_wordpress() {
    print_section "WordPress-Installation"

    # Frage nach Bestätigung
    echo -e "${YELLOW}Dies wird WordPress in $WP_PATH herunterladen und installieren.${NC}"
    echo -e "${YELLOW}Fortfahren? (j/n)${NC}"
    read -p "> " CONTINUE_WP_INSTALL

    if [[ $CONTINUE_WP_INSTALL != "j" && $CONTINUE_WP_INSTALL != "J" ]]; then
        print_error "WordPress-Installation abgebrochen."
        exit 1
    fi

    # Bereite das Verzeichnis vor
    if [ -d "$WP_PATH" ]; then
        print_info "Erstelle Backup des existierenden Verzeichnisses..."
        TIMESTAMP=$(date +"%Y%m%d%H%M%S")
        BACKUP_DIR="${WP_PATH}_backup_${TIMESTAMP}"

        execute_command "Backup erstellen" "mv \"$WP_PATH\" \"$BACKUP_DIR\""
        if [ $? -ne 0 ]; then
            print_error "Backup konnte nicht erstellt werden. Installation abgebrochen."
            exit 1
        fi

        print_success "Backup erstellt unter $BACKUP_DIR"

        # Erstelle neues WordPress-Verzeichnis
        execute_command "WordPress-Verzeichnis erstellen" "mkdir -p \"$WP_PATH\""
        if [ $? -ne 0 ]; then
            print_error "WordPress-Verzeichnis konnte nicht erstellt werden. Installation abgebrochen."
            exit 1
        fi
    else
        # Erstelle WordPress-Verzeichnis, falls es nicht existiert
        execute_command "WordPress-Verzeichnis erstellen" "mkdir -p \"$WP_PATH\""
        if [ $? -ne 0 ]; then
            print_error "WordPress-Verzeichnis konnte nicht erstellt werden. Installation abgebrochen."
            exit 1
        fi
    fi

    # WordPress herunterladen
    print_info "Lade neueste WordPress-Version herunter..."
    TMP_DIR="/tmp/wp-install-$$"
    mkdir -p "$TMP_DIR"

    if command_exists wget; then
        execute_command "WordPress herunterladen" "wget -q -O \"$TMP_DIR/wordpress.zip\" https://wordpress.org/latest.zip"
    else
        execute_command "WordPress herunterladen" "curl -s -L -o \"$TMP_DIR/wordpress.zip\" https://wordpress.org/latest.zip"
    fi

    if [ $? -ne 0 ]; then
        print_error "WordPress konnte nicht heruntergeladen werden. Installation abgebrochen."
        rm -rf "$TMP_DIR"
        exit 1
    fi

    # WordPress extrahieren
    print_info "Extrahiere WordPress..."
    execute_command "WordPress extrahieren" "unzip -q \"$TMP_DIR/wordpress.zip\" -d \"$TMP_DIR\""
    if [ $? -ne 0 ]; then
        print_error "WordPress konnte nicht extrahiert werden. Installation abgebrochen."
        rm -rf "$TMP_DIR"
        exit 1
    fi

    # WordPress-Dateien an den Zielort verschieben
    execute_command "WordPress installieren" "cp -r \"$TMP_DIR/wordpress/\"* \"$WP_PATH/\""
    if [ $? -ne 0 ]; then
        print_error "WordPress konnte nicht installiert werden. Installation abgebrochen."
        rm -rf "$TMP_DIR"
        exit 1
    fi

    # Temporäre Dateien aufräumen
    rm -rf "$TMP_DIR"

    # Korrekte Berechtigungen setzen
    print_info "Setze korrekte Berechtigungen..."
    execute_command "Verzeichnisberechtigungen setzen" "find \"$WP_PATH\" -type d -exec chmod 755 {} \\;"
    execute_command "Dateiberechtigungen setzen" "find \"$WP_PATH\" -type f -exec chmod 644 {} \\;"

    # wp-config.php erstellen
    print_info "Richte WordPress-Konfiguration ein..."

    # Sammel Datenbankinformationen
    echo -e "${YELLOW}WordPress benötigt Datenbankinformationen zur Fertigstellung der Installation.${NC}"
    echo -e "${YELLOW}Bitte gib deinen Datenbanknamen ein:${NC}"
    read -p "> " DB_NAME

    echo -e "${YELLOW}Bitte gib deinen Datenbank-Benutzernamen ein:${NC}"
    read -p "> " DB_USER

    echo -e "${YELLOW}Bitte gib dein Datenbank-Passwort ein:${NC}"
    read -p "> " DB_PASSWORD

    echo -e "${YELLOW}Bitte gib deinen Datenbank-Host ein (normalerweise localhost):${NC}"
    read -p "> " DB_HOST
    [ -z "$DB_HOST" ] && DB_HOST="localhost"

    echo -e "${YELLOW}Bitte gib dein Tabellen-Präfix ein (normalerweise wp_):${NC}"
    read -p "> " DB_PREFIX
    [ -z "$DB_PREFIX" ] && DB_PREFIX="wp_"

    # Authentifizierungsschlüssel generieren
    print_info "Generiere sichere Authentifizierungsschlüssel..."
    WP_KEYS=$(curl -s https://api.wordpress.org/secret-key/1.1/salt/)

    # wp-config.php erstellen
    if [ -f "$WP_PATH/wp-config-sample.php" ]; then
        cp "$WP_PATH/wp-config-sample.php" "$WP_PATH/wp-config.php"

        # Datenbankeinstellungen aktualisieren
        sed -i "s/database_name_here/$DB_NAME/g" "$WP_PATH/wp-config.php"
        sed -i "s/username_here/$DB_USER/g" "$WP_PATH/wp-config.php"
        sed -i "s/password_here/$DB_PASSWORD/g" "$WP_PATH/wp-config.php"
        sed -i "s/localhost/$DB_HOST/g" "$WP_PATH/wp-config.php"
        sed -i "s/wp_/$DB_PREFIX/g" "$WP_PATH/wp-config.php"

        # Authentifizierungsschlüssel ersetzen
        sed -i "/AUTH_KEY/,/NONCE_SALT/c\\$WP_KEYS" "$WP_PATH/wp-config.php"

        print_success "WordPress-Konfigurationsdatei erstellt"
    else
        print_error "wp-config-sample.php nicht gefunden. Manuelle Konfiguration erforderlich."
    fi

    print_success "WordPress wurde erfolgreich installiert!"
    echo -e "${YELLOW}Bitte vervollständige die Installation, indem du deine Seite im Webbrowser besuchst.${NC}"
}
EOF

    chmod +x "$WP_UTILS_PATH"
    print_success "WordPress-Hilfsdatei erstellt"
fi

# WordPress-Hilfsfunktionen laden
source "$WP_UTILS_PATH"

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

    # Führe einen Gesundheitscheck der WordPress-Installation durch
    if ! check_wp_health; then
        print_warning "Die WordPress-Installation hat Probleme."
        echo -e "${YELLOW}Möchtest du WordPress neu installieren? (j/n)${NC}"
        read -p "> " REINSTALL_WP

        if [[ $REINSTALL_WP == "j" || $REINSTALL_WP == "J" ]]; then
            install_wordpress
        else
            print_warning "Die Installation wird mit der vorhandenen WordPress-Installation fortgesetzt."
            print_warning "Dies könnte zu Problemen führen, wenn die WordPress-Installation beschädigt ist."
        fi
    fi
else
    print_warning "Keine WordPress-Installation gefunden (wp-config.php nicht vorhanden)"
    echo -e "${YELLOW}Möchtest du WordPress automatisch installieren? (j/n)${NC}"
    read -p "> " INSTALL_WP

    if [[ $INSTALL_WP == "j" || $INSTALL_WP == "J" ]]; then
        install_wordpress
    else
        echo -e "${YELLOW}Möchtest du trotzdem mit der Theme-Installation fortfahren? (j/n)${NC}"
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
            # Standarddatei wird aus theme-files verwendet
            print_warning "plugin-main.php nicht gefunden, überspringe..."
        fi

        # Kopiere Admin-Klasse, falls verfügbar
        if [ -f "$THEME_FILES_DIR/admin-class.php" ]; then
            if ! safe_copy "$THEME_FILES_DIR/admin-class.php" "$PLUGIN_PATH/includes/class-derleiti-admin.php" "Admin-Klasse"; then
                print_warning "Konnte Admin-Klasse nicht kopieren"
            else
                print_success "Admin-Klasse kopiert"
            fi
        fi

        # Kopiere weitere Klassen-Dateien, falls verfügbar
        if [ -f "$THEME_FILES_DIR/ai-integration-class.php" ]; then
            safe_copy "$THEME_FILES_DIR/ai-integration-class.php" "$PLUGIN_PATH/includes/class-derleiti-ai-integration.php" "AI-Integration-Klasse"
        fi

        if [ -f "$THEME_FILES_DIR/blocks-class.php" ]; then
            safe_copy "$THEME_FILES_DIR/blocks-class.php" "$PLUGIN_PATH/includes/class-derleiti-blocks.php" "Blocks-Klasse"
        fi

        if [ -f "$THEME_FILES_DIR/tools-class.php" ]; then
            safe_copy "$THEME_FILES_DIR/tools-class.php" "$PLUGIN_PATH/includes/class-derleiti-tools.php" "Tools-Klasse"
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
