<?php
/**
 * Verwaltet alle Admin-Funktionen des Plugins
 *
 * @package Derleiti_Plugin
 * @subpackage Admin
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Die Admin-Klasse des Plugins
 */
class Derleiti_Admin {
    
    /**
     * Initialisiere die Admin-Klasse
     */
    public function init() {
        // Hook für Admin-Menü hinzufügen
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue Admin-Skripte und Styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Dashboard Widget hinzufügen
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        
        // Plugin-Aktionslinks hinzufügen
        add_filter('plugin_action_links_derleiti-plugin/derleiti-plugin.php', array($this, 'add_plugin_action_links'));
        
        // TinyMCE-Buttons hinzufügen
        add_action('admin_init', array($this, 'add_tinymce_buttons'));
        
        // Admin-Notices für Plugin-Updates und Tipps
        add_action('admin_notices', array($this, 'display_admin_notices'));
        
        // Metaboxen für Projekte und Beiträge hinzufügen
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // Speichern der Metabox-Daten
        add_action('save_post', array($this, 'save_meta_box_data'));
    }
    
    /**
     * Admin-Menüs hinzufügen
     */
    public function add_admin_menu() {
        // Hauptmenüeintrag
        add_menu_page(
            __('Derleiti Theme', 'derleiti-plugin'),
            __('Derleiti Theme', 'derleiti-plugin'),
            'manage_options',
            'derleiti-plugin',
            array($this, 'display_main_admin_page'),
            'dashicons-admin-customizer',
            30
        );
        
        // Untermenü für Theme-Einstellungen
        add_submenu_page(
            'derleiti-plugin',
            __('Theme-Einstellungen', 'derleiti-plugin'),
            __('Theme-Einstellungen', 'derleiti-plugin'),
            'manage_options',
            'derleiti-plugin',
            array($this, 'display_main_admin_page')
        );
        
        // Untermenü für Layout-Builder
        add_submenu_page(
            'derleiti-plugin',
            __('Layout-Builder', 'derleiti-plugin'),
            __('Layout-Builder', 'derleiti-plugin'),
            'manage_options',
            'derleiti-layout',
            array($this, 'display_layout_page')
        );
        
        // Untermenü für KI-Funktionen
        add_submenu_page(
            'derleiti-plugin',
            __('KI-Integration', 'derleiti-plugin'),
            __('KI-Integration', 'derleiti-plugin'),
            'manage_options',
            'derleiti-ai',
            array($this, 'display_ai_page')
        );
        
        // Untermenü für Design-Tools
        add_submenu_page(
            'derleiti-plugin',
            __('Design-Tools', 'derleiti-plugin'),
            __('Design-Tools', 'derleiti-plugin'),
            'manage_options',
            'derleiti-design',
            array($this, 'display_design_page')
        );
        
        // Untermenü für Hilfe und Dokumentation
        add_submenu_page(
            'derleiti-plugin',
            __('Hilfe', 'derleiti-plugin'),
            __('Hilfe', 'derleiti-plugin'),
            'manage_options',
            'derleiti-help',
            array($this, 'display_help_page')
        );
    }
    
    /**
     * Hauptadmin-Seite anzeigen
     */
    public function display_main_admin_page() {
        include DERLEITI_PLUGIN_PATH . 'admin/views/main-page.php';
    }
    
    /**
     * Layout-Builder-Seite anzeigen
     */
    public function display_layout_page() {
        include DERLEITI_PLUGIN_PATH . 'admin/views/layout-page.php';
    }
    
    /**
     * KI-Integrations-Seite anzeigen
     */
    public function display_ai_page() {
        include DERLEITI_PLUGIN_PATH . 'admin/views/ai-page.php';
    }
    
    /**
     * Design-Tools-Seite anzeigen
     */
    public function display_design_page() {
        include DERLEITI_PLUGIN_PATH . 'admin/views/design-page.php';
    }
    
    /**
     * Hilfe-Seite anzeigen
     */
    public function display_help_page() {
        include DERLEITI_PLUGIN_PATH . 'admin/views/help-page.php';
    }
    
