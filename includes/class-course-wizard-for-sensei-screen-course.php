<?php
/**
 * Course Wizard for Sensei Screen Course
 *
 * @package Course Wizard for Sensei
 */

defined( 'ABSPATH' ) || exit;

/**
 * Course Wizard for Sensei Screen Course class
 */
class Course_Wizard_For_Sensei_Screen_Course extends Course_Wizard_For_Sensei_Screen {
	/**
	 * The course.
	 *
	 * @var    integer
	 * @access public
	 * @since  1.0.0
	 */
	public $course = 0;

	/**
	 * Duplicate object.
	 *
	 * @var    object
	 * @access public
	 * @since  1.0.0
	 */
	public $duplicate = 0;

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

		// Default screen.
		if ( ! $object_params['screen'] ) {

			$object_params['screen'] = 'course_landing';
		}

		// Construct screen.
		parent::__construct( $object_params );

		$this->duplicate = new Course_Wizard_For_Sensei_Duplicate();

		$screen_order = array(
			'course_landing' => 'course_title',
			'course_title' => 'course_description',
			'course_description' => 'module_landing',
		);

		$this->add_screen_order( $screen_order );

		$this->set_course( $object_params['course'] );

		$this->do_action( 'course', $object_params['course'] );

		//add_filter( 'cws_screen_do_action', 'no_ajax_redirect_to_description_fix', 90 );
	}


	/*public function no_ajax_redirect_to_description_fix( $action_result ) {

		if ( ! $action_result ) {

			return $action_result;
		}

		$next_screen = $this->get_next_screen();

		if ( 'course_description' === $next_screen ||
			'lesson_description' === $next_screen ||
			'session_description' === $next_screen ) {

			// Next screen HAS WordPress editor.
			// WordPress editor JS fix: no AJAX redirection.
			$this->set_screen( $next_screen );

			// Directly redirect to next screen, no AJAX.
			wp_redirect( $this->get_screen_url() );

			exit;
		}
	}*/



	/**
	 * Set Course
	 *
	 * @param int $course Course ID.
	 */
	public function set_course( $course = 0 ) {

		if ( ! $course ) {
			return;
		}

		// Check course exists.
		if ( ! get_post_status( $course ) ) {
			return;
		}

		$this->course = $course;

		$this->screen_get_vars['cws_course'] = $course;
	}


	/**
	 * Do Course action
	 *
	 * @since 1.6.0 New Course now has "Draft" status.
	 * @since 1.6.1 Publish Course action.
	 * @since 1.7.0 Save Course Teacher.
	 *
	 * @param int $course Course ID.
	 *
	 * @return bool True if action performed, else false.
	 */
	public function do_action_course( $course ) {

		if ( ! $this->action ) {

			return false;
		}

		// Course title:
		if ( 'course_title' === $this->screen ) {

			if ( ! isset( $_POST['post_title'] ) ) {
				return false;
			}

			if ( 'duplicate' === $this->action ) {

				$course = $this->duplicate->duplicate_course( $course, $_POST['post_title'], $_POST['cws-term-duplicate'] );
			}

			if ( 'new' === $this->action ) {
				// Save course title.
				$course = wp_insert_post( array(
					'ID' => $course,
					'post_title' => $_POST['post_title'],
					'post_status' => 'draft',
					'post_type' => 'course',
				) );
			} else {
				// Save course title.
				$course = wp_update_post( array(
					'ID' => $course,
					'post_title' => $_POST['post_title'],
				) );
			}

			if ( isset( $_POST['_thumbnail_id'] ) ) {
				// Save Post thumbnail.
				$thumbnail_id = ( intval( $_POST['_thumbnail_id'] ) <= 0 ) ? '-1' : intval( $_POST['_thumbnail_id'] );

				set_post_thumbnail( $course, $thumbnail_id );
			}

			if ( isset( $_POST['sensei-course-teacher-author'] ) ) {
				// Save Course Teacher.
				$_POST['post_ID'] = $course;

				Sensei()->teacher->save_teacher_meta_box( $course );
			}

			if ( ! $course ) {
				$this->action_error( __( 'Course', 'woothemes-sensei' ) );

				return false;
			}

			$this->set_course( $course );

			return true;
		}

		// Course description:
		if ( 'course_description' === $this->screen ) {

			if ( 'save' !== $this->action ||
				! isset( $_POST['cws-editor'] ) ) {
				return false;
			}

			// Save course description.
			$course = wp_update_post( array(
				'ID' => $course,
				'post_content' => $_POST['cws-editor'],
			) );

			if ( ! $course ) {

				$this->action_error( __( 'Course', 'woothemes-sensei' ) );

				return false;
			}

			return true;
		}

		return false;
	}


	/**
	 * Get Course Post
	 *
	 * @param int $course Course ID.
	 *
	 * @return object WP_Post
	 */
	public function get_course_post( $course = 0 ) {

		static $course_post;

		if ( ! $course ) {
			$course = $this->course;
		}

		if ( $course_post &&
			$course === $course_post->ID &&
			! $this->action ) {

			return $course_post;
		}

		$course_post = get_post( $course, OBJECT, 'edit' );

		return $course_post;
	}


	/**
	 * Get Sensei Courses
	 * formatted for the Course select input.
	 *
	 * @return array Sensei courses.
	 */
	public function get_courses() {
		// Get existing courses.
		$args = array(
			'post_type' => 'course',
			'post_status' => array( 'publish', 'draft', 'future', 'private' ),
			'posts_per_page' => -1,
			'orderby' => 'post_title',
			'order' => 'ASC',
		);

		$courses = get_posts( $args );

		// Build the courses array.
		$select_courses = array();

		if ( isset( $courses ) &&
			is_array( $courses ) ) {
			foreach ( $courses as $course ) {
				$select_courses[] = array(
					'id' => $course->ID,
					'title' => $course->post_title,
				);
			}
		}

		return $select_courses;
	}


	/**
	 * Get preview URL.
	 *
	 * @access  public
	 * @since   1.0.0
	 *
	 * @param string $url URL.
	 *
	 * @return string
	 */
	public function get_preview_url( $url = '' ) {

		if ( ! $url &&
			$this->course ) {

			$url = get_permalink( $this->course );
		}

		return parent::get_preview_url( $url );
	}


	/**
	 * Course Title screen output.
	 *
	 * @access  protected
	 * @since   1.0.0
	 */
	protected function course_title() {

		$next_action = ! isset( $_REQUEST['cws_next_action'] ) ? 'edit' : $_REQUEST['cws_next_action'];

		if ( ! $next_action ||
			'new' !== $next_action &&
			! $this->course ) {

			$this->not_found_error( __( 'Course', 'woothemes-sensei' ) );

			return;
		}

		if ( 'new' === $next_action ) {

			$value = '';
		} elseif ( 'edit' === $next_action ) {

			$value = $this->get_course_post()->post_title;

		} elseif ( 'duplicate' === $next_action ) {

			$value = $this->get_course_post()->post_title . '(' . __( 'Duplicate', 'woothemes-sensei' ) . ')';
		}

		$this->screen_title = $this->action_title( __( 'Course', 'woothemes-sensei' ), $next_action );

		$this->form( array(
			'cws_action' => $next_action,
		) );

		$this->title( __( 'Course', 'woothemes-sensei' ), $value );

		if ( 'duplicate' === $next_action ) {

			$this->checkbox(
				'cws-term-duplicate',
				__( 'Duplicate lessons', 'course-wizard-for-sensei' ),
				__( 'Duplicate this course with its lessons', 'woothemes-sensei' )
			);
		}

		$this->featured_image( $this->course, 'course' );

		$this->course_teacher( $this->get_course_post() );

		$this->previous_button();

		$this->next_button();
	}


	/**
	 * Course Description screen output.
	 *
	 * @access  protected
	 * @since   1.0.0
	 */
	protected function course_description() {

		if ( ! $this->course ) {

			$this->not_found_error( __( 'Course', 'woothemes-sensei' ) );

			return;
		}

		$post = $this->get_course_post();

		$screen_title = $post->post_title;

		$this->screen_title = $screen_title;

		$this->form( array(
			'cws_action' => 'save',
		) );

		$this->description( $post );

		$this->previous_button();

		$this->next_button();
	}


	/**
	 * Course Landing screen output
	 *
	 * @access  protected
	 * @since   1.0.0
	 *
	 * @uses $this->course_add()
	 */
	protected function course_landing() {

		ob_start();
		?>
		<span class="cws-dashicon dashicons dashicons-book"></span>
		<?php
		_e( 'Courses', 'woothemes-sensei' );
		$this->add_new_button( $this->get_next_screen_url( array(
			'cws_next_action' => 'new',
		) ), 'cws-no-ajax' );

		$screen_title = ob_get_clean();

		$this->screen_title = $screen_title;

		$this->course_add();
	}


	/**
	 * Course Add screen output
	 *
	 * @access  protected
	 * @since   1.0.0
	 *
	 * @uses $this->course_select()
	 */
	protected function course_add() {

		ob_start();

		$this->form_next(
			array(
				'cws_next_action' => 'duplicate',
			),
			'cws-no-ajax' // No AJAX for Featured image JS to work!
		);

		$this->course_select();

		$this->submit_button(
			__( 'Duplicate', 'woothemes-sensei' ),
			'duplicate',
			'button-primary'
		);

		$module_screen = apply_filters( 'cws_screen_course_add_module_screen', 'module_landing' );

		// Course hidden field is set on submit.
		$this->link_button(
			__( 'Edit' ) . '<span class="dashicons dashicons-arrow-right-alt2"></span>',
			array(
				'cws_screen' => $module_screen,
				'cws_course' => '',
			),
			'cws-course-edit-link'
		);

		$course_add_html = ob_get_clean();

		echo apply_filters( 'cws_course_add', $course_add_html );
	}


	/**
	 * Course Select screen output.
	 *
	 * @access  protected
	 * @since   1.0.0
	 */
	protected function course_select() {

		$courses = $this->get_courses();

		$this->select(
			'cws_course',
			$courses,
			$this->course,
			'cws-is-ajax-get-var cws-select2',
			__( 'Course', 'woothemes-sensei' )
		);
	}


	/**
	 * Course Select filter output.
	 *
	 * @access  protected
	 * @since   1.0.0
	 *
	 * @param string $filter_object Filtered object name.
	 */
	protected function course_select_filter( $filter_object ) {

		$courses = $this->get_courses();

		$this->select(
			'cws_course_filter',
			$courses,
			$this->course,
			'cws-select2 cws-select-filter',
			__( 'Course', 'woothemes-sensei' )
		);


		?>
		<input type="hidden" name="cws_filter_object" id="cws_filter_object"
			   value="<?php echo esc_attr( $filter_object ); ?>" />
		<?php
	}


	/**
	 * Publish Course and any Draft lesson.
	 *
	 * @since 1.6.3
	 *
	 * @param int $course_id
	 *
	 * @return bool
	 */
	public function publish_course( $course_id ) {

		if ( ! $course_id ) {

			return false;
		}

		// @warning Do not use `wp_publish_post()` as action hooks will remove the post slug!
		$course_post = array(
			'ID'           => $course_id,
			'post_status'   => 'publish',
		);

		wp_update_post( $course_post );

		// Publish remaining Draft lessons if any.
		$args = array(
			'post_type'      => 'lesson',
			'post_status'    => array( 'draft' ),
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_lesson_course',
					'value'   => intval( $course_id ),
					'compare' => '=',
				),
			),
			'order'            => 'ASC',
			'suppress_filters' => 0,
		);

		$draft_lessons = get_posts( $args );

		if ( count( $draft_lessons ) <= 0 ) {
			return true;
		}

		foreach ( $draft_lessons as $draft_lesson ) {
			$lesson_post = array(
				'ID'           => $draft_lesson->ID,
				'post_status'   => 'publish',
			);

			wp_update_post( $lesson_post );

		}

		return true;
	}
}
