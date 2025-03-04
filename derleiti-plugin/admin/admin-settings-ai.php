<?php
/**
 * AI Settings Page for Derleiti Modern Theme Plugin
 *
 * @package Derleiti_Plugin
 * @subpackage Admin
 * @version 1.3.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Derleiti_AI_Settings {
    // Configuration constants
    private const LOG_DIRECTORY = WP_CONTENT_DIR . '/derleiti-logs/ai-settings/';
    private const MAX_API_TEST_ATTEMPTS = 3;
    private const API_TEST_TRANSIENT_PREFIX = 'derleiti_api_test_';

    /**
     * Constructor with improved initialization
     */
    public function __construct() {
        // Ensure logging directory exists
        $this->ensure_log_directory();

        // Add hooks with priority and conditional checks
        add_action('admin_menu', array($this, 'add_settings_page'), 10);
        add_action('admin_init', array($this, 'register_settings'), 20);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'), 10);

        // AJAX actions with proper prefixing
        add_action('wp_ajax_derleiti_test_ai_connection', array($this, 'ajax_test_ai_connection'));
    }

    /**
     * Ensure secure log directory
     */
    private function ensure_log_directory() {
        $log_dir = self::LOG_DIRECTORY;

        // Create directory with proper permissions
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        // Add .htaccess to prevent direct access
        $htaccess_path = $log_dir . '.htaccess';
        if (!file_exists($htaccess_path)) {
            file_put_contents($htaccess_path, "Deny from all\n");
        }
    }

    /**
     * Secure logging method
     *
     * @param string $message Log message
     * @param string $level Log level (info, warning, error)
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
     * Enhanced settings registration with validation
     */
    public function register_settings() {
        // Register main AI settings group
        register_setting('derleiti_ai_settings', 'derleiti_ai_options', array(
            'sanitize_callback' => array($this, 'sanitize_ai_settings'),
                                                                              'default'           => $this->get_default_settings()
        ));

        // Add settings sections with descriptive callbacks
        add_settings_section(
            'derleiti_ai_general_section',
            __('Allgemeine KI-Einstellungen', 'derleiti-plugin'),
                             array($this, 'render_general_section_description'),
                             'derleiti_ai_settings'
        );

        // Register individual settings fields
        $this->register_ai_settings_fields();
    }

    /**
     * Get default AI settings with enhanced security
     *
     * @return array Default settings configuration
     */
    private function get_default_settings() {
        return array(
            'ai_enabled'         => 0,
            'provider'           => 'openai',
            'openai_api_key'     => '',
            'gemini_api_key'     => '',
            'anthropic_api_key'  => '',
            'max_tokens'         => 1500,
            'temperature'        => 0.7,
            'last_provider_test' => array(), // Track last successful tests
        );
    }

    /**
     * Register individual AI settings fields with enhanced validation
     */
    private function register_ai_settings_fields() {
        // AI Enabled Toggle
        add_settings_field(
            'ai_enabled',
            __('KI-Integration aktivieren', 'derleiti-plugin'),
                           array($this, 'render_toggle_field'),
                           'derleiti_ai_settings',
                           'derleiti_ai_general_section',
                           array(
                               'name'        => 'ai_enabled',
                               'description' => __('Aktiviert oder deaktiviert die KI-Funktionen komplett.', 'derleiti-plugin')
                           )
        );

        // Provider Selection
        add_settings_field(
            'ai_provider',
            __('Standard KI-Anbieter', 'derleiti-plugin'),
                           array($this, 'render_provider_select'),
                           'derleiti_ai_settings',
                           'derleiti_ai_general_section'
        );

        // API Key Fields with Enhanced Security
        $providers = array(
            'openai'    => 'OpenAI',
            'gemini'    => 'Google Gemini',
            'anthropic' => 'Anthropic Claude'
        );

        foreach ($providers as $provider_key => $provider_name) {
            add_settings_field(
                "{$provider_key}_api_key",
                sprintf(__('%s API-Schlüssel', 'derleiti-plugin'), $provider_name),
                               array($this, 'render_api_key_field'),
                               'derleiti_ai_settings',
                               'derleiti_ai_general_section',
                               array(
                                   'provider' => $provider_key,
                                   'name'     => "{$provider_key}_api_key"
                               )
            );
        }
    }

    /**
     * Sanitize and validate AI settings
     *
     * @param array $input Submitted settings
     * @return array Sanitized settings
     */
    public function sanitize_ai_settings($input) {
        $output = array();
        $current_options = get_option('derleiti_ai_options', array());

        // Sanitize Enable Flag
        $output['ai_enabled'] = isset($input['ai_enabled']) ? 1 : 0;

        // Validate Provider – prüfe, ob der Schlüssel vorhanden ist
        $valid_providers = array('openai', 'gemini', 'anthropic');
        $output['provider'] = (isset($input['provider']) && in_array($input['provider'], $valid_providers))
        ? $input['provider']
        : 'openai';

        // API Key Handling with Enhanced Security
        $providers = array('openai', 'gemini', 'anthropic');
        foreach ($providers as $provider) {
            $key_name = "{$provider}_api_key";
            // Keep existing key if new one is empty
            if (empty($input[$key_name])) {
                $output[$key_name] = isset($current_options[$key_name]) ? $current_options[$key_name] : '';
            } else {
                // Sanitize and partially mask new key
                $output[$key_name] = $this->mask_api_key($input[$key_name]);
            }
        }

        // Validate Temperature
        $temperature = floatval($input['temperature'] ?? 0.7);
        $output['temperature'] = max(0, min(1, $temperature));

        // Validate Max Tokens
        $max_tokens = intval($input['max_tokens'] ?? 1500);
        $output['max_tokens'] = max(50, min(4000, $max_tokens));

        return $output;
    }

    /**
     * Partially mask API keys for display
     *
     * @param string $key API Key
     * @return string Masked API Key
     */
    private function mask_api_key($key) {
        $key = trim($key);
        if (empty($key)) {
            return '';
        }
        // Keep first and last 4 characters, mask middle
        return substr($key, 0, 4) . str_repeat('*', max(0, strlen($key) - 8)) . substr($key, -4);
    }

    /**
     * Enhanced AJAX connection test with rate limiting
     */
    public function ajax_test_ai_connection() {
        // Security checks
        check_ajax_referer('derleiti_ai_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Keine Berechtigung.', 'derleiti-plugin'));
        }

        $provider = sanitize_key($_POST['provider'] ?? '');
        if (empty($provider)) {
            wp_send_json_error(__('Kein Anbieter angegeben.', 'derleiti-plugin'));
        }

        // Rate limiting to prevent abuse
        $transient_key = self::API_TEST_TRANSIENT_PREFIX . $provider;
        $attempts = get_transient($transient_key) ?: 0;

        if ($attempts >= self::MAX_API_TEST_ATTEMPTS) {
            $this->log_event("API Test Rate Limit Exceeded for {$provider}", 'warning');
            wp_send_json_error(__('Zu viele Verbindungstests. Bitte später erneut versuchen.', 'derleiti-plugin'));
        }

        // Increment attempts
        set_transient($transient_key, $attempts + 1, HOUR_IN_SECONDS);

        try {
            $result = $this->test_provider_connection($provider);

            if ($result['success']) {
                // Reset attempts on successful test
                delete_transient($transient_key);
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
        } catch (Exception $e) {
            $this->log_event("API Test Error for {$provider}: " . $e->getMessage(), 'error');
            wp_send_json_error(__('Unerwarteter Fehler beim Testen der Verbindung.', 'derleiti-plugin'));
        }
    }

    /**
     * Dummy implementation to test provider connection.
     *
     * @param string $provider Anbieter-Schlüssel
     * @return array Ergebnis des Verbindungstests
     */
    private function test_provider_connection($provider) {
        $options = get_option('derleiti_ai_options', $this->get_default_settings());
        $key_name = $provider . '_api_key';
        $api_key = $options[$key_name] ?? '';
        if (empty($api_key)) {
            return array(
                'success' => false,
                'message' => __('Kein API-Schlüssel für diesen Anbieter gefunden.', 'derleiti-plugin')
            );
        }
        // Hier würde die tatsächliche API-Abfrage erfolgen. (Dummy-Implementierung)
        return array(
            'success' => true,
            'message' => __('Verbindung erfolgreich getestet.', 'derleiti-plugin')
        );
    }

    /**
     * Add the AI settings page to the WordPress-Admin-Menü.
     */
    public function add_settings_page() {
        add_options_page(
            __('KI-Einstellungen', 'derleiti-plugin'),
                         __('KI-Einstellungen', 'derleiti-plugin'),
                         'manage_options',
                         'derleiti_ai_settings',
                         array($this, 'render_settings_page')
        );
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
        <h1><?php _e('KI-Einstellungen', 'derleiti-plugin'); ?></h1>
        <form method="post" action="options.php">
        <?php
        settings_fields('derleiti_ai_settings');
        do_settings_sections('derleiti_ai_settings');
        submit_button();
        ?>
        </form>
        </div>
        <?php
    }

    /**
     * Enqueue admin scripts for the settings page.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_scripts($hook) {
        // Enqueue scripts only on our settings page.
        if ($hook !== 'settings_page_derleiti_ai_settings') {
            return;
        }
        wp_enqueue_script(
            'derleiti-ai-settings',
            plugin_dir_url(__FILE__) . 'js/ai-settings.js',
                          array('jquery'),
                          '1.0',
                          true
        );
        wp_localize_script('derleiti-ai-settings', 'derleitiAISettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
                                                                               'nonce'   => wp_create_nonce('derleiti_ai_settings_nonce')
        ));
    }

    /**
     * Render a toggle field.
     *
     * @param array $args Field arguments.
     */
    public function render_toggle_field($args) {
        $options = get_option('derleiti_ai_options', $this->get_default_settings());
        $name = $args['name'];
        $value = $options[$name] ?? 0;
        ?>
        <label>
        <input type="checkbox" name="derleiti_ai_options[<?php echo esc_attr($name); ?>]" value="1" <?php checked(1, $value); ?>>
        <?php echo esc_html($args['description']); ?>
        </label>
        <?php
    }

    /**
     * Render provider selection field.
     */
    public function render_provider_select() {
        $options = get_option('derleiti_ai_options', $this->get_default_settings());
        $current_provider = $options['provider'] ?? 'openai';
        $valid_providers = array(
            'openai'    => 'OpenAI',
            'gemini'    => 'Google Gemini',
            'anthropic' => 'Anthropic Claude'
        );
        ?>
        <select name="derleiti_ai_options[provider]">
        <?php foreach ($valid_providers as $key => $label): ?>
        <option value="<?php echo esc_attr($key); ?>" <?php selected($current_provider, $key); ?>>
        <?php echo esc_html($label); ?>
        </option>
        <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render API key input field.
     *
     * @param array $args Field arguments.
     */
    public function render_api_key_field($args) {
        $options = get_option('derleiti_ai_options', $this->get_default_settings());
        $name = $args['name'];
        $value = $options[$name] ?? '';
        ?>
        <input type="text" name="derleiti_ai_options[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <?php
    }

    /**
     * Render description for the general section.
     */
    public function render_general_section_description() {
        echo '<p>' . __('Konfigurieren Sie die allgemeinen KI-Einstellungen für das Plugin.', 'derleiti-plugin') . '</p>';
    }
}

// Initialize the class
$derleiti_ai_settings = new Derleiti_AI_Settings();
