<?php
/**
 * Plugin Name: Derleiti Modern Theme Plugin
 * Plugin URI: https://derleiti.de/plugin
 * Description: Erweitert das Derleiti Modern Theme mit zusätzlichen Funktionen wie KI-Integration, erweiterten Blockeditor-Funktionen und Designtools.
 * Version: 1.2.0
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
defined('ABSPATH') || exit;

// Definiere Konstanten mit erweiterten Sicherheitsüberprüfungen
if (!defined('DERLEITI_PLUGIN_VERSION')) {
    define('DERLEITI_PLUGIN_VERSION', '1.2.0');
}

// Sicherheits- und Leistungskonstanten
if (!defined('DERLEITI_PLUGIN_PATH')) {
    define('DERLEITI_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

if (!defined('DERLEITI_PLUGIN_URL')) {
    define('DERLEITI_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Sicherheits- und Debugging-Modus
if (!defined('DERLEITI_DEBUG')) {
    define('DERLEITI_DEBUG', false);
}

/**
 * Haupt-Plugin-Klasse mit verbesserte Sicherheit und Fehlerbehandlung
 */
class Derleiti_Plugin {
    /**
     * Statische Instanz für Singleton-Muster
     * @var Derleiti_Plugin
     */
    private static $instance = null;

    /**
     * Liste der erforderlichen Komponenten
     * @var array
     */
    private $required_components = [
        'admin' => 'class-derleiti-admin.php',
        'blocks' => 'class-derleiti-blocks.php',
        'ai' => 'class-derleiti-ai-integration.php',
        'tools' => 'class-derleiti-tools.php',
    ];

    /**
     * Logging-Verzeichnis
     */
    private const LOG_DIRECTORY = WP_CONTENT_DIR . '/derleiti-logs/plugin/';

    /**
     * Singleton-Instanz abrufen
     * @return Derleiti_Plugin
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Privater Konstruktor für Singleton-Muster
     */
    private function __construct() {
        // Verhindere Klonen
        add_action('plugins_loaded', [$this, 'init_plugin']);
    }

    /**
     * Sichere Initialisierung des Plugins
     */
    public function init_plugin() {
        // Sicherheitsüberprüfungen
        if (!$this->check_system_requirements()) {
            return;
        }

        // Laden der Sprachdateien
        $this->load_plugin_textdomain();

        // Sicheres Laden von Komponenten
        $this->load_components();

        // Registriere Hooks
        $this->register_hooks();

        // Debug-Funktionen nur im Entwicklungsmodus
        if (DERLEITI_DEBUG) {
            $this->setup_debug_features();
        }
    }

    /**
     * Systemanforderungen überprüfen
     * @return bool
     */
    private function check_system_requirements() {
        // WordPress-Version-Check
        global $wp_version;
        $min_wp_version = '6.2';
        $min_php_version = '8.1';

        if (version_compare($wp_version, $min_wp_version, '<')) {
            $this->log_error(sprintf(
                'Inkompatible WordPress-Version. Erforderlich: %s, Aktuell: %s', 
                $min_wp_version, 
                $wp_version
            ));
            
            add_action('admin_notices', function() use ($min_wp_version) {
                printf(
                    '<div class="notice notice-error"><p>%s</p></div>', 
                    sprintf(
                        __('Das Derleiti Plugin benötigt WordPress %s oder höher.', 'derleiti-plugin'), 
                        $min_wp_version
                    )
                );
            });
            return false;
        }

        if (version_compare(PHP_VERSION, $min_php_version, '<')) {
            $this->log_error(sprintf(
                'Inkompatible PHP-Version. Erforderlich: %s, Aktuell: %s', 
                $min_php_version, 
                PHP_VERSION
            ));
            
            add_action('admin_notices', function() use ($min_php_version) {
                printf(
                    '<div class="notice notice-error"><p>%s</p></div>', 
                    sprintf(
                        __('Das Derleiti Plugin benötigt PHP %s oder höher.', 'derleiti-plugin'), 
                        $min_php_version
                    )
                );
            });
            return false;
        }

        return true;
    }

    /**
     * Komponenten sicher laden
     */
    private function load_components() {
        foreach ($this->required_components as $component => $file) {
            $filepath = DERLEITI_PLUGIN_PATH . "includes/{$file}";
            
            if (!file_exists($filepath)) {
                $this->log_error("Komponente nicht gefunden: {$component}");
                continue;
            }

            try {
                require_once $filepath;
                
                // Dynamische Klasseninitialisierung
                $class_name = 'Derleiti_' . ucfirst($component);
                if (class_exists($class_name)) {
                    $instance = new $class_name();
                    if (method_exists($instance, 'init')) {
                        $instance->init();
                    }
                }
            } catch (Exception $e) {
                $this->log_error("Fehler beim Laden der Komponente {$component}: " . $e->getMessage());
            }
        }
    }

    /**
     * Hooks und Aktionen registrieren
     */
    private function register_hooks() {
        // Plugin-Aktivierung
        register_activation_hook(__FILE__, [$this, 'activate_plugin']);

        // Plugin-Deaktivierung
        register_deactivation_hook(__FILE__, [$this, 'deactivate_plugin']);

        // Shortcodes registrieren
        add_action('init', [$this, 'register_shortcodes']);
    }

