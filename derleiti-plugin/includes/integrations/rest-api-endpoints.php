<?php
/**
 * Add these endpoints to the register_rest_routes() method in the AI Integration class
 */

// Endpoint for theme-specific AI content generation
register_rest_route('derleiti-plugin/v1', '/ai/generate-theme-content', array(
    'methods'             => 'POST',
    'callback'            => array($this, 'rest_generate_theme_content'),
                                                                              'permission_callback' => function() {
                                                                                  return current_user_can('edit_posts');
                                                                              },
                                                                              'args'                => array(
                                                                                  'prompt' => array(
                                                                                      'required' => true,
                                                                                      'type'     => 'string',
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
                                                                                  'provider' => array(
                                                                                      'type'    => 'string',
                                                                                      'default' => '',
                                                                                  ),
                                                                                  'contentStyle' => array(
                                                                                      'type'    => 'string',
                                                                                      'default' => 'default',
                                                                                  ),
                                                                                  'customClasses' => array(
                                                                                      'type'    => 'string',
                                                                                      'default' => '',
                                                                                  ),
                                                                              ),
));

/**
 * REST API handler for theme-specific content generation
 * Add this method to the AI Integration class
 */
public function rest_generate_theme_content($request) {
    $prompt        = $request->get_param('prompt');
    $content_type  = $request->get_param('contentType');
    $tone          = $request->get_param('tone');
    $length        = $request->get_param('length');
    $provider      = $request->get_param('provider');
    $content_style = $request->get_param('contentStyle');
    $custom_classes= $request->get_param('customClasses');

    // Generate content using the existing method
    $content = $this->generate_content($prompt, $content_type, $tone, $length, $provider);

    if (is_wp_error($content)) {
        return rest_ensure_response(array(
            'success' => false,
            'error'   => $content->get_error_message(),
        ));
    }

    // Process the content for theme-specific styling if supported
    if (method_exists($this, 'process_theme_content') && current_theme_supports('derleiti-ai-integration')) {
        $processed_content = $this->process_theme_content($content, $content_type, $content_style, $custom_classes);
        return rest_ensure_response(array(
            'success'     => true,
            'content'     => $processed_content,
            'rawContent'  => $content,
            'themeSupport'=> true,
        ));
    }

    // Fallback: Return raw content if keine Theme-Unterstützung
    return rest_ensure_response(array(
        'success'     => true,
        'content'     => $content,
        'themeSupport'=> false,
    ));
}

/**
 * Enhanced process_theme_content method to support additional options
 * Add this method to the AI Integration class
 */
private function process_theme_content($content, $content_type, $content_style = 'default', $custom_classes = '') {
    // Don't process if theme doesn't support AI integration
    if (!$this->is_theme_supported()) {
        return $content;
    }

    // Get theme context for styling classes
    $theme_context = $this->get_theme_context();

    // Build CSS classes
    $wrapper_class = 'derleiti-ai-block';

    // Add content type as a class
    $wrapper_class .= ' ' . $content_type;

    // Add content style as a class if not default
    if ($content_style !== 'default') {
        $wrapper_class .= ' ' . $content_style;
    }

    // Add theme-specific class if available
    if (isset($theme_context['ai_content_class']) && !empty($theme_context['ai_content_class'])) {
        $wrapper_class .= ' ' . $theme_context['ai_content_class'];
    }

    // Add custom classes if provided
    if (!empty($custom_classes)) {
        $wrapper_class .= ' ' . $custom_classes;
    }

    // Wrap content with the constructed classes
    return '<div class="' . esc_attr($wrapper_class) . '">' . $content . '</div>';
}

/**
 * Updated get_theme_context method with extended information.
 * This overrides the filter 'derleiti_ai_context' call in the existing method.
 */
private function get_theme_context() {
    $context = array(
        'theme_name'      => 'Generic',
        'theme_version'   => '1.0',
        'layout_style'    => 'default',
        'color_scheme'    => 'default',
        'primary_color'   => '#0066cc',
        'secondary_color' => '#2c3e50',
        'text_color'      => '#333333',
        'ai_content_class'=> '',
        'css_classes'     => array(),
    );

    $theme = wp_get_theme();
    if ($theme) {
        $context['theme_name'] = $theme->get('Name');
        $context['theme_version'] = $theme->get('Version');
    }

    // Holen Sie Theme-Customizations, falls vorhanden.
    $context['primary_color']   = get_theme_mod('primary_color', $context['primary_color']);
    $context['secondary_color'] = get_theme_mod('secondary_color', $context['secondary_color']);
    $context['text_color']      = get_theme_mod('text_color', $context['text_color']);
    $context['layout_style']    = get_theme_mod('layout_style', $context['layout_style']);

    // Falls es sich um "Derleiti Modern" handelt, fügen Sie empfohlene CSS-Klassen hinzu.
    if ($context['theme_name'] === 'Derleiti Modern') {
        $context['css_classes'] = array(
            'paragraph' => 'derleiti-text',
            'headline'  => 'derleiti-heading',
            'list'      => 'derleiti-list',
            'callout'   => 'derleiti-callout',
            'button'    => 'derleiti-button'
        );
        $context['ai_content_class'] = 'theme-derleiti-modern';
    }

    return apply_filters('derleiti_ai_context', $context);
}
