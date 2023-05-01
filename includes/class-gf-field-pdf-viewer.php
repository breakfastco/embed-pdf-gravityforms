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
			'size_setting',
			'rules_setting',
			'visibility_setting',
			'duplicate_setting',
			'default_value_setting',
			'placeholder_setting',
			'description_setting',
			'css_class_setting',
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

		//TODO do we have a PDF to display?
		$handle = 'embed-pdf-gravityforms-pdfjs';
		wp_enqueue_script(
			$handle,
			plugins_url( 'js/pdfjs/pdf.min.js', EMBED_PDF_GRAVITYFORMS_PATH ),
			array(),
			EMBED_PDF_GRAVITYFORMS_VERSION,
			true
		);
		wp_enqueue_script(
			'embed-pdf-gravityforms-pdfjs-worker',
			plugins_url( 'js/pdfjs/pdf.worker.min.js', EMBED_PDF_GRAVITYFORMS_PATH ),
			array( $handle ),
			EMBED_PDF_GRAVITYFORMS_VERSION,
			true
		);

		// TODO Create "From a URL" setting.
		//$pdf_url = '';

		// TODO Allow more than one of these on a page.
		return '<canvas id="embed-pdf-gravityforms"></canvas>'

		. "
		<script type=\"text/javascript\">
		window.addEventListener( 'load', function () {
			//
			// If absolute URL from the remote server is provided, configure the CORS
			// header on that server.
			//
			const url = 'https://breakfastco.test/wp-content/uploads/vscode-keyboard-shortcuts-macos.pdf';

			//
			// The workerSrc property shall be specified.
			//
			pdfjsLib.GlobalWorkerOptions.workerSrc =
				'https://breakfastco.test/wp-content/plugins/embed-pdf-gravityforms/js/pdfjs/pdf.worker.min.js';

			//
			// Asynchronous download PDF
			//
			const loadingTask = pdfjsLib.getDocument(url);
			(async () => {
				const pdf = await loadingTask.promise;
				//
				// Fetch the first page
				//
				const page = await pdf.getPage(1);
				const scale = 1.5;
				const viewport = page.getViewport({ scale });
				// Support HiDPI-screens.
				const outputScale = window.devicePixelRatio || 1;

				//
				// Prepare canvas using PDF page dimensions
				//
				const canvas = document.getElementById(\"embed-pdf-gravityforms\");
				const context = canvas.getContext(\"2d\");

				canvas.width = Math.floor(viewport.width * outputScale);
				canvas.height = Math.floor(viewport.height * outputScale);
				canvas.style.width = Math.floor(viewport.width) + \"px\";
				canvas.style.height = Math.floor(viewport.height) + \"px\";

				const transform = outputScale !== 1 
				? [outputScale, 0, 0, outputScale, 0, 0] 
				: null;

				//
				// Render PDF page into canvas context
				//
				const renderContext = {
				canvasContext: context,
				transform,
				viewport,
				};
				page.render(renderContext);
			})();
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
		return ! $is_admin ? '{FIELD}' : $field_content;
	}
}
GF_Fields::register( new GF_Field_PDF_Viewer() );
