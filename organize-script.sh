#!/bin/bash
# Skript zum Organisieren der Derleiti-Theme und Plugin-Dateien
# Ausführen aus dem Hauptverzeichnis, in dem derleiti-modern/ und theme-files/ enthalten sind

# Farben für bessere Lesbarkeit
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Starte Dateiorganisation für Derleiti Modern Theme...${NC}"

# Prüfen, ob die notwendigen Verzeichnisse existieren
if [ ! -d "theme-files" ]; then
    echo -e "${RED}Fehler: Verzeichnis 'theme-files' nicht gefunden!${NC}"
    exit 1
fi

if [ ! -d "derleiti-modern" ]; then
    echo -e "${RED}Fehler: Verzeichnis 'derleiti-modern' nicht gefunden!${NC}"
    exit 1
fi

if [ ! -d "derleiti-plugin" ]; then
    echo -e "${YELLOW}Erstelle Plugin-Verzeichnis 'derleiti-plugin'...${NC}"
    mkdir -p derleiti-plugin
fi

# Erstelle Verzeichnisstruktur für das Theme
echo -e "${YELLOW}Erstelle Theme-Verzeichnisstruktur...${NC}"
mkdir -p derleiti-modern/inc
mkdir -p derleiti-modern/template-parts
mkdir -p derleiti-modern/js
mkdir -p derleiti-modern/css

# Erstelle Verzeichnisstruktur für das Plugin
echo -e "${YELLOW}Erstelle Plugin-Verzeichnisstruktur...${NC}"
mkdir -p derleiti-plugin/includes
mkdir -p derleiti-plugin/admin/css
mkdir -p derleiti-plugin/admin/js
mkdir -p derleiti-plugin/admin/views
mkdir -p derleiti-plugin/blocks/build
mkdir -p derleiti-plugin/blocks/patterns
mkdir -p derleiti-plugin/includes/shortcodes
mkdir -p derleiti-plugin/includes/integrations

# Funktion zur Protokollierung von Kopier- oder Verschiebungsvorgängen
function log_copy {
    echo -e "${GREEN}Kopiere: $1 -> $2${NC}"
}

# Verschiebe Haupttheme-Dateien
echo -e "${YELLOW}Verschiebe Haupttheme-Dateien...${NC}"
theme_files=(
    "functions.php"
    "header.php"
    "footer.php"
    "index.php"
    "sidebar.php"
)

for file in "${theme_files[@]}"; do
    if [ -f "theme-files/$file" ]; then
        cp "theme-files/$file" "derleiti-modern/$file"
        log_copy "theme-files/$file" "derleiti-modern/$file"
    else
        echo -e "${RED}Warnung: Datei '$file' nicht gefunden${NC}"
    fi
done

# Verschiebe und benenne theme-json.json um
if [ -f "theme-files/theme-json.json" ]; then
    cp "theme-files/theme-json.json" "derleiti-modern/theme.json"
    log_copy "theme-files/theme-json.json" "derleiti-modern/theme.json"
fi

# Verschiebe und benenne style-css.css um
if [ -f "theme-files/style-css.css" ]; then
    cp "theme-files/style-css.css" "derleiti-modern/style.css"
    log_copy "theme-files/style-css.css" "derleiti-modern/style.css"
fi

# Verschiebe Template-Teil-Dateien
echo -e "${YELLOW}Verschiebe Template-Teil-Dateien...${NC}"
if [ -f "theme-files/content-none.php" ]; then
    cp "theme-files/content-none.php" "derleiti-modern/template-parts/content-none.php"
    log_copy "theme-files/content-none.php" "derleiti-modern/template-parts/content-none.php"
fi

# Verschiebe und benenne JavaScript-Dateien
echo -e "${YELLOW}Verschiebe JavaScript-Dateien...${NC}"
if [ -f "theme-files/navigation-js.js" ]; then
    cp "theme-files/navigation-js.js" "derleiti-modern/js/navigation.js"
    log_copy "theme-files/navigation-js.js" "derleiti-modern/js/navigation.js"
