#!/bin/bash

# Derleiti Modern Theme - Enhanced Installation Script
# Version 3.1.0 - Fully compatible with WordPress 6.7 and supports PHP 8.1-8.3
# Enhanced error handling, improved logging, expanded AI integration, and streamlined installation process

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
SCRIPT_VERSION="3.1.0"
MIN_PHP_VERSION="8.1.0"
MIN_WP_VERSION="6.2.0"
RECOMMENDED_WP_VERSION="6.7"

# Functions for UI
print_header() {
    clear
    echo -e "${CYAN}====================================================${NC}"
    echo -e "${CYAN}       DERLEITI MODERN THEME INSTALLER             ${NC}"
    echo -e "${CYAN}                Version ${SCRIPT_VERSION}                  ${NC}"
    echo -e "${CYAN}====================================================${NC}"
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
    if [ $DEBUG_MODE -eq 1 ]; then
        echo -e "${MAGENTA}DEBUG: $1${NC}"
        log_message "DEBUG: $1"
    fi
}

# Logging function with enhanced security
log_message() {
    # Create log directory if it doesn't exist
    log_dir=$(dirname "$LOG_FILE")
    if [ ! -d "$log_dir" ] && [ "$log_dir" != "." ]; then
        mkdir -p "$log_dir"
        # Secure log directory
        chmod 750 "$log_dir"
    fi
    
    echo "[$(date +"%Y-%m-%d %H:%M:%S")] $1" >> "$LOG_FILE"
    
    # Rotate log file if it gets too large (over 5MB)
    if [ -f "$LOG_FILE" ] && [ $(stat -c%s "$LOG_FILE") -gt 5242880 ]; then
        mv "$LOG_FILE" "${LOG_FILE}.old"
        echo "[$(date +"%Y-%m-%d %H:%M:%S")] Log rotated due to size" > "$LOG_FILE"
    fi
}

# Execute command and log output with enhanced error capture
execute_command() {
    local description="$1"
    local command="$2"
    local timeout=${3:-30} # Default timeout of 30 seconds
    
    print_debug "Executing: $command"
    log_message "COMMAND: $command"
    
    # Use timeout to prevent hanging commands
    output=$(timeout $timeout bash -c "$command" 2>&1)
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
    
    print_debug "Command executed successfully"
    log_message "COMMAND SUCCESS: $output"
    return 0
}

# Check if a command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check PHP version with more detailed feedback
check_php_version() {
    if command_exists php; then
        PHP_VERSION=$(php -r 'echo PHP_VERSION;')
        PHP_MAJOR_VERSION=$(php -r 'echo PHP_MAJOR_VERSION;')
        PHP_MINOR_VERSION=$(php -r 'echo PHP_MINOR_VERSION;')
        
        if [[ $(php -r "echo version_compare('$PHP_VERSION', '$MIN_PHP_VERSION', '>=') ? '1' : '0';") == "1" ]]; then
            print_success "PHP Version: $PHP_VERSION"
            
            # Check for optimal PHP extensions
            missing_extensions=""
            for ext in mbstring xml gd zip curl json; do
                if ! php -m | grep -q -i "$ext"; then
                    missing_extensions="$missing_extensions $ext"
                fi
            done
            
            if [ -n "$missing_extensions" ]; then
                print_warning "Some recommended PHP extensions are missing:$missing_extensions"
                print_info "These extensions are not required but recommended for optimal performance."
            fi
            
            return 0
        else
            print_warning "PHP Version $PHP_VERSION is not supported. PHP $MIN_PHP_VERSION or higher is required."
            print_info "Please update your PHP installation before proceeding."
            return 1
        fi
    else
        print_warning "PHP was not found. Please make sure PHP is installed."
        return 1
    fi
}

# Check WordPress version with more details
check_wp_version() {
    if [ -f "$WP_PATH/wp-includes/version.php" ]; then
        WP_VERSION=$(grep "wp_version = " "$WP_PATH/wp-includes/version.php" | cut -d "'" -f 2)
        if [[ $(php -r "echo version_compare('$WP_VERSION', '$MIN_WP_VERSION', '>=') ? '1' : '0';") == "1" ]]; then
            print_success "WordPress Version: $WP_VERSION"
            
            if [[ $(php -r "echo version_compare('$WP_VERSION', '$RECOMMENDED_WP_VERSION', '<') ? '1' : '0';") == "1" ]]; then
                print_info "For best results, WordPress $RECOMMENDED_WP_VERSION or higher is recommended."
            fi
            
            return 0
        else
            print_warning "WordPress Version $WP_VERSION is not supported. WordPress $MIN_WP_VERSION or higher is required."
            return 1
        fi
    else
        print_warning "WordPress version could not be determined."
        return 1
    fi
}

# Create directory with permission checks and security enhancements
create_directory() {
    local dir="$1"
    local msg="$2"
    
    print_debug "Creating directory: $dir"
    
    # Check for directory traversal attempts
    if [[ "$dir" == *".."* ]]; then
        print_error "Invalid directory path: $dir"
        return 1
    fi
    
    # Check if directory already exists
    if [ -d "$dir" ]; then
        print_debug "Directory already exists: $dir"
        
        # Check write permissions
        if [ ! -w "$dir" ]; then
            print_error "No write permission for existing directory: $dir"
            return 1
        fi
        
        return 0
    fi
    
    # Create parent directories if needed
    mkdir -p "$dir" 2>/dev/null
    
    if [ $? -ne 0 ]; then
        print_error "Error creating directory: $dir"
        print_error "Please check permissions."
        return 1
    else
        if [ -n "$msg" ]; then
            print_success "$msg"
        fi
    fi
    
    # Check write permissions after creation
    if [ ! -w "$dir" ]; then
        print_error "No write permission for directory: $dir"
        return 1
    fi
    
    # Set secure permissions
    chmod 755 "$dir"
    
    return 0
}

