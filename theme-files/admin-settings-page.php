<?php
/**
 * Extended Settings Page for Derleiti Modern Theme Plugin
 *
 * @package Derleiti_Plugin
 * @subpackage Admin
 * @version 1.2.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Derleiti_Admin_Settings {
    private $options;
    private $tabs;

    public function __construct() {
        // Define available tabs
        $this->tabs = [
            'general'      => __('Allgemein', 'derleiti-plugin'),
            'appearance'   => __('Design', 'derleiti-plugin'),
            'performance'  => __('Performance', 'derleiti-plugin'),
            'integrations' => __('Integrationen', 'derleiti-plugin'),
            'advanced'     => __('Erweitert', 'derleiti-plugin'),
            'permissions'  => __('Berechtigungen', 'derleiti-plugin'),
        ];

        // Load existing options from custom database table
        $this->options = $this->get_plugin_options();
    }

    /**
     * Retrieve current plugin options from database
     */
    private function get_plugin_options() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'derleiti_settings';

        $options = [];
        $results = $wpdb->get_results("SELECT setting_name, setting_value FROM $table_name", ARRAY_A);

        if ( ! empty( $results ) ) {
            foreach ( $results as $result ) {
                $options[ $result['setting_name'] ] = maybe_unserialize( $result['setting_value'] );
            }
        }

        return $options;
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        // General Settings
        register_setting('derleiti_general_settings', 'derleiti_general_options', [$this, 'sanitize_general_options']);
        add_settings_section(
            'derleiti_general_section',
            __('Allgemeine Einstellungen', 'derleiti-plugin'),
                             [$this, 'general_section_callback'],
                             'derleiti_general_settings'
        );

        // Similar sections for other setting categories (appearance, performance, etc.)
        // Each section follows the same pattern as the general settings
    }

    /**
     * Render the main settings page
     */
    public function render_settings_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Get current tab from URL; default is 'general'
        $current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
        ?>
        <div class="wrap derleiti-settings-page">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <nav class="nav-tab-wrapper">
        <?php foreach ( $this->tabs as $tab_key => $tab_label ) : ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=derleiti-settings&tab=' . $tab_key ) ); ?>"
        class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
        <?php echo esc_html( $tab_label ); ?>
        </a>
        <?php endforeach; ?>
        </nav>

        <form action="options.php" method="post">
        <div class="derleiti-settings-container">
        <?php
        // Render the specific tab's content
        switch ( $current_tab ) {
            case 'general':
                settings_fields( 'derleiti_general_settings' );
                do_settings_sections( 'derleiti_general_settings' );
                break;
            case 'appearance':
                settings_fields( 'derleiti_appearance_settings' );
                do_settings_sections( 'derleiti_appearance_settings' );
                break;
            case 'performance':
                settings_fields( 'derleiti_performance_settings' );
                do_settings_sections( 'derleiti_performance_settings' );
                break;
            case 'integrations':
                settings_fields( 'derleiti_integrations_settings' );
                do_settings_sections( 'derleiti_integrations_settings' );
                break;
            case 'advanced':
                settings_fields( 'derleiti_advanced_settings' );
                do_settings_sections( 'derleiti_advanced_settings' );
                break;
            case 'permissions':
                settings_fields( 'derleiti_permissions_settings' );
                do_settings_sections( 'derleiti_permissions_settings' );
                break;
        }

        submit_button( __( 'Einstellungen speichern', 'derleiti-plugin' ) );
        ?>
        </div>
        </form>
        </div>
        <?php
    }

    /**
     * Callback for General Settings Section
     */
    public function general_section_callback() {
        echo '<p>' . esc_html__( 'Allgemeine Einstellungen für das Derleiti Modern Theme Plugin.', 'derleiti-plugin' ) . '</p>';

        // Add general setting fields
        add_settings_field(
            'derleiti_site_logo',
            __('Site Logo', 'derleiti-plugin'),
                           [$this, 'logo_upload_callback'],
                           'derleiti_general_settings',
                           'derleiti_general_section'
        );
    }

    /**
     * Logo Upload Callback
     */
    public function logo_upload_callback() {
        $logo = isset( $this->options['site_logo'] ) ? $this->options['site_logo'] : '';
        ?>
        <div class="derleiti-logo-upload">
        <input type="hidden" name="derleiti_general_options[site_logo]" value="<?php echo esc_attr( $logo ); ?>" />
        <button type="button" class="button derleiti-upload-logo"><?php _e( 'Logo auswählen', 'derleiti-plugin' ); ?></button>
        <?php if ( ! empty( $logo ) ) : ?>
        <img src="<?php echo esc_url( $logo ); ?>" alt="Site Logo" style="max-width: 200px; margin-top: 10px;" />
        <button type="button" class="button derleiti-remove-logo"><?php _e( 'Logo entfernen', 'derleiti-plugin' ); ?></button>
        <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Sanitize General Options
     */
    public function sanitize_general_options( $input ) {
        $output = [];

        // Sanitize site logo
        if ( isset( $input['site_logo'] ) ) {
            $output['site_logo'] = esc_url_raw( $input['site_logo'] );
        }

        return $output;
    }

    /**
     * Enqueue Admin Scripts and Styles
     */
    public function enqueue_admin_scripts( $hook ) {
        // Only enqueue on our plugin settings page
        if ( 'toplevel_page_derleiti-settings' !== $hook ) {
            return;
        }

        // Media uploader scripts
        wp_enqueue_media();

        // Custom admin scripts
        wp_enqueue_script(
            'derleiti-admin-settings',
            DERLEITI_PLUGIN_URL . 'admin/js/settings.js',
            ['jquery'],
            DERLEITI_PLUGIN_VERSION,
            true
        );

        // Custom admin styles
        wp_enqueue_style(
            'derleiti-admin-settings-style',
            DERLEITI_PLUGIN_URL . 'admin/css/settings.css',
            [],
            DERLEITI_PLUGIN_VERSION
        );

        // Localize script with necessary data
        wp_localize_script(
            'derleiti-admin-settings',
            'derleitiSettingsData',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                           'nonce'   => wp_create_nonce( 'derleiti-settings-nonce' ),
                           'strings' => [
                               'logoUploadTitle'   => __( 'Logo auswählen', 'derleiti-plugin' ),
                           'logoRemoveConfirm' => __( 'Möchten Sie das Logo wirklich entfernen?', 'derleiti-plugin' ),
                           ]
            ]
        );
    }

    /**
     * Add Settings Page to Admin Menu
     */
    public function add_settings_page() {
        add_menu_page(
            __('Derleiti Einstellungen', 'derleiti-plugin'), // Page title
                      __('Derleiti', 'derleiti-plugin'), // Menu title
                      'manage_options', // Capability
                      'derleiti-settings', // Menu slug
                      [$this, 'render_settings_page'], // Callback function
                      'dashicons-admin-generic', // Icon
                      30 // Position
        );
    }

    /**
     * AJAX Handler for Logo Upload
     */
    public function ajax_upload_logo() {
        // Check nonce and user capabilities
        check_ajax_referer( 'derleiti-settings-nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Keine Berechtigung', 'derleiti-plugin' ) );
        }

        // Check if file was uploaded
        if ( ! isset( $_FILES['file'] ) ) {
            wp_send_json_error( __( 'Keine Datei hochgeladen', 'derleiti-plugin' ) );
        }

        // Handle file upload
        $file   = $_FILES['file'];
        $upload = wp_handle_upload( $file, [
            'test_form' => false,
            'mimes'     => [
                'jpg|jpeg|jpe' => 'image/jpeg',
                'gif'          => 'image/gif',
                'png'          => 'image/png',
                'svg'          => 'image/svg+xml',
                'webp'         => 'image/webp',
            ]
        ]);

        // Check for upload errors
        if ( isset( $upload['error'] ) ) {
            wp_send_json_error( $upload['error'] );
        }

        // If successful, return file URL
        wp_send_json_success( [
            'url'  => $upload['url'],
            'type' => isset( $_POST['type'] ) ? $_POST['type'] : 'logo'
        ] );
    }

    /**
     * Initialize the plugin settings
     */
    public function init() {
        // Add settings page to admin menu
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );

        // Register settings and sections
        add_action( 'admin_init', [ $this, 'register_settings' ] );

        // Enqueue admin scripts and styles
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );

        // AJAX handler for logo uploads
        add_action( 'wp_ajax_derleiti_upload_logo', [ $this, 'ajax_upload_logo' ] );
    }
}

// Instantiate and initialize the settings page
$derleiti_admin_settings = new Derleiti_Admin_Settings();
$derleiti_admin_settings->init();
