<?php
/**
 * Enhanced methods for the Derleiti_AI_Integration class to improve theme integration
 * Add these methods to the ai-integration-class.php file
 */

/**
 * Check if theme supports AI integration
 * Add this method to the Derleiti_AI_Integration class
 */
private function is_theme_supported() {
    return current_theme_supports('derleiti-ai-integration');
}

/**
 * Get theme-specific AI context
 * Add this method to the Derleiti_AI_Integration class
 */
private function get_theme_context() {
    $context = array(
        'theme_name'   => 'Generic',
        'layout_style' => 'default',
        'color_scheme' => 'default'
    );

    // Allow themes and plugins to modify the context via a filter
    return apply_filters('derleiti_ai_context', $context);
}

/**
 * Generate theme-optimized system prompt
 * Add this method to the Derleiti_AI_Integration class to replace the existing generate_system_prompt
 */
private function generate_system_prompt($content_type, $tone, $length) {
    $system_prompt = "Du bist ein hilfreicher KI-Assistent, der Inhalte für eine WordPress-Website generiert. ";

    // Get theme context for more specific instructions
    $theme_context = $this->get_theme_context();

    // Add theme-specific instructions if supported
    if ($this->is_theme_supported()) {
        $system_prompt .= "Die Website verwendet das Theme '{$theme_context['theme_name']}' ";
        $system_prompt .= "mit einem {$theme_context['layout_style']} Layout-Stil und einem {$theme_context['color_scheme']} Farbschema. ";
    }

    // Content Type
    switch ($content_type) {
        case 'paragraph':
            $system_prompt .= "Generiere einen informativen Absatz ";
            break;
        case 'list':
            $system_prompt .= "Generiere eine übersichtliche Liste mit Stichpunkten ";
            break;
        case 'headline':
            $system_prompt .= "Generiere eine ansprechende Überschrift ";
            break;
        case 'code':
            $system_prompt .= "Generiere gut kommentierten Code ";
            break;
        case 'hero':
            $system_prompt .= "Generiere ansprechenden Text für einen Hero-Bereich mit Haupttitel (h1), Untertitel und Call-to-Action ";
            break;
        case 'feature':
            $system_prompt .= "Generiere eine kurze, überzeugende Feature-Beschreibung ";
            break;
        default:
            $system_prompt .= "Generiere einen informativen Text ";
    }

    // Tone
    switch ($tone) {
        case 'formal':
            $system_prompt .= "in einem formellen, professionellen Ton ";
            break;
        case 'casual':
            $system_prompt .= "in einem lockeren, freundlichen Ton ";
            break;
        case 'informative':
            $system_prompt .= "in einem informierenden, sachlichen Ton ";
            break;
        case 'persuasive':
            $system_prompt .= "in einem überzeugenden, werbenden Ton ";
            break;
        default:
            $system_prompt .= "in einem ausgewogenen, neutralen Ton ";
    }

    // Length
    switch ($length) {
        case 'short':
            $system_prompt .= "mit kurzer Länge (ca. 50-100 Wörter). ";
            break;
        case 'medium':
            $system_prompt .= "mit mittlerer Länge (ca. 150-250 Wörter). ";
            break;
        case 'long':
            $system_prompt .= "mit ausführlicher Länge (ca. 300-500 Wörter). ";
            break;
        default:
            $system_prompt .= "mit angemessener Länge. ";
    }

    // Add HTML guidance if theme supports AI integration and CSS classes are provided
    if ($this->is_theme_supported() && isset($theme_context['css_classes']) && !empty($theme_context['css_classes'])) {
        $system_prompt .= "Wenn du HTML-Auszeichnungen verwendest, nutze diese CSS-Klassen für optimale Darstellung im Theme: ";
        $system_prompt .= implode(', ', $theme_context['css_classes']) . ". ";
    } else {
        $system_prompt .= "Verwende HTML-Formatierung wenn sinnvoll, aber halte es einfach. ";
    }

    $system_prompt .= "Antworte nur mit dem generierten Inhalt ohne zusätzliche Einleitungen oder Erklärungen.";

    return $system_prompt;
}

/**
 * Get available prompt templates
 * Add this method to the Derleiti_AI_Integration class
 */
public function get_prompt_templates() {
    $default_templates = array(
        'blog_post' => array(
            'title'     => __('Blog-Beitrag', 'derleiti-plugin'),
                             'prompt'    => __('Schreibe einen informativen Blog-Beitrag zum Thema {topic} mit Fokus auf {aspect}.', 'derleiti-plugin'),
                             'variables' => array('topic', 'aspect'),
                             'type'      => 'paragraph'
        ),
        'product_description' => array(
            'title'     => __('Produktbeschreibung', 'derleiti-plugin'),
                                       'prompt'    => __('Erstelle eine überzeugende Produktbeschreibung für {product_name}, das folgende Vorteile bietet: {benefits}.', 'derleiti-plugin'),
                                       'variables' => array('product_name', 'benefits'),
                                       'type'      => 'paragraph'
        ),
        'faq_questions' => array(
            'title'     => __('FAQ Fragen', 'derleiti-plugin'),
                                 'prompt'    => __('Generiere 5 häufig gestellte Fragen und Antworten zum Thema {topic}.', 'derleiti-plugin'),
                                 'variables' => array('topic'),
                                 'type'      => 'list'
        ),
    );

    return apply_filters('derleiti_ai_prompt_templates', $default_templates);
}

/**
 * Process theme-specific content output
 * Add this method to the Derleiti_AI_Integration class
 */
private function process_theme_content($content, $content_type) {
    if (!$this->is_theme_supported()) {
        return $content;
    }

    $theme_context = $this->get_theme_context();
    $wrapper_class = 'derleiti-ai-block ' . $content_type;

    if (isset($theme_context['ai_content_class'])) {
        $wrapper_class .= ' ' . $theme_context['ai_content_class'];
    }

    return '<div class="' . esc_attr($wrapper_class) . '">' . $content . '</div>';
}

/**
 * Modified render_ai_content_block method to enhance theme compatibility
 * Use this to replace the existing method in blocks-class.php or blocks-manager.php
 */
public function render_ai_content_block($attributes, $content) {
    $generated_content = isset($attributes['generatedContent']) ? $attributes['generatedContent'] : '';
    $content_type = isset($attributes['contentType']) ? $attributes['contentType'] : 'paragraph';

    if (empty($generated_content)) {
        return '<div class="derleiti-ai-placeholder">' . __('Kein KI-Inhalt generiert. Bitte geben Sie einen Prompt ein und generieren Sie Inhalt im Block-Editor.', 'derleiti-plugin') . '</div>';
    }

    if (class_exists('Derleiti_AI_Integration')) {
        $ai = new Derleiti_AI_Integration();
        if (method_exists($ai, 'process_theme_content')) {
            return $ai->process_theme_content($generated_content, $content_type);
        }
    }

    return '<div class="derleiti-ai-block ' . esc_attr($content_type) . '">' . wp_kses_post($generated_content) . '</div>';
}
