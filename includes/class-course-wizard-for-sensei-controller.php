<?php
/**
 * Course Wizard for Sensei Controller
 * Where we really
 * - do the installation (check Sensei plugin is activated)
 * - Load our Wizard page.
 *
 * @package Course Wizard for Sensei
 */

defined( 'ABSPATH' ) || exit;

/**
 * Course Wizard for Sensei Controller class
 */
class Course_Wizard_For_Sensei_Controller extends Course_Wizard_For_Sensei {

	/**
	 * The single instance of Course_Wizard_For_Sensei_Controller.
	 *
	 * @var    object
	 * @access private
	 * @since  1.0.0
	 */
	private static $_instance = null;

	/**
	 * Constructor function.
	 *
	 * @access  public
	 * @since   1.0.0
	 *
	 * @param string $file    File pathname.
	 * @param string $version Version number.
	 * @return  void
	 */
	public function __construct( $file = '', $version = '1.0.0' ) {
		parent::__construct( $file, $version );

		register_activation_hook( $this->file, array( $this, 'installation' ) );

		// @link https://codex.wordpress.org/Function_Reference/register_activation_hook#Process_Flow
		if ( is_admin() ) {
			if ( get_option( 'CWS_Sensei_Plugin_Not_Activated' ) ) {

				delete_option( 'CWS_Sensei_Plugin_Not_Activated' );

				// Display warning to user: should activate Sensei plugin.
				add_action( 'admin_notices', array( $this, 'no_sensei_admin_notice__warning' ) );
			}
		}

		// Sensei plugin is activated, load our Course Wizard page.
		$this->page = new Course_Wizard_For_Sensei_Page();
	}


	/**
	 * Main Course_Wizard_For_Sensei_Controller Instance
	 *
	 * Ensures only one instance of Course_Wizard_For_Sensei_Controller is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Course_Wizard_For_Sensei()
	 *
	 * @param string $file    File pathname.
	 * @param string $version Version number.
	 * @return Course_Wizard_For_Sensei_Controller instance
	 */
	public static function instance( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	}


	/**
	 * Installation. Runs on activation.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  bool false if Sensei plugin not activated.
	 */
	public function installation() {

		if ( ! $this->is_sensei_plugin_active() ) {
			// https://codex.wordpress.org/Function_Reference/register_activation_hook#Process_Flow
			add_option( 'CWS_Sensei_Plugin_Not_Activated', true );

			return false;
		}

		return true;
	}


	/**
	 * Is Sensei plugin active?
	 *
	 * @return bool
	 */
	public function is_sensei_plugin_active() {
		static $active = null;

		if ( ! is_null( $active ) ) {

			return $active;
		}

		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

		$active = in_array( 'woothemes-sensei/woothemes-sensei.php', $active_plugins ) ||
			in_array( 'sensei/woothemes-sensei.php', $active_plugins );

		return $active;
	}


	/**
	 * Display warning to user: should activate Sensei plugin.
	 *
	 * @access  public
	 * @since 1.0.0
	 */
	public function no_sensei_admin_notice__warning() {
		?>
		<div class="notice notice-warning">
			<p><strong><?php esc_html_e( 'Course Wizard for Sensei', 'course-wizard-for-sensei' ); ?></strong>:
			<?php
			esc_html_e(
				'Please activate the Sensei plugin first and then reactivate the plugin.',
				'course-wizard-for-sensei'
			);
			?>
			</p>
		</div>
		<?php
	}
}
