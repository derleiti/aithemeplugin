<?php
/**
 * Erweiterte Plugin-Einstellungsverwaltung für Derleiti Modern Theme
 *
 * @package Derleiti_Plugin
 * @subpackage Admin
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

class Derleiti_Admin_Settings {
    /**
     * Initialisiere Einstellungsseiten und -optionen
     */
    public function init() {
        // Registriere Einstellungsbereiche
        add_action('admin_init', [$this, 'register_settings']);
        
        // Füge Einstellungsseite zum Menü hinzu
        add_action('admin_menu', [$this, 'add_settings_page']);
        
        // Enqueue Skripte und Styles für Einstellungsseite
        add_action('admin_enqueue_scripts', [$this, 'enqueue_settings_assets']);
        
        // AJAX-Handler für dynamische Einstellungsaktionen
        add_action('wp_ajax_derleiti_update_menu_settings', [$this, 'update_menu_settings']);
        add_action('wp_ajax_derleiti_reset_settings', [$this, 'reset_settings']);
    }
    
    /**
     * Registriere Einstellungsbereiche
     */
    public function register_settings() {
        // Haupteinstellungsgruppe
        register_setting('derleiti_main_settings', 'derleiti_main_options', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_main_settings']
        ]);
        
        // Design-Einstellungen
        register_setting('derleiti_design_settings', 'derleiti_design_options', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_design_settings']
        ]);
        
        // Menü-Einstellungen
        register_setting('derleiti_menu_settings', 'derleiti_menu_options', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_menu_settings']
        ]);
        
        // Performance-Einstellungen
        register_setting('derleiti_performance_settings', 'derleiti_performance_options', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_performance_settings']
        ]);
        
        // Erweiterte WordPress-Einstellungen
        register_setting('derleiti_wordpress_settings', 'derleiti_wordpress_options', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_wordpress_settings']
        ]);
    }
    
    /**
     * Füge Einstellungsseite zum WordPress-Menü hinzu
     */
    public function add_settings_page() {
        add_menu_page(
            __('Derleiti Theme', 'derleiti-plugin'),
            __('Derleiti Theme', 'derleiti-plugin'),
            'manage_options',
            'derleiti-settings',
            [$this, 'render_settings_page'],
            'dashicons-admin-generic',
            30
        );
        
        // Untermenüs für verschiedene Einstellungsbereiche
        add_submenu_page(
            'derleiti-settings',
            __('Haupteinstellungen', 'derleiti-plugin'),
            __('Haupteinstellungen', 'derleiti-plugin'),
            'manage_options',
            'derleiti-settings',
            [$this, 'render_settings_page']
        );
        
        add_submenu_page(
            'derleiti-settings',
            __('Design', 'derleiti-plugin'),
            __('Design', 'derleiti-plugin'),
            'manage_options',
            'derleiti-design-settings',
            [$this, 'render_design_settings_page']
        );
        
        add_submenu_page(
            'derleiti-settings',
            __('Menü-Einstellungen', 'derleiti-plugin'),
            __('Menü-Einstellungen', 'derleiti-plugin'),
            'manage_options',
            'derleiti-menu-settings',
            [$this, 'render_menu_settings_page']
        );
        
        add_submenu_page(
            'derleiti-settings',
            __('Performance', 'derleiti-plugin'),
            __('Performance', 'derleiti-plugin'),
            'manage_options',
            'derleiti-performance-settings',
            [$this, 'render_performance_settings_page']
        );
        
        add_submenu_page(
            'derleiti-settings',
            __('WordPress Erweitert', 'derleiti-plugin'),
            __('WordPress Erweitert', 'derleiti-plugin'),
            'manage_options',
            'derleiti-wordpress-settings',
            [$this, 'render_wordpress_settings_page']
        );
    }
    
    /**
     * Render der Haupteinstellungsseite
     */
    public function render_settings_page() {
        ?>
        <div class="wrap derleiti-settings-page">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('derleiti_main_settings');
                do_settings_sections('derleiti_main_settings');
                submit_button(__('Einstellungen speichern', 'derleiti-plugin'));
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render der Design-Einstellungsseite
     */
    public function render_design_settings_page() {
        ?>
        <div class="wrap derleiti-design-settings-page">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('derleiti_design_settings');
                do_settings_sections('derleiti_design_settings');
                submit_button(__('Design-Einstellungen speichern', 'derleiti-plugin'));
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render der Menü-Einstellungsseite
     */
    public function render_menu_settings_page() {
        $nav_menus = wp_get_nav_menus();
        $current_menu_options = get_option('derleiti_menu_options', []);
        ?>
        <div class="wrap derleiti-menu-settings-page">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="derleiti-menu-settings-container">
                <h2><?php _e('Menü-Anpassungen', 'derleiti-plugin'); ?></h2>
                
                <div class="derleiti-menu-settings-grid">
                    <div class="derleiti-menu-settings-column">
                        <h3><?php _e('Verfügbare Menüs', 'derleiti-plugin'); ?></h3>
                        <select id="derleiti-main-menu" name="derleiti_menu_options[main_menu]">
                            <option value=""><?php _e('Standard-Menü wählen', 'derleiti-plugin'); ?></option>
                            <?php 
                            foreach ($nav_menus as $menu) {
                                echo '<option value="' . esc_attr($menu->term_id) . '" ' . 
                                     selected(isset($current_menu_options['main_menu']) ? $current_menu_options['main_menu'] : '', $menu->term_id, false) . '>' . 
                                     esc_html($menu->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="derleiti-menu-settings-column">
                        <h3><?php _e('Menü-Darstellung', 'derleiti-plugin'); ?></h3>
                        <label>
                            <input type="checkbox" name="derleiti_menu_options[dropdown_enabled]" value="1" 
                                <?php checked(isset($current_menu_options['dropdown_enabled']) ? $current_menu_options['dropdown_enabled'] : 0, 1); ?>>
                            <?php _e('Dropdown-Menüs aktivieren', 'derleiti-plugin'); ?>
                        </label>
                        
                        <label>
                            <input type="checkbox" name="derleiti_menu_options[mobile_hamburger]" value="1" 
                                <?php checked(isset($current_menu_options['mobile_hamburger']) ? $current_menu_options['mobile_hamburger'] : 0, 1); ?>>
                            <?php _e('Hamburger-Menü für mobile Geräte', 'derleiti-plugin'); ?>
                        </label>
                    </div>
                </div>
                
                <div class="derleiti-menu-custom-links">
                    <h3><?php _e('Benutzerdefinierte Menülinks', 'derleiti-plugin'); ?></h3>
                    <div id="custom-menu-links-container">
                        <?php
                        // Zeige vorhandene benutzerdefinierte Links
                        $custom_links = isset($current_menu_options['custom_links']) ? $current_menu_options['custom_links'] : [];
                        foreach ($custom_links as $index => $link) {
                            ?>
                            <div class="custom-menu-link">
                                <input type="text" name="derleiti_menu_options[custom_links][<?php echo esc_attr($index); ?>][label]" 
                                       placeholder="<?php _e('Link-Beschriftung', 'derleiti-plugin'); ?>" 
                                       value="<?php echo esc_attr($link['label']); ?>">
                                <input type="url" name="derleiti_menu_options[custom_links][<?php echo esc_attr($index); ?>][url]" 
                                       placeholder="<?php _e('Link-URL', 'derleiti-plugin'); ?>" 
                                       value="<?php echo esc_url($link['url']); ?>">
                                <button type="button" class="button remove-custom-link"><?php _e('Entfernen', 'derleiti-plugin'); ?></button>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <button type="button" id="add-custom-link" class="button"><?php _e('Benutzerdefinierten Link hinzufügen', 'derleiti-plugin'); ?></button>
                </div>
                
                <div class="derleiti-menu-advanced-settings">
                    <h3><?php _e('Erweiterte Menü-Einstellungen', 'derleiti-plugin'); ?></h3>
                    <label>
                        <input type="checkbox" name="derleiti_menu_options[sticky_menu]" value="1" 
                            <?php checked(isset($current_menu_options['sticky_menu']) ? $current_menu_options['sticky_menu'] : 0, 1); ?>>
                        <?php _e('Menü beim Scrollen fixieren', 'derleiti-plugin'); ?>
                    </label>
                    
                    <label><?php _e('Menütiefe', 'derleiti-plugin'); ?>
                        <select name="derleiti_menu_options[menu_depth]">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                echo '<option value="' . esc_attr($i) . '" ' . 
                                     selected(isset($current_menu_options['menu_depth']) ? $current_menu_options['menu_depth'] : 2, $i, false) . '>' . 
                                     sprintf(__('%d Ebenen', 'derleiti-plugin'), $i) . '</option>';
                            }
                            ?>
                        </select>
                    </label>
                </div>
                
                <div class="derleiti-menu-save-actions">
                    <?php submit_button(__('Menü-Einstellungen speichern', 'derleiti-plugin'), 'primary', 'submit', false); ?>
                    <button type="button" id="reset-menu-settings" class="button button-secondary"><?php _e('Zurücksetzen', 'derleiti-plugin'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render der Performance-Einstellungsseite
     */
    public function render_performance_settings_page() {
        $performance_options = get_option('derleiti_performance_options', []);
        ?>
        <div class="wrap derleiti-performance-settings-page">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="derleiti-performance-settings-container">
                <div class="performance-optimization-section">
                    <h2><?php _e('Performance-Optimierungen', 'derleiti-plugin'); ?></h2>
                    
                    <div class="performance-setting">
                        <label>
                            <input type="checkbox" name="derleiti_performance_options[lazy_load_images]" value="1"
                                <?php checked(isset($performance_options['lazy_load_images']) ? $performance_options['lazy_load_images'] : 0, 1); ?>>
                            <?php _e('Lazy Loading für Bilder aktivieren', 'derleiti-plugin'); ?>
                        </label>
                        <p class="description"><?php _e('Verbessert die Seitenlade-Geschwindigkeit, indem Bilder erst geladen werden, wenn sie sichtbar sind.', 'derleiti-plugin'); ?></p>
                    </div>
                    
                    <div class="performance-setting">
                        <label>
                            <input type="checkbox" name="derleiti_performance_options[minify_assets]" value="1"
                                <?php checked(isset($performance_options['minify_assets']) ? $performance_options['minify_assets'] : 0, 1); ?>>
                            <?php _e('CSS und JavaScript minifizieren', 'derleiti-plugin'); ?>
                        </label>
                        <p class="description"><?php _e('Reduziert die Größe von CSS- und JavaScript-Dateien für schnelleres Laden.', 'derleiti-plugin'); ?></p>
                    </div>
                    
                    <div class="performance-setting">
                        <label>
                            <input type="checkbox" name="derleiti_performance_options[browser_caching]" value="1"
                                <?php checked(isset($performance_options['browser_caching']) ? $performance_options['browser