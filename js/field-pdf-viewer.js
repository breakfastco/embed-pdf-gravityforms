// After field settings are loaded in the form editor.
gform.addAction( 'gform_post_load_field_settings', function( field, form ) {
	if ( epdf_gf_pdf_viewer_strings.field_type !== field[0].type ) {
		return;
	}

	// Populate initial scale setting.
	var elements = [];
	elements.push(document.getElementById( 'field_initial_scale' ));
	var keys = [];
	keys.push( 'initialScale' );
	if ( elements[0] ) {
		// Populate the setting value.
		elements[0].value = rgar( field[0], keys[0] );
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
		elements[1].value = rgar( field[0], keys[1] );
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
					const { __ } = wp.i18n;
					setFieldError(
						'pdf_url_setting',
						'below',
						__( 'Please enter a valid URL.', 'embed-pdf-gravityforms' )
					);
				// Is it a local URL?
				} else if ( epdf_gf_pdf_viewer_strings.site_url !== this.value.substring( 0, epdf_gf_pdf_viewer_strings.site_url.length ) ) {
					const { __ } = wp.i18n;
					setFieldError(
						'pdf_url_setting',
						'below',
						__( 'Only PDFs hosted by this website and other websites listing this website in a CORS header ‘Access-Control-Allow-Origin’ can load in the viewer.', 'embed-pdf-gravityforms' )
							+ '<a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS">' + __( 'Learn about CORS →', 'embed-pdf-gravityforms' ) + '</a>'
							//+ '<p><button class="gform-button gform-button--white">Download PDF into Media Library</button></p>'
					);
				} else {
					resetFieldError( 'pdf_url_setting' );
				}
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
}, 10, 2 );