fi

# Verschiebe Plugin Hauptdatei
if [ -f "theme-files/plugin-main.php" ]; then
    cp "theme-files/plugin-main.php" "derleiti-plugin/derleiti-plugin.php"
    log_copy "theme-files/plugin-main.php" "derleiti-plugin/derleiti-plugin.php"
fi

# Verschiebe PHP-Klassendateien ins Plugin
echo -e "${YELLOW}Verschiebe PHP-Klassenateien ins Plugin...${NC}"
class_files=(
    "class-derleiti-admin.php"
    "class-derleiti-ai-integration.php"
    "class-derleiti-blocks.php"
    "class-derleiti-tools.php"
)

for file in "${class_files[@]}"; do
    if [ -f "theme-files/$file" ]; then
        cp "theme-files/$file" "derleiti-plugin/includes/$file"
        log_copy "theme-files/$file" "derleiti-plugin/includes/$file"
    fi
done

# Verschiebe Admin-Settings-Dateien
echo -e "${YELLOW}Verschiebe Admin-Settings-Dateien...${NC}"
admin_files=(
    "admin-settings-ai.php"
    "admin-settings-class.php"
)

for file in "${admin_files[@]}"; do
    if [ -f "theme-files/$file" ]; then
        cp "theme-files/$file" "derleiti-plugin/admin/$file"
        log_copy "theme-files/$file" "derleiti-plugin/admin/$file"
    fi
done

# Verschiebe Settings-Initialization
if [ -f "theme-files/settings-initialization.php" ]; then
    cp "theme-files/settings-initialization.php" "derleiti-plugin/includes/class-derleiti-settings-manager.php"
    log_copy "theme-files/settings-initialization.php" "derleiti-plugin/includes/class-derleiti-settings-manager.php"
fi

# Verschiebe und benenne CSS-Dateien
echo -e "${YELLOW}Verschiebe und benenne CSS-Dateien...${NC}"
if [ -f "theme-files/ai-content-css.css" ]; then
    cp "theme-files/ai-content-css.css" "derleiti-plugin/css/ai-content.css"
    log_copy "theme-files/ai-content-css.css" "derleiti-plugin/css/ai-content.css"
fi

if [ -f "theme-files/ai-settings-css.css" ]; then
    cp "theme-files/ai-settings-css.css" "derleiti-plugin/admin/css/ai-settings.css"
    log_copy "theme-files/ai-settings-css.css" "derleiti-plugin/admin/css/ai-settings.css"
fi

if [ -f "theme-files/settings-css.css" ]; then
    cp "theme-files/settings-css.css" "derleiti-plugin/admin/css/settings.css"
    log_copy "theme-files/settings-css.css" "derleiti-plugin/admin/css/settings.css"
fi

# Verschiebe und benenne JavaScript-Dateien für Admin
echo -e "${YELLOW}Verschiebe und benenne JavaScript-Dateien für Admin...${NC}"
if [ -f "theme-files/ai-settings-js.js" ]; then
    cp "theme-files/ai-settings-js.js" "derleiti-plugin/admin/js/ai-settings.js"
    log_copy "theme-files/ai-settings-js.js" "derleiti-plugin/admin/js/ai-settings.js"
fi

if [ -f "theme-files/settings-js.js" ]; then
    cp "theme-files/settings-js.js" "derleiti-plugin/admin/js/settings.js"
    log_copy "theme-files/settings-js.js" "derleiti-plugin/admin/js/settings.js"
fi

# Verschiebe Block-bezogene Dateien
echo -e "${YELLOW}Verschiebe Block-bezogene Dateien...${NC}"
if [ -f "theme-files/ai-block-enhancements.js" ]; then
    cp "theme-files/ai-block-enhancements.js" "derleiti-plugin/blocks/js/ai-block-enhancements.js"
    log_copy "theme-files/ai-block-enhancements.js" "derleiti-plugin/blocks/js/ai-block-enhancements.js"
fi

