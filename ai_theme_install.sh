#!/bin/bash
# Derleiti Modern Theme - Enhanced Installation Script
# Version 3.3.0 - Compatible with WordPress 6.7+ and supports PHP 8.1-8.3
# Enhanced error handling, improved logging, expanded AI integration, and automated installation
# Updates: Automatic detection of installation directories and files

# Color codes for better readability
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Variables
DEBUG_MODE=0
LOG_FILE="derleiti_install_$(date +%Y%m%d_%H%M%S).log"
SCRIPT_VERSION="3.3.0"
MIN_PHP_VERSION="8.1.0"
MIN_WP_VERSION="6.2.0"
RECOMMENDED_WP_VERSION="6.7"
THEME_VERSION="2.2"
PLUGIN_VERSION="1.2.0"
BACKUP_DIR="derleiti_backups_$(date +%Y%m%d)"
SCRIPT_DIR="$(pwd)"

# Functions for UI
print_header() {
    clear
    echo -e "${CYAN}====================================================${NC}"
    echo -e "${CYAN}       DERLEITI MODERN THEME INSTALLER             ${NC}"
    echo -e "${CYAN}                Version ${SCRIPT_VERSION}                  ${NC}"
    echo -e "${CYAN}====================================================${NC}"
    echo -e "${BLUE}Theme Version: ${THEME_VERSION} | Plugin Version: ${PLUGIN_VERSION}${NC}"
    echo ""
}

print_section() {
    echo -e "\n${BLUE}${BOLD}$1${NC}\n"
    log_message "SECTION: $1"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
    log_message "SUCCESS: $1"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
    log_message "ERROR: $1"
}

print_warning() {
    echo -e "${YELLOW}! $1${NC}"
    log_message "WARNING: $1"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
    log_message "INFO: $1"
}

print_progress() {
    echo -ne "${CYAN}$1...${NC} "
    log_message "PROGRESS: $1..."
}

print_debug() {
    if [ "$DEBUG_MODE" -eq 1 ]; then
        echo -e "${MAGENTA}DEBUG: $1${NC}"
        log_message "DEBUG: $1"
    fi
}

# Logging function with enhanced security
log_message() {
    local message="$1"
    local log_dir
    log_dir=$(dirname "$LOG_FILE")
    if [ ! -d "$log_dir" ] && [ "$log_dir" != "." ]; then
        mkdir -p "$log_dir"
        chmod 750 "$log_dir"
    fi

    echo "[$(date +"%Y-%m-%d %H:%M:%S")] $message" >> "$LOG_FILE"

    # Rotate log file if over 5MB
    if [ -f "$LOG_FILE" ] && [ $(stat -c%s "$LOG_FILE" 2>/dev/null || stat -f%z "$LOG_FILE" 2>/dev/null) -gt 5242880 ]; then
        mv "$LOG_FILE" "${LOG_FILE}.old"
        echo "[$(date +"%Y-%m-%d %H:%M:%S")] Log rotated due to size" > "$LOG_FILE"
    fi
}

# Initialize log file
init_log_file() {
    echo "[$(date +"%Y-%m-%d %H:%M:%S")] Derleiti Modern Theme Installation - v${SCRIPT_VERSION}" > "$LOG_FILE"
    echo "[$(date +"%Y-%m-%d %H:%M:%S")] System: $(uname -a)" >> "$LOG_FILE"
    if command_exists php; then
        echo "[$(date +"%Y-%m-%d %H:%M:%S")] PHP Version: $(php -r 'echo PHP_VERSION;')" >> "$LOG_FILE"
    fi
    echo "[$(date +"%Y-%m-%d %H:%M:%S")] Running in directory: $(pwd)" >> "$LOG_FILE"
    echo "[$(date +"%Y-%m-%d %H:%M:%S")] ----------------------------------------" >> "$LOG_FILE"
}

