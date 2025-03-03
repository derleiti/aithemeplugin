/**
 * Add this section to the admin-settings-ai.php file to manage AI prompt templates
 */

/**
 * Add this to the register_settings() method
 */
// Prompt Templates Section
add_settings_section(
    'derleiti_ai_templates_section',
    __('KI-Prompt-Vorlagen', 'derleiti-plugin'),
    array($this, 'render_templates_section'),
    'derleiti_ai_settings'
);

/**
 * Add this as a new method to the Derleiti_AI_Settings class
 */
public function render_templates_section() {
    ?>
    <p><?php _e('Verwalten Sie Ihre KI-Prompt-Vorlagen für verschiedene Anwendungsfälle.', 'derleiti-plugin'); ?></p>
    
    <div class="derleiti-prompt-templates">
        <h3><?php _e('Verfügbare Vorlagen', 'derleiti-plugin'); ?></h3>
        
        <?php 
        // Get all prompt templates
        $templates = $this->get_prompt_templates();
        if (empty($templates)) {
            echo '<p>' . __('Keine Vorlagen gefunden. Sie können unten neue Vorlagen erstellen.', 'derleiti-plugin') . '</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped prompt-templates-table">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>' . __('Titel', 'derleiti-plugin') . '</th>';
            echo '<th>' . __('Prompt', 'derleiti-plugin') . '</th>';
            echo '<th>' . __('Variablen', 'derleiti-plugin') . '</th>';
            echo '<th>' . __('Typ', 'derleiti-plugin') . '</th>';
            echo '<th>' . __('Aktionen', 'derleiti-plugin') . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($templates as $id => $template) {
                echo '<tr>';
                echo '<td>' . esc_html($template['title']) . '</td>';
                echo '<td>' . esc_html($template['prompt']) . '</td>';
                echo '<td>' . (isset($template['variables']) ? implode(', ', $template['variables']) : '') . '</td>';
                echo '<td>' . esc_html($template['type']) . '</td>';
                echo '<td>';
                echo '<button type="button" class="button edit-template" data-template-id="' . esc_attr($id) . '">' . __('Bearbeiten', 'derleiti-plugin') . '</button> ';
                if (!$this->is_default_template($id)) {
                    echo '<button type="button" class="button delete-template" data-template-id="' . esc_attr($id) . '">' . __('Löschen', 'derleiti-plugin') . '</button>';
                }
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        }
        ?>
        
        <div class="derleiti-add-template">
            <h3><?php _e('Neue Vorlage hinzufügen', 'derleiti-plugin'); ?></h3>
            
            <div class="template-form">
                <p>
                    <label for="template-title"><?php _e('Titel:', 'derleiti-plugin'); ?></label>
                    <input type="text" id="template-title" name="template_title" class="regular-text">
                </p>
                
                <p>
                    <label for="template-prompt"><?php _e('Prompt:', 'derleiti-plugin'); ?></label>
                    <textarea id="template-prompt" name="template_prompt" class="large-text" rows="4"></textarea>
                    <span class="description"><?php _e('Verwende {variable_name} für dynamische Variablen, die vom Benutzer ausgefüllt werden.', 'derleiti-plugin'); ?></span>
                </p>
                
                <p>
                    <label for="template-variables"><?php _e('Variablen (durch Kommas getrennt):', 'derleiti-plugin'); ?></label>
                    <input type="text" id="template-variables" name="template_variables" class="regular-text" placeholder="z.B. topic, product_name">
                </p>
                
                <p>
                    <label for="template-type"><?php _e('Inhaltstyp:', 'derleiti-plugin'); ?></label>
                    <select id="template-type" name="template_type">
                        <option value="paragraph"><?php _e('Absatz', 'derleiti-plugin'); ?></option>
                        <option value="list"><?php _e('Liste', 'derleiti-plugin'); ?></option>
                        <option value="headline"><?php _e('Überschrift', 'derleiti-plugin'); ?></option>
                        <option value="code"><?php _e('Code', 'derleiti-plugin'); ?></option>
                        <option value="hero"><?php _e('Hero-Bereich', 'derleiti-plugin'); ?></option>
                        <option value="feature"><?php _e('Feature-Beschreibung', 'derleiti-plugin'); ?></option>
                    </select>
                </p>
                
                <input type="hidden" id="template-id" name="template_id" value="">
                <input type="hidden" id="template-action" name="template_action" value="add">
                
                <div class="template-actions">
                    <button type="button" id="save-template" class="button button-primary"><?php _e('Vorlage speichern', 'derleiti-plugin'); ?></button>
                    <button type="button" id="cancel-edit" class="button" style="display: none;"><?php _e('Abbrechen', 'derleiti-plugin'); ?></button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Edit template
        $('.edit-template').on('click', function() {
            var templateId = $(this).data('template-id');
            
            // Get template data via AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'derleiti_get_prompt_template',
                    nonce: '<?php echo wp_create_nonce('derleiti_ai_templates_nonce'); ?>',
                    template_id: templateId
                },
                success: function(response) {
                    if (response.success) {
                        var template = response.data.template;
                        
                        // Fill form fields
                        $('#template-title').val(template.title);
                        $('#template-prompt').val(template.prompt);
                        $('#template-variables').val(template.variables.join(', '));
                        $('#template-type').val(template.type);
                        $('#template-id').val(templateId);
                        $('#template-action').val('edit');
                        
                        // Show cancel button and scroll to form
                        $('#cancel-edit').show();
                        $('html, body').animate({
                            scrollTop: $('.derleiti-add-template').offset().top - 50
                        }, 500);
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        });
        
        // Delete template
        $('.delete-template').on('click', function() {
            if (confirm('<?php _e('Sind Sie sicher, dass Sie diese Vorlage löschen möchten?', 'derleiti-plugin'); ?>')) {
                var templateId = $(this).data('template-id');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'derleiti_delete_prompt_template',
                        nonce: '<?php echo wp_create_nonce('derleiti_ai_templates_nonce'); ?>',
                        template_id: templateId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message);
                        }
                    }
                });
            }
        });
        
        // Save template
        $('#save-template').on('click', function() {
            var title = $('#template-title').val();
            var prompt = $('#template-prompt').val();
            var variables = $('#template-variables').val();
            var type = $('#template-type').val();
            var id = $('#template-id').val();
            var action = $('#template-action').val();
            
            if (!title || !prompt) {
                alert('<?php _e('Bitte füllen Sie Titel und Prompt aus.', 'derleiti-plugin'); ?>');
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'derleiti_save_prompt_template',
                    nonce: '<?php echo wp_create_nonce('derleiti_ai_templates_nonce'); ?>',
                    template: {
                        id: id,
                        title: title,
                        prompt: prompt,
                        variables: variables,
                        type: type
                    },
                    template_action: action
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        });
        
        // Cancel edit
        $('#cancel-edit').on('click', function() {
            // Reset form
            $('#template-title').val('');
            $('#template-prompt').val('');
            $('#template-variables').val('');
            $('#template-type').val('paragraph');
            $('#template-id').val('');
            $('#template-action').val('add');
            
            // Hide cancel button
            $(this).hide();
        });
    });
    </script>
    <?php
}