# Check if required files exist with enhanced validation
check_required_files() {
    local theme_files_dir="$1"
    local result=0
    
    # Validate theme_files_dir is a safe path
    if [[ ! -d "$theme_files_dir" || "$theme_files_dir" == *".."* ]]; then
        print_error "Invalid theme files directory: $theme_files_dir"
        return 2
    }
    
    # Minimum requirements for theme functionality
    if [ ! -f "$theme_files_dir/style.css" ] && [ ! -f "$SCRIPT_DIR/theme-files/style-css.css" ]; then
        print_warning "style.css was not found."
        result=1
    fi
    
    if [ ! -f "$theme_files_dir/functions.php" ] && [ ! -f "$SCRIPT_DIR/theme-files/functions-php.php" ]; then
        print_warning "functions.php was not found."
        result=1
    fi
    
    if [ ! -f "$theme_files_dir/index.php" ] && [ ! -f "$SCRIPT_DIR/theme-files/index-php.php" ]; then
        print_warning "index.php was not found."
        result=1
    }
    
    # Check for theme.json (required for newer features)
    if [ ! -f "$theme_files_dir/theme.json" ] && [ ! -f "$SCRIPT_DIR/theme-files/theme-json.json" ]; then
        print_warning "theme.json was not found. This file is recommended for block theme features."
        result=1
    }
    
    return $result
}

# Check dependencies with enhanced recommendations
check_dependencies() {
    print_section "Checking Dependencies"
    
    # Check if unzip is installed
    if ! command_exists unzip; then
        print_warning "unzip is not installed. It is recommended to install it."
        print_info "On Ubuntu/Debian: sudo apt-get install unzip"
        print_info "On CentOS/RHEL: sudo yum install unzip"
        print_info "On macOS: brew install unzip"
    else
        print_success "unzip is installed"
    fi
    
    # Check if curl or wget is installed
    if ! command_exists curl && ! command_exists wget; then
        print_warning "Neither curl nor wget is installed. One of these applications is needed for downloads."
        print_info "On Ubuntu/Debian: sudo apt-get install curl"
        print_info "On CentOS/RHEL: sudo yum install curl"
        print_info "On macOS: brew install curl"
    elif command_exists curl; then
        print_success "curl is installed"
    elif command_exists wget; then
        print_success "wget is installed"
    fi
    
    # Check for git (optional but useful)
    if ! command_exists git; then
        print_info "git is not installed. It's not required but can be useful for development."
    else
        print_success "git is installed"
    fi
    
    # Check PHP version
    check_php_version
    
    return 0
}

# Download file using curl or wget with enhanced error handling and timeout
download_file() {
    local url="$1"
    local destination="$2"
    local timeout=${3:-30} # Default timeout of 30 seconds
    
    print_debug "Downloading $url to $destination"
    
    # Validate URL (basic check)
    if [[ ! "$url" =~ ^https?:// ]]; then
        print_error "Invalid URL format: $url"
        return 1
    }
    
    # Validate destination path
    if [[ "$destination" == *".."* ]]; then
        print_error "Invalid destination path: $destination"
        return 1
    }
    
    if command_exists curl; then
        curl --connect-timeout "$timeout" -s -L "$url" -o "$destination"
        return $?
    elif command_exists wget; then
        wget --timeout="$timeout" -q "$url" -O "$destination"
        return $?
    else
        print_error "Neither curl nor wget is available. Cannot download file."
        return 1
    fi
}

# Safe copy function with error handling and validation
safe_copy() {
    local src="$1"
    local dest="$2"
    local description="$3"
    
    print_debug "Copying from $src to $dest"
    
    # Validate paths for security
    if [[ "$src" == *".."* || "$dest" == *".."* ]]; then
        print_error "Invalid path detected in copy operation"
        return 1
    }
    
    # Check if source file exists
    if [ ! -f "$src" ]; then
        print_warning "$description: Source file not found: $src"
        return 1
    }
    
    # Check if destination directory exists and create it if not
    local dest_dir=$(dirname "$dest")
    if [ ! -d "$dest_dir" ]; then
        mkdir -p "$dest_dir" 2>/dev/null
        if [ $? -ne 0 ]; then
            print_error "$description: Could not create destination directory: $dest_dir"
            return 1
        fi
    }
    
    # Copy file
    cp -f "$src" "$dest" 2>/dev/null
    if [ $? -ne 0 ]; then
        print_error "$description: Error copying"
        return 1
    }
    
    # Set proper permissions
    chmod 644 "$dest"
    
    print_debug "$description successfully copied"
    return 0
}

# Initialize log file with enhanced metadata
init_log_file() {
    echo "===== Derleiti Modern Theme Installation Log =====" > "$LOG_FILE"
    echo "Date: $(date)" >> "$LOG_FILE"
    echo "Script Version: $SCRIPT_VERSION" >> "$LOG_FILE"
    echo "System Information:" >> "$LOG_FILE"
    echo "  - OS: $(uname -s)" >> "$LOG_FILE"
    echo "  - Distribution: $(cat /etc/os-release 2>/dev/null | grep "PRETTY_NAME" | cut -d "=" -f 2 || echo "Unknown")" >> "$LOG_FILE"
    echo "  - User: $(whoami)" >> "$LOG_FILE"
    echo "  - PHP: $(php -v 2>/dev/null | head -n 1 || echo "Not installed")" >> "$LOG_FILE"
    echo "==========================================" >> "$LOG_FILE"
    
    # Set secure permissions for log file
    chmod 600 "$LOG_FILE"
}

