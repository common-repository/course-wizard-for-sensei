<?php
/**
 * Course Wizard for Sensei Screen Question
 *
 * @package Course Wizard for Sensei
 */

defined( 'ABSPATH' ) || exit;

/**
 * Course Wizard for Sensei Screen Question class
 */
class Course_Wizard_For_Sensei_Screen_Question extends Course_Wizard_For_Sensei_Screen_Lesson {
	/**
	 * The question.
	 *
	 * @var    integer
	 * @access public
	 * @since  1.0.0
	 */
	public $question = 0;

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

		if ( ! empty( $_POST['cws_lesson_filter'] ) ) {

			if ( isset( $_POST['cws_filter_object'] ) &&
				'question' === $_POST['cws_filter_object'] ) {

				// Print the quiz question options and die.
				$this->quiz_question_options( $_POST['cws_lesson_filter'] );

				wp_die();
			}
		}

		// Construct lesson.
		parent::__construct( $object_params );

		$screen_order = array(
			'question_title_type' => 'question_answer',
			'question_answer' => 'question_landing',
			'question_landing' => 'question_landing',
			'question_settings' => 'question_landing',
		);

		$this->add_screen_order( $screen_order );

		$this->set_question( $object_params['question'] );

		$this->do_action( 'question', $object_params['question'] );
	}


	/**
	 * Set Question
	 *
	 * @param int $question Question ID.
	 */
	public function set_question( $question = 0 ) {

		if ( ! 'question_landing' === $this->screen ||
			! $this->check_question( $question ) ) {
			return;
		}

		$this->question = $question;

		$this->screen_get_vars['cws_question'] = $question;
	}


	/**
	 * Check question
	 *
	 * @access  public
	 * @since   1.0.0
	 *
	 * @param int $question Question ID.
	 *
	 * @return bool True if question exists and belongs to lesson.
	 */
	public function check_question( $question ) {

		if ( ! $question ||
			! $this->quiz ) {

			return false;
		}

		// Check question exists.
		if ( ! get_post_status( $question ) ) {
			return false;
		}

		// Check question belongs to lesson.
		$quizzes = get_post_meta( $question, '_quiz_id', false );

		return in_array( $this->quiz, $quizzes );
	}


	/**
	 * Do question action
	 * Depends on current screen:
	 * 1. Add existing question to order list.
	 * 2. Remove question from order list.
	 * 3. Save list order.
	 *
	 * @param int $question Question ID.
	 *
	 * @return bool True if action preformed, else false.
	 */
	public function do_action_question( $question ) {

		if ( ! $this->action ) {

			return false;
		}

		// Question landing:
		// 1. Add existing question to order list.
		if ( 'question_landing' === $this->screen &&
			'add' === $this->action &&
			$question &&
			isset( $_POST['cws_add_question_count'] ) ) {

			$question_count = intval( $_POST['cws_add_question_count'] );

			++$question_count;

			$quizzes = get_post_meta( $question, '_quiz_id', false );

			$this->quiz = $this->create_quiz();

			if ( ! in_array( $this->quiz, $quizzes ) ) {
				add_post_meta( $question, '_quiz_id', $this->quiz, false );

				update_post_meta( $this->lesson, '_quiz_has_questions', '1' );
			}

			add_post_meta(
				$question,
				'_quiz_question_order' . $this->quiz,
				$this->quiz . '000' . $question_count
			);

			return true;
		}

		// Question landing:
		// 2. Remove question from order list.
		if ( 'question_landing' === $this->screen &&
			'remove' === $this->action &&
			$question ) {

			if ( ! $this->question ) {

				// Question not belonging to course.
				return false;
			}

			delete_post_meta(
				$question,
				'_quiz_id',
				$this->quiz
			);

			return true;
		}

		// Question landing:
		// 3. Save list order.
		if ( 'question_landing' === $this->screen &&
			'order' === $this->action ) {


			if ( empty( $_POST['question-order'] ) ) {
				return false;
			}

			$questions = explode( ',', $_POST['question-order'] );

			$o = 1;

			foreach ( $questions as $question_id ) {
				update_post_meta(
					$question_id,
					'_quiz_question_order' . $this->quiz,
					$this->quiz . '000' . $o
				);

				++$o;
			}

			update_post_meta( $this->quiz, '_question_order', $questions );

			return true;
		}

		// Question title & type.
		// 4. Edit or new question title & type.
		if ( 'question_title_type' === $this->screen &&
			! empty( $_POST['post_title'] ) ) {

			if ( 'new' === $this->action ) {

				$this->quiz = $this->create_quiz();

				// Save question title & type.
				$data = $_POST;
				$data['quiz_id'] = $this->quiz;
				$data['question_id'] = $question;
				$data['post_author'] = get_current_user_id();
				$data['question'] = $_POST['post_title'];

				$question = Sensei()->lesson->lesson_save_question( $data, 'quiz' );

				/*$questions = Sensei()->lesson->lesson_quiz_questions( $this->quiz );

				add_post_meta(
					$question,
					'_quiz_question_order' . $this->quiz,
					$this->quiz . '000' . ( count( $questions ) + 1 )
				);*/
			} else {

				// Save question title.
				$question = wp_update_post( array(
					'ID' => $question,
					'post_title' => $_POST['post_title'],
				) );

				update_post_meta( $question, '_question_grade', $_POST['question_grade'] );
			}

			if ( ! $question ) {

				$this->action_error( __( 'Question', 'woothemes-sensei' ) );

				return false;
			}

			$this->set_question( $question );

			return true;
		}

		// Question answer.
		// 5. Save answer.
		if ( 'question_answer' === $this->screen &&
			'save' === $this->action ) {

			$data = $_POST;
			$data['quiz_id'] = $this->quiz;
			$data['question_id'] = $question;
			$data['post_author'] = get_current_user_id();
			// Add Title type data.
			$data['question'] = $this->get_question_post( $question )->post_title;
			$data['question_type'] = Sensei()->question->get_question_type( $question );
			$data['question_grade'] = Sensei()->question->get_question_grade( $question );

			$question = Sensei()->lesson->lesson_save_question( $data, 'quiz' );

			if ( ! $question ) {

				$this->action_error( __( 'Question', 'woothemes-sensei' ) );

				return false;
			}

			$this->unset_object( 'question' );

			return true;
		}

		// Question settings:
		// 1. Save Quiz settings.
		if ( 'question_settings' === $this->screen &&
			'save' === $this->action ) {

			if ( ! $this->quiz ) {

				return true;
			}

			$quiz_grade_type_value = empty( $_POST['quiz_grade_type'] ) ? 'manual' : $_POST['quiz_grade_type'];

			update_post_meta( $this->quiz, '_random_question_order', $_POST['random_question_order'] );
			update_post_meta( $this->quiz, '_show_questions', $_POST['show_questions'] );
			update_post_meta( $this->quiz, '_quiz_passmark', $_POST['quiz_passmark'] );
			update_post_meta( $this->quiz, '_pass_required', $_POST['pass_required'] );
			update_post_meta( $this->quiz, '_quiz_grade_type', $quiz_grade_type_value );
			update_post_meta( $this->quiz, '_enable_quiz_reset', $_POST['enable_quiz_reset'] );

			return true;
		}

		return false;
	}

	
	/**
	 * Get Question Post
	 *
	 * @param int $question Question ID, defaults to $this->question.
	 *
	 * @return object Question post.
	 */
	public function get_question_post( $question = 0 ) {

		static $question_post;

		if ( ! $question ) {
			$question = $this->question;
		}

		if ( $question_post &&
			$question === $question_post->ID &&
			! $this->action ) {

			return $question_post;
		}

		$question_post = get_post( $question );

		return $question_post;
	}


	/**
	 * Get Sensei Questions
	 * formatted for the Question select input.
	 *
	 * @param string $exclude_quiz Exclude or include quiz questions.
	 *
	 * @return array Sensei questions.
	 */
	public function get_questions( $exclude_quiz = 'exclude', $lesson = 0 ) {
		// Get existing questions.
		$args = array(
			'post_type' => 'question',
			'post_status' => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => -1,
			'orderby' => 'post_title',
			'order' => 'ASC',
		);

		if ( ! $lesson ) {

			$quiz = $this->quiz;
		} else {

			$quiz = Sensei()->lesson->lesson_quizzes( $lesson );
		}

		if ( $quiz &&
			( 'exclude' === $exclude_quiz ||
				'include' === $exclude_quiz ) ) {

			$args['meta_compare'] = ( 'include' === $exclude_quiz ) ? '=' : '!=';
			$args['meta_key'] = '_quiz_id';
			$args['meta_value'] = $quiz;
		}

		$questions = get_posts( $args );

		// Build the questions array.
		$select_questions = array();

		if ( isset( $questions ) &&
			is_array( $questions ) ) {
			foreach ( $questions as $question ) {
				$select_questions[] = array(
					'id' => $question->ID,
					'title' => $question->post_title,
				);
			}
		}

		return $select_questions;
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
			$this->quiz &&
			false === strpos( $this->screen, 'lesson_' ) ) {

			$url = get_permalink( $this->quiz );
		}

		return parent::get_preview_url( $url );
	}


	/**
	 * Question Title and Type screen output.
	 *
	 * @access  protected
	 * @since   1.0.0
	 */
	protected function question_title_type() {
		if ( ! $this->question ) {
			$action = 'new';

			$value = '';
		} else {

			$action = 'edit';

			$value = $this->get_question_post()->post_title;
		}

		$this->screen_title = $this->action_title( __( 'Question', 'woothemes-sensei' ), $action );

		$this->form( array(
			'cws_action' => $action,
		) );

		$this->title( __( 'Question', 'woothemes-sensei' ), $value );

		// @todo Evaluate checkbox?

		$this->question_type();

		$this->question_grade();

		$this->previous_button();

		$this->next_button();
	}


	/**
	 * Question Type field output.
	 *
	 * @see Sensei_Lesson::quiz_panel_add()
	 *
	 * @uses Sensei()->question->question_types()
	 *
	 * @access  protected
	 * @since   1.0.0
	 */
	protected function question_type() {
		// Question type.
		$question_type = Sensei()->question->get_question_type( $this->question );

		// Question types.
		$question_types = Sensei()->question->question_types();

		?>
		<p>
			<label for="cws-select-question-type">
				<?php esc_html_e( 'Question Type:' , 'woothemes-sensei' ); ?>
			</label>
			<?php if ( $question_type ) : // Cannot Edit question type. ?>
				<?php $this->form_hidden_fields( array(
					'question_type' => $question_type,
				) ); ?>
				<span>
					<?php echo esc_html( $question_types[ $question_type ] ); ?>
				</span>
			<?php else : ?>
				<select id="cws-select-question-type" name="question_type" class="cws-select2">
				<?php foreach ( $question_types as $type => $label ) : ?>
					<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
				</select>
			<?php endif; ?>
		</p>
		<?php
	}


	/**
	 * Question Grade field output.
	 *
	 * @see Sensei_Lesson::quiz_panel_add()
	 *
	 * @access  protected
	 * @since   1.0.0
	 */
	protected function question_grade() {

		$grade = '1';

		if ( $this->question ) {

			$grade = Sensei()->question->get_question_grade( $this->question );
		}
		?>
		<p>
			<label for="add-question-grade">
				<?php esc_html_e( 'Question Grade:', 'woothemes-sensei' ); ?>
			</label>
			<input type="number" id="add-question-grade" name="question_grade" class="small-text" min="0"
				value="<?php echo esc_attr( $grade ); ?>" />
		</p>
		<?php
	}


	/**
	 * Question Description screen output.
	 *
	 * @access  protected
	 * @since   1.0.0
	 */
	protected function question_description() {

		if ( ! $this->question ) {
			$this->not_found_error( __( 'Question', 'woothemes-sensei' ) );

			return;
		}

		$post = $this->get_question_post();

		$screen_title = $post->post_title;

		$this->screen_title = $screen_title;

		$this->form_next();

		$this->description( $post );

		$this->previous_button();

		$this->next_button();
	}


	/**
	 * Question Answer screen output.
	 *
	 * @access  protected
	 * @since   1.0.0
	 *
	 * @see Sensei_Question::question_edit_panel()
	 * @see Sensei_Lesson::enqueue_scripts()
	 */
	protected function question_answer() {

		if ( ! $this->question ) {
			$this->not_found_error( __( 'Question', 'woothemes-sensei' ) );

			return;
		}

		$post = $this->get_question_post();

		$this->screen_title = $post->post_title;

		$question_id = $this->question;

		$question_type = Sensei()->question->get_question_type( $this->question );

		$this->form( array(
			'cws_action' => 'save',
		) );

		?>
		<div class="single-question" id="lesson-quiz">
			<div id="add-question-main">
			<?php
				echo Sensei()->lesson->quiz_panel_question_field(
					$question_type,
					$question_id,
					0
				);
			?>
			</div>
		</div>
		<?php

		$this->previous_button();

		$this->next_button();
	}


	/**
	 * Question Landing screen output
	 *
	 * @access  protected
	 * @since   1.0.0
	 *
	 * @uses $this->question_add()
	 * @uses $this->question_order()
	 */
	protected function question_landing() {

		if ( ! $this->lesson ) {

			$this->not_found_error( __( 'Lesson', 'woothemes-sensei' ) );

			return;
		}

		$lesson = $this->get_lesson_post();

		$screen_title = $lesson->post_title;

		ob_start();

		$this->link_button(
			'<i class="dashicons dashicons-admin-generic"></i>',
			array( 'cws_screen' => 'question_settings' ),
			'',
			__( 'Quiz Settings', 'woothemes-sensei' )
		);

		$settings_button = ob_get_clean();

		$this->screen_title = $this->back_button_html( array(
			'cws_screen' => 'lesson_landing',
			'cws_lesson' => '0',
		), __( 'Lessons', 'woothemes-sensei' ) ) . ' ' . $settings_button . ' ' . $screen_title;

		$this->question_add();

		$this->questions_order();
	}


	/**
	 * Quiz Settings screen output.
	 *
	 * @uses Sensei_Lesson::lesson_quiz_settings_meta_box_content()
	 */
	protected function question_settings() {

		if ( ! $this->lesson ) {

			$this->not_found_error( __( 'Lesson', 'woothemes-sensei' ) );

			return;
		}

		$lesson = $this->get_lesson_post();

		$this->screen_title = '<span class="cws-dashicon dashicons dashicons-admin-generic"></span>' .
			$lesson->post_title;

		$this->form( array(
			'cws_action' => 'save',
		) );

		$this->quiz_settings_meta_box();

		$this->next_button();
	}


	/**
	 * Quiz settings meta box.
	 */
	protected function quiz_settings_meta_box() {

		global $post;

		$post->ID = $this->lesson;

		$sensei_lesson = new Sensei_Lesson();

		?>
		<div id="poststuff" style="min-width: 320px;">
			<div id="postimagediv" class="postbox">
				<h2 class='hndle'><span><?php echo esc_html__( 'Quiz Settings', 'woothemes-sensei' ); ?></span></h2>
				<div class="inside">
					<?php
					$sensei_lesson->lesson_quiz_settings_meta_box_content();
					?>
				</div>
			</div>
		</div>
		<?php
	}


	/**
	 * Question Add screen output
	 *
	 * @access  protected
	 * @since   1.0.0
	 *
	 * @uses $this->question_select()
	 */
	protected function question_add() {

		?>
		<h3>
			<span class="cws-dashicon dashicons dashicons-editor-help"></span>
			<?php _e( 'Questions', 'woothemes-sensei' ); ?>
			<?php
			$this->add_new_button( $this->get_screen_url( array(
				'cws_screen' => 'question_title_type',
			) ) );
			?>
		</h3>
		<?php

		// Filter questions by quiz.
		$this->quiz_select_filter( 'question' );

		$this->form( array(
			'cws_action' => 'add',
		) );

		$this->form_hidden_fields( array(
			'cws_add_question_count' => 0,
		) );

		$this->question_select();

		$this->add_button();
	}


	/**
	 * Question Select screen output.
	 *
	 * @access  protected
	 * @since   1.0.0
	 */
	protected function question_select() {

		// $questions = $this->get_questions( 'include' );

		$this->select(
			'cws_question',
			array(),
			$this->question,
			'cws-select2 cws-question-select-filtered',
			__( 'Question', 'woothemes-sensei' )
		);
	}


	/**
	 * Questions Order screen output
	 *
	 * @access  protected
	 * @since   1.0.0
	 *
	 * @see Sensei_Lesson::quiz_panel()
	 */
	protected function questions_order() {
		if ( ! $this->quiz ) {

			// $this->not_found_error( __( 'Quiz', 'woothemes-sensei' ) );

			return;
		}

		// Setup Questions Query.
		$questions = Sensei()->lesson->lesson_quiz_questions( $this->quiz );

		if ( count( $questions ) <= 0 ) {
			return;
		}

		$question_count = 0;

		foreach ( $questions as $question ) {

			if ( 'multiple_question' == $question->post_type ) {
				$question_number = get_post_meta( $question->ID, 'number', true );
				$question_count += $question_number;
			} else {
				++$question_count;
			}
		}

		?>
		<h3><?php _e( 'Order Questions', 'course-wizard-for-sensei' ); ?></h3>

		<?php
		$this->form( array(
			'cws_action' => 'order',
			'cws_ajax_silent' => 1, // Do not output screen HTML on submit.
		) );
		?>
		<!--<form id="editgrouping" method="post" action="" class="validate">-->
		<ul class="sortable-question-list" data-quiz_id="<?php echo esc_attr( $this->quiz ); ?>">
			<?php
			// @todo Edit link only if question not in another lesson!
			$this->order_item_list(
				'question',
				$questions,
				array(
					'question_title_type' => __( 'Edit' ),
				)
			);
			?>
		</ul>

		<input type="hidden"
			   name="<?php echo esc_attr( 'question-order' ); ?>"
			   value="" autocomplete="off" />
		<input type="hidden" name="question_counter" value="<?php echo esc_attr( $question_count ); ?>" />
		<!--<input type="submit" class="button-primary"
				value="<?php echo esc_html__( 'Save question order', 'woothemes-sensei' ); ?>" />-->
		<?php
	}


	/**
	 * Output the course question options.
	 * For use with the quiz select filter.
	 * On course select, an AJAX call is made, we send back the options
	 * to update the question select.
	 *
	 * @param int $lesson Lesson ID.
	 */
	protected function quiz_question_options( $lesson ) {

		if ( ! $lesson ) {

			return;
		}

		$questions = $this->get_questions( 'include', $lesson );

		array_unshift( $questions, array(
			'id' => '',
			// Translators: %s is an object (Course, Lesson, Question, Module...).
			'title' => sprintf( __( 'Select an existing %s', 'course-wizard-for-sensei' ), __( 'Question', 'woothemes-sensei' ) ),
		) );

		foreach ( (array) $questions as $question ) {
			?>
			<option value="<?php echo esc_attr( $question['id'] ); ?>"><?php echo esc_html( $question['title'] ); ?></option>
			<?php
		}
	}
}
