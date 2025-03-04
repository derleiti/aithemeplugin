/**
 * AI Content Block for Gutenberg Editor
 *
 * @package Derleiti_Plugin
 * @version 1.1.0
 */

(function( blocks, element, blockEditor, components, i18n ) {
    const { __ } = i18n;
    const { registerBlockType } = blocks;
    const { useState, useEffect } = element;
    const { InspectorControls, RichText } = blockEditor;
    const { PanelBody, SelectControl, TextareaControl, Button, Spinner, ToggleControl, RangeControl } = components;

    registerBlockType( 'derleiti/ai-content', {
        title: __( 'KI-Inhaltsblock', 'derleiti-plugin' ),
                       icon: 'superhero',
                       category: 'derleiti-blocks',
                       attributes: {
                           prompt: {
                               type: 'string',
                       default: '',
                           },
                       contentType: {
                           type: 'string',
                       default: 'paragraph',
                       },
                       tone: {
                           type: 'string',
                       default: 'neutral',
                       },
                       length: {
                           type: 'string',
                       default: 'medium',
                       },
                       generatedContent: {
                           type: 'string',
                       default: '',
                       },
                       provider: {
                           type: 'string',
                       default: '',
                       },
                       temperature: {
                           type: 'number',
                       default: 0.7,
                       },
                       autoUpdate: {
                           type: 'boolean',
                       default: false,
                       }
                       },

                       edit: function( props ) {
                           const { attributes, setAttributes } = props;
                           const [ isGenerating, setIsGenerating ] = useState( false );
                           const [ error, setError ] = useState( null );
                           const [ availableProviders, setAvailableProviders ] = useState( [] );
                           const [ activeProvider, setActiveProvider ] = useState( attributes.provider || '' );

                           // Fetch available providers if not already loaded
                           useEffect( () => {
                               if ( availableProviders.length === 0 ) {
                                   fetchProviders();
                               }
                           }, [] );

                           const fetchProviders = () => {
                               fetch( derleitiBlocksData.restUrl + 'ai/provider-status', {
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
                                   if ( data.success ) {
                                       const providers = [];
                                       // Default option
                                       providers.push({
                                           label: __( 'Standardanbieter verwenden', 'derleiti-plugin' ),
                                                      value: ''
                                       });
                                       // Add each available provider which is enabled and supports text generation
                                       Object.keys( data.providers ).forEach( providerId => {
                                           const provider = data.providers[ providerId ];
                                           if ( provider.enabled && provider.features.includes( 'text' ) ) {
                                               providers.push({
                                                   label: provider.name,
                                                   value: providerId
                                               });
                                           }
                                       });
                                       setAvailableProviders( providers );
                                       // Set active provider if not already set
                                       if ( data.activeProvider && ! attributes.provider ) {
                                           setActiveProvider( data.activeProvider );
                                           setAttributes({ provider: data.activeProvider });
                                       }
                                   }
                               } )
                               .catch( error => {
                                   console.error( 'Error fetching AI providers:', error );
                                   // Fallback options
                                   setAvailableProviders([
                                       { label: __( 'Standardanbieter verwenden', 'derleiti-plugin' ), value: '' },
                                                         { label: 'OpenAI (ChatGPT)', value: 'openai' },
                                                         { label: 'Google Gemini', value: 'gemini' },
                                                         { label: 'Anthropic Claude', value: 'anthropic' }
                                   ]);
                               });
                           };

                           // Function to generate content using AI
                           const generateContent = () => {
                               if ( ! attributes.prompt.trim() ) {
                                   setError( __( 'Bitte geben Sie einen Prompt ein.', 'derleiti-plugin' ) );
                                   return;
                               }

                               setIsGenerating( true );
                               setError( null );

                               fetch( derleitiBlocksData.restUrl + 'ai/generate-content', {
                                   method: 'POST',
                                   headers: {
                                       'Content-Type': 'application/json',
                                      'X-WP-Nonce': derleitiBlocksData.nonce,
                                   },
                                   body: JSON.stringify({
                                       prompt: attributes.prompt,
                                       contentType: attributes.contentType,
                                       tone: attributes.tone,
                                       length: attributes.length,
                                       provider: attributes.provider,
                                       temperature: attributes.temperature
                                   })
                               } )
                               .then( response => {
                                   if ( ! response.ok ) {
                                       throw new Error( 'Network response was not ok' );
                                   }
                                   return response.json();
                               } )
                               .then( data => {
                                   if ( data.success ) {
                                       setAttributes({ generatedContent: data.content });
                                   } else {
                                       setError( data.error || __( 'Ein Fehler ist aufgetreten.', 'derleiti-plugin' ) );
                                   }
                                   setIsGenerating( false );
                               } )
                               .catch( error => {
                                   setError( error.message );
                                   setIsGenerating( false );
                               });
                           };

                           // Auto-update the generated content if autoUpdate is enabled
                           useEffect( () => {
                               if ( attributes.autoUpdate && attributes.prompt.trim() && ! isGenerating ) {
                                   const debounceTimer = setTimeout( () => {
                                       generateContent();
                                   }, 1000 );
                                   return () => {
                                       clearTimeout( debounceTimer );
                                   };
                               }
                           }, [ attributes.prompt, attributes.autoUpdate ] );

                           const contentTypeOptions = [
                               { label: __( 'Absatz', 'derleiti-plugin' ), value: 'paragraph' },
                       { label: __( 'Liste', 'derleiti-plugin' ), value: 'list' },
                       { label: __( 'Überschrift', 'derleiti-plugin' ), value: 'headline' },
                       { label: __( 'Code', 'derleiti-plugin' ), value: 'code' }
                           ];

                           const toneOptions = [
                               { label: __( 'Neutral', 'derleiti-plugin' ), value: 'neutral' },
                       { label: __( 'Formal', 'derleiti-plugin' ), value: 'formal' },
                       { label: __( 'Locker', 'derleiti-plugin' ), value: 'casual' },
                       { label: __( 'Informativ', 'derleiti-plugin' ), value: 'informative' },
                       { label: __( 'Überzeugend', 'derleiti-plugin' ), value: 'persuasive' }
                           ];

                           const lengthOptions = [
                               { label: __( 'Kurz', 'derleiti-plugin' ), value: 'short' },
                       { label: __( 'Mittel', 'derleiti-plugin' ), value: 'medium' },
                       { label: __( 'Lang', 'derleiti-plugin' ), value: 'long' }
                           ];

                           return [
                               <InspectorControls key="inspector">
                               <PanelBody title={ __( 'KI-Einstellungen', 'derleiti-plugin' ) } initialOpen={ true }>
                               <SelectControl
                               label={ __( 'Inhaltstyp', 'derleiti-plugin' ) }
                               value={ attributes.contentType }
                               options={ contentTypeOptions }
                               onChange={ value => setAttributes({ contentType: value }) }
                               />
                               <SelectControl
                               label={ __( 'Tonalität', 'derleiti-plugin' ) }
                               value={ attributes.tone }
                               options={ toneOptions }
                               onChange={ value => setAttributes({ tone: value }) }
                               />
                               <SelectControl
                               label={ __( 'Länge', 'derleiti-plugin' ) }
                               value={ attributes.length }
                               options={ lengthOptions }
                               onChange={ value => setAttributes({ length: value }) }
                               />
                               <SelectControl
                               label={ __( 'KI-Anbieter', 'derleiti-plugin' ) }
                               value={ attributes.provider }
                               options={ availableProviders }
                               onChange={ value => setAttributes({ provider: value }) }
                               help={ __( 'Wählen Sie den KI-Anbieter für die Inhaltsgenerierung.', 'derleiti-plugin' ) }
                               />
                               <RangeControl
                               label={ __( 'Kreativität (Temperature)', 'derleiti-plugin' ) }
                               value={ attributes.temperature }
                               onChange={ value => setAttributes({ temperature: value }) }
                               min={ 0 }
                               max={ 1 }
                               step={ 0.1 }
                               help={ __( 'Niedrigere Werte = präziser, höhere Werte = kreativer', 'derleiti-plugin' ) }
                               />
                               <ToggleControl
                               label={ __( 'Automatische Aktualisierung', 'derleiti-plugin' ) }
                               checked={ attributes.autoUpdate }
                               onChange={ value => setAttributes({ autoUpdate: value }) }
                               help={ __( 'Inhalt automatisch generieren, wenn sich der Prompt ändert', 'derleiti-plugin' ) }
                               />
                               </PanelBody>
                               </InspectorControls>,

                       <div key="content" className="derleiti-ai-content-block">
                       <div className="derleiti-ai-prompt-container">
                       <TextareaControl
                       label={ __( 'KI-Prompt', 'derleiti-plugin' ) }
                       value={ attributes.prompt }
                       onChange={ value => setAttributes({ prompt: value }) }
                       placeholder={ __( 'Beschreiben Sie, welchen Inhalt die KI generieren soll...', 'derleiti-plugin' ) }
                       rows={ 3 }
                       />

                       <div className="derleiti-ai-actions">
                       <Button
                       isPrimary
                       onClick={ generateContent }
                       isBusy={ isGenerating }
                       disabled={ isGenerating || ! attributes.prompt.trim() }
                       >
                       { isGenerating
                           ? derleitiBlocksData.strings.generating
                           : attributes.generatedContent
                           ? derleitiBlocksData.strings.regenerateContent
                           : derleitiBlocksData.strings.generateContent }
                           </Button>

                           <div className="derleiti-ai-settings-summary">
                           <span className="derleiti-ai-setting">
                           { __( 'Typ:', 'derleiti-plugin' ) } { contentTypeOptions.find( o => o.value === attributes.contentType )?.label }
                           </span>
                           <span className="derleiti-ai-setting">
                           { __( 'Ton:', 'derleiti-plugin' ) } { toneOptions.find( o => o.value === attributes.tone )?.label }
                           </span>
                           <span className="derleiti-ai-setting">
                           { __( 'Länge:', 'derleiti-plugin' ) } { lengthOptions.find( o => o.value === attributes.length )?.label }
                           </span>
                           </div>
                           </div>

                           { error && <div className="derleiti-ai-error">{ error }</div> }
                           </div>

                           { isGenerating && (
                               <div className="derleiti-ai-loading">
                               <Spinner />
                               <p>{ __( 'Generiere Inhalte mit KI...', 'derleiti-plugin' ) }</p>
                               </div>
                           ) }

                           { ! isGenerating && attributes.generatedContent && (
                               <div className="derleiti-ai-content">
                               <div className="derleiti-ai-content-header">
                               <h3>{ __( 'Generierter Inhalt', 'derleiti-plugin' ) }</h3>
                               </div>
                               <RichText
                               tagName="div"
                               className="derleiti-ai-content-editable"
                               value={ attributes.generatedContent }
                               onChange={ value => setAttributes({ generatedContent: value }) }
                               placeholder={ __( 'Generierter Inhalt erscheint hier...', 'derleiti-plugin' ) }
                               />
                               </div>
                           ) }

                           { ! isGenerating && ! attributes.generatedContent && ! error && (
                               <div className="derleiti-ai-placeholder">
                               { __( 'Geben Sie einen Prompt ein und klicken Sie auf "Inhalt generieren".', 'derleiti-plugin' ) }
                               </div>
                           ) }
                           </div>
                           ];
                       },

                       save: function( props ) {
                           // Server-side rendering, so we return null.
                           return null;
                       }
    });
})(
    window.wp.blocks,
   window.wp.element,
   window.wp.blockEditor,
   window.wp.components,
   window.wp.i18n
);