# Execute command and log output with error capture
execute_command() {
    local description="$1"
    local command="$2"
    local timeout=${3:-30}
    print_debug "Executing: $command"
    log_message "COMMAND: $command"

    if command -v timeout >/dev/null 2>&1; then
        output=$(timeout "$timeout" bash -c "$command" 2>&1)
        status=$?
        if [ $status -eq 124 ]; then
            print_error "$description timed out after $timeout seconds"
            log_message "COMMAND TIMEOUT ($timeout seconds): $command"
            return 124
        elif [ $status -ne 0 ]; then
            print_error "$description failed: $output"
            log_message "COMMAND FAILED ($status): $output"
            return 1
        fi
    else
        output=$(bash -c "$command" 2>&1)
        status=$?
        if [ $status -ne 0 ]; then
            print_error "$description failed: $output"
            log_message "COMMAND FAILED ($status): $output"
            return 1
        fi
    fi
    print_debug "Command executed successfully"
    log_message "COMMAND SUCCESS: $output"
    return 0
}

command_exists() {
    command -v "$1" >/dev/null 2>&1
}

check_php_version() {
    if command_exists php; then
        PHP_VERSION=$(php -r 'echo PHP_VERSION;')
        if [[ $(php -r "echo version_compare('$PHP_VERSION', '$MIN_PHP_VERSION', '>=') ? '1' : '0';") == "1" ]]; then
            print_success "PHP Version: $PHP_VERSION"
            
            # Check for required extensions
            missing_extensions=""
            required_extensions=("mbstring" "xml" "gd" "zip" "curl" "json" "mysqli")
            
            for ext in "${required_extensions[@]}"; do
                if ! php -m | grep -qi "$ext"; then
                    missing_extensions="$missing_extensions $ext"
                fi
            done
            
            # Optional but recommended extensions
            optional_extensions=("imagick" "intl" "opcache")
            missing_optional=""
            
            for ext in "${optional_extensions[@]}"; do
                if ! php -m | grep -qi "$ext"; then
                    missing_optional="$missing_optional $ext"
                fi
            done
            
            if [ -n "$missing_extensions" ]; then
                print_error "Required PHP extensions are missing:$missing_extensions"
                print_info "These extensions are necessary for Derleiti Modern Theme to function correctly."
                return 1
            fi
            
            if [ -n "$missing_optional" ]; then
                print_warning "Some recommended PHP extensions are missing:$missing_optional"
                print_info "These extensions are recommended for optimal performance, but not required."
            fi
            
            return 0
        else
            print_error "PHP Version $PHP_VERSION is not supported. Required: $MIN_PHP_VERSION or higher."
            print_info "Please update your PHP installation."
            return 1
        fi
    else
        print_error "PHP is not installed or not accessible in the PATH."
        return 1
    fi
}

# Check if WordPress version meets requirements
check_wp_version() {
    if [ ! -f "$WP_PATH/wp-includes/version.php" ]; then
        print_warning "Cannot determine WordPress version (wp-includes/version.php not found)"
        return 1
    fi

    # Extract WordPress version from version.php file
    WP_VERSION=$(grep "wp_version =" "$WP_PATH/wp-includes/version.php" | sed "s/.*'\(.*\)'.*/\1/")
    
    if [ -z "$WP_VERSION" ]; then
        print_warning "Could not extract WordPress version"
        return 1
    fi
    
    if [[ $(php -r "echo version_compare('$WP_VERSION', '$MIN_WP_VERSION', '>=') ? '1' : '0';") == "1" ]]; then
        print_success "WordPress Version: $WP_VERSION"
        
        if [[ $(php -r "echo version_compare('$WP_VERSION', '$RECOMMENDED_WP_VERSION', '>=') ? '1' : '0';") == "0" ]]; then
            print_warning "Your WordPress version is below the recommended version ($RECOMMENDED_WP_VERSION)"
            print_info "The theme will work, but updating is recommended for best experience"
        fi
        
        return 0
    else
        print_error "WordPress Version $WP_VERSION is below the minimum required version ($MIN_WP_VERSION)"
        print_info "Please update your WordPress installation before proceeding"
        return 1
    fi
}

# Check if required dependencies are present
check_dependencies() {
    print_section "Checking Dependencies"
    
    local missing_deps=0
    
    # Required tools
    required_tools=("wget" "unzip" "chmod" "php")
    
    for tool in "${required_tools[@]}"; do
        if command_exists "$tool"; then
            print_success "$tool is installed"
        else
            print_error "$tool is not installed"
            missing_deps=1
        fi
    done
    
    # Optional but useful tools
    optional_tools=("curl" "rsync")
    
    for tool in "${optional_tools[@]}"; do
        if command_exists "$tool"; then
            print_success "$tool is installed"
        else
            print_warning "$tool is not installed (optional)"
        fi
    done
    
    check_php_version
    php_status=$?
    
    if [ $missing_deps -eq 1 ] || [ $php_status -eq 1 ]; then
        print_error "Some required dependencies are missing. Please install them and try again."
        return 1
    fi
    
    print_success "All required dependencies are installed"
    return 0
}

