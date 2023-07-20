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
					// SetFieldProperty defined in gravityforms/js/form_editor.js
					SetFieldProperty(textSettings[key], this.value);

					//if this works rewrite
					if ( 'field_pdf_url' === key ) {
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
						} else if ( epgf_pdf_viewer_strings.site_url !== this.value.substring( 0, epgf_pdf_viewer_strings.site_url.length ) ) {
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
					}

				});
			});
			// Fire input events so errors show as soon as the field is selected.
			el.dispatchEvent(new Event('input'));
		}
	});

	// Launch an upload media modal when the Choose PDF button is clicked.
	el = document.getElementById('choose_pdf_url');
	if ( el ) {
		el.removeEventListener( 'click', handleChooseClick );
		el.addEventListener( 'click', handleChooseClick );
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
		pdfjsLib.GlobalWorkerOptions.workerSrc = epgf_pdfjs_strings.url_worker;
	}
});

/**
 * Get page info from document, resize canvas accordingly, and render page.
 * @param num Page number.
 */
function renderPage( epgfInstance, pageNum ) {
	epgfInstance.pageRendering = true;
	// Using promise to fetch the page
	epgfInstance.pdfDoc.getPage(pageNum).then(function(page) {

		var viewport = page.getViewport({scale: epgfInstance.pdfDoc.currentScaleValue});
		epgfInstance.canvas.height = viewport.height;
		epgfInstance.canvas.width = viewport.width;

		// Render PDF page into canvas context
		var renderContext = {
			canvasContext: epgfInstance.canvas.getContext('2d'),
			viewport: viewport
		};
		var renderTask = page.render(renderContext);

		// Wait for rendering to finish
		renderTask.promise.then(function() {
			epgfInstance.pageRendering = false;
			if (epgfInstance.pageNumPending !== null) {
				// New page rendering is pending
				renderPage(epgfInstance, epgfInstance.pageNumPending);
				epgfInstance.pageNumPending = null;
			}

			// Set the canvas width once or else zoom in and out break
			epgfInstance.canvas.style.width = '100%';
			epgfInstance.canvas.style.width = epgfInstance.canvas.width + 'px';

			// Dispatch an event after a page render.
			const event = new CustomEvent( 'epgf_render_page', { detail: epgfInstance.pageNum });
			window.dispatchEvent(event);
		});
	});

	// Update page counters
	document.getElementById( epgfInstance.canvasId + '_page_num').textContent = pageNum;
}
/**
 * If another page rendering in progress, waits until the rendering is
 * finised. Otherwise, executes rendering immediately.
 */
function queueRenderPage(epgfInstance) {
	if (epgfInstance.pageRendering) {
		epgfInstance.pageNumPending = epgfInstance.pageNum;
	} else {
		renderPage(epgfInstance,epgfInstance.pageNum);
	}
}
/**
 * Displays previous page.
 */
function onPrevPage(e) {
	var epgfInstance = window['epgf_' + e.target.dataset.viewerId];
	if (epgfInstance.pageNum <= 1) {
		return;
	}
	epgfInstance.pageNum--;
	queueRenderPage(epgfInstance);
	togglePrevNextButtons(epgfInstance);
}
/**
 * Displays next page.
 */
function onNextPage(e) {
	var epgfInstance = window['epgf_' + e.target.dataset.viewerId];
	if (epgfInstance.pageNum >= epgfInstance.pdfDoc.numPages) {
		return;
	}
	epgfInstance.pageNum++;
	queueRenderPage(epgfInstance);
	togglePrevNextButtons(epgfInstance);
}
function togglePrevNextButtons( epgfInstance ) {
	document.getElementById( epgfInstance.canvasId + '_prev').disabled = ( 1 == epgfInstance.pageNum );
	document.getElementById( epgfInstance.canvasId + '_next').disabled = ( epgfInstance.pageNum == epgfInstance.pdfDoc.numPages );
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
	var epgfInstance = window['epgf_' + e.target.dataset.viewerId];
	let newScale = epgfInstance.pdfDoc.currentScaleValue;
	newScale = (newScale * scaleDeltaDefault()).toFixed(2);
	newScale = Math.ceil(newScale * 10) / 10;
	newScale = Math.min(scaleMax(), newScale);
	epgfInstance.pdfDoc.currentScaleValue = newScale;
	renderPage(epgfInstance, epgfInstance.pageNum);

	// Dispatch an event about the new scale value.
	const event = new CustomEvent( 'epgf_scale_value', { detail: newScale });
	window.dispatchEvent(event);
}
function onZoomOut(e) {
	var epgfInstance = window['epgf_' + e.target.dataset.viewerId];
	let newScale = epgfInstance.pdfDoc.currentScaleValue;
	newScale = (newScale / scaleDeltaDefault()).toFixed(2);
	newScale = Math.floor(newScale * 10) / 10;
	newScale = Math.max(scaleMin(), newScale);
	epgfInstance.pdfDoc.currentScaleValue = newScale;
	renderPage(epgfInstance, epgfInstance.pageNum);

	// Dispatch an event about the new scale value.
	const event = new CustomEvent( 'epgf_scale_value', { detail: newScale });
	window.dispatchEvent(event);
}
function loadPreview( fieldId, formId ) {
	var epgfInstance = window['epgf_' + fieldId];
	var fieldElementId = 'field_' + formId + '_' + fieldId;
	if ( '' === epgfInstance.urlPdf ) {
		// There is no PDF to load.
		return;
	}
	/**
	 * Asynchronously downloads PDF.
	 */
	pdfjsLib.getDocument({ url: epgfInstance.urlPdf, verbosity: 0 }).promise.then(function(pdfDoc_) {
		if (epgfInstance.pdfDoc) {
			epgfInstance.pdfDoc.destroy();
		}
		epgfInstance.pdfDoc = pdfDoc_;
		document.getElementById( epgfInstance.canvasId + '_page_count').textContent = epgfInstance.pdfDoc.numPages;
		epgfInstance.pdfDoc.currentScaleValue = epgfInstance.initialScale;

		// Blow up the canvas to 100% width before rendering
		epgfInstance.canvas.style.width = '100%';

		// Initial/first page rendering
		renderPage(epgfInstance, epgfInstance.pageNum);

		// Disable the Previous or Next buttons depending on page count.
		togglePrevNextButtons(epgfInstance);
	}).catch(function(error){
		console.log(error);
		// Display an error on the front-end.
		const el = document.querySelector('#' + fieldElementId + ' .ginput_container_pdf_viewer');
		if ( el && error.message ) {
			const { __ } = wp.i18n;
			var msg = '<p><b>' + __( 'PDF Viewer Error:', 'embed-pdf-gravityforms' ) + '</b> ' + error.message;
			if ( epgf_pdfjs_strings.is_user_logged_in ) {
				msg += ' <a href="https://breakfastco.xyz/embed-pdf-for-gravity-forms/#troubleshooting">' + __( 'Troubleshooting →', 'embed-pdf-gravityforms' ) + '</a>';
			}
			msg += '</p>';
			el.innerHTML += msg;
		}
		// Hide the broken controls.
		const controlEls = document.querySelectorAll( '#' + fieldElementId + ' .epgf-controls-container, #' + fieldElementId + ' .epgf-container' ).forEach( function( el ) { el.style.display ='none'; });
	});
}