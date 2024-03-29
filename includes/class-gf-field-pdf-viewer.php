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

		$url = $this->get_url( $value );

		/**
		 * Do we have a PDF?
		 * Are we on a feed settings page? This isn't a problem when configuring
		 * feeds in Inkless. We want to load the field inputs anyways so we can
		 * load a PDF into the viewer after the user takes some action, like
		 * uploading one or pasting a URL.
		 */
		if ( empty( $url ) && 'form_settings_inkless' !== GFForms::get_page() ) {
			/* translators: 1. Gravity Forms field ID. 2. Gravity Forms form ID. */
			$this->log_error( sprintf( __( 'No PDF to load into field %1$s on form %2$s', 'embed-pdf-gravityforms' ), $this->id, $form['id'] ) );
			return;
		}

		$this->sanitize_settings();

		$form_id   = $form['id'] ?? rgget( 'id' );
		$field_id  = sprintf( 'field_%s_%s', $form_id, $this->id );
		$canvas_id = $field_id . '_embed_pdf_gravityforms';

		// Output the viewer canvas and controls.
		return sprintf(
			'<div class="ginput_container ginput_container_pdf_viewer"><div class="epdf-controls-container">'
				// Paging controls.
				. '<span class="page"><button class="button" onclick="return false" id="%1$s_prev" data-field="%7$s" data-form="%10$s" title="%2$s">%2$s</button> <button class="button" onclick="return false" id="%1$s_next" data-field="%7$s" data-form="%10$s" title="%3$s">%3$s</button></span> '
				. '<span class="paging">%4$s <span id="%1$s_page_num"></span> / <span id="%1$s_page_count"></span></span> '
				// Zoom controls.
				. '<span class="zoom"><button class="button" onclick="return false" id="%1$s_zoom_out" data-field="%7$s" data-form="%10$s" title="%5$s">%5$s</button> <button class="button" onclick="return false" id="%1$s_zoom_in" data-field="%7$s" data-form="%10$s" title="%6$s">%6$s</button></span>'
				. '</div>'
				. '<div class="epdf-container"><canvas id="%1$s" class="epdf" data-initial-scale="%9$s" data-page-num="1" data-page-pending="" data-rendering="false" data-field="%7$s" data-form="%10$s"></canvas></div>'
				. '<input id="input_%10$s_%7$s" name="input_%7$s" type="hidden" value="%8$s">'
				. '</div>',
			/* 1 */ esc_attr( $canvas_id ),
			/* 2 */ esc_html__( 'Previous', 'embed-pdf-gravityforms' ),
			/* 3 */ esc_html__( 'Next', 'embed-pdf-gravityforms' ),
			/* 4 */ esc_html__( 'Page:', 'embed-pdf-gravityforms' ),
			/* 5 */ esc_html__( 'Zoom Out', 'embed-pdf-gravityforms' ),
			/* 6 */ esc_html__( 'Zoom In', 'embed-pdf-gravityforms' ),
			/* 7 */ esc_attr( $this->id ),
			/* 8 */ esc_attr( $url ),
			/* 9 */ esc_attr( $this->initialScale ?? GF_Addon_PDF_Viewer::DEFAULT_SCALE_VALUE ),
			/*10 */ esc_attr( $form_id )
		);
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
	 * Made of code copied out of form_display.php and webapi.php. Get the field
	 * value via Dynamic Population or $_POST to replicate the first argument to
	 * $this->get_field_content().
	 *
	 * @return string
	 */
	public function get_field_dynamic_value() {
		if ( ! $this->allowsPrepopulate ) {
			return '';
		}

		$field_value = GFForms::post( 'input_' . $this->id );
		if ( ! empty( $field_value ) ) {
			return $field_value;
		}

		$field_value = GFForms::get( $this->inputName );
		return $field_value;
	}

	/**
	 * Returns the URL of a PDF this field should display defined by the field
	 * settings in the editor or dynamic population.
	 *
	 * @param  string $value
	 * @return string
	 */
	public function get_url( $value ) {
		// The user might have chosen a PDF and saved it with the form.
		$url = $this->pdfUrl;
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			$url = '';
		}

		// Do we have a PDF URL via Dynamic Population, $_POST, or partial entry?
		if ( is_string( $value ) && '' !== $value ) {
			// Is the populated value a URL?
			if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
				// Yes.
				$url = esc_url( $value );
			}
		}

		return $url;
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
