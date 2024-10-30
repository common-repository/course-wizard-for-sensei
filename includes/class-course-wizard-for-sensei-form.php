<?php
/**
 * Course Wizard for Sensei Form
 *
 * @package Course Wizard for Sensei
 */

defined( 'ABSPATH' ) || exit;

/**
 * Course Wizard for Sensei Screen class
 */
class Course_Wizard_For_Sensei_Form {
	/**
	 * Title input
	 *
	 * @access  protected
	 * @since   1.0.0
	 *
	 * @param string $object_name Object name.
	 * @param string $value       Input value.
	 * @param string $name        Input name, defaults to 'post_title'.
	 */
	protected function title( $object_name, $value = '', $name = 'post_title' ) {
		// Autofocus when new title.
		$maybe_autofocus = ( '' === $value ? 'autofocus' : '' );

		// Translators: %s is object name: Course, module, lesson, question...
		$input_placeholder = sprintf( __( 'Enter a title for this %s here', 'course-wizard-for-sensei' ), $object_name );
		?>
		<div id="titlediv">
			<div id="titlewrap">
				<input name="<?php echo esc_attr( $name ); ?>" size="30" value="<?php echo esc_attr( $value ); ?>"
					   id="title" spellcheck="true" autocomplete="off" type="text" required
					   placeholder="<?php echo esc_attr( $input_placeholder ); ?>"
					   <?php echo $maybe_autofocus; ?>>
			</div>
		</div>
		<?php
	}


	/**
	 * Slug input
	 *
	 * @param string $value Input value.
	 * @param string $name  Input name, defaults to 'slug'.
	 */
	protected function slug( $value = '', $name = 'slug' ) {

		?>
		<div class="form-field term-slug-wrap">
			<label for="tag-slug"><?php _e( 'Slug' ); ?></label>
			<input name="<?php echo esc_attr( $name ); ?>" id="tag-slug" type="text" autocomplete="off"
				value="<?php echo esc_attr( $value ); ?>" size="40" />
			<p><?php _e( 'The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.' ); ?></p>
		</div>
		<?php
	}


	/**
	 * Description input
	 * WP Editor for posts or textarea for terms.
	 *
	 * @access  protected
	 * @since   1.0.0
	 *
	 * @link https://developer.wordpress.org/reference/functions/wp_editor/
	 *
	 * @param object $post_or_term Post or term object.
	 */
	protected function description( $post_or_term ) {

		if ( ! is_a( $post_or_term, 'WP_Post' ) ) {

			// Term.
			?>
			<div class="form-field term-description-wrap">
				<label for="tag-description"><?php _e( 'Description' ); ?></label><br />
				<textarea name="description" id="tag-description" rows="5" cols="40"><?php echo esc_textarea( $post_or_term->description ); ?></textarea>
				<p><?php _e( 'The description is not prominent by default; however, some themes may show it.' ); ?></p>
			</div>
			<?php
			return;
		}

		// Post.
		// Fix WordPress editor AJAX: old code.
		/*add_filter( 'wp_default_editor', function() {
			return 'tinymce';
		} );
		?>
		<div class="cws-editor-wrapper" id="cws-editor-wrapper">
			<?php
			wp_editor(
				$post_or_term->post_content,
				'cws-editor-' . time(),
				array(
					'editor_class' => 'cws-editor',
					'textarea_name' => 'cws-editor',
				)
			);
			?>
		</div>
		<?php */
		?>
		<input type="hidden" name="cws-editor" id="cws-editor"
			value="<?php echo esc_attr( $post_or_term->post_content ); ?>" />
		<?php
	}


