<?php
/**
 * Course Wizard for Sensei Screen
 *
 * @package Course Wizard for Sensei
 */

defined( 'ABSPATH' ) || exit;

/**
 * Course Wizard for Sensei Screen class
 */
class Course_Wizard_For_Sensei_Screen extends Course_Wizard_For_Sensei_Form {
	/**
	 * The screen $_GET variables.
	 *
	 * @var    array
	 * @access public
	 * @since  1.0.0
	 */
	public $screen_get_vars = array();

	/**
	 * The screen.
	 *
	 * @var    string
	 * @access public
	 * @since  1.0.0
	 */
	public $screen = '';

	/**
	 * The action to perform.
	 *
	 * @var    string
	 * @access public
	 * @since  1.0.0
	 */
	public $action = '';

	/**
	 * The screen title.
	 *
	 * @var    string
	 * @access public
	 * @since  1.0.0
	 */
	public $screen_title = '';

	/**
	 * Screens order.
	 * Key is screen name.
	 * Value is next screen name.
	 *
	 * @example array( 'course_title' => 'course_description' )
	 *
	 * @var    array()
	 * @access public
	 * @since  1.0.0
	 */
	public $screen_order = array();

	/**
	 * Constructor function.
	 * Sets the current screen HTML.
	 *
	 * @access  public
	 * @since   1.0.0
	 *
	 * @param array $object_params Object requested parameters.
	 *
	 * @return void
	 */
	public function __construct( $object_params = array() ) {

		$this->set_screen( $object_params['screen'] );

		$this->action = $object_params['action'];

		// var_dump( $screen, $this->screen );
	}


	/**
	 * Set Screen
	 *
	 * @param string $screen Screen (method name).
	 */
	public function set_screen( $screen = '' ) {

		if ( ! $screen ) {
			return;
		}

		if ( method_exists( $this, $screen ) ) {

			$this->screen = $screen;

			$this->screen_get_vars['cws_screen'] = $screen;
		}
	}


	/**
	 * Get Screen URL
	 *
	 * @param array $get_vars $_GET variables.
	 *
	 * @return string Screen URL.
	 */
	protected function get_screen_url( $get_vars = array() ) {

		// The Course Wizard URL.
		$screen_url = 'edit.php?post_type=course&page=course-wizard';

		// $_GET vars passed to function + screen $_GET vars.
		$get_vars = array_merge( $this->screen_get_vars, (array) $get_vars );

		// Add $_GET variables.
		foreach ( $get_vars as $key => $value ) {
			if ( ! $key ) {

				continue;
			}

			$screen_url .= '&' . $key . '=' . $value;
		}

		$screen_url = admin_url( $screen_url );

		return apply_filters( 'cws_screen_get_screen_url', $screen_url );
	}


	/**
	 * Set Screens Order
	 *
	 * @param array $screens Screens.
	 */
	public function add_screen_order( $screens = array() ) {

		if ( ! $screens ||
			! is_array( $screens ) ) {
			return;
		}

		$add_screens = array();

		foreach ( $screens as $screen => $next_screen ) {

			if ( ! method_exists( $this, $screen ) ||
				! method_exists( $this, $next_screen ) ) {

				// (Next) Screen method does not exist.
				continue;
			}

			$add_screens[ $screen ] = $next_screen;
		}

		$this->screen_order = array_merge( $this->screen_order, $add_screens );
	}


	/**
	 * Get Next Screen
	 *
	 * @param array $screen Screen name.
	 *
	 * @return string Next screen name.
	 */
	public function get_next_screen( $screen = '' ) {

		if ( ! $screen ) {
			$screen = $this->screen;
		}

		if ( ! isset( $this->screen_order[ $screen ] ) ) {

			$next_screen = '';
		} else {

			$next_screen = $this->screen_order[ $screen ];
		}

		return apply_filters( 'cws_screen_get_next_screen', $next_screen );
	}


	/**
	 * Get Previous Screen
	 *
	 * @param string $screen Screen name.
	 *
	 * @return string Previous screen name.
	 */
	public function get_previous_screen( $screen = '' ) {

		if ( ! $screen ) {
			$screen = $this->screen;
		}

		if ( ! isset( $this->screen_order[ $screen ] ) ) {

			return '';
		}

		return (string) array_search( $screen, $this->screen_order );
	}


	/**
	 * Get Next Screen URL
	 *
	 * @uses $this->get_screen_url()
	 * @uses $this->get_next_screen()
	 *
	 * @param array $get_vars $_GET variables.
	 *
	 * @return string Next Screen URL.
	 */
	protected function get_next_screen_url( $get_vars = array() ) {

		$next_screen = $this->get_next_screen();

		$get_vars = array_merge( $get_vars, array( 'cws_screen' => $next_screen ) );

		$next_screen_url = $this->get_screen_url( $get_vars );

		return $next_screen_url;
	}


	/**
	 * Redirect URL
	 * Will update the requested URL in the browser,
	 * (soft redirection using the X-Redirect-Url header)
	 * removing the requested parameters passed as argument.
	 * Use after a successful remove / delete / update / save operation.
	 * Prevents showing an obsolete & confusing delete confirmation screen on page reload.
	 */
	protected function ajax_redirect_url( $url = '' ) {

		if ( ! $url ) {
			$url = $this->get_screen_url();
		}

		header( 'X-Redirect-Url: ' . $url );
	}