    /**
     * Lade Admin-Skripte und Styles
     */
    public function enqueue_admin_assets($hook) {
        // Nur auf Plugin-Seiten laden
        if (strpos($hook, 'derleiti') === false) {
            return;
        }
        
        // Admin CSS
        wp_enqueue_style(
            'derleiti-admin-styles',
            DERLEITI_PLUGIN_URL . 'admin/css/admin-styles.css',
            array(),
            DERLEITI_PLUGIN_VERSION
        );
        
        // Admin JavaScript
        wp_enqueue_script(
            'derleiti-admin-scripts',
            DERLEITI_PLUGIN_URL . 'admin/js/admin-scripts.js',
            array('jquery', 'wp-api'),
            DERLEITI_PLUGIN_VERSION,
            true
        );
        
        // Lokalisiere Skript mit Übersetzungen und AJAX-URL
        wp_localize_script(
            'derleiti-admin-scripts',
            'derleitiPluginData',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'restUrl' => esc_url_raw(rest_url('derleiti-plugin/v1/')),
                'nonce' => wp_create_nonce('wp_rest'),
                'strings' => array(
                    'saveSuccess' => __('Einstellungen gespeichert!', 'derleiti-plugin'),
                    'saveError' => __('Fehler beim Speichern der Einstellungen.', 'derleiti-plugin'),
                    'confirmReset' => __('Möchten Sie wirklich alle Einstellungen zurücksetzen?', 'derleiti-plugin')
                )
            )
        );
        
        // Medien-Uploader-Scripts
        wp_enqueue_media();
        
        // Color Picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Auf Layout-Builder-Seite zusätzliche Skripte laden
        if ($hook === 'derleiti_page_derleiti-layout') {
            wp_enqueue_script(
                'derleiti-layout-builder',
                DERLEITI_PLUGIN_URL . 'admin/js/layout-builder.js',
                array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable'),
                DERLEITI_PLUGIN_VERSION,
                true
            );
        }
        