// Choose PDF button click handler in form editor & feed settings in pro
function handleChooseClick (e) {
	e.preventDefault();
	const { __ } = wp.i18n;
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

function isValidHttpUrl(string) {
	let url;

	try {
		url = new URL(string);
	} catch (_) {
		return false;
	}

	return url.protocol === "http:" || url.protocol === "https:";
}

// Loading, paging, & zooming pdf.js viewers
window.addEventListener( 'load', function(e) {
	// The workerSrc property shall be specified.
	if ( 'undefined' !== typeof pdfjsLib ) {
		pdfjsLib.GlobalWorkerOptions.workerSrc = epdf_gf_pdfjs_strings.url_worker;
	}
});

/**
 * Get page info from document, resize canvas accordingly, and render page.
 * @param num Page number.
 */
function renderPage( epdfInstance, pageNum ) {
	epdfInstance.pageRendering = true;
	// Using promise to fetch the page
	epdfInstance.pdfDoc.getPage(pageNum).then(function(page) {

		var viewport = page.getViewport({scale: epdfInstance.pdfDoc.currentScaleValue});
		epdfInstance.canvas.height = viewport.height;
		epdfInstance.canvas.width = viewport.width;

		// Render PDF page into canvas context
		var renderContext = {
			canvasContext: epdfInstance.canvas.getContext('2d'),
			viewport: viewport
		};
		var renderTask = page.render(renderContext);

		// Wait for rendering to finish
		renderTask.promise.then(function() {
			epdfInstance.pageRendering = false;
			if (epdfInstance.pageNumPending !== null) {
				// New page rendering is pending
				renderPage(epdfInstance, epdfInstance.pageNumPending);
				epdfInstance.pageNumPending = null;
			}

			// Set the canvas width once or else zoom in and out break
			epdfInstance.canvas.style.width = '100%';
			epdfInstance.canvas.style.width = epdfInstance.canvas.width + 'px';

			// Dispatch an event after a page render.
			const event = new CustomEvent( 'epdf_render_page', { detail: epdfInstance.pageNum });
			window.dispatchEvent(event);
		});
	});

	// Update page counters
	document.getElementById( epdfInstance.canvasId + '_page_num').textContent = pageNum;
}
/**
 * If another page rendering in progress, waits until the rendering is
 * finised. Otherwise, executes rendering immediately.
 */
function queueRenderPage(epdfInstance) {
	if (epdfInstance.pageRendering) {
		epdfInstance.pageNumPending = epdfInstance.pageNum;
	} else {
		renderPage(epdfInstance,epdfInstance.pageNum);
	}
}
/**
 * Displays previous page.
 */
function onPrevPage(e) {
	var epdfInstance = window['epdf_' + e.target.dataset.viewerId];
	if (epdfInstance.pageNum <= 1) {
		return;
	}
	epdfInstance.pageNum--;
	queueRenderPage(epdfInstance);
	togglePrevNextButtons(epdfInstance);
}
/**
 * Displays next page.
 */
function onNextPage(e) {
	var epdfInstance = window['epdf_' + e.target.dataset.viewerId];
	if (epdfInstance.pageNum >= epdfInstance.pdfDoc.numPages) {
		return;
	}
	epdfInstance.pageNum++;
	queueRenderPage(epdfInstance);
	togglePrevNextButtons(epdfInstance);
}
function togglePrevNextButtons( epdfInstance ) {
	document.getElementById( epdfInstance.canvasId + '_prev').disabled = ( 1 == epdfInstance.pageNum );
	document.getElementById( epdfInstance.canvasId + '_next').disabled = ( epdfInstance.pageNum == epdfInstance.pdfDoc.numPages );
}
function scaleDeltaDefault() {
	return 1.1;
}
function scaleMin() {
	return 0.25;
}
function scaleMax() {
	return 10.0;
}

function onZoomIn(e) {
	var epdfInstance = window['epdf_' + e.target.dataset.viewerId];
	let newScale = epdfInstance.pdfDoc.currentScaleValue;
	newScale = (newScale * scaleDeltaDefault()).toFixed(2);
	newScale = Math.ceil(newScale * 10) / 10;
	newScale = Math.min(scaleMax(), newScale);
	epdfInstance.pdfDoc.currentScaleValue = newScale;
	renderPage(epdfInstance, epdfInstance.pageNum);

	// Dispatch an event about the new scale value.
	const event = new CustomEvent( 'epdf_scale_value', { detail: newScale });
	window.dispatchEvent(event);
}
function onZoomOut(e) {
	var epdfInstance = window['epdf_' + e.target.dataset.viewerId];
	let newScale = epdfInstance.pdfDoc.currentScaleValue;
	newScale = (newScale / scaleDeltaDefault()).toFixed(2);
	newScale = Math.floor(newScale * 10) / 10;
	newScale = Math.max(scaleMin(), newScale);
	epdfInstance.pdfDoc.currentScaleValue = newScale;
	renderPage(epdfInstance, epdfInstance.pageNum);

	// Dispatch an event about the new scale value.
	const event = new CustomEvent( 'epdf_scale_value', { detail: newScale });
	window.dispatchEvent(event);
}
function loadPreview( fieldId, formId ) {
	var epdfInstance = window['epdf_' + fieldId];
	var fieldElementId = 'field_' + formId + '_' + fieldId;
	if ( 'undefined' === typeof epdfInstance ) {
		// Something is wrong, spin up data for this this preview is missing.
		if ( epdf_gf_pdf_viewer_strings.script_debug ) {
			console.log( '[Embed PDF for Gravity Forms] loadPreview( ' + fieldId + ' ) failed, spin up data missing' );
		}
		return;
	}

	if ( '' === epdfInstance.urlPdf ) {
		// There is no PDF to load.
		if ( epdf_gf_pdf_viewer_strings.script_debug ) {
			console.log( '[Embed PDF for Gravity Forms] loadPreview( ' + fieldId + ' ) failed, no PDF URL' );
		}
		return;
	}

	const controls = {
		'prev': 'onPrevPage',
		'next': 'onNextPage',
		'zoom_in': 'onZoomIn',
		'zoom_out': 'onZoomOut'
	};
	Object.keys(controls).forEach(function(key, index){
		var el = document.getElementById( epdfInstance.canvasId + '_' + key);
		if ( el ) {
			el.addEventListener('click', window[controls[key]]);
		}
	});

	/**
	 * Asynchronously downloads PDF.
	 */
	pdfjsLib.getDocument({ url: epdfInstance.urlPdf, verbosity: 0 }).promise.then(function(pdfDoc_) {
		if (epdfInstance.pdfDoc) {
			epdfInstance.pdfDoc.destroy();
		}
		epdfInstance.pdfDoc = pdfDoc_;
		document.getElementById( epdfInstance.canvasId + '_page_count').textContent = epdfInstance.pdfDoc.numPages;
		epdfInstance.pdfDoc.currentScaleValue = epdfInstance.initialScale;

		// Blow up the canvas to 100% width before rendering
		epdfInstance.canvas.style.width = '100%';

		// Initial/first page rendering
		renderPage(epdfInstance, epdfInstance.pageNum);

		// Disable the Previous or Next buttons depending on page count.
		togglePrevNextButtons(epdfInstance);
	}).catch(function(error){
		if ( epdf_gf_pdf_viewer_strings.script_debug ) {
			console.log( '[Embed PDF for Gravity Forms]' );
			console.log( error );
		}
		// Display an error on the front-end.
		const el = document.querySelector('#' + fieldElementId + ' .ginput_container_pdf_viewer');
		if ( el && error.message ) {
			const { __ } = wp.i18n;
			var msg = '<p><b>' + __( 'PDF Viewer Error:', 'embed-pdf-gravityforms' ) + '</b> ' + error.message;
			if ( epdf_gf_pdfjs_strings.is_user_logged_in ) {
				msg += ' <a href="https://breakfastco.xyz/embed-pdf-for-gravity-forms/#troubleshooting">' + __( 'Troubleshooting →', 'embed-pdf-gravityforms' ) + '</a>';
			}
			msg += '</p>';
			el.innerHTML += msg;
		}
		// Hide the broken controls.
		const controlEls = document.querySelectorAll( '#' + fieldElementId + ' .epdf-controls-container, #' + fieldElementId + ' .epdf-container' ).forEach( function( el ) { el.style.display ='none'; });
	});
}