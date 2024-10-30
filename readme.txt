=== Course Wizard for Sensei ===
Contributors: opendsi
Tags: sensei, course, wizard, lesson, module, question, quiz, duplicate, creation, edition, preview, intuitive
Requires at least: 4.7
Tested up to: 5.0.3
Stable tag: 1.7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily design and edit courses with this Wizard for Sensei LMS.

== Description ==

Create, duplicate and edit Sensei courses, modules, lessons and questions. Instead of having to navigate between 5 or 6 screens full of options, the Wizard will help you design courses all within the same, intuitive interface. Last but not least, your modifications will show instantly on the right side of the wizard screen so you can preview your course, just how students will take it.

Made for the [Sensei](https://woocommerce.com/products/sensei/) Learning Management System plugin for WordPress.

== Installation ==

Installing "Course Wizard For Sensei" can be done either by searching for "Course Wizard For Sensei" via the "Plugins > Add New" screen in your WordPress dashboard, or by using the following steps:

0. Check the 'Sensei' plugin is active through the 'Plugins' menu in WordPress or install it
1. Download the plugin via WordPress.org
2. Upload the ZIP file through the 'Plugins > Add New > Upload' screen in your WordPress dashboard
3. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. Course Wizard main screen. Select an existing course to edit or duplicate, or create a new one.
2. Modules screen. Reorder, edit, remove modules from the list, or add a new one. Edit course. Preview your course on the right.
3. Lesson content screen. Edit your lesson content and preview it.
4. Lessons screen. Reorder, edit or remove modules from the list, or add (a new or existing) one.
5. Question screen. Edit your question content. Preview your quiz on the right.
6. Course Wizard on smartphone.
7. Previewing a course from the wizard on smartphone. Note the preview icon on the top right corner.


== Frequently Asked Questions ==

= Does this plugin depend on any others? =

Yes. It depends on the [Sensei](https://woocommerce.com/products/sensei/) plugin. It was tested with Sensei 1.11.0.


= Does this create new database tables? =

No. There are no new database tables with this plugin.


= Does this load additional JS or CSS files ? =

Yes. It loads the `admin.css`, `admin.min.js`, and `lesson-metadata.min.js` files on the admin side only.


= Can I use the Wizard from my tablet or smartphone? =

Yes. The Wizard interface is responsive and should work on modern tablets and smartphone browsers.


= Is the plugin compatible with WordPress Multisite (MU)? =

Yes. The Wizard plugin was successfully tested on WordPress Multisite. You will have to activate the plugin separately for each site.


= How can I create a Quiz? =

In the wizard, once on the Lessons screen, click on the right arrow next to the desired lesson in the list. This will bring you to the Questions screen. To create a Quiz, simply add a new Question to your lesson.


= I want to enroll students to my course, can I do it with the Wizard? =

Yes, you can create new student users and enroll them at once with the Learner extension to the Course Wizard plugin (coming soon!).


= Is the plugin translated? =

Yes. It is translated in French (fr_FR).
You will find the translation files in the `lang/` folder.
New translations are welcome at https://translate.wordpress.org/projects/wp-plugins/course-wizard-for-sensei


= Where can I get support? =

Buy support packs at https://www.open-dsi.fr


== Changelog ==

= 1.7.2 =
* 2018.10.25
* Fix Sensei plugin detection (now detects sensei/woothemes-sensei.php)

= 1.7.1 =
* 2018-10-11
* Fix auto grade quiz checkbox.

= 1.7.0 =
* 2018-09-20
* Add and Save Course Teacher.

= 1.6.2 =
* 2018-09-19
* New and Duplicated Course now has "Draft" status.
* Add Publish Course button.
* Show "Draft" lessons in order list.

= 1.5.3 =
* 2018-09-18
* Fix do not prompt user when removing a course session.
* Append (empty) to module with no lessons.

= 1.5.0 =
* 2018-09-14
* Ask if user wants to delete Module and its Lessons on Remove button click.

= 1.4.0 =
* 2018-07-27
* Duplicate questions when duplicating a course and its lessons.

= 1.3.0 =
* 2018-07-06
* Duplicate modules when duplicating a course and its lessons.

= 1.2.1 =
* 2018-06-28
* Fix prevent Sensei from removing module courses.

= 1.2.0 =
* 2018-05-31
* Add Quiz Settings.

= 1.1.3 =
* 2018-05-30
* JS Fix form data serialize when no AJAX.

= 1.1.2 =
* 2018-05-29
* JS fix: No AJAX for Featured image JS to work! (on duplicate course).

= 1.1.1 =
* 2018-05-28
* Fix Quiz graded auto type: add missing meta keys.

= 1.1.0 =
* 2018-04-13
* Fix Save WordPress editor content when "Text" tab is active.
* Ask if user wants to delete Lesson on Remove button click.

= 1.0.0 =
* 2018-03-28
* Initial release