	protected function next_screen_redirect() {

		$this->set_screen( $this->get_next_screen() );

		$this->ajax_redirect_url();
	}




	/**
	 * Get screen HTML.
	 *
	 * @access  public
	 * @since   1.0.0
	 *
	 * @param string $screen Screen name.
	 *
	 * @return string
	 */
	public function get_screen_html( $screen = '' ) {

		if ( ! $screen ) {

			$screen = $this->screen;
		}

		$screen_content_html = $this->get_screen_content_html( $screen );

		ob_start();

		$this->screen_title_html();

		echo $screen_content_html;

		// Close any open form.
		$this->form( false );

		$screen_content_ajax = ob_get_clean();

		if ( ! empty( $_POST['action'] ) &&
			'cws_ajax_form' === $_POST['action'] ) {

			return $screen_content_ajax;
		}

		ob_start(); ?>
		<div class="cws-wizard-screen-content">
			<div class="cws-wizard-screen-ajax-content">
			<?php

			echo $screen_content_ajax;

			?>
			</div>
			<div class="cws-editor-hidden">
				<?php
				// @link https://wordpress.stackexchange.com/questions/51776/how-to-load-wp-editor-through-ajax-jquery/192132
				wp_editor( '', 'cws-editor-id', array(
					'editor_class' => 'cws-editor',
					'textarea_name' => 'cws-editor-textarea',
				) );
				?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}


	/**
	 * Get preview URL.
	 *
	 * @access  protected
	 * @since   1.0.0
	 *
	 * @param string $url URL.
	 *
	 * @return string Validated preview URL with cws-is-preview $_GET param.
	 */
	protected function get_preview_url( $url ) {

		if ( empty( $url ) ) {

			return '';
		}

		$url .= strpos( $url, '?' ) ? '&' : '?';

		// Add cws-is-preview $_GET param.
		$url .= 'cws-is-preview=1';

		return (string) wp_http_validate_url( $url );
	}


	/**
	 * Get screen content HTML.
	 *
	 * @access  public
	 * @since   1.0.0
	 *
	 * @param string $screen Screen name.
	 *
	 * @return string Empty if screen method not found, else Screen content HTML.
	 */
	public function get_screen_content_html( $screen = '' ) {

		if ( ! $screen ) {

			$screen = $this->screen;
		}

		if ( ! method_exists( $this, $screen ) ) {

			return '';
		}

		ob_start();

		$this->{$screen}();

		$screen_content_html = ob_get_clean();

		return apply_filters( 'cws_screen_get_screen_content_html', $screen_content_html, $screen );
	}


	/**
	 * Screen Title HTML
	 * Screen title can either contain <h2> tag or not (default).
	 * This potentially lets you add HTML before / after the h2 tag.
	 *
	 * @param string $title Title.
	 */
	protected function screen_title_html( $title = '' ) {

		if ( ! $title ) {

			$title = $this->screen_title;
		}

		if ( strpos( $title, '<h2' ) === false ) {
			// Wrap h2 around title.
			?>
			<h2><?php echo wp_kses_post( $title ); ?></h2>
			<?php
		} else {
			// Title contains h2 tag, display as is.
			echo $title;
		}
	}


	/**
	 * Do Screen action
	 * Call the right Object do_action_{object_name} method
	 *
	 * @param string $object_name Object (course, module, lesson...) name.
	 * @param int    $object_id   Object (course, module, lesson...) ID.
	 *
	 * @return bool True if action performed and redirect to next screen, else false.
	 */
	protected function do_action( $object_name, $object_id ) {

		if ( ! $this->action ||
			0 !== strpos( $this->screen, $object_name ) ||
			! method_exists( $this, 'do_action_' . $object_name ) ) {

			return false;
		}

		// Check nonce.
		if ( isset( $_POST['cws_nonce'] )
			&& ! wp_verify_nonce( $_POST['cws_nonce'], 'cws_form_action' ) ) {

			return false;
		}

		$action_result = $this->{'do_action_' . $object_name}( $object_id );

		$action_hook_params = array(
			'object_name' => $object_name,
			'object_id' => $object_id,
			'action' => $this->action,
			'screen' => $this->screen,
		);

		// Apply your actions to existing ones using this filter.
		$action_result = apply_filters( 'cws_screen_do_action', $action_result, $action_hook_params );

		if ( $action_result ) {

			$this->next_screen_redirect();

			// Unset action.
			$this->action = '';

			return true;
		}

		// Unset action.
		$this->action = '';

		return false;
	}


	/**
	 * Unset Object ID and screen $_GET var.
	 *
	 * @param string $object_name Object name.
	 */
	protected function unset_object( $object_name ) {

		if ( ! property_exists( $this, $object_name ) ) {

			// Object not found.
			return;
		}

		$this->{$object_name} = 0;

		if ( isset( $this->screen_get_vars['cws_' . $object_name ] ) ) {

			unset( $this->screen_get_vars['cws_' . $object_name ] );
		}
	}
}