# Create directory safely with proper permissions
create_directory() {
    local dir="$1"
    local success_message="$2"
    
    if [ ! -d "$dir" ]; then
        print_progress "Creating directory $dir"
        if mkdir -p "$dir"; then
            # Set secure default permissions
            chmod 755 "$dir"
            print_success "$success_message"
            return 0
        else
            print_error "Failed to create directory: $dir"
            return 1
        fi
    else
        print_info "Directory already exists: $dir"
        return 0
    fi
}

# Check if required files exist in the theme package
check_required_files() {
    local theme_dir="$1"
    required_files=("style.css" "functions.php" "index.php" "footer.php" "header.php")
    
    for file in "${required_files[@]}"; do
        if [ ! -f "$theme_dir/$file" ]; then
            print_error "Missing required theme file: $file"
            return 1
        fi
    done
    
    print_success "All required theme files are present"
    return 0
}

# Backup existing theme or plugin if it exists
backup_existing() {
    local target_dir="$1"
    local type="$2"  # "theme" or "plugin"
    
    if [ -d "$target_dir" ]; then
        print_info "Existing $type found at $target_dir"
        print_info "Creating backup of existing $type before replacement"
        
        # Create backup directory if it doesn't exist
        if ! create_directory "$BACKUP_DIR" "Backup directory created"; then
            print_error "Failed to create backup directory"
            return 1
        }
        
        backup_name=$(basename "$target_dir")_$(date +%Y%m%d_%H%M%S)
        backup_path="$BACKUP_DIR/$backup_name"
        
        print_progress "Backing up existing $type to $backup_path"
        
        if cp -r "$target_dir" "$backup_path"; then
            print_success "Existing $type backed up successfully"
        else
            print_error "Failed to backup existing $type"
            print_warning "Continuing without backup"
        fi
    fi
    
    return 0
}

# Download file with progress and verification
download_file() {
    local url="$1"
    local output_file="$2"
    local description="$3"
    
    print_progress "Downloading $description"
    
    if command_exists wget; then
        if wget --show-progress -q "$url" -O "$output_file"; then
            print_success "Downloaded $description successfully"
        else
            print_error "Failed to download $description"
            return 1
        fi
    elif command_exists curl; then
        if curl -L --progress-bar "$url" -o "$output_file"; then
            print_success "Downloaded $description successfully"
        else
            print_error "Failed to download $description"
            return 1
        fi
    else
        print_error "Neither wget nor curl is available for downloading"
        return 1
    fi
    
    # Verify downloaded file
    if [ ! -f "$output_file" ] || [ ! -s "$output_file" ]; then
        print_error "Downloaded file is empty or not found"
        return 1
    fi
    
    return 0
}

