(function( epdfGf, undefined ) {
	// After a field is added to a form in the editor.
	jQuery(document).on( 'gform_field_added', function( event, form, field ) {
		loadPdfViewerFieldEditorSettings( field );
	} );
	// After field settings are loaded in the form editor when a field is selected.
	// Also an equivalent jQuery event hook 'gform_load_field_settings'.
	gform.addAction( 'gform_post_load_field_settings', function( field, form ) {
		loadPdfViewerFieldEditorSettings( field[0] );
	}, 10, 2 );

	const { __ } = wp.i18n;

	function isValidHttpUrl(string) {
		let url;

		try {
			url = new URL(string);
		} catch (_) {
			return false;
		}

		return url.protocol === "http:" || url.protocol === "https:";
	}

	function loadPdfViewerFieldEditorSettings( field ) {
		if ( epdf_gf_form_editor_strings.field_type !== field.type ) {
			return;
		}

		// Populate initial scale setting.
		var elements = [];
		elements.push(document.getElementById( 'field_initial_scale' ));
		var keys = [];
		keys.push( 'initialScale' );
		if ( elements[0] ) {
			// Populate the setting value.
			elements[0].value = rgar( field, keys[0] );
			// Update the setting when users change the value.
			[ 'input', 'propertychange' ].forEach(function(e){
				elements[0].addEventListener(e, function() {
					// SetFieldProperty defined in gravityforms/js/form_editor.js
					SetFieldProperty( keys[0], this.value );
				} );
			} );
			// Fire input events so errors show as soon as the field is selected.
			elements[0].dispatchEvent(new Event('input'));
		}

		// Populate PDF URL setting.
		elements.push(document.getElementById( 'field_pdf_url' ));
		keys.push( 'pdfUrl' );
		if ( elements[1] ) {
			// Populate the setting value.
			elements[1].value = rgar( field, keys[1] );
			// Update the setting when users change the value.
			[ 'input', 'propertychange' ].forEach(function(e){
				elements[1].addEventListener(e, function() {
					// SetFieldProperty defined in gravityforms/js/form_editor.js
					SetFieldProperty( keys[1], this.value );

					if ( '' === this.value ) {
						resetFieldError( 'pdf_url_setting' );
						return;
					}

					// Is it a valid URL?
					if ( ! isValidHttpUrl( this.value ) ) {
						setFieldError(
							'pdf_url_setting',
							'below',
							__( 'Please enter a valid URL.', 'embed-pdf-gravityforms' )
						);
						return;
					// Is it a local URL?
					} else if ( epdf_gf_form_editor_strings.site_url !== this.value.substring( 0, epdf_gf_form_editor_strings.site_url.length ) ) {
						setFieldError(
							'pdf_url_setting',
							'below',
							__( 'Only PDFs hosted by this website and other websites listing this website in a CORS header ‘Access-Control-Allow-Origin’ can load in the viewer.', 'embed-pdf-gravityforms' )
								+ '<a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS">' + __( 'Learn about CORS →', 'embed-pdf-gravityforms' ) + '</a>'
								//+ '<p><button class="gform-button gform-button--white">Download PDF into Media Library</button></p>'
						);
						return;
					} else {
						resetFieldError( 'pdf_url_setting' );
					}

					// Does the file exist?
					localFileExists( this.value ).then( exists => exists ? resetFieldError( 'pdf_url_setting' ) : setFieldError(
						'pdf_url_setting',
						'below',
						__( 'No file exists at the provided URL.', 'embed-pdf-gravityforms' )
					));
				});
			});
			// Fire input events so errors show as soon as the field is selected.
			elements[1].dispatchEvent(new Event('input'));
		}

		// Launch an upload media modal when the Choose PDF button is clicked.
		elements.push(document.getElementById('choose_pdf_url'));
		if ( elements[2] ) {
			elements[2].removeEventListener( 'click', handleChooseClick );
			elements[2].addEventListener( 'click', handleChooseClick );
		}
	}

	const localFileExists = file => {
		if ( epdf_gf_form_editor_strings.site_url !== file.substring( 0, epdf_gf_form_editor_strings.site_url.length ) ) {
			return Promise.resolve(false);
		}
		const response = fetch(
			file,
			{
				method: 'HEAD',
				cache:'no-store',
				credentials: 'omit',
			}
		).then(response => ( 200 === response.status && response.url === file))
		.catch( exception => false );
		return response;
	}

	// Choose PDF button click handler in form editor
	function handleChooseClick (e) {
		e.preventDefault();
		var file_frame = wp.media.frames.file_frame = wp.media({
			title: __( 'Choose PDF', 'embed-pdf-gravityforms' ),
			button: {
				text: __( 'Load', 'embed-pdf-gravityforms' )
			},
			frame: 'select',
			multiple: false
		});

		// When an image is selected, run a callback.
		file_frame.on('select', function () {
			// Get one image from the uploader.
			var attachment = file_frame.state().get('selection').first().toJSON();
			var urlEl = document.getElementById('field_pdf_url');
			if ( ! urlEl ) {
				urlEl = document.getElementById('url_pdf'); // Feed settings input.
			}
			if ( urlEl && attachment.url ) {
				urlEl.value = attachment.url;
				// Fire the input event so our listener runs.
				urlEl.dispatchEvent(new Event('input'));
			}
		});

		// Finally, open the modal
		file_frame.open();

		// Don't submit forms.
		return false;
	}
}( window.epdfGf = window.epdfGf || {} ));
