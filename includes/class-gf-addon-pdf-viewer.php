<?php
/**
 * PDF Viewer Add-on
 *
 * @package embed-pdf-gravityforms
 */

defined( 'ABSPATH' ) || exit;

if ( method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
	GFForms::include_feed_addon_framework();
}

if ( class_exists( 'GFAddOn' ) ) {
	/**
	 * GF_Addon_PDF_Viewer
	 */
	class GF_Addon_PDF_Viewer extends GFAddOn {

		const DEFAULT_SCALE_VALUE = '1';

		/**
		 * Defines the version of the Gravity Forms Quote Tracker Writer Add-On.
		 *
		 * @since 1.0
		 * @var string $_version Contains the version.
		 */
		protected $_version = EMBED_PDF_GRAVITYFORMS_VERSION;

		/**
		 * Defines the minimum Gravity Forms version required.
		 *
		 * @since 1.0
		 * @var string $_min_gravityforms_version The minimum version required.
		 */
		protected $_min_gravityforms_version = '1.9';

		/**
		 * The add-on slug doubles as the key in which all the settings are stored. If this changes, also change uninstall.php where the string is hard-coded.
		 *
		 * @var $_slug  string The add-on slug doubles as the key in which all the settings are stored. If this changes, also change uninstall.php where the string is hard-coded.
		 * @see get_slug()
		 */
		protected $_slug = 'embedpdfviewer';

		/**
		 * Defines the main plugin file.
		 *
		 * @since 1.0
		 * @var string $_path The path to the main plugin file, relative to the plugins folder.
		 */
		protected $_path = 'embed-pdf-gravityforms/embed-pdf-gravityforms.php';

		/**
		 * Defines the full path to this class file.
		 *
		 * @since 1.0
		 * @var string $_full_path The full path.
		 */
		protected $_full_path = __FILE__;

		/**
		 * Defines the URL where this add-on can be found.
		 *
		 * @since 1.0
		 * @var string
		 */
		protected $_url = 'https://github.com/breakfastco/embed-pdf-gravityforms';

		/**
		 * Defines the title of this add-on.
		 *
		 * @since 1.0
		 * @var string $_title The title of the add-on.
		 */
		protected $_title = 'Embed PDF Viewer';

		/**
		 * Defines the short title of the add-on.
		 *
		 * @since 1.0
		 * @var string $_short_title The short title.
		 */
		protected $_short_title = 'Embed PDF Viewer';

		/**
		 * Defines the capabilities needed for the Add-On. Ensures compatibility
		 * with Members plugin.
		 *
		 * @since  1.0
		 * @var    array $_capabilities The capabilities needed for the Add-On
		 */
		protected $_capabilities = array(
			'embed-pdf-gravityforms',
			'embed-pdf-gravityforms_uninstall',
			'embed-pdf-gravityforms_results',
			'embed-pdf-gravityforms_settings',
			'embed-pdf-gravityforms_form_settings',
		);

		private static $_instance = null;

		/**
		 * Get an instance of this class.
		 *
		 * @return GF_Addon_PDF_Viewer
		 */
		public static function get_instance() {
			if ( null === self::$_instance ) {
				self::$_instance = new GF_Addon_PDF_Viewer();
			}
			return self::$_instance;
		}

		/**
		 * Handles anything which requires early initialization.
		 *
		 * @return void
		 */
		public function pre_init() {
			parent::pre_init();

			// Include the class that defines our PDF Viewer field.
			if ( $this->is_gravityforms_supported() && class_exists( 'GF_Field' ) ) {
				require_once dirname( EMBED_PDF_GRAVITYFORMS_PATH ) . '/includes/class-gf-field-pdf-viewer.php';
			}

			add_action( 'gform_field_standard_settings', array( $this, 'add_field_settings' ), 10, 2 );
		}

		/**
		 * Outputs HTML that renders the PDF section of the field settings
		 * sidebar.
		 *
		 * @param  int $level Specify the position that the settings should be displayed.
		 * @param  int $form_id The ID of the form for which the settings are presented.
		 * @return void
		 */
		public function add_field_settings( $level, $form_id ) {
			if ( 20 !== $level ?? 0 ) {
				return;
			}

			// PDF URL.
			?><li class="pdf_url_setting field_setting">
			<label for="field_pdf_url" class="section_label">
				<?php esc_html_e( 'PDF', 'embed-pdf-gravityforms' ); ?>
			</label>
			<button class="gform-button gform-button--white" id="choose_pdf_url"><?php esc_html_e( 'Choose PDF', 'embed-pdf-gravityforms' ); ?></button>
			<input type="text" id="field_pdf_url" autocomplete="off" placeholder="https://" />
			</li><?php

			// Initial Scale.
			?><li class="initial_scale_setting field_setting">
			<label for="field_initial_scale" class="section_label">
				<?php esc_html_e( 'Initial Scale', 'embed-pdf-gravityforms' ); ?>
			</label>
			<input type="text" id="field_initial_scale" autocomplete="off" placeholder="<?php echo esc_attr( self::DEFAULT_SCALE_VALUE ); ?>" />
			<div id="gform_server_initial_scale_notice">
				<small><?php esc_html_e( 'Loading too small to read? Increase this value to zoom in.', 'embed-pdf-gravityforms' ); ?></small>
			</div>
			</li><?php
		}

		/**
		 * Return the scripts which should be enqueued.
		 *
		 * @return array
		 */
		public function scripts() {
			$min     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			$scripts = array(
				array(
					// Need this JavaScript file or we can't load PDFs.
					'handle'    => 'epdf_gf_pdfjs',
					'src'       => plugins_url( 'js/pdfjs/pdf.min.js', EMBED_PDF_GRAVITYFORMS_PATH ), // No un-minimized version of this script included.
					'version'   => $this->_version,
					'deps'      => array(),
					'in_footer' => true,
					'enqueue'   => array(
						array( 'field_types' => array( 'pdf_viewer' ) ),
					),
					'strings'   => array(
						'url_worker'        => plugins_url( 'js/pdfjs/pdf.worker.min.js', EMBED_PDF_GRAVITYFORMS_PATH ), // No unminimized version of this script included.
						'initial_scale'     => self::DEFAULT_SCALE_VALUE,
						'is_user_logged_in' => is_user_logged_in(),
					),
				),
				array(
					'handle'  => 'epdf_gf_pdf_viewer',
					'src'     => plugins_url( "js/field-pdf-viewer{$min}.js", EMBED_PDF_GRAVITYFORMS_PATH ),
					'version' => $this->_version,
					'deps'    => array( 'wp-i18n' ),
					'strings' => array(
						'site_url' => site_url(),
					),
					'enqueue' => array(
						array( 'field_types' => array( 'pdf_viewer' ) ),
					),
				),
			);
			return array_merge( parent::scripts(), $scripts );
		}

		/**
		 * Return the styles which should be enqueued.
		 *
		 * @return array
		 */
		public function styles() {
			$min    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			$styles = array(
				// Front-end.
				array(
					'handle'  => 'embed-pdf-gravityforms-field',
					'src'     => plugins_url( "css/viewer{$min}.css", EMBED_PDF_GRAVITYFORMS_PATH ),
					'version' => $this->_version,
					'enqueue' => array(
						array( 'field_types' => array( 'pdf_viewer' ) ),
					),
				),
				// Form editor.
				array(
					'handle'  => 'embed-pdf-gravityforms-editor',
					'src'     => plugins_url( "css/editor{$min}.css", EMBED_PDF_GRAVITYFORMS_PATH ),
					'version' => $this->_version,
					'enqueue' => array(
						array( 'query' => 'page=gf_edit_forms&id=_notempty_' ),
					),
				),
			);
			return array_merge( parent::styles(), $styles );
		}
	}
}