	/**
	 * Select input
	 *
	 * @since 1.0.0
	 *
	 * @param string $name  Name, use post type name.
	 * @param array  $posts Posts array( 'id' => 1, 'title' => 'My course title' ).
	 * @param string $class CSS classes.
	 * @param string $value Selected value (post ID).
	 */
	protected function select( $name, $posts, $value = '', $class = '', $placeholder = '' ) {

		// Is $_GET var?
		$is_get_var = property_exists( $this, str_replace( 'cws_', '', $name ) );

		if ( $is_get_var ) {

			$class .= ' cws-is-get-var';
		}

		if ( $placeholder ) {

			array_unshift( $posts, array(
				'id' => '',
				// Translators: %s is an object (Course, Lesson, Question, Module...).
				'title' => sprintf( __( 'Select an existing %s', 'course-wizard-for-sensei' ), $placeholder ),
			) );
		}
		?>
		<div>
			<?php /*if ( $label ) : ?>
				<label for="cws-select-post"><?php echo esc_html( $label ); ?></label><br />
			<?php endif;*/ ?>
			<select name="<?php echo esc_attr( $name ); ?>"
					class="<?php echo esc_attr( $class ); ?>"
					id="cws-select-post" required="required"
					autocomplete="off">
				<?php foreach ( $posts as $post ) { ?>
					<option value="<?php echo esc_attr( $post['id'] ); ?>"
						<?php selected( $post['id'], $value, true ); ?>><?php echo esc_html( $post['title'] ); ?></option>
				<?php } ?>
			</select>
		</div>
		<?php
	}


	/**
	 * Checkbox input
	 *
	 * @param string $name  Input name.
	 * @param string $label Checkbox label.
	 * @param string $title Label title (on mouse over), optional.
	 */
	protected function checkbox( $name, $label, $title = '' ) {

		?>
		<div>
			<label title="<?php echo esc_attr( $title ); ?>">
				<input type="checkbox"
					name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>"
					value="1" checked="checked" />
				<?php echo esc_html( $label ); ?>
			</label>
		</div>
		<?php
	}


	/**
	 * Featured image
	 *
	 * @uses _wp_post_thumbnail_html
	 *
	 * Use .cws-no-ajax CSS class on links taking to a page having Featured image
	 * So JS is loaded.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $post_type Post type.
	 */
	protected function featured_image( $post_id, $post_type ) {

		// @see edit-form-advanced.php
		$thumbnail_support = current_theme_supports( 'post-thumbnails', $post_type ) && post_type_supports( $post_type, 'thumbnail' );

		if ( ! $thumbnail_support ||
			! current_user_can( 'upload_files' ) ) {

			return;
		}

		// All meta boxes should be defined and added before the first do_meta_boxes() call (or potentially during the do_meta_boxes action).
		require_once ABSPATH . 'wp-admin/includes/meta-boxes.php';

		if ( ! $post_id ) {
			// New post.
			$post = get_default_post_to_edit( $post_type, true );

			// Fix PHP error. Add filter so get_post( $post ) returns our post!
			$post->filter = 'raw';
		} else {
			$post = get_post( $post_id );
		}

		$thumbnail_id = get_post_meta( $post->ID, '_thumbnail_id', true );

		if ( ! $thumbnail_id ) {

			$thumbnail_id = -1;
		}

		$post_type_object = get_post_type_object( $post_type );

		$title = $post_type_object->labels->featured_image;
		?>
		<script>
			jQuery( document ).ready(function( e ) {
				wp.media.view.settings.post.id = <?php echo json_encode( $post->ID ); ?>;
				wp.media.view.settings.post.featuredImageId = <?php echo json_encode( $thumbnail_id ); ?>;
				wp.media.view.settings.post.nonce = <?php echo json_encode( wp_create_nonce( 'update-post_' . $post->ID ) ); ?>;
			});
		</script>
		<div id="poststuff" style="min-width: 320px;">
			<div id="postimagediv" class="postbox">
				<h2 class='hndle'><span><?php echo esc_html( $title ); ?></span></h2>
				<div class="inside">
				<?php
					echo _wp_post_thumbnail_html( $thumbnail_id, $post );
				?>
				</div>
			</div>
		</div>
		<?php
	}


	/**
	 * Course Teacher meta box
	 *
	 * @since 1.7.0
	 *
	 * @uses Sensei_Teacher::teacher_meta_box_content
	 *
	 * @param object $post Course Post.
	 */
	protected function course_teacher( $post ) {

		if ( empty( $post ) ) {

			// New Course, set Post author to current user!
			$post = new WP_Post( (object) array( 'post_author' => get_current_user_id() ) );
		}

		// All meta boxes should be defined and added before the first do_meta_boxes() call (or potentially during the do_meta_boxes action).
		require_once ABSPATH . 'wp-admin/includes/meta-boxes.php';

		?>
		<div id="poststuff" style="min-width: 320px;">
			<div id="sensei-teacher" class="postbox">
				<h2 class='hndle'><span><?php echo esc_html( __( 'Teacher', 'woothemes-sensei' ) ); ?></span></h2>
				<div class="inside">
				<?php
					Sensei()->teacher->teacher_meta_box_content( $post );
				?>
				</div>
			</div>
		</div>
		<?php
	}


