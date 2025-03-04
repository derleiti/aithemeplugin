#!/bin/bash
# Verbessertes Skript zum Überprüfen und Korrigieren der Derleiti-Theme und Plugin-Struktur

# Farben für bessere Lesbarkeit
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Überprüfe und korrigiere Dateistruktur für Derleiti Modern Theme...${NC}"

# Funktion zur Erstellung von Verzeichnissen (falls nicht vorhanden)
ensure_directory() {
    if [ ! -d "$1" ]; then
        mkdir -p "$1"
        echo -e "${GREEN}Verzeichnis erstellt: $1${NC}"
    else
        echo -e "${BLUE}Verzeichnis existiert bereits: $1${NC}"
    fi
}

# Funktion zur Überprüfung und Korrektur von Dateien
check_and_move_file() {
    src=$1
    dest=$2
    src_exists=0
    dest_exists=0

    # Prüfe Quelldatei
    if [ -f "$src" ]; then
        src_exists=1
    elif [ -f "theme-files/$src" ]; then
        src="theme-files/$src"
        src_exists=1
    fi

    # Prüfe Zieldatei
    if [ -f "$dest" ]; then
        dest_exists=1
    fi

    if [ $src_exists -eq 1 ]; then
        if [ $dest_exists -eq 0 ]; then
            # Stelle sicher, dass das Zielverzeichnis existiert
            dest_dir=$(dirname "$dest")
            ensure_directory "$dest_dir"

            # Kopiere die Datei
            cp "$src" "$dest"
            echo -e "${GREEN}Datei kopiert: $src -> $dest${NC}"
        else
            echo -e "${BLUE}Datei existiert bereits: $dest${NC}"
        fi
    else
        echo -e "${RED}Quelldatei nicht gefunden: $src${NC}"
    fi
}

# Erstelle die grundlegende Verzeichnisstruktur für das Theme
echo -e "${YELLOW}Erstelle Theme-Verzeichnisstruktur...${NC}"
ensure_directory "derleiti-modern/inc"
ensure_directory "derleiti-modern/template-parts"
ensure_directory "derleiti-modern/js"
ensure_directory "derleiti-modern/css"
ensure_directory "derleiti-modern/languages"

# Erstelle die Verzeichnisstruktur für das Plugin
echo -e "${YELLOW}Erstelle Plugin-Verzeichnisstruktur...${NC}"
ensure_directory "derleiti-plugin/includes"
ensure_directory "derleiti-plugin/admin/css"
ensure_directory "derleiti-plugin/admin/js"
ensure_directory "derleiti-plugin/admin/views"
ensure_directory "derleiti-plugin/blocks/build"
ensure_directory "derleiti-plugin/blocks/patterns"
ensure_directory "derleiti-plugin/blocks/js"
ensure_directory "derleiti-plugin/blocks/css"
ensure_directory "derleiti-plugin/includes/shortcodes"
ensure_directory "derleiti-plugin/includes/integrations"
ensure_directory "derleiti-plugin/languages"

# Überprüfe und kopiere Kerndateien des Themes
echo -e "${YELLOW}Überprüfe Theme-Dateien...${NC}"
check_and_move_file "functions.php" "derleiti-modern/functions.php"
check_and_move_file "header.php" "derleiti-modern/header.php"
check_and_move_file "footer.php" "derleiti-modern/footer.php"
check_and_move_file "index.php" "derleiti-modern/index.php"
check_and_move_file "sidebar.php" "derleiti-modern/sidebar.php"
check_and_move_file "style-css.css" "derleiti-modern/style.css"
check_and_move_file "theme-json.json" "derleiti-modern/theme.json"
check_and_move_file "content-none.php" "derleiti-modern/template-parts/content-none.php"
check_and_move_file "navigation-js.js" "derleiti-modern/js/navigation.js"

# Überprüfe und kopiere Theme inc-Dateien
check_and_move_file "derleiti-modern/inc/template-functions.php" "derleiti-modern/inc/template-functions.php"
check_and_move_file "derleiti-modern/inc/template-tags.php" "derleiti-modern/inc/template-tags.php"
check_and_move_file "derleiti-modern/inc/customizer.php" "derleiti-modern/inc/customizer.php"

