// After field settings are loaded in the form editor.
gform.addAction( 'gform_post_load_field_settings', function( field, form ) {
	if ( 'pdf_viewer' !== field[0].type ) {
		return;
	}

	// Populate and update our text settings for initial scale and PDF URL.
	var textSettings = {
		'field_initial_scale': 'initialScale',
		'field_pdf_url':       'pdfUrl',
	};
	Object.keys(textSettings).forEach(key=>{
		var el = document.getElementById(key);
		if ( el ) {
			// Populate the setting value.
			el.value = rgar( field[0], textSettings[key] );
			// Update the setting when users change the value.
			[ 'input', 'propertychange' ].forEach(function(e){
				el.addEventListener(e, function() {
					SetFieldProperty(textSettings[key], this.value);
				});
			});
		}
	});

	// Launch an upload media modal when the Choose PDF button is clicked.
	el = document.getElementById('choose_pdf_url');
	if ( el ) {
		el.removeEventListener( 'click', handleChooseClick );
		el.addEventListener( 'click', handleChooseClick );
	}
}, 10, 2 );

function handleChooseClick (e) {
	e.preventDefault();
	var file_frame = wp.media.frames.file_frame = wp.media({
		title: 'Choose PDF',
		button: {
			text: 'Load'
		},
		frame: 'select',
		multiple: false
	});

	// When an image is selected, run a callback.
	file_frame.on('select', function () {
		// Get one image from the uploader.
		var attachment = file_frame.state().get('selection').first().toJSON();
		var urlEl = document.getElementById('field_pdf_url');
		if ( urlEl && attachment.url ) {
			urlEl.value = attachment.url;
			// Fire the input event so our listener runs.
			urlEl.dispatchEvent(new Event('input'));
		}
	});

	// Finally, open the modal
	file_frame.open();
}