	/**
	 * Previous button
	 *
	 * @since 1.0.0
	 *
	 * @param string $id    Input name and ID.
	 * @param string $class CSS classes, use 'primary for blue button.
	 */
	protected function previous_button( $id = 'previous', $class = '' ) {
		return;
		?>
		<input type="button" value="<?php esc_attr_e( 'Previous', 'course-wizard-for-sensei' ); ?>"
			   onclick="history.back();"
			   id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>"
			   class="<?php echo esc_attr( 'cws-button button button-large ' . $class ); ?>" />
		<?php
	}

	/**
	 * Submit button
	 *
	 * @since 1.0.0
	 *
	 * @param string $text  Button text.
	 * @param string $id    Input name and ID.
	 * @param string $class CSS classes, use 'primary for blue button.
	 */
	protected function submit_button( $text, $id = 'submit', $class = '' ) {
		?>
		<input type="submit" value="<?php echo esc_attr( $text ); ?>"
			   id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>"
			   class="<?php echo esc_attr( 'cws-button button button-large ' . $class ); ?>" />
		<?php
	}


	/**
	 * Submit button
	 *
	 * @since 1.0.0
	 *
	 * @param string $text     Button text.
	 * @param array  $get_vars $_GET vars.
	 * @param string $class    CSS classes, use 'primary for blue button.
	 */
	protected function link_button( $text, $get_vars = array(), $class = '', $title = '' ) {
		$link_url = $this->get_screen_url( $get_vars );
		?>
		<a href="<?php echo esc_url( $link_url ); ?>"
			class="<?php echo esc_attr( 'cws-link cws-button button button-large ' . $class ); ?>"
			title="<?php echo esc_attr( $title ); ?>"
			><?php echo wp_kses_post( $text ); ?></a>
		<?php
	}


	/**
	 * Next submit button
	 *
	 * @since 1.0.0
	 *
	 * @param string $id    Input name and ID.
	 */
	protected function next_button( $id = 'submit' ) {
		$this->submit_button(
			__( 'Next' ),
			$id,
			'button-primary cws-next-button'
		);
	}


	/**
	 * Add submit button
	 *
	 * @since 1.0.0
	 */
	protected function add_button() {
		$this->submit_button(
			__( 'Add' ),
			'add',
			'button-primary'
		);
	}


	/**
	 * Back button HTML
	 *
	 * @since 1.0.0
	 *
	 * @param array  $get_vars $_GET vars.
	 * @param string $title    Button title.
	 *
	 * @return string
	 */
	protected function back_button_html( $get_vars = array(), $title = '' ) {
		$back_url = $this->get_screen_url( $get_vars );

		ob_start(); ?>
		<a href="<?php echo esc_url( $back_url ); ?>" class="cws-button button cws-link cws-back-button"
			title="<?php echo esc_attr( $title ); ?>">
			<i class="dashicons dashicons-arrow-left-alt2"></i>
		</a>
		<?php

		return ob_get_clean();
	}

	/**
	 * Add New button
	 *
	 * @since 1.0.0
	 *
	 * @param string $href  button link href.
	 * @param string $class CSS class.
	 */
	protected function add_new_button( $href, $class = '' ) {
		?>
		<a href="<?php echo esc_url( $href ); ?>" class="cws-link page-title-action <?php echo esc_attr( $class ); ?>">
			<?php esc_html_e( 'Add New', 'course-wizard-for-sensei' ); ?>
		</a>
		<?php
	}


	/**
	 * Form
	 * Open and automatically close previous forms.
 	 * Call $this->form( false ) to close last form.
	 *
	 * @param string|array|bool $action Form action URL or $_GET vars to add or false to only close form.
	 */
	protected function form( $action = '', $class = '' ) {

		static $open_form = false;

		if ( $open_form ) {
			?>
			</form>
			<?php
		}

		if ( false === $action ) {

			return;
		}

		if ( is_array( $action ) ) {

			$action = $this->get_screen_url( $action );
		} elseif ( ! $action ) {

			$action = $this->get_screen_url();
		}

		?>
		<form method="post" action="<?php echo esc_url( $action ); ?>" class="cws-form <?php echo esc_attr( $class ); ?>">
		<?php

		// Add nonce.
		wp_nonce_field( 'cws_form_action', 'cws_nonce' );

		$open_form = true;
	}


