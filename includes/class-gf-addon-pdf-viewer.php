<?php
defined( 'ABSPATH' ) || exit;

if ( method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
	GFForms::include_feed_addon_framework();
}

if ( class_exists( 'GFFeedAddOn' ) ) {
	class GF_Addon_PDF_Viewer extends GFAddOn {

		const DEFAULT_SCALE_VALUE = '1.5';

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
 
		public static function get_instance() {
			if ( self::$_instance == null ) {
				self::$_instance = new self();
			}
		 
			return self::$_instance;
		}

		public function pre_init() {
			parent::pre_init();
		 
			// Include the class that defines our PDF Viewer field.
			if ( $this->is_gravityforms_supported() && class_exists( 'GF_Field' ) ) {
				require_once dirname( EMBED_PDF_GRAVITYFORMS_PATH ) . '/includes/class-gf-field-pdf-viewer.php';
			}
		}

		/**
		 * Handles hooks and loading of language files.
		 */
		public function init() {

		}

		public function init_admin() {
			parent::init_admin();
		 
			//add_filter( 'gform_tooltips', array( $this, 'tooltips' ) );
			add_action( 'gform_field_standard_settings', array( $this, 'add_field_settings' ), 10, 2 );
		}

		public function add_field_settings( $level, $form_id ) {
			if ( 20 !== $level ?? 0 ) {
				return;
			}
		
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
			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			$scripts = array(
				array(
					'handle'  => 'pdf-viewer',
					'src'     => plugins_url( "js/field-pdf-viewer{$min}.js", EMBED_PDF_GRAVITYFORMS_PATH ),
					'version' => $this->_version,
					'deps'    => array(),
					// 'strings' => array(
					// 	'form_id'        => ( ! empty( $_GET['id'] ) ? $_GET['id'] : null ),
					// 	'ajax_url'       => admin_url( 'admin-ajax.php' ),
					// 	'feed_id'        => $this->get_current_feed_id(),
					// 	'nonce'          => wp_create_nonce( self::NONCE_AJAX ),
					// 	'test_failed'    => __( 'Insert Test Row failed', 'gravityforms-quote-tracker' ),
					// 	'test_succeeded' => __( 'Insert Test Row succeeded', 'gravityforms-quote-tracker' ),
					// ),
					'enqueue' => array(
						array( 'field_types' => array( 'pdf_viewer' ) ),
					),
				),
			);
			return array_merge( parent::scripts(), $scripts );
	   }

		// public function tooltips( $tooltips ) {
		// 	$simple_tooltips = array(
		// 		'input_class_setting' => sprintf( '<h6>%s</h6>%s', esc_html__( 'Input CSS Classes', 'simplefieldaddon' ), esc_html__( 'The CSS Class names to be added to the field input.', 'simplefieldaddon' ) ),
		// 	);
		 
		// 	return array_merge( $tooltips, $simple_tooltips );
		// }
	}
}