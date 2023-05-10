<?php
/**
 * Gravity Forms PDF Viewer Field
 *
 * @package embed-pdf-gravityforms
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


/**
 * GF_Field_PDF_Viewer
 */
class GF_Field_PDF_Viewer extends GF_Field {

	/**
	 * type
	 *
	 * @var string
	 */
	public $type = 'pdf_viewer';

	/**
	 * get_form_editor_field_title
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'PDF Viewer', 'embed-pdf-gravityforms' );
	}

	/**
	 * get_form_editor_button
	 *
	 * @return array
	 */
	public function get_form_editor_button() {
		return array(
			'group' => 'advanced_fields',
			'text'  => $this->get_form_editor_field_title(),
		);
	}

	/**
	 * Adds conditional logic support.
	 *
	 * @return bool
	 */
	public function is_conditional_logic_supported() {
		return true;
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Embed PDF for Gravity Forms', 'embed-pdf-gravityforms' );
	}

	/**
	 * Returns the field's form editor icon.
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return 'gform-icon--page';
	}

	public function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'size_setting', // ?
			'rules_setting',
			'visibility_setting',
			'duplicate_setting',
			'default_value_setting', // ?
			'placeholder_setting', // ?
			'description_setting',
			'css_class_setting',
			'initial_scale_setting', /* Ours */
			'pdf_url_setting', /* Ours */
		);
	}
	
	/**
	 * This method is used to define the fields inner markup, including the div 
	 * with the ginput_container class.
	 *
	 * @param  mixed $form
	 * @param  mixed $value
	 * @param  mixed $entry
	 * @return void
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {

		// The user might have chosen a PDF and saved it with the form.
		$url = $this->pdfUrl;
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			$url = '';
		}

		// Do we have a PDF URL via Dynamic Population?
		if ( ! empty( $value ) ) {
			// Is the populated value a URL?
			if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
				// Yes.
				$url = esc_url( $value );
			}
		}

		// Do we even have a PDF?
		if ( empty( $url ) ) {
			// No.
			$this->log_error( sprintf( __( 'No PDF to load into field %s on form %s', 'embed-pdf-gravityforms' ), $this->id, $form['id'] ) );
			return;
		}

		$canvas_id = sprintf(
			'field_%s_%s_embed_pdf_gravityforms',
			$form['id'],
			$this->id
		);

		return sprintf( 
			'<div class="ginput_container ginput_container_pdf_viewer"><div class="epgf-controls-container">'
				// Paging controls.
				. '<button class="button" onclick="return false" id="%1$s_prev" title="%2$s">%2$s</button> <button class="button" onclick="return false" id="%1$s_next" title="%3$s">%3$s</button> '
				. '<span class="paging">%4$s <span id="%1$s_page_num"></span> / <span id="%1$s_page_count"></span></span> '
				// Zoom controls.
				. '<span class="zoom"><button class="button" onclick="return false" id="%1$s_zoom_out" title="%6$s">%6$s</button> <button class="button" onclick="return false" id="%1$s_zoom_in" title="%7$s">%7$s</button></span>'
				. '</div>'
				. '<div class="epgf-container"><canvas id="%5$s" class="epgf"></canvas></div></div>',
			$canvas_id,
			esc_html__( 'Previous', 'embed-pdf-gravityforms' ),
			esc_html__( 'Next', 'embed-pdf-gravityforms' ),
			esc_html__( 'Page:', 'embed-pdf-gravityforms' ),
			esc_attr( $canvas_id ),
			esc_html__( 'Zoom Out', 'embed-pdf-gravityforms' ),
			esc_html__( 'Zoom In', 'embed-pdf-gravityforms' )
		)
			. "<script type=\"text/javascript\">
		window.addEventListener( 'load', function () {
			// The workerSrc property shall be specified.
			pdfjsLib.GlobalWorkerOptions.workerSrc = epgf.url_worker;

			//These variables should be in an array just like the URLs
			var pdfDoc = null,
				pageNum = 1,
				pageRendering = false,
				pageNumPending = null,
				canvas = document.getElementById('$canvas_id'),
				epgf_{$this->id} = {
					url_pdf: '{$url}',
					initial_scale: {$this->initialScale},
				};

			/**
			 * Get page info from document, resize canvas accordingly, and render page.
			 * @param num Page number.
			 */
			function renderPage(num) {
				pageRendering = true;
				// Using promise to fetch the page
				pdfDoc.getPage(num).then(function(page) {

					var viewport = page.getViewport({scale: pdfDoc.currentScaleValue});
					canvas.height = viewport.height;
					canvas.width = viewport.width;
				
					// Render PDF page into canvas context
					var renderContext = {
						canvasContext: canvas.getContext('2d'),
						viewport: viewport
					};
					var renderTask = page.render(renderContext);
				
					// Wait for rendering to finish
					renderTask.promise.then(function() {
						pageRendering = false;
						if (pageNumPending !== null) {
							// New page rendering is pending
							renderPage(pageNumPending);
							pageNumPending = null;
						}

						// Set the canvas width once or else zoom in and out break
						document.getElementById('{$canvas_id}').style.width = '100%';
						var fullWidth = document.getElementById('{$canvas_id}').width;
						document.getElementById('{$canvas_id}').style.width = fullWidth + 'px';
					});
				});
			
				// Update page counters
				document.getElementById('{$canvas_id}_page_num').textContent = num;
			}
			
			/**
			 * If another page rendering in progress, waits until the rendering is
			 * finised. Otherwise, executes rendering immediately.
			 */
			function queueRenderPage(num) {
				if (pageRendering) {
					pageNumPending = num;
				} else {
					renderPage(num);
				}
			}
			
			/**
			 * Displays previous page.
			 */
			function onPrevPage() {
				if (pageNum <= 1) {
					return;
				}
				pageNum--;
				queueRenderPage(pageNum);
				togglePrevNextButtons();
			}
			document.getElementById('{$canvas_id}_prev').addEventListener('click', onPrevPage);
			
			/**
			 * Displays next page.
			 */
			function onNextPage() {
				if (pageNum >= pdfDoc.numPages) {
					return;
				}
				pageNum++;
				queueRenderPage(pageNum);
				togglePrevNextButtons();
			}
			document.getElementById('{$canvas_id}_next').addEventListener('click', onNextPage);
			
			function togglePrevNextButtons() {
				document.getElementById('{$canvas_id}_prev').disabled = ( 1 == pageNum );
				document.getElementById('{$canvas_id}_next').disabled = ( pageNum == pdfDoc.numPages );
			}

			const DEFAULT_SCALE_DELTA = 1.1;
			const MIN_SCALE = 0.25;
			const MAX_SCALE = 10.0;

			function onZoomIn(ticks) {
				let newScale = pdfDoc.currentScaleValue;
				do {
				  newScale = (newScale * DEFAULT_SCALE_DELTA).toFixed(2);
				  newScale = Math.ceil(newScale * 10) / 10;
				  newScale = Math.min(MAX_SCALE, newScale);
				} while (--ticks && newScale < MAX_SCALE);
				pdfDoc.currentScaleValue = newScale;
				renderPage(pageNum);
			}
			document.getElementById('{$canvas_id}_zoom_in').addEventListener('click', onZoomIn);
			
			function onZoomOut(ticks) {
				let newScale = pdfDoc.currentScaleValue;
				do {
				  newScale = (newScale / DEFAULT_SCALE_DELTA).toFixed(2);
				  newScale = Math.floor(newScale * 10) / 10;
				  newScale = Math.max(MIN_SCALE, newScale);
				} while (--ticks && newScale > MIN_SCALE);
				pdfDoc.currentScaleValue = newScale;
				renderPage(pageNum);
			}			
			document.getElementById('{$canvas_id}_zoom_out').addEventListener('click', onZoomOut);

			/**
			 * Asynchronously downloads PDF.
			 */
			pdfjsLib.getDocument({ url: epgf_{$this->id}.url_pdf, verbosity: 0 }).promise.then(function(pdfDoc_) {
				pdfDoc = pdfDoc_;
				document.getElementById('{$canvas_id}_page_count').textContent = pdfDoc.numPages;
				pdfDoc.currentScaleValue = epgf_{$this->id}.initial_scale ?? epgf.initial_scale;

				// Blow up the canvas to 100% width before rendering
				document.getElementById('{$canvas_id}').style.width = '100%';

				// Initial/first page rendering
				renderPage(pageNum);

				// Disable the Previous or Next buttons depending on page count.
				togglePrevNextButtons();
			});
		});
		</script>";
	}
	
	/**
	 * This method is used to define the fields overall appearance, such as how
	 * the admin buttons, field label, description or validation messages are
	 * included.
	 *
	 * @param  mixed $value
	 * @param  mixed $force_frontend_label
	 * @param  mixed $form
	 * @return void
	 */
	public function get_field_content( $value, $force_frontend_label, $form ) {
		$field_content = sprintf(
			'%s <label class="gfield_label">%s</label><div class="gf-pdf-viewer-inline gf-pdf-viewer"></div><div class="gf-html-container"><span class="gf_blockheader"><i class="fa fa-file-text-o fa-lg"></i> %s</span><span>%s</span></div>',
			$this->get_admin_buttons(),
			$this->get_field_label( $force_frontend_label, $value ),
			esc_html__( 'PDF Viewer', 'embed-pdf-gravityforms' ),
			esc_html__( 'This is a content placeholder. PDFs are not displayed in the form admin. Preview this form to view the content.', 'embed-pdf-gravityforms' )
		);
		return ! is_admin() ? '{FIELD}' : $field_content;
	}

	protected function log_error( $message ) {
		// Logging is officially supported in Add-ons not Fields.
		$addon = GF_Addon_PDF_Viewer::get_instance();
		$addon->log_error( $message );
	}

	public function sanitize_settings() {
		parent::sanitize_settings();
		if ( false === $this->initialScale ) {
			$this->initialScale = GF_Addon_PDF_Viewer::DEFAULT_SCALE_VALUE;
		}
		$this->initialScale = GFCommon::to_number( $this->initialScale );
	}
}
GF_Fields::register( new GF_Field_PDF_Viewer() );
