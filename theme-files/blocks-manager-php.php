<?php
/**
 * Enhanced Block Registration System for FSE
 *
 * @package Derleiti_Plugin
 * @subpackage Blocks
 * @version 2.6.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modern Block Registration and FSE Support
 */
class Derleiti_Blocks_Manager {
    /**
     * Block assets directory URL
     * @var string
     */
    private $assets_url;

    /**
     * Block assets directory path
     * @var string
     */
    private $assets_path;

    /**
     * Constructor
     */
    public function __construct() {
        $this->assets_url = DERLEITI_PLUGIN_URL . 'blocks/';
        $this->assets_path = DERLEITI_PLUGIN_PATH . 'blocks/';
    }

    /**
     * Initialize the blocks manager
     */
    public function init() {
        // Set up block category
        add_filter('block_categories_all', [$this, 'register_block_category'], 10, 2);
        
        // Register block types
        add_action('init', [$this, 'register_blocks']);
        
        // Enqueue block assets
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
        add_action('enqueue_block_assets', [$this, 'enqueue_frontend_assets']);
        
        // Register block patterns
        add_action('init', [$this, 'register_block_patterns']);
        
        // Register custom block variations
        add_action('enqueue_block_editor_assets', [$this, 'register_block_variations']);
    }

    /**
     * Register block category
     */
    public function register_block_category($categories, $post) {
        return array_merge(
            $categories,
            [
                [
                    'slug' => 'derleiti-blocks',
                    'title' => __('Derleiti Blocks', 'derleiti-plugin'),
                    'icon' => 'layout',
                ],
            ]
        );
    }

    /**
     * Register all blocks
     */
    public function register_blocks() {
        // Get all block.json files
        $block_json_files = glob($this->assets_path . '*/block.json');
        
        if (!empty($block_json_files)) {
            // Register each block
            foreach ($block_json_files as $block_json) {
                register_block_type_from_metadata(dirname($block_json));
            }
        }
        
        // Legacy blocks that don't use block.json yet
        $this->register_legacy_blocks();
    }
    
    /**
     * Register legacy blocks
     */
    private function register_legacy_blocks() {
        // Feature Grid Block
        register_block_type('derleiti/feature-grid', [
            'editor_script' => 'derleiti-blocks-editor',
            'editor_style' => 'derleiti-blocks-editor-style',
            'style' => 'derleiti-blocks-style',
            'script' => 'derleiti-blocks-frontend',
            'attributes' => [
                'columns' => [
                    'type' => 'number',
                    'default' => 3,
                ],
                'items' => [
                    'type' => 'array',
                    'default' => [],
                    'items' => [
                        'type' => 'object',
                    ],
                ],
                'backgroundColor' => [
                    'type' => 'string',
                    'default' => '',
                ],
                'textColor' => [
                    'type' => 'string',
                    'default' => '',
                ],
                'align' => [
                    'type' => 'string',
                    'default' => 'wide',
                ],
            ],
            'render_callback' => [$this, 'render_feature_grid_block'],
        ]);
        
        // AI Content Generator Block
        register_block_type('derleiti/ai-content', [
            'editor_script' => 'derleiti-blocks-editor',
            'editor_style' => 'derleiti-blocks-editor-style',
            'style' => 'derleiti-blocks-style',
            'script' => 'derleiti-blocks-frontend',
            'attributes' => [
                'prompt' => [
                    'type' => 'string',
                    'default' => '',
                ],
                'contentType' => [
                    'type' => 'string',
                    'default' => 'paragraph',
                ],
                'tone' => [
                    'type' => 'string',
                    'default' => 'neutral',
                ],
                'length' => [
                    'type' => 'string',
                    'default' => 'medium',
                ],
                'generatedContent' => [
                    'type' => 'string',
                    'default' => '',
                ],
                'provider' => [
                    'type' => 'string',
                    'default' => '',
                ],
            ],
            'render_callback' => [$this, 'render_ai_content_block'],
        ]);
    }