# Copy files safely with permission setting
safe_copy() {
    local source="$1"
    local destination="$2"
    local description="$3"
    
    print_progress "Installing $description"
    
    if [ -d "$source" ]; then
        # Directory copy
        if cp -r "$source"/* "$destination/"; then
            find "$destination" -type d -exec chmod 755 {} \;
            find "$destination" -type f -exec chmod 644 {} \;
            print_success "$description installed successfully"
            return 0
        else
            print_error "Failed to copy $description files"
            return 1
        fi
    elif [ -f "$source" ]; then
        # File copy
        if cp "$source" "$destination/"; then
            chmod 644 "$destination/$(basename "$source")"
            print_success "$description installed successfully"
            return 0
        else
            print_error "Failed to copy $description file"
            return 1
        fi
    else
        print_error "Source does not exist: $source"
        return 1
    fi
}

# Detect WordPress installation
detect_wordpress() {
    print_section "Detecting WordPress Installation"
    
    # Check if current directory is a WordPress installation
    if [ -f "./wp-config.php" ]; then
        WP_PATH=$(pwd)
        print_success "WordPress installation found in current directory"
        return 0
    fi
    
    # Check if this might be a wp-content/themes directory
    if [ -d "../../../wp-includes" ] && [ -f "../../../wp-config.php" ]; then
        WP_PATH=$(cd "../../../" && pwd)
        print_success "WordPress installation found (we appear to be in a theme subdirectory)"
        return 0
    fi
    
    # Check if this might be a wp-content directory
    if [ -d "../../wp-includes" ] && [ -f "../../wp-config.php" ]; then
        WP_PATH=$(cd "../../" && pwd)
        print_success "WordPress installation found (we appear to be in the wp-content directory)"
        return 0
    fi
    
    # Check if parent directory is a WordPress installation
    if [ -f "../wp-config.php" ]; then
        WP_PATH=$(cd "../" && pwd)
        print_success "WordPress installation found in parent directory"
        return 0
    fi
    
    print_warning "No WordPress installation detected automatically"
    print_info "Assuming current directory is the target WordPress directory"
    WP_PATH=$(pwd)
    
    # Create necessary directories if they don't exist
    if ! create_directory "$WP_PATH/wp-content/themes" "WordPress themes directory created"; then
        print_error "Failed to create WordPress directories"
        return 1
    fi
    
    if ! create_directory "$WP_PATH/wp-content/plugins" "WordPress plugins directory created"; then
        print_error "Failed to create WordPress directories"
        return 1
    fi
    
    return 0
}

# Detect theme and plugin files
detect_installation_files() {
    print_section "Detecting Installation Files"
    
    # Check for theme files in the current directory
    LOCAL_THEME_DIR=""
    if [ -d "$SCRIPT_DIR/derleiti-modern" ] && [ -f "$SCRIPT_DIR/derleiti-modern/style.css" ]; then
        LOCAL_THEME_DIR="$SCRIPT_DIR/derleiti-modern"
        print_success "Derleiti Modern theme found in current directory"
    fi
    
    # Check for plugin files in the current directory
    LOCAL_PLUGIN_DIR=""
    if [ -d "$SCRIPT_DIR/derleiti-plugin" ] && [ -f "$SCRIPT_DIR/derleiti-plugin/derleiti-plugin.php" ]; then
        LOCAL_PLUGIN_DIR="$SCRIPT_DIR/derleiti-plugin"
        print_success "Derleiti Plugin found in current directory"
    fi
    
    # If we didn't find the theme, check various likely locations
    if [ -z "$LOCAL_THEME_DIR" ]; then
        # Check possible relative locations
        possible_theme_locations=(
            "./themes/derleiti-modern"
            "../themes/derleiti-modern"
            "../derleiti-modern"
        )
        
        for location in "${possible_theme_locations[@]}"; do
            if [ -d "$location" ] && [ -f "$location/style.css" ]; then
                LOCAL_THEME_DIR=$(cd "$location" && pwd)
                print_success "Derleiti Modern theme found at $location"
                break
            fi
        done
    fi
    
    # If we didn't find the plugin, check various likely locations
    if [ -z "$LOCAL_PLUGIN_DIR" ]; then
        # Check possible relative locations
        possible_plugin_locations=(
            "./plugins/derleiti-plugin"
            "../plugins/derleiti-plugin"
            "../derleiti-plugin"
        )
        
        for location in "${possible_plugin_locations[@]}"; do
            if [ -d "$location" ] && [ -f "$location/derleiti-plugin.php" ]; then
                LOCAL_PLUGIN_DIR=$(cd "$location" && pwd)
                print_success "Derleiti Plugin found at $location"
                break
            fi
        done
    fi
    
    # Summary of what we found
    if [ -n "$LOCAL_THEME_DIR" ]; then
        print_info "Will install theme from: $LOCAL_THEME_DIR"
    else
        print_info "Theme not found locally, will download from web"
    fi
    
    if [ -n "$LOCAL_PLUGIN_DIR" ]; then
        print_info "Will install plugin from: $LOCAL_PLUGIN_DIR"
    else
        print_info "Plugin not found locally, will download from web if needed"
    fi
}

# Install Derleiti Modern Theme
install_theme() {
    print_section "Installing Derleiti Modern Theme"
    
    # Backup existing theme if present
    backup_existing "$THEME_PATH" "theme" || return 1
    
    # Create theme directory
    if [ -d "$THEME_PATH" ]; then
        print_info "Cleaning existing theme directory"
        rm -rf "$THEME_PATH"/*
    fi
    
    if ! create_directory "$THEME_PATH" "Theme directory created"; then
        print_error "Failed to create theme directory"
        return 1
    fi
    
    # Install from local files if available, otherwise download
    if [ -n "$LOCAL_THEME_DIR" ]; then
        safe_copy "$LOCAL_THEME_DIR" "$THEME_PATH" "Derleiti Modern Theme"
    else
        # Download from the web
        THEME_URL="https://downloads.derleiti.de/themes/derleiti-modern-${THEME_VERSION}.zip"
        THEME_ZIP="/tmp/derleiti-modern.zip"
        
        download_file "$THEME_URL" "$THEME_ZIP" "Derleiti Modern Theme" || return 1
        
        print_progress "Extracting theme files"
        if ! unzip -q "$THEME_ZIP" -d "/tmp/derleiti-theme"; then
            print_error "Failed to extract theme files"
            return 1
        fi
        
        # Find the theme directory in the extracted files
        THEME_EXTRACTED_DIR=$(find /tmp/derleiti-theme -type d -name "derleiti-modern*" | head -n 1)
        
        if [ -z "$THEME_EXTRACTED_DIR" ]; then
            print_error "Failed to find theme directory in the extracted files"
            return 1
        fi
        
        safe_copy "$THEME_EXTRACTED_DIR" "$THEME_PATH" "Derleiti Modern Theme"
        
        # Clean up
        rm -f "$THEME_ZIP"
        rm -rf "/tmp/derleiti-theme"
    fi
    
    # Verify theme installation
    if ! check_required_files "$THEME_PATH"; then
        print_error "Theme installation failed: Missing required files"
        return 1
    fi
    
    print_success "Derleiti Modern Theme installed successfully"
    return 0
}

# Install Derleiti Plugin
install_plugin() {
    print_section "Installing Derleiti Modern Theme Plugin"
    
    # Backup existing plugin if present
    backup_existing "$PLUGIN_PATH" "plugin" || return 1
    
    # Create plugin directory
    if [ -d "$PLUGIN_PATH" ]; then
        print_info "Cleaning existing plugin directory"
        rm -rf "$PLUGIN_PATH"/*
    fi
    
    if ! create_directory "$PLUGIN_PATH" "Plugin directory created"; then
        print_error "Failed to create plugin directory"
        return 1
    fi
    
    # Install from local files if available, otherwise download
    if [ -n "$LOCAL_PLUGIN_DIR" ]; then
        safe_copy "$LOCAL_PLUGIN_DIR" "$PLUGIN_PATH" "Derleiti Plugin"
    else
        # Download from the web
        PLUGIN_URL="https://downloads.derleiti.de/plugins/derleiti-plugin-${PLUGIN_VERSION}.zip"
        PLUGIN_ZIP="/tmp/derleiti-plugin.zip"
        
        download_file "$PLUGIN_URL" "$PLUGIN_ZIP" "Derleiti Plugin" || return 1
        
        print_progress "Extracting plugin files"
        if ! unzip -q "$PLUGIN_ZIP" -d "/tmp/derleiti-plugin"; then
            print_error "Failed to extract plugin files"
            return 1
        fi
        
        # Find the plugin directory in the extracted files
        PLUGIN_EXTRACTED_DIR=$(find /tmp/derleiti-plugin -type d -name "derleiti-plugin*" | head -n 1)
        
        if [ -z "$PLUGIN_EXTRACTED_DIR" ]; then
            print_error "Failed to find plugin directory in the extracted files"
            return 1
        fi
        
        safe_copy "$PLUGIN_EXTRACTED_DIR" "$PLUGIN_PATH" "Derleiti Plugin"
        
        # Clean up
        rm -f "$PLUGIN_ZIP"
        rm -rf "/tmp/derleiti-plugin"
    fi
    
    # Verify plugin installation
    if [ ! -f "$PLUGIN_PATH/derleiti-plugin.php" ]; then
        print_error "Plugin installation failed: Main plugin file missing"
        return 1
    fi
    
    print_success "Derleiti Plugin installed successfully"
    return 0
}

# Verify theme and plugin compatibility
verify_compatibility() {
    print_section "Verifying Compatibility"
    
    # Check if both theme and plugin are installed
    if [ ! -f "$THEME_PATH/style.css" ]; then
        print_error "Theme is not installed properly"
        return 1
    fi
    
    if [ "$INSTALL_PLUGIN" = "j" ] || [ "$INSTALL_PLUGIN" = "J" ] || [ "$INSTALL_PLUGIN" = "y" ] || [ "$INSTALL_PLUGIN" = "Y" ] || [ "$INSTALL_PLUGIN" = "1" ]; then
        if [ ! -f "$PLUGIN_PATH/derleiti-plugin.php" ]; then
            print_warning "Plugin is not installed properly"
        else
            # Extract plugin version
            INSTALLED_PLUGIN_VERSION=$(grep "Version:" "$PLUGIN_PATH/derleiti-plugin.php" | head -n 1 | sed 's/.*Version: \([0-9.]*\).*/\1/')
            
            if [ -z "$INSTALLED_PLUGIN_VERSION" ]; then
                print_warning "Could not determine plugin version"
            else
                print_success "Plugin version: $INSTALLED_PLUGIN_VERSION"
            fi
        fi
    fi
    
    # Extract theme version
    INSTALLED_THEME_VERSION=$(grep "Version:" "$THEME_PATH/style.css" | head -n 1 | sed 's/.*Version: \([0-9.]*\).*/\1/')
    
    if [ -z "$INSTALLED_THEME_VERSION" ]; then
        print_warning "Could not determine theme version"
    else
        print_success "Theme version: $INSTALLED_THEME_VERSION"
    fi
    
    # Check WordPress compatibility from theme style.css
    WP_REQUIRES=$(grep "Requires at least:" "$THEME_PATH/style.css" | sed 's/.*Requires at least: \([0-9.]*\).*/\1/')
    WP_TESTED=$(grep "Tested up to:" "$THEME_PATH/style.css" | sed 's/.*Tested up to: \([0-9.]*\).*/\1/')
    PHP_REQUIRES=$(grep "Requires PHP:" "$THEME_PATH/style.css" | sed 's/.*Requires PHP: \([0-9.]*\).*/\1/')
    
    if [ -n "$WP_REQUIRES" ]; then
        print_info "Theme requires WordPress: $WP_REQUIRES+"
    fi
    
    if [ -n "$WP_TESTED" ]; then
        print_info "Theme tested with WordPress: Up to $WP_TESTED"
    fi
    
    if [ -n "$PHP_REQUIRES" ]; then
        print_info "Theme requires PHP: $PHP_REQUIRES+"
    fi
    
    print_success "Compatibility verification completed"
    return 0
}

