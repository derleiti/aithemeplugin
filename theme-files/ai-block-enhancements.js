/**
 * Enhanced Gutenberg AI Content Block
 * 
 * Add these enhancements to the ai-content-block-js.js file
 */

// Add these new attributes to the existing block attributes
/*
attributes: {
    // Existing attributes...
    
    // New theme-integration attributes
    contentStyle: {
        type: 'string',
        default: 'default',
    },
    themeIntegration: {
        type: 'boolean',
        default: true,
    },
    customClasses: {
        type: 'string',
        default: '',
    },
},
*/

// Add this function to load theme-specific prompt templates
// Add this to the edit function, after the useEffect that fetches providers
const [promptTemplates, setPromptTemplates] = useState([]);
const [selectedTemplate, setSelectedTemplate] = useState('');

// Fetch prompt templates
useEffect(() => {
    if (promptTemplates.length === 0) {
        fetch(derleitiBlocksData.restUrl + 'ai/prompt-templates', {
            headers: {
                'X-WP-Nonce': derleitiBlocksData.nonce,
            },
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.templates) {
                // Create template options for select control
                const templates = [
                    { label: __('Eigener Prompt', 'derleiti-plugin'), value: '' }
                ];
                
                Object.keys(data.templates).forEach(templateId => {
                    const template = data.templates[templateId];
                    templates.push({
                        label: template.title,
                        value: templateId
                    });
                });
                
                setPromptTemplates(templates);
            }
        })
        .catch(error => {
            console.error('Error fetching AI prompt templates:', error);
        });
    }
}, []);

// Add template selection handler
const handleTemplateSelection = (value) => {
    setSelectedTemplate(value);
    
    if (!value) {
        return; // User selected custom prompt
    }
    
    // Fetch the template details
    fetch(derleitiBlocksData.restUrl + 'ai/prompt-template-details', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': derleitiBlocksData.nonce,
        },
        body: JSON.stringify({
            template_id: value
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.template) {
            // Update prompt with template
            setAttributes({ 
                prompt: data.template.prompt,
                // If the template specifies a content type, update that too
                contentType: data.template.type || attributes.contentType
            });
        }
    })
    .catch(error => {
        console.error('Error fetching template details:', error);
    });
};

// Add these new controls to the InspectorControls panel
// Insert this after the existing theme controls
<PanelBody title={__('Theme-Integration', 'derleiti-plugin')} initialOpen={false}>
    <ToggleControl
        label={__('Theme-spezifisches Styling', 'derleiti-plugin')}
        checked={attributes.themeIntegration}
        onChange={value => setAttributes({ themeIntegration: value })}
        help={__('Optimiert das Styling der generierten Inhalte für das aktuelle Theme', 'derleiti-plugin')}
    />
    
    {attributes.themeIntegration && (
        <SelectControl
            label={__('Inhaltsart', 'derleiti-plugin')}
            value={attributes.contentStyle}
            options={[
                { label: __('Standard', 'derleiti-plugin'), value: 'default' },
                { label: __('Definition', 'derleiti-plugin'), value: 'definition' },
                { label: __('Zitat', 'derleiti-plugin'), value: 'quote' },
                { label: __('Tipp', 'derleiti-plugin'), value: 'tip' },
                { label: __('Warnung', 'derleiti-plugin'), value: 'warning' },
            ]}
            onChange={value => setAttributes({ contentStyle: value })}
        />
    )}
    
    <TextControl
        label={__('Benutzerdefinierte CSS-Klassen', 'derleiti-plugin')}
        value={attributes.customClasses}
        onChange={value => setAttributes({ customClasses: value })}
        help={__('Füge benutzerdefinierte CSS-Klassen zum AI-Block hinzu', 'derleiti-plugin')}
    />
</PanelBody>

<PanelBody title={__('Prompt-Vorlagen', 'derleiti-plugin')} initialOpen={false}>
    <SelectControl
        label={__('Vorlage wählen', 'derleiti-plugin')}
        value={selectedTemplate}
        options={promptTemplates}
        onChange={handleTemplateSelection}
        help={__('Wähle eine vordefinierte Prompt-Vorlage oder erstelle deinen eigenen Prompt', 'derleiti-plugin')}
    />
</PanelBody>

// Modify content request to include theme integration info
// Update the fetchData function that sends the API request:
/*
body: JSON.stringify({
    prompt: attributes.prompt,
    contentType: attributes.contentType,
    tone: attributes.tone,
    length: attributes.length,
    provider: attributes.provider,
    temperature: attributes.temperature,
    // Add new parameters
    themeIntegration: attributes.themeIntegration,
    contentStyle: attributes.contentStyle,
    customClasses: attributes.customClasses
})
*/

// Modify the generated content preview to reflect theme styles
// Update the content preview section
/*
{!isGenerating && attributes.generatedContent && (
    <div className={`derleiti-ai-content ${attributes.themeIntegration ? 'theme-integrated' : ''} ${attributes.contentStyle !== 'default' ? attributes.contentStyle : ''} ${attributes.customClasses}`}>
        <div className="derleiti-ai-content-header">
            <h3>{__('Generierter Inhalt', 'derleiti-plugin')}</h3>
        </div>
        <RichText
            tagName="div"
            className="derleiti-ai-content-editable"
            value={attributes.generatedContent}
            onChange={value => setAttributes({ generatedContent: value })}
            placeholder={__('Generierter Inhalt erscheint hier...', 'derleiti-plugin')}
        />
    </div>
)}
*/
