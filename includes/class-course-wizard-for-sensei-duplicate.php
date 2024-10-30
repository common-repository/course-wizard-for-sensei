<?php
/**
 * Course Wizard For Sensei Duplicate
 * Where we duplicate courses.
 *
 * Inspired by the Sensei LMS Admin.
 * @see class-sensei-admin.php
 *
 * @package Course Wizard For Sensei
 */

defined( 'ABSPATH' ) || exit;

/**
 * Course Wizard For Sensei Duplicate class
 */
class Course_Wizard_For_Sensei_Duplicate {
	/**
	 * Duplicate course
	 *
	 * @param int     $post_id Post ID.
	 * @param string  $new_title New Post Title.
	 * @param boolean $with_lessons Include lessons or not.
	 *
	 * @return int New post ID or 0 on error.
	 */
	public function duplicate_course( $post_id, $new_title, $with_lessons = true ) {

		// Duplicate course.
		$post = get_post( $post_id );

		if ( ! is_wp_error( $post ) ) {
			$new_post_id = $this->duplicate_post( $post, $new_title, $with_lessons );

			if ( $with_lessons ) {
				$this->duplicate_course_lessons( $post_id, $new_post_id );
			}

			return $new_post_id;
		}

		return 0;
	}


	/**
	 * Duplicate lesson
	 *
	 * @param  integer $lesson_id ID of original lesson.
	 *
	 * @return int 0 or new Lesson ID.
	 */
	public function duplicate_lesson( $lesson_id, $course_id ) {

		$lesson = get_post( $lesson_id );

		if ( ! $lesson && is_wp_error( $lesson ) ) {

			return 0;
		}

		$new_lesson_id = $this->duplicate_post( $lesson, $lesson->post_title );

		if ( ! $new_lesson_id ) {
			return 0;
		}

		add_post_meta( $new_lesson_id, '_lesson_course', $course_id );

		$this->duplicate_lesson_quizzes( $lesson->ID, $new_lesson_id );

		return $new_lesson_id;
	}


	/**
	 * Duplicate post
	 * We copied the original Sensei_Admin function here to access it the way we want.
	 * The post author is now the current user.
	 * Private functions...
	 *
	 * @since 1.3.0 Modules are duplicated.
	 *
	 * @since 1.6.0 Duplicated Course now has "Draft" status.
	 *
	 * @param  object  $post          Post to be duplicated.
	 * @param  string  $new_title     New post title.
	 * @param  boolean $ignore_course_or_with_lessons Ignore lesson course when duplicating or with lessons.
	 * @return int                    Duplicate post ID.
	 */
	public function duplicate_post( $post, $new_title = null, $ignore_course_or_with_lessons = false ) {

		static $modules_map = array();

		$new_post = array();

		foreach ( $post as $k => $v ) {
			if ( ! in_array( $k, array( 'ID', 'post_author', 'post_status', 'post_date', 'post_date_gmt', 'post_name', 'post_modified', 'post_modified_gmt', 'guid', 'comment_count' ) ) ) {
				$new_post[ $k ] = $v;
			}
		}

		$new_post['post_title']        = ( empty( $new_title ) ?
			$new_post['post_title'] . __( '(Duplicate)', 'woothemes-sensei' ) :
			$new_title
		);
		$new_post['post_date']         = current_time( 'mysql' );
		$new_post['post_date_gmt']     = get_gmt_from_date( $new_post['post_date'] );
		$new_post['post_modified']     = $new_post['post_date'];
		$new_post['post_modified_gmt'] = $new_post['post_date_gmt'];

		switch ( $post->post_type ) {
			case 'course':
				$new_post['post_status'] = 'draft';
				break;
			case 'lesson':
				$new_post['post_status'] = 'publish'; // 'draft';
				break;
			case 'quiz':
				$new_post['post_status'] = 'publish';
				break;
			case 'question':
				$new_post['post_status'] = 'publish';
				break;
			default:
				$new_post['post_status'] = 'publish';
				break;
		}

		// As per wp_update_post() we need to escape the data from the db.
		$new_post = wp_slash( $new_post );

		$new_post_id = wp_insert_post( $new_post );

		if ( ! is_wp_error( $new_post_id ) ) {

			$post_meta = get_post_custom( $post->ID );

			if ( $post_meta && count( $post_meta ) > 0 ) {

				$ignore_meta = array( '_quiz_lesson', '_quiz_id', '_lesson_quiz' );

				if ( 'lesson' === $post->post_type &&
					$ignore_course_or_with_lessons ) {
					$ignore_meta[] = '_lesson_course';
				}

				foreach ( $post_meta as $key => $meta ) {
					foreach ( $meta as $value ) {
						$value = maybe_unserialize( $value );

						if ( ! in_array( $key, $ignore_meta ) &&
							false === strpos( $key, '_order_module_' ) ) {

							add_post_meta( $new_post_id, $key, $value );
						}
					}
				}
			}

			add_post_meta( $new_post_id, '_duplicate', $post->ID );

			$taxonomies = get_object_taxonomies( $post->post_type, 'objects' );

			foreach ( $taxonomies as $slug => $tax ) {
				$terms = get_the_terms( $post->ID, $slug );

				if ( isset( $terms ) && is_array( $terms ) && 0 < count( $terms ) ) {
					foreach ( $terms as $term ) {

						if ( 'course' === $post->post_type &&
							'module' === $slug &&
							$ignore_course_or_with_lessons ) {

							$old_module_id = $term->term_id;

							$new_module = (object) $this->duplicate_module( $term );

							if ( $new_module && ! is_wp_error( $new_module ) ) {

								$term = $new_module;
							}

							$modules_map[ $old_module_id ] = $term->term_id;
						}

						if ( 'lesson' === $post->post_type &&
							'module' === $slug &&
							! empty( $modules_map[ $term->term_id ] ) ) {

							$order_module = get_post_meta( $post->ID, '_order_module_' . $term->term_id, true );

							// Get duplicated module ID.
							$term->term_id = $modules_map[ $term->term_id ];

							add_post_meta( $new_post_id, '_order_module_' . $term->term_id, $order_module );
						}

						wp_set_object_terms( $new_post_id, $term->term_id, $slug, true );
					}
				}
			}

			if ( 'course' === $post->post_type &&
				$ignore_course_or_with_lessons &&
				$modules_map ) {

				// Update _module_order meta with duplicated module IDs.
				update_post_meta( $new_post_id, '_module_order', array_values( $modules_map ) );
			}

			return $new_post_id;
		}

		return 0;
	}