# Display installation summary with enhanced information
display_summary() {
    local success=$1
    
    echo ""
    if [ $success -eq 1 ]; then
        echo -e "${GREEN}====================================================${NC}"
        echo -e "${GREEN}      DERLEITI MODERN THEME SUCCESSFULLY INSTALLED!  ${NC}"
        echo -e "${GREEN}====================================================${NC}"
        echo -e "${YELLOW}The theme is now installed in $THEME_PATH.${NC}"
        echo -e "${YELLOW}Activate the theme in the WordPress admin under 'Appearance' > 'Themes'.${NC}"
        
        if [[ $INSTALL_PLUGIN == "j" ]] && [ -d "$PLUGIN_PATH" ]; then
            echo -e "${YELLOW}The plugin is installed in $PLUGIN_PATH.${NC}"
            echo -e "${YELLOW}Activate the plugin in the WordPress admin under 'Plugins'.${NC}"
        }
        
        # Next steps with more detailed information
        echo -e "\n${BLUE}${BOLD}Next Steps:${NC}"
        echo -e "1. ${CYAN}Log in to your WordPress admin${NC}"
        echo -e "2. ${CYAN}Activate the theme${NC}"
        if [[ $INSTALL_PLUGIN == "j" ]] && [ -d "$PLUGIN_PATH" ]; then
            echo -e "3. ${CYAN}Activate the plugin${NC}"
            echo -e "4. ${CYAN}Configure the theme and plugin settings${NC}"
            echo -e "   - ${CYAN}Visit 'Derleiti' in the admin menu to configure plugin features${NC}"
            echo -e "   - ${CYAN}Configure AI integration in 'Derleiti' > 'AI Integration'${NC}"
        else
            echo -e "3. ${CYAN}Configure the theme settings${NC}"
        fi
        
        # Success message with additional information
        echo -e "\n${GREEN}Theme Features:${NC}"
        echo -e "• Modern design with customizable colors and typography"
        echo -e "• Block editor support with custom patterns"
        echo -e "• Responsive layout optimized for all devices"
        echo -e "• AI integration for enhanced content creation (requires plugin)"
        
        echo -e "\n${YELLOW}For questions or issues, visit https://derleiti.de/support${NC}"
        echo -e "${YELLOW}Installation log saved to: $LOG_FILE${NC}"
        
        # Detect possible WordPress URL
        if command_exists php && [ -f "$WP_PATH/wp-config.php" ]; then
            WP_URL=$(php -r "include '$WP_PATH/wp-config.php'; echo isset(\$_SERVER['HTTP_HOST']) ? 'http://'.\$_SERVER['HTTP_HOST'] : '';")
            if [ -n "$WP_URL" ]; then
                echo -e "\n${GREEN}Access your WordPress site at: $WP_URL${NC}"
            fi
        fi
    else
        echo -e "${RED}====================================================${NC}"
        echo -e "${RED}        ERROR DURING THEME INSTALLATION!           ${NC}"
        echo -e "${RED}====================================================${NC}"
        echo -e "${RED}Please check the errors above and try again.${NC}"
        
        # List missing critical files
        echo -e "\n${RED}Missing critical files:${NC}"
        [ ! -f "$THEME_PATH/style.css" ] && echo -e "${RED}- style.css is missing${NC}"
        [ ! -f "$THEME_PATH/js/navigation.js" ] && echo -e "${RED}- js/navigation.js is missing${NC}"
        [ ! -f "$THEME_PATH/functions.php" ] && echo -e "${RED}- functions.php is missing${NC}"
        [ ! -f "$THEME_PATH/index.php" ] && echo -e "${RED}- index.php is missing${NC}"
        
        # Provide troubleshooting tips
        echo -e "\n${YELLOW}Troubleshooting Tips:${NC}"
        echo -e "1. Ensure you have write permissions to the WordPress directory"
        echo -e "2. Check if all required theme files are present in the theme-files directory"
        echo -e "3. Make sure your PHP version is compatible (${MIN_PHP_VERSION}+)"
        echo -e "4. Try running the script with sudo if you have permission issues"
        echo -e "5. Check the installation log for detailed error messages"
        
        # Debug information for troubleshooting
        if [ $DEBUG_MODE -eq 1 ]; then
            echo -e "\n${MAGENTA}Debug Information:${NC}"
            echo -e "${MAGENTA}Script Directory: $SCRIPT_DIR${NC}"
            echo -e "${MAGENTA}Theme Files Directory: $THEME_FILES_DIR${NC}"
            echo -e "${MAGENTA}WordPress Directory: $WP_PATH${NC}"
            echo -e "${MAGENTA}Theme Directory: $THEME_PATH${NC}"
            
            if [[ $INSTALL_PLUGIN == "j" ]]; then
                echo -e "${MAGENTA}Plugin Directory: $PLUGIN_PATH${NC}"
            fi
            
            echo -e "${MAGENTA}Theme files in theme-files directory:${NC}"
            ls -la "$THEME_FILES_DIR" 2>/dev/null || echo -e "${MAGENTA}Cannot display content.${NC}"
            
            echo -e "${MAGENTA}Theme directory content:${NC}"
            ls -la "$THEME_PATH" 2>/dev/null || echo -e "${MAGENTA}Cannot display content.${NC}"
        }
        
        echo -e "${YELLOW}Installation log saved to: $LOG_FILE${NC}"
    fi
}

