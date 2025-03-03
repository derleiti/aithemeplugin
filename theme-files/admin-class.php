<?php
/**
 * Manages all Admin Functionalities for the Derleiti Plugin
 *
 * @package Derleiti_Plugin
 * @subpackage Admin
 * @version 1.3.0
 */

// Prevent direct access
defined('ABSPATH') || exit;

class Derleiti_Admin {
    // Logging and security constants
    private const LOG_DIRECTORY = WP_CONTENT_DIR . '/derleiti-logs/admin/';
    private const NONCE_ACTION = 'derleiti_admin_action';
    private const NONCE_NAME = 'derleiti_admin_nonce';

    // Transient key for rate limiting
    private const RATE_LIMIT_TRANSIENT = 'derleiti_admin_rate_limit_';

    // Maximum number of actions per time period
    private const MAX_ACTIONS = 10;
    private const ACTION_PERIOD = 300; // 5 minutes

    /**
     * Initialize admin functionalities
     */
    public function init() {
        // Ensure logging directory exists
        $this->ensure_log_directory();

        // WordPress hooks
        add_action('admin_menu', [$this, 'register_admin_menus'], 10);
        add_action('admin_init', [$this, 'register_settings'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets'], 10);
        
        // AJAX handlers with security
        add_action('wp_ajax_derleiti_dismiss_notice', [$this, 'ajax_dismiss_notice']);
        add_action('wp_ajax_derleiti_reset_settings', [$this, 'ajax_reset_settings']);

        // Dashboard widget
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widgets']);

        // Plugin action links
        add_filter('plugin_action_links_' . plugin_basename(DERLEITI_PLUGIN_FILE), [$this, 'add_plugin_action_links']);
    }

    /**
     * Ensure secure logging directory
     */
    private function ensure_log_directory() {
        $log_dir = self::LOG_DIRECTORY;
        
        // Create directory with proper permissions
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            
            // Add .htaccess to prevent direct access
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
     * Rate limiting mechanism for admin actions
     * 
     * @param string $action Unique action identifier
     * @return bool True if action is allowed, false if rate limited
     */
    private function check_rate_limit($action) {
        $user_id = get_current_user_id();
        $transient_key = self::RATE_LIMIT_TRANSIENT . $user_id . '_' . $action;
        
        // Get current action count
        $action_count = get_transient($transient_key);
        
        if ($action_count === false) {
            // First action, set initial count
            set_transient($transient_key, 1, self::ACTION_PERIOD);
            return true;
        }
        
        if ($action_count >= self::MAX_ACTIONS) {
            $this->log_event("Rate limit exceeded for action: {$action}", 'warning');
            return false;
        }
        
        // Increment action count
        set_transient($transient_key, $action_count + 1, self::ACTION_PERIOD);
        return true;
    }

    /**
     * Register admin menus with enhanced security
     */
    public function register_admin_menus() {
        // Main plugin settings page
        add_menu_page(
            __('Derleiti Plugin', 'derleiti-plugin'),
            __('Derleiti', 'derleiti-plugin'),
            'manage_options',
            'derleiti-settings',
            [$this, 'render_main_settings_page'],
            'dashicons-admin-generic',
            30
        );

        // Submenu pages
        $submenus = [
            [
                'parent_slug' => 'derleiti-settings',
                'page_title' => __('Allgemeine Einstellungen', 'derleiti-plugin'),
                'menu_title' => __('Allgemein', 'derleiti-plugin'),
                'capability' => 'manage_options',
                'menu_slug' => 'derleiti-general-settings',
                'callback' => [$this, 'render_general_settings_page']
            ],
            [
                'parent_slug' => 'derleiti-settings',
                'page_title' => __('Performance', 'derleiti-plugin'),
                'menu_title' => __('Performance', 'derleiti-plugin'),
                'capability' => 'manage_options',
                'menu_slug' => 'derleiti-performance',
                'callback' => [$this, 'render_performance_settings_page']
            ],
            [
                'parent_slug' => 'derleiti-settings',
                'page_title' => __('KI-Einstellungen', 'derleiti-plugin'),
                'menu_title' => __('KI-Integration', 'derleiti-plugin'),
                'capability' => 'manage_options',
                'menu_slug' => 'derleiti-ai-settings',
                'callback' => [$this, 'render_ai_settings_page']
            ]
        ];

        foreach ($submenus as $submenu) {
            add_submenu_page(
                $submenu['parent_slug'],
                $submenu['page_title'],
                $submenu['menu_title'],
                $submenu['capability'],
                $submenu['menu_slug'],
                $submenu['callback']
            );
        }
    }

    /**
     * Enqueue admin assets with version control and security
     * 
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets($hook) {
        // Only enqueue on plugin pages
        if (strpos($hook, 'derleiti') === false) {
            return;
        }

        // Enqueue styles
        wp_enqueue_style(
            'derleiti-admin-styles',
            DERLEITI_PLUGIN_URL . 'admin/css/admin-styles.css',
            [],
            DERLEITI_PLUGIN_VERSION
        );

        // Enqueue scripts with nonce
        wp_enqueue_script(
            'derleiti-admin-scripts',
            DERLEITI_PLUGIN_URL . 'admin/js/admin-scripts.js',
            ['jquery', 'wp-api'],
            DERLEITI_PLUGIN_VERSION,
            true
        );

        // Localize script with security nonce
        wp_localize_script(
            'derleiti-admin-scripts',
            'derleitiAdminData',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'restUrl' => esc_url_raw(rest_url('derleiti-plugin/v1/')),
                'nonce' => wp_create_nonce(self::NONCE_ACTION),
                'strings' => [
                    'saveSuccess' => __('Einstellungen gespeichert!', 'derleiti-plugin'),
                    'saveError' => __('Fehler beim Speichern der Einstellungen.', 'derleiti-plugin'),
                    'confirmReset' => __('Möchten Sie wirklich alle Einstellungen zurücksetzen?', 'derleiti-plugin')
                ]
            ]
        );
    }

    /**
     * AJAX handler to dismiss admin notices
     */
    public function ajax_dismiss_notice() {
        // Verify nonce
        check_ajax_referer(self::NONCE_ACTION, self::NONCE_NAME);

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Keine Berechtigung.', 'derleiti-plugin'));
        }

        // Rate limit check
        if (!$this->check_rate_limit('dismiss_notice')) {
            wp_send_json_error(__('Zu viele Aktionen. Bitte warten.', 'derleiti-plugin'));
        }

        // Sanitize notice ID
        $notice_id = sanitize_key($_POST['notice'] ?? '');
        
        if (empty($notice_id)) {
            wp_send_json_error(__('Ungültige Benachrichtigungs-ID.', 'derleiti-plugin'));
        }

        // Get current hidden notices
        $hidden_notices = get_user_meta(get_current_user_id(), 'derleiti_hidden_notices', true);
        $hidden_notices = is_array($hidden_notices) ? $hidden_notices : [];

        // Add notice to hidden list
        $hidden_notices[] = $notice_id;
        $hidden_notices = array_unique($hidden_notices);

        // Update user meta
        update_user_meta(get_current_user_id(), 'derleiti_hidden_notices', $hidden_notices);

        // Log the action
        $this->log_event("Notice dismissed: {$notice_id}");

        wp_send_json_success();
    }

    /**
     * AJAX handler to reset settings
     */
    public function ajax_reset_settings() {
        // Verify nonce
        check_ajax_referer(self::NONCE_ACTION, self::NONCE_NAME);

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Keine Berechtigung.', 'derleiti-plugin'));
        }

        // Rate limit check
        if (!$this->check_rate_limit('reset_settings')) {
            wp_send_json_error(__('Zu viele Aktionen. Bitte warten.', 'derleiti-plugin'));
        }

        // Sanitize reset type
        $reset_type = sanitize_key($_POST['type'] ?? 'all');

        try {
            // Perform reset based on type
            switch ($reset_type) {
                case 'general':
                    $this->reset_general_settings();
                    break;
                case 'performance':
                    $this->reset_performance_settings();
                    break;
                case 'ai':
                    $this->reset_ai_settings();
                    break;
                case 'all':
                default:
                    $this->reset_all_settings();
            }

            // Log the reset action
            $this->log_event("Settings reset: {$reset_type}");

            wp_send_json_success([
                'message' => __('Einstellungen erfolgreich zurückgesetzt.', 'derleiti-plugin')
            ]);
        } catch (Exception $e) {
            // Log any errors during reset
            $this->log_event("Settings reset failed: {$e->getMessage()}", 'error');

            wp_send_json_error([
                'message' => __('Fehler beim Zurücksetzen der Einstellungen.', 'derleiti-plugin')
            ]);
        }
    }

    /**
     * Reset general settings
     */
    private function reset_general_settings() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';

        $wpdb->delete(
            $table_name, 
            ['category' => 'general'], 
            ['%s']
        );
    }