	/**
	 * Duplicate module
	 *
	 * @since 1.3.0
	 *
	 * @param object $module
	 *
	 * @return array|WP_Error Duplicated Module.
	 */
	private function duplicate_module( $module ) {

		if ( is_numeric( $module ) ) {

			$module = get_term( $module, 'module' );
		} else {

			$module = get_term( $module->term_id, 'module' );
		}

		if ( empty( $module->term_id ) ) {

			return array();
		}

		$module_title = $module->name;

		$module->term_id = 0;

		// Duplicate module, unique slug.
		$module = wp_insert_term(
			$module_title,
			'module',
			array(
				'slug' => wp_unique_term_slug( sanitize_title( $module_title ), $module ),
			)
		);

		return $module;
	}

	/**
	 * Duplicate lessons inside a course
	 *
	 * @param  integer $old_course_id ID of original course.
	 * @param  integer $new_course_id ID of duplicated course.
	 *
	 * @return void
	 */
	private function duplicate_course_lessons( $old_course_id, $new_course_id ) {
		$lesson_args = array(
			'post_type' => 'lesson',
			'posts_per_page' => -1,
			'meta_key' => '_lesson_course',
			'meta_value' => $old_course_id,
			'suppress_filters' => 0,
		);

		$lessons = get_posts( $lesson_args );

		foreach ( $lessons as $lesson ) {
			$new_lesson_id = $this->duplicate_post( $lesson, $lesson->post_title, true );

			add_post_meta( $new_lesson_id, '_lesson_course', $new_course_id );

			$this->duplicate_lesson_quizzes( $lesson->ID, $new_lesson_id );
		}
	}


	/**
	 * Duplicate quizzes inside lessons
	 *
	 * @since 1.4.0 Questions are duplicated.
	 *
	 * @param  integer $old_lesson_id ID of original lesson.
	 * @param  integer $new_lesson_id ID of duplicate lesson.
	 *
	 * @return void
	 */
	private function duplicate_lesson_quizzes( $old_lesson_id, $new_lesson_id ) {

		$old_quiz_id = Sensei()->lesson->lesson_quizzes( $old_lesson_id );
		$old_quiz_questions = Sensei()->lesson->lesson_quiz_questions( $old_quiz_id );

		// Duplicate the generic wp post information.
		$new_quiz_id = $this->duplicate_post( get_post( $old_quiz_id ), '' );

		// Update the new lesson data.
		add_post_meta( $new_lesson_id, '_lesson_quiz', $new_quiz_id );

		//update the new quiz data
		add_post_meta( $new_quiz_id, '_quiz_lesson', $new_lesson_id );

		wp_update_post(
			array(
				'ID' => $new_quiz_id,
				'post_parent' => $new_lesson_id,
			)
		);

		foreach ( $old_quiz_questions as $old_question ) {

			// Duplicate question.
			$question_id = $this->duplicate_post( $old_question, $old_question->post_title );

			// Copy the question order over to the new quiz.
			$old_question_order = get_post_meta( $old_question->ID, '_quiz_question_order' . $old_quiz_id, true );
			$new_question_order = str_ireplace( $old_quiz_id, $new_quiz_id , $old_question_order );
			add_post_meta( $question_id, '_quiz_question_order' . $new_quiz_id, $new_question_order );

			// Add question to quiz.
			add_post_meta( $question_id, '_quiz_id', $new_quiz_id, false );
		}
	}
}
