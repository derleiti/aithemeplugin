#!/bin/bash

# Cleanup-Script für Dateien und Ordner
# Dieses Script prüft, ob Dateien in den korrekten Ordnern liegen und löscht
# unnötige Dateien und Ordner.

# Farben für Ausgaben
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Log-Funktion
log() {
    echo -e "${2:-$BLUE}[$(date '+%Y-%m-%d %H:%M:%S')] $1${NC}"
}

# Funktion zur Bestätigung
confirm() {
    read -p "$1 (j/n): " answer
    case "$answer" in
        [jJ]* ) return 0;;
        * ) return 1;;
    esac
}

# Verzeichnisse, die überprüft werden sollen (anpassen je nach Bedarf)
MAIN_DIR="."
DOCUMENT_DIR="./Dokumente"
IMAGE_DIR="./Bilder"
VIDEO_DIR="./Videos"
DOWNLOAD_DIR="./Downloads"
TEMP_DIR="./Temp"

# Temporäre Datei für die Ergebnisse
TEMP_FILE="/tmp/cleanup_results_$(date +%s).txt"

# Erstelle Log-Datei
LOG_FILE="cleanup_log_$(date +%Y%m%d_%H%M%S).log"
touch "$LOG_FILE"

log "Cleanup-Script gestartet" | tee -a "$LOG_FILE"
log "Arbeitet im Verzeichnis: $(pwd)" | tee -a "$LOG_FILE"

# 1. Überprüfe, ob die Verzeichnisstruktur existiert, sonst erstelle sie
check_directories() {
    log "Überprüfe Verzeichnisstruktur..." | tee -a "$LOG_FILE"
    
    for dir in "$DOCUMENT_DIR" "$IMAGE_DIR" "$VIDEO_DIR" "$DOWNLOAD_DIR"; do
        if [ ! -d "$dir" ]; then
            log "Verzeichnis $dir existiert nicht" "$YELLOW" | tee -a "$LOG_FILE"
            if confirm "Möchten Sie das Verzeichnis $dir erstellen?"; then
                mkdir -p "$dir"
                log "Verzeichnis $dir erstellt" "$GREEN" | tee -a "$LOG_FILE"
            fi
        else
            log "Verzeichnis $dir existiert bereits" "$GREEN" | tee -a "$LOG_FILE"
        fi
    done
}

# 2. Überprüfe, ob Dateien in den korrekten Ordnern liegen
check_files_location() {
    log "Überprüfe Dateien auf korrekte Platzierung..." | tee -a "$LOG_FILE"
    
    # Dokumente
    find "$MAIN_DIR" -maxdepth 1 -type f \( -name "*.pdf" -o -name "*.doc" -o -name "*.docx" -o -name "*.txt" -o -name "*.odt" \) -print > "$TEMP_FILE"
    if [ -s "$TEMP_FILE" ]; then
        log "Gefundene Dokumente außerhalb des Dokumente-Ordners:" "$YELLOW" | tee -a "$LOG_FILE"
        cat "$TEMP_FILE" | tee -a "$LOG_FILE"
        if confirm "Möchten Sie diese Dateien in den Dokumente-Ordner verschieben?"; then
            while IFS= read -r file; do
                mkdir -p "$DOCUMENT_DIR"
                mv "$file" "$DOCUMENT_DIR/"
                log "Verschoben: $file -> $DOCUMENT_DIR/" "$GREEN" | tee -a "$LOG_FILE"
            done < "$TEMP_FILE"
        fi
    fi
    
    # Bilder
    find "$MAIN_DIR" -maxdepth 1 -type f \( -name "*.jpg" -o -name "*.jpeg" -o -name "*.png" -o -name "*.gif" -o -name "*.bmp" \) -print > "$TEMP_FILE"
    if [ -s "$TEMP_FILE" ]; then
        log "Gefundene Bilder außerhalb des Bilder-Ordners:" "$YELLOW" | tee -a "$LOG_FILE"
        cat "$TEMP_FILE" | tee -a "$LOG_FILE"
        if confirm "Möchten Sie diese Dateien in den Bilder-Ordner verschieben?"; then
            while IFS= read -r file; do
                mkdir -p "$IMAGE_DIR"
                mv "$file" "$IMAGE_DIR/"
                log "Verschoben: $file -> $IMAGE_DIR/" "$GREEN" | tee -a "$LOG_FILE"
            done < "$TEMP_FILE"
        fi
    fi
    
    # Videos
    find "$MAIN_DIR" -maxdepth 1 -type f \( -name "*.mp4" -o -name "*.avi" -o -name "*.mkv" -o -name "*.mov" -o -name "*.wmv" \) -print > "$TEMP_FILE"
    if [ -s "$TEMP_FILE" ]; then
        log "Gefundene Videos außerhalb des Video-Ordners:" "$YELLOW" | tee -a "$LOG_FILE"
        cat "$TEMP_FILE" | tee -a "$LOG_FILE"
        if confirm "Möchten Sie diese Dateien in den Video-Ordner verschieben?"; then
            while IFS= read -r file; do
                mkdir -p "$VIDEO_DIR"
                mv "$file" "$VIDEO_DIR/"
                log "Verschoben: $file -> $VIDEO_DIR/" "$GREEN" | tee -a "$LOG_FILE"
            done < "$TEMP_FILE"
        fi
    fi
}

