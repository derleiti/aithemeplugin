<?php
/**
 * Derleiti Modern Theme functions and definitions
 * 
 * @package Derleiti_Modern
 * @version 2.6
 */

// Definiere Theme-Version für Cache-Busting
define('DERLEITI_THEME_VERSION', '2.6');

// Definiere Pfade als Konstanten für einfachere Verwaltung
define('DERLEITI_THEME_DIR', get_template_directory());
define('DERLEITI_THEME_URI', get_template_directory_uri());

/**
 * Theme-Setup und Features
 */
if (!function_exists('derleiti_setup')) :
    /**
     * Theme-Grundeinstellungen und WordPress-Features aktivieren
     */
    function derleiti_setup() {
        // Übersetzung aktivieren
        load_theme_textdomain('derleiti-modern', DERLEITI_THEME_DIR . '/languages');

        // RSS-Feed-Links im Head aktivieren
        add_theme_support('automatic-feed-links');

        // Titel-Tag aktivieren
        add_theme_support('title-tag');

        // Featured Images aktivieren
        add_theme_support('post-thumbnails');
        add_image_size('derleiti-featured', 1200, 600, true);
        add_image_size('derleiti-card', 600, 400, true);

        // Navigationsmenüs registrieren
        register_nav_menus(array(
            'primary' => esc_html__('Hauptmenü', 'derleiti-modern'),
            'footer' => esc_html__('Footer-Menü', 'derleiti-modern'),
        ));

        // HTML5-Unterstützung aktivieren
        add_theme_support('html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
            'navigation-widgets',
        ));

        // Logo-Unterstützung aktivieren
        add_theme_support('custom-logo', array(
            'height'      => 250,
            'width'       => 250,
            'flex-width'  => true,
            'flex-height' => true,
            'unlink-homepage-logo' => true,
        ));

        // Block Editor Features
        add_theme_support('wp-block-styles'); // Block styles
        add_theme_support('responsive-embeds'); // Responsive embeds
        add_theme_support('editor-styles'); // Editor styles
        add_theme_support('align-wide'); // Wide alignment
        add_theme_support('custom-spacing'); // Custom spacing
        add_theme_support('custom-units'); // Custom units

        // Editor Style hinzufügen
        add_editor_style('assets/css/editor-style.css');

        // Starter content hinzufügen
        add_theme_support('starter-content', array(
            'widgets' => array(
                'sidebar-1' => array(
                    'search',
                    'recent-posts',
                    'categories',
                    'archives',
                ),
                'footer-1' => array(
                    'text_about',
                ),
                'footer-2' => array(
                    'nav_menu' => array(
                        'title' => __('Links', 'derleiti-modern'),
                    ),
                ),
                'footer-3' => array(
                    'text_business_info',
                ),
            ),
            'posts' => array(
                'home' => array(
                    'post_type' => 'page',
                    'post_title' => __('Startseite', 'derleiti-modern'),
                    'post_content' => '<!-- wp:paragraph --><p>Willkommen auf unserer Website.</p><!-- /wp:paragraph -->',
                ),
                'about' => array(
                    'post_type' => 'page',
                    'post_title' => __('Über uns', 'derleiti-modern'),
                ),
                'contact' => array(
                    'post_type' => 'page',
                    'post_title' => __('Kontakt', 'derleiti-modern'),
                ),
                'blog' => array(
                    'post_type' => 'page',
                    'post_title' => __('Blog', 'derleiti-modern'),
                ),
            ),
            'options' => array(
                'show_on_front' => 'page',
                'page_on_front' => '{{home}}',
                'page_for_posts' => '{{blog}}',
            ),
            'theme_mods' => array(
                'custom_logo' => '{{custom_logo}}',
            ),
            'nav_menus' => array(
                'primary' => array(
                    'name' => __('Hauptmenü', 'derleiti-modern'),
                    'items' => array(
                        'link_home',
                        'page_about',
                        'page_blog',
                        'page_contact',
                    ),
                ),
                'footer' => array(
                    'name' => __('Footer-Menü', 'derleiti-modern'),
                    'items' => array(
                        'link_home',
                        'page_about',
                        'page_blog',
                        'page_contact',
                    ),
                ),
            ),
        ));
    }
