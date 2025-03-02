<?php
/**
 * Verwaltet zusätzliche Theme-Tools und Utilities
 *
 * @package Derleiti_Plugin
 * @subpackage Tools
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Die Tools-Klasse des Plugins
 */
class Derleiti_Tools {
    
    /**
     * Initialisiere die Tools-Klasse
     */
    public function init() {
        // Performance-Optimierungen
        add_action('wp_enqueue_scripts', array($this, 'optimize_scripts'), 999);
        
        // Erweiterte Bildverarbeitung
        add_filter('wp_handle_upload', array($this, 'process_uploaded_images'));
        add_filter('wp_generate_attachment_metadata', array($this, 'optimize_attachment_metadata'), 10, 2);
        
        // Benutzerdefinierte Schriftarten
        add_action('wp_enqueue_scripts', array($this, 'enqueue_custom_fonts'));
        
        // Shortcodes für Tools
        add_shortcode('derleiti_social_share', array($this, 'social_share_shortcode'));
        add_shortcode('derleiti_responsive_video', array($this, 'responsive_video_shortcode'));
        add_shortcode('derleiti_button', array($this, 'button_shortcode'));
        
        // SEO-Verbesserungen
        add_action('wp_head', array($this, 'add_seo_meta_tags'), 1);
        
        // Schema.org Markup
        add_filter('the_content', array($this, 'add_schema_markup'));
        
        // Exportfunktionen
        add_action('admin_post_derleiti_export_theme_settings', array($this, 'export_theme_settings'));
        add_action('admin_post_derleiti_import_theme_settings', array($this, 'import_theme_settings'));
        
        // Wartungsarbeiten
        add_action('admin_post_derleiti_maintenance_tasks', array($this, 'run_maintenance_tasks'));
        
        // Cache-Management
        add_action('admin_post_derleiti_clear_cache', array($this, 'clear_plugin_cache'));
    }
    
    /**
     * Optimiere Skripte und Styles
     */
    public function optimize_scripts() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';
        
        $performance_optimization = $wpdb->get_var("SELECT setting_value FROM $table_name WHERE setting_name = 'performance_optimization'");
        