# Display installation summary
display_summary() {
    local theme_success=$1
    local plugin_success=0
    
    if [ "$INSTALL_PLUGIN" = "j" ] || [ "$INSTALL_PLUGIN" = "J" ] || [ "$INSTALL_PLUGIN" = "y" ] || [ "$INSTALL_PLUGIN" = "Y" ] || [ "$INSTALL_PLUGIN" = "1" ]; then
        plugin_success=$2
    fi
    
    print_section "Installation Summary"
    
    if [ $theme_success -eq 0 ]; then
        print_success "Derleiti Modern Theme installed successfully"
        print_info "Theme directory: $THEME_PATH"
    else
        print_error "Derleiti Modern Theme installation failed"
    fi
    
    if [ "$INSTALL_PLUGIN" = "j" ] || [ "$INSTALL_PLUGIN" = "J" ] || [ "$INSTALL_PLUGIN" = "y" ] || [ "$INSTALL_PLUGIN" = "Y" ] || [ "$INSTALL_PLUGIN" = "1" ]; then
        if [ $plugin_success -eq 0 ]; then
            print_success "Derleiti Plugin installed successfully"
            print_info "Plugin directory: $PLUGIN_PATH"
        else
            print_error "Derleiti Plugin installation failed"
        fi
    fi
    
    print_info "Log file: $LOG_FILE"
    
    if [ -d "$BACKUP_DIR" ]; then
        print_info "Backups created in: $BACKUP_DIR"
    fi
    
    echo -e "\n${GREEN}${BOLD}Next Steps:${NC}"
    echo -e "1. ${BLUE}Activate the theme in WordPress Admin > Appearance > Themes${NC}"
    if [ "$INSTALL_PLUGIN" = "j" ] || [ "$INSTALL_PLUGIN" = "J" ] || [ "$INSTALL_PLUGIN" = "y" ] || [ "$INSTALL_PLUGIN" = "Y" ] || [ "$INSTALL_PLUGIN" = "1" ]; then
        echo -e "2. ${BLUE}Activate the plugin in WordPress Admin > Plugins${NC}"
        echo -e "3. ${BLUE}Configure the plugin settings in WordPress Admin > Derleiti Theme${NC}"
    fi
    echo -e "\n${YELLOW}Need help? Visit ${BLUE}https://derleiti.de/support${NC} for documentation and support.${NC}"
}