endif;
add_action('after_setup_theme', 'derleiti_setup');

/**
 * Inhaltsbreite in Pixeln festlegen
 */
function derleiti_content_width() {
    $GLOBALS['content_width'] = apply_filters('derleiti_content_width', 1140);
}
add_action('after_setup_theme', 'derleiti_content_width', 0);

/**
 * Widget-Bereiche registrieren
 */
function derleiti_widgets_init() {
    register_sidebar(array(
        'name'          => esc_html__('Sidebar', 'derleiti-modern'),
        'id'            => 'sidebar-1',
        'description'   => esc_html__('Füge hier Widgets hinzu, die in der Sidebar erscheinen sollen.', 'derleiti-modern'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));

    register_sidebar(array(
        'name'          => esc_html__('Footer 1', 'derleiti-modern'),
        'id'            => 'footer-1',
        'description'   => esc_html__('Erster Footer-Widget-Bereich', 'derleiti-modern'),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4>',
        'after_title'   => '</h4>',
    ));

    register_sidebar(array(
        'name'          => esc_html__('Footer 2', 'derleiti-modern'),
        'id'            => 'footer-2',
        'description'   => esc_html__('Zweiter Footer-Widget-Bereich', 'derleiti-modern'),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4>',
        'after_title'   => '</h4>',
    ));

    register_sidebar(array(
        'name'          => esc_html__('Footer 3', 'derleiti-modern'),
        'id'            => 'footer-3',
        'description'   => esc_html__('Dritter Footer-Widget-Bereich', 'derleiti-modern'),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4>',
        'after_title'   => '</h4>',
    ));
    
    // Zusätzlicher Widget-Bereich für die Homepage
    register_sidebar(array(
        'name'          => esc_html__('Homepage Hero', 'derleiti-modern'),
        'id'            => 'homepage-hero',
        'description'   => esc_html__('Widgets für den Hero-Bereich der Homepage', 'derleiti-modern'),
        'before_widget' => '<div id="%1$s" class="homepage-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="homepage-widget-title">',
        'after_title'   => '</h2>',
    ));
}
add_action('widgets_init', 'derleiti_widgets_init');

/**
 * Skripte und Stylesheets einbinden
 */
function derleiti_scripts() {
    // Preload wichtige Webfonts
    echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" as="style">';
    
    // Google Fonts einbinden
    wp_enqueue_style('derleiti-google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', array(), null);
    
    // Haupt-Stylesheet einbinden
    $theme_version = derleiti_get_theme_version();
    wp_enqueue_style('derleiti-style', get_stylesheet_uri(), array(), $theme_version);
    
    // Theme-Skript einbinden
    wp_enqueue_script('derleiti-navigation', DERLEITI_THEME_URI . '/js/navigation.js', array('jquery'), $theme_version, true);
    
    // Skript-Variablen für JavaScript
    wp_localize_script('derleiti-navigation', 'derleitiSettings', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'themeUrl' => DERLEITI_THEME_URI,
        'nonce' => wp_create_nonce('derleiti-ajax-nonce'),
    ));
    
    // Kommentar-Antwort-Funktionalität aktivieren
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'derleiti_scripts');

/**
 * Enqueue Block Editor Assets
 */
function derleiti_block_editor_assets() {
    // Block Editor Stylesheet
    $theme_version = derleiti_get_theme_version();
    wp_enqueue_style(
        'derleiti-block-editor-style',
        DERLEITI_THEME_URI . '/assets/css/editor-style.css',
        array('wp-edit-blocks'),
        $theme_version
    );
}
add_action('enqueue_block_editor_assets', 'derleiti_block_editor_assets');

/**
 * Dynamische Theme-Versionsnummer
 * Ermöglicht automatische Aktualisierung der Versionsnummer
 */
function derleiti_get_theme_version() {
    $theme_data = wp_get_theme();
    $theme_version = $theme_data->get('Version');
    
    // Falls die Version direkt im Theme definiert ist, verwende diese
    if (defined('DERLEITI_THEME_VERSION') && DERLEITI_THEME_VERSION) {
        $theme_version = DERLEITI_THEME_VERSION;
    }
    
    // Füge eine zufällige Zeichenfolge im Entwicklungsmodus hinzu (falls WP_DEBUG aktiviert ist)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $theme_version .= '-' . time();
    }
    
    return $theme_version;
}

