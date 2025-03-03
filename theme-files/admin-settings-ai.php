<?php
/**
 * AI Settings Page for Derleiti Modern Theme Plugin
 *
 * @package Derleiti_Plugin
 * @subpackage Admin
 * @version 1.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Derleiti_AI_Settings {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_derleiti_test_ai_connection', array($this, 'test_ai_connection'));
    }

    /**
     * Add AI settings page to admin menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'derleiti-plugin', 
            __('KI-Integration', 'derleiti-plugin'),
            __('KI-Integration', 'derleiti-plugin'),
            'manage_options',
            'derleiti-ai-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings and sections
     */
    public function register_settings() {
        // Register the setting
        register_setting(
            'derleiti_ai_settings',
            'derleiti_ai_options',
            array(
                'sanitize_callback' => array($this, 'sanitize_options'),
                'default' => array(
                    'ai_enabled' => 0,
                    'provider' => 'openai',
                    'openai_api_key' => '',
                    'gemini_api_key' => '',
                    'anthropic_api_key' => '',
                    'stable_diffusion_api_key' => '',
                    'max_tokens' => 1500,
                    'temperature' => 0.7,
                ),
            )
        );

        // Add sections
        add_settings_section(
            'derleiti_ai_general_section',
            __('Allgemeine KI-Einstellungen', 'derleiti-plugin'),
            array($this, 'render_general_section'),
            'derleiti_ai_settings'
        );

        add_settings_section(
            'derleiti_ai_provider_section',
            __('KI-Anbieter Einstellungen', 'derleiti-plugin'),
            array($this, 'render_provider_section'),
            'derleiti_ai_settings'
        );

        add_settings_section(
            'derleiti_ai_advanced_section',
            __('Erweiterte Einstellungen', 'derleiti-plugin'),
            array($this, 'render_advanced_section'),
            'derleiti_ai_settings'
        );

        // Add fields for general section
        add_settings_field(
            'ai_enabled',
            __('KI-Integration aktivieren', 'derleiti-plugin'),
            array($this, 'render_ai_enabled_field'),
            'derleiti_ai_settings',
            'derleiti_ai_general_section'
        );
        
        add_settings_field(
            'ai_provider',
            __('Standard KI-Anbieter', 'derleiti-plugin'),
            array($this, 'render_ai_provider_field'),
            'derleiti_ai_settings',
            'derleiti_ai_general_section'
        );

        // Add fields for provider section
        add_settings_field(
            'openai_api_key',
            __('OpenAI API-Schlüssel', 'derleiti-plugin'),
            array($this, 'render_openai_api_key_field'),
            'derleiti_ai_settings',
            'derleiti_ai_provider_section'
        );
        
        add_settings_field(
            'gemini_api_key',
            __('Google Gemini API-Schlüssel', 'derleiti-plugin'),
            array($this, 'render_gemini_api_key_field'),
            'derleiti_ai_settings',
            'derleiti_ai_provider_section'
        );
        
        add_settings_field(
            'anthropic_api_key',
            __('Anthropic Claude API-Schlüssel', 'derleiti-plugin'),
            array($this, 'render_anthropic_api_key_field'),
            'derleiti_ai_settings',
            'derleiti_ai_provider_section'
        );
        
        add_settings_field(
            'stable_diffusion_api_key',
            __('Stable Diffusion API-Schlüssel', 'derleiti-plugin'),
            array($this, 'render_stable_diffusion_api_key_field'),
            'derleiti_ai_settings',
            'derleiti_ai_provider_section'
        );

        // Add fields for advanced section
        add_settings_field(
            'max_tokens',
            __('Maximale Token', 'derleiti-plugin'),
            array($this, 'render_max_tokens_field'),
            'derleiti_ai_settings',
            'derleiti_ai_advanced_section'
        );
        
        add_settings_field(
            'temperature',
            __('Kreativität (Temperature)', 'derleiti-plugin'),
            array($this, 'render_temperature_field'),
            'derleiti_ai_settings',
            'derleiti_ai_advanced_section'
        );
    }

    /**
     * Sanitize options
     */
    public function sanitize_options($input) {
        $output = array();
        
        // General settings
        $output['ai_enabled'] = isset($input['ai_enabled']) ? 1 : 0;
        $output['provider'] = isset($input['provider']) ? sanitize_key($input['provider']) : 'openai';
        
        // API keys (these should be encrypted in a production environment)
        $output['openai_api_key'] = isset($input['openai_api_key']) ? trim(sanitize_text_field($input['openai_api_key'])) : '';
        $output['gemini_api_key'] = isset($input['gemini_api_key']) ? trim(sanitize_text_field($input['gemini_api_key'])) : '';
        $output['anthropic_api_key'] = isset($input['anthropic_api_key']) ? trim(sanitize_text_field($input['anthropic_api_key'])) : '';
        $output['stable_diffusion_api_key'] = isset($input['stable_diffusion_api_key']) ? trim(sanitize_text_field($input['stable_diffusion_api_key'])) : '';
        
        // Advanced settings
        $output['max_tokens'] = isset($input['max_tokens']) ? intval($input['max_tokens']) : 1500;
        $output['temperature'] = isset($input['temperature']) ? floatval($input['temperature']) : 0.7;
        
        // Ensure max_tokens is within acceptable range
        if ($output['max_tokens'] < 50) {
            $output['max_tokens'] = 50;
        } elseif ($output['max_tokens'] > 4000) {
            $output['max_tokens'] = 4000;
        }
        
        // Ensure temperature is within acceptable range
        if ($output['temperature'] < 0) {
            $output['temperature'] = 0;
        } elseif ($output['temperature'] > 1) {
            $output['temperature'] = 1;
        }
        
        // Save the options to the database table as well (for compatibility)
        $this->save_to_db_table($output);
        
        return $output;
    }
    
    /**
     * Save options to the database table
     */
    private function save_to_db_table($options) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return; // Table doesn't exist, skip
        }
        
        // Save each option to the table
        foreach ($options as $key => $value) {
            // For security reasons, mask API keys when saving to log
            $log_value = strpos($key, 'api_key') !== false ? '********' : $value;
            
            $wpdb->replace(
                $table_name,
                array(
                    'setting_name' => $key,
                    'setting_value' => $value,
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s')
            );
        }
        
        // Also save the main ai_provider setting
        $wpdb->replace(
            $table_name,
            array(
                'setting_name' => 'ai_provider',
                'setting_value' => $options['provider'],
                'updated_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div id="derleiti-ai-settings-notice" class="notice hidden">
                <p></p>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('derleiti_ai_settings');
                do_settings_sections('derleiti_ai_settings');
                submit_button(__('Einstellungen speichern', 'derleiti-plugin'));
                ?>
            </form>
            
            <div class="derleiti-ai-connection-tester">
                <h2><?php _e('KI-Verbindungen testen', 'derleiti-plugin'); ?></h2>
                <p><?php _e('Klicken Sie auf die Schaltflächen, um die Verbindung zu den konfigurierten KI-Anbietern zu testen:', 'derleiti-plugin'); ?></p>
                
                <div class="derleiti-ai-provider-tests">
                    <button type="button" class="button button-secondary test-provider" data-provider="openai">
                        <?php _e('OpenAI testen', 'derleiti-plugin'); ?>
                    </button>
                    
                    <button type="button" class="button button-secondary test-provider" data-provider="gemini">
                        <?php _e('Google Gemini testen', 'derleiti-plugin'); ?>
                    </button>
                    
                    <button type="button" class="button button-secondary test-provider" data-provider="anthropic">
                        <?php _e('Anthropic Claude testen', 'derleiti-plugin'); ?>
                    </button>
                    
                    <button type="button" class="button button-secondary test-provider" data-provider="stable-diffusion">
                        <?php _e('Stable Diffusion testen', 'derleiti-plugin'); ?>
                    </button>
                </div>
                
                <div id="derleiti-ai-test-results" class="derleiti-ai-test-results hidden">
                    <h3><?php _e('Testergebnisse', 'derleiti-plugin'); ?></h3>
                    <pre id="derleiti-ai-test-output"></pre>
                </div>
            </div>
            
            <hr>
            
            <div class="derleiti-ai-documentation">
                <h2><?php _e('Dokumentation und Hilfe', 'derleiti-plugin'); ?></h2>
                
                <div class="derleiti-ai-docs-section">
                    <h3><?php _e('API-Schlüssel erhalten', 'derleiti-plugin'); ?></h3>
                    <ul>
                        <li><strong><?php _e('OpenAI (ChatGPT):', 'derleiti-plugin'); ?></strong> <a href="https://platform.openai.com/api-keys" target="_blank"><?php _e('Rufen Sie die OpenAI-Plattform auf, um einen API-Schlüssel zu erhalten', 'derleiti-plugin'); ?></a></li>
                        <li><strong><?php _e('Google Gemini:', 'derleiti-plugin'); ?></strong> <a href="https://ai.google.dev/" target="_blank"><?php _e('Besuchen Sie Google AI Studio, um einen API-Schlüssel zu erhalten', 'derleiti-plugin'); ?></a></li>
                        <li><strong><?php _e('Anthropic Claude:', 'derleiti-plugin'); ?></strong> <a href="https://console.anthropic.com/" target="_blank"><?php _e('Registrieren Sie sich für die Anthropic-Console, um einen API-Schlüssel zu erhalten', 'derleiti-plugin'); ?></a></li>
                        <li><strong><?php _e('Stable Diffusion:', 'derleiti-plugin'); ?></strong> <a href="https://stablediffusionapi.com/" target="_blank"><?php _e('Besuchen Sie die Stable Diffusion API-Website, um einen Schlüssel zu erhalten', 'derleiti-plugin'); ?></a></li>
                    </ul>
                </div>
                
                <div class="derleiti-ai-docs-section">
                    <h3><?php _e('Funktionalitäten und Anbietervergleich', 'derleiti-plugin'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Anbieter', 'derleiti-plugin'); ?></th>
                                <th><?php _e('Textgenerierung', 'derleiti-plugin'); ?></th>
                                <th><?php _e('Bildgenerierung', 'derleiti-plugin'); ?></th>
                                <th><?php _e('Kontextverstehen', 'derleiti-plugin'); ?></th>
                                <th><?php _e('Kosten', 'derleiti-plugin'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>OpenAI (ChatGPT)</strong></td>
                                <td><span class="dashicons dashicons-yes"></span> <?php _e('Ausgezeichnet', 'derleiti-plugin'); ?></td>
                                <td><span class="dashicons dashicons-yes"></span> <?php _e('DALL-E 3', 'derleiti-plugin'); ?></td>
                                <td><span class="dashicons dashicons-yes"></span> <?php _e('Sehr gut', 'derleiti-plugin'); ?></td>
                                <td><?php _e('Mittel', 'derleiti-plugin'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Google Gemini</strong></td>
                                <td><span class="dashicons dashicons-yes"></span> <?php _e('Sehr gut', 'derleiti-plugin'); ?></td>
                                <td><span class="dashicons dashicons-no"></span> <?php _e('Noch nicht verfügbar', 'derleiti-plugin'); ?></td>
                                <td><span class="dashicons dashicons-yes"></span> <?php _e('Ausgezeichnet', 'derleiti-plugin'); ?></td>
                                <td><?php _e('Niedrig bis mittel', 'derleiti-plugin'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Anthropic Claude</strong></td>
                                <td><span class="dashicons dashicons-yes"></span> <?php _e('Ausgezeichnet', 'derleiti-plugin'); ?></td>
                                <td><span class="dashicons dashicons-no"></span> <?php _e('Nicht verfügbar', 'derleiti-plugin'); ?></td>
                                <td><span class="dashicons dashicons-yes"></span> <?php _e('Ausgezeichnet', 'derleiti-plugin'); ?></td>
                                <td><?php _e('Mittel bis hoch', 'derleiti-plugin'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Stable Diffusion</strong></td>
                                <td><span class="dashicons dashicons-no"></span> <?php _e('Nicht verfügbar', 'derleiti-plugin'); ?></td>
                                <td><span class="dashicons dashicons-yes"></span> <?php _e('Ausgezeichnet', 'derleiti-plugin'); ?></td>
                                <td><span class="dashicons dashicons-no"></span> <?php _e('Nicht anwendbar', 'derleiti-plugin'); ?></td>
                                <td><?php _e('Niedrig bis mittel', 'derleiti-plugin'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render general section description
     */
    public function render_general_section() {
        echo '<p>' . __('Konfigurieren Sie die grundlegenden Einstellungen für die KI-Integration.', 'derleiti-plugin') . '</p>';
    }

    /**
     * Render provider section description
     */
    public function render_provider_section() {
        echo '<p>' . __('Konfigurieren Sie die API-Schlüssel für die verschiedenen KI-Anbieter. Sie benötigen mindestens einen gültigen API-Schlüssel, um die KI-Funktionalität nutzen zu können.', 'derleiti-plugin') . '</p>';
    }

    /**
     * Render advanced section description
     */
    public function render_advanced_section() {
        echo '<p>' . __('Passen Sie erweiterte Parameter für die KI-Generierung an.', 'derleiti-plugin') . '</p>';
    }

    /**
     * Render AI enabled field
     */
    public function render_ai_enabled_field() {
        $options = get_option('derleiti_ai_options');
        $enabled = isset($options['ai_enabled']) ? $options['ai_enabled'] : 0;
        ?>
        <label for="ai-enabled">
            <input type="checkbox" id="ai-enabled" name="derleiti_ai_options[ai_enabled]" value="1" <?php checked(1, $enabled); ?>>
            <?php _e('KI-Funktionen aktivieren', 'derleiti-plugin'); ?>
        </label>
        <p class="description"><?php _e('Aktiviert alle KI-Funktionen im Theme und Plugin.', 'derleiti-plugin'); ?></p>
        <?php
    }

    /**
     * Render AI provider field
     */
    public function render_ai_provider_field() {
        $options = get_option('derleiti_ai_options');
        $provider = isset($options['provider']) ? $options['provider'] : 'openai';
        ?>
        <select id="ai-provider" name="derleiti_ai_options[provider]">
            <option value="openai" <?php selected('openai', $provider); ?>><?php _e('OpenAI (ChatGPT)', 'derleiti-plugin'); ?></option>
            <option value="gemini" <?php selected('gemini', $provider); ?>><?php _e('Google Gemini', 'derleiti-plugin'); ?></option>
            <option value="anthropic" <?php selected('anthropic', $provider); ?>><?php _e('Anthropic Claude', 'derleiti-plugin'); ?></option>
            <option value="stable-diffusion" <?php selected('stable-diffusion', $provider); ?>><?php _e('Stable Diffusion (nur Bilder)', 'derleiti-plugin'); ?></option>
        </select>
        <p class="description"><?php _e('Wählen Sie den Standard-KI-Anbieter für die Inhalts- und Bildgenerierung.', 'derleiti-plugin'); ?></p>
        <?php
    }

    /**
     * Render OpenAI API key field
     */
    public function render_openai_api_key_field() {
        $options = get_option('derleiti_ai_options');
        $api_key = isset($options['openai_api_key']) ? $options['openai_api_key'] : '';
        ?>
        <input type="password" id="openai-api-key" name="derleiti_ai_options[openai_api_key]" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
        <button type="button" class="button button-secondary toggle-password" data-target="openai-api-key">
            <span class="dashicons dashicons-visibility"></span>
        </button>
        <p class="description">
            <?php _e('Geben Sie Ihren OpenAI API-Schlüssel ein. Dieser wird für ChatGPT und DALL-E verwendet.', 'derleiti-plugin'); ?>
            <a href="https://platform.openai.com/api-keys" target="_blank"><?php _e('API-Schlüssel erhalten', 'derleiti-plugin'); ?></a>
        </p>
        <?php
    }

    /**
     * Render Google Gemini API key field
     */
    public function render_gemini_api_key_field() {
        $options = get_option('derleiti_ai_options');
        $api_key = isset($options['gemini_api_key']) ? $options['gemini_api_key'] : '';
        ?>
        <input type="password" id="gemini-api-key" name="derleiti_ai_options[gemini_api_key]" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
        <button type="button" class="button button-secondary toggle-password" data-target="gemini-api-key">
            <span class="dashicons dashicons-visibility"></span>
        </button>
        <p class="description">
            <?php _e('Geben Sie Ihren Google Gemini API-Schlüssel ein.', 'derleiti-plugin'); ?>
            <a href="https://ai.google.dev/" target="_blank"><?php _e('API-Schlüssel erhalten', 'derleiti-plugin'); ?></a>
        </p>
        <?php
    }

    /**
     * Render Anthropic Claude API key field
     */
    public function render_anthropic_api_key_field() {
        $options = get_option('derleiti_ai_options');
        $api_key = isset($options['anthropic_api_key']) ? $options['anthropic_api_key'] : '';
        ?>
        <input type="password" id="anthropic-api-key" name="derleiti_ai_options[anthropic_api_key]" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
        <button type="button" class="button button-secondary toggle-password" data-target="anthropic-api-key">
            <span class="dashicons dashicons-visibility"></span>
        </button>
        <p class="description">
            <?php _e('Geben Sie Ihren Anthropic Claude API-Schlüssel ein.', 'derleiti-plugin'); ?>
            <a href="https://console.anthropic.com/" target="_blank"><?php _e('API-Schlüssel erhalten', 'derleiti-plugin'); ?></a>
        </p>
        <?php
    }

    /**
     * Render Stable Diffusion API key field
     */
    public function render_stable_diffusion_api_key_field() {
        $options = get_option('derleiti_ai_options');
        $api_key = isset($options['stable_diffusion_api_key']) ? $options['stable_diffusion_api_key'] : '';
        ?>
        <input type="password" id="stable-diffusion-api-key" name="derleiti_ai_options[stable_diffusion_api_key]" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
        <button type="button" class="button button-secondary toggle-password" data-target="stable-diffusion-api-key">
            <span class="dashicons dashicons-visibility"></span>
        </button>
        <p class="description">
            <?php _e('Geben Sie Ihren Stable Diffusion API-Schlüssel ein (nur für Bildgenerierung).', 'derleiti-plugin'); ?>
            <a href="https://stablediffusionapi.com/" target="_blank"><?php _e('API-Schlüssel erhalten', 'derleiti-plugin'); ?></a>
        </p>
        <?php
    }

    /**
     * Render max tokens field
     */
    public function render_max_tokens_field() {
        $options = get_option('derleiti_ai_options');
        $max_tokens = isset($options['max_tokens']) ? $options['max_tokens'] : 1500;
        ?>
        <input type="number" id="max-tokens" name="derleiti_ai_options[max_tokens]" value="<?php echo esc_attr($max_tokens); ?>" min="50" max="4000" step="50" class="small-text">
        <p class="description"><?php _e('Maximale Anzahl an Tokens (Wörter/Zeichen) für die Textgenerierung. Höhere Werte ermöglichen längere Texte, erhöhen aber die Kosten.', 'derleiti-plugin'); ?></p>
        <?php
    }

    /**
     * Render temperature field
     */
    public function render_temperature_field() {
        $options = get_option('derleiti_ai_options');
        $temperature = isset($options['temperature']) ? $options['temperature'] : 0.7;
        ?>
        <input type="range" id="temperature" name="derleiti_ai_options[temperature]" value="<?php echo esc_attr($temperature); ?>" min="0" max="1" step="0.1" class="derleiti-range">
        <span class="temperature-value"><?php echo esc_html($temperature); ?></span>
        <p class="description"><?php _e('Steuert die Kreativität und Zufälligkeit der KI-Antworten. Niedrigere Werte (0-0.3) ergeben konsistentere, fokussiertere Ergebnisse. Höhere Werte (0.7-1.0) führen zu kreativeren, überraschenderen Antworten.', 'derleiti-plugin'); ?></p>
        <?php
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'derleiti_page_derleiti-ai-settings') {
            return;
        }

        wp_enqueue_style('derleiti-ai-settings-style', DERLEITI_PLUGIN_URL . 'admin/css/ai-settings.css', array(), DERLEITI_PLUGIN_VERSION);
        wp_enqueue_script('derleiti-ai-settings-script', DERLEITI_PLUGIN_URL . 'admin/js/ai-settings.js', array('jquery'), DERLEITI_PLUGIN_VERSION, true);
        
        wp_localize_script(
            'derleiti-ai-settings-script', 
            'derleitiAiSettings', 
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('derleiti_ai_settings_nonce'),
                'strings' => array(
                    'testing' => __('Teste Verbindung...', 'derleiti-plugin'),
                    'success' => __('Verbindung erfolgreich!', 'derleiti-plugin'),
                    'error' => __('Verbindungsfehler: ', 'derleiti-plugin'),
                    'show' => __('Anzeigen', 'derleiti-plugin'),
                    'hide' => __('Verbergen', 'derleiti-plugin'),
                    'settingsSaved' => __('Einstellungen gespeichert!', 'derleiti-plugin'),
                    'settingsError' => __('Fehler beim Speichern der Einstellungen.', 'derleiti-plugin'),
                    'apiKeyMissing' => __('API-Schlüssel fehlt. Bitte geben Sie einen API-Schlüssel ein.', 'derleiti-plugin'),
                )
            )
        );
    }

    /**
     * AJAX handler to test AI connection
     */
    public function test_ai_connection() {
        // Check nonce
        check_ajax_referer('derleiti_ai_settings_nonce', 'nonce');
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung.', 'derleiti-plugin')));
            wp_die();
        }
        
        // Get the provider to test
        $provider = isset($_POST['provider']) ? sanitize_key($_POST['provider']) : '';
        
        if (empty($provider)) {
            wp_send_json_error(array('message' => __('Kein Anbieter angegeben.', 'derleiti-plugin')));
            wp_die();
        }
        
        // Get options
        $options = get_option('derleiti_ai_options');
        $api_key_name = $provider . '_api_key';
        
        if (!isset($options[$api_key_name]) || empty($options[$api_key_name])) {
            wp_send_json_error(array('message' => __('API-Schlüssel fehlt.', 'derleiti-plugin')));
            wp_die();
        }
        
        $api_key = $options[$api_key_name];
        
        // Test the connection based on the provider
        switch ($provider) {
            case 'openai':
                $result = $this->test_openai_connection($api_key);
                break;
                
            case 'gemini':
                $result = $this->test_gemini_connection($api_key);
                break;
                
            case 'anthropic':
                $result = $this->test_anthropic_connection($api_key);
                break;
                
            case 'stable-diffusion':
                $result = $this->test_stable_diffusion_connection($api_key);
                break;
                
            default:
                wp_send_json_error(array('message' => __('Ungültiger Anbieter.', 'derleiti-plugin')));
                wp_die();
        }
        
        if (isset($result['success']) && $result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
        
        wp_die();
    }
    
    /**
     * Test OpenAI connection
     */
    private function test_openai_connection($api_key) {
        $api_url = 'https://api.openai.com/v1/models';
        
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 15,
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code === 200) {
            // Extract model information
            $models = array();
            if (isset($response_body['data']) && is_array($response_body['data'])) {
                foreach ($response_body['data'] as $model) {
                    if (isset($model['id']) && (
                        strpos($model['id'], 'gpt-4') === 0 || 
                        strpos($model['id'], 'gpt-3.5') === 0 ||
                        strpos($model['id'], 'dall-e') === 0 ||
                        strpos($model['id'], 'text-davinci') === 0
                    )) {
                        $models[] = $model['id'];
                    }
                }
            }
            
            return array(
                'success' => true,
                'message' => __('Verbindung zu OpenAI erfolgreich hergestellt.', 'derleiti-plugin'),
                'models' => $models,
            );
        } else {
            $error_message = isset($response_body['error']['message']) ? $response_body['error']['message'] : __('Unbekannter Fehler', 'derleiti-plugin');
            
            return array(
                'success' => false,
                'message' => sprintf(__('Fehler bei der OpenAI-Verbindung: %s', 'derleiti-plugin'), $error_message),
                'code' => $response_code,
            );
        }
    }
    
    /**
     * Test Google Gemini connection
     */
    private function test_gemini_connection($api_key) {
        $api_url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $api_key;
        
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => 15,
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code === 200) {
            // Extract model information
            $models = array();
            if (isset($response_body['models']) && is_array($response_body['models'])) {
                foreach ($response_body['models'] as $model) {
                    if (isset($model['name'])) {
                        $models[] = $model['name'];
                    }
                }
            }
            
            return array(
                'success' => true,
                'message' => __('Verbindung zu Google Gemini erfolgreich hergestellt.', 'derleiti-plugin'),
                'models' => $models,
            );
        } else {
            $error_message = isset($response_body['error']['message']) ? $response_body['error']['message'] : __('Unbekannter Fehler', 'derleiti-plugin');
            
            return array(
                'success' => false,
                'message' => sprintf(__('Fehler bei der Google Gemini-Verbindung: %s', 'derleiti-plugin'), $error_message),
                'code' => $response_code,
            );
        }
    }
    
    /**
     * Test Anthropic connection
     */
    private function test_anthropic_connection($api_key) {
        $api_url = 'https://api.anthropic.com/v1/models';
        
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ),
            'timeout' => 15,
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code === 200) {
            // Extract model information
            $models = array();
            if (isset($response_body['models']) && is_array($response_body['models'])) {
                foreach ($response_body['models'] as $model) {
                    if (isset($model['name'])) {
                        $models[] = $model['name'];
                    }
                }
            }
            
            return array(
                'success' => true,
                'message' => __('Verbindung zu Anthropic Claude erfolgreich hergestellt.', 'derleiti-plugin'),
                'models' => $models,
            );
        } else {
            $error_message = isset($response_body['error']['message']) ? $response_body['error']['message'] : __('Unbekannter Fehler', 'derleiti-plugin');
            
            return array(
                'success' => false,
                'message' => sprintf(__('Fehler bei der Anthropic-Verbindung: %s', 'derleiti-plugin'), $error_message),
                'code' => $response_code,
            );
        }
    }
    
    /**
     * Test Stable Diffusion connection
     */
    private function test_stable_diffusion_connection($api_key) {
        $api_url = 'https://api.stability.ai/v1/engines/list';
        
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 15,
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code === 200) {
            // Extract model information
            $engines = array();
            if (is_array($response_body)) {
                foreach ($response_body as $engine) {
                    if (isset($engine['id'])) {
                        $engines[] = $engine['id'];
                    }
                }
            }
            
            return array(
                'success' => true,
                'message' => __('Verbindung zu Stable Diffusion erfolgreich hergestellt.', 'derleiti-plugin'),
                'engines' => $engines,
            );
        } else {
            $error_message = isset($response_body['message']) ? $response_body['message'] : __('Unbekannter Fehler', 'derleiti-plugin');
            
            return array(
                'success' => false,
                'message' => sprintf(__('Fehler bei der Stable Diffusion-Verbindung: %s', 'derleiti-plugin'), $error_message),
                'code' => $response_code,
            );
        }
    }
}

// Initialize the class
$derleiti_ai_settings = new Derleiti_AI_Settings();