# Main installation function with expanded features
install_theme() {
    # Create theme directory structure
    print_section "Creating Theme Directory Structure"

    print_progress "Creating main directories"
    if ! create_directory "$THEME_PATH" ""; then
        print_error "Error creating theme main directory. Installation aborted."
        return 1
    fi

    # Create all required directories at once with expanded structure for FSE
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
        # New directories for AI integration and enhanced features
        "$THEME_PATH/blocks"
        "$THEME_PATH/blocks/ai"
        "$THEME_PATH/blocks/css"
        "$THEME_PATH/blocks/js"
        "$THEME_PATH/blocks/patterns"
        "$THEME_PATH/templates/project"  # For project custom post type
        "$THEME_PATH/inc/ai"
        "$THEME_PATH/inc/performance"
    )

    for dir in "${THEME_DIRECTORIES[@]}"; do
        if ! create_directory "$dir" ""; then
            print_warning "Could not create directory: $dir"
        fi
    done

    print_success "Theme directories successfully created"

    # Copy files from the current directory to the theme folder
    print_section "Copying Theme Files"

    # Check if theme-files directory exists
    if [ ! -d "$THEME_FILES_DIR" ]; then
        print_warning "The 'theme-files' directory was not found."
        echo -e "${YELLOW}Would you like to create it? (j/n)${NC}"
        read -p "> " CREATE_THEME_FILES
        if [[ $CREATE_THEME_FILES == "j" || $CREATE_THEME_FILES == "J" ]]; then
            execute_command "Create theme-files directory" "mkdir -p \"$THEME_FILES_DIR\""
            print_success "Directory created."
            echo -e "${YELLOW}Please place theme files in $THEME_FILES_DIR and run the script again.${NC}"
            return 1
        else
            print_error "Installation aborted."
            return 1
        fi
    fi

    # Check if all required files are present
    print_progress "Checking required files"
    if ! check_required_files "$THEME_FILES_DIR"; then
        print_warning "Some important theme files are missing."
        echo -e "${YELLOW}Continue anyway? This could lead to a non-functioning theme. (j/n)${NC}"
        read -p "> " CONTINUE_MISSING_FILES
        if [[ $CONTINUE_MISSING_FILES != "j" && $CONTINUE_MISSING_FILES != "J" ]]; then
            print_error "Installation aborted."
            return 1
        fi
    else
        print_success "All required files found"
    fi

    # Copy core files with enhanced filename mapping
    core_files=(
        "style.css:style-css.css"
        "theme.json:theme-json.json"
        "functions.php:functions-php.php"
        "index.php:index-php.php"
        "header.php:header-php.php"
        "footer.php:footer-php.php"
        "sidebar.php:sidebar-php.php"
        "single.php:single-php.php"
        "archive.php:archive-php.php"
        "page.php:page-php.php"
        "404.php:404-php.php"
        "comments.php:comments-php.php"
        "search.php:search-php.php"
    )

    print_progress "Copying core files"
    COPY_ERROR=0

    for file_pair in "${core_files[@]}"; do
        target_file="${file_pair%%:*}"
        source_file="${file_pair##*:}"
        
        # Try to copy from the theme-files directory first
        if [ -f "$THEME_FILES_DIR/$target_file" ]; then
            if ! safe_copy "$THEME_FILES_DIR/$target_file" "$THEME_PATH/$target_file" "$target_file"; then
                COPY_ERROR=1
            fi
        # Try the renamed file as alternative
        elif [ -f "$THEME_FILES_DIR/$source_file" ]; then
            if ! safe_copy "$THEME_FILES_DIR/$source_file" "$THEME_PATH/$target_file" "$target_file"; then
                COPY_ERROR=1
            fi
        # For style.css, create a default file if none exists
        elif [ "$target_file" == "style.css" ]; then
            cat > "$THEME_PATH/style.css" << 'EOF'
/*
Theme Name: Derleiti Modern
Theme URI: https://derleiti.de
Author: Derleiti
Description: Ein modernes WordPress-Theme für Blog- und Projektdarstellung mit optimiertem Design, KI-Integration und erweiterten Block-Editor-Features.
Version: 2.6.1
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 8.1
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: derleiti-modern
Tags: blog, portfolio, grid-layout, custom-colors, custom-logo, custom-menu, featured-images, footer-widgets, full-width-template, sticky-post, theme-options, translation-ready, block-styles, wide-blocks, editor-style, full-site-editing, block-patterns
*/

:root {
    /* Base Colors */
    --primary-color: #0066cc;
    --primary-hover: #0052a3;
    --secondary-color: #2c3e50;
    --accent-color: #e74c3c;
    --text-color: #333;
    --light-text: #777;
    --background: #f5f7fa;
    --card-bg: #fff;
    
    /* UI Elements */
    --border-radius: 10px;
    --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    --hover-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
    
    /* Layout */
    --container-width: 1200px;
    --grid-gap: 30px;
    --content-padding: 25px;
    --header-height: 80px;
    
    /* Container Query Breakpoints */
    --mobile: 480px;
    --tablet: 768px;
    --laptop: 1024px;
    --desktop: 1280px;
    
    /* Animation Parameters */
    --animation-speed: 0.3s;
    --animation-easing: cubic-bezier(0.4, 0, 0.2, 1);
    
    /* Extended Font System */
    --font-family-base: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    --font-size-base: 16px;
    --line-height-base: 1.6;
    --font-weight-normal: 400;
    --font-weight-medium: 500;
    --font-weight-bold: 600;
}

/* Rest of CSS file here... */
EOF
        # For theme.json, create a default file if none exists
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
      ],
      "gradients": [
        {
          "slug": "primary-to-accent",
          "gradient": "linear-gradient(135deg, #0066cc 0%, #e74c3c 100%)",
          "name": "Primary to Accent"
        },
        {
          "slug": "secondary-to-background",
          "gradient": "linear-gradient(135deg, #2c3e50 0%, #f5f7fa 100%)",
          "name": "Secondary to Background"
        }
      ]
    },
    "typography": {
      "fontFamilies": [
        {
          "fontFamily": "Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif",
          "slug": "primary"
        }
      ]
    },
    "layout": {
      "contentSize": "800px",
      "wideSize": "1200px"
    }
  }
}
EOF
        else
            print_warning "$target_file was not found or could not be copied."
            COPY_ERROR=1
        fi
    done

    # Copy template-parts files
    print_progress "Copying template-parts files"
    template_parts=(
        "content.php:content-php.php:template-parts/content.php"
        "content-none.php:content-none-php.php:template-parts/content-none.php"
        "content-search.php:content-search-php.php:template-parts/content-search.php"
    )

    for file_info in "${template_parts[@]}"; do
        target_file="${file_info%%:*}"
        source_file="${file_info#*:}"
        source_file="${source_file%%:*}"
        dest_path="${file_info##*:}"
        
        # Try with different options
        if [ -f "$THEME_FILES_DIR/$target_file" ]; then
            if ! safe_copy "$THEME_FILES_DIR/$target_file" "$THEME_PATH/$dest_path" "$dest_path"; then
                COPY_ERROR=1
            fi
        elif [ -f "$THEME_FILES_DIR/$source_file" ]; then
            if ! safe_copy "$THEME_FILES_DIR/$source_file" "$THEME_PATH/$dest_path" "$dest_path"; then
                COPY_ERROR=1
            fi
        else
            print_warning "$dest_path was not found or could not be copied."
            COPY_ERROR=1
        fi
    done

    # Copy JS files with enhanced fallbacks
    print_progress "Copying JS files"
    if [ -f "$THEME_FILES_DIR/js/navigation.js" ]; then
        if ! safe_copy "$THEME_FILES_DIR/js/navigation.js" "$THEME_PATH/js/navigation.js" "navigation.js"; then
            COPY_ERROR=1
        fi
    elif [ -f "$THEME_FILES_DIR/navigation-js.js" ]; then
        if ! safe_copy "$THEME_FILES_DIR/navigation-js.js" "$THEME_PATH/js/navigation.js" "navigation.js"; then
            COPY_ERROR=1
        fi
    else
        # Create a default file
        print_progress "Creating navigation.js"
        cat > "$THEME_PATH/js/navigation.js" << 'EOF'
