<?php
/**
 * Course Wizard for Sensei Page
 *
 * @package Course Wizard for Sensei
 */

defined( 'ABSPATH' ) || exit;

/**
 * Course Wizard for Sensei Page class
 */
class Course_Wizard_For_Sensei_Page {

	/**
	 * The screen object.
	 *
	 * @var    object
	 * @access public
	 * @since  1.0.0
	 */
	public $screen;


	public $screen_object = 'Course_Wizard_For_Sensei_Screen_Question';


	public $title;


	/**
	 * The requested vars.
	 *
	 * @var    array
	 * @access protected
	 * @since  1.0.0
	 */
	protected $requested_vars = array();


	/**
	 * Constructor function.
	 *
	 * @access  public
	 * @since   1.0.0
	 *
	 * @link https://developer.wordpress.org/reference/functions/add_submenu_page/
	 *
	 * @return  void
	 */
	public function __construct() {

		if ( ! is_admin() ) {

			if ( ! empty( $_GET['cws-is-preview'] ) ) {

				// Is front course page preview.
				$this->hide_admin_menu_bar();
			}

			return;
		}

		// Translators: %s is Course.
		$this->title = sprintf( __( '%s Wizard', 'course-wizard-for-sensei' ), __( 'Course', 'woothemes-sensei' ) );

		add_action( 'admin_menu', array( $this, 'register_submenu_page' ) );

		if ( ( empty( $_GET['page'] ) ||
				'course-wizard' !== $_GET['page'] ) &&
			( empty( $_POST['action'] ) ||
				'cws_ajax_form' !== $_POST['action'] ) ) {

			// Not on course Wizard page, neither is AJAX call.
			return;
		}

		add_action( 'wp_ajax_cws_ajax_form', array( $this, 'ajax_form' ) );

		add_filter( 'sensei_scripts_allowed_pages', array( $this, 'sensei_allow_course_wizard_page' ) );
		add_filter(
			'sensei_module_admin_script_page_white_lists',
			array( $this, 'sensei_allow_course_wizard_page' )
		);

		add_action( 'plugins_loaded', array( $this, 'enqueue_scripts' ) );

		add_action( 'plugins_loaded', array( $this, 'set_requested_vars' ) );

		add_action( 'plugins_loaded', array( $this, 'cws_page_loaded' ) );
	}


	/**
	 * Do action hook cws_page_loaded on plugins_loaded
	 *
	 * So other plugins can load their classes (custom screens and others) after CWS.
	 */
	public function cws_page_loaded() {

		do_action( 'cws_page_loaded', array( &$this ) );
	}


	/**
	 * Get screen dynamic class.
	 *
	 * @param string $class_name Class name.
	 *
	 * @return string Last screen object.
	 */
	public function set_screen_dynamic_class( $class_name ) {

		$dynamic_class = $this->screen_object;

		// https://stackoverflow.com/questions/1539530/is-it-possible-to-extend-a-class-dynamically#16773369
		class_alias( $dynamic_class, $class_name . '_Dynamic', false );

		$this->screen_object = $class_name;

		return;
	}


	/**
	 * Get current screen HTML
	 *
	 * @access  public
	 * @since   1.0.0
	 *
	 * @uses Course_Wizard_For_Sensei_Screen::get_screen()
	 *
	 * @return string
	 */
	public function get_screen() {

		if ( $this->screen ) {

			return $this->screen->get_screen_html();
		}

		$this->screen = new $this->screen_object(
			$this->requested_vars
		);

		return $this->screen->get_screen_html();
	}