# --- Main script start ---
init_log_file
print_header

if [ "$1" == "--debug" ]; then
    DEBUG_MODE=1
    print_info "Debug mode enabled"
fi

check_dependencies
if [ $? -ne 0 ]; then
    print_error "Missing dependencies. Please install required tools and try again."
    exit 1
fi

# Auto-detect WordPress installation in current directory
detect_wordpress
if [ $? -ne 0 ]; then
    print_error "Failed to detect WordPress installation. Aborting."
    exit 1
fi

print_info "WordPress directory: $WP_PATH"

# Set theme and plugin paths
THEME_PATH="$WP_PATH/wp-content/themes/derleiti-modern"
PLUGIN_PATH="$WP_PATH/wp-content/plugins/derleiti-plugin"

print_info "Theme will be installed in: $THEME_PATH"
print_info "Plugin can be installed in: $PLUGIN_PATH"

# Detect installation files in the current directory
detect_installation_files

# Check if WordPress is installed
if [ -f "$WP_PATH/wp-config.php" ]; then
    print_success "WordPress installation found"
    check_wp_version
    WP_VERSION_OK=$?
    if [ $WP_VERSION_OK -ne 0 ]; then
        print_warning "Continue despite WordPress version warning"
    fi
else
    print_warning "No WordPress installation found (wp-config.php not present)"
    print_info "Creating necessary directories for WordPress..."
    if ! create_directory "$WP_PATH/wp-content/themes" "Themes directory created"; then
        print_error "Installation aborted."
        exit 1
    fi
    if ! create_directory "$WP_PATH/wp-content/plugins" "Plugins directory created"; then
        print_error "Installation aborted."
        exit 1
    fi