    /**
     * Sprachdateien laden
     */
    private function load_plugin_textdomain() {
        load_plugin_textdomain(
            'derleiti-plugin', 
            false, 
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    /**
     * Plugin-Aktivierung mit erweiterten Sicherheitsmaßnahmen
     */
    public function activate_plugin() {
        // Überprüfe Benutzerberechtigungen
        if (!current_user_can('activate_plugins')) {
            $this->log_error('Aktivierungsversuch ohne ausreichende Berechtigungen');
            return;
        }

        // Erstelle erforderliche Verzeichnisse
        $this->create_plugin_directories();

        // Initialisiere Datenbanktabellen
        $this->initialize_database_tables();

        // Setze Standardeinstellungen
        $this->set_default_settings();

        // Aktualisiere Permalinks
        flush_rewrite_rules();

        // Logge Aktivierung
        $this->log_event('Plugin aktiviert');
    }

    /**
     * Plugin-Deaktivierung
     */
    public function deactivate_plugin() {
        // Überprüfe Benutzerberechtigungen
        if (!current_user_can('activate_plugins')) {
            $this->log_error('Deaktivierungsversuch ohne ausreichende Berechtigungen');
            return;
        }

        // Temporäre Daten und Caches bereinigen
        $this->cleanup_temporary_data();

        // Logge Deaktivierung
        $this->log_event('Plugin deaktiviert');
    }

    /**
     * Debug-Funktionen einrichten
     */
    private function setup_debug_features() {
        // Zusätzliche Logging-Mechanismen
        add_action('admin_footer', [$this, 'debug_info']);
        add_action('wp_footer', [$this, 'debug_info']);
    }

    /**
     * Debug-Informationen anzeigen
     */
    public function debug_info() {
        if (!DERLEITI_DEBUG || !current_user_can('manage_options')) {
            return;
        }

        global $wpdb;
        $memory_usage = round(memory_get_usage() / 1024 / 1024, 2);

        echo '<div style="position:fixed;bottom:0;left:0;background:#23282d;color:#fff;padding:10px;z-index:9999;">';
        echo '<h4>Derleiti Plugin Debug</h4>';
        echo '<p>Version: ' . esc_html(DERLEITI_PLUGIN_VERSION) . '</p>';
        echo '<p>Memory: ' . esc_html($memory_usage) . ' MB</p>';
        echo '<p>Queries: ' . esc_html($wpdb->num_queries) . '</p>';
        echo '</div>';
    }

    /**
     * Erforderliche Verzeichnisse erstellen
     */
    private function create_plugin_directories() {
        $dirs = [
            self::LOG_DIRECTORY,
            WP_CONTENT_DIR . '/cache/derleiti-plugin/',
        ];

        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                
                // Sicherheits-.htaccess
                $htaccess_path = $dir . '.htaccess';
                if (!file_exists($htaccess_path)) {
                    file_put_contents($htaccess_path, "Deny from all\n");
                }
            }
        }
    }

    /**
     * Datenbanktabellen initialisieren
     */
    private function initialize_database_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = [
            'derleiti_settings' => "CREATE TABLE {$wpdb->prefix}derleiti_settings (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                setting_name varchar(255) NOT NULL,
                setting_value longtext NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                UNIQUE KEY setting_name (setting_name)
            ) $charset_collate;"
        ];

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($tables as $table_name => $sql) {
            dbDelta($sql);
        }
    }

    /**
     * Standardeinstellungen setzen
     */
    private function set_default_settings() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';
        
        $default_settings = [
            'plugin_version' => DERLEITI_PLUGIN_VERSION,
            'installation_date' => current_time('mysql'),
            'ai_enabled' => 0,
            'performance_optimization' => 1,
            'debug_mode' => 0,
        ];

        foreach ($default_settings as $name => $value) {
            $wpdb->replace(
                $table_name,
                [
                    'setting_name' => $name,
                    'setting_value' => is_array($value) ? serialize($value) : $value,
                    'updated_at' => current_time('mysql')
                ],
                ['%s', '%s', '%s']
            );
        }
    }

    /**
     * Temporäre Daten bereinigen
     */
    private function cleanup_temporary_data() {
        global $wpdb;
        
        // Lösche Plugin-spezifische Transients
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_derleiti_%'");
        
        // Bereinige Cache-Verzeichnis
        $cache_dir = WP_CONTENT_DIR . '/cache/derleiti-plugin/';
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }

    /**
     * Shortcodes registrieren
     */
    public function register_shortcodes() {
        $shortcodes = [
            'derleiti_feature' => 'features_shortcode',
            'derleiti_ai' => 'ai_shortcode',
            'derleiti_button' => 'button_shortcode',
        ];

        foreach ($shortcodes as $tag => $method) {
            add_shortcode($tag, [$this, $method]);
        }
    }

    /**
     * Ereignis protokollieren
     * @param string $message Protokollnachricht
     * @param string $level Protokollebene
     */
    private function log_event($message, $level = 'info') {
        $log_file = self::LOG_DIRECTORY . $level . '_' . date('Y-m-d') . '.log';
        
        $log_entry = sprintf(
            "[%s] [%s] %s\n", 
            current_time('mysql'), 
            strtoupper($level), 
            $message
        );
        
        error_log($log_entry, 3, $log_file);
    }

    /**
     * Fehler protokollieren
     * @param string $message Fehlermeldung
     */
    private function log_error($message) {
        $this->log_event($message, 'error');
    }
}

// Plugin initialisieren
Derleiti_Plugin::get_instance();