/**
 * Benutzerdefinierter "Post Type" für Projekte
 */
function derleiti_register_project_post_type() {
    // Überprüfen, ob der Post-Type bereits existiert
    if (post_type_exists('project')) {
        return;
    }
    
    $labels = array(
        'name'                  => _x('Projekte', 'Post Type General Name', 'derleiti-modern'),
        'singular_name'         => _x('Projekt', 'Post Type Singular Name', 'derleiti-modern'),
        'menu_name'             => __('Projekte', 'derleiti-modern'),
        'name_admin_bar'        => __('Projekt', 'derleiti-modern'),
        'archives'              => __('Projekt-Archiv', 'derleiti-modern'),
        'attributes'            => __('Projekt-Attribute', 'derleiti-modern'),
        'parent_item_colon'     => __('Übergeordnetes Projekt:', 'derleiti-modern'),
        'all_items'             => __('Alle Projekte', 'derleiti-modern'),
        'add_new_item'          => __('Neues Projekt hinzufügen', 'derleiti-modern'),
        'add_new'               => __('Neu hinzufügen', 'derleiti-modern'),
        'new_item'              => __('Neues Projekt', 'derleiti-modern'),
        'edit_item'             => __('Projekt bearbeiten', 'derleiti-modern'),
        'update_item'           => __('Projekt aktualisieren', 'derleiti-modern'),
        'view_item'             => __('Projekt ansehen', 'derleiti-modern'),
        'view_items'            => __('Projekte ansehen', 'derleiti-modern'),
        'search_items'          => __('Projekt suchen', 'derleiti-modern'),
        'not_found'             => __('Keine Projekte gefunden', 'derleiti-modern'),
        'not_found_in_trash'    => __('Keine Projekte im Papierkorb gefunden', 'derleiti-modern'),
        'featured_image'        => __('Projektbild', 'derleiti-modern'),
        'set_featured_image'    => __('Projektbild festlegen', 'derleiti-modern'),
        'remove_featured_image' => __('Projektbild entfernen', 'derleiti-modern'),
        'use_featured_image'    => __('Als Projektbild verwenden', 'derleiti-modern'),
        'insert_into_item'      => __('In Projekt einfügen', 'derleiti-modern'),
        'uploaded_to_this_item' => __('Zu diesem Projekt hochgeladen', 'derleiti-modern'),
        'items_list'            => __('Projektliste', 'derleiti-modern'),
        'items_list_navigation' => __('Projektlisten-Navigation', 'derleiti-modern'),
        'filter_items_list'     => __('Projekte filtern', 'derleiti-modern'),
    );
    $args = array(
        'label'                 => __('Projekt', 'derleiti-modern'),
        'description'           => __('Projekte und Portfolio-Einträge', 'derleiti-modern'),
        'labels'                => $labels,
        'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'comments', 'revisions'),
        'taxonomies'            => array('project_category', 'project_tag'),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-portfolio',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => true, // Aktiviere Gutenberg-Editor
        'rewrite'               => array(
            'slug' => 'projekte',
            'with_front' => true,
            'pages' => true,
            'feeds' => true,
        ),
    );
    register_post_type('project', $args);
    
    // Überprüfen, ob die Taxonomien bereits existieren
    if (!taxonomy_exists('project_category')) {
        // Projekt-Kategorien
        $cat_labels = array(
            'name'                       => _x('Projekt-Kategorien', 'Taxonomy General Name', 'derleiti-modern'),
            'singular_name'              => _x('Projekt-Kategorie', 'Taxonomy Singular Name', 'derleiti-modern'),
            'menu_name'                  => __('Kategorien', 'derleiti-modern'),
            'all_items'                  => __('Alle Kategorien', 'derleiti-modern'),
            'parent_item'                => __('Übergeordnete Kategorie', 'derleiti-modern'),
            'parent_item_colon'          => __('Übergeordnete Kategorie:', 'derleiti-modern'),
            'new_item_name'              => __('Neuer Kategoriename', 'derleiti-modern'),
            'add_new_item'               => __('Neue Kategorie hinzufügen', 'derleiti-modern'),
            'edit_item'                  => __('Kategorie bearbeiten', 'derleiti-modern'),
            'update_item'                => __('Kategorie aktualisieren', 'derleiti-modern'),
            'view_item'                  => __('Kategorie ansehen', 'derleiti-modern'),
            'separate_items_with_commas' => __('Kategorien mit Kommas trennen', 'derleiti-modern'),
            'add_or_remove_items'        => __('Kategorien hinzufügen oder entfernen', 'derleiti-modern'),
            'choose_from_most_used'      => __('Aus den meistgenutzten wählen', 'derleiti-modern'),
            'popular_items'              => __('Beliebte Kategorien', 'derleiti-modern'),
            'search_items'               => __('Kategorien suchen', 'derleiti-modern'),
            'not_found'                  => __('Keine Kategorien gefunden', 'derleiti-modern'),
        );
        
        $cat_args = array(
            'labels'                     => $cat_labels,
            'hierarchical'               => true, // Wie Kategorien (nicht wie Tags)
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true, // Für Gutenberg-Support
            'rewrite'                    => array('slug' => 'projekt-kategorie'),
        );
        
        register_taxonomy('project_category', array('project'), $cat_args);
    }
    
    if (!taxonomy_exists('project_tag')) {
        // Projekt-Tags
        $tag_labels = array(
            'name'                       => _x('Projekt-Tags', 'Taxonomy General Name', 'derleiti-modern'),
            'singular_name'              => _x('Projekt-Tag', 'Taxonomy Singular Name', 'derleiti-modern'),
            'menu_name'                  => __('Tags', 'derleiti-modern'),
            'all_items'                  => __('Alle Tags', 'derleiti-modern'),
            'parent_item'                => __('Übergeordneter Tag', 'derleiti-modern'),
            'parent_item_colon'          => __('Übergeordneter Tag:', 'derleiti-modern'),
            'new_item_name'              => __('Neuer Tag-Name', 'derleiti-modern'),
            'add_new_item'               => __('Neuen Tag hinzufügen', 'derleiti-modern'),
            'edit_item'                  => __('Tag bearbeiten', 'derleiti-modern'),
            'update_item'                => __('Tag aktualisieren', 'derleiti-modern'),
            'view_item'                  => __('Tag ansehen', 'derleiti-modern'),
            'separate_items_with_commas' => __('Tags mit Kommas trennen', 'derleiti-modern'),
            'add_or_remove_items'        => __('Tags hinzufügen oder entfernen', 'derleiti-modern'),
            'choose_from_most_used'      => __('Aus den meistgenutzten wählen', 'derleiti-modern'),
            'popular_items'              => __('Beliebte Tags', 'derleiti-modern'),
            'search_items'               => __('Tags suchen', 'derleiti-modern'),
            'not_found'                  => __('Keine Tags gefunden', 'derleiti-modern'),
        );
        
        $tag_args = array(
            'labels'                     => $tag_labels,
            'hierarchical'               => false, // Wie Tags (nicht wie Kategorien)
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true, // Für Gutenberg-Support
            'rewrite'                    => array('slug' => 'projekt-tag'),
        );
        
        register_taxonomy('project_tag', array('project'), $tag_args);
    }
}
add_action('init', 'derleiti_register_project_post_type');

