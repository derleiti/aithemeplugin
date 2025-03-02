<?php
/**
 * Derleiti Plugin Settings Initialization
 * Handles first-time setup, default settings, and migration
 *
 * @package Derleiti_Plugin
 * @version 1.2.0
 */

class Derleiti_Settings_Manager {
    // Define default settings for each category
    private $default_settings = [
        'general' => [
            'site_name' => '',
            'site_tagline' => '',
            'site_logo' => '',
            'site_favicon' => '',
            'site_language' => 'de_DE',
            'maintenance_mode' => 0,
        ],
        'appearance' => [
            'color_scheme' => 'default',
            'dark_mode' => 'auto',
            'custom_css' => '',
            'typography_preset' => 'inter',
            'layout_style' => 'boxed',
            'header_style' => 'default',
            'footer_style' => 'default',
        ],
        'performance' => [
            'cache_enabled' => 1,
            'cache_duration' => 3600, // 1 hour
            'lazy_load_images' => 1,
            'lazy_load_videos' => 1,
            'minify_html' => 0,
            'minify_css' => 0,
            'minify_js' => 0,
            'cdn_support' => 0,
        ],
        'integrations' => [
            'google_analytics_enabled' => 0,
            'google_analytics_id' => '',
            'google_tag_manager_id' => '',
            'facebook_pixel_id' => '',
            'social_media_links' => [],
            'third_party_scripts' => [],
        ],
        'advanced' => [
            'debug_mode' => 0,
            'log_errors' => 0,
            'error_reporting_level' => 'default',
            'custom_header_code' => '',
            'custom_footer_code' => '',
            'backup_frequency' => 'weekly',
        ],
        'permissions' => [
            'default_user_role' => 'subscriber',
            'login_logo' => '',
            'login_background' => '',
            'login_form_customization' => 0,
            'two_factor_auth' => 0,
            'user_registration_options' => [
                'allow_registration' => 1,
                'default_role' => 'subscriber',
                'require_email_verification' => 0,
            ],
        ],
        'security' => [
            'login_attempts_limit' => 5,
            'lockout_duration' => 15, // minutes
            'ip_blacklist' => [],
            'blocked_usernames' => [],
            'disable_xmlrpc' => 1,
            'disable_rest_api_for_non_users' => 1,
        ]
    ];

    /**
     * Initialize plugin settings
     */
    public function init_settings() {
        // Check if settings exist, if not, create defaults
        $this->check_and_create_settings();

        // Add migration hooks
        add_action('plugins_loaded', [$this, 'maybe_run_migrations']);
    }

    /**
     * Check and create initial settings
     */
    private function check_and_create_settings() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === null) {
            $this->create_settings_table();
        }

        // Verify and create default settings
        foreach ($this->default_settings as $category => $settings) {
            $this->ensure_category_settings($category, $settings);
        }
    }

    /**
     * Create settings table
     */
    private function create_settings_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category varchar(50) NOT NULL,
            setting_name varchar(100) NOT NULL,
            setting_value longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY category_setting (category, setting_name)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Ensure settings exist for a specific category
     */
    private function ensure_category_settings($category, $settings) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';

        foreach ($settings as $setting_name => $default_value) {
            // Check if setting exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name 
                 WHERE category = %s AND setting_name = %s",
                $category,
                $setting_name
            ));

            // If setting doesn't exist, insert default
            if (!$exists) {
                $wpdb->insert(
                    $table_name,
                    [
                        'category' => $category,
                        'setting_name' => $setting_name,
                        'setting_value' => is_array($default_value) 
                            ? maybe_serialize($default_value) 
                            : $default_value
                    ],
                    ['%s', '%s', '%s']
                );
            }
        }
    }

    /**
     * Run database migrations if needed
     */
    public function maybe_run_migrations() {
        $current_version = get_option('derleiti_plugin_version', '0.0.0');
        $latest_version = DERLEITI_PLUGIN_VERSION;

        // Different migration logic based on version
        if (version_compare($current_version, '1.2.0', '<')) {
            $this->migrate_to_1_2_0();
        }

        // Update version in database
        update_option('derleiti_plugin_version', $latest_version);
    }

    /**
     * Migration to version 1.2.0
     */
    private function migrate_to_1_2_0() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';

        // Migrate social media links
        $social_links = $this->get_old_social_links();
        if (!empty($social_links)) {
            $wpdb->replace(
                $table_name,
                [
                    'category' => 'integrations',
                    'setting_name' => 'social_media_links',
                    'setting_value' => maybe_serialize($social_links)
                ],
                ['%s', '%s', '%s']
            );
        }

        // Migrate other potential legacy settings
        $this->migrate_legacy_plugin_settings();
    }

    /**
     * Get old social media links from previous versions
     */
    private function get_old_social_links() {
        $networks = ['facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'pinterest'];
        $links = [];

        foreach ($networks as $network) {
            $link = get_option("derleiti_{$network}_link");
            if (!empty($link)) {
                $links[$network] = $link;
            }
        }

        return $links;
    }

    /**
     * Migrate legacy plugin settings
     */
    private function migrate_legacy_plugin_settings() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';

        // Migrate Google Analytics
        $ga_id = get_option('derleiti_google_analytics_id');
        if (!empty($ga_id)) {
            $wpdb->replace(
                $table_name,
                [
                    'category' => 'integrations',
                    'setting_name' => 'google_analytics_id',
                    'setting_value' => $ga_id
                ],
                ['%s', '%s', '%s']
            );
        }

        // More migration logic can be added here
    }

    /**
     * Get a specific setting
     */
    public function get_setting($category, $setting_name, $default = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';

        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM $table_name 
             WHERE category = %s AND setting_name = %s",
            $category,
            $setting_name
        ));

        // If no value found, return default
        if ($value === null) {
            return $default;
        }

        // Unserialize if it's a serialized array
        $unserialized = maybe_unserialize($value);
        return $unserialized !== false ? $unserialized : $value;
    }

    /**
     * Update a specific setting
     */
    public function update_setting($category, $setting_name, $value) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';

        // Serialize if it's an array
        $serialized_value = is_array($value) ? maybe_serialize($value) : $value;

        return $wpdb->replace(
            $table_name,
            [
                'category' => $category,
                'setting_name' => $setting_name,
                'setting_value' => $serialized_value
            ],
            ['%s', '%s', '%s']
        );
    }

    /**
     * Get all settings for a category
     */
    public function get_category_settings($category) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT setting_name, setting_value FROM $table_name 
             WHERE category = %s",
            $category
        ), ARRAY_A);

        $settings = [];
        foreach ($results as $result) {
            $settings[$result['setting_name']] = maybe_unserialize($result['setting_value']);
        }

        return $settings;
    }

    /**
     * Export current settings
     */
    public function export_settings() {
        $all_settings = [];
        
        foreach (array_keys($this->default_settings) as $category) {
            $all_settings[$category] = $this->get_category_settings($category);
        }

        return [
            'version' => DERLEITI_PLUGIN_VERSION,
            'exported_at' => current_time('mysql'),
            'settings' => $all_settings
        ];
    }

    /**
     * Import settings
     */
    public function import_settings($import_data) {
        if (!is_array($import_data) || !isset($import_data['settings'])) {
            return false;
        }

        foreach ($import_data['settings'] as $category => $settings) {
            foreach ($settings as $setting_name => $value) {
                $this->update_setting($category, $setting_name, $value);
            }
        }

        return true;
    }
}

// Initialize the settings manager
$derleiti_settings_manager = new Derleiti_Settings_Manager();
$derleiti_settings_manager->init_settings();
