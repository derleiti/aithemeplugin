<?php
/**
 * Theme-specific AI Shortcodes
 * 
 * @package Derleiti_Modern
 * @subpackage AI_Integration
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AI shortcodes for the theme
 */
function derleiti_register_ai_shortcodes() {
    add_shortcode('derleiti_ai', 'derleiti_ai_shortcode');
    add_shortcode('derleiti_ai_image', 'derleiti_ai_image_shortcode');
    add_shortcode('derleiti_ai_complete', 'derleiti_ai_complete_shortcode');
}
add_action('init', 'derleiti_register_ai_shortcodes');

/**
 * Shortcode for AI-generated content
 */
function derleiti_ai_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'prompt' => '',
        'type' => 'paragraph',
        'tone' => 'neutral',
        'length' => 'medium',
        'style' => 'default',
        'provider' => '',
        'class' => '',
        'cache' => 'yes',
    ), $atts, 'derleiti_ai');
    
    // Check if prompt is provided
    if (empty($atts['prompt'])) {
        return '<p class="derleiti-ai-error">' . __('Bitte geben Sie einen Prompt an.', 'derleiti-modern') . '</p>';
    }
    
    // Check if AI Integration class exists
    if (!class_exists('Derleiti_AI_Integration')) {
        return '<p class="derleiti-ai-error">' . __('Die KI-Integration ist nicht verfügbar. Bitte installieren und aktivieren Sie das Derleiti Plugin.', 'derleiti-modern') . '</p>';
    }
    
    // Generate cache key if caching is enabled
    $cache_key = null;
    if ($atts['cache'] === 'yes') {
        $cache_key = 'derleiti_ai_content_' . md5($atts['prompt'] . $atts['type'] . $atts['tone'] . $atts['length'] . $atts['provider']);
        $cached_content = get_transient($cache_key);
        
        if ($cached_content !== false) {
            // Wrap in appropriate styling
            return apply_theme_styling_to_ai_content($cached_content, $atts['type'], $atts['style'], $atts['class']);
        }
    }
    
    // Initialize AI Integration
    $ai = new Derleiti_AI_Integration();
    
    // Generate content
    $content = $ai->generate_content(
        $atts['prompt'],
        $atts['type'],
        $atts['tone'],
        $atts['length'],
        $atts['provider']
    );
    
    // Check for errors
    if (is_wp_error($content)) {
        return '<p class="derleiti-ai-error">' . $content->get_error_message() . '</p>';
    }
    
    // Cache the content if enabled
    if ($atts['cache'] === 'yes' && $cache_key) {
        set_transient($cache_key, $content, DAY_IN_SECONDS);
    }
    
    // Apply theme styling
    return apply_theme_styling_to_ai_content($content, $atts['type'], $atts['style'], $atts['class']);
}

/**
 * Shortcode for AI-generated images
 */
function derleiti_ai_image_shortcode($atts) {
    $atts = shortcode_atts(array(
        'prompt' => '',
        'style' => 'realistic',
        'size' => 'medium',
        'provider' => '',
        'class' => '',
        'align' => 'center',
        'cache' => 'yes',
        'alt' => '',
        'caption' => '',
    ), $atts, 'derleiti_ai_image');
    
    // Check if prompt is provided
    if (empty($atts['prompt'])) {
        return '<p class="derleiti-ai-error">' . __('Bitte geben Sie einen Prompt an.', 'derleiti-modern') . '</p>';
    }
    
    // Check if AI Integration class exists
    if (!class_exists('Derleiti_AI_Integration')) {
        return '<p class="derleiti-ai-error">' . __('Die KI-Integration ist nicht verfügbar. Bitte installieren und aktivieren Sie das Derleiti Plugin.', 'derleiti-modern') . '</p>';
    }
    
    // Generate cache key if caching is enabled
    $cache_key = null;
    if ($atts['cache'] === 'yes') {
        $cache_key = 'derleiti_ai_image_' . md5($atts['prompt'] . $atts['style'] . $atts['size'] . $atts['provider']);
        $cached_image = get_transient($cache_key);
        
        if ($cached_image !== false) {
            return render_ai_image($cached_image, $atts);
        }
    }
    
    // Initialize AI Integration
    $ai = new Derleiti_AI_Integration();
    
    // Generate image
    $image = $ai->generate_image(
        $atts['prompt'],
        $atts['style'],
        $atts['size'],
        $atts['provider']
    );
    
    // Check for errors
    if (is_wp_error($image)) {
        return '<p class="derleiti-ai-error">' . $image->get_error_message() . '</p>';
    }
    
    // Cache the image if enabled
    if ($atts['cache'] === 'yes' && $cache_key) {
        set_transient($cache_key, $image, WEEK_IN_SECONDS);
    }
    
    // Render the image
    return render_ai_image($image, $atts);
}

/**
 * Shortcode for a complete AI-generated section
 */