/**
 * Block Patterns registrieren
 */
function derleiti_register_block_patterns() {
    if (function_exists('register_block_pattern_category')) {
        register_block_pattern_category(
            'derleiti',
            array('label' => __('Derleiti Layouts', 'derleiti-modern'))
        );
    }

    if (function_exists('register_block_pattern')) {
        // Hero-Bereich Muster
        register_block_pattern(
            'derleiti/hero-section',
            array(
                'title'       => __('Hero-Bereich mit CTA', 'derleiti-modern'),
                'description' => __('Ein Hero-Bereich mit Titel, Beschreibung und Call-to-Action-Button.', 'derleiti-modern'),
                'categories'  => array('derleiti'),
                'content'     => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"backgroundColor":"secondary","textColor":"background","layout":{"type":"constrained"}} -->
                <div class="wp-block-group alignfull has-background-color has-secondary-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)"><!-- wp:heading {"textAlign":"center","level":1,"fontSize":"xx-large"} -->
                <h1 class="has-text-align-center has-xx-large-font-size">Willkommen auf unserer Website</h1>
                <!-- /wp:heading -->

                <!-- wp:paragraph {"align":"center","fontSize":"large"} -->
                <p class="has-text-align-center has-large-font-size">Wir bieten innovative Lösungen für Ihre Herausforderungen</p>
                <!-- /wp:paragraph -->

                <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
                <div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"accent","textColor":"background"} -->
                <div class="wp-block-button"><a class="wp-block-button__link has-background-color has-accent-background-color has-text-color has-background wp-element-button">Mehr erfahren</a></div>
                <!-- /wp:button -->

                <!-- wp:button {"backgroundColor":"background","textColor":"secondary"} -->
                <div class="wp-block-button"><a class="wp-block-button__link has-secondary-color has-background-background-color has-text-color has-background wp-element-button">Kontaktieren Sie uns</a></div>
                <!-- /wp:button --></div>
                <!-- /wp:buttons --></div>
                <!-- /wp:group -->',
            )
        );
        
        // Zweispaltige Feature-Sektion
        register_block_pattern(
            'derleiti/feature-section',
            array(
                'title'       => __('Feature-Bereich mit zwei Spalten', 'derleiti-modern'),
                'description' => __('Zweispaltige Sektion mit Bild und Text zur Vorstellung von Features.', 'derleiti-modern'),
                'categories'  => array('derleiti'),
                'content'     => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"backgroundColor":"background","layout":{"type":"constrained"}} -->
                <div class="wp-block-group alignfull has-background-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:heading {"textAlign":"center"} -->
                <h2 class="has-text-align-center">Unsere Features</h2>
                <!-- /wp:heading -->

                <!-- wp:columns {"align":"wide"} -->
                <div class="wp-block-columns alignwide"><!-- wp:column -->
                <div class="wp-block-column"><!-- wp:image {"align":"center","sizeSlug":"large"} -->
                <figure class="wp-block-image aligncenter size-large"><img src="https://via.placeholder.com/600x400" alt="Feature Image"/></figure>
                <!-- /wp:image --></div>
                <!-- /wp:column -->

                <!-- wp:column {"verticalAlignment":"center"} -->
                <div class="wp-block-column is-vertically-aligned-center"><!-- wp:heading {"level":3} -->
                <h3>Überschrift Feature 1</h3>
                <!-- /wp:heading -->

                <!-- wp:paragraph -->
                <p>Hier steht eine detaillierte Beschreibung des Features mit allen wichtigen Informationen. Diese Sektion kann beliebig erweitert werden.</p>
                <!-- /wp:paragraph -->

                <!-- wp:buttons -->
                <div class="wp-block-buttons"><!-- wp:button -->
                <div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Mehr erfahren</a></div>
                <!-- /wp:button --></div>
                <!-- /wp:buttons --></div>
                <!-- /wp:column --></div>
                <!-- /wp:columns --></div>
                <!-- /wp:group -->',
            )
        );
    }
}
add_action('init', 'derleiti_register_block_patterns');