	/**
	 * Set Requested variables
	 * $_GET['cws_*'] passed in URL.
	 * Sanitize them.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function set_requested_vars() {

		$type = INPUT_GET;

		if ( ! empty( $_POST['action'] ) &&
			'cws_ajax_form' === $_POST['action'] ) {

			$type = INPUT_POST;
		}

		$requested_vars = array();

		$requested_vars['screen'] = (string) filter_input( $type, 'cws_screen', FILTER_SANITIZE_STRING );
		$requested_vars['action'] = (string) filter_input(
			( isset( $_GET['cws_action'] ) ? INPUT_GET : INPUT_POST ),
			'cws_action',
			FILTER_SANITIZE_STRING
		);
		$requested_vars['course'] = (int) filter_input( $type, 'cws_course', FILTER_VALIDATE_INT );
		$requested_vars['module'] = (int) filter_input( $type, 'cws_module', FILTER_VALIDATE_INT );
		$requested_vars['lesson'] = (int) filter_input( $type, 'cws_lesson', FILTER_VALIDATE_INT );
		$requested_vars['question'] = (int) filter_input( $type, 'cws_question', FILTER_VALIDATE_INT );

		$requested_vars = apply_filters( 'cws_page_set_requested_vars', $requested_vars );

		$this->requested_vars = $requested_vars;
	}


	/**
	 * Register the submenu page.
	 * Under Sensei Course menu, if user can manage Sensei.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function register_submenu_page() {

		// Add submenu page.
		add_submenu_page(
			'edit.php?post_type=course',
			$this->title,
			$this->title,
			'manage_sensei_grades',
			'course-wizard',
			array( $this, 'page_output' )
		);
	}


	/**
	 * Output the page HTML.
	 *
	 * @uses WordPress Customizer classes.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function page_output() {

		?>
		<div class="cws-wizard">
			<div class="wp-full-overlay expanded">
				<div class="wrap wp-full-overlay-sidebar cws-wrap-screen">
					<h1>
						<a href="<?php echo esc_url( admin_url() ); ?>" class="cws-button button cws-close-button"
							title="<?php esc_html_e( 'Close' ); ?>">
							<i class="dashicons dashicons-no-alt"></i>
						</a>
						<!--<span class="cws-dashicon dashicons dashicons-welcome-learn-more"></span>-->
						<?php echo $this->title; ?>
						<a href="#"
						   class="cws-button button cws-link cws-preview-button"
						   title="<?php esc_attr_e( 'Preview' ); ?>">
							<i class="dashicons dashicons-visibility"></i>
						</a>
						<div class="spinner"></div>
					</h1>
					<div id="cws-wizard-screen-transition"
						 class="wrap wp-full-overlay-sidebar-content" tabindex="-1">
					</div>
					<div id="cws-wizard-screen"
						 class="wrap wp-full-overlay-sidebar-content" tabindex="-1">
						<?php echo $this->get_screen(); ?>
					</div>
				</div>
				<?php

				$this->preview_output();
				?>
			</div>
		</div>
		<?php
	}


	/**
	 * Output the Preview HTML
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function preview_output() {

		$preview_url = $this->screen->get_preview_url();
		?>
		<div id="cws-preview" class="wp-full-overlay-main iframe-ready">
			<iframe id="cws-preview-iframe" name="cws-preview-iframe" src="<?php echo esc_url( $preview_url ); ?>"></iframe>
		</div>
		<?php
	}


	/**
	 * Reload preview iframe.
	 *
	 * @param string $preview_url Preview URL.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function preview_reload( $preview_url ) {

		if ( ! $preview_url ) {

			return;
		}

		?>
		<script>
			cwsPreviewUrl = <?php echo json_encode( $preview_url ); ?>;
		</script>
		<?php
	}


	/**
	 * Output the AJAX page HTML.
	 *
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function ajax_page_output() {

		echo $this->get_screen();

		// Moved to JS, so it is compatible with AJAX silent.
		if ( ! isset( $_REQUEST['cws_ajax_silent'] ) ) {
			$this->preview_reload( $this->screen->get_preview_url( '' ) );
		}
	}


	/**
	 * Allow Course Wizard page
	 * Will let us enqueue Sensei admin scripts if needed.
	 *
	 * @param array $allowed_pages Allowed pages.
	 *
	 * @return array
	 */
	public function sensei_allow_course_wizard_page( $allowed_pages ) {

		$allowed_pages[] = 'course-wizard';

		$allowed_pages[] = 'course_page_course-wizard';

		return $allowed_pages;
	}

	/**
	 * Hide WordPress admin menu and bar
	 */
	public function hide_admin_menu_bar() {

		add_filter( 'show_admin_bar', '__return_false', 99 );

		add_action( 'admin_menu', array( 'Course_Wizard_For_Sensei_Page', 'empty_admin_menu' ), '9999' );
	}


	/**
	 * Empty admin menu to load faster.
	 */
	static function empty_admin_menu() {

		global $menu;

		$menu = array();
	}


	/**
	 * @link https://codex.wordpress.org/AJAX_in_Plugins
	 */
	public function ajax_form() {
		global $wpdb; // This is how you get access to the database.

		if ( ! empty( $_REQUEST['cws_ajax_silent'] ) ) {

			ob_start();
		}

		$this->ajax_page_output();

		if ( ! empty( $_REQUEST['cws_ajax_silent'] ) ) {

			ob_clean();
		}

		wp_die(); // This is required to terminate immediately and return a proper response.
	}


	/**
	 * Load all our scripts once, not on AJAX.
	 */
	public function enqueue_scripts() {

		if ( ! empty( $_POST['action'] ) &&
			'cws_ajax_form' === $_POST['action'] ) {

			return;
		}

		add_action( 'admin_enqueue_scripts', array( Sensei()->modules, 'admin_enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( Sensei()->modules, 'admin_enqueue_scripts' ),  20 , 2 );

		add_action( 'admin_enqueue_scripts', array( Sensei()->lesson, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( Sensei()->lesson, 'enqueue_styles' ) );
	}
}