    /**
     * Enqueue block editor assets
     */
    public function enqueue_editor_assets() {
        // Check if build file exists
        if (file_exists($this->assets_path . 'build/index.asset.php')) {
            $asset_file = require_once $this->assets_path . 'build/index.asset.php';
            
            // Editor script
            wp_enqueue_script(
                'derleiti-blocks-editor',
                $this->assets_url . 'build/index.js',
                $asset_file['dependencies'],
                $asset_file['version'],
                true
            );
            
            // Editor style
            wp_enqueue_style(
                'derleiti-blocks-editor-style',
                $this->assets_url . 'build/index.css',
                [],
                $asset_file['version']
            );
        } else {
            // Fallback for traditional scripts
            wp_enqueue_script(
                'derleiti-blocks-editor',
                $this->assets_url . 'js/blocks.editor.js',
                ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-compose'],
                DERLEITI_PLUGIN_VERSION,
                true
            );
            
            wp_enqueue_style(
                'derleiti-blocks-editor-style',
                $this->assets_url . 'css/blocks.editor.css',
                ['wp-edit-blocks'],
                DERLEITI_PLUGIN_VERSION
            );
        }
        
        // Localize script
        wp_localize_script(
            'derleiti-blocks-editor',
            'derleitiBlocksData',
            [
                'pluginUrl' => DERLEITI_PLUGIN_URL,
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('derleiti_blocks_nonce'),
                'placeholder' => $this->assets_url . 'images/placeholder.jpg',
                'strings' => [
                    'generateContent' => __('Inhalt generieren', 'derleiti-plugin'),
                    'regenerateContent' => __('Inhalt neu generieren', 'derleiti-plugin'),
                    'generating' => __('Generiere...', 'derleiti-plugin'),
                    'chooseImage' => __('Bild auswählen', 'derleiti-plugin'),
                    'replaceImage' => __('Bild ersetzen', 'derleiti-plugin'),
                    'removeImage' => __('Bild entfernen', 'derleiti-plugin'),
                ],
            ]
        );
    }

    /**
     * Enqueue frontend assets for blocks
     */
    public function enqueue_frontend_assets() {
        // Check if on admin
        if (is_admin()) {
            return;
        }
        
        // Check if build exists
        if (file_exists($this->assets_path . 'build/style-index.css')) {
            // Frontend style from build
            wp_enqueue_style(
                'derleiti-blocks-style',
                $this->assets_url . 'build/style-index.css',
                [],
                DERLEITI_PLUGIN_VERSION
            );
            
            // Frontend script from build if exists
            if (file_exists($this->assets_path . 'build/frontend.js')) {
                wp_enqueue_script(
                    'derleiti-blocks-frontend',
                    $this->assets_url . 'build/frontend.js',
                    ['jquery'],
                    DERLEITI_PLUGIN_VERSION,
                    true
                );
            }
        } else {
            // Fallback for traditional styles
            wp_enqueue_style(
                'derleiti-blocks-style',
                $this->assets_url . 'css/blocks.style.css',
                [],
                DERLEITI_PLUGIN_VERSION
            );
            
            // Fallback for traditional scripts
            if (file_exists($this->assets_path . 'js/blocks.frontend.js')) {
                wp_enqueue_script(
                    'derleiti-blocks-frontend',
                    $this->assets_url . 'js/blocks.frontend.js',
                    ['jquery'],
                    DERLEITI_PLUGIN_VERSION,
                    true
                );
            }
        }
    }
    
