<?php
/**
 * Course Wizard for Sensei Screen Module
 *
 * @package Course Wizard for Sensei
 */

defined( 'ABSPATH' ) || exit;

/**
 * Course Wizard for Sensei Screen Module class
 */
class Course_Wizard_For_Sensei_Screen_Module extends Course_Wizard_For_Sensei_Screen_Course {
	/**
	 * The module.
	 *
	 * @var    integer
	 * @access public
	 * @since  1.0.0
	 */
	public $module = 0;

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

		// Construct screen.
		parent::__construct( $object_params );

		$screen_order = array(
			'module_landing' => 'module_landing',
			'module_title' => 'module_description',
			'module_description' => 'module_landing',
		);

		$this->add_screen_order( $screen_order );

		$this->set_module( $object_params['module'] );

		$this->do_action( 'module', $object_params['module'] );
	}


	/**
	 * Set Module
	 *
	 * @param int $module Module ID.
	 */
	public function set_module( $module = 0 ) {

		if ( 'module_landing' === $this->screen ||
			! $this->check_module( $module ) ) {
			return;
		}

		$this->module = $module;

		$this->screen_get_vars['cws_module'] = $module;
	}


	/**
	 * Check module
	 *
	 * @access  public
	 * @since   1.0.0
	 *
	 * @param int $module Module ID.
	 *
	 * @return bool True if module exists and belongs to course.
	 */
	public function check_module( $module ) {

		if ( ! $module ||
			! $this->course ) {
			return false;
		}

		// Check module exists.
		if ( ! term_exists( $module, 'module' ) ) {
			return false;
		}

		// Check module belongs to course.
		return has_term( $module, 'module', $this->course );
	}


	/**
	 * Do module action
	 * Depends on current screen:
	 * 1. Add existing module to order list.
	 * 2. Remove module from order list.
	 * 3. Save list order.
	 *
	 * @since 1.6.1 Publish Course.
	 *
	 * @param int $module Module ID.
	 *
	 * @return bool True if action preformed, else false.
	 */
	public function do_action_module( $module ) {

		if ( ! $this->action ) {

			return false;
		}

		// Module landing:
		// 1. Add existing module to order list.
		if ( 'module_landing' === $this->screen &&
			'add' === $this->action &&
			$module ) {

			// Associate module with Course.
			return $this->set_course_module( $module );
		}

		// Publish Course:
		if ( 'module_landing' === $this->screen &&
			'publish' === $this->action ) {

			if ( ! $this->course ) {

				$this->action_error( __( 'Course', 'woothemes-sensei' ) );

				return false;
			}

			// Publish Course.
			$this->publish_course( $this->course );

			return true;
		}

		// Module landing:
		// 2. Remove module from order list and its lessons.
		// @since 1.5.0 Delete them if confirmed by user (see JS).
		if ( 'module_landing' === $this->screen &&
			( 'remove' === $this->action ||
				'delete' === $this->action ) &&
			$module ) {

			if ( ! $this->check_module( $module ) ) {

				// Module not belonging to course.
				return false;
			}

			wp_remove_object_terms(
				absint( $this->course ),
				absint( $module ),
				'module'
			);

			// Remove the Module Lessons.
			$args = array(
				'post_type'      => 'lesson',
				'post_status'    => array( 'publish', 'private' ),
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'     => '_lesson_course',
						'value'   => intval( $this->course ),
						'compare' => '=',
					),
				),
				'tax_query' => array(
					array(
						'taxonomy' => Sensei()->modules->taxonomy,
						'field'    => 'id',
						'terms'    => intval( $module ),
					),
				),
				'meta_key'         => '_order_module_' . $module,
			);

			$lessons = get_posts( $args );

			foreach ( $lessons as $lesson_key => $lesson ) {

				wp_remove_object_terms(
					absint( $lesson->ID ),
					absint( $module ),
					'module'
				);

				if ( 'delete' === $this->action ) {
					// Unset Lesson Course.
					delete_post_meta( $this->course, '_lesson_course', $lesson->ID );

					// Delete lesson.
					wp_trash_post( $lesson->ID );
				}
			}


			if ( 'delete' === $this->action ) {
				// Delete module if has no other course oir lessons linked.
				$module_courses = get_objects_in_term( absint( $module ), 'module' );

				if ( ! $module_courses ) {

					wp_delete_term( absint( $module ), 'module' );
				}
			}

			$this->unset_object( 'module' );

			return true;
		}

		// Module landing:
		// 3. Save list order.
		if ( 'module_landing' === $this->screen &&
			'order' === $this->action ) {

			if ( empty( $_POST['module-order'] ) ) {
				return false;
			}

			$order = explode( ',', $_POST['module-order'] );

			update_post_meta( intval( $this->course ), '_module_order', $order );

			return true;
		}

		// Module title:
		if ( 'module_title' === $this->screen ) {

			if ( ! isset( $_POST['post_title'] ) ) {
				return false;
			}

			// Fix prevent Sensei from removing module courses.
			$_POST['action'] = 'inline-save-tax';

			if ( 'new' === $this->action ) {
				// Save module title.
				$module = wp_insert_term(
					$_POST['post_title'],
					'module'
				);

				// Term slug already exists error, no fail!
				if ( is_wp_error( $module ) &&
					'term_exists' === $module->get_error_code() ) {

					$existing_module_id = $module->error_data['term_exists'];
					$existing_module = get_term( $existing_module_id );

					// Hack: change ID so we do not exclude it when checking for existing slug.
					$existing_module->term_id = 0;

					// Re-intent with a unique module slug.
					$module_slug = wp_unique_term_slug(
						sanitize_title( $_POST['post_title'] ),
						$existing_module
					);

					$module = wp_insert_term(
						$_POST['post_title'],
						'module',
						array(
							'slug' => $module_slug,
						)
					);
				}
			} else {

				// Save module title.
				$module = wp_update_term(
					$module,
					'module',
					array(
						'name' => $_POST['post_title'],
					)
				);
			}

			if ( ! $module ||
				is_wp_error( $module ) ) {

				$this->action_error( __( 'Module', 'woothemes-sensei' ), $this->action, $module->get_error_message() );

				return false;
			}

			$module = $module['term_id'];

			// Associate module with Course.
			$this->set_course_module( $module );

			$this->set_module( $module );

			return true;
		}

		// Module description:
		if ( 'module_description' === $this->screen ) {

			// Fix prevent Sensei from removing module courses.
			$_POST['action'] = 'inline-save-tax';

			if ( 'save' !== $this->action ||
				! isset( $_POST['description'] ) ) {
				return false;
			}

			// Save module description.
			$module = wp_update_term(
				$module,
				'module',
				array(
					'description' => $_POST['description'],
				)
			);

			if ( ! $module ||
				is_wp_error( $module ) ) {

				$this->action_error( __( 'Module', 'woothemes-sensei' ) );

				return false;
			}

			$module = $module['term_id'];

			// Associate module with Course.
			$this->set_course_module( $module );

			$this->unset_object( 'module' );

			return true;
		}
		
		return false;
	}


	/**
	 * Set course module.
	 * Associate module with Course.
	 *
	 * @param int $module Module ID.
	 * @param int $course Course ID, defaults to $this->>course.
	 *
	 * @return bool False if module already belongs to course, else true.
	 */
	public function set_course_module( $module, $course = 0 ) {

		if ( ! $course ) {

			$course = $this->course;
		}

		// Check module belongs to course.
		if ( has_term( $module, 'module', $course ) ) {

			return false;
		}

		// Associate module with Course.
		wp_set_object_terms(
			absint( $course ),
			absint( $module ),
			'module',
			true
		);

		$courses = get_posts(array(
			'post_type' => 'course',
			'numberposts' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'module',
					'field' => 'id',
					'terms' => $module, // Where term_id of Term 1 is "1".
					'include_children' => false
				)
			)
		));

		return true;
	}


	/**
	 * Get Module Term
	 *
	 * @param int $module Module ID.
	 */
	public function get_module_term( $module = 0 ) {

		static $module_term;

		if ( ! $module ) {
			$module = $this->module;
		}

		if ( $module_term &&
			$module === $module_term->term_id &&
			! $this->action ) {

			return $module_term;
		}

		$module_term = get_term( $module );

		return $module_term;
	}


	/**
	 * Get Sensei Modules
	 * formatted for the Module select input.
	 *
	 * @return array Sensei modules.
	 */
	public function get_modules( $exclude_course = 'exclude' ) {
		// Get existing modules.
		$modules_args = array(
			'taxonomy' => 'module',
			'hide_empty' => false,
		);

		if ( 'exclude' === $exclude_course ||
			'include' === $exclude_course ) {

			$modules_args[ $exclude_course ] = $this->get_course_module_ids();
		}

		$modules = get_terms( $modules_args );

		if ( empty( $modules ) ||
			is_wp_error( $modules ) ) {
			return array();
		}

		// Build the modules array.
		$select_modules = array();

		foreach ( $modules as $module ) {
			$select_modules[] = array(
				'id' => $module->term_id,
				'title' => $module->name,
			);
		}

		return $select_modules;
	}


	/**
	 * Get Course Module IDs.
	 *
	 * @param int $course Course ID, defaults to $this->course.
	 *
	 * @return array Module IDs.
	 */
	public function get_course_module_ids( $course = 0 ) {

		if ( ! $course ) {
			$course = $this->course;
		}

		return wp_get_object_terms(
			$course,
			'module',
			array(
				'fields' => 'ids',
			)
		);
	}

	/**
	 * Create module term
	 *
	 * @link https://developer.wordpress.org/reference/functions/wp_create_term/
	 * @link https://developer.wordpress.org/reference/functions/wp_insert_term/
	 * If the term already exists on the same hierarchical level,
	 * or the term slug and name are not unique, a WP_Error object will be returned.
	 *
	 * @param string $title Module title.
	 */
	public function create_module_term( $title ) {

		$term = wp_create_term( $title, 'module' );

		return $this->add_course_module_term( $title );
	}


	/**
	 * Add course module term.
	 *
	 * @link https://developer.wordpress.org/reference/functions/wp_set_object_terms/
	 * Creates a term if it doesn't exist (using the slug).
	 *
	 * @param int|string $module Module ID or Module title (create).
	 * @param int        $course Course ID.
	 *
	 * @return bool|int  Module ID or false.
	 */
	public function add_course_module_term( $module = 0, $course = 0 ) {

		if ( ! $course ) {

			$course = $this->course;
		}

		if ( ! $module ) {

			$module = $this->module;
		}

		$terms = wp_set_object_terms( $course, $module, 'module', true );

		if ( is_wp_error( $terms ) ) {

			// Term already exists, based on slug...
			return false;

		} else {

			// Term created, return ID. @todo set module again!
			return $terms[0];
		}
	}


	/**
	 * Module Title screen output.
	 *
	 * @access  protected
	 * @since   1.0.0
	 */
	protected function module_title() {
		if ( ! $this->module ) {
			$value = '';

			$action = 'new';
		} else {

			$action = 'edit';

			$value = $this->get_module_term()->name;
		}

		$this->screen_title = $this->action_title( __( 'Module', 'woothemes-sensei' ), $action );

		$this->form( array(
			'cws_action' => $action,
		) );

		$this->title( __( 'Module', 'woothemes-sensei' ), $value );

		$this->previous_button();

		$this->next_button( $action );
	}


	/**
	 * Module Description screen output.
	 *
	 * @access  protected
	 * @since   1.0.0
	 */
	protected function module_description() {

		if ( ! $this->module ) {

			$this->not_found_error( __( 'Module', 'woothemes-sensei' ) );

			return;
		}

		$term = $this->get_module_term();

		$screen_title = $term->name;

		$this->screen_title = $screen_title;

		$this->form( array(
			'cws_action' => 'save',
		) );

		$this->description( $term );

		$this->previous_button();

		$this->next_button();
	}


	/**
	 * Get Module landing screen title HTML:
	 * Back button, course title and Edit course button.
	 *
	 * @return string
	 */
	public function get_module_landing_title() {

		if ( ! $this->course ) {

			return '';
		}

		$screen_title = $this->get_course_post()->post_title;

		$back_button = $this->back_button_html( array(
			'cws_screen' => 'course_landing',
			'cws_course' => '0',
		), __( 'Courses', 'woothemes-sensei' ) );

		ob_start();

		$this->link_button(
			'<i class="dashicons dashicons-edit"></i>',
			array(
				'cws_screen' => 'course_title',
				'cws_next_action' => 'edit',
			),
			'cws-no-ajax', // No AJAX for Featured image JS to work!
			__( 'Edit' )
		);

		$edit_button = ob_get_clean();

		$module_landing_title = $back_button . ' ' . $edit_button . ' ' . $screen_title;

		return apply_filters( 'cws_module_landing_title', $module_landing_title );
	}


	/**
	 * Module Landing screen output
	 *
	 * @access  protected
	 * @since   1.0.0
	 *
	 * @uses $this->module_order()
	 * @uses $this->module_add()
	 * @uses $this->course_publish()
	 */
	protected function module_landing() {

		if ( ! $this->course ) {

			$this->not_found_error( __( 'Course', 'woothemes-sensei' ) );

			return;
		}

		$this->screen_title = $this->get_module_landing_title();

		$this->module_add();

		$this->modules_order();

		$this->course_publish();
	}


	/**
	 * Publish Course button
	 *
	 * @access  protected
	 * @since 1.6.1
	 */
	protected function course_publish() {

		if ( ! $this->course ||
			'publish' === get_post_status( $this->course ) ) {

			return;
		}

		$this->form( array(
			'cws_action' => 'publish',
		) );

		$this->submit_button(
			__( 'Publish' ),
			'publish',
			'button-primary'
		);
	}


	/**
	 * Module Add screen output
	 *
	 * @access  protected
	 * @since   1.0.0
	 *
	 * @uses $this->module_select()
	 */
	protected function module_add() {
		?>
		<h3>
			<?php _e( 'Modules', 'woothemes-sensei' ); ?>
			<?php
			$this->add_new_button( $this->get_screen_url( array(
				'cws_screen' => 'module_title',
			) ) );
			?>
		</h3>
		<?php

		$this->form( array(
			'cws_action' => 'add',
		) );

		// No existing modules selection, only add new.
		// $this->module_select();

		// $this->add_button();
	}


	/**
	 * Module Select screen output.
	 *
	 * @access  protected
	 * @since   1.0.0
	 */
	protected function module_select() {

		$modules = $this->get_modules( 'exclude' );

		$this->select(
			'cws_module',
			$modules,
			$this->module,
			'cws-select2',
			__( 'Module', 'woothemes-sensei' )
		);
	}


	/**
	 * Modules Order screen output.
	 *
	 * @access  protected
	 * @since   1.0.0
	 *
	 * @see Sensei_Core_Modules::module_order_screen()
	 */
	protected function modules_order() {
		if ( ! $this->course ) {

			$this->not_found_error( __( 'Course', 'woothemes-sensei' ) );

			return;
		}

		$modules = Sensei()->modules->get_course_modules( $this->course );

		// Already appending Teacher name by default.
		// $modules = Sensei()->modules->append_teacher_name_to_module( $modules, array( 'module' ), array() );

		if ( count( $modules ) <= 0 ) {
			return;
		}

		$modules = $this->append_empty_to_modules_with_no_lessons( $modules );

		$order = Sensei()->modules->get_course_module_order( $this->course );

		$order_string = '';

		if ( $order ) {
			$order_string = implode(',', $order );
		}
		?>
		<h3><?php _e( 'Order Modules', 'woothemes-sensei' ); ?></h3>

		<?php
		$this->form( array(
			'cws_action' => 'order',
			'cws_ajax_silent' => '1',
		) );
		?>
		<!--<form id="editgrouping" method="post" action="" class="validate">-->
			<ul class="sortable-module-list">
				<?php
				$this->order_item_list(
					'module',
					$modules,
					array(
						'lesson_landing' => __( 'Lessons', 'woothemes-sensei' ),
						'module_title' => __( 'Edit' ),
					)
				);
				?>
			</ul>

			<input type="hidden" name="module-order" value="<?php echo esc_attr( $order_string ); ?>" />
			<!--<input type="hidden" name="course_id" value="<?php echo esc_attr( $this->course ); ?>" />
			<input type="submit" class="button-primary"
				   value="<?php echo esc_attr( __( 'Save module order', 'woothemes-sensei' ) ); ?>" />-->
		<?php
	}


	/**
	 * Format module name: append (empty) to modules with no lessons
	 *
	 * @since 1.5.2
	 *
	 * @param array $modules
	 *
	 * @return array Formatted modules.
	 */
	public function append_empty_to_modules_with_no_lessons( $modules ) {

		global $wp_query;

		$formatted_modules = array();

		// Add (empty) mention if module does not have any lesson.
		foreach ( $modules as $module ) {

			if ( ! empty( $module->post_title ) ) {

				// Is session, skip.
				$formatted_modules[] = $module;

				continue;
			}

			// Fix when lesson inside module have "Draft" status.
			$wp_query->is_preview = true;

			if ( Sensei()->modules->get_lessons( $this->course, $module->term_id ) ) {

				// Module has lessons, skip.
				$formatted_modules[] = $module;

				continue;
			}

			$module->name .= ' <small><i>' . __( '(empty)', 'course-wizard-for-sensei' ) . '</i></small>';

			$formatted_modules[] = $module;
		}

		return $formatted_modules;
	}
}
