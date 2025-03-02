<?php
/**
 * Verwaltet die KI-Integration-Funktionen
 *
 * @package Derleiti_Plugin
 * @subpackage AI_Integration
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
            ),
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
        
        $content = $this->generate_content($prompt, $content_type, $tone, $length);
        
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
        
        $image = $this->generate_image($prompt, $style, $size);
        
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
        
        if (empty($prompt)) {
            wp_send_json_error(array('message' => __('Bitte geben Sie einen Prompt ein.', 'derleiti-plugin')));
            wp_die();
        }
        
        $content = $this->generate_content($prompt, $content_type, $tone, $length);
        
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
        
        if (empty($prompt)) {
            wp_send_json_error(array('message' => __('Bitte geben Sie einen Prompt ein.', 'derleiti-plugin')));
            wp_die();
        }
        
        $image = $this->generate_image($prompt, $style, $size);
        
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
     */
    private function generate_content($prompt, $content_type = 'paragraph', $tone = 'neutral', $length = 'medium') {
        // Überprüfe, ob KI-Integrationen aktiviert sind
        if (!$this->is_ai_enabled()) {
            return new WP_Error('ai_disabled', __('KI-Integration ist deaktiviert.', 'derleiti-plugin'));
        }
        
        // Hier kannst du verschiedene KI-Dienste integrieren
        // Beispiel: OpenAI (du benötigst einen gültigen API-Schlüssel)
        
        // Mock-Implementierung für Testzwecke
        $content = $this->mock_generate_content($prompt, $content_type, $tone, $length);
        
        return $content;
    }
    
    /**
     * Generiere KI-Bilder
     */
    private function generate_image($prompt, $style = 'realistic', $size = 'medium') {
        // Überprüfe, ob KI-Integrationen aktiviert sind
        if (!$this->is_ai_enabled()) {
            return new WP_Error('ai_disabled', __('KI-Integration ist deaktiviert.', 'derleiti-plugin'));
        }
        
        // Hier kannst du verschiedene KI-Dienste integrieren
        // Beispiel: DALL-E, Midjourney API, Stable Diffusion API, etc.
        
        // Mock-Implementierung für Testzwecke
        $image = $this->mock_generate_image($prompt, $style, $size);
        
        return $image;
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
        ), $atts, 'derleiti_ai_content');
        
        if (empty($atts['prompt'])) {
            return '<p><em>' . __('Bitte geben Sie einen Prompt an.', 'derleiti-plugin') . '</em></p>';
        }
        
        $content = $this->generate_content($atts['prompt'], $atts['type'], $atts['tone'], $atts['length']);
        
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
                    'generate' => __('Generieren', 'derleiti-plugin'),
                    'cancel' => __('Abbrechen', 'derleiti-plugin'),
                    'generating' => __('Generiere...', 'derleiti-plugin'),
                    'insertContent' => __('Inhalt einfügen', 'derleiti-plugin'),
                    'insertImage' => __('Bild einfügen', 'derleiti-plugin'),
                    'error' => __('Fehler', 'derleiti-plugin'),
                )
            )
        );
    }
}
