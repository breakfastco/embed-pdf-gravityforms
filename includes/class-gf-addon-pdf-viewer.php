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
		const FIELD_TYPE          = 'pdf_viewer';

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

			/**
			 * Users with custom capabilities, like a Subscriber with all
			 * Gravity Forms capabilities but not upload_files, will not be able
			 * to use the Choose PDF button in the form editor. The media
			 * library modal is not available unless wp_enqueue_media() is run.
			 */
			add_action( 'admin_enqueue_scripts', 'wp_enqueue_media' );

			// AJAX handler for the Download PDF into Media Library button.
			add_action( 'wp_ajax_download_pdf_media', array( $this, 'ajax_handler_download_pdf_media' ) );
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
		 * AJAX handler for the Download PDF into Media Library button.
		 *
		 * @return void
		 */
		public function ajax_handler_download_pdf_media() {
			check_ajax_referer( 'epdf_gf_download_pdf_media' );

			if ( empty( $_POST['url'] ) ) {
				wp_send_json_error();
			}

			$url = sanitize_url( wp_unslash( $_POST['url'] ) );

			// Download the file.
			$tmp_file = download_url( $url );
			if ( is_wp_error( $tmp_file ) ) {
				wp_send_json_error(
					array(
						/* translators: 1. An error message. */
						'msg' => sprintf( __( 'The download failed with error "%s"', 'embed-pdf-gravityforms' ), $tmp_file->get_error_message() ),
					)
				);
			}
			// Move from a temp file to the uploads directory.
			$upload_dir = wp_upload_dir();
			$file_name  = wp_unique_filename( $upload_dir['path'], basename( $url ) );
			$path       = $upload_dir['path'] . DIRECTORY_SEPARATOR . $file_name;
			global $wp_filesystem;
			if ( ! class_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
			}
			WP_Filesystem();
			$wp_filesystem->move( $tmp_file, $path );
			// Add to the database.
			$media_id = wp_insert_attachment(
				array(
					'post_author'    => wp_get_current_user()->ID,
					'post_title'     => $file_name,
					'post_status'    => 'publish',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
					'meta_input'     => array(
						/* translators: 1. An external URL. */
						'_source' => sprintf( __( 'Downloaded from %s by Embed PDF for Gravity Forms', 'embed-pdf-gravityforms' ), $url ),
					),
				),
				$path
			);
			wp_update_attachment_metadata( $media_id, wp_generate_attachment_metadata( $media_id, $path ) );
			wp_send_json_success(
				array(
					'url' => wp_get_attachment_url( $media_id ),
				)
			);
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
						array( 'field_types' => array( self::FIELD_TYPE ) ),
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
					'deps'    => array( 'epdf_gf_pdfjs', 'wp-i18n' ),
					'strings' => array(
						'field_type'   => self::FIELD_TYPE,
						'script_debug' => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
					),
					'enqueue' => array(
						array(
							'field_types' => array( self::FIELD_TYPE ),
						),
					),
				),
				array(
					'handle'  => 'epdf_gf_form_editor',
					'src'     => plugins_url( "js/form-editor{$min}.js", EMBED_PDF_GRAVITYFORMS_PATH ),
					'version' => $this->_version,
					'deps'    => array( 'jquery' ),
					'strings' => array(
						'field_type'       => self::FIELD_TYPE,
						'site_url'         => site_url(),
						'can_upload_files' => current_user_can( 'upload_files' ),
						'ajax_url'         => admin_url( 'admin-ajax.php' ),
						'nonce'            => wp_create_nonce( 'epdf_gf_download_pdf_media' ),
					),
					'enqueue' => array(
						array(
							'admin' => 'form_editor',
						),
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
						array( 'field_types' => array( self::FIELD_TYPE ) ),
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
