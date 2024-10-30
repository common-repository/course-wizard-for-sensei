<?php
/**
 * Course Wizard for Sensei Screen Lesson
 *
 * @package Course Wizard for Sensei
 */

defined( 'ABSPATH' ) || exit;

/**
 * Course Wizard for Sensei Screen Lesson class
 */
class Course_Wizard_For_Sensei_Screen_Lesson extends Course_Wizard_For_Sensei_Screen_Module {
	/**
	 * The lesson.
	 *
	 * @var    integer
	 * @access public
	 * @since  1.0.0
	 */
	public $lesson = 0;

	/**
	 * The quiz.
	 *
	 * @var    integer
	 * @access public
	 * @since  1.0.0
	 */
	public $quiz = 0;

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

		if ( ! empty( $_POST['cws_course_filter'] ) ) {

			if ( isset( $_POST['cws_filter_object'] ) &&
				'lesson' === $_POST['cws_filter_object'] ) {

				// Print the course lesson options and die.
				$this->course_lesson_options( $_POST['cws_course_filter'] );

				wp_die();
			}
		}

		// Construct module.
		parent::__construct( $object_params );

		$screen_order = array(
			'lesson_landing' => 'lesson_landing',
			'lesson_title' => 'lesson_description',
			'lesson_description' => 'lesson_landing',
		);

		$this->add_screen_order( $screen_order );

		$this->set_lesson( $object_params['lesson'] );

		$this->do_action( 'lesson', $object_params['lesson'] );

