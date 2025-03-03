<?php
/**
 * Plugin Name: Derleiti Modern Theme Plugin
 * Plugin URI: https://derleiti.de/plugin
 * Description: Erweitert das Derleiti Modern Theme mit zusätzlichen Funktionen wie KI-Integration, erweiterten Blockeditor-Funktionen und Designtools.
 * Version: 1.1.0
 * Author: Derleiti
 * Author URI: https://derleiti.de
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: derleiti-plugin
 * Domain Path: /languages
 * Requires at least: 6.2
 * Tested up to: 6.6
 * Requires PHP: 8.1
 *
 * @package Derleiti_Plugin
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Version definieren
define('DERLEITI_PLUGIN_VERSION', '1.1.0');

// Plugin-Pfad definieren
define('DERLEITI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('DERLEITI_PLUGIN_URL', plugin_dir_url(__FILE__));

// Debug-Modus (nur für Entwicklung)
define('DERLEITI_DEBUG', false);

/**
 * Initialisierung des Plugins
 */
function derleiti_plugin_init(): void {
    // Lade Textdomain für Übersetzungen
    load_plugin_textdomain('derleiti-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Lade Komponenten
    require_once DERLEITI_PLUGIN_PATH . 'includes/class-derleiti-admin.php';
    require_once DERLEITI_PLUGIN_PATH . 'includes/class-derleiti-blocks.php';
    require_once DERLEITI_PLUGIN_PATH . 'includes/class-derleiti-ai-integration.php';
    require_once DERLEITI_PLUGIN_PATH . 'includes/class-derleiti-tools.php';

    // Neue Komponenten in v1.1.0
    if (file_exists(DERLEITI_PLUGIN_PATH . 'includes/class-derleiti-performance.php')) {
        require_once DERLEITI_PLUGIN_PATH . 'includes/class-derleiti-performance.php';
    }

    if (file_exists(DERLEITI_PLUGIN_PATH . 'includes/class-derleiti-analytics.php')) {
        require_once DERLEITI_PLUGIN_PATH . 'includes/class-derleiti-analytics.php';
    }

    if (file_exists(DERLEITI_PLUGIN_PATH . 'includes/class-derleiti-compatibility.php')) {
        require_once DERLEITI_PLUGIN_PATH . 'includes/class-derleiti-compatibility.php';
    }

    // Initialisiere Admin-Klasse
    if (class_exists('Derleiti_Admin')) {
        $admin = new Derleiti_Admin();
        $admin->init();
    }

    // Initialisiere Blöcke
    if (class_exists('Derleiti_Blocks')) {
        $blocks = new Derleiti_Blocks();
        $blocks->init();
    }

    // Initialisiere KI-Integration
    if (class_exists('Derleiti_AI_Integration')) {
        $ai = new Derleiti_AI_Integration();
        $ai->init();
    }

    // Initialisiere Tools
    if (class_exists('Derleiti_Tools')) {
        $tools = new Derleiti_Tools();
        $tools->init();
    }

    // Initialisiere neue Komponenten
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

    // Hooks für Entwicklermodus
    if (DERLEITI_DEBUG) {
        add_action('admin_footer', 'derleiti_debug_info');
        add_action('wp_footer', 'derleiti_debug_info');
    }
}
add_action('plugins_loaded', 'derleiti_plugin_init');

/**
 * Plugin-Aktivierung
 */
function derleiti_plugin_activate(): void {
    // Überprüfe WordPress-Version
    if (version_compare(get_bloginfo('version'), '6.2', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Dieses Plugin erfordert WordPress 6.2 oder höher.', 'derleiti-plugin'));
    }

    // Überprüfe PHP-Version
    if (version_compare(PHP_VERSION, '8.1', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Dieses Plugin erfordert PHP 8.1 oder höher.', 'derleiti-plugin'));
    }

    // Erstelle notwendige DB-Tabellen
    global $wpdb;
    $table_name = $wpdb->prefix . 'derleiti_settings';

    // Überprüfe, ob die Tabelle bereits existiert
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

    // Standardeinstellungen speichern
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

        // Nur einfügen, wenn der Wert noch nicht existiert
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

    // Erstelle Verzeichnisse für Cache
    $cache_dir = WP_CONTENT_DIR . '/cache/derleiti-plugin';
    if (!file_exists($cache_dir)) {
        wp_mkdir_p($cache_dir);
    }

    // Setze Capability für Administratoren
    $role = get_role('administrator');
    if ($role) {
        $role->add_cap('manage_derleiti_plugin');
    }

    // Setze Aktivierungs-Flag für Willkommensnachricht
    set_transient('derleiti_plugin_activated', true, 5);

    // Aktualisiere Permalinks
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'derleiti_plugin_activate');

/**
 * Plugin-Deaktivierung
 */
function derleiti_plugin_deactivate(): void {
    // Lösche temporäre Daten
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

    // Transients leeren
    delete_transient('derleiti_plugin_cache');

    // Alle Plugin-spezifischen Transients löschen
    global $wpdb;
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_derleiti_%'");
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_derleiti_%'");

    // Entferne Capability
    $role = get_role('administrator');
    if ($role) {
        $role->remove_cap('manage_derleiti_plugin');
    }

    // Aktualisiere Permalinks
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'derleiti_plugin_deactivate');

/**
 * Plugin-Deinstallation
 */
function derleiti_plugin_uninstall(): void {
    // Tabellen entfernen, wenn angefordert
    $remove_data = get_option('derleiti_remove_data_on_uninstall', false);

    if ($remove_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';

        // Tabelle löschen
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

        // Optionen löschen
        delete_option('derleiti_remove_data_on_uninstall');

        // Weitere Plugin-spezifische Optionen löschen
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'derleiti_%'");
    }
}
register_uninstall_hook(__FILE__, 'derleiti_plugin_uninstall');

/**
 * Shortcode für Plugin-Features
 */
function derleiti_features_shortcode($atts): string {
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
                esc_html_e('Layout-Builder-Template nicht gefunden', 'derleiti-plugin');
            }
            break;
        case 'ai':
            if (file_exists(DERLEITI_PLUGIN_PATH . 'templates/ai-content.php')) {
                include DERLEITI_PLUGIN_PATH . 'templates/ai-content.php';
            } else {
                esc_html_e('KI-Content-Template nicht gefunden', 'derleiti-plugin');
            }
            break;
        case 'gallery':
            if (file_exists(DERLEITI_PLUGIN_PATH . 'templates/enhanced-gallery.php')) {
                include DERLEITI_PLUGIN_PATH . 'templates/enhanced-gallery.php';
            } else {
                esc_html_e('Gallery-Template nicht gefunden', 'derleiti-plugin');
            }
            break;
        case 'analytics':
            if (file_exists(DERLEITI_PLUGIN_PATH . 'templates/analytics.php')) {
                include DERLEITI_PLUGIN_PATH . 'templates/analytics.php';
            } else {
                esc_html_e('Analytics-Template nicht gefunden', 'derleiti-plugin');
            }
            break;
        default:
            esc_html_e('Feature nicht gefunden', 'derleiti-plugin');
    }

    echo '</div>';

    return ob_get_clean();
}
add_shortcode('derleiti_feature', 'derleiti_features_shortcode');

/**
 * Füge REST API Endpunkte hinzu
 */
function derleiti_plugin_register_rest_routes(): void {
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

    // Neuer Endpunkt für System-Informationen
    register_rest_route('derleiti-plugin/v1', '/system-info', array(
        'methods' => 'GET',
        'callback' => 'derleiti_plugin_get_system_info',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));

    // Neuer Endpunkt für Cache-Löschen
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
}
add_action('rest_api_init', 'derleiti_plugin_register_rest_routes');

/**
 * REST API Callback für Einstellungen abrufen
 */
function derleiti_plugin_get_settings(): array {
    global $wpdb;
    $table_name = $wpdb->prefix . 'derleiti_settings';

    $settings = $wpdb->get_results("SELECT setting_name, setting_value FROM $table_name", ARRAY_A);

    if (!is_array($settings)) {
        return [
            'success' => false,
            'message' => __('Fehler beim Abrufen der Einstellungen', 'derleiti-plugin')
        ];
    }

    $formatted_settings = array();
    foreach ($settings as $setting) {
        $value = $setting['setting_value'];
        if (@unserialize($value) !== false) {
            $value = unserialize($value);
        }
        $formatted_settings[$setting['setting_name']] = $value;
    }

    return [
        'success' => true,
        'data' => $formatted_settings
    ];
}

/**
 * REST API Callback für Einstellungen aktualisieren
 */
function derleiti_plugin_update_settings($request): array {
    global $wpdb;
    $table_name = $wpdb->prefix . 'derleiti_settings';

    $params = $request->get_params();

    // Nonce wurde bereits im permission_callback überprüft

    if (!isset($params['settings']) || !is_array($params['settings']) || empty($params['settings'])) {
        return [
            'success' => false,
            'message' => __('Keine gültigen Einstellungen zum Aktualisieren gefunden', 'derleiti-plugin')
        ];
    }

    $updated = 0;
    $settings = $params['settings'];

    foreach ($settings as $name => $value) {
        // Validiere Einstellungsnamen und -werte
        if (!is_string($name) || empty($name)) {
            continue;
        }

        // Bestimmte Einstellungen spezifisch validieren
        switch ($name) {
            case 'ai_enabled':
            case 'performance_optimization':
            case 'seo_features':
            case 'analytics_integration':
            case 'layout_builder':
                $value = in_array($value, [0, 1, '0', '1', true, false], true) ? (int)$value : 0;
                break;

            case 'dark_mode':
                $valid_modes = ['auto', 'light', 'dark'];
                $value = in_array($value, $valid_modes, true) ? $value : 'auto';
                break;

            case 'ai_provider':
                $valid_providers = ['openai', 'anthropic', 'google', 'custom'];
                $value = in_array($value, $valid_providers, true) ? $value : 'openai';
                break;
        }

        if (is_array($value)) {
            $value = maybe_serialize($value);
        }

        $result = $wpdb->replace(
            $table_name,
            array(
                'setting_name' => sanitize_key($name),
                  'setting_value' => $value,
                  'updated_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );

        if ($result) {
            $updated++;
        }
    }

    if ($updated > 0) {
        // Lösche Cache nach Einstellungsänderungen
        if (function_exists('derleiti_clear_all_caches')) {
            derleiti_clear_all_caches();
        }

        return [
            'success' => true,
            'message' => sprintf(__('%d Einstellungen aktualisiert', 'derleiti-plugin'), $updated)
        ];
    } else {
        return [
            'success' => false,
            'message' => __('Keine Einstellungen aktualisiert', 'derleiti-plugin')
        ];
    }
}

/**
 * REST API Callback für System-Informationen
 */
function derleiti_plugin_get_system_info(): array {
    global $wp_version;

    $server_software = isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : '';

    $theme = wp_get_theme();
    $theme_name = $theme->get('Name');
    $theme_version = $theme->get('Version');

    $plugins = get_plugins();
    $active_plugins = get_option('active_plugins', []);

    $active_plugin_data = [];
    foreach ($active_plugins as $plugin) {
        if (isset($plugins[$plugin])) {
            $plugin_data = $plugins[$plugin];
            $active_plugin_data[] = [
                'name' => $plugin_data['Name'],
                'version' => $plugin_data['Version'],
                'author' => $plugin_data['Author'],
            ];
        }
    }

    $memory_limit = ini_get('memory_limit');
    $max_execution_time = ini_get('max_execution_time');
    $post_max_size = ini_get('post_max_size');
    $upload_max_filesize = ini_get('upload_max_filesize');

    return [
        'success' => true,
        'data' => [
            'wordpress' => [
                'version' => $wp_version,
                'site_url' => get_site_url(),
                'home_url' => get_home_url(),
                'is_multisite' => is_multisite(),
                'language' => get_locale(),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'memory_limit' => $memory_limit,
                'max_execution_time' => $max_execution_time,
                'post_max_size' => $post_max_size,
                'upload_max_filesize' => $upload_max_filesize,
                'extensions' => get_loaded_extensions(),
            ],
            'server' => [
                'software' => $server_software,
                'os' => PHP_OS,
            ],
            'theme' => [
                'name' => $theme_name,
                'version' => $theme_version,
            ],
            'database' => [
                'version' => $GLOBALS['wpdb']->get_var("SELECT VERSION()"),
                'table_prefix' => $GLOBALS['wpdb']->prefix,
            ],
            'active_plugins' => $active_plugin_data,
            'plugin' => [
                'version' => DERLEITI_PLUGIN_VERSION,
                'path' => DERLEITI_PLUGIN_PATH,
                'url' => DERLEITI_PLUGIN_URL,
            ],
        ]
    ];
}

/**
 * REST API Callback für Cache-Löschen
 */
function derleiti_plugin_clear_cache_endpoint($request): array {
    // Nonce wurde bereits im permission_callback überprüft

    if (function_exists('derleiti_clear_all_caches')) {
        $result = derleiti_clear_all_caches();

        if ($result) {
            return [
                'success' => true,
                'message' => __('Cache erfolgreich geleert', 'derleiti-plugin')
            ];
        }
    }

    return [
        'success' => false,
        'message' => __('Fehler beim Leeren des Caches', 'derleiti-plugin')
    ];
}

/**
 * Lösche alle Caches
 */
function derleiti_clear_all_caches(): bool {
    // Plugin-spezifische Caches löschen
    delete_transient('derleiti_plugin_cache');

    // Alle Plugin-spezifischen Transients löschen
    global $wpdb;
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_derleiti_%'");
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_derleiti_%'");

    // Cache-Dateien löschen
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

    return true;
}

/**
 * Willkommensnachricht nach Aktivierung anzeigen
 */
function derleiti_plugin_admin_notices(): void {
    // Prüfe, ob das Plugin gerade aktiviert wurde
    if (get_transient('derleiti_plugin_activated')) {
        delete_transient('derleiti_plugin_activated');
        ?>
        <div class="notice notice-success is-dismissible">
        <h3><?php esc_html_e('Willkommen beim Derleiti Modern Theme Plugin!', 'derleiti-plugin'); ?></h3>
        <p><?php esc_html_e('Vielen Dank, dass Sie sich für unser Plugin entschieden haben. Hier sind einige nützliche Links, um zu beginnen:', 'derleiti-plugin'); ?></p>
        <ul style="list-style-type: disc; padding-left: 20px;">
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=derleiti-plugin')); ?>"><?php esc_html_e('Plugin-Einstellungen', 'derleiti-plugin'); ?></a></li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=derleiti-help')); ?>"><?php esc_html_e('Hilfe und Dokumentation', 'derleiti-plugin'); ?></a></li>
        </ul>
        </div>
        <?php
    }

    // Prüfe auf Plugin-Update-Hinweise
    $current_version = DERLEITI_PLUGIN_VERSION;
    $previous_version = get_option('derleiti_plugin_version', '0.0.0');

    if (version_compare($current_version, $previous_version, '>')) {
        update_option('derleiti_plugin_version', $current_version);

        if (version_compare($previous_version, '0.0.0', '>')) {  // Nur anzeigen, wenn es ein Update ist, nicht bei Neuinstallation
            ?>
            <div class="notice notice-info is-dismissible">
            <p>
            <?php
            echo sprintf(
                esc_html__('Das Derleiti Modern Theme Plugin wurde auf Version %s aktualisiert. <a href="%s">Was ist neu?</a>', 'derleiti-plugin'),
                         esc_html($current_version),
                         esc_url(admin_url('admin.php?page=derleiti-help&tab=changelog'))
            );
            ?>
            </p>
            </div>
            <?php
        }
    }
}
add_action('admin_notices', 'derleiti_plugin_admin_notices');

/**
 * AJAX-Handler für Admin-Benachrichtigungen ausblenden
 */
function derleiti_dismiss_notice() {
    // Sicherheitscheck
    check_ajax_referer('derleiti_dismiss_notice', 'nonce');

    // Überprüfe Berechtigung
    if (!current_user_can('edit_posts')) {
        wp_die();
    }

    // Hole und bereinige die Benachrichtigungs-ID
    $notice_id = isset($_POST['notice']) ? sanitize_key($_POST['notice']) : '';

    if ($notice_id) {
        // Hole bestehende ausgeblendete Benachrichtigungen
        $hidden_notices = get_user_meta(get_current_user_id(), 'derleiti_hidden_notices', true);
        if (!is_array($hidden_notices)) {
            $hidden_notices = array();
        }

        // Füge neue Benachrichtigung hinzu
        $hidden_notices[] = $notice_id;

        // Speichere aktualisierte Liste
        update_user_meta(get_current_user_id(), 'derleiti_hidden_notices', $hidden_notices);
    }

    wp_die();
}
add_action('wp_ajax_derleiti_dismiss_notice', 'derleiti_dismiss_notice');

/**
 * Debug-Informationen anzeigen (nur im Entwicklermodus)
 */
function derleiti_debug_info(): void {
    if (!DERLEITI_DEBUG || !current_user_can('manage_options')) {
        return;
    }

    global $wpdb;

    $memory_usage = round(memory_get_usage() / 1024 / 1024, 2);
    $queries = $wpdb->num_queries;
    $query_time = 0;

    if (defined('SAVEQUERIES') && SAVEQUERIES) {
        foreach ($wpdb->queries as $query) {
            $query_time += $query[1];
        }
        $query_time = round($query_time, 4);
    }

    echo '<div id="derleiti-debug" style="position:fixed; bottom:0; left:0; background:#23282d; color:#fff; padding:10px; font-size:12px; z-index:9999; opacity:0.8;">';
    echo '<h4 style="margin:0; padding:0 0 5px 0; border-bottom:1px solid #ccc;">Derleiti Debug</h4>';
    echo '<p style="margin:5px 0;">Version: ' . esc_html(DERLEITI_PLUGIN_VERSION) . '</p>';
    echo '<p style="margin:5px 0;">Memory: ' . esc_html($memory_usage) . ' MB</p>';
    echo '<p style="margin:5px 0;">Queries: ' . esc_html($queries) . ($query_time ? ' (' . esc_html($query_time) . 's)' : '') . '</p>';
    echo '<p style="margin:5px 0;">PHP: ' . esc_html(PHP_VERSION) . '</p>';
    echo '</div>';
}