# Überprüfe und kopiere Plugin-Hauptdatei
echo -e "${YELLOW}Überprüfe Plugin-Dateien...${NC}"
check_and_move_file "plugin-main.php" "derleiti-plugin/derleiti-plugin.php"

# Überprüfe und kopiere Plugin-Klassendateien
check_and_move_file "class-derleiti-admin.php" "derleiti-plugin/includes/class-derleiti-admin.php"
check_and_move_file "class-derleiti-ai-integration.php" "derleiti-plugin/includes/class-derleiti-ai-integration.php"
check_and_move_file "class-derleiti-blocks.php" "derleiti-plugin/includes/class-derleiti-blocks.php"
check_and_move_file "class-derleiti-tools.php" "derleiti-plugin/includes/class-derleiti-tools.php"
check_and_move_file "settings-initialization.php" "derleiti-plugin/includes/class-derleiti-settings-manager.php"

# Überprüfe und kopiere Admin-Dateien
check_and_move_file "admin-settings-ai.php" "derleiti-plugin/admin/admin-settings-ai.php"
check_and_move_file "admin-settings-class.php" "derleiti-plugin/admin/admin-settings-class.php"
check_and_move_file "prompt-templates-management.php" "derleiti-plugin/admin/prompt-templates-management.php"

# Überprüfe und kopiere CSS-Dateien
check_and_move_file "ai-content-css.css" "derleiti-plugin/css/ai-content.css"
check_and_move_file "ai-settings-css.css" "derleiti-plugin/admin/css/ai-settings.css"
check_and_move_file "settings-css.css" "derleiti-plugin/admin/css/settings.css"

# Überprüfe und kopiere JS-Dateien
check_and_move_file "ai-settings-js.js" "derleiti-plugin/admin/js/ai-settings.js"
check_and_move_file "settings-js.js" "derleiti-plugin/admin/js/settings.js"
check_and_move_file "ai-block-enhancements.js" "derleiti-plugin/blocks/js/ai-block-enhancements.js"

# Überprüfe und kopiere Integration-Dateien
check_and_move_file "ai-shortcodes.php" "derleiti-plugin/includes/shortcodes/ai-shortcodes.php"
check_and_move_file "ai-theme-integration.php" "derleiti-plugin/includes/integrations/ai-theme-integration.php"
check_and_move_file "rest-api-endpoints.php" "derleiti-plugin/includes/integrations/rest-api-endpoints.php"

# Stelle sicher, dass README-Dateien existieren
check_and_move_file "derleiti-modern/README.md" "derleiti-modern/README.md"
check_and_move_file "derleiti-plugin/README.md" "derleiti-plugin/README.md"

# Überprüfe, ob leere Stubs vorhanden sind
echo -e "${YELLOW}Prüfe auf vorhandene Stub-Dateien...${NC}"
stub_files=(
    "derleiti-modern/inc/template-functions.php"
    "derleiti-modern/inc/template-tags.php"
    "derleiti-modern/inc/customizer.php"
    "derleiti-plugin/admin/views/main-settings.php"
    "derleiti-plugin/admin/views/general-settings.php"
    "derleiti-plugin/admin/views/performance-settings.php"
    "derleiti-plugin/admin/views/ai-settings.php"
)

for file in "${stub_files[@]}"; do
    if [ ! -f "$file" ]; then
        echo -e "${YELLOW}Erstelle Stub-Datei: $file${NC}"
        dir=$(dirname "$file")
        ensure_directory "$dir"
        echo -e "<?php\n/**\n * $file\n * Auto-generierte Stub-Datei\n */\n" > "$file"
    else
        filesize=$(stat -c%s "$file" 2>/dev/null || stat -f%z "$file" 2>/dev/null)
        if [ "$filesize" -lt 10 ]; then
            echo -e "${YELLOW}Stub-Datei zu klein, erneuere: $file${NC}"
            echo -e "<?php\n/**\n * $file\n * Auto-generierte Stub-Datei\n */\n" > "$file"
        else
            echo -e "${BLUE}Stub-Datei existiert bereits: $file${NC}"
        fi
    fi
done

echo -e "${GREEN}Dateistruktur wurde überprüft und korrigiert.${NC}"
echo -e "${YELLOW}Wenn alle Dateien korrekt platziert sind, können Sie den 'theme-files' Ordner löschen.${NC}"
