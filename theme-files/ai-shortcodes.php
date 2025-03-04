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
        'prompt'   => '',
        'type'     => 'paragraph',
        'tone'     => 'neutral',
        'length'   => 'medium',
        'style'    => 'default',
        'provider' => '',
        'class'    => '',
        'cache'    => 'yes',
    ), $atts, 'derleiti_ai');

    // Check if prompt is provided
    if (empty($atts['prompt'])) {
        return '<p class="derleiti-ai-error">' . __('Bitte geben Sie einen Prompt an.', 'derleiti-modern') . '</p>';
    }

    // Check if AI Integration class exists
    if (!class_exists('Derleiti_AI_Integration')) {
        return '<p class="derleiti-ai-error">' . __('Die KI-Integration ist nicht verfügbar. Bitte installieren und aktivieren Sie das Derleiti Plugin.', 'derleiti-modern') . '</p>';
    }

    // Check cache
    $cache_key = null;
    if ($atts['cache'] === 'yes') {
        $cache_key = 'derleiti_ai_content_' . md5($atts['prompt'] . $atts['type'] . $atts['tone'] . $atts['length'] . $atts['provider']);
        $cached_content = get_transient($cache_key);
        if ($cached_content !== false) {
            return apply_theme_styling_to_ai_content($cached_content, $atts['type'], $atts['style'], $atts['class']);
        }
    }

    // Initialize AI Integration and generate content
    $ai = new Derleiti_AI_Integration();
    $ai_content = $ai->generate_content(
        $atts['prompt'],
        $atts['type'],
        $atts['tone'],
        $atts['length'],
        $atts['provider']
    );

    // Error check
    if (is_wp_error($ai_content)) {
        return '<p class="derleiti-ai-error">' . $ai_content->get_error_message() . '</p>';
    }

    // Cache content if enabled
    if ($atts['cache'] === 'yes' && $cache_key) {
        set_transient($cache_key, $ai_content, DAY_IN_SECONDS);
    }

    // Apply theme styling
    return apply_theme_styling_to_ai_content($ai_content, $atts['type'], $atts['style'], $atts['class']);
}

/**
 * Shortcode for AI-generated images
 */
function derleiti_ai_image_shortcode($atts) {
    $atts = shortcode_atts(array(
        'prompt'   => '',
        'style'    => 'realistic',
        'size'     => 'medium',
        'provider' => '',
        'class'    => '',
        'align'    => 'center',
        'cache'    => 'yes',
        'alt'      => '',
        'caption'  => '',
    ), $atts, 'derleiti_ai_image');

    if (empty($atts['prompt'])) {
        return '<p class="derleiti-ai-error">' . __('Bitte geben Sie einen Prompt an.', 'derleiti-modern') . '</p>';
    }

    if (!class_exists('Derleiti_AI_Integration')) {
        return '<p class="derleiti-ai-error">' . __('Die KI-Integration ist nicht verfügbar. Bitte installieren und aktivieren Sie das Derleiti Plugin.', 'derleiti-modern') . '</p>';
    }

    $cache_key = null;
    if ($atts['cache'] === 'yes') {
        $cache_key = 'derleiti_ai_image_' . md5($atts['prompt'] . $atts['style'] . $atts['size'] . $atts['provider']);
        $cached_image = get_transient($cache_key);
        if ($cached_image !== false) {
            return render_ai_image($cached_image, $atts);
        }
    }

    $ai = new Derleiti_AI_Integration();
    $image = $ai->generate_image(
        $atts['prompt'],
        $atts['style'],
        $atts['size'],
        $atts['provider']
    );

    if (is_wp_error($image)) {
        return '<p class="derleiti-ai-error">' . $image->get_error_message() . '</p>';
    }

    if ($atts['cache'] === 'yes' && $cache_key) {
        set_transient($cache_key, $image, WEEK_IN_SECONDS);
    }

    return render_ai_image($image, $atts);
}

/**
 * Shortcode for a complete AI-generated section
 */
