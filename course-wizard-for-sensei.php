<?php
/**
 * Plugin Name: Course Wizard For Sensei
 * Version: 1.7.2
 * Plugin URI: http://git.open-dsi.fr/wordpress-plugin/course-wizard-for-sensei
 * Description: Wizard to easily design and edit courses for Sensei LMS.
 * Author: Open-DSI
 * Author URI: https://www.open-dsi.fr/
 * Requires at least: 4.7
 * Tested up to: 5.0.3
 *
 * Text Domain: course-wizard-for-sensei
 * Domain Path: /lang/
 *
 * @package Course Wizard for Sensei
 * @author Open-DSI
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Load plugin class files.
require_once 'includes/class-course-wizard-for-sensei.php';
// require_once 'includes/class-course-wizard-for-sensei-settings.php';
require_once 'includes/class-course-wizard-for-sensei-controller.php';
require_once 'includes/class-course-wizard-for-sensei-page.php';
require_once 'includes/class-course-wizard-for-sensei-form.php';
require_once 'includes/class-course-wizard-for-sensei-screen.php';
require_once 'includes/class-course-wizard-for-sensei-screen-course.php';
require_once 'includes/class-course-wizard-for-sensei-screen-module.php';
require_once 'includes/class-course-wizard-for-sensei-screen-lesson.php';
require_once 'includes/class-course-wizard-for-sensei-screen-question.php';
require_once 'includes/class-course-wizard-for-sensei-duplicate.php';

// Load plugin libraries.
// require_once 'includes/lib/class-course-wizard-for-sensei-admin-api.php';
// require_once 'includes/lib/class-course-wizard-for-sensei-post-type.php';
// require_once 'includes/lib/class-course-wizard-for-sensei-taxonomy.php';

/**
 * Returns the main instance of Course_Wizard_For_Sensei_Controller to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Course_Wizard_For_Sensei_Controller
 */
function course_wizard_for_sensei() {
	$instance = Course_Wizard_For_Sensei_Controller::instance( __FILE__, '1.7.2' );

	/*if ( is_null( $instance->settings ) ) {
		$instance->settings = Course_Wizard_For_Sensei_Settings::instance( $instance );
	}*/

	return $instance;
}

course_wizard_for_sensei();
