<?php
/**
 * Verwaltet die KI-Integration-Funktionen
 *
 * @package Derleiti_Plugin
 * @subpackage AI_Integration
 * @version 1.2.0
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Die AI-Integration-Klasse des Plugins
 */
class Derleiti_AI_Integration {
    
    /**
     * Initialisiere die AI-Integration-Klasse
     */
    public function init() {
        // AJAX-Handler für KI-Inhalte
        add_action('wp_ajax_derleiti_generate_ai_content', array($this, 'generate_ai_content'));
        
        // AJAX-Handler für KI-Bildgenerierung
        add_action('wp_ajax_derleiti_generate_ai_image', array($this, 'generate_ai_image'));
        
        // REST API Endpunkte für KI-Integration
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Shortcodes für KI-Funktionen
        add_shortcode('derleiti_ai_content', array($this, 'ai_content_shortcode'));
        
        // Filter für automatische Inhaltsverbesserung
        add_filter('the_content', array($this, 'enhance_content_with_ai'), 20);
        
        // Editor-Sidebar für KI-Assistenten
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_ai_assistant_scripts'));
        
        // Schedules für KI-basierte Inhaltsoptimierung
        add_action('derleiti_ai_content_optimization', array($this, 'scheduled_content_optimization'));
        if (!wp_next_scheduled('derleiti_ai_content_optimization')) {
            wp_schedule_event(time(), 'daily', 'derleiti_ai_content_optimization');
        }
    }
    
    /**
     * Registriere REST API Routen
     */
    public function register_rest_routes() {
        register_rest_route('derleiti-plugin/v1', '/ai/generate-content', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_generate_content'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
            'args' => array(
                'prompt' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'contentType' => array(
                    'type' => 'string',
                    'default' => 'paragraph',
                ),
                'tone' => array(
                    'type' => 'string',
                    'default' => 'neutral',
                ),
                'length' => array(
                    'type' => 'string',
                    'default' => 'medium',
                ),
                'provider' => array(
                    'type' => 'string',
                    'default' => '',
                ),
            ),
        ));
        