		$this->set_quiz();
	}


	/**
	 * Set Lesson
	 *
	 * @param int $lesson Lesson ID.
	 */
	public function set_lesson( $lesson = 0 ) {

		if ( 'lesson_landing' === $this->screen ||
			! $this->check_lesson( $lesson ) ) {
			return;
		}

		$this->lesson = $lesson;

		$this->screen_get_vars['cws_lesson'] = $lesson;
	}


	/**
	 * Check lesson
	 *
	 * @access  public
	 * @since   1.0.0
	 *
	 * @param int $lesson Lesson ID.
	 *
	 * @return bool True if lesson exists and belongs to lesson.
	 */
	public function check_lesson( $lesson ) {

		if ( ! $lesson ||
			! $this->course ) {

			return false;
		}

		// Check lesson belongs to course.
		$course = get_post_meta( $lesson, '_lesson_course', true );

		return $this->course == $course;
	}


	/**
	 * Set Quiz
	 *
	 * @uses Sensei()->lesson->lesson_quizzes()
	 */
	public function set_quiz() {

		if ( ! $this->lesson ) {
			return;
		}

		$quiz = Sensei()->lesson->lesson_quizzes( $this->lesson );

		$this->quiz = $quiz;
	}


	/**
	 * Create Quiz
	 *
	 * @param int $lesson Lesson ID, defaults to $this->lesson.
	 *
	 * @return int Quiz ID or 0 on failure.
	 */
	public function create_quiz( $lesson = 0 ) {

		if ( $this->quiz ) {

			return $this->quiz;
		}

		if ( ! $lesson ) {

			$lesson = $this->lesson;
		}

		// Create Quiz.
		// Setup Query Arguments.
		$post_type_args = array(
			'post_content' => '',
			'post_status' => get_post_status( $lesson ),
			'post_title' => $this->get_lesson_post( $lesson )->post_title,
			'post_type' => 'quiz',
			'post_parent' => $lesson,
		);

		$settings = Sensei()->lesson->get_quiz_settings();

		// Update or Insert the Lesson Quiz.
		// Create the Quiz.
		$quiz_id = wp_insert_post( $post_type_args );

		if ( ! $quiz_id ||
			is_wp_error( $quiz_id ) ) {

			return 0;
		}

		// Add the post meta data WP will add it if it doesn't exist.
		update_post_meta( $quiz_id, '_quiz_lesson', $lesson );

		// Get quiz grade type: automatic, not graded.
		// @todo let user chose if manual mode!
		update_post_meta( $quiz_id, '_quiz_grade_type', 'auto' );

		// Get quiz pass setting.
		update_post_meta( $quiz_id, '_pass_required', true );

		foreach ( $settings as $field ) {
			if ( 'random_question_order' != $field['id'] ) {

				// Ignore values not posted to avoid
				// overwriting with empty or default values
				// when the values are posted from bulk edit or quick edit.
				if ( ! isset( $_POST[ $field['id'] ] ) ) {
					continue;
				}

				$value = Sensei()->lesson->get_submitted_setting_value( $field );

				if ( isset( $value ) ) {
					add_post_meta( $quiz_id, '_' . $field['id'], $value );
				}
			}
		}

		// Set the post terms for quiz-type
		wp_set_post_terms( $quiz_id, array( 'multiple-choice' ), 'quiz-type' );

		update_post_meta( $lesson, '_lesson_quiz', $quiz_id );

		return $quiz_id;
	}


	/**
	 * Set lesson module and course
	 *
	 * @param string $lesson Lesson ID.
	 * @param int    $course Course ID.
	 * @param int    $module Module ID.
	 *
	 * @return bool
	 */
	public function set_lesson_module_course( $lesson, $course_id = 0, $module_id = 0 ) {

		if ( ! $lesson ||
			! get_post_status( $lesson ) ) {

			return false;
		}

		if ( ! $course_id ) {

			$course_id = $this->course;
		}

		if ( ! $module_id ) {

			$module_id = $this->module;
		}

		// Set Lesson Course.
		update_post_meta( $lesson, '_lesson_course', $course_id );

		if ( ! $module_id ) {
			// Lesson outside module.
			return true;
		}

		// Set Lesson Module.
		wp_set_object_terms(
			absint( $lesson ),
			absint( $module_id ),
			'module',
			false
		);

		$order_module = get_post_meta( $lesson, '_order_module_' . $module_id, true );

		// Set default order for lesson inside module.
		if ( ! $order_module && '0' !== $order_module ) {

			update_post_meta( $lesson, '_order_module_' . $module_id, '0' );
		}

		return true;
	}


	/**
	 * Do lesson action
	 * Depends on current screen:
	 * 1. Add existing lesson to order list.
	 * 2. Remove lesson from order list.
	 * 3. Save list order.
	 *
	 * @todo Sensei_Quiz::update_after_lesson_change()!
	 * Sensei()->course->update_status_after_lesson_change()
	 *
	 * @param int $lesson Module ID.
	 *
	 * @return bool True if action preformed, else false.
	 */
	public function do_action_lesson( $lesson ) {

		if ( ! $this->action ) {

			return false;
		}

		// Lesson landing:
		// 1. Add existing lesson to order list.
		if ( 'lesson_landing' === $this->screen &&
			'add' === $this->action &&
			$lesson ) {

			$term_args = array(
				'include' => $this->get_course_module_ids(),
			);

			if ( ! $this->check_lesson( $lesson ) ||
				wp_get_post_terms( $lesson, 'module', $term_args ) ) {

				// Duplicate lesson if belonging to OTHER course,
				// or to THIS course, but already in a MODULE.
				$original_lesson = $lesson;

				$lesson = $this->duplicate->duplicate_lesson( $lesson, $this->course );

				if ( ! $lesson ) {
					$this->action_error( __( 'Lesson', 'woothemes-sensei' ) );

					return false;
				}
			}

			if ( 'draft' === get_post_status( $lesson ) ) {

				$update_post_args = array(
					'post_type' => 'lesson',
					'ID' => $lesson,
					'post_status' => 'publish',
				);

				wp_update_post( $update_post_args );
			}

			$this->set_lesson_module_course( $lesson );

			return true;
		}

		// Lesson landing:
		// 2. Remove lesson from order list, eventually delete it.
		if ( 'lesson_landing' === $this->screen &&
			( 'remove' === $this->action ||
				'delete' === $this->action ) &&
			$lesson ) {

			if ( ! $this->check_lesson( $lesson ) ) {

				// Lesson not belonging to course.
				return false;
			}

			wp_remove_object_terms(
				absint( $lesson ),
				absint( $this->module ),
				'module'
			);

			if ( 'delete' === $this->action ) {

				// Unset Lesson Course.
				delete_post_meta( $this->course, '_lesson_course', $lesson );

				// Delete lesson.
				wp_trash_post( $lesson );
			}

			return true;
		}

		// Lesson landing:
		// 3. Save list order.
		if ( 'lesson_landing' === $this->screen &&
			'order' === $this->action ) {
			return Sensei()->admin->save_lesson_order( '', $this->course );
		}

		// Lesson title:
		if ( 'lesson_title' === $this->screen ) {

			if ( ! isset( $_POST['post_title'] ) ) {
				return false;
			}

			if ( 'new' === $this->action ) {
				// Save lesson title.
				$lesson = wp_insert_post( array(
					'ID' => $lesson,
					'post_title' => $_POST['post_title'],
					'post_status' => 'publish',
					'post_type' => 'lesson',
				) );
			} else {
				// Save lesson title.
				$lesson = wp_update_post( array(
					'ID' => $lesson,
					'post_title' => $_POST['post_title'],
				) );
			}

			if ( ! $lesson ) {

				$this->action_error( __( 'Lesson', 'woothemes-sensei' ) );

				return false;
			}

			$this->set_lesson_module_course( $lesson );

			$this->set_lesson( $lesson );

			return true;
		}

		// Lesson description:
		if ( 'lesson_description' === $this->screen ) {

			if ( 'save' !== $this->action ||
				! isset( $_POST['cws-editor'] ) ) {
				return false;
			}

			// Save lesson description.
			$lesson = wp_update_post( array(
				'ID' => $lesson,
				'post_content' => $_POST['cws-editor'],
			) );

			if ( ! $lesson ) {

				$this->action_error( __( 'Lesson', 'woothemes-sensei' ) );

				return false;
			}

			$this->unset_object( 'lesson' );

			return true;
		}

		return false;
	}


	/**
	 * Get Lesson Post
	 *
	 * @param int $lesson Lesson ID.
	 */
	public function get_lesson_post( $lesson = 0 ) {

		static $lesson_post;

		if ( ! $lesson ) {
			$lesson = $this->lesson;
		}

		if ( $lesson_post &&
			$lesson === $lesson_post->ID &&
			! $this->action ) {

			return $lesson_post;
		}

		$lesson_post = get_post( $lesson );

		return $lesson_post;
	}


	/**
	 * Get Sensei Lessons
	 * formatted for the Lesson select input.
	 *
	 * @return array Sensei lessons.
	 */
	public function get_lessons( $exclude, $course = 0 ) {
		// Get existing lessons.
		$args = array(
			'post_type' => 'lesson',
			'post_status' => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => -1,
			'orderby' => 'post_title',
			'order' => 'ASC',
		);

		if ( 'exclude_modules' === $exclude ||
			'include_modules' === $exclude ) {

			$operator = ( 'include_modules' === $exclude ) ? 'IN' : 'NOT IN';

			$args['tax_query'] = array(
				array(
					'taxonomy' => 'module',
					'field'    => 'id',
					'terms'    => $this->get_course_module_ids(),
					'operator' => $operator,
				),
			);
		}

		$args['meta_query'] = array();

		if ( 'include_quizzes' === $exclude ) {

			// Limit lessons to quizzes.
			$args['meta_query'][] = array(
				'key' => '_quiz_has_questions',
				'value' => true,
				'compare' => '=',
			);
		}

		if ( $course ) {

			// Limit lessons to course.
			$args['meta_query'][] = array(
				array(
					'key' => '_lesson_course',
					'value' => $course,
					'compare' => '=',
				),
			);
		}

		$lessons = get_posts( $args );

		// Build the lessons array.
		$select_lessons = array();

		if ( isset( $lessons ) &&
			is_array( $lessons ) ) {
			foreach ( $lessons as $lesson ) {
				$select_lessons[] = array(
					'id' => $lesson->ID,
					'title' => $lesson->post_title,
				);
			}
		}

		return $select_lessons;
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
			$this->lesson ) {

			$url = get_permalink( $this->lesson );
		}

		return parent::get_preview_url( $url );
	}


	/**
	 * Lesson Title screen output.
	 *
	 * @access  protected
	 * @since   1.0.0
	 */
	protected function lesson_title() {
		if ( ! $this->lesson ) {

			$value = '';

			$action = 'new';
		} else {

			$action = 'edit';

			$value = $this->get_lesson_post()->post_title;
		}

		$this->screen_title = $this->action_title( __( 'Lesson', 'woothemes-sensei' ), $action );

		$this->form( array(
			'cws_action' => $action,
		) );

		$this->title( __( 'Lesson', 'woothemes-sensei' ), $value );

		$this->previous_button();

		$this->next_button( $action );
	}


	/**
	 * Lesson Description screen output.
	 *
	 * @access  protected
	 * @since   1.0.0
	 */
	protected function lesson_description() {

		if ( ! $this->lesson ) {
			$this->not_found_error( __( 'Lesson', 'woothemes-sensei' ) );
		}

		$post = $this->get_lesson_post();

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
	 * Lesson Landing screen output
	 *
	 * @access  protected
	 * @since   1.0.0
	 *
	 * @uses $this->lesson_add()
	 * @uses $this->lesson_order()
	 */
	protected function lesson_landing() {

		if ( ! $this->course ||
			! $this->module ) {

			$this->not_found_error( __( 'Module', 'woothemes-sensei' ) );

			return;
		}

		$module = $this->get_module_term();

		$screen_title = $module->name;


		$this->screen_title = $this->back_button_html( array(
			'cws_screen' => 'module_landing',
			'cws_module' => '0',
		), __( 'Modules', 'woothemes-sensei' ) ) . ' ' . $screen_title;

		$this->lesson_add();

		$this->lessons_order();
	}


	/**
	 * Lesson Add screen output
	 *
	 * @access  protected
	 * @since   1.0.0
	 *
	 * @uses $this->lesson_select()
	 */
	protected function lesson_add() {

		ob_start();
		?>
		<h3>
			<span class="cws-dashicon dashicons dashicons-list-view"></span>
			<?php _e( 'Lessons', 'woothemes-sensei' ); ?>
			<?php
			$this->add_new_button( $this->get_screen_url( array(
				'cws_screen' => 'lesson_title',
			) ) );
			?>
		</h3>
		<?php

		// Filter lessons by course.
		$this->course_select_filter( 'lesson' );

		$this->form( array(
			'cws_action' => 'add',
		) );

		$this->lesson_select();

		$this->add_button();

		$lesson_add_html = ob_get_clean();

		echo apply_filters( 'cws_lesson_add', $lesson_add_html );
	}

	/**
	 * Lesson Select screen output.
	 *
	 * @access  protected
	 * @since   1.0.0
	 */
	protected function lesson_select() {

		$lessons = $this->get_lessons( '', $this->course );

		$this->select(
			'cws_lesson',
			$lessons,
			$this->lesson,
			'cws-select2 cws-lesson-select-filtered',
			__( 'Lesson', 'woothemes-sensei' )
		);
	}


	/**
	 * Lessons Order screen output.
	 *
	 * @access  protected
	 * @since   1.0.0
	 *
	 * @see Sensei_Admin::lesson_order_screen()
	 */
	protected function lessons_order() {
		if ( ! $this->course ||
			! $this->module ) {

			$this->not_found_error( __( 'Module', 'woothemes-sensei' ) );

			return;
		}

		$course_id = $this->course;

		$args = array(
			'post_type'      => 'lesson',
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_lesson_course',
					'value'   => intval( $course_id ),
					'compare' => '=',
				),
			),
			'tax_query' => array(
				array(
					'taxonomy' => Sensei()->modules->taxonomy,
					'field'    => 'id',
					'terms'    => intval( $this->module ),
				),
			),
			'meta_key'         => '_order_module_' . $this->module,
			'orderby'          => 'meta_value_num date',
			'order'            => 'ASC',
			'suppress_filters' => 0,
		);

		$lessons = get_posts( $args );

		if ( count( $lessons ) <= 0 ) {
			return;
		}

		foreach ( $lessons as $lesson_key => $lesson ) {

			if ( get_post_meta( $lesson->ID, '_quiz_has_questions', true ) ) {

				// Prepend Quiz icon to Lesson title.
				$lessons[ $lesson_key ]->post_title = '<i class="cws-dashicon dashicons dashicons-editor-help"></i>' .
					$lesson->post_title;
			}
		}
		?>
		<h3><?php _e( 'Order Lessons', 'woothemes-sensei' ); ?></h3>

		<?php
		$this->form( array(
			'cws_action' => 'order',
			'cws_ajax_silent' => 1, // Do not output screen HTML on submit.
		) );
		?>
		<!--<form id="editgrouping" method="post" action="" class="validate">-->
			<ul class="sortable-lesson-list" data-module_id="<?php echo esc_attr( $this->module ); ?>">
				<?php
				$this->order_item_list(
					'lesson',
					$lessons,
					array(
						'question_landing' => __( 'Questions', 'woothemes-sensei' ),
						'lesson_title' => __( 'Edit' ),
					)
				);
				?>
			</ul>

			<input type="hidden"
				   name="<?php echo esc_attr( 'lesson-order-module-' . $this->module ); ?>" value="" autocomplete="off" />
			<!--<input type="hidden" name="course_id" value="<?php echo esc_attr( $course_id ); ?>" />
			<input type="submit" class="button-primary"
				value="<?php echo esc_html__( 'Save lesson order', 'woothemes-sensei' ); ?>" />-->
		<?php
	}


	/**
	 * Quiz Select filter output.
	 *
	 * @access  protected
	 * @since   1.0.0
	 *
	 * @param string $filter_object Filtered object name.
	 */
	protected function quiz_select_filter( $filter_object ) {

		$lessons = $this->get_lessons( 'include_quizzes' );

		$this->select(
			'cws_lesson_filter',
			$lessons,
			'',
			'cws-select2 cws-select-filter',
			__( 'Quiz', 'woothemes-sensei' )
		);

		?>
		<input type="hidden" name="cws_filter_object" id="cws_filter_object"
			   value="<?php echo esc_attr( $filter_object ); ?>" />
		<?php
	}


	/**
	 * Output the course lesson options.
	 * For use with the course select filter.
	 * On course select, an AJAX call is made, we send back the options
	 * to update the lesson select.
	 *
	 * @param int $course Course ID.
	 */
	protected function course_lesson_options( $course ) {

		if ( ! $course ) {

			return;
		}

		$lessons = $this->get_lessons( '', $course );

		array_unshift( $lessons, array(
			'id' => '',
			// Translators: %s is an object (Course, Lesson, Question, Module...).
			'title' => sprintf( __( 'Select an existing %s', 'course-wizard-for-sensei' ), __( 'Lesson', 'woothemes-sensei' ) ),
		) );

		foreach ( (array) $lessons as $lesson ) {
			?>
			 <option value="<?php echo esc_attr( $lesson['id'] ); ?>"><?php echo esc_html( $lesson['title'] ); ?></option>
			<?php
		}
	}
}