    /**
     * Register block patterns
     */
    public function register_block_patterns() {
        // Register pattern category if function exists
        if (function_exists('register_block_pattern_category')) {
            register_block_pattern_category('derleiti-patterns', [
                'label' => __('Derleiti Patterns', 'derleiti-plugin')
            ]);
        }
        
        // Register patterns if function exists
        if (function_exists('register_block_pattern')) {
            // Hero Section Pattern
            register_block_pattern(
                'derleiti/hero-section',
                [
                    'title' => __('Hero Section', 'derleiti-plugin'),
                    'description' => __('A hero section with heading, text and button.', 'derleiti-plugin'),
                    'categories' => ['derleiti-patterns'],
                    'content' => $this->get_pattern_content('hero-section'),
                ]
            );
            
            // Features Grid Pattern
            register_block_pattern(
                'derleiti/features-grid',
                [
                    'title' => __('Features Grid', 'derleiti-plugin'),
                    'description' => __('A grid of features with icons and text.', 'derleiti-plugin'),
                    'categories' => ['derleiti-patterns'],
                    'content' => $this->get_pattern_content('features-grid'),
                ]
            );
            
            // Testimonial Section Pattern
            register_block_pattern(
                'derleiti/testimonial-section',
                [
                    'title' => __('Testimonial Section', 'derleiti-plugin'),
                    'description' => __('A testimonial section with quotes and images.', 'derleiti-plugin'),
                    'categories' => ['derleiti-patterns'],
                    'content' => $this->get_pattern_content('testimonial-section'),
                ]
            );
            
            // Call to Action Pattern
            register_block_pattern(
                'derleiti/call-to-action',
                [
                    'title' => __('Call to Action', 'derleiti-plugin'),
                    'description' => __('A call to action section with background, heading, and button.', 'derleiti-plugin'),
                    'categories' => ['derleiti-patterns'],
                    'content' => $this->get_pattern_content('call-to-action'),
                ]
            );
            
            // Project Showcase Pattern
            register_block_pattern(
                'derleiti/project-showcase',
                [
                    'title' => __('Project Showcase', 'derleiti-plugin'),
                    'description' => __('A showcase for portfolio projects with images and details.', 'derleiti-plugin'),
                    'categories' => ['derleiti-patterns'],
                    'content' => $this->get_pattern_content('project-showcase'),
                ]
            );
        }
    }
    
    /**
     * Register custom block variations
     */
    public function register_block_variations() {
        // If blocks.js is enqueued, we can add our variations script
        wp_add_inline_script(
            'derleiti-blocks-editor',
            '
            (function() {
                window.addEventListener("load", function() {
                    if (window.wp && window.wp.blocks && window.wp.blocks.registerBlockVariation) {
                        // Button with Icon Right Variation
                        wp.blocks.registerBlockVariation("core/button", {
                            name: "button-icon-right",
                            title: "Button with Icon Right",
                            description: "Button with an icon on the right side",
                            attributes: {
                                className: "has-icon-right"
                            },
                            isDefault: false,
                            icon: "button",
                            scope: ["inserter"]
                        });
                        
                        // Button with Icon Left Variation
                        wp.blocks.registerBlockVariation("core/button", {
                            name: "button-icon-left",
                            title: "Button with Icon Left",
                            description: "Button with an icon on the left side",
                            attributes: {
                                className: "has-icon-left"
                            },
                            isDefault: false,
                            icon: "button",
                            scope: ["inserter"]
                        });
                        
                        // Hero Container Variation
                        wp.blocks.registerBlockVariation("core/group", {
                            name: "hero-container",
                            title: "Hero Container",
                            description: "A container for hero sections",
                            attributes: {
                                className: "is-hero-container",
                                align: "full"
                            },
                            isDefault: false,
                            icon: "align-center",
                            scope: ["inserter"]
                        });
                        
                        // Section Container Variation
                        wp.blocks.registerBlockVariation("core/group", {
                            name: "section-container",
                            title: "Section Container",
                            description: "A container for page sections",
                            attributes: {
                                className: "is-section-container",
                                align: "full"
                            },
                            isDefault: false,
                            icon: "align-center",
                            scope: ["inserter"]
                        });
                    }
                });
            })();
            ',
            'after'
        );
    }
    
