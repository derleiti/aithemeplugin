#!/bin/bash

# Skript zum Erstellen der Verzeichnisstruktur für das Derleiti Modern Theme

# Farbcodes für bessere Lesbarkeit
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}Erstelle Verzeichnisstruktur für das Derleiti Modern Theme...${NC}"

# Skript-Verzeichnis ermitteln
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
THEME_FILES_DIR="${SCRIPT_DIR}/theme-files"

# Erstelle Basisverzeichnisse
mkdir -p "${THEME_FILES_DIR}/inc"
mkdir -p "${THEME_FILES_DIR}/js"
mkdir -p "${THEME_FILES_DIR}/template-parts"
mkdir -p "${THEME_FILES_DIR}/templates"
mkdir -p "${THEME_FILES_DIR}/assets/css"
mkdir -p "${THEME_FILES_DIR}/assets/js"
mkdir -p "${THEME_FILES_DIR}/assets/images"

echo -e "${GREEN}Verzeichnisstruktur wurde erstellt.${NC}"
echo -e "${YELLOW}Speichere nun alle Theme-Dateien im Verzeichnis 'theme-files':${NC}"
echo -e "  - ${THEME_FILES_DIR}/style.css"
echo -e "  - ${THEME_FILES_DIR}/functions.php"
echo -e "  - ${THEME_FILES_DIR}/index.php"
echo -e "  - usw."
echo ""
echo -e "${BLUE}Nachdem du alle Dateien gespeichert hast, verwende das Installationsskript:${NC}"
echo -e "  chmod +x install_derleiti_theme.sh"
echo -e "  ./install_derleiti_theme.sh"
