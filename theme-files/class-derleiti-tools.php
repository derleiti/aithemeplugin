<?php
/**
 * Enhanced Theme Tools and Utilities
 *
 * @package Derleiti_Plugin
 * @subpackage Tools
 * @version 1.3.0
 */

// Prevent direct access
defined('ABSPATH') || exit;

class Derleiti_Tools {
    // Logging and security constants
    private const LOG_DIRECTORY = WP_CONTENT_DIR . '/derleiti-logs/tools/';
    private const MAX_FILE_SIZE = 10485760; // 10MB
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'application/pdf',
    ];

    // Caching configuration
    private const CACHE_EXPIRATION = HOUR_IN_SECONDS;
    private const CACHE_GROUP = 'derleiti_tools';

    /**
     * Initialize tools with enhanced security
     */
    public function init() {
        // Ensure secure logging directory
        $this->ensure_log_directory();

        // Performance and image optimization hooks
        add_action('wp_handle_upload_prefilter', [$this, 'validate_upload']);
        add_filter('wp_handle_upload', [$this, 'process_uploaded_images'], 10, 2);

        // Register advanced shortcodes
        $this->register_advanced_shortcodes();

        // Add performance-related resource hints
        add_action('wp_head', [$this, 'add_performance_hints'], 2);

        // Add security headers
        add_action('send_headers', [$this, 'add_security_headers']);
    }

    /**
     * Ensure secure logging directory
     */
    private function ensure_log_directory() {
        $log_dir = self::LOG_DIRECTORY;
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            $htaccess_path = $log_dir . '.htaccess';
            if (!file_exists($htaccess_path)) {
                file_put_contents($htaccess_path, "Deny from all\n");
            }
        }
    }

    /**
     * Secure logging method with enhanced error tracking
     *
     * @param string $message Log message
     * @param string $level Log level (info, warning, error)
     */
    private function log_event($message, $level = 'info') {
        $log_file = self::LOG_DIRECTORY . $level . '_' . date('Y-m-d') . '.log';
        $log_entry = sprintf(
            "[%s] [%s] [%s] %s\n",
            current_time('mysql'),
                             strtoupper($level),
                             $this->get_current_user_context(),
                             $message
        );
        error_log($log_entry, 3, $log_file);
    }

    /**
     * Get current user context for logging
     *
     * @return string User context information
     */
    private function get_current_user_context() {
        $current_user = wp_get_current_user();
        return $current_user->ID ? $current_user->user_login : 'anonymous';
    }

    /**
     * Register advanced shortcodes with improved security
     */
    private function register_advanced_shortcodes() {
        $shortcodes = [
            'derleiti_social_share' => [$this, 'social_share_shortcode'],
            'derleiti_responsive_video' => [$this, 'responsive_video_shortcode'],
            'derleiti_performance_optimized_image' => [$this, 'performance_image_shortcode'],
        ];

        foreach ($shortcodes as $tag => $callback) {
            add_shortcode($tag, $callback);
        }
    }

    /**
     * Validate file uploads with enhanced security checks
     *
     * @param array $file File upload data
     * @return array Filtered file data
     */
    public function validate_upload($file) {
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $file['error'] = sprintf(
                __('Datei zu groß. Maximale Dateigröße: %s', 'derleiti-plugin'),
                                     size_format(self::MAX_FILE_SIZE)
            );
            $this->log_event("Dateiupload-Größenbeschränkung überschritten: {$file['name']}", 'warning');
            return $file;
        }

        $filetype = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
        if (!in_array($filetype['type'], self::ALLOWED_MIME_TYPES)) {
            $file['error'] = __('Dateityp nicht erlaubt.', 'derleiti-plugin');
            $this->log_event("Unerlaubter Dateityp: {$file['name']} ({$filetype['type']})", 'warning');
            return $file;
        }

        return $file;
    }

    /**
     * Process and optimize uploaded images
     *
     * @param array  $upload  Upload information
     * @param string $context Upload context
     * @return array Processed upload
     */
    public function process_uploaded_images($upload, $context = 'upload') {
        if (!wp_attachment_is_image($upload['file'])) {
            return $upload;
        }

        try {
            $this->maybe_convert_to_webp($upload['file']);
            $this->optimize_image($upload['file']);
            $this->log_event("Bild optimiert: {$upload['file']}", 'info');
        } catch (Exception $e) {
            $this->log_event("Bild-Optimierung fehlgeschlagen: " . $e->getMessage(), 'error');
        }

        return $upload;
    }

    /**
     * Conditionally convert image to WebP
     *
     * @param string $file_path Path to image file
     */
    private function maybe_convert_to_webp($file_path) {
        $options = get_option('derleiti_performance_options');
        $webp_enabled = isset($options['webp_conversion']) ? $options['webp_conversion'] : false;
        if (!$webp_enabled) {
            return;
        }
        // Hier müsste Logik zur WebP-Konvertierung implementiert werden
        $this->log_event("WebP-Konvertierung nicht vollständig implementiert", 'warning');
    }

    /**
     * Basic image optimization
     *
     * @param string $file_path Path to image file
     */
    private function optimize_image($file_path) {
        $options = get_option('derleiti_performance_options');
        $image_quality = isset($options['image_quality']) ? $options['image_quality'] : 85;
        $this->log_event("Bild-Optimierung mit Qualität: {$image_quality}", 'info');
        // Hier kann zusätzliche Bildoptimierung implementiert werden (z.B. mit Imagick oder GD)
    }

    /**
     * Add performance-related resource hints
     */
    public function add_performance_hints() {
        $hints = [
            'preconnect'   => [
                'https://fonts.googleapis.com',
                'https://fonts.gstatic.com',
            ],
            'dns-prefetch' => [
                '//www.google-analytics.com',
            ],
        ];

        foreach ($hints as $rel => $urls) {
            foreach ($urls as $url) {
                printf(
                    '<link rel="%s" href="%s" crossorigin>%s',
                    esc_attr($rel),
                       esc_url($url),
                       "\n"
                );
            }
        }
    }

    /**
     * Add security headers
     */
    public function add_security_headers() {
        if (headers_sent()) {
            return;
        }
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options'        => 'SAMEORIGIN',
            'Referrer-Policy'        => 'strict-origin-when-cross-origin',
            'Permissions-Policy'     => 'camera=(), microphone=(), geolocation=()',
        ];
        foreach ($headers as $header => $value) {
            header("{$header}: {$value}");
        }
    }

    /**
     * Enhanced social share shortcode with security improvements
     *
     * @param array $atts Shortcode attributes
     * @return string Generated social share HTML
     */
    public function social_share_shortcode($atts) {
        $atts = shortcode_atts([
            'networks' => 'facebook,twitter,linkedin',
            'title'    => get_the_title(),
                               'url'      => get_permalink(),
                               'style'    => 'buttons',
        ], $atts, 'derleiti_social_share');

        $title = sanitize_text_field($atts['title']);
        $url   = esc_url($atts['url']);
        $networks = array_map('sanitize_key', explode(',', $atts['networks']));

        $allowed_networks = [
            'facebook' => [
                'name' => 'Facebook',
                'url'  => 'https://www.facebook.com/sharer/sharer.php?u=%s',
            ],
            'twitter' => [
                'name' => 'Twitter',
                'url'  => 'https://twitter.com/intent/tweet?url=%s&text=%s',
            ],
            'linkedin' => [
                'name' => 'LinkedIn',
                'url'  => 'https://www.linkedin.com/shareArticle?mini=true&url=%s&title=%s',
            ],
        ];

        $share_links = [];
        foreach ($networks as $network) {
            if (!isset($allowed_networks[$network])) {
                continue;
            }
            $network_info = $allowed_networks[$network];
            $share_url = sprintf(
                $network_info['url'],
                rawurlencode($url),
                                 rawurlencode($title)
            );
            $share_links[] = sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer" class="social-share-%s">%s</a>',
                esc_url($share_url),
                                     esc_attr($network),
                                     esc_html($network_info['name'])
            );
        }

        return sprintf(
            '<div class="derleiti-social-share">%s</div>',
            implode(' ', $share_links)
        );
    }

    // Additional methods for responsive_video_shortcode, performance_image_shortcode, etc.
}

// Initialize the tools
add_action('plugins_loaded', function() {
    $tools = new Derleiti_Tools();
    $tools->init();
});