# 3. Finde und lösche temporäre Dateien
cleanup_temp_files() {
    log "Suche nach temporären Dateien..." | tee -a "$LOG_FILE"
    
    # Temporäre Dateien
    find "$MAIN_DIR" -type f \( -name "*.tmp" -o -name "*.temp" -o -name "*.bak" -o -name "*.swp" -o -name "*~" \) -print > "$TEMP_FILE"
    if [ -s "$TEMP_FILE" ]; then
        log "Gefundene temporäre Dateien:" "$YELLOW" | tee -a "$LOG_FILE"
        cat "$TEMP_FILE" | tee -a "$LOG_FILE"
        if confirm "Möchten Sie diese temporären Dateien löschen?"; then
            while IFS= read -r file; do
                rm "$file"
                log "Gelöscht: $file" "$GREEN" | tee -a "$LOG_FILE"
            done < "$TEMP_FILE"
        fi
    else
        log "Keine temporären Dateien gefunden" "$GREEN" | tee -a "$LOG_FILE"
    fi
    
    # Logs
    find "$MAIN_DIR" -type f -name "*.log" ! -path "*/$LOG_FILE" -print > "$TEMP_FILE"
    if [ -s "$TEMP_FILE" ]; then
        log "Gefundene Log-Dateien:" "$YELLOW" | tee -a "$LOG_FILE"
        cat "$TEMP_FILE" | tee -a "$LOG_FILE"
        if confirm "Möchten Sie diese Log-Dateien löschen?"; then
            while IFS= read -r file; do
                rm "$file"
                log "Gelöscht: $file" "$GREEN" | tee -a "$LOG_FILE"
            done < "$TEMP_FILE"
        fi
    else
        log "Keine Log-Dateien gefunden" "$GREEN" | tee -a "$LOG_FILE"
    fi
}

# 4. Finde und lösche leere Ordner
cleanup_empty_dirs() {
    log "Suche nach leeren Ordnern..." | tee -a "$LOG_FILE"
    
    find "$MAIN_DIR" -type d -empty -print > "$TEMP_FILE"
    if [ -s "$TEMP_FILE" ]; then
        log "Gefundene leere Ordner:" "$YELLOW" | tee -a "$LOG_FILE"
        cat "$TEMP_FILE" | tee -a "$LOG_FILE"
        if confirm "Möchten Sie diese leeren Ordner löschen?"; then
            while IFS= read -r dir; do
                if [ "$dir" != "." ] && [ "$dir" != ".." ]; then
                    rmdir "$dir"
                    log "Gelöscht: $dir" "$GREEN" | tee -a "$LOG_FILE"
                fi
            done < "$TEMP_FILE"
        fi
    else
        log "Keine leeren Ordner gefunden" "$GREEN" | tee -a "$LOG_FILE"
    fi
}

# 5. Suche nach unnötigen Ordnern (hier anpassen nach Bedarf)
cleanup_unnecessary_dirs() {
    log "Überprüfe auf unnötige Ordner..." | tee -a "$LOG_FILE"
    
    # Liste der zu löschenden Ordner (nach Bedarf anpassen)
    DIRS_TO_CHECK=("$TEMP_DIR" "./tmp" "./__pycache__" "./node_modules" "./cache")
    
    for dir in "${DIRS_TO_CHECK[@]}"; do
        if [ -d "$dir" ]; then
            log "Unnötiger Ordner gefunden: $dir" "$YELLOW" | tee -a "$LOG_FILE"
            if confirm "Möchten Sie den Ordner $dir und seinen Inhalt löschen?"; then
                rm -rf "$dir"
                log "Gelöscht: $dir" "$GREEN" | tee -a "$LOG_FILE"
            fi
        fi
    done
}

# 6. Cleanup für große Dateien
check_large_files() {
    log "Suche nach großen Dateien (>100MB)..." | tee -a "$LOG_FILE"
    
    find "$MAIN_DIR" -type f -size +100M -print > "$TEMP_FILE"
    if [ -s "$TEMP_FILE" ]; then
        log "Gefundene große Dateien:" "$YELLOW" | tee -a "$LOG_FILE"
        while IFS= read -r file; do
            size=$(du -h "$file" | cut -f1)
            log "$file ($size)" "$YELLOW" | tee -a "$LOG_FILE"
        done < "$TEMP_FILE"
        
        if confirm "Möchten Sie diese großen Dateien überprüfen?"; then
            while IFS= read -r file; do
                if confirm "Möchten Sie die Datei $file löschen?"; then
                    rm "$file"
                    log "Gelöscht: $file" "$GREEN" | tee -a "$LOG_FILE"
                fi
            done < "$TEMP_FILE"
        fi
    else
        log "Keine großen Dateien gefunden" "$GREEN" | tee -a "$LOG_FILE"
    fi
}

# Hauptprogramm
main() {
    check_directories
    check_files_location
    cleanup_temp_files
    cleanup_empty_dirs
    cleanup_unnecessary_dirs
    check_large_files
    
    # Aufräumen
    if [ -f "$TEMP_FILE" ]; then
        rm "$TEMP_FILE"
    fi
    
    log "Cleanup abgeschlossen. Log-Datei: $LOG_FILE" "$GREEN" | tee -a "$LOG_FILE"
}

# Starte das Hauptprogramm
main