        // Auf KI-Seite zusätzliche Skripte laden
        if ($hook === 'derleiti_page_derleiti-ai') {
            wp_enqueue_script(
                'derleiti-ai-integration',
                DERLEITI_PLUGIN_URL . 'admin/js/ai-integration.js',
                array('jquery'),
                DERLEITI_PLUGIN_VERSION,
                true
            );
        }
    }
    
    /**
     * Dashboard-Widget hinzufügen
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'derleiti_dashboard_widget',
            __('Derleiti Theme Status', 'derleiti-plugin'),
            array($this, 'display_dashboard_widget')
        );
    }
    
    /**
     * Dashboard-Widget anzeigen
     */
    public function display_dashboard_widget() {
        include DERLEITI_PLUGIN_PATH . 'admin/views/dashboard-widget.php';
    }
    
    /**
     * Plugin-Aktionslinks hinzufügen
     */
    public function add_plugin_action_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=derleiti-plugin') . '">' . __('Einstellungen', 'derleiti-plugin') . '</a>',
            '<a href="' . admin_url('admin.php?page=derleiti-help') . '">' . __('Hilfe', 'derleiti-plugin') . '</a>'
        );
        
        return array_merge($plugin_links, $links);
    }
    
    /**
     * TinyMCE-Buttons hinzufügen
     */
    public function add_tinymce_buttons() {
        // Überprüfe Benutzerberechtigungen
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
            return;
        }
        
        // Überprüfe, ob Rich Editing aktiviert ist
        if (get_user_option('rich_editing') !== 'true') {
            return;
        }
        
        // Registriere die TinyMCE-Plugin-Skripte
        add_filter('mce_external_plugins', array($this, 'register_tinymce_plugin'));
        add_filter('mce_buttons', array($this, 'register_tinymce_buttons'));
    }
    
    /**
     * Registriere TinyMCE-Plugin
     */
    public function register_tinymce_plugin($plugin_array) {
        $plugin_array['derleiti_tinymce'] = DERLEITI_PLUGIN_URL . 'admin/js/tinymce-plugin.js';
        return $plugin_array;
    }
    
    /**
     * Registriere TinyMCE-Buttons
     */
    public function register_tinymce_buttons($buttons) {
        array_push($buttons, 'derleiti_shortcodes');
        return $buttons;
    }
    
    /**
     * Admin-Notices anzeigen
     */
    public function display_admin_notices() {
        // Überprüfe, ob Benachrichtigungen ausgeblendet wurden
        $hidden_notices = get_user_meta(get_current_user_id(), 'derleiti_hidden_notices', true);
        if (!is_array($hidden_notices)) {
            $hidden_notices = array();
        }
        
        // Überprüfe, ob das Theme installiert ist
        $current_theme = wp_get_theme();
        if ($current_theme->get('TextDomain') !== 'derleiti-modern' && !in_array('theme_missing', $hidden_notices)) {
            ?>
            <div class="notice notice-warning is-dismissible" data-notice-id="theme_missing">
                <p>
                    <?php _e('Das Derleiti Plugin funktioniert am besten mit dem Derleiti Modern Theme. <a href="themes.php">Jetzt aktivieren</a> oder <a href="#" class="derleiti-dismiss-notice" data-notice="theme_missing">Diese Nachricht ausblenden</a>.', 'derleiti-plugin'); ?>
                </p>
            </div>
            <?php
        }
        
        // Überprüfe auf ausstehende Theme-Updates
        if (function_exists('derleiti_check_theme_updates')) {
            $updates = derleiti_check_theme_updates();
            if ($updates && !in_array('theme_update', $hidden_notices)) {
                ?>
                <div class="notice notice-info is-dismissible" data-notice-id="theme_update">
                    <p>
                        <?php _e('Eine neue Version des Derleiti Modern Themes ist verfügbar. <a href="themes.php">Jetzt aktualisieren</a> oder <a href="#" class="derleiti-dismiss-notice" data-notice="theme_update">Diese Nachricht ausblenden</a>.', 'derleiti-plugin'); ?>
                    </p>
                </div>
                <?php
            }
        }
        
        // JavaScript für Dismiss-Funktionalität
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.derleiti-dismiss-notice').on('click', function(e) {
                e.preventDefault();
                var noticeId = $(this).data('notice');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'derleiti_dismiss_notice',
                        notice: noticeId,
                        nonce: '<?php echo wp_create_nonce('derleiti_dismiss_notice'); ?>'
                    }
                });
                
                $(this).closest('.notice').remove();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Metaboxen hinzufügen
     */
    public function add_meta_boxes() {
        // Metabox für Projekte
        add_meta_box(
            'derleiti_project_options',
            __('Projekt-Optionen', 'derleiti-plugin'),
            array($this, 'render_project_metabox'),
            'project',
            'side',
            'default'
        );
        
        // Metabox für Beiträge und Seiten
        add_meta_box(
            'derleiti_post_options',
            __('Derleiti Optionen', 'derleiti-plugin'),
            array($this, 'render_post_metabox'),
            array('post', 'page'),
            'side',
            'default'
        );
    }
    
    /**
     * Projekt-Metabox rendern
     */
    public function render_project_metabox($post) {
        // Nonce für Sicherheit
        wp_nonce_field('derleiti_project_metabox', 'derleiti_project_nonce');
        
        // Vorhandene Werte abrufen
        $project_url = get_post_meta($post->ID, '_derleiti_project_url', true);
        $project_client = get_post_meta($post->ID, '_derleiti_project_client', true);
        $project_year = get_post_meta($post->ID, '_derleiti_project_year', true);
        
        // Ausgabe der Felder
        ?>
        <p>
            <label for="derleiti_project_url"><?php _e('Projekt-URL:', 'derleiti-plugin'); ?></label>
            <input class="widefat" type="url" id="derleiti_project_url" name="derleiti_project_url" value="<?php echo esc_url($project_url); ?>">
        </p>
        <p>
            <label for="derleiti_project_client"><?php _e('Kunde:', 'derleiti-plugin'); ?></label>
            <input class="widefat" type="text" id="derleiti_project_client" name="derleiti_project_client" value="<?php echo esc_attr($project_client); ?>">
        </p>
        <p>
            <label for="derleiti_project_year"><?php _e('Jahr:', 'derleiti-plugin'); ?></label>
            <input class="widefat" type="number" id="derleiti_project_year" name="derleiti_project_year" min="1900" max="2100" value="<?php echo esc_attr($project_year); ?>">
        </p>
        <?php
    }
    
    /**
     * Beitrags-/Seiten-Metabox rendern
     */
    public function render_post_metabox($post) {
        // Nonce für Sicherheit
        wp_nonce_field('derleiti_post_metabox', 'derleiti_post_nonce');
        
        // Vorhandene Werte abrufen
        $enable_ai = get_post_meta($post->ID, '_derleiti_enable_ai', true);
        $custom_css = get_post_meta($post->ID, '_derleiti_custom_css', true);
        $sidebar_position = get_post_meta($post->ID, '_derleiti_sidebar_position', true);
        
        // Standard-Seitenleiste
        if (empty($sidebar_position)) {
            $sidebar_position = 'right';
        }
        
        // Ausgabe der Felder
        ?>
        <p>
            <input type="checkbox" id="derleiti_enable_ai" name="derleiti_enable_ai" value="1" <?php checked($enable_ai, '1'); ?>>
            <label for="derleiti_enable_ai"><?php _e('KI-Features aktivieren', 'derleiti-plugin'); ?></label>
        </p>
        <p>
            <label for="derleiti_sidebar_position"><?php _e('Seitenleisten-Position:', 'derleiti-plugin'); ?></label>
            <select id="derleiti_sidebar_position" name="derleiti_sidebar_position" class="widefat">
                <option value="right" <?php selected($sidebar_position, 'right'); ?>><?php _e('Rechts', 'derleiti-plugin'); ?></option>
                <option value="left" <?php selected($sidebar_position, 'left'); ?>><?php _e('Links', 'derleiti-plugin'); ?></option>
                <option value="none" <?php selected($sidebar_position, 'none'); ?>><?php _e('Keine Seitenleiste', 'derleiti-plugin'); ?></option>
            </select>
        </p>
        <p>
            <label for="derleiti_custom_css"><?php _e('Benutzerdefiniertes CSS:', 'derleiti-plugin'); ?></label>
            <textarea id="derleiti_custom_css" name="derleiti_custom_css" class="widefat" rows="5"><?php echo esc_textarea($custom_css); ?></textarea>
        </p>
        <?php
    }
    
    /**
     * Metabox-Daten speichern
     */
    public function save_meta_box_data($post_id) {
        // Überprüfe Autorisierung
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Projekt-Metabox speichern
        if (isset($_POST['derleiti_project_nonce']) && wp_verify_nonce($_POST['derleiti_project_nonce'], 'derleiti_project_metabox')) {
            // Projekt-URL
            if (isset($_POST['derleiti_project_url'])) {
                update_post_meta($post_id, '_derleiti_project_url', esc_url_raw($_POST['derleiti_project_url']));
            }
            
            // Projekt-Kunde
            if (isset($_POST['derleiti_project_client'])) {
                update_post_meta($post_id, '_derleiti_project_client', sanitize_text_field($_POST['derleiti_project_client']));
            }
            
            // Projekt-Jahr
            if (isset($_POST['derleiti_project_year'])) {
                update_post_meta($post_id, '_derleiti_project_year', intval($_POST['derleiti_project_year']));
            }
        }
        
        // Post/Page-Metabox speichern
        if (isset($_POST['derleiti_post_nonce']) && wp_verify_nonce($_POST['derleiti_post_nonce'], 'derleiti_post_metabox')) {
            // KI-Features
            $enable_ai = isset($_POST['derleiti_enable_ai']) ? '1' : '0';
            update_post_meta($post_id, '_derleiti_enable_ai', $enable_ai);
            
            // Seitenleisten-Position
            if (isset($_POST['derleiti_sidebar_position'])) {
                update_post_meta($post_id, '_derleiti_sidebar_position', sanitize_text_field($_POST['derleiti_sidebar_position']));
            }
            
            // Benutzerdefiniertes CSS
            if (isset($_POST['derleiti_custom_css'])) {
                update_post_meta($post_id, '_derleiti_custom_css', wp_strip_all_tags($_POST['derleiti_custom_css']));
            }
        }
    }
}