/**
 * Füge Theme-spezifische Admin-Styles hinzu
 */
function derleiti_admin_styles() {
    $theme_version = derleiti_get_theme_version();
    wp_enqueue_style('derleiti-admin-style', DERLEITI_THEME_URI . '/assets/css/admin-style.css', array(), $theme_version);
}
add_action('admin_enqueue_scripts', 'derleiti_admin_styles');

/**
 * Verbesserte Performance durch Lazy Loading
 * Verwendet DOM-Parser statt Regex für robustere HTML-Manipulation
 */
function derleiti_lazy_load_images($content) {
    // Nicht im Admin-Bereich oder Elementor-Vorschau anwenden
    if (is_admin() || isset($_GET['elementor-preview'])) {
        return $content;
    }
    
    // Keine Bearbeitung, wenn DOMDocument nicht verfügbar ist
    if (!class_exists('DOMDocument')) {
        return $content;
    }
    
    // Wenn der Inhalt leer ist, früh zurückkehren
    if (empty($content)) {
        return $content;
    }
    
    // Erstelle ein DOMDocument-Objekt
    $dom = new DOMDocument();
    
    // Verhindere Fehlermeldungen beim Parsen von unvollständigem HTML
    libxml_use_internal_errors(true);
    
    // Füge einen Wrapper hinzu, um HTML-Fragmente zu unterstützen
    $content = '<div>' . $content . '</div>';
    
    // UTF-8 Encoding für korrekte Verarbeitung von Sonderzeichen
    $dom->loadHTML('<?xml encoding="UTF-8">' . $content);
    
    // Fehler zurücksetzen
    libxml_clear_errors();
    
    // Finde alle Bilder
    $images = $dom->getElementsByTagName('img');
    
    // Durchlaufe die Bilder rückwärts (da sich die NodeList beim Ändern ändern kann)
    $images_array = array();
    foreach ($images as $img) {
        $images_array[] = $img;
    }
    
    foreach ($images_array as $img) {
        // Füge loading="lazy" hinzu, wenn es noch nicht vorhanden ist
        if (!$img->hasAttribute('loading')) {
            $img->setAttribute('loading', 'lazy');
        }
    }
    
    // HTML zurückgewinnen
    $body = $dom->getElementsByTagName('body')->item(0);
    $div = $body->getElementsByTagName('div')->item(0);
    
    // Konvertiere den DOMElement zurück zu einem String
    $content = '';
    foreach ($div->childNodes as $node) {
        $content .= $dom->saveHTML($node);
    }
    
    return $content;
}
add_filter('the_content', 'derleiti_lazy_load_images');
add_filter('post_thumbnail_html', 'derleiti_lazy_load_images');
add_filter('get_avatar', 'derleiti_lazy_load_images');