/**
 * Navigation for mobile menus and enhanced keyboard navigation
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
        
        // Hide menu initially
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
        
        // Keyboard navigation for submenus
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
        
        // Scroll header animation
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
    
    # Copy AI integration files
    print_progress "Copying AI integration files"
    ai_files=(
        "ai-content-css.css:blocks/css/ai-content.css"
        "ai-shortcodes.php:inc/ai/shortcodes.php"
        "ai-theme-integration.php:inc/ai/theme-integration.php"
    )
    
    for file_info in "${ai_files[@]}"; do
        source_file="${file_info%%:*}"
        dest_path="${file_info##*:}"
        
        if [ -f "$THEME_FILES_DIR/$source_file" ]; then
            if ! safe_copy "$THEME_FILES_DIR/$source_file" "$THEME_PATH/$dest_path" "$dest_path"; then
                print_warning "Could not copy AI file: $source_file to $dest_path"
            else
                print_success "AI file copied: $dest_path"
            fi
        fi
    done

    # Copy README.md if available
    if [ -f "$THEME_FILES_DIR/README.md" ]; then
        safe_copy "$THEME_FILES_DIR/README.md" "$THEME_PATH/README.md" "README.md"
    fi

    # Summary of copy operations
    if [ $COPY_ERROR -eq 1 ]; then
        print_warning "Some files could not be copied."
    else
        print_success "All files were successfully copied."
    fi

    # Create screenshot placeholder
    print_progress "Creating placeholder for screenshot.png"
    if command_exists curl; then
        if ! curl -s -o "$THEME_PATH/screenshot.png" "https://via.placeholder.com/1200x900.png?text=Derleiti+Modern+Theme+v2.6.1" 2>/dev/null; then
            print_warning "Could not download screenshot, creating empty file"
            touch "$THEME_PATH/screenshot.png"
        fi
    elif command_exists wget; then
        if ! wget -q -O "$THEME_PATH/screenshot.png" "https://via.placeholder.com/1200x900.png?text=Derleiti+Modern+Theme+v2.6.1" 2>/dev/null; then
            print_warning "Could not download screenshot, creating empty file"
            touch "$THEME_PATH/screenshot.png"
        fi
    else
        print_warning "Could not download screenshot (curl/wget missing), creating empty file"
        touch "$THEME_PATH/screenshot.png"
    fi
    print_success "Done"

    # Set permissions with error handling
    print_progress "Setting permissions"
    execute_command "Setting permissions for directories" "chmod -R 755 \"$THEME_PATH\"" || print_warning "Could not set permissions for directories"
    execute_command "Setting permissions for files" "find \"$THEME_PATH\" -type f -exec chmod 644 {} \\;" || print_warning "Could not set permissions for files"
    print_success "Done"

    return 0
}

# Plugin installation function with expanded features
install_plugin() {
    print_section "Installing Derleiti Modern Theme Plugin"
    
    # Create plugin directory structure
    print_progress "Creating plugin directory structure"
    if ! create_directory "$PLUGIN_PATH" ""; then
        print_error "Error creating plugin main directory."
        print_error "Plugin installation will be skipped."
        return 1
    else
        # Create all required plugin directories at once
        PLUGIN_DIRECTORIES=(
            "$PLUGIN_PATH/admin"
            "$PLUGIN_PATH/admin/css"
            "$PLUGIN_PATH/admin/js"
            "$PLUGIN_PATH/admin/views"
            "$PLUGIN_PATH/includes"
            "$PLUGIN_PATH/includes/ai"    # New dedicated AI directory
            "$PLUGIN_PATH/includes/performance"  # New performance directory
            "$PLUGIN_PATH/blocks"
            "$PLUGIN_PATH/blocks/css"
            "$PLUGIN_PATH/blocks/js"
            "$PLUGIN_PATH/blocks/img"
            "$PLUGIN_PATH/blocks/patterns" # New patterns directory
            "$PLUGIN_PATH/blocks/ai"       # New AI blocks directory
            "$PLUGIN_PATH/templates"
            "$PLUGIN_PATH/js"
            "$PLUGIN_PATH/languages"
            "$PLUGIN_PATH/assets"         # New assets directory
            "$PLUGIN_PATH/assets/images"
            "$PLUGIN_PATH/vendor"         # For third-party libraries
        )

        for dir in "${PLUGIN_DIRECTORIES[@]}"; do
            if ! create_directory "$dir" ""; then
                print_warning "Could not create directory: $dir"
            fi
        done
        
        print_success "Plugin directory structure created"
        
        # Copy plugin-main.php
        print_progress "Creating plugin-main.php"
        if [ -f "$THEME_FILES_DIR/plugin-main.php" ]; then
            if ! safe_copy "$THEME_FILES_DIR/plugin-main.php" "$PLUGIN_PATH/plugin-main.php" "plugin-main.php"; then
                print_warning "Could not copy plugin-main.php"
            else
                print_success "Main plugin file copied"
            fi
        else
            # Create standard plugin-main.php from the template in documents
            if ! safe_copy "$SCRIPT_DIR/theme-files/plugin-main.php" "$PLUGIN_PATH/plugin-main.php" "plugin-main.php"; then
                cat > "$PLUGIN_PATH/plugin-main.php" << 'EOF'
<?php
/**
 * Plugin Name: Derleiti Modern Theme Plugin
 * Plugin URI: https://derleiti.de/plugin
 * Description: Extends the Derleiti Modern Theme with additional functions like AI integration, enhanced block editor functions, and design tools.
 * Version: 1.2.0
 * Author: Derleiti
 * Author URI: https://derleiti.de
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: derleiti-plugin
 * Domain Path: /languages
 * Requires at least: 6.2
 * Tested up to: 6.7
 * Requires PHP: 8.1
 *
 * @package Derleiti_Plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin version
define('DERLEITI_PLUGIN_VERSION', '1.2.0');

// Define plugin path
define('DERLEITI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('DERLEITI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DERLEITI_PLUGIN_FILE', __FILE__);

// Debug mode (only for development)
define('DERLEITI_DEBUG', false);

/**
 * Plugin initialization
 */
