import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { PanelBody, ToggleControl, SelectControl, TextControl } from '@wordpress/components';
import { RichText, InspectorControls } from '@wordpress/block-editor';

// Beispiel: Ergänze in deinen Block-Attributes folgende neue Attribute:
export const attributes = {
    // ... (bestehende Attribute)
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
    // z. B. auch:
    prompt: {
        type: 'string',
        default: '',
    },
    contentType: {
        type: 'string',
        default: '',
    },
    generatedContent: {
        type: 'string',
        default: '',
    },
};

const Edit = ( props ) => {
    const { attributes, setAttributes, isGenerating } = props;
    const { prompt, contentType } = attributes;

    // Neue States für Prompt-Vorlagen und Auswahl
    const [promptTemplates, setPromptTemplates] = useState([]);
    const [selectedTemplate, setSelectedTemplate] = useState('');

    // Prompt-Vorlagen einmalig laden
    useEffect(() => {
        if ( promptTemplates.length === 0 ) {
            fetch( derleitiBlocksData.restUrl + 'ai/prompt-templates', {
                headers: {
                    'X-WP-Nonce': derleitiBlocksData.nonce,
                },
            } )
            .then( response => {
                if ( ! response.ok ) {
                    throw new Error( 'Network response was not ok' );
                }
                return response.json();
            } )
            .then( data => {
                if ( data.success && data.templates ) {
                    // Erstelle Optionen – der erste Eintrag steht für den eigenen Prompt
                    const templates = [
                        { label: __( 'Eigener Prompt', 'derleiti-plugin' ), value: '' }
                    ];
                    Object.keys( data.templates ).forEach( templateId => {
                        const template = data.templates[ templateId ];
                        templates.push( {
                            label: template.title,
                            value: templateId,
                        } );
                    } );
                    setPromptTemplates( templates );
                }
            } )
            .catch( error => {
                console.error( 'Error fetching AI prompt templates:', error );
            } );
        }
    }, [] ); // Leere Dependency-Liste, damit der Effekt nur einmal läuft

    // Handler für die Auswahl einer Prompt-Vorlage
    const handleTemplateSelection = ( value ) => {
        setSelectedTemplate( value );
        if ( ! value ) {
            return; // Benutzer hat den eigenen Prompt gewählt
        }
        // Hole die Details zur ausgewählten Vorlage
        fetch( derleitiBlocksData.restUrl + 'ai/prompt-template-details', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
               'X-WP-Nonce': derleitiBlocksData.nonce,
            },
            body: JSON.stringify({
                template_id: value
            })
        } )
        .then( response => response.json() )
        .then( data => {
            if ( data.success && data.template ) {
                // Aktualisiere den Block-Prompt und ggf. den Content-Type
                setAttributes({
                    prompt: data.template.prompt,
                    contentType: data.template.type || contentType,
                });
            }
        } )
        .catch( error => {
            console.error( 'Error fetching template details:', error );
        });
    };

    return (
        <>
        <InspectorControls>
        <PanelBody title={ __( 'Theme-Integration', 'derleiti-plugin' ) } initialOpen={ false }>
        <ToggleControl
        label={ __( 'Theme-spezifisches Styling', 'derleiti-plugin' ) }
        checked={ attributes.themeIntegration }
        onChange={ ( value ) => setAttributes({ themeIntegration: value }) }
        help={ __( 'Optimiert das Styling der generierten Inhalte für das aktuelle Theme', 'derleiti-plugin' ) }
        />
        { attributes.themeIntegration && (
            <SelectControl
            label={ __( 'Inhaltsart', 'derleiti-plugin' ) }
            value={ attributes.contentStyle }
            options={ [
                { label: __( 'Standard', 'derleiti-plugin' ), value: 'default' },
                                          { label: __( 'Definition', 'derleiti-plugin' ), value: 'definition' },
                                          { label: __( 'Zitat', 'derleiti-plugin' ), value: 'quote' },
                                          { label: __( 'Tipp', 'derleiti-plugin' ), value: 'tip' },
                                          { label: __( 'Warnung', 'derleiti-plugin' ), value: 'warning' },
            ] }
            onChange={ ( value ) => setAttributes({ contentStyle: value }) }
            />
        )}
        <TextControl
        label={ __( 'Benutzerdefinierte CSS-Klassen', 'derleiti-plugin' ) }
        value={ attributes.customClasses }
        onChange={ ( value ) => setAttributes({ customClasses: value }) }
        help={ __( 'Füge benutzerdefinierte CSS-Klassen zum AI-Block hinzu', 'derleiti-plugin' ) }
        />
        </PanelBody>
        <PanelBody title={ __( 'Prompt-Vorlagen', 'derleiti-plugin' ) } initialOpen={ false }>
        <SelectControl
        label={ __( 'Vorlage wählen', 'derleiti-plugin' ) }
        value={ selectedTemplate }
        options={ promptTemplates }
        onChange={ handleTemplateSelection }
        help={ __( 'Wähle eine vordefinierte Prompt-Vorlage oder erstelle deinen eigenen Prompt', 'derleiti-plugin' ) }
        />
        </PanelBody>
        </InspectorControls>
        <div className={ `derleiti-ai-content ${ attributes.themeIntegration ? 'theme-integrated' : '' } ${ attributes.contentStyle !== 'default' ? attributes.contentStyle : '' } ${ attributes.customClasses }` }>
        <div className="derleiti-ai-content-header">
        <h3>{ __( 'Generierter Inhalt', 'derleiti-plugin' ) }</h3>
        </div>
        <RichText
        tagName="div"
        className="derleiti-ai-content-editable"
        value={ attributes.generatedContent }
        onChange={ ( value ) => setAttributes({ generatedContent: value }) }
        placeholder={ __( 'Generierter Inhalt erscheint hier...', 'derleiti-plugin' ) }
        />
        </div>
        </>
    );
};

export default Edit;