function derleiti_ai_complete_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'prompt' => '',
        'title' => '',  // Optional title override
        'type' => 'section',  // section, callout, card
        'style' => 'default',
        'provider' => '',
        'class' => '',
        'image' => 'no',  // yes/no - whether to generate an image
        'cache' => 'yes',
    ), $atts, 'derleiti_ai_complete');
    
    // Check if prompt is provided
    if (empty($atts['prompt'])) {
        return '<p class="derleiti-ai-error">' . __('Bitte geben Sie einen Prompt an.', 'derleiti-modern') . '</p>';
    }
    
    // Check if AI Integration class exists
    if (!class_exists('Derleiti_AI_Integration')) {
        return '<p class="derleiti-ai-error">' . __('Die KI-Integration ist nicht verfügbar. Bitte installieren und aktivieren Sie das Derleiti Plugin.', 'derleiti-modern') . '</p>';
    }
    
    // Generate cache key if caching is enabled
    $cache_key = null;
    if ($atts['cache'] === 'yes') {
        $cache_key = 'derleiti_ai_complete_' . md5($atts['prompt'] . $atts['type'] . $atts['style'] . $atts['provider'] . $atts['image']);
        $cached_content = get_transient($cache_key);
        
        if ($cached_content !== false) {
            return $cached_content;
        }
    }
    
    // Initialize AI Integration
    $ai = new Derleiti_AI_Integration();
    
    // Generate content
    $content = $ai->generate_content(
        $atts['prompt'] . ' Erstelle strukturierten Inhalt mit einer Überschrift und ausführlichem Text.',
        'paragraph',
        'informative',
        'medium',
        $atts['provider']
    );
    
    // Check for errors
    if (is_wp_error($content)) {
        return '<p class="derleiti-ai-error">' . $content->get_error_message() . '</p>';
    }
    
    // Generate image if requested
    $image_html = '';
    if ($atts['image'] === 'yes') {
        $image = $ai->generate_image(
            $atts['prompt'],
            'realistic',
            'medium',
            $atts['provider']
        );
        
        if (!is_wp_error($image)) {
            $image_html = '<div class="derleiti-ai-complete-image">';
            $image_html .= '<img src="' . esc_url($image['url']) . '" alt="' . esc_attr($atts['prompt']) . '">';
            $image_html .= '</div>';
        }
    }
    
    // Use custom title if provided, otherwise extract from content
    $title = '';
    if (!empty($atts['title'])) {
        $title = $atts['title'];
    } else {
        // Extract first line as title if it looks like a heading
        $lines = explode("\n", $content);
        $first_line = trim($lines[0]);
        
        if (strpos($first_line, '<h') === 0 || strlen($first_line) < 100) {
            $title = strip_tags($first_line);
            // Remove the first line from content
            array_shift($lines);
            $content = implode("\n", $lines);
        }
    }
    
    // Determine container class based on type
    $container_class = 'derleiti-ai-complete derleiti-ai-' . $atts['type'];
    
    // Add style class if not default
    if ($atts['style'] !== 'default') {
        $container_class .= ' derleiti-ai-style-' . $atts['style'];
    }
    
    // Add custom class if provided
    if (!empty($atts['class'])) {
        $container_class .= ' ' . $atts['class'];
    }
    
    // Build the complete HTML
    $html = '<div class="' . esc_attr($container_class) . '">';
    
    // Add icon based on style/type
    $html .= '<div class="derleiti-ai-complete-header">';
    if (!empty($title)) {
        $html .= '<h3 class="derleiti-ai-complete-title">' . esc_html($title) . '</h3>';
    }
    $html .= '</div>';
    
    // Add image if generated
    if (!empty($image_html)) {
        $html .= $image_html;
    }
    
    // Add content
    $html .= '<div class="derleiti-ai-complete-content">';
    $html .= $content;
    $html .= '</div>';
    
    // Add AI attribution
    $html .= '<div class="derleiti-ai-attribution">';
    $html .= '<span class="derleiti-ai-icon">✨</span>';
    $html .= '<span class="derleiti-ai-text">' . __('KI-generierter Inhalt', 'derleiti-modern') . '</span>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    // Cache the output if enabled
    if ($atts['cache'] === 'yes' && $cache_key) {
        set_transient($cache_key, $html, DAY_IN_SECONDS);
    }
    
    return $html;
}

/**
 * Apply theme styling to AI-generated content
 */
function apply_theme_styling_to_ai_content($content, $type, $style, $class) {
    // Start with base class
    $wrapper_class = 'derleiti-ai-block';
    
    // Add content type class
    $wrapper_class .= ' ' . $type;
    
    // Add style class if not default
    if ($style !== 'default') {
        $wrapper_class .= ' ' . $style;
    }
    
    // Add custom class if provided
    if (!empty($class)) {
        $wrapper_class .= ' ' . $class;
    }
    
    // Wrap content in theme-styled container
    $html = '<div class="' . esc_attr($wrapper_class) . '">';
    $html .= $content;
    $html .= '</div>';
    
    return $html;
}

/**
 * Render AI-generated image with theme styling
 */
function render_ai_image($image, $atts) {
    // Basic sanity check
    if (!isset($image['url'])) {
        return '<p class="derleiti-ai-error">' . __('Ungültiges Bild-Format.', 'derleiti-modern') . '</p>';
    }
    
    // Prepare alt text
    $alt_text = !empty($atts['alt']) ? $atts['alt'] : $atts['prompt'];
    
    // Generate CSS classes
    $img_class = 'derleiti-ai-image';
    
    // Add alignment class
    if (!empty($atts['align'])) {
        $img_class .= ' align' . $atts['align'];
    }
    
    // Add custom class if provided
    if (!empty($atts['class'])) {
        $img_class .= ' ' . $atts['class'];
    }
    
    // Build HTML
    $html = '<figure class="' . esc_attr($img_class) . '">';
    $html .= '<img src="' . esc_url($image['url']) . '" alt="' . esc_attr($alt_text) . '" class="ai-generated-img">';
    
    // Add caption if provided
    if (!empty($atts['caption'])) {
        $html .= '<figcaption>' . esc_html($atts['caption']) . '</figcaption>';
    }
    
    // Add AI attribution
    $html .= '<div class="derleiti-ai-attribution">';
    $html .= '<span class="derleiti-ai-icon">✨</span>';
    $html .= '<span class="derleiti-ai-text">' . __('KI-generiertes Bild', 'derleiti-modern') . '</span>';
    $html .= '</div>';
    
    $html .= '</figure>';
    
    return $html;
}