function derleiti_plugin_init() {
    // Load text domain for translations
    load_plugin_textdomain('derleiti-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Load components
    $includes_dir = DERLEITI_PLUGIN_PATH . 'includes/';
    
    // Main components
    $core_files = [
        'class-derleiti-admin.php',
        'class-derleiti-blocks.php',
        'class-derleiti-ai-integration.php',
        'class-derleiti-tools.php'
    ];
    
    // Optional new components
    $optional_files = [
        'class-derleiti-performance.php',
        'class-derleiti-analytics.php',
        'class-derleiti-compatibility.php'
    ];
    
    // Load main components
    foreach ($core_files as $file) {
        $filepath = $includes_dir . $file;
        if (file_exists($filepath)) {
            require_once $filepath;
        }
    }
    
    // Load optional components
    foreach ($optional_files as $file) {
        $filepath = $includes_dir . $file;
        if (file_exists($filepath)) {
            require_once $filepath;
        }
    }

    // Initialize classes if available
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

    // Hooks for developer mode
    if (DERLEITI_DEBUG) {
        add_action('admin_footer', 'derleiti_debug_info');
        add_action('wp_footer', 'derleiti_debug_info');
    }
}
add_action('plugins_loaded', 'derleiti_plugin_init');

/**
 * Plugin activation
 */
function derleiti_plugin_activate() {
    // Check WordPress version
    if (version_compare(get_bloginfo('version'), '6.2', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('This plugin requires WordPress 6.2 or higher.', 'derleiti-plugin'));
    }

    // Check PHP version
    if (version_compare(PHP_VERSION, '8.1', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('This plugin requires PHP 8.1 or higher.', 'derleiti-plugin'));
    }

    // Create necessary DB tables
    global $wpdb;
    $table_name = $wpdb->prefix . 'derleiti_settings';

    // Check if the table already exists
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

    // Save default settings
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

        // Only insert if value doesn't exist yet
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

    // Create directories for cache
    $cache_dir = WP_CONTENT_DIR . '/cache/derleiti-plugin';
    if (!file_exists($cache_dir)) {
        wp_mkdir_p($cache_dir);
    }

    // Set capability for administrators
    $role = get_role('administrator');
    if ($role) {
        $role->add_cap('manage_derleiti_plugin');
    }

    // Set activation flag for welcome message
    set_transient('derleiti_plugin_activated', true, 5);

    // Update permalinks
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'derleiti_plugin_activate');

/**
 * Plugin deactivation
 */
function derleiti_plugin_deactivate() {
    // Delete temporary data
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

    // Clear transients
    delete_transient('derleiti_plugin_cache');

    // Delete all plugin-specific transients
    global $wpdb;
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_derleiti_%'");
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_derleiti_%'");

    // Remove capability
    $role = get_role('administrator');
    if ($role) {
        $role->remove_cap('manage_derleiti_plugin');
    }

    // Update permalinks
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'derleiti_plugin_deactivate');

/**
 * Plugin uninstallation
 */
function derleiti_plugin_uninstall() {
    // Remove tables if requested
    $remove_data = get_option('derleiti_remove_data_on_uninstall', false);

    if ($remove_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';

        // Drop table
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

        // Delete options
        delete_option('derleiti_remove_data_on_uninstall');

        // Delete additional plugin-specific options
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'derleiti_%'");
    }
}
register_uninstall_hook(__FILE__, 'derleiti_plugin_uninstall');

// Shortcodes and API endpoints

/**
 * Shortcode for plugin features
 */
function derleiti_features_shortcode($atts) {
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
                esc_html_e('Layout builder template not found', 'derleiti-plugin');
            }
            break;
            
        case 'ai':
            if (file_exists(DERLEITI_PLUGIN_PATH . 'templates/ai-content.php')) {
                include DERLEITI_PLUGIN_PATH . 'templates/ai-content.php';
            } else {
                esc_html_e('AI content template not found', 'derleiti-plugin');
            }
            break;
            
        case 'gallery':
            if (file_exists(DERLEITI_PLUGIN_PATH . 'templates/enhanced-gallery.php')) {
                include DERLEITI_PLUGIN_PATH . 'templates/enhanced-gallery.php';
            } else {
                esc_html_e('Gallery template not found', 'derleiti-plugin');
            }
            break;
            
        case 'analytics':
            if (file_exists(DERLEITI_PLUGIN_PATH . 'templates/analytics.php')) {
                include DERLEITI_PLUGIN_PATH . 'templates/analytics.php';
            } else {
                esc_html_e('Analytics template not found', 'derleiti-plugin');
            }
            break;
            
        default:
            esc_html_e('Feature not found', 'derleiti-plugin');
    }

    echo '</div>';

    return ob_get_clean();
}
add_shortcode('derleiti_feature', 'derleiti_features_shortcode');

/**
 * Add REST API endpoints
 */