    /**
     * Reset performance settings
     */
    private function reset_performance_settings() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';

        $wpdb->delete(
            $table_name, 
            ['category' => 'performance'], 
            ['%s']
        );
    }

    /**
     * Reset AI settings
     */
    private function reset_ai_settings() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';

        $wpdb->delete(
            $table_name, 
            ['category' => 'ai'], 
            ['%s']
        );
    }

    /**
     * Reset all settings
     */
    private function reset_all_settings() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';

        $wpdb->query("TRUNCATE TABLE $table_name");
    }

    /**
     * Render main settings page
     */
    public function render_main_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('Sie haben keine ausreichenden Berechtigungen.', 'derleiti-plugin'));
        }

        // Include settings page template
        include_once DERLEITI_PLUGIN_PATH . 'admin/views/main-settings.php';
    }

    /**
     * Render general settings page
     */
    public function render_general_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('Sie haben keine ausreichenden Berechtigungen.', 'derleiti-plugin'));
        }

        // Include general settings template
        include_once DERLEITI_PLUGIN_PATH . 'admin/views/general-settings.php';
    }

    /**
     * Render performance settings page
     */
    public function render_performance_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('Sie haben keine ausreichenden Berechtigungen.', 'derleiti-plugin'));
        }

        // Include performance settings template
        include_once DERLEITI_PLUGIN_PATH . 'admin/views/performance-settings.php';
    }

    /**
     * Render AI settings page
     */
    public function render_ai_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('Sie haben keine ausreichenden Berechtigungen.', 'derleiti-plugin'));
        }

        // Include AI settings template
        include_once DERLEITI_PLUGIN_PATH . 'admin/views/ai-settings.php';
    }

    /**
     * Add dashboard widgets
     */
    public function add_dashboard_widgets() {
        // Check user capabilities
        if (!current_user_can('manage_dashboard')) {
            return;
        }

        // Add dashboard widget
        wp_add_dashboard_widget(
            'derleiti_plugin_dashboard_widget',
            __('Derleiti Plugin Status', 'derleiti-plugin'),
            [$this, 'render_dashboard_widget']
        );
    }

    /**
     * Render dashboard widget content
     */
    public function render_dashboard_widget() {
        // Get plugin status and statistics
        $stats = $this->get_plugin_dashboard_stats();

        // Output dashboard widget HTML
        ?>
        <div class="derleiti-dashboard-widget">
            <div class="widget-content">
                <div class="plugin-status">
                    <h3><?php _e('Plugin-Status', 'derleiti-plugin'); ?></h3>
                    <ul>
                        <li>
                            <strong><?php _e('Version:', 'derleiti-plugin'); ?></strong>
                            <?php echo esc_html(DERLEITI_PLUGIN_VERSION); ?>
                        </li>
                        <li>
                            <strong><?php _e('AI-Integration:', 'derleiti-plugin'); ?></strong>
                            <?php echo $stats['ai_enabled'] ? __('Aktiviert', 'derleiti-plugin') : __('Deaktiviert', 'derleiti-plugin'); ?>
                        </li>
                        <li>
                            <strong><?php _e('Performance-Optimierung:', 'derleiti-plugin'); ?></strong>
                            <?php echo $stats['performance_enabled'] ? __('Aktiviert', 'derleiti-plugin') : __('Deaktiviert', 'derleiti-plugin'); ?>
                        </li>
                    </ul>
                </div>

                <div class="recent-actions">
                    <h3><?php _e('Letzte Aktionen', 'derleiti-plugin'); ?></h3>
                    <?php if (!empty($stats['recent_actions'])): ?>
                        <ul>
                            <?php foreach ($stats['recent_actions'] as $action): ?>
                                <li>
                                    <?php echo esc_html($action['description']); ?>
                                    <small>(<?php echo esc_html($action['time']); ?>)</small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><?php _e('Keine kürzlichen Aktionen', 'derleiti-plugin'); ?></p>
                    <?php endif; ?>
                </div>

                <div class="quick-actions">
                    <h3><?php _e('Schnellaktionen', 'derleiti-plugin'); ?></h3>
                    <div class="action-buttons">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=derleiti-settings')); ?>" class="button button-primary">
                            <?php _e('Plugin-Einstellungen', 'derleiti-plugin'); ?>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=derleiti-performance')); ?>" class="button">
                            <?php _e('Performance', 'derleiti-plugin'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get plugin dashboard statistics
     *
     * @return array Dashboard statistics
     */
    private function get_plugin_dashboard_stats() {
        global $wpdb;
        $settings_table = $wpdb->prefix . 'derleiti_settings';

        // Fetch plugin settings
        $ai_enabled = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT setting_value FROM $settings_table WHERE setting_name = %s",
                'ai_enabled'
            )
        ) === '1';

        $performance_enabled = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT setting_value FROM $settings_table WHERE setting_name = %s",
                'performance_optimization'
            )
        ) === '1';

        // Fetch recent actions (placeholder - in a real implementation, you'd track actual actions)
        $recent_actions = [
            [
                'description' => __('Plugin initialisiert', 'derleiti-plugin'),
                'time' => human_time_diff(strtotime('-2 hours'), current_time('timestamp'))
            ],
            [
                'description' => __('Performance-Optimierung überprüft', 'derleiti-plugin'),
                'time' => human_time_diff(strtotime('-30 minutes'), current_time('timestamp'))
            ]
        ];

        return [
            'ai_enabled' => $ai_enabled,
            'performance_enabled' => $performance_enabled,
            'recent_actions' => $recent_actions,
        ];
    }

    /**
     * Add plugin action links
     *
     * @param array $links Existing plugin action links
     * @return array Modified plugin action links
     */
    public function add_plugin_action_links($links) {
        // Add custom action links
        $custom_links = [
            'settings' => sprintf(
                '<a href="%s">%s</a>',
                esc_url(admin_url('admin.php?page=derleiti-settings')),
                __('Einstellungen', 'derleiti-plugin')
            ),
            'support' => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                'https://derleiti.de/support',
                __('Support', 'derleiti-plugin')
            )
        ];

        // Merge custom links with existing links
        return array_merge($custom_links, $links);
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        // Register settings groups
        $settings_groups = [
            'derleiti_general_settings' => [
                'section' => 'derleiti_general_section',
                'title' => __('Allgemeine Einstellungen', 'derleiti-plugin'),
                'callback' => [$this, 'render_general_section']
            ],
            'derleiti_performance_settings' => [
                'section' => 'derleiti_performance_section',
                'title' => __('Performance-Einstellungen', 'derleiti-plugin'),
                'callback' => [$this, 'render_performance_section']
            ],
            'derleiti_ai_settings' => [
                'section' => 'derleiti_ai_section',
                'title' => __('KI-Einstellungen', 'derleiti-plugin'),
                'callback' => [$this, 'render_ai_section']
            ]
        ];

        // Register each settings group
        foreach ($settings_groups as $group => $config) {
            register_setting(
                $group,
                'derleiti_' . str_replace('derleiti_', '', $group) . '_options',
                [$this, 'sanitize_settings']
            );

            add_settings_section(
                $config['section'],
                $config['title'],
                $config['callback'],
                $group
            );
        }

        // Register individual settings fields
        $this->register_general_settings_fields();
        $this->register_performance_settings_fields();
        $this->register_ai_settings_fields();
    }

    /**
     * Register general settings fields
     */
    private function register_general_settings_fields() {
        add_settings_field(
            'site_logo',
            __('Site Logo', 'derleiti-plugin'),
            [$this, 'render_logo_upload_field'],
            'derleiti_general_settings',
            'derleiti_general_section'
        );
    }

    /**
     * Register performance settings fields
     */
    private function register_performance_settings_fields() {
        add_settings_field(
            'lazy_load_images',
            __('Lazy Loading', 'derleiti-plugin'),
            [$this, 'render_lazy_load_field'],
            'derleiti_performance_settings',
            'derleiti_performance_section'
        );
    }

    /**
     * Register AI settings fields
     */
    private function register_ai_settings_fields() {
        add_settings_field(
            'ai_provider',
            __('KI-Anbieter', 'derleiti-plugin'),
            [$this, 'render_ai_provider_field'],
            'derleiti_ai_settings',
            'derleiti_ai_section'
        );
    }

    /**
     * Sanitize settings
     *
     * @param array $input Unvalidated settings input
     * @return array Sanitized settings
     */
    public function sanitize_settings($input) {
        $output = [];

        // Sanitize and validate each setting
        // Add specific sanitization logic for different setting types
        foreach ($input as $key => $value) {
            switch ($key) {
                case 'site_logo':
                    $output[$key] = esc_url_raw($value);
                    break;
                case 'lazy_load_images':
                    $output[$key] = isset($value) ? 1 : 0;
                    break;
                case 'ai_provider':
                    $allowed_providers = ['openai', 'anthropic', 'google'];
                    $output[$key] = in_array($value, $allowed_providers) ? $value : 'openai';
                    break;
                default:
                    // Default sanitization
                    $output[$key] = sanitize_text_field($value);
            }
        }

        return $output;
    }

    /**
     * Render logo upload field
     */
    public function render_logo_upload_field() {
        $options = get_option('derleiti_general_settings_options');
        $logo_url = $options['site_logo'] ?? '';
        ?>
        <div class="derleiti-logo-upload">
            <input
                type="text"
                id="site_logo"
                name="derleiti_general_settings_options[site_logo]"
                value="<?php echo esc_attr($logo_url); ?>"
                class="regular-text"
            >
            <button type="button" class="button derleiti-upload-logo">
                <?php _e('Logo auswählen', 'derleiti-plugin'); ?>
            </button>

            <?php if (!empty($logo_url)): ?>
                <img
                    src="<?php echo esc_url($logo_url); ?>"
                    alt="<?php _e('Site Logo', 'derleiti-plugin'); ?>"
                    style="max-width: 200px; margin-top: 10px;"
                >
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render lazy load field
     */
    public function render_lazy_load_field() {
        $options = get_option('derleiti_performance_settings_options');
        $lazy_load = $options['lazy_load_images'] ?? 0;
        ?>
        <label>
            <input
                type="checkbox"
                name="derleiti_performance_settings_options[lazy_load_images]"
                value="1"
                <?php checked(1, $lazy_load); ?>
            >
            <?php _e('Lazy Loading für Bilder aktivieren', 'derleiti-plugin'); ?>
        </label>
        <p class="description">
            <?php _e('Verbessert die Ladegeschwindigkeit, indem Bilder erst geladen werden, wenn sie sichtbar sind.', 'derleiti-plugin'); ?>
        </p>
        <?php
    }

    /**
     * Render AI provider field
     */
    public function render_ai_provider_field() {
        $options = get_option('derleiti_ai_settings_options');
        $current_provider = $options['ai_provider'] ?? 'openai';

        $providers = [
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic',
            'google' => 'Google'
        ];
        ?>
        <select
            name="derleiti_ai_settings_options[ai_provider]"
            id="ai_provider"
        >
            <?php foreach ($providers as $provider_key => $provider_name): ?>
                <option
                    value="<?php echo esc_attr($provider_key); ?>"
                    <?php selected($current_provider, $provider_key); ?>
                >
                    <?php echo esc_html($provider_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php _e('Wählen Sie den Standard-KI-Anbieter für Ihre Inhalte.', 'derleiti-plugin'); ?>
        </p>
        <?php
    }

    /**
     * Render general settings section
     */
    public function render_general_section() {
        echo '<p>' . __('Konfigurieren Sie die grundlegenden Einstellungen für das Plugin.', 'derleiti-plugin') . '</p>';
    }

    /**
     * Render performance settings section
     */
    public function render_performance_section() {
        echo '<p>' . __('Optimieren Sie die Leistung Ihrer Website.', 'derleiti-plugin') . '</p>';
    }

    /**
     * Render AI settings section
     */
    public function render_ai_section() {
        echo '<p>' . __('Konfigurieren Sie die KI-Integration und -Einstellungen.', 'derleiti-plugin') . '</p>';
    }
}

// Initialize the admin class
$derleiti_admin = new Derleiti_Admin();
$derleiti_admin->init();