        if ($performance_optimization == '1') {
            // Entferne Emoji-Skripte
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('admin_print_styles', 'print_emoji_styles');
            
            // Entferne oEmbed
            remove_action('wp_head', 'wp_oembed_add_discovery_links');
            remove_action('wp_head', 'wp_oembed_add_host_js');
            
            // Entferne REST API Link
            remove_action('wp_head', 'rest_output_link_wp_head');
            
            // Entferne WP Version
            remove_action('wp_head', 'wp_generator');
            
            // Entferne RSD Link
            remove_action('wp_head', 'rsd_link');
            
            // Entferne wlwmanifest Link
            remove_action('wp_head', 'wlwmanifest_link');
            
            // Entferne Shortlink
            remove_action('wp_head', 'wp_shortlink_wp_head');
            
            // Füge preload, preconnect und dns-prefetch hinzu
            add_action('wp_head', array($this, 'add_resource_hints'), 2);
            
            // Deaktiviere jQuery Migrate im Frontend
            if (!is_admin() && !is_customize_preview()) {
                add_action('wp_default_scripts', function($scripts) {
                    if (isset($scripts->registered['jquery'])) {
                        $script = $scripts->registered['jquery'];
                        
                        if ($script->deps) {
                            $script->deps = array_diff($script->deps, array('jquery-migrate'));
                        }
                    }
                });
            }
        }
    }
    
    /**
     * Füge Resource Hints hinzu (preload, preconnect, dns-prefetch)
     */
    public function add_resource_hints() {
        // Füge kritische Preloads hinzu
        echo '<link rel="preload" href="' . get_stylesheet_uri() . '" as="style">' . "\n";
        
        // Preconnect zu Google Fonts
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
        
        // DNS-Prefetch für häufig genutzte externe Dienste
        echo '<link rel="dns-prefetch" href="//www.google-analytics.com">' . "\n";
        echo '<link rel="dns-prefetch" href="//cdn.jsdelivr.net">' . "\n";
    }
    
    /**
     * Verarbeite hochgeladene Bilder
     */
    public function process_uploaded_images($file) {
        // Überprüfe, ob es sich um ein Bild handelt
        $filetype = wp_check_filetype($file['file']);
        $image_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        
        if (in_array($filetype['type'], $image_types)) {
            // Hier könntest du automatische Bildverarbeitung durchführen
            // Beispiel: Komprimierung, Wasserzeichen, etc.
            
            // In diesem Beispiel führen wir keine tatsächliche Verarbeitung durch
            // In einer echten Implementierung würdest du hier Bildbearbeitungsbibliotheken verwenden
        }
        
        return $file;
    }
    
    /**
     * Optimiere Attachment-Metadaten
     */
    public function optimize_attachment_metadata($metadata, $attachment_id) {
        // Hier könntest du zusätzliche Metadaten hinzufügen oder ändern
        // Beispiel: Zusätzliche Bildgrößen, WebP-Konvertierung, etc.
        
        return $metadata;
    }
    
    /**
     * Lade benutzerdefinierte Schriftarten
     */
    public function enqueue_custom_fonts() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';
        
        // Überprüfe, ob benutzerdefinierte Schriftarten aktiviert sind
        $custom_fonts_enabled = $wpdb->get_var("SELECT setting_value FROM $table_name WHERE setting_name = 'custom_fonts_enabled'");
        
        if ($custom_fonts_enabled == '1') {
            // Benutzerdefinierte Schriftarten einbinden
            // Beispiel: Google Fonts
            wp_enqueue_style('derleiti-google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', array(), null);
        }
    }
    
    /**
     * Shortcode für Social Media Sharing
     */
    public function social_share_shortcode($atts) {
        $atts = shortcode_atts(array(
            'networks' => 'facebook,twitter,linkedin,pinterest',
            'title' => get_the_title(),
            'url' => get_permalink(),
            'style' => 'buttons',
        ), $atts, 'derleiti_social_share');
        
        $networks = explode(',', $atts['networks']);
        $html = '<div class="derleiti-social-share derleiti-social-share-' . esc_attr($atts['style']) . '">';
        
        foreach ($networks as $network) {
            $network = trim($network);
            $share_url = '';
            $icon = '';
            $label = '';
            
            switch ($network) {
                case 'facebook':
                    $share_url = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($atts['url']);
                    $icon = 'facebook';
                    $label = __('Facebook', 'derleiti-plugin');
                    break;
                case 'twitter':
                    $share_url = 'https://twitter.com/intent/tweet?url=' . urlencode($atts['url']) . '&text=' . urlencode($atts['title']);
                    $icon = 'twitter';
                    $label = __('Twitter', 'derleiti-plugin');
                    break;
                case 'linkedin':
                    $share_url = 'https://www.linkedin.com/shareArticle?mini=true&url=' . urlencode($atts['url']) . '&title=' . urlencode($atts['title']);
                    $icon = 'linkedin';
                    $label = __('LinkedIn', 'derleiti-plugin');
                    break;
                case 'pinterest':
                    $image = '';
                    if (has_post_thumbnail()) {
                        $image_url = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');
                        if ($image_url) {
                            $image = $image_url[0];
                        }
                    }
                    $share_url = 'https://pinterest.com/pin/create/button/?url=' . urlencode($atts['url']) . '&media=' . urlencode($image) . '&description=' . urlencode($atts['title']);
                    $icon = 'pinterest';
                    $label = __('Pinterest', 'derleiti-plugin');
                    break;
                case 'whatsapp':
                    $share_url = 'https://api.whatsapp.com/send?text=' . urlencode($atts['title'] . ' ' . $atts['url']);
                    $icon = 'whatsapp';
                    $label = __('WhatsApp', 'derleiti-plugin');
                    break;
                case 'email':
                    $share_url = 'mailto:?subject=' . urlencode($atts['title']) . '&body=' . urlencode($atts['url']);
                    $icon = 'email';
                    $label = __('E-Mail', 'derleiti-plugin');
                    break;
            }
            
            if (!empty($share_url)) {
                $html .= '<a href="' . esc_url($share_url) . '" class="derleiti-social-share-' . esc_attr($network) . '" target="_blank" rel="noopener noreferrer" title="' . esc_attr(sprintf(__('Teilen auf %s', 'derleiti-plugin'), $label)) . '">';
                $html .= '<span class="derleiti-social-share-icon derleiti-social-share-icon-' . esc_attr($icon) . '"></span>';
                if ($atts['style'] === 'buttons' || $atts['style'] === 'text') {
                    $html .= '<span class="derleiti-social-share-label">' . esc_html($label) . '</span>';
                }
                $html .= '</a>';
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Shortcode für responsive Videos
     */
    public function responsive_video_shortcode($atts) {
        $atts = shortcode_atts(array(
            'url' => '',
            'width' => 16,
            'height' => 9,
        ), $atts, 'derleiti_responsive_video');
        
        if (empty($atts['url'])) {
            return '<p><em>' . __('Bitte geben Sie eine Video-URL an.', 'derleiti-plugin') . '</em></p>';
        }
        
        $video_id = '';
        $video_type = '';
        
        // YouTube
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $atts['url'], $matches) || preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/', $atts['url'], $matches)) {
            $video_id = $matches[1];
            $video_type = 'youtube';
        }
        // Vimeo
        elseif (preg_match('/vimeo\.com\/([0-9]+)/', $atts['url'], $matches)) {
            $video_id = $matches[1];
            $video_type = 'vimeo';
        }
        
        if (empty($video_id)) {
            return '<p><em>' . __('Ungültige Video-URL. Unterstützt werden YouTube und Vimeo.', 'derleiti-plugin') . '</em></p>';
        }
        
        $aspect_ratio = ($atts['height'] / $atts['width']) * 100;
        
        $html = '<div class="derleiti-responsive-video" style="position: relative; padding-bottom: ' . esc_attr($aspect_ratio) . '%; height: 0; overflow: hidden;">';
        
        if ($video_type === 'youtube') {
            $html .= '<iframe src="https://www.youtube.com/embed/' . esc_attr($video_id) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></iframe>';
        } elseif ($video_type === 'vimeo') {
            $html .= '<iframe src="https://player.vimeo.com/video/' . esc_attr($video_id) . '" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></iframe>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Shortcode für Buttons
     */
    public function button_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'url' => '#',
            'style' => 'primary',
            'size' => 'medium',
            'icon' => '',
            'target' => '_self',
            'rel' => '',
            'full_width' => 'no',
            'align' => 'left',
        ), $atts, 'derleiti_button');
        
        $class = 'derleiti-button';
        $class .= ' derleiti-button-' . esc_attr($atts['style']);
        $class .= ' derleiti-button-' . esc_attr($atts['size']);
        
        if ($atts['full_width'] === 'yes') {
            $class .= ' derleiti-button-full-width';
        }
        
        $style = '';
        if ($atts['align'] !== 'left' && $atts['full_width'] !== 'yes') {
            $style = ' style="text-align: ' . esc_attr($atts['align']) . ';"';
        }
        
        $target = '';
        if ($atts['target'] === '_blank') {
            $target = ' target="_blank"';
        }
        
        $rel = '';
        if (!empty($atts['rel'])) {
            $rel = ' rel="' . esc_attr($atts['rel']) . '"';
        } elseif ($atts['target'] === '_blank') {
            $rel = ' rel="noopener noreferrer"';
        }
        
        $html = '<div class="derleiti-button-wrap"' . $style . '>';
        $html .= '<a href="' . esc_url($atts['url']) . '" class="' . esc_attr($class) . '"' . $target . $rel . '>';
        
        if (!empty($atts['icon'])) {
            $html .= '<span class="derleiti-button-icon ' . esc_attr($atts['icon']) . '"></span>';
        }
        
        $html .= '<span class="derleiti-button-text">' . do_shortcode($content) . '</span>';
        $html .= '</a>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Füge SEO Meta-Tags hinzu
     */
    public function add_seo_meta_tags() {
        if (is_singular()) {
            global $post;
            
            // Canonical URL
            echo '<link rel="canonical" href="' . esc_url(get_permalink($post)) . '">' . "\n";
            
            // Meta Description
            $description = '';
            if (has_excerpt($post->ID)) {
                $description = get_the_excerpt($post->ID);
            } else {
                $content = get_post_field('post_content', $post->ID);
                $content = strip_shortcodes($content);
                $content = strip_tags($content);
                $words = explode(' ', $content, 30);
                if (count($words) >= 30) {
                    array_pop($words);
                    $content = implode(' ', $words) . '...';
                }
                $description = $content;
            }
            
            if (!empty($description)) {
                echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
            }
            
            // Open Graph Tags
            echo '<meta property="og:title" content="' . esc_attr(get_the_title($post->ID)) . '">' . "\n";
            echo '<meta property="og:type" content="article">' . "\n";
            echo '<meta property="og:url" content="' . esc_url(get_permalink($post)) . '">' . "\n";
            
            if (!empty($description)) {
                echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
            }
            
            if (has_post_thumbnail($post->ID)) {
                $image = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
                if ($image) {
                    echo '<meta property="og:image" content="' . esc_url($image[0]) . '">' . "\n";
                    echo '<meta property="og:image:width" content="' . esc_attr($image[1]) . '">' . "\n";
                    echo '<meta property="og:image:height" content="' . esc_attr($image[2]) . '">' . "\n";
                }
            }
            
            // Twitter Card Tags
            echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
            echo '<meta name="twitter:title" content="' . esc_attr(get_the_title($post->ID)) . '">' . "\n";
            
            if (!empty($description)) {
                echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
            }
            
            if (has_post_thumbnail($post->ID)) {
                $image = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
                if ($image) {
                    echo '<meta name="twitter:image" content="' . esc_url($image[0]) . '">' . "\n";
                }
            }
        }
    }
    
    /**
     * Füge Schema.org Markup hinzu
     */
    public function add_schema_markup($content) {
        if (is_singular('post')) {
            global $post;
            
            // Sammle Daten für Schema.org Markup
            $schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => get_the_title(),
                'datePublished' => get_the_date('c'),
                'dateModified' => get_the_modified_date('c'),
                'author' => array(
                    '@type' => 'Person',
                    'name' => get_the_author(),
                ),
                'publisher' => array(
                    '@type' => 'Organization',
                    'name' => get_bloginfo('name'),
                    'logo' => array(
                        '@type' => 'ImageObject',
                        'url' => get_site_icon_url(),
                    ),
                ),
            );
            
            // Füge Beitragsbild hinzu, wenn vorhanden
            if (has_post_thumbnail()) {
                $image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');
                if ($image) {
                    $schema['image'] = array(
                        '@type' => 'ImageObject',
                        'url' => $image[0],
                        'width' => $image[1],
                        'height' => $image[2],
                    );
                }
            }
            
            // Generiere JSON-LD und füge es zum Inhalt hinzu
            $json_ld = '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
            
            return $content . $json_ld;
        }
        
        return $content;
    }
    
    /**
     * Exportiere Theme-Einstellungen
     */
    public function export_theme_settings() {
        // Überprüfe Berechtigungen
        if (!current_user_can('manage_options')) {
            wp_die(__('Sie haben keine ausreichenden Berechtigungen, um diese Seite aufzurufen.', 'derleiti-plugin'));
        }
        
        // Überprüfe Nonce
        check_admin_referer('derleiti_export_theme_settings', 'derleiti_export_nonce');
        
        // Hole alle Einstellungen aus der Datenbank
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';
        
        $settings = $wpdb->get_results("SELECT setting_name, setting_value FROM $table_name", ARRAY_A);
        
        $export_data = array(
            'settings' => $settings,
            'timestamp' => current_time('mysql'),
            'site_url' => get_site_url(),
            'version' => DERLEITI_PLUGIN_VERSION,
        );
        
        // Setze Header für Download
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=derleiti-theme-settings-' . date('Y-m-d') . '.json');
        header('Pragma: no-cache');
        
        // Ausgabe der JSON-Daten
        echo wp_json_encode($export_data);
        exit;
    }
    
    /**
     * Importiere Theme-Einstellungen
     */
    public function import_theme_settings() {
        // Überprüfe Berechtigungen
        if (!current_user_can('manage_options')) {
            wp_die(__('Sie haben keine ausreichenden Berechtigungen, um diese Seite aufzurufen.', 'derleiti-plugin'));
        }
        
        // Überprüfe Nonce
        check_admin_referer('derleiti_import_theme_settings', 'derleiti_import_nonce');
        
        // Überprüfe, ob eine Datei hochgeladen wurde
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(admin_url('admin.php?page=derleiti-plugin&tab=tools&error=1'));
            exit;
        }
        
        // Lese JSON-Datei
        $file_content = file_get_contents($_FILES['import_file']['tmp_name']);
        $import_data = json_decode($file_content, true);
        
        // Überprüfe Datenformat
        if (!$import_data || !isset($import_data['settings']) || !is_array($import_data['settings'])) {
            wp_redirect(admin_url('admin.php?page=derleiti-plugin&tab=tools&error=2'));
            exit;
        }
        
        // Importiere Einstellungen
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';
        
        foreach ($import_data['settings'] as $setting) {
            $wpdb->replace(
                $table_name,
                array(
                    'setting_name' => $setting['setting_name'],
                    'setting_value' => $setting['setting_value'],
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s')
            );
        }
        
        // Leere Cache
        $this->clear_plugin_cache();
        
        // Weiterleitung mit Erfolgsmeldung
        wp_redirect(admin_url('admin.php?page=derleiti-plugin&tab=tools&imported=1'));
        exit;
    }
    
    /**
     * Führe Wartungsarbeiten aus
     */
    public function run_maintenance_tasks() {
        // Überprüfe Berechtigungen
        if (!current_user_can('manage_options')) {
            wp_die(__('Sie haben keine ausreichenden Berechtigungen, um diese Seite aufzurufen.', 'derleiti-plugin'));
        }
        
        // Überprüfe Nonce
        check_admin_referer('derleiti_maintenance_tasks', 'derleiti_maintenance_nonce');
        
        // Lösche alte Transients
        $this->delete_expired_transients();
        
        // Optimiere Datenbanktabellen
        $this->optimize_database_tables();
        
        // Lösche unbenutzte Medien
        $this->cleanup_unused_media();
        
        // Weiterleitung mit Erfolgsmeldung
        wp_redirect(admin_url('admin.php?page=derleiti-plugin&tab=tools&maintenance=1'));
        exit;
    }
    
    /**
     * Lösche abgelaufene Transients
     */
    private function delete_expired_transients() {
        global $wpdb;
        
        // Lösche abgelaufene Transients
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_%' AND option_value < " . time());
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_%' AND option_name NOT LIKE '%_transient_timeout_%' AND option_name NOT IN (SELECT CONCAT('_transient_', SUBSTRING(option_name, 20)) FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_%')");
        
        // Lösche abgelaufene Site-Transients in Multisite
        if (is_multisite()) {
            $wpdb->query("DELETE FROM $wpdb->sitemeta WHERE meta_key LIKE '%_site_transient_timeout_%' AND meta_value < " . time());
            $wpdb->query("DELETE FROM $wpdb->sitemeta WHERE meta_key LIKE '%_site_transient_%' AND meta_key NOT LIKE '%_site_transient_timeout_%' AND meta_key NOT IN (SELECT CONCAT('_site_transient_', SUBSTRING(meta_key, 25)) FROM $wpdb->sitemeta WHERE meta_key LIKE '%_site_transient_timeout_%')");
        }
    }
    
    /**
     * Optimiere Datenbanktabellen
     */
    private function optimize_database_tables() {
        global $wpdb;
        
        // Optimiere Plugin-Tabellen
        $table_name = $wpdb->prefix . 'derleiti_settings';
        $wpdb->query("OPTIMIZE TABLE $table_name");
    }
    
    /**
     * Bereinige unbenutzte Medien
     */
    private function cleanup_unused_media() {
        // In einer echten Implementierung würdest du hier unbenutzte Medien identifizieren und bereinigen
        // Dies erfordert jedoch eine umfangreiche Analyse und sollte vorsichtig implementiert werden
    }
    
    /**
     * Leere Plugin-Cache
     */
    public function clear_plugin_cache() {
        // Überprüfe Berechtigungen, wenn von Admin aufgerufen
        if (isset($_GET['_wpnonce'])) {
            if (!current_user_can('manage_options')) {
                wp_die(__('Sie haben keine ausreichenden Berechtigungen, um diese Seite aufzurufen.', 'derleiti-plugin'));
            }
            
            // Überprüfe Nonce
            check_admin_referer('derleiti_clear_cache');
        }
        
        // Lösche transients
        delete_transient('derleiti_plugin_cache');
        
        // Lösche alle Transients, die mit dem Plugin beginnen
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_derleiti_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_derleiti_%'");
        
        // Lösche Cache-Dateien
        $cache_dir = WP_CONTENT_DIR . '/cache/derleiti-plugin';
        
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        
        // Wenn von Admin aufgerufen, Weiterleitung mit Erfolgsmeldung
        if (isset($_GET['_wpnonce'])) {
            wp_redirect(admin_url('admin.php?page=derleiti-plugin&tab=tools&cache=1'));
            exit;
        }
    }
}
