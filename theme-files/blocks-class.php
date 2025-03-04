<?php
/**
 * Verwaltet die benutzerdefinierten Gutenberg-Blöcke
 *
 * @package Derleiti_Plugin
 * @subpackage Blocks
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Die Blocks-Klasse des Plugins
 */
class Derleiti_Blocks {

    /**
     * Initialisiere die Blocks-Klasse
     */
    public function init() {
        // Register block scripts and styles
        add_action('init', array($this, 'register_block_assets'));

        // Register custom blocks
        add_action('init', array($this, 'register_blocks'));

        // Add custom block categories
        add_filter('block_categories_all', array($this, 'register_block_categories'), 10, 2);

        // Enqueue block assets for editor
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));

        // Register dynamic blocks render callbacks
        $this->register_dynamic_blocks();
    }

    /**
     * Registriere Block-Assets
     */
    public function register_block_assets() {
        // Block stylesheet für Editor und Frontend
        wp_register_style(
            'derleiti-blocks-style',
            DERLEITI_PLUGIN_URL . 'blocks/css/blocks.style.css',
            array(),
                          DERLEITI_PLUGIN_VERSION
        );

        // Block JavaScript für Frontend
        wp_register_script(
            'derleiti-blocks-frontend',
            DERLEITI_PLUGIN_URL . 'blocks/js/blocks.frontend.js',
            array('jquery'),
                           DERLEITI_PLUGIN_VERSION,
                           true
        );
    }

    /**
     * Registriere Block-Kategorien
     */
    public function register_block_categories($categories, $post) {
        return array_merge(
            $categories,
            array(
                array(
                    'slug'  => 'derleiti-blocks',
                    'title' => __('Derleiti Blocks', 'derleiti-plugin'),
                      'icon'  => 'layout',
                ),
            )
        );
    }

    /**
     * Registriere Blöcke
     */
    public function register_blocks() {
        // Prüfe, ob Blocks API verfügbar ist
        if (!function_exists('register_block_type')) {
            return;
        }

        // Feature Grid Block
        register_block_type('derleiti/feature-grid', array(
            'editor_script'   => 'derleiti-blocks-editor',
            'editor_style'    => 'derleiti-blocks-editor-style',
            'style'           => 'derleiti-blocks-style',
            'script'          => 'derleiti-blocks-frontend',
            'attributes'      => array(
                'columns' => array(
                    'type'    => 'number',
                    'default' => 3,
                ),
                'items' => array(
                    'type'    => 'array',
                    'default' => array(),
                                 'items'   => array(
                                     'type' => 'object',
                                 ),
                ),
                'backgroundColor' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'textColor' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'align' => array(
                    'type'    => 'string',
                    'default' => 'wide',
                ),
            ),
            'render_callback' => array($this, 'render_feature_grid_block'),
        ));

        // Testimonial Block
        register_block_type('derleiti/testimonial', array(
            'editor_script'   => 'derleiti-blocks-editor',
            'editor_style'    => 'derleiti-blocks-editor-style',
            'style'           => 'derleiti-blocks-style',
            'script'          => 'derleiti-blocks-frontend',
            'attributes'      => array(
                'quote' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'author' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'company' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'imageUrl' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'imageId' => array(
                    'type' => 'number',
                ),
                'backgroundColor' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'textColor' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
            ),
            'render_callback' => array($this, 'render_testimonial_block'),
        ));

        // AI Content Generator Block
        register_block_type('derleiti/ai-content', array(
            'editor_script'   => 'derleiti-blocks-editor',
            'editor_style'    => 'derleiti-blocks-editor-style',
            'style'           => 'derleiti-blocks-style',
            'script'          => 'derleiti-blocks-frontend',
            'attributes'      => array(
                'prompt' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'contentType' => array(
                    'type'    => 'string',
                    'default' => 'paragraph',
                ),
                'tone' => array(
                    'type'    => 'string',
                    'default' => 'neutral',
                ),
                'length' => array(
                    'type'    => 'string',
                    'default' => 'medium',
                ),
                'generatedContent' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
            ),
            'render_callback' => array($this, 'render_ai_content_block'),
        ));

        // Advanced Call-to-Action Block
        register_block_type('derleiti/advanced-cta', array(
            'editor_script'   => 'derleiti-blocks-editor',
            'editor_style'    => 'derleiti-blocks-editor-style',
            'style'           => 'derleiti-blocks-style',
            'script'          => 'derleiti-blocks-frontend',
            'attributes'      => array(
                'title' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'description' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'buttonText' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'buttonUrl' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'buttonNewTab' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
                'backgroundType' => array(
                    'type'    => 'string',
                    'default' => 'color',
                ),
                'backgroundColor' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'backgroundGradient' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'backgroundImageUrl' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'backgroundImageId' => array(
                    'type' => 'number',
                ),
                'textColor' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'buttonColor' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'buttonTextColor' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'align' => array(
                    'type'    => 'string',
                    'default' => 'center',
                ),
                'padding' => array(
                    'type'    => 'object',
                    'default' => array(
                        'top'    => '40px',
                        'right'  => '40px',
                        'bottom' => '40px',
                        'left'   => '40px',
                    ),
                ),
                'borderRadius' => array(
                    'type'    => 'string',
                    'default' => '10px',
                ),
            ),
            'render_callback' => array($this, 'render_advanced_cta_block'),
        ));
    }

    /**
     * Registriere dynamische Blöcke
     */
    private function register_dynamic_blocks() {
        // Hier können weitere dynamische Blöcke registriert werden
    }

    /**
     * Lade Editor-Assets
     */
    public function enqueue_editor_assets() {
        wp_enqueue_style(
            'derleiti-blocks-editor-style',
            DERLEITI_PLUGIN_URL . 'blocks/css/blocks.editor.css',
            array('wp-edit-blocks'),
                         DERLEITI_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'derleiti-blocks-editor',
            DERLEITI_PLUGIN_URL . 'blocks/js/blocks.editor.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
                          DERLEITI_PLUGIN_VERSION,
                          true
        );

        wp_localize_script(
            'derleiti-blocks-editor',
            'derleitiBlocksData',
            array(
                'pluginUrl'   => DERLEITI_PLUGIN_URL,
                'ajaxUrl'     => admin_url('admin-ajax.php'),
                  'nonce'       => wp_create_nonce('derleiti_blocks_nonce'),
                  'placeholder' => DERLEITI_PLUGIN_URL . 'blocks/img/placeholder.jpg',
                  'strings'     => array(
                      'generateContent'   => __('Inhalt generieren', 'derleiti-plugin'),
                                         'regenerateContent' => __('Inhalt neu generieren', 'derleiti-plugin'),
                                         'generating'        => __('Generiere...', 'derleiti-plugin'),
                                         'chooseImage'       => __('Bild auswählen', 'derleiti-plugin'),
                                         'replaceImage'      => __('Bild ersetzen', 'derleiti-plugin'),
                                         'removeImage'       => __('Bild entfernen', 'derleiti-plugin'),
                  ),
            )
        );
    }

    /**
     * Render Feature Grid Block
     */
    public function render_feature_grid_block($attributes, $content) {
        $columns = isset($attributes['columns']) ? intval($attributes['columns']) : 3;
        $items = isset($attributes['items']) ? $attributes['items'] : array();
        $background_color = isset($attributes['backgroundColor']) ? $attributes['backgroundColor'] : '';
        $text_color = isset($attributes['textColor']) ? $attributes['textColor'] : '';
        $align = isset($attributes['align']) ? $attributes['align'] : 'wide';

        $style = '';
        if (!empty($background_color)) {
            $style .= 'background-color: ' . esc_attr($background_color) . ';';
        }
        if (!empty($text_color)) {
            $style .= 'color: ' . esc_attr($text_color) . ';';
        }

        $class_names = 'derleiti-feature-grid';
        if (!empty($align)) {
            $class_names .= ' align' . esc_attr($align);
        }

        $html = '<div class="' . esc_attr($class_names) . '" style="' . esc_attr($style) . '">';
        $html .= '<div class="derleiti-feature-grid-inner columns-' . esc_attr($columns) . '">';

        if (!empty($items)) {
            foreach ($items as $item) {
                $html .= '<div class="derleiti-feature-grid-item">';

                if (!empty($item['iconType']) && $item['iconType'] === 'image' && !empty($item['imageUrl'])) {
                    $html .= '<div class="derleiti-feature-grid-image">';
                    $html .= '<img src="' . esc_url($item['imageUrl']) . '" alt="' . esc_attr($item['title']) . '">';
                    $html .= '</div>';
                } elseif (!empty($item['iconType']) && $item['iconType'] === 'icon' && !empty($item['icon'])) {
                    $html .= '<div class="derleiti-feature-grid-icon">';
                    $html .= '<span class="' . esc_attr($item['icon']) . '"></span>';
                    $html .= '</div>';
                }

                if (!empty($item['title'])) {
                    $html .= '<h3 class="derleiti-feature-grid-title">' . esc_html($item['title']) . '</h3>';
                }

                if (!empty($item['description'])) {
                    $html .= '<div class="derleiti-feature-grid-description">' . wp_kses_post($item['description']) . '</div>';
                }

                $html .= '</div>';
            }
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render Testimonial Block
     */
    public function render_testimonial_block($attributes, $content) {
        $quote = isset($attributes['quote']) ? $attributes['quote'] : '';
        $author = isset($attributes['author']) ? $attributes['author'] : '';
        $company = isset($attributes['company']) ? $attributes['company'] : '';
        $image_url = isset($attributes['imageUrl']) ? $attributes['imageUrl'] : '';
        $background_color = isset($attributes['backgroundColor']) ? $attributes['backgroundColor'] : '';
        $text_color = isset($attributes['textColor']) ? $attributes['textColor'] : '';

        $style = '';
        if (!empty($background_color)) {
            $style .= 'background-color: ' . esc_attr($background_color) . ';';
        }
        if (!empty($text_color)) {
            $style .= 'color: ' . esc_attr($text_color) . ';';
        }

        $html = '<div class="derleiti-testimonial" style="' . esc_attr($style) . '">';
        $html .= '<div class="derleiti-testimonial-content">';
        if (!empty($quote)) {
            $html .= '<div class="derleiti-testimonial-quote">' . wp_kses_post($quote) . '</div>';
        }
        $html .= '<div class="derleiti-testimonial-footer">';
        if (!empty($image_url)) {
            $html .= '<div class="derleiti-testimonial-image">';
            $html .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($author) . '">';
            $html .= '</div>';
        }
        $html .= '<div class="derleiti-testimonial-info">';
        if (!empty($author)) {
            $html .= '<div class="derleiti-testimonial-author">' . esc_html($author) . '</div>';
        }
        if (!empty($company)) {
            $html .= '<div class="derleiti-testimonial-company">' . esc_html($company) . '</div>';
        }
        $html .= '</div>'; // .testimonial-info
        $html .= '</div>'; // .testimonial-footer
        $html .= '</div>'; // .testimonial-content
        $html .= '</div>'; // .testimonial

        return $html;
    }

    /**
     * Render AI Content Block
     */
    public function render_ai_content_block($attributes, $content) {
        $generated_content = isset($attributes['generatedContent']) ? $attributes['generatedContent'] : '';

        if (empty($generated_content)) {
            return '<div class="derleiti-ai-content"><em>' . __('Kein Inhalt generiert.', 'derleiti-plugin') . '</em></div>';
        }

        $html = '<div class="derleiti-ai-block">';
        $html .= wp_kses_post($generated_content);
        $html .= '</div>';

        return $html;
    }

    /**
     * Render Advanced CTA Block
     */
    public function render_advanced_cta_block($attributes, $content) {
        $title = isset($attributes['title']) ? $attributes['title'] : '';
        $description = isset($attributes['description']) ? $attributes['description'] : '';
        $button_text = isset($attributes['buttonText']) ? $attributes['buttonText'] : '';
        $button_url = isset($attributes['buttonUrl']) ? $attributes['buttonUrl'] : '';
        $button_new_tab = isset($attributes['buttonNewTab']) && $attributes['buttonNewTab'] ? true : false;

        $background_type = isset($attributes['backgroundType']) ? $attributes['backgroundType'] : 'color';
        $background_color = isset($attributes['backgroundColor']) ? $attributes['backgroundColor'] : '';
        $background_gradient = isset($attributes['backgroundGradient']) ? $attributes['backgroundGradient'] : '';
        $background_image_url = isset($attributes['backgroundImageUrl']) ? $attributes['backgroundImageUrl'] : '';

        $text_color = isset($attributes['textColor']) ? $attributes['textColor'] : '';
        $button_color = isset($attributes['buttonColor']) ? $attributes['buttonColor'] : '';
        $button_text_color = isset($attributes['buttonTextColor']) ? $attributes['buttonTextColor'] : '';

        $align = isset($attributes['align']) ? $attributes['align'] : 'center';
        $padding = isset($attributes['padding']) ? $attributes['padding'] : array(
            'top'    => '40px',
            'right'  => '40px',
            'bottom' => '40px',
            'left'   => '40px',
        );
        $border_radius = isset($attributes['borderRadius']) ? $attributes['borderRadius'] : '10px';

        // Build container style
        $style = '';
        if ($background_type === 'color' && !empty($background_color)) {
            $style .= 'background-color: ' . esc_attr($background_color) . ';';
        } elseif ($background_type === 'gradient' && !empty($background_gradient)) {
            $style .= 'background: ' . esc_attr($background_gradient) . ';';
        } elseif ($background_type === 'image' && !empty($background_image_url)) {
            $style .= 'background-image: url(' . esc_url($background_image_url) . ');';
            $style .= 'background-size: cover;';
            $style .= 'background-position: center;';
        }
        if (!empty($text_color)) {
            $style .= 'color: ' . esc_attr($text_color) . ';';
        }
        if (!empty($padding)) {
            $style .= 'padding: ' . esc_attr($padding['top']) . ' ' . esc_attr($padding['right']) . ' ' . esc_attr($padding['bottom']) . ' ' . esc_attr($padding['left']) . ';';
        }
        if (!empty($border_radius)) {
            $style .= 'border-radius: ' . esc_attr($border_radius) . ';';
        }

        // Build button style
        $button_style = '';
        if (!empty($button_color)) {
            $button_style .= 'background-color: ' . esc_attr($button_color) . ';';
        }
        if (!empty($button_text_color)) {
            $button_style .= 'color: ' . esc_attr($button_text_color) . ';';
        }

        $text_align = 'text-align: ' . esc_attr($align) . ';';

        $html = '<div class="derleiti-advanced-cta" style="' . esc_attr($style) . '">';
        $html .= '<div class="derleiti-advanced-cta-content" style="' . esc_attr($text_align) . '">';

        if (!empty($title)) {
            $html .= '<h2 class="derleiti-advanced-cta-title">' . esc_html($title) . '</h2>';
        }
        if (!empty($description)) {
            $html .= '<div class="derleiti-advanced-cta-description">' . wp_kses_post($description) . '</div>';
        }
        if (!empty($button_text) && !empty($button_url)) {
            $target = $button_new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';
            $html .= '<div class="derleiti-advanced-cta-button-wrap">';
            $html .= '<a href="' . esc_url($button_url) . '" class="derleiti-advanced-cta-button" style="' . esc_attr($button_style) . '"' . $target . '>' . esc_html($button_text) . '</a>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
