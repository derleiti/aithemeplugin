<?php
/**
 * Verwaltet die KI-Integration-Funktionen
 *
 * @package Derleiti_Plugin
 * @subpackage AI_Integration
 * @version 1.3.0
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

class Derleiti_AI_Integration {
    // Rate Limiting Configuration
    private const MAX_API_CALLS = 100;
    private const API_RATE_LIMIT_PERIOD = HOUR_IN_SECONDS;

    // Logging Configuration
    private const LOG_FILE = WP_CONTENT_DIR . '/derleiti-ai-logs/ai-integration.log';

    /**
     * Initialisiere die AI-Integration-Klasse
     */
    public function init() {
        // Existing initialization actions
        add_action('wp_ajax_derleiti_generate_ai_content', array($this, 'generate_ai_content'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Add error logging directory
        $this->ensure_log_directory();
    }

    /**
     * Sichere Verzeichnis für Logging erstellen
     */
    private function ensure_log_directory() {
        $log_dir = dirname(self::LOG_FILE);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        // Sicherheit: Füge .htaccess hinzu, um Zugriff zu blockieren
        $htaccess_file = $log_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, "Deny from all\n");
        }
    }

    /**
     * Protokolliere API-Fehler
     * 
     * @param string $provider KI-Provider
     * @param string $error Fehlermeldung
     * @param array $context Zusätzlicher Fehlerkontext
     */
    private function log_api_error($provider, $error, $context = []) {
        $log_entry = sprintf(
            "[%s] Provider: %s, Error: %s, Context: %s\n", 
            current_time('mysql'), 
            $provider, 
            $error, 
            json_encode($context)
        );
        
        // Sichere Fehlerprotokollierung
        error_log($log_entry, 3, self::LOG_FILE);
    }

    /**
     * Rate Limiting für API-Aufrufe
     * 
     * @param string $provider KI-Provider
     * @return bool|WP_Error
     */
    private function check_rate_limit($provider) {
        $transient_key = "derleiti_ai_rate_limit_{$provider}";
        $current_calls = get_transient($transient_key) ?: 0;
        
        if ($current_calls >= self::MAX_API_CALLS) {
            $this->log_api_error($provider, 'Rate limit exceeded', [
                'max_calls' => self::MAX_API_CALLS,
                'current_calls' => $current_calls
            ]);
            
            return new WP_Error(
                'rate_limit', 
                __('API-Aufruf-Limit überschritten. Bitte später erneut versuchen.', 'derleiti-plugin')
            );
        }
        
        // Inkrementiere API-Aufrufe
        set_transient($transient_key, $current_calls + 1, self::API_RATE_LIMIT_PERIOD);
        
        return true;
    }

    /**
     * Generiere KI-Inhalte mit erweiterten Sicherheitsmaßnahmen
     */
    public function generate_content($prompt, $content_type = 'paragraph', $tone = 'neutral', $length = 'medium', $provider = '', $temperature = 0.7) {
        // Eingabe-Sanitierung
        $prompt = wp_strip_all_tags($prompt);
        $content_type = sanitize_key($content_type);
        $tone = sanitize_key($tone);
        $length = sanitize_key($length);
        $provider = sanitize_key($provider);
        $temperature = floatval($temperature);

        // Überprüfe Eingabelänge
        if (mb_strlen($prompt) > 2000) {
            return new WP_Error('prompt_too_long', __('Prompt ist zu lang. Maximal 2000 Zeichen erlaubt.', 'derleiti-plugin'));
        }

        // Rate Limiting
        $rate_limit_check = $this->check_rate_limit($provider ?: 'default');
        if (is_wp_error($rate_limit_check)) {
            return $rate_limit_check;
        }

        // Provider-Auswahl mit Fallback
        if (empty($provider)) {
            $provider = $this->get_active_provider();
        }

        try {
            switch ($provider) {
                case 'openai':
                    $content = $this->generate_content_openai($prompt, $temperature);
                    break;
                case 'gemini':
                    $content = $this->generate_content_gemini($prompt, $temperature);
                    break;
                case 'anthropic':
                    $content = $this->generate_content_anthropic($prompt, $temperature);
                    break;
                default:
                    $content = $this->mock_generate_content($prompt, $content_type, $tone, $length);
            }

            // Sicherheitsfilterung des generierten Inhalts
            $content = $this->sanitize_generated_content($content);

            return $content;
        } catch (Exception $e) {
            $this->log_api_error($provider, $e->getMessage(), [
                'prompt' => $prompt,
                'content_type' => $content_type,
                'trace' => $e->getTraceAsString()
            ]);

            return new WP_Error('generation_error', __('Fehler bei der Inhaltsgenerierung.', 'derleiti-plugin'));
        }
    }

    /**
     * Sanitize generated AI content
     * 
     * @param string $content Generierter Inhalt
     * @return string Bereinigter Inhalt
     */
    private function sanitize_generated_content($content) {
        // Entferne potenziell schädliche Inhalte
        $content = wp_kses_post($content);
        
        // Begrenze die Länge
        $content = mb_substr($content, 0, 5000);
        
        return $content;
    }

    // Bestehende Methoden wie generate_content_openai(), generate_content_gemini() etc. 
    // würden hier ähnlich überarbeitet werden, mit zusätzlicher Fehlerbehandlung und Sicherheitsmaßnahmen

    /**
     * Hole den aktiven KI-Provider mit erweiterten Fallback-Mechanismen
     */
    public function get_active_provider() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';
        
        // Hole konfigurierten Provider
        $provider = $wpdb->get_var("SELECT setting_value FROM $table_name WHERE setting_name = 'ai_provider'");
        
        // Überprüfe Provider-Verfügbarkeit
        $providers = $this->get_available_providers();
        
        // Fallback-Logik
        if (empty($provider) || !isset($providers[$provider]) || !$providers[$provider]['enabled']) {
            foreach ($providers as $provider_id => $provider_info) {
                if ($provider_info['enabled']) {
                    return $provider_id;
                }
            }
        }
        
        // Letzter Fallback
        return 'mock';
    }

    // Andere bestehende Methoden würden ähnlich überarbeitet werden
}