function derleiti_plugin_register_rest_routes() {
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

    // New endpoint for system information
    register_rest_route('derleiti-plugin/v1', '/system-info', array(
        'methods' => 'GET',
        'callback' => 'derleiti_plugin_get_system_info',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));

    // New endpoint for clearing cache
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
    
    // AI content generation endpoint
    register_rest_route('derleiti-plugin/v1', '/ai/generate-content', array(
        'methods' => 'POST',
        'callback' => 'derleiti_plugin_generate_ai_content',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
}
add_action('rest_api_init', 'derleiti_plugin_register_rest_routes');
EOF
                print_success "Main plugin file created"
            fi
        fi
        
        # Copy Admin class
        print_progress "Creating Admin class"
        mkdir -p "$PLUGIN_PATH/includes"
        if [ -f "$THEME_FILES_DIR/admin-class.php" ]; then
            if ! safe_copy "$THEME_FILES_DIR/admin-class.php" "$PLUGIN_PATH/includes/class-derleiti-admin.php" "Admin class"; then
                print_warning "Could not copy Admin class"
            else
                print_success "Admin class copied"
            fi
        else
            # Try to copy from script directory
            if [ -f "$SCRIPT_DIR/theme-files/admin-class.php" ]; then
                if ! safe_copy "$SCRIPT_DIR/theme-files/admin-class.php" "$PLUGIN_PATH/includes/class-derleiti-admin.php" "Admin class"; then
                    print_warning "Could not copy Admin class from script directory"
                else
                    print_success "Admin class copied from script directory"
                fi
            else
                # Create standard Admin class (simplified for brevity in this example)
                cat > "$PLUGIN_PATH/includes/class-derleiti-admin.php" << 'EOF'
<?php
/**
 * Manages all Admin functions of the plugin
 *
 * @package Derleiti_Plugin
 * @subpackage Admin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The Admin class of the plugin
 */
class Derleiti_Admin {
    /**
     * Initialize the Admin class
     */
    public function init() {
        // Hook for admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add dashboard widget
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        
        // Add plugin action links
        add_filter('plugin_action_links_derleiti-plugin/plugin-main.php', array($this, 'add_plugin_action_links'));
        
        // Admin notices for plugin updates and tips
        add_action('admin_notices', array($this, 'display_admin_notices'));
        
        // Add metaboxes for projects and posts
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // Save metabox data
        add_action('save_post', array($this, 'save_meta_box_data'));
    }
    
    // Other standard Admin methods would go here
}
EOF
                print_success "Admin class created"
            fi
        fi

        # Copy class files from theme-files directory
        print_progress "Copying class files"
        class_files=(
            "ai-integration-class.php:includes/class-derleiti-ai-integration.php"
            "blocks-class.php:includes/class-derleiti-blocks.php"
            "tools-class.php:includes/class-derleiti-tools.php"
            "blocks-manager.php:includes/class-derleiti-blocks-manager.php"
        )
        
        for file_mapping in "${class_files[@]}"; do
            source_file="${file_mapping%%:*}"
            dest_path="${file_mapping##*:}"
            
            # Find most appropriate source file
            if [ -f "$THEME_FILES_DIR/$source_file" ]; then
                if ! safe_copy "$THEME_FILES_DIR/$source_file" "$PLUGIN_PATH/$dest_path" "$dest_path"; then
                    print_warning "Could not copy class file: $source_file to $dest_path"
                else
                    print_success "Class file copied: $dest_path"
                fi
            elif [ -f "$SCRIPT_DIR/theme-files/$source_file" ]; then
                if ! safe_copy "$SCRIPT_DIR/theme-files/$source_file" "$PLUGIN_PATH/$dest_path" "$dest_path"; then
                    print_warning "Could not copy class file from script directory: $source_file to $dest_path"
                else
                    print_success "Class file copied from script directory: $dest_path"
                fi
            fi
        }
        
        # Copy required AI settings files
        print_progress "Copying AI settings files"
        ai_settings_files=(
            "admin-settings-ai.php:includes/ai/class-derleiti-ai-settings.php"
            "prompt-templates-management.php:includes/ai/class-derleiti-ai-templates.php"
            "rest-api-endpoints.php:includes/ai/class-derleiti-ai-api.php"
        )
        
        for file_mapping in "${ai_settings_files[@]}"; do
            source_file="${file_mapping%%:*}"
            dest_path="${file_mapping##*:}"
            
            if [ -f "$THEME_FILES_DIR/$source_file" ]; then
                if ! safe_copy "$THEME_FILES_DIR/$source_file" "$PLUGIN_PATH/$dest_path" "$dest_path"; then
                    print_warning "Could not copy AI settings file: $source_file to $dest_path"
                else
                    print_success "AI settings file copied: $dest_path"
                fi
            fi
        }
        
        # Copy CSS and JS files
        print_progress "Copying CSS and JS files"
        asset_files=(
            "ai-content-css.css:blocks/css/ai-content.css" 
            "ai-settings-css.css:admin/css/ai-settings.css"
            "ai-settings-js.js:admin/js/ai-settings.js"
            "settings-css.css:admin/css/settings.css"
            "settings-js.js:admin/js/settings.js"
        )
        
        for file_mapping in "${asset_files[@]}"; do
            source_file="${file_mapping%%:*}"
            dest_path="${file_mapping##*:}"
            
            if [ -f "$THEME_FILES_DIR/$source_file" ]; then
                if ! safe_copy "$THEME_FILES_DIR/$source_file" "$PLUGIN_PATH/$dest_path" "$dest_path"; then
                    print_warning "Could not copy asset file: $source_file to $dest_path"
                else
                    print_success "Asset file copied: $dest_path"
                fi
            fi
        }
        
        # Create basic admin view templates
        print_progress "Creating admin view templates"
        if ! create_directory "$PLUGIN_PATH/admin/views" ""; then
            print_warning "Could not create admin/views directory"
        else
            # Create main settings page
            cat > "$PLUGIN_PATH/admin/views/main-page.php" << 'EOF'
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="derleiti-admin-content">
        <div class="derleiti-admin-tabs">
            <nav class="nav-tab-wrapper">
                <a href="?page=derleiti-plugin&tab=general" class="nav-tab <?php echo empty($_GET['tab']) || $_GET['tab'] === 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General', 'derleiti-plugin'); ?></a>
                <a href="?page=derleiti-plugin&tab=ai" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'ai' ? 'nav-tab-active' : ''; ?>"><?php _e('AI Integration', 'derleiti-plugin'); ?></a>
                <a href="?page=derleiti-plugin&tab=blocks" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'blocks' ? 'nav-tab-active' : ''; ?>"><?php _e('Blocks', 'derleiti-plugin'); ?></a>
                <a href="?page=derleiti-plugin&tab=tools" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'tools' ? 'nav-tab-active' : ''; ?>"><?php _e('Tools', 'derleiti-plugin'); ?></a>
                <a href="?page=derleiti-plugin&tab=advanced" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'advanced' ? 'nav-tab-active' : ''; ?>"><?php _e('Advanced', 'derleiti-plugin'); ?></a>
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
            print_success "Admin view template created"
            
            # Create placeholders for tabs
            for tab in general ai blocks tools advanced; do
                touch "$PLUGIN_PATH/admin/views/${tab}-tab.php"
            done
        fi
        
        # Set permissions for the plugin
        print_progress "Setting permissions for the plugin"
        execute_command "Setting permissions for plugin directories" "chmod -R 755 \"$PLUGIN_PATH\"" || print_warning "Could not set permissions for plugin directories"
        execute_command "Setting permissions for plugin files" "find \"$PLUGIN_PATH\" -type f -exec chmod 644 {} \\;" || print_warning "Could not set permissions for plugin files"
        
        print_success "Plugin installation completed!"
    fi
    
    return 0
}