function derleiti_ai_complete_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'prompt'   => '',
        'title'    => '',
        'type'     => 'section',
        'style'    => 'default',
        'provider' => '',
        'class'    => '',
        'image'    => 'no',
        'cache'    => 'yes',
    ), $atts, 'derleiti_ai_complete');

    if (empty($atts['prompt'])) {
        return '<p class="derleiti-ai-error">' . __('Bitte geben Sie einen Prompt an.', 'derleiti-modern') . '</p>';
    }

    if (!class_exists('Derleiti_AI_Integration')) {
        return '<p class="derleiti-ai-error">' . __('Die KI-Integration ist nicht verfügbar. Bitte installieren und aktivieren Sie das Derleiti Plugin.', 'derleiti-modern') . '</p>';
    }

    $cache_key = null;
    if ($atts['cache'] === 'yes') {
        $cache_key = 'derleiti_ai_complete_' . md5($atts['prompt'] . $atts['type'] . $atts['style'] . $atts['provider'] . $atts['image']);
        $cached_content = get_transient($cache_key);
        if ($cached_content !== false) {
            return $cached_content;
        }
    }

    $ai = new Derleiti_AI_Integration();
    $ai_content = $ai->generate_content(
        $atts['prompt'] . ' Erstelle strukturierten Inhalt mit einer Überschrift und ausführlichem Text.',
        'paragraph',
        'informative',
        'medium',
        $atts['provider']
    );

    if (is_wp_error($ai_content)) {
        return '<p class="derleiti-ai-error">' . $ai_content->get_error_message() . '</p>';
    }

    $image_html = '';
    if ($atts['image'] === 'yes') {
        $image = $ai->generate_image(
            $atts['prompt'],
            'realistic',
            'medium',
            $atts['provider']
        );
        if (!is_wp_error($image)) {
            $image_html  = '<div class="derleiti-ai-complete-image">';
            $image_html .= '<img src="' . esc_url($image['url']) . '" alt="' . esc_attr($atts['prompt']) . '">';
            $image_html .= '</div>';
        }
    }

    // Determine title
    $section_title = '';
    if (!empty($atts['title'])) {
        $section_title = $atts['title'];
    } else {
        $lines = explode("\n", $ai_content);
        $first_line = trim($lines[0]);
        if (strpos($first_line, '<h') === 0 || strlen($first_line) < 100) {
            $section_title = strip_tags($first_line);
            array_shift($lines);
            $ai_content = implode("\n", $lines);
        }
    }

    $container_class = 'derleiti-ai-complete derleiti-ai-' . $atts['type'];
    if ($atts['style'] !== 'default') {
        $container_class .= ' derleiti-ai-style-' . $atts['style'];
    }
    if (!empty($atts['class'])) {
        $container_class .= ' ' . $atts['class'];
    }

    $html  = '<div class="' . esc_attr($container_class) . '">';
    $html .= '<div class="derleiti-ai-complete-header">';
    if (!empty($section_title)) {
        $html .= '<h3 class="derleiti-ai-complete-title">' . esc_html($section_title) . '</h3>';
    }
    $html .= '</div>';
    if (!empty($image_html)) {
        $html .= $image_html;
    }
    $html .= '<div class="derleiti-ai-complete-content">';
    $html .= $ai_content;
    $html .= '</div>';
    $html .= '<div class="derleiti-ai-attribution">';
    $html .= '<span class="derleiti-ai-icon">✨</span>';
    $html .= '<span class="derleiti-ai-text">' . __('KI-generierter Inhalt', 'derleiti-modern') . '</span>';
    $html .= '</div>';
    $html .= '</div>';

    if ($atts['cache'] === 'yes' && $cache_key) {
        set_transient($cache_key, $html, DAY_IN_SECONDS);
    }

    return $html;
}

/**
 * Apply theme styling to AI-generated content
 */
function apply_theme_styling_to_ai_content($content, $type, $style, $class) {
    $wrapper_class = 'derleiti-ai-block';
    $wrapper_class .= ' ' . $type;
    if ($style !== 'default') {
        $wrapper_class .= ' ' . $style;
    }
    if (!empty($class)) {
        $wrapper_class .= ' ' . $class;
    }

    $html  = '<div class="' . esc_attr($wrapper_class) . '">';
    $html .= $content;
    $html .= '</div>';

    return $html;
}

/**
 * Render AI-generated image with theme styling
 */
function render_ai_image($image, $atts) {
    if (!isset($image['url'])) {
        return '<p class="derleiti-ai-error">' . __('Ungültiges Bild-Format.', 'derleiti-modern') . '</p>';
    }

    $alt_text = !empty($atts['alt']) ? $atts['alt'] : $atts['prompt'];
    $img_class = 'derleiti-ai-image';
    if (!empty($atts['align'])) {
        $img_class .= ' align' . $atts['align'];
    }
    if (!empty($atts['class'])) {
        $img_class .= ' ' . $atts['class'];
    }

    $html  = '<figure class="' . esc_attr($img_class) . '">';
    $html .= '<img src="' . esc_url($image['url']) . '" alt="' . esc_attr($alt_text) . '" class="ai-generated-img">';
    if (!empty($atts['caption'])) {
        $html .= '<figcaption>' . esc_html($atts['caption']) . '</figcaption>';
    }
    $html .= '<div class="derleiti-ai-attribution">';
    $html .= '<span class="derleiti-ai-icon">✨</span>';
    $html .= '<span class="derleiti-ai-text">' . __('KI-generiertes Bild', 'derleiti-modern') . '</span>';
    $html .= '</div>';
    $html .= '</figure>';

    return $html;
}