/**
 * Integriere Theme mit Plugin
 */
function derleiti_theme_plugin_integration() {
    // Prüfe, ob das Plugin aktiv ist
    if (function_exists('derleiti_plugin_init')) {
        // Füge Theme-Support für erweiterte Plugin-Funktionen hinzu
        add_theme_support('derleiti-extended-blocks');
        add_theme_support('derleiti-ai-integration');
        add_theme_support('derleiti-layout-builder');
    }
}
add_action('after_setup_theme', 'derleiti_theme_plugin_integration');

/**
 * Registriere benutzerdefinierte REST API Endpunkte
 */
function derleiti_register_rest_routes() {
    register_rest_route('derleiti/v1', '/theme-info', array(
        'methods' => 'GET',
        'callback' => 'derleiti_get_theme_info',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'derleiti_register_rest_routes');

/**
 * REST API Callback für Theme-Informationen
 */
function derleiti_get_theme_info() {
    return array(
        'name' => 'Derleiti Modern',
        'version' => derleiti_get_theme_version(),
        'features' => array(
            'block_patterns' => true,
            'custom_colors' => true,
            'ai_integration' => function_exists('derleiti_plugin_init'),
            'dark_mode' => true
        )
    );
}

/**
 * AJAX-Handler zum Prüfen der Theme-Version auf Updates
 */
function derleiti_check_theme_version() {
    // Überprüfe Nonce für Sicherheit
    check_ajax_referer('derleiti-ajax-nonce', 'nonce');
    
    // Simuliere eine Anfrage an einen Update-Server
    // In einer echten Implementierung würde hier ein API-Endpunkt abgefragt werden
    $current_version = derleiti_get_theme_version();
    $latest_version = '2.6'; // Diese Information würde normalerweise von einem externen API-Endpunkt kommen
    
    $response = array(
        'current_version' => $current_version,
        'latest_version' => $latest_version,
        'has_update' => version_compare($latest_version, $current_version, '>'),
        'download_url' => 'https://derleiti.de/download/latest',
    );
    
    wp_send_json_success($response);
}
add_action('wp_ajax_derleiti_check_theme_version', 'derleiti_check_theme_version');
