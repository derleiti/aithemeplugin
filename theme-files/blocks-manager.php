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