fi

# Auto-detect if plugin should be installed
INSTALL_PLUGIN="n"
if [ -n "$LOCAL_PLUGIN_DIR" ]; then
    print_info "Derleiti Plugin found, will be installed automatically"
    INSTALL_PLUGIN="y"
else
    print_info "Would you like to install the Derleiti Modern Theme Plugin? (j/n)"
    print_info "The plugin adds AI integration, custom blocks, and other features."
    read -p "> " INSTALL_PLUGIN
fi

# Create backup directory if needed
create_directory "$BACKUP_DIR" "Backup directory created"

# Install theme
install_theme
THEME_SUCCESS=$?

# Install plugin if requested
PLUGIN_SUCCESS=0
if [ "$INSTALL_PLUGIN" = "j" ] || [ "$INSTALL_PLUGIN" = "J" ] || [ "$INSTALL_PLUGIN" = "y" ] || [ "$INSTALL_PLUGIN" = "Y" ] || [ "$INSTALL_PLUGIN" = "1" ]; then
    install_plugin
    PLUGIN_SUCCESS=$?
fi

# Verify compatibility if everything was successful
if [ $THEME_SUCCESS -eq 0 ] && { [ "$INSTALL_PLUGIN" != "j" ] || [ $PLUGIN_SUCCESS -eq 0 ]; }; then
    verify_compatibility
fi

# Display installation summary
display_summary $THEME_SUCCESS $PLUGIN_SUCCESS

# Clean up empty backup directory if no backups were created
if [ -d "$BACKUP_DIR" ] && [ -z "$(ls -A "$BACKUP_DIR")" ]; then
    rmdir "$BACKUP_DIR"
fi

exit 0