        register_rest_route('derleiti-plugin/v1', '/ai/generate-image', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_generate_image'),
            'permission_callback' => function() {
                return current_user_can('upload_files');
            },
            'args' => array(
                'prompt' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'style' => array(
                    'type' => 'string',
                    'default' => 'realistic',
                ),
                'size' => array(
                    'type' => 'string',
                    'default' => 'medium',
                ),
                'provider' => array(
                    'type' => 'string',
                    'default' => '',
                ),
            ),
        ));
        
        // Endpoint für AI-Provider-Status überprüfen
        register_rest_route('derleiti-plugin/v1', '/ai/provider-status', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_provider_status'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ));
    }
    
    /**
     * REST API Callback für Inhaltsgenerierung
     */
    public function rest_generate_content($request) {
        $prompt = $request->get_param('prompt');
        $content_type = $request->get_param('contentType');
        $tone = $request->get_param('tone');
        $length = $request->get_param('length');
        $provider = $request->get_param('provider');
        
        $content = $this->generate_content($prompt, $content_type, $tone, $length, $provider);
        
        if (is_wp_error($content)) {
            return rest_ensure_response(array(
                'success' => false,
                'error' => $content->get_error_message(),
            ));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'content' => $content,
        ));
    }
    
    /**
     * REST API Callback für Bildgenerierung
     */
    public function rest_generate_image($request) {
        $prompt = $request->get_param('prompt');
        $style = $request->get_param('style');
        $size = $request->get_param('size');
        $provider = $request->get_param('provider');
        
        $image = $this->generate_image($prompt, $style, $size, $provider);
        
        if (is_wp_error($image)) {
            return rest_ensure_response(array(
                'success' => false,
                'error' => $image->get_error_message(),
            ));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'imageUrl' => $image['url'],
            'imageId' => $image['id'],
        ));
    }
    
    /**
     * REST API Callback für Provider-Status
     */
    public function rest_get_provider_status() {
        $providers = $this->get_available_providers();
        $active_provider = $this->get_active_provider();
        
        return rest_ensure_response(array(
            'success' => true,
            'providers' => $providers,
            'activeProvider' => $active_provider,
        ));
    }
    
    /**
     * Hole alle verfügbaren KI-Provider
     */
    public function get_available_providers() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';
        
        $providers = array(
            'openai' => array(
                'name' => 'OpenAI (ChatGPT)',
                'enabled' => false,
                'hasApiKey' => false,
                'features' => array('text', 'image'),
            ),
            'gemini' => array(
                'name' => 'Google Gemini',
                'enabled' => false,
                'hasApiKey' => false,
                'features' => array('text', 'image'),
            ),
            'anthropic' => array(
                'name' => 'Anthropic (Claude)',
                'enabled' => false,
                'hasApiKey' => false,
                'features' => array('text'),
            ),
            'stable-diffusion' => array(
                'name' => 'Stable Diffusion',
                'enabled' => false,
                'hasApiKey' => false,
                'features' => array('image'),
            ),
        );
        
        // Überprüfe, ob API-Schlüssel vorhanden sind
        foreach ($providers as $provider_id => &$provider) {
            $api_key = $wpdb->get_var($wpdb->prepare(
                "SELECT setting_value FROM $table_name WHERE setting_name = %s",
                $provider_id . '_api_key'
            ));
            
            $provider['hasApiKey'] = !empty($api_key);
            $provider['enabled'] = !empty($api_key);
        }
        
        return $providers;
    }
    
    /**
     * Hole den aktiven KI-Provider
     */
    public function get_active_provider() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';
        
        $active_provider = $wpdb->get_var("SELECT setting_value FROM $table_name WHERE setting_name = 'ai_provider'");
        $providers = $this->get_available_providers();
        
        // Fall back to first available provider if the active one isn't available
        if (empty($active_provider) || !isset($providers[$active_provider]) || !$providers[$active_provider]['enabled']) {
            foreach ($providers as $provider_id => $provider) {
                if ($provider['enabled']) {
                    $active_provider = $provider_id;
                    break;
                }
            }
        }
        
        // If no provider is available, use mock
        if (empty($active_provider) || !isset($providers[$active_provider]) || !$providers[$active_provider]['enabled']) {
            $active_provider = 'mock';
        }
        
        return $active_provider;
    }
    
    /**
     * AJAX-Handler für KI-Inhalte
     */
    public function generate_ai_content() {
        // Sicherheitscheck
        if (!check_ajax_referer('derleiti_ai_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Sicherheitsprüfung fehlgeschlagen.', 'derleiti-plugin')));
            wp_die();
        }
        
        // Überprüfe Berechtigungen
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung.', 'derleiti-plugin')));
            wp_die();
        }
        
        // Parameter abrufen
        $prompt = isset($_POST['prompt']) ? sanitize_text_field($_POST['prompt']) : '';
        $content_type = isset($_POST['contentType']) ? sanitize_text_field($_POST['contentType']) : 'paragraph';
        $tone = isset($_POST['tone']) ? sanitize_text_field($_POST['tone']) : 'neutral';
        $length = isset($_POST['length']) ? sanitize_text_field($_POST['length']) : 'medium';
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        
        if (empty($prompt)) {
            wp_send_json_error(array('message' => __('Bitte geben Sie einen Prompt ein.', 'derleiti-plugin')));
            wp_die();
        }
        
        $content = $this->generate_content($prompt, $content_type, $tone, $length, $provider);
        
        if (is_wp_error($content)) {
            wp_send_json_error(array('message' => $content->get_error_message()));
            wp_die();
        }
        
        wp_send_json_success(array('content' => $content));
        wp_die();
    }
    
    /**
     * AJAX-Handler für KI-Bildgenerierung
     */
    public function generate_ai_image() {
        // Sicherheitscheck
        if (!check_ajax_referer('derleiti_ai_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Sicherheitsprüfung fehlgeschlagen.', 'derleiti-plugin')));
            wp_die();
        }
        
        // Überprüfe Berechtigungen
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung.', 'derleiti-plugin')));
            wp_die();
        }
        
        // Parameter abrufen
        $prompt = isset($_POST['prompt']) ? sanitize_text_field($_POST['prompt']) : '';
        $style = isset($_POST['style']) ? sanitize_text_field($_POST['style']) : 'realistic';
        $size = isset($_POST['size']) ? sanitize_text_field($_POST['size']) : 'medium';
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        
        if (empty($prompt)) {
            wp_send_json_error(array('message' => __('Bitte geben Sie einen Prompt ein.', 'derleiti-plugin')));
            wp_die();
        }
        
        $image = $this->generate_image($prompt, $style, $size, $provider);
        
        if (is_wp_error($image)) {
            wp_send_json_error(array('message' => $image->get_error_message()));
            wp_die();
        }
        
        wp_send_json_success(array(
            'imageUrl' => $image['url'],
            'imageId' => $image['id'],
        ));
        wp_die();
    }
    
    /**
     * Generiere KI-Inhalte
     * 
     * @param string $prompt Der Prompt für die KI
     * @param string $content_type Art des Inhalts (paragraph, list, headline)
     * @param string $tone Tonalität des Inhalts (neutral, formal, freundlich)
     * @param string $length Länge des Inhalts (short, medium, long)
     * @param string $provider Optionaler spezifischer KI-Provider
     * 
     * @return string|WP_Error Generierter Inhalt oder Fehler
     */
    public function generate_content($prompt, $content_type = 'paragraph', $tone = 'neutral', $length = 'medium', $provider = '') {
        // Überprüfe, ob KI-Integrationen aktiviert sind
        if (!$this->is_ai_enabled()) {
            return new WP_Error('ai_disabled', __('KI-Integration ist deaktiviert.', 'derleiti-plugin'));
        }
        
        // Bestimme den zu verwendenden Provider
        if (empty($provider)) {
            $provider = $this->get_active_provider();
        }
        
        // Generiere Systemprompt basierend auf content_type, tone und length
        $system_prompt = $this->generate_system_prompt($content_type, $tone, $length);
        
        // Cache-Schlüssel generieren
        $cache_key = 'derleiti_ai_content_' . md5($prompt . $content_type . $tone . $length . $provider);
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            return $cached_result;
        }
        
        try {
            // Provider-spezifische Generierung
            switch ($provider) {
                case 'openai':
                    $content = $this->generate_content_openai($prompt, $system_prompt);
                    break;
                    
                case 'gemini':
                    $content = $this->generate_content_gemini($prompt, $system_prompt);
                    break;
                    
                case 'anthropic':
                    $content = $this->generate_content_anthropic($prompt, $system_prompt);
                    break;
                    
                case 'mock':
                default:
                    $content = $this->mock_generate_content($prompt, $content_type, $tone, $length);
                    break;
            }
            
            // Speichere im Cache für eine Stunde
            if (!is_wp_error($content)) {
                set_transient($cache_key, $content, HOUR_IN_SECONDS);
            }
            
            return $content;
            
        } catch (Exception $e) {
            return new WP_Error('ai_generation_error', $e->getMessage());
        }
    }
    
    /**
     * Generiere KI-Bilder
     * 
     * @param string $prompt Der Prompt für die KI
     * @param string $style Bildstil (realistic, cartoon, etc.)
     * @param string $size Bildgröße (small, medium, large)
     * @param string $provider Optionaler spezifischer KI-Provider
     * 
     * @return array|WP_Error Generiertes Bild oder Fehler
     */
    public function generate_image($prompt, $style = 'realistic', $size = 'medium', $provider = '') {
        // Überprüfe, ob KI-Integrationen aktiviert sind
        if (!$this->is_ai_enabled()) {
            return new WP_Error('ai_disabled', __('KI-Integration ist deaktiviert.', 'derleiti-plugin'));
        }
        
        // Bestimme den zu verwendenden Provider
        if (empty($provider)) {
            $provider = $this->get_active_provider();
        }
        
        // Prüfe, ob der Provider Bildgenerierung unterstützt
        $providers = $this->get_available_providers();
        if (isset($providers[$provider]) && !in_array('image', $providers[$provider]['features'])) {
            // Fallback zu einem Provider mit Bildunterstützung
            foreach ($providers as $provider_id => $provider_data) {
                if ($provider_data['enabled'] && in_array('image', $provider_data['features'])) {
                    $provider = $provider_id;
                    break;
                }
            }
        }
        
        // Cache-Schlüssel generieren
        $cache_key = 'derleiti_ai_image_' . md5($prompt . $style . $size . $provider);
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            return $cached_result;
        }
        
        try {
            // Provider-spezifische Generierung
            switch ($provider) {
                case 'openai':
                    $image = $this->generate_image_openai($prompt, $style, $size);
                    break;
                    
                case 'gemini':
                    $image = $this->generate_image_gemini($prompt, $style, $size);
                    break;
                    
                case 'stable-diffusion':
                    $image = $this->generate_image_stable_diffusion($prompt, $style, $size);
                    break;
                    
                case 'mock':
                default:
                    $image = $this->mock_generate_image($prompt, $style, $size);
                    break;
            }
            
            // Speichere im Cache für einen Tag
            if (!is_wp_error($image)) {
                set_transient($cache_key, $image, DAY_IN_SECONDS);
            }
            
            return $image;
            
        } catch (Exception $e) {
            return new WP_Error('ai_image_generation_error', $e->getMessage());
        }
    }
    
    /**
     * Generiere Systemprompt basierend auf den Parametern
     */
    private function generate_system_prompt($content_type, $tone, $length) {
        $system_prompt = "Du bist ein hilfreicher KI-Assistent, der Inhalte für eine WordPress-Website generiert. ";
        
        // Content Type
        switch ($content_type) {
            case 'paragraph':
                $system_prompt .= "Generiere einen informativen Absatz ";
                break;
            case 'list':
                $system_prompt .= "Generiere eine übersichtliche Liste mit Stichpunkten ";
                break;
            case 'headline':
                $system_prompt .= "Generiere eine ansprechende Überschrift ";
                break;
            case 'code':
                $system_prompt .= "Generiere gut kommentierten Code ";
                break;
            default:
                $system_prompt .= "Generiere einen informativen Text ";
        }
        
        // Tone
        switch ($tone) {
            case 'formal':
                $system_prompt .= "in einem formellen, professionellen Ton ";
                break;
            case 'casual':
                $system_prompt .= "in einem lockeren, freundlichen Ton ";
                break;
            case 'informative':
                $system_prompt .= "in einem informierenden, sachlichen Ton ";
                break;
            case 'persuasive':
                $system_prompt .= "in einem überzeugenden, werbenden Ton ";
                break;
            default:
                $system_prompt .= "in einem ausgewogenen, neutralen Ton ";
        }
        
        // Length
        switch ($length) {
            case 'short':
                $system_prompt .= "mit kurzer Länge (ca. 50-100 Wörter). ";
                break;
            case 'medium':
                $system_prompt .= "mit mittlerer Länge (ca. 150-250 Wörter). ";
                break;
            case 'long':
                $system_prompt .= "mit ausführlicher Länge (ca. 300-500 Wörter). ";
                break;
            default:
                $system_prompt .= "mit angemessener Länge. ";
        }
        
        $system_prompt .= "Verwende HTML-Formatierung wenn sinnvoll, aber halte es einfach. Antworte nur mit dem generierten Inhalt ohne zusätzliche Einleitungen oder Erklärungen.";
        
        return $system_prompt;
    }
    
    /**
     * Generiere Inhalte mit OpenAI
     */
    private function generate_content_openai($prompt, $system_prompt) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';
        
        // API-Schlüssel abrufen
        $api_key = $wpdb->get_var("SELECT setting_value FROM $table_name WHERE setting_name = 'openai_api_key'");
        
        if (empty($api_key)) {
            return new WP_Error('api_key_missing', __('OpenAI API-Schlüssel fehlt.', 'derleiti-plugin'));
        }
        
        $api_url = 'https://api.openai.com/v1/chat/completions';
        
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        );
        
        $body = json_encode(array(
            'model' => 'gpt-4-turbo-preview', // Aktuellstes Modell verwenden
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $system_prompt,
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt,
                ),
            ),
            'temperature' => 0.7,
            'max_tokens' => 1500,
        ));
        
        $response = wp_remote_post($api_url, array(
            'headers' => $headers,
            'body' => $body,
            'timeout' => 60,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error_message = wp_remote_retrieve_response_message($response);
            return new WP_Error('openai_api_error', sprintf(__('OpenAI API-Fehler: %s', 'derleiti-plugin'), $error_message));
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($response_body['choices'][0]['message']['content'])) {
            return $response_body['choices'][0]['message']['content'];
        } else {
            return new WP_Error('openai_response_error', __('Ungültige Antwort von OpenAI.', 'derleiti-plugin'));
        }
    }
    
    /**
     * Generiere Inhalte mit Google Gemini
     */
    private function generate_content_gemini($prompt, $system_prompt) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';
        
        // API-Schlüssel abrufen
        $api_key = $wpdb->get_var("SELECT setting_value FROM $table_name WHERE setting_name = 'gemini_api_key'");
        
        if (empty($api_key)) {
            return new WP_Error('api_key_missing', __('Google Gemini API-Schlüssel fehlt.', 'derleiti-plugin'));
        }
        
        $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $api_key;
        
        $headers = array(
            'Content-Type' => 'application/json',
        );
        
        $body = json_encode(array(
            'contents' => array(
                array(
                    'role' => 'user',
                    'parts' => array(
                        array(
                            'text' => $system_prompt . "\n\n" . $prompt,
                        ),
                    ),
                ),
            ),
            'generationConfig' => array(
                'temperature' => 0.7,
                'maxOutputTokens' => 1500,
            ),
        ));
        
        $response = wp_remote_post($api_url, array(
            'headers' => $headers,
            'body' => $body,
            'timeout' => 60,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error_message = wp_remote_retrieve_response_message($response);
            return new WP_Error('gemini_api_error', sprintf(__('Google Gemini API-Fehler: %s', 'derleiti-plugin'), $error_message));
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($response_body['candidates'][0]['content']['parts'][0]['text'])) {
            return $response_body['candidates'][0]['content']['parts'][0]['text'];
        } else {
            return new WP_Error('gemini_response_error', __('Ungültige Antwort von Google Gemini.', 'derleiti-plugin'));
        }
    }
    
    /**
     * Generiere Inhalte mit Anthropic Claude
     */
    private function generate_content_anthropic($prompt, $system_prompt) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';
        
        // API-Schlüssel abrufen
        $api_key = $wpdb->get_var("SELECT setting_value FROM $table_name WHERE setting_name = 'anthropic_api_key'");
        
        if (empty($api_key)) {
            return new WP_Error('api_key_missing', __('Anthropic API-Schlüssel fehlt.', 'derleiti-plugin'));
        }
        
        $api_url = 'https://api.anthropic.com/v1/messages';
        
        $headers = array(
            'x-api-key' => $api_key,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01',
        );
        
        $body = json_encode(array(
            'model' => 'claude-3-opus-20240229',
            'system' => $system_prompt,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt,
                ),
            ),
            'max_tokens' => 1500,
        ));
        
        $response = wp_remote_post($api_url, array(
            'headers' => $headers,
            'body' => $body,
            'timeout' => 60,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error_message = wp_remote_retrieve_response_message($response);
            return new WP_Error('anthropic_api_error', sprintf(__('Anthropic API-Fehler: %s', 'derleiti-plugin'), $error_message));
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($response_body['content'][0]['text'])) {
            return $response_body['content'][0]['text'];
        } else {
            return new WP_Error('anthropic_response_error', __('Ungültige Antwort von Anthropic.', 'derleiti-plugin'));
        }
    }
    
    /**
     * Generiere Bilder mit OpenAI
     */
    private function generate_image_openai($prompt, $style, $size) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';
        
        // API-Schlüssel abrufen
        $api_key = $wpdb->get_var("SELECT setting_value FROM $table_name WHERE setting_name = 'openai_api_key'");
        
        if (empty($api_key)) {
            return new WP_Error('api_key_missing', __('OpenAI API-Schlüssel fehlt.', 'derleiti-plugin'));
        }
        
        // Größen-Mapping
        $size_mapping = array(
            'small' => '512x512',
            'medium' => '1024x1024',
            'large' => '1792x1024',
        );
        
        $image_size = isset($size_mapping[$size]) ? $size_mapping[$size] : '1024x1024';
        
        // Style in den Prompt einbauen
        $style_prompt = '';
        switch ($style) {
            case 'realistic':
                $style_prompt = 'Ein fotorealistisches Bild: ';
                break;
            case 'cartoon':
                $style_prompt = 'Im Cartoon-Stil: ';
                break;
            case 'sketch':
                $style_prompt = 'Als Skizze/Zeichnung: ';
                break;
            case 'painting':
                $style_prompt = 'Als künstlerisches Gemälde: ';
                break;
            default:
                $style_prompt = '';
        }
        
        $enhanced_prompt = $style_prompt . $prompt;
        
        $api_url = 'https://api.openai.com/v1/images/generations';
        
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        );
        
        $body = json_encode(array(
            'prompt' => $enhanced_prompt,
            'n' => 1,
            'size' => $image_size,
            'response_format' => 'url',
        ));
        
        $response = wp_remote_post($api_url, array(
            'headers' => $headers,
            'body' => $body,
            'timeout' => 60,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error_message = wp_remote_retrieve_response_message($response);
            return new WP_Error('openai_api_error', sprintf(__('OpenAI API-Fehler: %s', 'derleiti-plugin'), $error_message));
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($response_body['data'][0]['url'])) {
            $image_url = $response_body['data'][0]['url'];
            
            // Bild in die Medienbibliothek importieren
            $image_id = $this->import_image_to_media_library($image_url, $prompt);
            
            if (is_wp_error($image_id)) {
                return $image_id;
            }
            
            return array(
                'url' => wp_get_attachment_url($image_id),
                'id' => $image_id,
            );
        } else {
            return new WP_Error('openai_response_error', __('Ungültige Antwort von OpenAI.', 'derleiti-plugin'));
        }
    }
    
    /**
     * Generiere Bilder mit Google Gemini
     */
    private function generate_image_gemini($prompt, $style, $size) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';
        
        // API-Schlüssel abrufen
        $api_key = $wpdb->get_var("SELECT setting_value FROM $table_name WHERE setting_name = 'gemini_api_key'");
        
        if (empty($api_key)) {
            return new WP_Error('api_key_missing', __('Google Gemini API-Schlüssel fehlt.', 'derleiti-plugin'));
        }
        
        // Gemini unterstützt derzeit keine direkte Bildgenerierung 
        // Diese Funktion wird in einer zukünftigen Version implementiert, sobald es verfügbar ist
        // Bis dahin verwenden wir einen Fallback zum Testen
        
        return new WP_Error('gemini_image_unsupported', 
            __('Bildgenerierung mit Google Gemini wird derzeit noch implementiert. Bitte verwenden Sie einen anderen Anbieter für Bildgenerierung.', 'derleiti-plugin'));
    }
    
    /**
     * Generiere Bilder mit Stable Diffusion
     */
    private function generate_image_stable_diffusion($prompt, $style, $size) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';
        
        // API-Schlüssel abrufen
        $api_key = $wpdb->get_var("SELECT setting_value FROM $table_name WHERE setting_name = 'stable_diffusion_api_key'");
        
        if (empty($api_key)) {
            return new WP_Error('api_key_missing', __('Stable Diffusion API-Schlüssel fehlt.', 'derleiti-plugin'));
        }
        
        // Größen-Mapping
        $size_mapping = array(
            'small' => array('width' => 512, 'height' => 512),
            'medium' => array('width' => 768, 'height' => 768),
            'large' => array('width' => 1024, 'height' => 1024),
        );
        
        $image_size = isset($size_mapping[$size]) ? $size_mapping[$size] : $size_mapping['medium'];
        
        // Style in den Prompt einbauen
        $style_prompt = '';
        switch ($style) {
            case 'realistic':
                $style_prompt = 'realistic, detailed photography, 8k, ';
                break;
            case 'cartoon':
                $style_prompt = 'cartoon style, vibrant colors, ';
                break;
            case 'sketch':
                $style_prompt = 'pencil sketch, black and white, ';
                break;
            case 'painting':
                $style_prompt = 'oil painting, artistic, canvas texture, ';
                break;
            default:
                $style_prompt = '';
        }
        
        $enhanced_prompt = $style_prompt . $prompt;
        
        $api_url = 'https://api.stability.ai/v1/generation/stable-diffusion-xl-1024-v1-0/text-to-image';
        
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        );
        
        $body = json_encode(array(
            'text_prompts' => array(
                array(
                    'text' => $enhanced_prompt,
                    'weight' => 1,
                ),
            ),
            'cfg_scale' => 7,
            'height' => $image_size['height'],
            'width' => $image_size['width'],
            'samples' => 1,
            'steps' => 30,
        ));
        
        $response = wp_remote_post($api_url, array(
            'headers' => $headers,
            'body' => $body,
            'timeout' => 60,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error_message = wp_remote_retrieve_response_message($response);
            return new WP_Error('stable_diffusion_api_error', sprintf(__('Stable Diffusion API-Fehler: %s', 'derleiti-plugin'), $error_message));
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($response_body['artifacts'][0]['base64'])) {
            $base64_image = $response_body['artifacts'][0]['base64'];
            
            // Temporäre Datei erstellen
            $upload_dir = wp_upload_dir();
            $temp_file = $upload_dir['path'] . '/' . uniqid() . '.png';
            
            // Base64 zu Bild konvertieren
            $image_data = base64_decode($base64_image);
            file_put_contents($temp_file, $image_data);
            
            // Bild in die Medienbibliothek importieren
            $file_array = array(
                'name'     => sanitize_file_name($prompt) . '.png',
                'tmp_name' => $temp_file,
                'error'    => 0,
                'size'     => filesize($temp_file),
            );
            
            $image_id = media_handle_sideload($file_array, 0);
            
            // Temporäre Datei löschen
            @unlink($temp_file);
            
            if (is_wp_error($image_id)) {
                return $image_id;
            }
            
            // Metadaten hinzufügen
            update_post_meta($image_id, '_derleiti_ai_generated', true);
            update_post_meta($image_id, '_derleiti_ai_prompt', $prompt);
            update_post_meta($image_id, '_derleiti_ai_provider', 'stable-diffusion');
            
            return array(
                'url' => wp_get_attachment_url($image_id),
                'id' => $image_id,
            );
        } else {
            return new WP_Error('stable_diffusion_response_error', __('Ungültige Antwort von Stable Diffusion.', 'derleiti-plugin'));
        }
    }
    
    /**
     * Importiere ein Bild in die Medienbibliothek
     */
    private function import_image_to_media_library($image_url, $prompt) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Lade das Bild herunter
        $temp_file = download_url($image_url);
        
        if (is_wp_error($temp_file)) {
            return $temp_file;
        }
        
        // Bestimme den Dateityp
        $file_info = wp_check_filetype(basename($image_url), null);
        $file_ext = empty($file_info['ext']) ? 'png' : $file_info['ext'];
        
        $file_array = array(
            'name'     => sanitize_file_name($prompt) . '.' . $file_ext,
            'tmp_name' => $temp_file,
            'error'    => 0,
            'size'     => filesize($temp_file),
        );
        
        // Füge das Bild hinzu
        $image_id = media_handle_sideload($file_array, 0);
        
        // Temporäre Datei löschen
        @unlink($temp_file);
        
        if (is_wp_error($image_id)) {
            return $image_id;
        }
        
        // Metadaten hinzufügen
        update_post_meta($image_id, '_derleiti_ai_generated', true);
        update_post_meta($image_id, '_derleiti_ai_prompt', $prompt);
        
        return $image_id;
    }
    
    /**
     * Mock-Implementierung für Inhaltsgenerierung (Testzwecke)
     */
    private function mock_generate_content($prompt, $content_type, $tone, $length) {
        // Lege Länge basierend auf Einstellung fest
        $paragraph_count = 1;
        switch ($length) {
            case 'short':
                $paragraph_count = 1;
                break;
            case 'medium':
                $paragraph_count = 2;
                break;
            case 'long':
                $paragraph_count = 4;
                break;
        }
        
        // Generiere Mock-Inhalt
        $content = '';
        
        switch ($content_type) {
            case 'paragraph':
                for ($i = 0; $i < $paragraph_count; $i++) {
                    $content .= '<p>' . sprintf(
                        __('Dies ist ein KI-generierter Absatz zum Thema "%s" im %s Ton. Der Inhalt ist für Testzwecke generiert und würde normalerweise durch eine echte KI-Integration ersetzt werden.', 'derleiti-plugin'),
                        $prompt,
                        $tone
                    ) . '</p>';
                }
                break;
                
            case 'list':
                $content .= '<ul>';
                for ($i = 0; $i < $paragraph_count * 3; $i++) {
                    $content .= '<li>' . sprintf(
                        __('Punkt %d zum Thema "%s"', 'derleiti-plugin'),
                        $i + 1,
                        $prompt
                    ) . '</li>';
                }
                $content .= '</ul>';
                break;
                
            case 'headline':
                $content .= '<h2>' . sprintf(
                    __('%s: Eine KI-generierte Überschrift', 'derleiti-plugin'),
                    $prompt
                ) . '</h2>';
                break;
                
            case 'code':
                $content .= '<pre><code>';
                $content .= "// Beispielcode zum Thema \"$prompt\"\n";
                $content .= "function processData() {\n";
                $content .= "    // Dies ist ein Platzhalter für tatsächlichen Code\n";
                $content .= "    console.log('Verarbeite Daten zu: $prompt');\n";
                $content .= "    return 'Ergebnis';\n";
                $content .= "}\n";
                $content .= '</code></pre>';
                break;
                
            default:
                $content .= '<p>' . __('Inhaltstyp nicht unterstützt.', 'derleiti-plugin') . '</p>';
        }
        
        return $content;
    }
    
    /**
     * Mock-Implementierung für Bildgenerierung (Testzwecke)
     */
    private function mock_generate_image($prompt, $style, $size) {
        // Lege Größe basierend auf Einstellung fest
        $width = 600;
        $height = 400;
        
        switch ($size) {
            case 'small':
                $width = 300;
                $height = 200;
                break;
            case 'medium':
                $width = 600;
                $height = 400;
                break;
            case 'large':
                $width = 1200;
                $height = 800;
                break;
        }
        
        // Für Testzwecke: Verwende Placeholder-Bild
        $image_url = "https://via.placeholder.com/{$width}x{$height}.png?text=" . urlencode($prompt);
        
        // In einer echten Implementierung würdest du hier das Bild in die Medienbibliothek hochladen
        // und die Bild-ID zurückgeben
        
        return array(
            'url' => $image_url,
            'id' => 0, // Platzhalter-ID
        );
    }
    
    /**
     * Überprüfe, ob KI-Integration aktiviert ist
     */
    private function is_ai_enabled() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';
        
        $ai_enabled = $wpdb->get_var("SELECT setting_value FROM $table_name WHERE setting_name = 'ai_enabled'");
        
        return $ai_enabled == '1';
    }
    
    /**
     * Shortcode für KI-Inhalte
     */
    public function ai_content_shortcode($atts) {
        $atts = shortcode_atts(array(
            'prompt' => '',
            'type' => 'paragraph',
            'tone' => 'neutral',
            'length' => 'medium',
            'provider' => '',
        ), $atts, 'derleiti_ai_content');
        
        if (empty($atts['prompt'])) {
            return '<p><em>' . __('Bitte geben Sie einen Prompt an.', 'derleiti-plugin') . '</em></p>';
        }
        
        $content = $this->generate_content($atts['prompt'], $atts['type'], $atts['tone'], $atts['length'], $atts['provider']);
        
        if (is_wp_error($content)) {
            return '<p><em>' . $content->get_error_message() . '</em></p>';
        }
        
        return '<div class="derleiti-ai-block">' . $content . '</div>';
    }
    
    /**
     * Filter für automatische Inhaltsverbesserung
     */
    public function enhance_content_with_ai($content) {
        // Überprüfe, ob KI-Integration für den aktuellen Beitrag aktiviert ist
        if (is_singular() && !is_admin()) {
            $post_id = get_the_ID();
            $enable_ai = get_post_meta($post_id, '_derleiti_enable_ai', true);
            
            if ($enable_ai == '1') {
                // Hier könntest du automatische Verbesserungen hinzufügen
                // z.B. automatische Zusammenfassungen, verwandte Links, etc.
                
                // Beispiel: Füge eine automatisch generierte Zusammenfassung am Anfang hinzu
                $post_title = get_the_title();
                $summary = $this->get_post_summary($post_id, $post_title);
                
                if (!empty($summary) && !is_wp_error($summary)) {
                    $summary_html = '<div class="derleiti-ai-summary">';
                    $summary_html .= '<h4>' . __('Zusammenfassung', 'derleiti-plugin') . '</h4>';
                    $summary_html .= $summary;
                    $summary_html .= '</div>';
                    
                    $content = $summary_html . $content;
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Erhalte eine automatisch generierte Zusammenfassung für einen Beitrag
     */
    private function get_post_summary($post_id, $post_title) {
        // Überprüfe, ob bereits eine Zusammenfassung im Cache ist
        $summary = get_transient('derleiti_ai_summary_' . $post_id);
        
        if (false === $summary) {
            // Generiere eine neue Zusammenfassung
            $summary = $this->generate_content(
                sprintf(__('Kurze Zusammenfassung von "%s"', 'derleiti-plugin'), $post_title),
                'paragraph',
                'informative',
                'short'
            );
            
            // Speichere im Cache für 7 Tage
            if (!is_wp_error($summary)) {
                set_transient('derleiti_ai_summary_' . $post_id, $summary, 7 * DAY_IN_SECONDS);
            }
        }
        
        return $summary;
    }
    
    /**
     * Lade KI-Assistenten-Skripte für den Editor
     */
    public function enqueue_ai_assistant_scripts() {
        // Lade KI-Assistenten-Plugin für den Block-Editor
        wp_enqueue_script(
            'derleiti-ai-assistant',
            DERLEITI_PLUGIN_URL . 'js/ai-assistant.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-compose', 'wp-plugins', 'wp-edit-post'),
            DERLEITI_PLUGIN_VERSION,
            true
        );
        
        // Lokalisiere Skript
        wp_localize_script(
            'derleiti-ai-assistant',
            'derleitiAiData',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'restUrl' => esc_url_raw(rest_url('derleiti-plugin/v1/')),
                'nonce' => wp_create_nonce('derleiti_ai_nonce'),
                'wpNonce' => wp_create_nonce('wp_rest'),
                'providers' => $this->get_available_providers(),
                'activeProvider' => $this->get_active_provider(),
                'strings' => array(
                    'aiAssistant' => __('KI-Assistent', 'derleiti-plugin'),
                    'generateContent' => __('Inhalt generieren', 'derleiti-plugin'),
                    'generateImage' => __('Bild generieren', 'derleiti-plugin'),
                    'prompt' => __('Prompt', 'derleiti-plugin'),
                    'contentType' => __('Inhaltstyp', 'derleiti-plugin'),
                    'tone' => __('Tonalität', 'derleiti-plugin'),
                    'length' => __('Länge', 'derleiti-plugin'),
                    'style' => __('Stil', 'derleiti-plugin'),
                    'size' => __('Größe', 'derleiti-plugin'),
                    'provider' => __('KI-Anbieter', 'derleiti-plugin'),
                    'generate' => __('Generieren', 'derleiti-plugin'),
                    'cancel' => __('Abbrechen', 'derleiti-plugin'),
                    'generating' => __('Generiere...', 'derleiti-plugin'),
                    'insertContent' => __('Inhalt einfügen', 'derleiti-plugin'),
                    'insertImage' => __('Bild einfügen', 'derleiti-plugin'),
                    'error' => __('Fehler', 'derleiti-plugin'),
                    'noProviders' => __('Keine KI-Anbieter verfügbar. Bitte konfigurieren Sie Ihre API-Schlüssel in den Plugin-Einstellungen.', 'derleiti-plugin'),
                )
            )
        );
    }
    
    /**
     * Geplante Inhaltsoptimierung
     */
    public function scheduled_content_optimization() {
        // Diese Funktion wird täglich ausgeführt, wenn die Cron-Jobs aktiviert sind
        
        // 1. Finde Beiträge, die optimiert werden sollen
        $posts_to_optimize = get_posts(array(
            'post_type' => 'post',
            'meta_query' => array(
                array(
                    'key' => '_derleiti_enable_ai',
                    'value' => '1',
                    'compare' => '=',
                ),
                array(
                    'key' => '_derleiti_last_ai_optimization',
                    'value' => strtotime('-7 days'),
                    'compare' => '<',
                    'type' => 'NUMERIC',
                ),
            ),
            'posts_per_page' => 5,
        ));
        
        foreach ($posts_to_optimize as $post) {
            // 2. Optimiere Beitrag (z.B. aktualisiere die Zusammenfassung)
            $this->optimize_post_content($post->ID);
            
            // 3. Aktualisiere Zeitstempel
            update_post_meta($post->ID, '_derleiti_last_ai_optimization', time());
        }
    }
    
    /**
     * Optimiere den Inhalt eines Beitrags
     */
    private function optimize_post_content($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return false;
        }
        
        // Aktualisiere die Zusammenfassung
        $summary = $this->generate_content(
            sprintf(__('Erstelle eine kurze Zusammenfassung des Artikels mit dem Titel "%s"', 'derleiti-plugin'), $post->post_title),
            'paragraph',
            'informative',
            'short'
        );
        
        if (!is_wp_error($summary)) {
            // Speichere die aktualisierte Zusammenfassung
            update_post_meta($post_id, '_derleiti_ai_summary', $summary);
            set_transient('derleiti_ai_summary_' . $post_id, $summary, 7 * DAY_IN_SECONDS);
            return true;
        }
        
        return false;
    }
}