/**
 * Add these methods to the Derleiti_AI_Settings class
 */
private function get_prompt_templates() {
    $saved_templates = get_option('derleiti_ai_prompt_templates', array());
    
    // Merge with default templates
    $default_templates = $this->get_default_prompt_templates();
    
    return array_merge($default_templates, $saved_templates);
}

private function get_default_prompt_templates() {
    return array(
        'blog_post' => array(
            'title' => __('Blog-Beitrag', 'derleiti-plugin'),
            'prompt' => __('Schreibe einen informativen Blog-Beitrag zum Thema {topic} mit Fokus auf {aspect}.', 'derleiti-plugin'),
            'variables' => array('topic', 'aspect'),
            'type' => 'paragraph'
        ),
        'product_description' => array(
            'title' => __('Produktbeschreibung', 'derleiti-plugin'),
            'prompt' => __('Erstelle eine überzeugende Produktbeschreibung für {product_name}, das folgende Vorteile bietet: {benefits}.', 'derleiti-plugin'),
            'variables' => array('product_name', 'benefits'),
            'type' => 'paragraph'
        ),
        'faq_questions' => array(
            'title' => __('FAQ Fragen', 'derleiti-plugin'),
            'prompt' => __('Generiere 5 häufig gestellte Fragen und Antworten zum Thema {topic}.', 'derleiti-plugin'),
            'variables' => array('topic'),
            'type' => 'list'
        )
    );
}

