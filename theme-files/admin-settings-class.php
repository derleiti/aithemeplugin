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
                <form method="post" action="options.php">
                    <?php settings_fields('derleiti_performance_settings'); ?>
                    
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
                                    <?php checked(isset($performance_options['browser_caching']) ? $performance_options['browser_caching'] : 0, 1); ?>>
                                <?php _e('Browser-Caching aktivieren', 'derleiti-plugin'); ?>
                            </label>
                            <p class="description"><?php _e('Verwendet Browser-Cache, um wiederholte Besuche zu beschleunigen.', 'derleiti-plugin'); ?></p>
                        </div>
                        
                        <div class="performance-setting">
                            <label>
                                <input type="checkbox" name="derleiti_performance_options[preload_assets]" value="1"
                                    <?php checked(isset($performance_options['preload_assets']) ? $performance_options['preload_assets'] : 0, 1); ?>>
                                <?php _e('Kritische Assets vorladen', 'derleiti-plugin'); ?>
                            </label>
                            <p class="description"><?php _e('Lädt wichtige Ressourcen frühzeitig, um die Ladezeit zu verbessern.', 'derleiti-plugin'); ?></p>
                        </div>
                        
                        <h3><?php _e('Bild-Optimierungen', 'derleiti-plugin'); ?></h3>
                        
                        <div class="performance-setting">
                            <label>
                                <input type="checkbox" name="derleiti_performance_options[webp_conversion]" value="1"
                                    <?php checked(isset($performance_options['webp_conversion']) ? $performance_options['webp_conversion'] : 0, 1); ?>>
                                <?php _e('WebP-Konvertierung', 'derleiti-plugin'); ?>
                            </label>
                            <p class="description"><?php _e('Konvertiert Bilder automatisch ins WebP-Format für bessere Kompression.', 'derleiti-plugin'); ?></p>
                        </div>
                        
                        <div class="performance-setting">
                            <label for="image_quality">
                                <?php _e('Bild-Qualität', 'derleiti-plugin'); ?>
                                <select name="derleiti_performance_options[image_quality]" id="image_quality">
                                    <?php
                                    $quality = isset($performance_options['image_quality']) ? $performance_options['image_quality'] : 85;
                                    for ($i = 60; $i <= 100; $i += 5) {
                                        echo '<option value="' . $i . '" ' . selected($quality, $i, false) . '>' . $i . '%</option>';
                                    }
                                    ?>
                                </select>
                            </label>
                            <p class="description"><?php _e('Qualitätsstufe für Bildkompression. Niedrigere Werte bedeuten kleinere Dateien, aber geringere Qualität.', 'derleiti-plugin'); ?></p>
                        </div>
                    </div>
                    
                    <div class="cache-management-section">
                        <h2><?php _e('Cache-Management', 'derleiti-plugin'); ?></h2>
                        
                        <div class="performance-setting">
                            <label for="cache_lifetime">
                                <?php _e('Cache-Lebenszeit', 'derleiti-plugin'); ?>
                                <select name="derleiti_performance_options[cache_lifetime]" id="cache_lifetime">
                                    <?php
                                    $cache_lifetime = isset($performance_options['cache_lifetime']) ? $performance_options['cache_lifetime'] : 86400;
                                    $lifetimes = [
                                        3600 => __('1 Stunde', 'derleiti-plugin'),
                                        21600 => __('6 Stunden', 'derleiti-plugin'),
                                        43200 => __('12 Stunden', 'derleiti-plugin'),
                                        86400 => __('1 Tag', 'derleiti-plugin'),
                                        604800 => __('1 Woche', 'derleiti-plugin'),
                                    ];
                                    
                                    foreach ($lifetimes as $value => $label) {
                                        echo '<option value="' . $value . '" ' . selected($cache_lifetime, $value, false) . '>' . $label . '</option>';
                                    }
                                    ?>
                                </select>
                            </label>
                            <p class="description"><?php _e('Wie lange sollen Cache-Dateien gespeichert werden, bevor sie erneuert werden.', 'derleiti-plugin'); ?></p>
                        </div>
                        
                        <div class="cache-actions">
                            <button type="button" id="clear-cache-button" class="button button-secondary">
                                <?php _e('Cache leeren', 'derleiti-plugin'); ?>
                            </button>
                            <span class="spinner" style="float: none; margin-top: 0;"></span>
                            <div id="cache-notice" style="display: inline-block; margin-left: 10px;"></div>
                        </div>
                    </div>
                    
                    <div class="advanced-performance-section">
                        <h2><?php _e('Erweiterte Einstellungen', 'derleiti-plugin'); ?></h2>
                        
                        <div class="performance-setting">
                            <label>
                                <input type="checkbox" name="derleiti_performance_options[disable_jquery_migrate]" value="1"
                                    <?php checked(isset($performance_options['disable_jquery_migrate']) ? $performance_options['disable_jquery_migrate'] : 0, 1); ?>>
                                <?php _e('jQuery Migrate deaktivieren', 'derleiti-plugin'); ?>
                            </label>
                            <p class="description"><?php _e('Deaktiviert jQuery Migrate, was die Ladezeit verbessern kann. Vorsicht: Kann Probleme mit älteren Plugins verursachen.', 'derleiti-plugin'); ?></p>
                        </div>
                        
                        <div class="performance-setting">
                            <label>
                                <input type="checkbox" name="derleiti_performance_options[defer_js]" value="1"
                                    <?php checked(isset($performance_options['defer_js']) ? $performance_options['defer_js'] : 0, 1); ?>>
                                <?php _e('JavaScript verzögert laden', 'derleiti-plugin'); ?>
                            </label>
                            <p class="description"><?php _e('Lädt JavaScript-Dateien verzögert, um die anfängliche Ladezeit zu verbessern.', 'derleiti-plugin'); ?></p>
                        </div>
                    </div>
                    
                    <?php submit_button(__('Einstellungen speichern', 'derleiti-plugin')); ?>
                </form>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#clear-cache-button').on('click', function() {
                var $button = $(this);
                var $spinner = $button.next('.spinner');
                var $notice = $('#cache-notice');
                
                $button.prop('disabled', true);
                $spinner.css('visibility', 'visible');
                $notice.html('').hide();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'derleiti_clear_cache',
                        nonce: '<?php echo wp_create_nonce('derleiti_clear_cache_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $notice.html('<span style="color: green;">' + response.data.message + '</span>').show();
                        } else {
                            $notice.html('<span style="color: red;">' + response.data.message + '</span>').show();
                        }
                    },
                    error: function() {
                        $notice.html('<span style="color: red;"><?php _e('Fehler beim Leeren des Caches.', 'derleiti-plugin'); ?></span>').show();
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                        $spinner.css('visibility', 'hidden');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render der WordPress-Erweitert-Einstellungsseite
     */
    public function render_wordpress_settings_page() {
        // Implementation für WordPress erweiterte Einstellungen
        $wordpress_options = get_option('derleiti_wordpress_options', []);
        ?>
        <div class="wrap derleiti-wordpress-settings-page">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('derleiti_wordpress_settings');
                ?>
                
                <div class="wordpress-settings-container">
                    <h2><?php _e('WordPress Core Anpassungen', 'derleiti-plugin'); ?></h2>
                    
                    <div class="wordpress-setting">
                        <label>
                            <input type="checkbox" name="derleiti_wordpress_options[disable_comments]" value="1"
                                <?php checked(isset($wordpress_options['disable_comments']) ? $wordpress_options['disable_comments'] : 0, 1); ?>>
                            <?php _e('Kommentare deaktivieren', 'derleiti-plugin'); ?>
                        </label>
                        <p class="description"><?php _e('Deaktiviert die WordPress-Kommentarfunktion komplett.', 'derleiti-plugin'); ?></p>
                    </div>
                    
                    <div class="wordpress-setting">
                        <label>
                            <input type="checkbox" name="derleiti_wordpress_options[disable_xmlrpc]" value="1"
                                <?php checked(isset($wordpress_options['disable_xmlrpc']) ? $wordpress_options['disable_xmlrpc'] : 0, 1); ?>>
                            <?php _e('XML-RPC deaktivieren', 'derleiti-plugin'); ?>
                        </label>
                        <p class="description"><?php _e('Deaktiviert die XML-RPC-Funktionalität für verbesserte Sicherheit.', 'derleiti-plugin'); ?></p>
                    </div>
                    
                    <div class="wordpress-setting">
                        <label>
                            <input type="checkbox" name="derleiti_wordpress_options[remove_wp_version]" value="1"
                                <?php checked(isset($wordpress_options['remove_wp_version']) ? $wordpress_options['remove_wp_version'] : 0, 1); ?>>
                            <?php _e('WordPress-Version ausblenden', 'derleiti-plugin'); ?>
                        </label>
                        <p class="description"><?php _e('Entfernt die WordPress-Versionsnummer aus dem HTML und RSS-Feeds.', 'derleiti-plugin'); ?></p>
                    </div>
                    
                    <div class="wordpress-setting">
                        <label>
                            <input type="checkbox" name="derleiti_wordpress_options[disable_self_pingbacks]" value="1"
                                <?php checked(isset($wordpress_options['disable_self_pingbacks']) ? $wordpress_options['disable_self_pingbacks'] : 0, 1); ?>>
                            <?php _e('Selbst-Pingbacks deaktivieren', 'derleiti-plugin'); ?>
                        </label>
                        <p class="description"><?php _e('Verhindert, dass WordPress Pingbacks an sich selbst sendet.', 'derleiti-plugin'); ?></p>
                    </div>
                    
                    <h2><?php _e('WordPress Medien Einstellungen', 'derleiti-plugin'); ?></h2>
                    
                    <div class="wordpress-setting">
                        <label for="image_sizes">
                            <?php _e('Bild-Größen verwalten', 'derleiti-plugin'); ?>
                        </label>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><?php _e('Thumbnail', 'derleiti-plugin'); ?></th>
                                <td>
                                    <label for="thumbnail_width"><?php _e('Breite:', 'derleiti-plugin'); ?></label>
                                    <input type="number" min="0" step="1" id="thumbnail_width" name="derleiti_wordpress_options[thumbnail_width]" value="<?php echo isset($wordpress_options['thumbnail_width']) ? intval($wordpress_options['thumbnail_width']) : 150; ?>">
                                    <label for="thumbnail_height"><?php _e('Höhe:', 'derleiti-plugin'); ?></label>
                                    <input type="number" min="0" step="1" id="thumbnail_height" name="derleiti_wordpress_options[thumbnail_height]" value="<?php echo isset($wordpress_options['thumbnail_height']) ? intval($wordpress_options['thumbnail_height']) : 150; ?>">
                                    <label>
                                        <input type="checkbox" name="derleiti_wordpress_options[thumbnail_crop]" value="1"
                                            <?php checked(isset($wordpress_options['thumbnail_crop']) ? $wordpress_options['thumbnail_crop'] : 1, 1); ?>>
                                        <?php _e('Crop', 'derleiti-plugin'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Medium', 'derleiti-plugin'); ?></th>
                                <td>
                                    <label for="medium_width"><?php _e('Breite:', 'derleiti-plugin'); ?></label>
                                    <input type="number" min="0" step="1" id="medium_width" name="derleiti_wordpress_options[medium_width]" value="<?php echo isset($wordpress_options['medium_width']) ? intval($wordpress_options['medium_width']) : 300; ?>">
                                    <label for="medium_height"><?php _e('Höhe:', 'derleiti-plugin'); ?></label>
                                    <input type="number" min="0" step="1" id="medium_height" name="derleiti_wordpress_options[medium_height]" value="<?php echo isset($wordpress_options['medium_height']) ? intval($wordpress_options['medium_height']) : 300; ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Large', 'derleiti-plugin'); ?></th>
                                <td>
                                    <label for="large_width"><?php _e('Breite:', 'derleiti-plugin'); ?></label>
                                    <input type="number" min="0" step="1" id="large_width" name="derleiti_wordpress_options[large_width]" value="<?php echo isset($wordpress_options['large_width']) ? intval($wordpress_options['large_width']) : 1024; ?>">
                                    <label for="large_height"><?php _e('Höhe:', 'derleiti-plugin'); ?></label>
                                    <input type="number" min="0" step="1" id="large_height" name="derleiti_wordpress_options[large_height]" value="<?php echo isset($wordpress_options['large_height']) ? intval($wordpress_options['large_height']) : 1024; ?>">
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <h2><?php _e('WordPress Sicherheit', 'derleiti-plugin'); ?></h2>
                    
                    <div class="wordpress-setting">
                        <label>
                            <input type="checkbox" name="derleiti_wordpress_options[login_protection]" value="1"
                                <?php checked(isset($wordpress_options['login_protection']) ? $wordpress_options['login_protection'] : 0, 1); ?>>
                            <?php _e('Login-Schutz aktivieren', 'derleiti-plugin'); ?>
                        </label>
                        <p class="description"><?php _e('Begrenzt fehlgeschlagene Login-Versuche und schützt vor Brute-Force-Angriffen.', 'derleiti-plugin'); ?></p>
                    </div>
                    
                    <div class="wordpress-setting">
                        <label>
                            <input type="checkbox" name="derleiti_wordpress_options[file_editing]" value="1"
                                <?php checked(isset($wordpress_options['file_editing']) ? $wordpress_options['file_editing'] : 0, 1); ?>>
                            <?php _e('Theme- und Plugin-Editor deaktivieren', 'derleiti-plugin'); ?>
                        </label>
                        <p class="description"><?php _e('Deaktiviert den eingebauten Theme- und Plugin-Editor für verbesserte Sicherheit.', 'derleiti-plugin'); ?></p>
                    </div>
                </div>
                
                <?php submit_button(__('WordPress-Einstellungen speichern', 'derleiti-plugin')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Sanitize Haupteinstellungen
     */
    public function sanitize_main_settings($input) {
        // Implementation für Validierung und Bereinigung der Haupteinstellungen
        return $input;
    }
    
    /**
     * Sanitize Design-Einstellungen
     */
    public function sanitize_design_settings($input) {
        // Implementation für Validierung und Bereinigung der Design-Einstellungen
        return $input;
    }
    
    /**
     * Sanitize Menü-Einstellungen
     */
    public function sanitize_menu_settings($input) {
        // Implementation für Validierung und Bereinigung der Menü-Einstellungen
        return $input;
    }
    
    /**
     * Sanitize Performance-Einstellungen
     */
    public function sanitize_performance_settings($input) {
        // Implementation für Validierung und Bereinigung der Performance-Einstellungen
        return $input;
    }
    
    /**
     * Sanitize WordPress-Einstellungen
     */
    public function sanitize_wordpress_settings($input) {
        // Implementation für Validierung und Bereinigung der WordPress-Einstellungen
        return $input;
    }
    
    /**
     * Enqueue settings assets
     */
    public function enqueue_settings_assets($hook) {
        // Prüfe, ob wir auf einer der Plugin-Einstellungsseiten sind
        if (strpos($hook, 'derleiti') === false) {
            return;
        }
        
        // Enqueue admin styles
        wp_enqueue_style(
            'derleiti-admin-settings',
            DERLEITI_PLUGIN_URL . 'admin/css/settings.css',
            array(),
            DERLEITI_PLUGIN_VERSION
        );
        
        // Enqueue admin scripts
        wp_enqueue_script(
            'derleiti-admin-settings',
            DERLEITI_PLUGIN_URL . 'admin/js/settings.js',
            array('jquery', 'jquery-ui-sortable'),
            DERLEITI_PLUGIN_VERSION,
            true
        );
        
        // Load media uploader
        wp_enqueue_media();
        
        // Load color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Localize script
        wp_localize_script(
            'derleiti-admin-settings',
            'derleitiSettings',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('derleiti_settings_nonce'),
                'strings' => array(
                    'saveSuccess' => __('Einstellungen gespeichert!', 'derleiti-plugin'),
                    'saveError' => __('Fehler beim Speichern der Einstellungen.', 'derleiti-plugin'),
                    'confirmReset' => __('Sind Sie sicher, dass Sie alle Einstellungen zurücksetzen möchten?', 'derleiti-plugin'),
                    'confirmDelete' => __('Sind Sie sicher, dass Sie diesen Eintrag löschen möchten?', 'derleiti-plugin'),
                    'processing' => __('Verarbeite...', 'derleiti-plugin'),
                )
            )
        );
    }
    
    /**
     * AJAX handler für Menü-Einstellungen update
     */
    public function update_menu_settings() {
        // Check nonce
        check_ajax_referer('derleiti_settings_nonce', 'nonce');
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung.', 'derleiti-plugin')));
        }
        
        // Get and sanitize data
        $menu_options = isset($_POST['menu_options']) ? $_POST['menu_options'] : array();
        
        // Sanitize and save options
        update_option('derleiti_menu_options', $menu_options);
        
        wp_send_json_success(array(
            'message' => __('Menü-Einstellungen gespeichert!', 'derleiti-plugin')
        ));
    }
    
    /**
     * AJAX handler für Einstellungen zurücksetzen
     */
    public function reset_settings() {
        // Check nonce
        check_ajax_referer('derleiti_settings_nonce', 'nonce');
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung.', 'derleiti-plugin')));
        }
        
        // Get settings type
        $settings_type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        
        // Reset based on type
        switch ($settings_type) {
            case 'main':
                delete_option('derleiti_main_options');
                break;
            case 'design':
                delete_option('derleiti_design_options');
                break;
            case 'menu':
                delete_option('derleiti_menu_options');
                break;
            case 'performance':
                delete_option('derleiti_performance_options');
                break;
            case 'wordpress':
                delete_option('derleiti_wordpress_options');
                break;
            case 'all':
                delete_option('derleiti_main_options');
                delete_option('derleiti_design_options');
                delete_option('derleiti_menu_options');
                delete_option('derleiti_performance_options');
                delete_option('derleiti_wordpress_options');
                break;
            default:
                wp_send_json_error(array('message' => __('Ungültiger Einstellungstyp.', 'derleiti-plugin')));
                break;
        }
        
        wp_send_json_success(array(
            'message' => __('Einstellungen wurden zurückgesetzt!', 'derleiti-plugin')
        ));
    }
}
