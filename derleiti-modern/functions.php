<?php
/**
 * Derleiti Modern functions and definitions
 *
 * @package Derleiti_Modern
 * @version 2.2
 */

if (!function_exists('derleiti_modern_setup')) :
    /**
     * Sets up theme defaults and registers support for various WordPress features.
     */
    function derleiti_modern_setup() {
        /*
         * Make theme available for translation.
         */
        load_theme_textdomain('derleiti-modern', get_template_directory() . '/languages');

        /*
         * Let WordPress manage the document title.
         */
        add_theme_support('title-tag');

        /*
         * Enable support for Post Thumbnails on posts and pages.
         */
        add_theme_support('post-thumbnails');

        /*
         * Register menu locations.
         */
        register_nav_menus(array(
            'primary' => esc_html__('Primary Menu', 'derleiti-modern'),
            'footer'  => esc_html__('Footer Menu', 'derleiti-modern'),
        ));

        /*
         * Switch default core markup for search form, comment form, and comments
         * to output valid HTML5.
         */
        add_theme_support('html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        ));

        /*
         * Add support for core custom logo.
         */
        add_theme_support('custom-logo', array(
            'height'      => 250,
            'width'       => 250,
            'flex-width'  => true,
            'flex-height' => true,
        ));

        // Add theme support for selective refresh for widgets.
        add_theme_support('customize-selective-refresh-widgets');

        // Add support for Block Styles.
        add_theme_support('wp-block-styles');

        // Add support for full and wide align images.
        add_theme_support('align-wide');

        // Add support for editor styles.
        add_theme_support('editor-styles');

        // Add support for responsive embeds.
        add_theme_support('responsive-embeds');

        // Add custom theme settings to indicate support for AI integrations (used by the plugin)
        add_theme_support('derleiti-ai-integration');
    }
endif;
add_action('after_setup_theme', 'derleiti_modern_setup');

/**
 * Register widget areas.
 */
function derleiti_modern_widgets_init() {
    register_sidebar(array(
        'name'          => esc_html__('Sidebar', 'derleiti-modern'),
        'id'            => 'sidebar-1',
        'description'   => esc_html__('Add widgets here.', 'derleiti-modern'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));

    register_sidebar(array(
        'name'          => esc_html__('Footer 1', 'derleiti-modern'),
        'id'            => 'footer-1',
        'description'   => esc_html__('First footer widget area.', 'derleiti-modern'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));

    register_sidebar(array(
        'name'          => esc_html__('Footer 2', 'derleiti-modern'),
        'id'            => 'footer-2',
        'description'   => esc_html__('Second footer widget area.', 'derleiti-modern'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));

    register_sidebar(array(
        'name'          => esc_html__('Footer 3', 'derleiti-modern'),
        'id'            => 'footer-3',
        'description'   => esc_html__('Third footer widget area.', 'derleiti-modern'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
}
add_action('widgets_init', 'derleiti_modern_widgets_init');

/**
 * Enqueue scripts and styles.
 */
function derleiti_modern_scripts() {
    // Main stylesheet
    wp_enqueue_style(
        'derleiti-modern-style',
        get_stylesheet_uri(),
        array(),
        wp_get_theme()->get('Version')
    );

    // Navigation script
    wp_enqueue_script(
        'derleiti-modern-navigation',
        get_template_directory_uri() . '/js/navigation.js',
        array(),
        wp_get_theme()->get('Version'),
        true
    );

    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'derleiti_modern_scripts');

/**
 * Plugin compatibility function
 * Automatically detects the Derleiti Modern Theme Plugin and integrates
 */
function derleiti_modern_plugin_compatibility() {
    // Check if the plugin is active
    if (defined('DERLEITI_PLUGIN_VERSION')) {
        // Add plugin compatibilty filter
        add_filter('derleiti_ai_context', 'derleiti_modern_ai_context');
    }
}
add_action('after_setup_theme', 'derleiti_modern_plugin_compatibility');

/**
 * Define theme-specific AI context for the plugin
 *
 * @param array $context Default context
 * @return array Modified context
 */
function derleiti_modern_ai_context($context) {
    $context['theme_name'] = 'Derleiti Modern';
    $context['layout_style'] = 'modern';
    $context['color_scheme'] = get_theme_mod('color_scheme', 'default');
    $context['primary_color'] = get_theme_mod('primary_color', '#0066cc');
    $context['css_classes'] = array(
        'paragraph' => 'derleiti-text',
        'headline'  => 'derleiti-heading',
        'list'      => 'derleiti-list',
        'callout'   => 'derleiti-callout',
        'button'    => 'derleiti-button'
    );
    $context['ai_content_class'] = 'theme-derleiti-modern';
    
    return $context;
}

/**
 * Include additional files
 */
require get_template_directory() . '/inc/template-functions.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/customizer.php';