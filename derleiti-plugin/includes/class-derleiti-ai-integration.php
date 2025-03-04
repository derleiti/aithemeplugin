<?php
/**
 * Verwaltet die KI-Integration-Funktionen
 *
 * @package Derleiti_Plugin
 * @subpackage AI_Integration
 * @version 1.3.0
 */

// Verhindere direkten Zugriff
if ( ! defined( 'ABSPATH' ) ) {
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
        // AJAX-Handler für AI-Inhaltsgenerierung
        add_action( 'wp_ajax_derleiti_generate_ai_content', array( $this, 'generate_ai_content' ) );
        // REST API-Routen registrieren
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

        // Erstelle das Logging-Verzeichnis, falls nicht vorhanden
        $this->ensure_log_directory();
    }

    /**
     * Sichere Verzeichnis für Logging erstellen
     */
    private function ensure_log_directory() {
        $log_dir = dirname( self::LOG_FILE );
        if ( ! file_exists( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
        }

        // Sicherheit: Füge .htaccess hinzu, um direkten Zugriff zu blockieren
        $htaccess_file = $log_dir . '/.htaccess';
        if ( ! file_exists( $htaccess_file ) ) {
            file_put_contents( $htaccess_file, "Deny from all\n" );
        }
    }

    /**
     * Protokolliere API-Fehler
     *
     * @param string $provider KI-Provider
     * @param string $error Fehlermeldung
     * @param array  $context Zusätzlicher Fehlerkontext
     */
    private function log_api_error( $provider, $error, $context = [] ) {
        $log_entry = sprintf(
            "[%s] Provider: %s, Error: %s, Context: %s\n",
            current_time( 'mysql' ),
                             $provider,
                             $error,
                             json_encode( $context )
        );

        // Schreibe den Logeintrag in die Log-Datei
        error_log( $log_entry, 3, self::LOG_FILE );
    }

    /**
     * Rate Limiting für API-Aufrufe
     *
     * @param string $provider KI-Provider
     * @return bool|WP_Error
     */
    private function check_rate_limit( $provider ) {
        $transient_key = "derleiti_ai_rate_limit_{$provider}";
        $current_calls = get_transient( $transient_key ) ?: 0;

        if ( $current_calls >= self::MAX_API_CALLS ) {
            $this->log_api_error( $provider, 'Rate limit exceeded', [
                'max_calls'    => self::MAX_API_CALLS,
                'current_calls'=> $current_calls,
            ] );

            return new WP_Error(
                'rate_limit',
                __( 'API-Aufruf-Limit überschritten. Bitte später erneut versuchen.', 'derleiti-plugin' )
            );
        }

        // Inkrementiere API-Aufrufe
        set_transient( $transient_key, $current_calls + 1, self::API_RATE_LIMIT_PERIOD );

        return true;
    }

    /**
     * Generiere KI-Inhalte mit erweiterten Sicherheitsmaßnahmen
     *
     * @param string $prompt
     * @param string $content_type
     * @param string $tone
     * @param string $length
     * @param string $provider
     * @param float  $temperature
     * @return string|WP_Error
     */
    public function generate_content( $prompt, $content_type = 'paragraph', $tone = 'neutral', $length = 'medium', $provider = '', $temperature = 0.7 ) {
        // Eingabe-Sanitierung
        $prompt       = wp_strip_all_tags( $prompt );
        $content_type = sanitize_key( $content_type );
        $tone         = sanitize_key( $tone );
        $length       = sanitize_key( $length );
        $provider     = sanitize_key( $provider );
        $temperature  = floatval( $temperature );

        // Überprüfe Eingabelänge
        if ( mb_strlen( $prompt ) > 2000 ) {
            return new WP_Error( 'prompt_too_long', __( 'Prompt ist zu lang. Maximal 2000 Zeichen erlaubt.', 'derleiti-plugin' ) );
        }

        // Rate Limiting
        $rate_limit_check = $this->check_rate_limit( $provider ?: 'default' );
        if ( is_wp_error( $rate_limit_check ) ) {
            return $rate_limit_check;
        }

        // Provider-Auswahl mit Fallback
        if ( empty( $provider ) ) {
            $provider = $this->get_active_provider();
        }

        try {
            switch ( $provider ) {
                case 'openai':
                    $content = $this->generate_content_openai( $prompt, $temperature );
                    break;
                case 'gemini':
                    $content = $this->generate_content_gemini( $prompt, $temperature );
                    break;
                case 'anthropic':
                    $content = $this->generate_content_anthropic( $prompt, $temperature );
                    break;
                default:
                    $content = $this->mock_generate_content( $prompt, $content_type, $tone, $length );
            }

            // Sicherheitsfilterung des generierten Inhalts
            $content = $this->sanitize_generated_content( $content );

            return $content;
        } catch ( Exception $e ) {
            $this->log_api_error( $provider, $e->getMessage(), [
                'prompt'       => $prompt,
                'content_type' => $content_type,
                'trace'        => $e->getTraceAsString(),
            ] );

            return new WP_Error( 'generation_error', __( 'Fehler bei der Inhaltsgenerierung.', 'derleiti-plugin' ) );
        }
    }

    /**
     * AJAX Callback für KI-Inhaltsgenerierung
     */
    public function generate_ai_content() {
        $prompt       = isset( $_POST['prompt'] ) ? $_POST['prompt'] : '';
        $content_type = isset( $_POST['contentType'] ) ? $_POST['contentType'] : 'paragraph';
        $tone         = isset( $_POST['tone'] ) ? $_POST['tone'] : 'neutral';
        $length       = isset( $_POST['length'] ) ? $_POST['length'] : 'medium';
        $provider     = isset( $_POST['provider'] ) ? $_POST['provider'] : '';
        $temperature  = isset( $_POST['temperature'] ) ? floatval( $_POST['temperature'] ) : 0.7;

        $content = $this->generate_content( $prompt, $content_type, $tone, $length, $provider, $temperature );

        if ( is_wp_error( $content ) ) {
            wp_send_json_error( [ 'error' => $content->get_error_message() ] );
        } else {
            wp_send_json_success( [ 'content' => $content ] );
        }
    }

    /**
     * Sanitize generated AI content
     *
     * @param string $content Generierter Inhalt
     * @return string Bereinigter Inhalt
     */
    private function sanitize_generated_content( $content ) {
        // Entferne potenziell schädliche Inhalte
        $content = wp_kses_post( $content );

        // Begrenze die Länge
        $content = mb_substr( $content, 0, 5000 );

        return $content;
    }

    /**
     * Hole den aktiven KI-Provider mit erweiterten Fallback-Mechanismen
     */
    public function get_active_provider() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';

        // Hole konfigurierten Provider
        $provider = $wpdb->get_var( "SELECT setting_value FROM $table_name WHERE setting_name = 'ai_provider'" );

        // Überprüfe Provider-Verfügbarkeit
        $providers = $this->get_available_providers();

        // Fallback-Logik
        if ( empty( $provider ) || ! isset( $providers[ $provider ] ) || ! $providers[ $provider ]['enabled'] ) {
            foreach ( $providers as $provider_id => $provider_info ) {
                if ( $provider_info['enabled'] ) {
                    return $provider_id;
                }
            }
        }

        // Letzter Fallback
        return 'mock';
    }

    /**
     * Dummy-Implementierung: Verfügbare KI-Provider abrufen
     *
     * @return array
     */
    private function get_available_providers() {
        // Beispielhafte Implementierung, hier sollten die echten Konfigurationen stehen.
        return [
            'openai'    => [ 'enabled' => true, 'name' => 'OpenAI' ],
            'gemini'    => [ 'enabled' => true, 'name' => 'Google Gemini' ],
            'anthropic' => [ 'enabled' => false, 'name' => 'Anthropic Claude' ],
            'mock'      => [ 'enabled' => true, 'name' => 'Mock Provider' ],
        ];
    }

    /**
     * Dummy-Implementierung: Inhalte mit OpenAI generieren
     */
    private function generate_content_openai( $prompt, $temperature ) {
        // Hier würde der API-Aufruf an OpenAI erfolgen
        return "OpenAI generierter Inhalt: " . $prompt;
    }

    /**
     * Dummy-Implementierung: Inhalte mit Gemini generieren
     */
    private function generate_content_gemini( $prompt, $temperature ) {
        return "Gemini generierter Inhalt: " . $prompt;
    }

    /**
     * Dummy-Implementierung: Inhalte mit Anthropic generieren
     */
    private function generate_content_anthropic( $prompt, $temperature ) {
        return "Anthropic generierter Inhalt: " . $prompt;
    }

    /**
     * Dummy-Implementierung: Inhalte generieren (Fallback)
     */
    private function mock_generate_content( $prompt, $content_type, $tone, $length ) {
        return "Mock-Inhalt basierend auf Prompt: " . $prompt;
    }

    /**
     * Dummy-Implementierung: Registriere REST-Routen
     */
    public function register_rest_routes() {
        // Hier können REST-Routen registriert werden, falls benötigt.
    }
}

// Initialisiere die AI-Integration
$derleiti_ai_integration = new Derleiti_AI_Integration();
$derleiti_ai_integration->init();