# Verschiebe Shortcodes und Integration
echo -e "${YELLOW}Verschiebe Shortcodes und Integration...${NC}"
if [ -f "theme-files/ai-shortcodes.php" ]; then
    cp "theme-files/ai-shortcodes.php" "derleiti-plugin/includes/shortcodes/ai-shortcodes.php"
    log_copy "theme-files/ai-shortcodes.php" "derleiti-plugin/includes/shortcodes/ai-shortcodes.php"
fi

if [ -f "theme-files/ai-theme-integration.php" ]; then
    cp "theme-files/ai-theme-integration.php" "derleiti-plugin/includes/integrations/ai-theme-integration.php"
    log_copy "theme-files/ai-theme-integration.php" "derleiti-plugin/includes/integrations/ai-theme-integration.php"
fi

# Verschiebe zusätzliche Dateien
echo -e "${YELLOW}Verschiebe zusätzliche Dateien...${NC}"
if [ -f "theme-files/prompt-templates-management.php" ]; then
    cp "theme-files/prompt-templates-management.php" "derleiti-plugin/admin/prompt-templates-management.php"
    log_copy "theme-files/prompt-templates-management.php" "derleiti-plugin/admin/prompt-templates-management.php"
fi

if [ -f "theme-files/rest-api-endpoints.php" ]; then
    cp "theme-files/rest-api-endpoints.php" "derleiti-plugin/includes/integrations/rest-api-endpoints.php"
    log_copy "theme-files/rest-api-endpoints.php" "derleiti-plugin/includes/integrations/rest-api-endpoints.php"
fi

# Erstelle leere Dateien, die im Code referenziert werden, aber nicht in den Quelldateien enthalten sind
echo -e "${YELLOW}Erstelle leere Stub-Dateien...${NC}"
touch_files=(
    "derleiti-modern/inc/template-functions.php"
    "derleiti-modern/inc/template-tags.php"
    "derleiti-modern/inc/customizer.php"
    "derleiti-plugin/admin/views/main-settings.php"
    "derleiti-plugin/admin/views/general-settings.php"
    "derleiti-plugin/admin/views/performance-settings.php"
    "derleiti-plugin/admin/views/ai-settings.php"
)

for file in "${touch_files[@]}"; do
    touch "$file"
    echo -e "<?php\n/**\n * $file\n * Auto-generierte Stub-Datei\n */\n" > "$file"
    echo -e "${YELLOW}Erstellt: $file (Stub)${NC}"
done

# Erstelle eine einfache README-Datei
echo -e "${YELLOW}Erstelle README-Dateien...${NC}"
cat > "derleiti-modern/README.md" << EOF
# Derleiti Modern Theme

Ein modernes WordPress-Theme mit KI-Integration.

## Installation

1. Lade dieses Theme in das Verzeichnis /wp-content/themes/ hoch
2. Aktiviere das Theme über das WordPress-Admin-Panel
3. Installiere das Derleiti Plugin für erweiterte Funktionalität

## Systemanforderungen

- WordPress 6.2+
- PHP 8.1+
EOF

cat > "derleiti-plugin/README.md" << EOF
# Derleiti Plugin

Ein Plugin für erweiterte Funktionen des Derleiti Modern Themes mit KI-Integration.

## Installation

1. Lade dieses Plugin in das Verzeichnis /wp-content/plugins/ hoch
2. Aktiviere das Plugin über das WordPress-Admin-Panel

## Systemanforderungen

- WordPress 6.2+
- PHP 8.1+
- Derleiti Modern Theme (empfohlen)
EOF

echo -e "${GREEN}Dateiorganisation abgeschlossen!${NC}"
echo -e "${GREEN}Das Theme und Plugin sind jetzt in ihren jeweiligen Verzeichnissen organisiert.${NC}"
echo -e "${YELLOW}Hinweis: Leere Stub-Dateien wurden für fehlende, aber referenzierte Dateien erstellt.${NC}"
echo -e "${YELLOW}Sie sollten diese mit dem entsprechenden Inhalt füllen, bevor Sie das Theme verwenden.${NC}"