	/**
	 * Next screen form
	 *
	 * @param array $get_vars $_GET vars.
	 */
	protected function form_next( $get_vars = array(), $class = '' ) {

		$action = $this->get_next_screen_url( $get_vars );

		$this->form( $action, $class );
	}


	/**
	 * Output Form hidden fields
	 *
	 * @param array $hidden_vars Hidden variables.
	 */
	protected function form_hidden_fields( $hidden_vars = array() ) {

		// Add hidden variables.
		foreach ( $hidden_vars as $key => $value ) {
			if ( ! $key ) {
				continue;
			}
			?>
			<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
			<?php
		}
	}


	/**
	 * Order item CSS classes
	 *
	 * @param string $object_name   Object name (module, lesson, question).
	 * @param int    $objects_count Objects count.
	 *
	 * @return string CSS classes.
	 */
	protected function order_item_class( $object_name, $objects_count ) {

		static $count = 0;

		$count++;

		$class = 'cws-order-item ' . $object_name;

		if ( 1 == $count ) {
			$class .= ' first';
		}

		if ( $objects_count == $count ) {
			$class .= ' last';
		}

		if ( 0 != $count % 2 ) {
			$class .= ' alternate';
		}

		return $class;
	}


	/**
	 * Order list items output.
	 * Sortable items + Remove link, + custom links.
	 *
	 * @param string $object_name Object name.
	 * @param array $items        Array of item objects.
	 * @param array $screen_links Array of: screen_name => Label.
	 */
	protected function order_item_list( $object_name, $items, $screen_links ) {
		foreach ( (array) $items as $item ) :

			$item_id = isset( $item->ID ) ? $item->ID : $item->term_id;

			$item_name = isset( $item->post_title ) ? $item->post_title : $item->name;

			$class = $this->order_item_class( $object_name, count( $items ) );

			?>
			<li class="<?php echo esc_attr( $class ); ?>">
				<span rel="<?php echo esc_attr( $item_id ); ?>" class="name"><?php echo wp_kses_post( $item_name ); ?></span>
				<?php
				if ( ! empty( $item->extra_html ) ) {

					// Add extra HTML after name <span> and before order links.
					echo $item->extra_html;
				}

				if ( empty( $item->cws_order_no_links ) ) {
					$this->order_item_links( $object_name, $item_id, $screen_links );
				}
				?>
			</li>
		<?php
		endforeach;
	}


	/**
	 * Order item links:
	 * Screen links + remove link.
	 *
	 * @param string $object_name  Object name.
	 * @param int    $item_id      Item ID.
	 * @param array  $screen_links Array of: screen_name => Info (Label or array containing label [and dashicon [and class]]).
	 */
	protected function order_item_links( $object_name, $item_id, $screen_links ) {

		$remove_url = '';

		if ( ! isset( $screen_links['remove'] ) || $screen_links['remove'] ) {

			$remove_url = $this->get_screen_url(
				array(
					'cws_action' => 'remove',
					'cws_' . $object_name => $item_id,
				)
			);
		}

		if ( isset( $screen_links['remove'] ) ) {
			unset( $screen_links['remove'] );
		}

		foreach ( $screen_links as $screen_key => $screen_info ) :
			$screen_url = $this->get_screen_url(
				array(
					'cws_screen' => $screen_key,
					'cws_' . $object_name => $item_id,
				)
			);

			$class = 'cws-order-edit-link';
			$dashicon = 'edit';

			if ( false === strpos( $screen_key, $object_name ) ) {
				$class = 'cws-order-items-link';

				$dashicon = 'arrow-right-alt2';
			}

			// Screen info IS screen label.
			$screen_label = $screen_info;

			if ( is_array( $screen_info ) ) {
				// Screen info contains label and dashicon and class.
				if ( isset( $screen_info['label'] ) ) {

					$screen_label = $screen_info['label'];
				}

				if ( isset( $screen_info['dashicon'] ) ) {

					$dashicon = $screen_info['dashicon'];
				}

				if ( isset( $screen_info['class'] ) ) {

					$class = $screen_info['class'];
				}

				if ( isset( $screen_info['url'] ) ) {

					$screen_url = $screen_info['url'];
				}
			}
			?>
			<a href="<?php echo esc_url( $screen_url ); ?>"
			   class="cws-button button cws-link cws-order-link <?php echo esc_attr( $class ); ?>"
			   title="<?php echo esc_attr( $screen_label ); ?>">
				<i class="dashicons dashicons-<?php echo esc_attr( $dashicon ); ?>"></i>
			</a>
		<?php endforeach;

		if ( $remove_url ) :
		?>
			<a href="<?php echo esc_url( $remove_url ); ?>" class="cws-button button cws-link cws-order-remove-link"
			   title="<?php esc_attr_e( 'Remove' ); ?>">
				<i class="dashicons dashicons-no-alt"></i>
			</a>
		<?php
		endif;
	}


