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
	 * Field type.
	 *
	 * @var string
	 */
	public $type = GF_Addon_PDF_Viewer::FIELD_TYPE;

	/**
	 * Returns the field title.
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'PDF Viewer', 'embed-pdf-gravityforms' );
	}

	/**
	 * Returns the field button properties for the form editor. The array
	 * contains two elements:
	 * 'group' => 'standard_fields'|'advanced_fields'|'post_fields'|'pricing_fields'
	 * 'text'  => 'Button text'
	 *
	 * @since 2.4
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

	/**
	 * The class names of the settings which should be available on the field in
	 * the form editor.
	 *
	 * @return array
	 */
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
	 * Define the fields inner markup, including the div with the
	 * ginput_container class.
	 *
	 * @param array        $form The Form Object currently being processed.
	 * @param string|array $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array   $entry Null or the Entry Object currently being edited.
	 *
	 * @return string
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
			// Are we on a feed settings page? This isn't a problem when configuring feeds in Inkless.
			if ( 'form_settings_embedpdfviewerpro' !== GFForms::get_page() ) {
				$this->log_error( sprintf( __( 'No PDF to load into field %1$s on form %2$s', 'embed-pdf-gravityforms' ), $this->id, $form['id'] ) );
				return;
			}
		}

		$this->sanitize_settings();

		$form_id   = $form['id'] ?? rgget( 'id' );
		$field_id  = sprintf( 'field_%s_%s', $form_id, $this->id );
		$canvas_id = $field_id . '_embed_pdf_gravityforms';

		// Output the viewer canvas and controls.
		return sprintf(
			'<div class="ginput_container ginput_container_pdf_viewer"><div class="epdf-controls-container">'
				// Paging controls.
				. '<button class="button" onclick="return false" id="%1$s_prev" data-viewer-id="%7$s" title="%2$s">%2$s</button> <button class="button" onclick="return false" id="%1$s_next" data-viewer-id="%7$s" title="%3$s">%3$s</button> '
				. '<span class="paging">%4$s <span id="%1$s_page_num"></span> / <span id="%1$s_page_count"></span></span> '
				// Zoom controls.
				. '<span class="zoom"><button class="button" onclick="return false" id="%1$s_zoom_out" data-viewer-id="%7$s" title="%5$s">%5$s</button> <button class="button" onclick="return false" id="%1$s_zoom_in" data-viewer-id="%7$s" title="%6$s">%6$s</button></span>'
				. '</div>'
				. '<div class="epdf-container"><canvas id="%1$s" class="epdf"></canvas></div>'
				. '<input name="input_%7$s" type="hidden" value="%8$s">'
				. '</div>',
			esc_attr( $canvas_id ),
			esc_html__( 'Previous', 'embed-pdf-gravityforms' ),
			esc_html__( 'Next', 'embed-pdf-gravityforms' ),
			esc_html__( 'Page:', 'embed-pdf-gravityforms' ),
			esc_html__( 'Zoom Out', 'embed-pdf-gravityforms' ),
			esc_html__( 'Zoom In', 'embed-pdf-gravityforms' ),
			esc_attr( $this->id ),
			esc_attr( $url )
		)
			. "<script type=\"text/javascript\">
		var epdf_{$this->id} = {
				canvas: document.getElementById('$canvas_id'),
				canvasId: '{$canvas_id}',
				initialScale: {$this->initialScale} ?? epdf_gf_pdfjs_strings.initialScale,
				pageNum: 1,
				pageNumPending: null,
				pageRendering: false,
				pdfDoc: null,
				urlPdf: '{$url}',
			};
			window.addEventListener( 'load', function () {
				document.getElementById('{$canvas_id}_prev').addEventListener('click', onPrevPage);
				document.getElementById('{$canvas_id}_next').addEventListener('click', onNextPage);
				document.getElementById('{$canvas_id}_zoom_in').addEventListener('click', onZoomIn);
				document.getElementById('{$canvas_id}_zoom_out').addEventListener('click', onZoomOut);
				loadPreview( {$this->id}, {$form_id} );
			});
		</script>";
	}

	/**
	 * This method is used to define the fields overall appearance, such as how
	 * the admin buttons, field label, description or validation messages are
	 * included.
	 *
	 * @param string|array $value                The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param bool         $force_frontend_label Should the frontend label be displayed in the admin even if an admin label is configured.
	 * @param array        $form                 The Form Object currently being processed.
	 * @return string
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

	/**
	 * Save an error. Logging is officially supported in Add-ons not Fields.
	 *
	 * @param  string $message The message to log.
	 * @return void
	 */
	protected function log_error( $message ) {
		// Logging is officially supported in Add-ons not Fields.
		$addon = GF_Addon_PDF_Viewer::get_instance();
		$addon->log_error( $message );
	}

	/**
	 * Forces settings into expected values while saving the form object. No
	 * escaping should be done at this stage to prevent double escaping on
	 * output.
	 *
	 * @return void
	 */
	public function sanitize_settings() {
		parent::sanitize_settings();
		if ( empty( $this->initialScale ) ) {
			$this->initialScale = GF_Addon_PDF_Viewer::DEFAULT_SCALE_VALUE;
		}
		$this->initialScale = GFCommon::to_number( $this->initialScale );
	}
}
GF_Fields::register( new GF_Field_PDF_Viewer() );
