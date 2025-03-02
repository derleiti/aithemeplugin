# Technische Implementierungsdetails

## 1. Aktualisierung der Versionen und Konstanten

### Theme
```php
// In style.css
/*
Theme Name: Derleiti Modern
Theme URI: https://derleiti.de
Author: Derleiti
Description: Ein modernes WordPress-Theme für Blog- und Projektdarstellung mit optimiertem Design, KI-Integration und erweiterten Block-Editor-Features.
Version: 2.6
Requires at least: 6.2
Tested up to: 6.6
Requires PHP: 8.1
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: derleiti-modern
Tags: blog, portfolio, grid-layout, custom-colors, custom-logo, custom-menu, featured-images, footer-widgets, full-width-template, sticky-post, theme-options, translation-ready, block-styles, wide-blocks, editor-style, full-site-editing, block-patterns
*/

// In functions.php
define('DERLEITI_THEME_VERSION', '2.6');
```

### Plugin
```php
// In plugin-main.php
/**
 * Plugin Name: Derleiti Modern Theme Plugin
 * Plugin URI: https://derleiti.de/plugin
 * Description: Erweitert das Derleiti Modern Theme mit zusätzlichen Funktionen wie KI-Integration, erweiterten Blockeditor-Funktionen und Designtools.
 * Version: 1.1.0
 * Author: Derleiti
 * Author URI: https://derleiti.de
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: derleiti-plugin
 * Domain Path: /languages
 *
 * @package Derleiti_Plugin
 */

// Plugin-Version definieren
define('DERLEITI_PLUGIN_VERSION', '1.1.0');
```

## 2. PHP 8.3 Kompatibilitätsanpassungen

### Typehints hinzufügen
```php
// Alter Code
public function process_data($data) {
    // Funktion
}

// Neuer Code
public function process_data(array $data): array {
    // Funktion
    return $result;
}
```

### Nullable Typehints
```php
public function get_option(?string $key = null): ?string {
    // Funktion
}
```

### readonly-Eigenschaften für Klassen
```php
class Config {
    public readonly string $version;
    
    public function __construct(string $version) {
        $this->version = $version;
    }
}
```

## 3. Verbesserung der KI-Integration

### API-Integration aktualisieren
```php
private function connect_to_ai_api(string $api_key, string $prompt, array $options = []): array {
    $api_url = 'https://api.example.com/v1/completions';
    
    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'prompt' => $prompt,
            'max_tokens' => $options['max_tokens'] ?? 100,
            'temperature' => $options['temperature'] ?? 0.7,
            'model' => $options['model'] ?? 'text-davinci-003',
        ]),
        'timeout' => 30,
    ];
    
    $response = wp_remote_post($api_url, $args);
    
    if (is_wp_error($response)) {
        return [
            'success' => false,
            'error' => $response->get_error_message(),
        ];
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    return [
        'success' => true,
        'data' => $body,
    ];
}
```

## 4. Neue Block-Registrierung 

```php
register_block_type(
    'derleiti/dynamic-content',
    [
        'api_version' => 3,
        'editor_script' => 'derleiti-blocks-editor',
        'editor_style' => 'derleiti-blocks-editor-style',
        'style' => 'derleiti-blocks-style',
        'render_callback' => [$this, 'render_dynamic_content_block'],
        'attributes' => [
            'contentType' => [
                'type' => 'string',
                'default' => 'recent',
            ],
            'postType' => [
                'type' => 'string',
                'default' => 'post',
            ],
            'numberOfItems' => [
                'type' => 'number',
                'default' => 3,
            ],
            'displayFeaturedImage' => [
                'type' => 'boolean',
                'default' => true,
            ],
            'displayExcerpt' => [
                'type' => 'boolean',
                'default' => true,
            ],
        ],
    ]
);
```

## 5. Core Web Vitals Optimierungen

### Preload kritische Assets
```php
function derleiti_preload_assets() {
    echo '<link rel="preload" href="' . get_stylesheet_uri() . '" as="style">';
    echo '<link rel="preload" href="' . get_template_directory_uri() . '/js/navigation.js" as="script">';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
}
add_action('wp_head', 'derleiti_preload_assets', 1);
```

### Inline kritisches CSS
```php
function derleiti_add_critical_css() {
    $critical_css = file_get_contents(get_template_directory() . '/assets/css/critical.css');
    if ($critical_css) {
        echo '<style id="derleiti-critical-css">' . $critical_css . '</style>';
    }
}
add_action('wp_head', 'derleiti_add_critical_css', 2);
```

## 6. Verbesserte Theme-Block-Patterns