	/**
	 * Notice HTML output
	 *
	 * @param string $message Message.
	 * @param string $level   Level: info, success, error or warning.
	 */
	public function notice_html( $message, $level = '' ) {

		$level_class = '';

		if ( $level && 'notice' !== $level ) {
			$level_class = 'notice-' . $level;
		}
		?>
		<div class="notice <?php echo esc_attr( $level_class ); ?>">
			<p><?php echo wp_kses_post( $message ); ?></p>
		</div>
		<?php
	}


	/**
	 * Not Found error output
	 *
	 * @param string $object_name Object Name.
	 */
	protected function not_found_error( $object_name ) {

		// The Course Wizard URL.
		$wizard_url = admin_url( 'edit.php?post_type=course&page=course-wizard' );

		$start_again_link = ' <a href="' . esc_url( $wizard_url ) . '" class="cws-link">' .
			__( 'Start again?', 'course-wizard-for-sensei' ) . '</a>';


		$back_link = ' <a href="#" id="cws-back-link" class="cws-link">' .
			esc_html__( 'Previous' ) . '</a>';

		$message = sprintf(
			// translators: %s is object name (course, module, lesson, question).
			__( '%s not found.', 'course-wizard-for-sensei' ),
			$object_name
		);

		$this->notice_html( $message . $back_link, 'error' );

		?>
		<script>
			document.getElementById( 'cws-back-link' ).href = cwsPreviousUrl;
		</script>
		<?php
	}


	/**
	 * Action error output
	 *
	 * @param string $object_title Object Title.
	 * @param string $action       Action, defaults to $this->action.
	 * @return string Action title.
	 */
	protected function action_title( $object_title, $action = '' ) {

		if ( ! $action ) {

			$action = $this->action;
		}

		if ( 'new' === $action ) {

			// Translators: %s is object name (course, module, lesson, question...).
			$action_name = __( 'Add New %s', 'course-wizard-for-sensei' );

		} elseif ( 'edit' === $action ) {

			// Translators: %s is object name (course, module, lesson, question...).
			$action_name = __( 'Edit %s', 'woothemes-sensei' );

		} elseif ( 'duplicate' === $action ) {

			// Translators: %s is object name (course, module, lesson, question...).
			$action_name = __( 'Duplicate %s', 'course-wizard-for-sensei' );
		} elseif ( 'save' === $action ) {

			// Translators: %s is object name (course, module, lesson, question...).
			$action_name = __( 'Save %s', 'woothemes-sensei' );
		} elseif ( 'add' === $action ) {

			// Translators: %s is object name (course, module, lesson, question...).
			$action_name = __( 'Add %s', 'woothemes-sensei' );
		}

		return sprintf( $action_name, $object_title );
	}


	/**
	 * Action error output
	 *
	 * @param string $object_title  Object Title.
	 * @param string $action        Action, defaults to $this->action.
	 * @param string $error_message Error message.
	 */
	protected function action_error( $object_title, $action = '', $error_message = '' ) {

		$action_title = $this->action_title( $object_title, $action );

		$message = sprintf(
			// translators: %s is action name (save, edit, remove).
			__( 'Action error: %s', 'course-wizard-for-sensei' ),
			$action_title
		);

		if ( $error_message ) {
			$message .= '<br />' . $error_message;
		}

		$this->notice_html( $message, 'error' );
	}
}