    /**
     * Get pattern content from file
     */
    private function get_pattern_content($pattern_name) {
        $file_path = $this->assets_path . 'patterns/' . $pattern_name . '.html';
        
        if (file_exists($file_path)) {
            return file_get_contents($file_path);
        }
        
        // Default patterns if file doesn't exist
        switch ($pattern_name) {
            case 'hero-section':
                return '<!-- wp:group {"align":"full","backgroundColor":"secondary","textColor":"background","layout":{"type":"constrained"}} -->
                <div class="wp-block-group alignfull has-background-color has-secondary-background-color has-text-color has-background"><!-- wp:heading {"textAlign":"center","level":1,"fontSize":"huge"} -->
                <h1 class="has-text-align-center has-huge-font-size">Welcome to Derleiti Modern</h1>
                <!-- /wp:heading -->

                <!-- wp:paragraph {"align":"center","fontSize":"large"} -->
                <p class="has-text-align-center has-large-font-size">A powerful WordPress theme for modern websites.</p>
                <!-- /wp:paragraph -->

                <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
                <div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"primary"} -->
                <div class="wp-block-button"><a class="wp-block-button__link has-primary-background-color has-background wp-element-button">Get Started</a></div>
                <!-- /wp:button --></div>
                <!-- /wp:buttons --></div>
                <!-- /wp:group -->';
                
            case 'features-grid':
                return '<!-- wp:columns {"align":"wide"} -->
                <div class="wp-block-columns alignwide"><!-- wp:column -->
                <div class="wp-block-column"><!-- wp:heading {"textAlign":"center","level":3} -->
                <h3 class="has-text-align-center">Feature 1</h3>
                <!-- /wp:heading -->

                <!-- wp:paragraph {"align":"center"} -->
                <p class="has-text-align-center">Description of the first amazing feature of your product or service.</p>
                <!-- /wp:paragraph --></div>
                <!-- /wp:column -->

                <!-- wp:column -->
                <div class="wp-block-column"><!-- wp:heading {"textAlign":"center","level":3} -->
                <h3 class="has-text-align-center">Feature 2</h3>
                <!-- /wp:heading -->

                <!-- wp:paragraph {"align":"center"} -->
                <p class="has-text-align-center">Description of the second amazing feature of your product or service.</p>
                <!-- /wp:paragraph --></div>
                <!-- /wp:column -->

                <!-- wp:column -->
                <div class="wp-block-column"><!-- wp:heading {"textAlign":"center","level":3} -->
                <h3 class="has-text-align-center">Feature 3</h3>
                <!-- /wp:heading -->

                <!-- wp:paragraph {"align":"center"} -->
                <p class="has-text-align-center">Description of the third amazing feature of your product or service.</p>
                <!-- /wp:paragraph --></div>
                <!-- /wp:column --></div>
                <!-- /wp:columns -->';
                
            default:
                return '';
        }
    }
    
    /**
     * Render feature grid block
     */
    public function render_feature_grid_block($attributes, $content) {
        $columns = isset($attributes['columns']) ? intval($attributes['columns']) : 3;
        $items = isset($attributes['items']) ? $attributes['items'] : [];
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
        
        if (!empty($items) && is_array($items)) {
            foreach ($items as $item) {
                $html .= '<div class="derleiti-feature-grid-item">';
                
                if (!empty($item['iconType']) && $item['iconType'] === 'image' && !empty($item['imageUrl'])) {
                    $html .= '<div class="derleiti-feature-grid-image">';
                    $html .= '<img src="' . esc_url($item['imageUrl']) . '" alt="' . esc_attr($item['title'] ?? '') . '">';
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
        } else {
            $html .= '<div class="derleiti-feature-grid-placeholder">';
            $html .= __('Add feature items in the editor.', 'derleiti-plugin');
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render AI content block
     */
    public function render_ai_content_block($attributes, $content) {
        $generated_content = isset($attributes['generatedContent']) ? $attributes['generatedContent'] : '';
        $prompt = isset($attributes['prompt']) ? $attributes['prompt'] : '';
        
        if (empty($generated_content) && !empty($prompt)) {
            // Try to generate content if we have a prompt but no generated content
            $content_type = isset($attributes['contentType']) ? $attributes['contentType'] : 'paragraph';
            $tone = isset($attributes['tone']) ? $attributes['tone'] : 'neutral';
            $length = isset($attributes['length']) ? $attributes['length'] : 'medium';
            $provider = isset($attributes['provider']) ? $attributes['provider'] : '';
            
            // Check if AI Integration class exists
            if (class_exists('Derleiti_AI_Integration')) {
                $ai = new Derleiti_AI_Integration();
                $generated_content = $ai->generate_content($prompt, $content_type, $tone, $length, $provider);
                
                if (is_wp_error($generated_content)) {
                    return '<div class="derleiti-ai-error">' . esc_html($generated_content->get_error_message()) . '</div>';
                }
            } else {
                return '<div class="derleiti-ai-error">' . __('KI-Integration ist nicht verfügbar.', 'derleiti-plugin') . '</div>';
            }
        }
        
        if (empty($generated_content)) {
            return '<div class="derleiti-ai-placeholder">' . __('Kein KI-Inhalt generiert. Bitte geben Sie einen Prompt ein und generieren Sie Inhalt im Block-Editor.', 'derleiti-plugin') . '</div>';
        }
        
        return '<div class="derleiti-ai-content">' . wp_kses_post($generated_content) . '</div>';
    }
}