private function is_default_template($template_id) {
    $default_templates = $this->get_default_prompt_templates();
    return array_key_exists($template_id, $default_templates);
}

/**
 * Add these AJAX handlers to the Derleiti_AI_Settings class constructor
 */
add_action('wp_ajax_derleiti_get_prompt_template', array($this, 'ajax_get_prompt_template'));
add_action('wp_ajax_derleiti_save_prompt_template', array($this, 'ajax_save_prompt_template'));
add_action('wp_ajax_derleiti_delete_prompt_template', array($this, 'ajax_delete_prompt_template'));

/**
 * Add these AJAX handler methods to the Derleiti_AI_Settings class
 */
public function ajax_get_prompt_template() {
    // Check nonce and user capabilities
    check_ajax_referer('derleiti_ai_templates_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Keine Berechtigung.', 'derleiti-plugin')));
    }
    
    $template_id = isset($_POST['template_id']) ? sanitize_key($_POST['template_id']) : '';
    if (empty($template_id)) {
        wp_send_json_error(array('message' => __('Ungültige Vorlagen-ID.', 'derleiti-plugin')));
    }
    
    $templates = $this->get_prompt_templates();
    if (!isset($templates[$template_id])) {
        wp_send_json_error(array('message' => __('Vorlage nicht gefunden.', 'derleiti-plugin')));
    }
    
    wp_send_json_success(array('template' => $templates[$template_id]));
}

public function ajax_save_prompt_template() {
    // Check nonce and user capabilities
    check_ajax_referer('derleiti_ai_templates_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Keine Berechtigung.', 'derleiti-plugin')));
    }
    
    $template_data = isset($_POST['template']) ? $_POST['template'] : array();
    $action = isset($_POST['template_action']) ? sanitize_key($_POST['template_action']) : 'add';
    
    if (empty($template_data['title']) || empty($template_data['prompt'])) {
        wp_send_json_error(array('message' => __('Titel und Prompt sind erforderlich.', 'derleiti-plugin')));
    }
    
    // Sanitize template data
    $template = array(
        'title' => sanitize_text_field($template_data['title']),
        'prompt' => sanitize_textarea_field($template_data['prompt']),
        'type' => sanitize_key($template_data['type'])
    );
    
    // Process variables
    $variables = array();
    if (!empty($template_data['variables'])) {
        $vars = explode(',', $template_data['variables']);
        foreach ($vars as $var) {
            $variables[] = sanitize_key(trim($var));
        }
    }
    $template['variables'] = $variables;
    
    // Get existing templates
    $saved_templates = get_option('derleiti_ai_prompt_templates', array());
    
    if ($action === 'edit' && !empty($template_data['id'])) {
        $template_id = sanitize_key($template_data['id']);
        
        // Don't allow editing default templates
        if ($this->is_default_template($template_id)) {
            wp_send_json_error(array('message' => __('Standard-Vorlagen können nicht bearbeitet werden.', 'derleiti-plugin')));
        }
        
        $saved_templates[$template_id] = $template;
    } else {
        // Generate a unique ID
        $template_id = 'custom_' . uniqid();
        $saved_templates[$template_id] = $template;
    }
    
    // Save updated templates
    update_option('derleiti_ai_prompt_templates', $saved_templates);
    
    wp_send_json_success(array('message' => __('Vorlage erfolgreich gespeichert.', 'derleiti-plugin')));
}