```php
register_block_pattern(
    'derleiti/cta-with-background',
    [
        'title' => __('Call-to-Action mit Hintergrundbild', 'derleiti-modern'),
        'description' => __('Ein Call-to-Action-Bereich mit Hintergrundbild und überlagertem Text.', 'derleiti-modern'),
        'categories' => ['derleiti'],
        'content' => '<!-- wp:cover {"url":"https://example.com/wp-content/uploads/2023/01/background.jpg","dimRatio":60,"overlayColor":"primary","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}}} -->
        <div class="wp-block-cover alignfull" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)">
            <span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-60 has-background-dim"></span>
            <img class="wp-block-cover__image-background" alt="" src="https://example.com/wp-content/uploads/2023/01/background.jpg" data-object-fit="cover"/>
            <div class="wp-block-cover__inner-container">
                <!-- wp:heading {"textAlign":"center","level":1,"textColor":"background","fontSize":"xx-large"} -->
                <h1 class="wp-block-heading has-text-align-center has-background-color has-text-color has-xx-large-font-size">Jetzt Beratungstermin vereinbaren</h1>
                <!-- /wp:heading -->
                
                <!-- wp:paragraph {"align":"center","textColor":"background","fontSize":"large"} -->
                <p class="has-text-align-center has-background-color has-text-color has-large-font-size">Wir unterstützen Sie bei Ihrem nächsten Projekt von der Planung bis zur Umsetzung.</p>
                <!-- /wp:paragraph -->
                
                <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
                <div class="wp-block-buttons">
                    <!-- wp:button {"backgroundColor":"accent","textColor":"background","style":{"border":{"radius":"4px"}}} -->
                    <div class="wp-block-button"><a class="wp-block-button__link has-background-color has-accent-background-color has-text-color has-background" style="border-radius:4px">Kontakt aufnehmen</a></div>
                    <!-- /wp:button -->
                </div>
                <!-- /wp:buttons -->
            </div>
        </div>
        <!-- /wp:cover -->',
    ]
);
```

## 7. Performance-Monitoring

```php
function derleiti_add_performance_monitoring() {
    if (current_user_can('manage_options') && is_admin_bar_showing()) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Performance Monitoring nur für Administratoren
            if (performance && performance.getEntriesByType) {
                const perfData = performance.getEntriesByType('navigation')[0];
                const timing = {
                    total: Math.round(perfData.duration),
                    domComplete: Math.round(perfData.domComplete),
                    domInteractive: Math.round(perfData.domInteractive),
                    firstContentfulPaint: 0
                };
                
                // First Contentful Paint
                if (performance.getEntriesByName && window.PerformanceObserver) {
                    const paintEntries = performance.getEntriesByType('paint');
                    const fcpEntry = paintEntries.find(entry => entry.name === 'first-contentful-paint');
                    if (fcpEntry) {
                        timing.firstContentfulPaint = Math.round(fcpEntry.startTime);
                    }
                }
                
                // Admin-Bar Info hinzufügen
                const adminBar = document.getElementById('wpadminbar');
                if (adminBar) {
                    const perfNode = document.createElement('div');
                    perfNode.id = 'derleiti-performance';
                    perfNode.style.float = 'right';
                    perfNode.style.paddingRight = '10px';
                    perfNode.style.lineHeight = '32px';
                    perfNode.style.color = timing.total > 1000 ? '#ff8c8c' : '#8cff8c';
                    perfNode.innerHTML = `
                        <span title="FCP: ${timing.firstContentfulPaint}ms | DOM: ${timing.domInteractive}ms">
                            ⚡ ${timing.total}ms
                        </span>
                    `;
                    adminBar.appendChild(perfNode);
                }
            }
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'derleiti_add_performance_monitoring', 99);
```

## 8. Verbesserte Sicherheit für Plugin-Funktionen

```php
// Verbesserte Nonce-Validierung
if (!check_ajax_referer('derleiti_security_nonce', 'nonce', false)) {
    wp_send_json_error([
        'message' => __('Sicherheitsüberprüfung fehlgeschlagen. Bitte laden Sie die Seite neu.', 'derleiti-plugin'),
        'code' => 'invalid_nonce'
    ], 403);
    wp_die();
}

// Verbesserte Sanitization
$content_type = isset($_POST['contentType']) ? sanitize_key($_POST['contentType']) : 'paragraph';
$prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';

// Berechtigungsprüfung
if (!current_user_can('edit_posts')) {
    wp_send_json_error([
        'message' => __('Sie haben keine ausreichenden Berechtigungen für diese Aktion.', 'derleiti-plugin'),
        'code' => 'insufficient_permissions'
    ], 403);
    wp_die();
}

// Validierung der Einstellungen
$valid_types = ['paragraph', 'list', 'headline', 'code'];
if (!in_array($content_type, $valid_types, true)) {
    wp_send_json_error([
        'message' => __('Ungültiger Inhaltstyp.', 'derleiti-plugin'),
        'code' => 'invalid_content_type'
    ], 400);
    wp_die();
}
```