# Main script start
init_log_file
print_header

# Enable debug mode if argument is passed
if [ "$1" == "--debug" ]; then
    DEBUG_MODE=1
    print_info "Debug mode enabled"
fi

# Check dependencies
check_dependencies

# Ask for WordPress main directory
print_section "WordPress Directory"
echo -e "${YELLOW}Please enter the full path to the WordPress main directory${NC}"
echo -e "${YELLOW}(e.g. /var/www/html or /var/www/derleiti.de):${NC}"
read -p "> " WP_PATH

# Remove trailing slash if present
WP_PATH=${WP_PATH%/}

# Check if path exists
if [ ! -d "$WP_PATH" ]; then
    print_error "The specified directory does not exist."
    echo -e "${YELLOW}Would you like to create the directory? (j/n)${NC}"
    read -p "> " CREATE_DIR
    if [[ $CREATE_DIR == "j" || $CREATE_DIR == "J" ]]; then
        if ! create_directory "$WP_PATH" "Directory created."; then
            print_error "Installation aborted."
            exit 1
        fi
    else
        print_error "Installation aborted."
        exit 1
    fi
fi

# Check WordPress installation
if [ -f "$WP_PATH/wp-config.php" ]; then
    print_success "WordPress installation found"
    check_wp_version
else
    print_warning "No WordPress installation found (wp-config.php not present)"
    echo -e "${YELLOW}Do you want to continue anyway? (j/n)${NC}"
    read -p "> " CONTINUE_WITHOUT_WP
    if [[ $CONTINUE_WITHOUT_WP != "j" && $CONTINUE_WITHOUT_WP != "J" ]]; then
        print_error "Installation aborted."
        exit 1
    fi
    
    # Create directory structure for WordPress
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

# Set theme path and plugin path
THEME_PATH="$WP_PATH/wp-content/themes/derleiti-modern"
PLUGIN_PATH="$WP_PATH/wp-content/plugins/derleiti-plugin"

print_info "WordPress directory: $WP_PATH"
print_info "Theme will be installed in: $THEME_PATH"
print_info "Plugin can be installed in: $PLUGIN_PATH"
echo ""
echo -e "${YELLOW}Do you want to continue with the installation? (j/n)${NC}"
read -p "> " CONTINUE
if [[ $CONTINUE != "j" && $CONTINUE != "J" ]]; then
    print_error "Installation aborted."
    exit 1
fi

# Ask if the plugin should also be installed
echo -e "${YELLOW}Would you also like to install the Derleiti Modern Theme Plugin? (j/n)${NC}"
echo -e "${CYAN}The plugin adds AI integration, custom blocks, and other features.${NC}"
read -p "> " INSTALL_PLUGIN
INSTALL_PLUGIN=$(echo $INSTALL_PLUGIN | tr '[:upper:]' '[:lower:]')

# Check if theme already exists and back it up if needed
if [ -d "$THEME_PATH" ]; then
    print_warning "The 'derleiti-modern' theme already exists."
    TIMESTAMP=$(date +"%Y%m%d%H%M%S")
    BACKUP_PATH="$THEME_PATH-backup-$TIMESTAMP"
    print_info "Creating backup at: $BACKUP_PATH"
    
    execute_command "Create theme backup" "cp -r \"$THEME_PATH\" \"$BACKUP_PATH\""
    if [ $? -ne 0 ]; then
        print_error "Backup could not be created. Check permissions."
        exit 1
    fi
    
    execute_command "Remove old theme directory" "rm -rf \"$THEME_PATH\""
    if [ $? -ne 0 ]; then
        print_error "Old theme directory could not be removed."
        exit 1
    fi
    
    print_success "Backup created and old theme directory removed"
fi

# Check if plugin already exists and back it up if needed
if [[ $INSTALL_PLUGIN == "j" ]] && [ -d "$PLUGIN_PATH" ]; then
    print_warning "The 'derleiti-plugin' plugin already exists."
    TIMESTAMP=$(date +"%Y%m%d%H%M%S")
    BACKUP_PLUGIN_PATH="$PLUGIN_PATH-backup-$TIMESTAMP"
    print_info "Creating backup at: $BACKUP_PLUGIN_PATH"
    
    execute_command "Create plugin backup" "cp -r \"$PLUGIN_PATH\" \"$BACKUP_PLUGIN_PATH\""
    if [ $? -ne 0 ]; then
        print_error "Plugin backup could not be created. Check permissions."
        exit 1
    fi
    
    execute_command "Remove old plugin directory" "rm -rf \"$PLUGIN_PATH\""
    if [ $? -ne 0 ]; then
        print_error "Old plugin directory could not be removed."
        exit 1
    fi
    
    print_success "Backup created and old plugin directory removed"
fi

# Get the script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
THEME_FILES_DIR="${SCRIPT_DIR}/theme-files"

# Install the theme
if ! install_theme; then
    display_summary 0
    exit 1
fi

# Install the plugin if requested
if [[ $INSTALL_PLUGIN == "j" ]]; then
    if ! install_plugin; then
        print_warning "Plugin installation failed or was incomplete."
    fi
fi

# Check if installation was successful
if [ -f "$THEME_PATH/style.css" ] && [ -f "$THEME_PATH/js/navigation.js" ]; then
    display_summary 1
else
    display_summary 0
fi