public function ajax_delete_prompt_template() {
    // Check nonce and user capabilities
    check_ajax_referer('derleiti_ai_templates_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Keine Berechtigung.', 'derleiti-plugin')));
    }
    
    $template_id = isset($_POST['template_id']) ? sanitize_key($_POST['template_id']) : '';
    if (empty($template_id)) {
        wp_send_json_error(array('message' => __('Ungültige Vorlagen-ID.', 'derleiti-plugin')));
    }
    
    // Don't allow deleting default templates
    if ($this->is_default_template($template_id)) {
        wp_send_json_error(array('message' => __('Standard-Vorlagen können nicht gelöscht werden.', 'derleiti-plugin')));
    }
    
    // Get existing templates and remove the specified one
    $saved_templates = get_option('derleiti_ai_prompt_templates', array());
    if (isset($saved_templates[$template_id])) {
        unset($saved_templates[$template_id]);
        update_option('derleiti_ai_prompt_templates', $saved_templates);
        wp_send_json_success(array('message' => __('Vorlage erfolgreich gelöscht.', 'derleiti-plugin')));
    } else {
        wp_send_json_error(array('message' => __('Vorlage nicht gefunden.', 'derleiti-plugin')));
    }
}

/**
 * Add these REST API endpoints to the register_rest_routes() method in the AI Integration class
 */
// Endpoint for fetching prompt templates
register_rest_route('derleiti-plugin/v1', '/ai/prompt-templates', array(
    'methods' => 'GET',
    'callback' => array($this, 'rest_get_prompt_templates'),
    'permission_callback' => function() {
        return current_user_can('edit_posts');
    },
));

// Endpoint for fetching specific template details
register_rest_route('derleiti-plugin/v1', '/ai/prompt-template-details', array(
    'methods' => 'POST',
    'callback' => array($this, 'rest_get_template_details'),
    'permission_callback' => function() {
        return current_user_can('edit_posts');
    },
    'args' => array(
        'template_id' => array(
            'required' => true,
            'type' => 'string',
        ),
    ),
));

/**
 * Add these REST API handlers to the AI Integration class
 */
public function rest_get_prompt_templates() {
    // Get prompt templates
    $templates = array();
    
    if (class_exists('Derleiti_AI_Settings')) {
        $ai_settings = new Derleiti_AI_Settings();
        if (method_exists($ai_settings, 'get_prompt_templates')) {
            $templates = $ai_settings->get_prompt_templates();
        }
    } else {
        // Fallback to getting templates directly
        $saved_templates = get_option('derleiti_ai_prompt_templates', array());
        $templates = array_merge($this->get_default_prompt_templates(), $saved_templates);
    }
    
    return rest_ensure_response(array(
        'success' => true,
        'templates' => $templates,
    ));
}

public function rest_get_template_details($request) {
    $template_id = $request->get_param('template_id');
    
    if (empty($template_id)) {
        return rest_ensure_response(array(
            'success' => false,
            'error' => __('Ungültige Vorlagen-ID.', 'derleiti-plugin'),
        ));
    }
    
    // Get templates
    $templates = array();
    
    if (class_exists('Derleiti_AI_Settings')) {
        $ai_settings = new Derleiti_AI_Settings();
        if (method_exists($ai_settings, 'get_prompt_templates')) {
            $templates = $ai_settings->get_prompt_templates();
        }
    } else {
        // Fallback to getting templates directly
        $saved_templates = get_option('derleiti_ai_prompt_templates', array());
        $templates = array_merge($this->get_default_prompt_templates(), $saved_templates);
    }
    
    if (!isset($templates[$template_id])) {
        return rest_ensure_response(array(
            'success' => false,
            'error' => __('Vorlage nicht gefunden.', 'derleiti-plugin'),
        ));
    }
    
    return rest_ensure_response(array(
        'success' => true,
        'template' => $templates[$template_id],
    ));
}
