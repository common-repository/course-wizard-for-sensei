jQuery.fn.exists = function(){return this.length>0;}

function cwsSenseiLessonMetadataReady() {

  /**
   * Gap Fill text change events
   *
   * @since 1.3.0
   * @access public
   */
  jQuery( 'input.gapfill-field' ).each( function() {
    // Handles change events like paste, tabbing, and click change selectors
    jQuery( this ).change(function() {
      var gapPre = jQuery( this ).parent('div').find('input[name=add_question_right_answer_gapfill_pre]').val();
      var gapGap = jQuery( this ).parent('div').find('input[name=add_question_right_answer_gapfill_gap]').val();
      var gapPost = jQuery( this ).parent('div').find('input[name=add_question_right_answer_gapfill_post]').val();
      jQuery( this ).parent('div').find('p.gapfill-preview').html( gapPre + ' <u>' + gapGap + '</u> ' + gapPost );
    });
    // Handles the pressing up of the key, general typing
    jQuery( this ).keyup(function() {
      var gapPre = jQuery( this ).parent('div').find('input[name=add_question_right_answer_gapfill_pre]').val();
      var gapGap = jQuery( this ).parent('div').find('input[name=add_question_right_answer_gapfill_gap]').val();
      var gapPost = jQuery( this ).parent('div').find('input[name=add_question_right_answer_gapfill_post]').val();
      jQuery( this ).parent('div').find('p.gapfill-preview').html( gapPre + ' <u>' + gapGap + '</u> ' + gapPost );
    });
  });

  /**
   * Quiz grade type checkbox change event
   *
   * @since 1.3.0
   * @access public
   */
  jQuery( '#add-quiz-metadata' ).on( 'change', '#quiz_grade_type', function() {
    jQuery.fn.saveQuizGradeType();
  });

  /***************************************************************************************************
   * 	3 - Load Chosen Dropdowns.
   ***************************************************************************************************/

  // Lessons Write Panel
  if ( jQuery( '#lesson-complexity-options' ).exists() ) { jQuery( '#lesson-complexity-options' ).select2({width:"resolve"}); }
  if ( jQuery( '#lesson-prerequisite-options' ).exists() ) { jQuery( '#lesson-prerequisite-options' ).select2({width:"resolve"}); }
  if ( jQuery( '#lesson-course-options' ).exists() ) { jQuery( '#lesson-course-options' ).select2({width:"resolve"}); }
  if ( jQuery( '#course-prerequisite-options' ).exists() ) { jQuery( '#course-prerequisite-options' ).select2({width:"resolve"}); }
  if ( jQuery( '#lesson-course-details #course-category-options' ).exists() ) { jQuery( '#lesson-course-details #course-category-options' ).select2({width:"resolve"}); }
  if ( jQuery( 'select#course-woocommerce-product-options' ).exists() ) { jQuery( 'select#course-woocommerce-product-options' ).select2({width:"resolve"}); }

  // Quiz edit panel
  if ( jQuery( '#add-question-type-options' ).exists() ) { jQuery( '#add-question-type-options' ).select2({width:"resolve"}); }
  if ( jQuery( '#add-question-category-options' ).exists() ) { jQuery( '#add-question-category-options' ).select2({width:"resolve"}); }
  if ( jQuery( '#add-multiple-question-options' ).exists() ) { jQuery( '#add-multiple-question-options' ).select2({width:"resolve"}); }

  // Courses Write Panel
  if ( jQuery( '#course-wc-product #course-woocommerce-product-options' ).exists() ) { jQuery( '#course-woocommerce-product-options' ).select2({width:"resolve"}); }
  if ( jQuery( '#add-multiple-question-category-options' ).exists() ) { jQuery( '#add-multiple-question-category-options' ).select2({width:"resolve"}); }

  // Sensei Settings Panel
  jQuery( 'div.woothemes-sensei-settings form select' ).each( function() {
    if ( !jQuery( this ).hasClass( 'range-input' ) ) {
      jQuery( this ).select2({width:"resolve"});
    } // End If Statement
  });

  /***************************************************************************************************
   * 	4 - Course Functions.
   ***************************************************************************************************/

  /**
   * Add Course Click Event.
   *
   * @since 1.0.0
   * @access public
   */
  // Hide the add course panel
  jQuery( '#lesson-course-details' ).addClass( 'hidden' );
  // Display on click
  jQuery( '#lesson-course-add' ).click( function() {
    // Display the add course panel and hide the add course link
    jQuery( '#lesson-course-actions' ).hide();
    jQuery( '#lesson-course-details' ).removeClass( 'hidden' );
  });

  /**
   * Cancel Events Click Event - add course.
   *
   * @since 1.0.0
   * @access public
   */
  jQuery( '#lesson-course-details p' ).on( 'click', 'a.lesson_course_cancel', function() {
    // Hide the add course panel and show the add course link
    jQuery( '#lesson-course-actions' ).show();
    jQuery( '#lesson-course-details' ).addClass( 'hidden' );
  });

  /**
   * Save Course Click Event - Ajax.
   *
   * @since 1.0.0
   * @access public
   */
  jQuery( '#lesson-course-details p' ).on( 'click', 'a.lesson_course_save', function() {
    // Validate Inputs
    var validInput = jQuery.fn.validateCourseInput();
    if ( validInput ) {

      // Setup data
      var dataToPost = '';
      dataToPost += 'course_prerequisite' + '=' + jQuery( '#course-prerequisite-options' ).val();
      dataToPost += '&course_woocommerce_product' + '=' + jQuery( '#course-woocommerce-product-options' ).val();
      dataToPost += '&course_category' + '=' + jQuery( '#course-category-options' ).val();
      dataToPost += '&course_title' + '=' + encodeURIComponent( jQuery( '#course-title' ).attr( 'value' ) );
      dataToPost += '&course_content' + '=' + encodeURIComponent( jQuery( '#course-content' ).attr( 'value' ) );
      dataToPost += '&action=add';
      // Perform the AJAX call.
      jQuery.post(
        ajaxurl,
        {
          action : 'lesson_add_course',
          lesson_add_course_nonce : woo_localized_data.lesson_add_course_nonce,
          data : dataToPost
        },
        function( response ) {

          // Check for a course id
          if ( 0 < response ) {
            jQuery( '#lesson-course-actions' ).show();
            jQuery( '#lesson-course-details' ).addClass( 'hidden' );
            jQuery( '#lesson-course-options' ).append(jQuery( '<option></option>' ).attr( 'value' , response ).text(  jQuery( '#course-title' ).attr( 'value' ) ) );
            jQuery( '#lesson-course-options' ).val( response).trigger("change");;
          } else {
            // TODO - course creation fail message
          }
        }
      );
      return false; // TODO - move this below the next bracket when doing the ajax loader
      //});
    } else {
      jQuery( '#course-title' ).focus();
      // TODO - add error message
    }
  });

  /***************************************************************************************************
   * 	5 - Quiz Question Functions.
   ***************************************************************************************************/

  /**
   * Add Question Click Event.
   *
   * @since 1.0.0
   * @access public
   */
  jQuery( '#add-question-actions' ).on( 'change', 'select.question-type-select', function() {
    // Show the correct Question Type
    var questionType = jQuery(this).val();

    jQuery( '#add-new-question' ).find( 'div.question_default_fields' ).hide();
    jQuery( '#add-new-question' ).find( 'div.question_boolean_fields' ).hide();
    jQuery( '#add-new-question' ).find( 'div.question_gapfill_fields' ).hide();
    jQuery( '#add-new-question' ).find( 'div.question_multiline_fields' ).hide();
    jQuery( '#add-new-question' ).find( 'div.question_singleline_fields' ).hide();
    jQuery( '#add-new-question' ).find( 'div.question_fileupload_fields' ).hide();

    jQuery( '.add_question_random_order' ).hide();

    switch ( questionType ) {
      case 'multiple-choice':
        jQuery( '#add-new-question' ).find( 'div.question_default_fields' ).show();
        jQuery( '.add_question_random_order' ).show();
        break;
      case 'boolean':
        jQuery( '#add-new-question' ).find( 'div.question_boolean_fields' ).show();
        break;
      case 'gap-fill':
        jQuery( '#add-new-question' ).find( 'div.question_gapfill_fields' ).show();
        break;
      case 'multi-line':
        jQuery( '#add-new-question' ).find( 'div.question_multiline_fields' ).show();
        break;
      case 'single-line':
        jQuery( '#add-new-question' ).find( 'div.question_singleline_fields' ).show();
        break;
      case 'file-upload':
        jQuery( '#add-new-question' ).find( 'div.question_fileupload_fields' ).show();
        break;
    } // End Switch Statement
  });

  /**
   * Edit Question Click Event.
   *
   * @since 1.0.0
   * @access public
   */
  jQuery( '#add-question-metadata' ).on( 'click', 'a.question_table_edit', function() {
    // Display the question for edit
    var questionId = jQuery(this).closest('tr').next('tr').find('.question_original_counter').text();
    jQuery( '#add-question-actions button.add_question_answer' ).removeClass('hidden');

    jQuery.fn.resetAddQuestionForm();
    jQuery.fn.resetQuestionTable();

    jQuery(this).closest('tr').next('tr').removeClass('hidden');
    jQuery( '#question_' + questionId ).focus();
  });

  /**
   * Cancel Events Click Event - edit question.
   *
   * @since 1.0.0
   * @access public
   */
  jQuery( '#add-question-metadata' ).on( 'click', 'a.lesson_question_cancel', function() {
    // Hide the edit question panel
    jQuery( this ).closest('tr.question-quick-edit').addClass( 'hidden' );
  });

  /**
   * Add Question Save Click Event - Ajax.
   *
   * @since 1.0.0
   * @access public
   */
  jQuery( '#add-new-question' ).on( 'click', 'a.add_question_save', function() {
    var dataToPost = '';
    var questionType = 'multiple-choice';
    var questionCategory = '';
    var radioValue = 'true';
    // Validate Inputs
    var validInput = jQuery.fn.validateQuestionInput( 'add', jQuery(this) );
    if ( validInput ) {
      // Setup data to post
      dataToPost += 'quiz_id' + '=' + jQuery( '#quiz_id' ).attr( 'value' );
      dataToPost += '&action=add';
      if ( jQuery( '#add-question-type-options' ).val() != '' ) {
        questionType = jQuery( '#add-question-type-options' ).val();
      } // End If Statement

      if ( jQuery( '#add-question-category-options' ).val() != '' ) {
        questionCategory = jQuery( '#add-question-category-options' ).val();
      } // End If Statement

      var divFieldsClass = 'question_default_fields';
      switch ( questionType ) {
        case 'multiple-choice':
          divFieldsClass = 'question_default_fields';
          break;
        case 'boolean':
          divFieldsClass = 'question_boolean_fields';
          break;
        case 'gap-fill':
          divFieldsClass = 'question_gapfill_fields';
          break;
        case 'multi-line':
          divFieldsClass = 'question_multiline_fields';
          break;
        case 'single-line':
          divFieldsClass = 'question_singleline_fields';
          break;
        case 'file-upload':
          divFieldsClass = 'question_fileupload_fields';
          break;
      } // End Switch Statement

      // Handle Required Fields
      jQuery( '#add-new-question' ).find( 'div.question_required_fields' ).find( 'input' ).each( function() {
        if ( jQuery( this ).attr( 'type' ) != 'radio' ) {
          dataToPost += '&' + jQuery( this ).attr( 'name' ) + '=' + encodeURIComponent( jQuery( this ).attr( 'value' ) );
        } // End If Statement
      });

      // Handle textarea required field
      if ( jQuery( '#add-new-question' ).find( 'div.question_required_fields' ).find( 'textarea' ).val() != '' ) {
        dataToPost += '&' + jQuery( '#add-new-question' ).find( 'div.question_required_fields' ).find( 'textarea' ).attr( 'name' ) + '=' + encodeURIComponent( jQuery( '#add-new-question' ).find( 'div.question_required_fields' ).find( 'textarea' ).val() );
      } // End If Statement

      // Handle Question Input Fields
      var radioCount = 0;
      jQuery( '#add-new-question' ).find( 'div.' + divFieldsClass ).find( 'input' ).each( function() {
        if ( jQuery( this ).attr( 'type' ) == 'radio' ) {
          // Only get the selected radio button
          if ( radioCount == 0 ) {
            radioValue = jQuery( 'input[name=' + jQuery( this ).attr( 'name' ) + ']:checked' ).attr( 'value' );
            dataToPost += '&' + jQuery( this ).attr( 'name' ) + '=' + encodeURIComponent( radioValue );
            radioCount++;
          } // End If Statement
        } else {
          dataToPost += '&' + jQuery( this ).attr( 'name' ) + '=' + encodeURIComponent( jQuery( this ).attr( 'value' ) );
        } // End If Statement
      });
      // Handle Question Textarea Fields
      if ( jQuery( '#add_question_right_answer_essay' ).val() != '' && divFieldsClass == 'question_essay_fields' ) {
        dataToPost += '&' + jQuery( '#add_question_right_answer_essay' ).attr( 'name' ) + '=' + encodeURIComponent( jQuery( '#add_question_right_answer_essay' ).val() );
      } // End If Statement
      if ( jQuery( '#add_question_right_answer_multiline' ).val() != '' && divFieldsClass == 'question_multiline_fields' ) {
        dataToPost += '&' + jQuery( '#add_question_right_answer_multiline' ).attr( 'name' ) + '=' + encodeURIComponent( jQuery( '#add_question_right_answer_multiline' ).val() );
      } // End If Statement
      dataToPost += '&' + 'question_type' + '=' + questionType;
      dataToPost += '&' + 'question_category' + '=' + questionCategory;
      questionGrade = jQuery( '#add-question-grade' ).val();
      dataToPost += '&' + 'question_grade' + '=' + questionGrade;

      var questionCount = parseInt( jQuery( '#question_counter' ).attr( 'value' ) );
      dataToPost += '&' + 'question_count' + '=' + questionCount;

      var answer_order = jQuery( '#add-new-question' ).find( '.answer_order' ).attr( 'value' );
      dataToPost += '&' + 'answer_order' + '=' + answer_order;

      var question_media = jQuery( '#add-new-question' ).find( '.question_media' ).attr( 'value' );
      dataToPost += '&' + 'question_media' + '=' + question_media;

      if ( '' != jQuery( 'div#add-new-question' ).find( 'div.' + divFieldsClass ).find( '.answer_feedback' ).exists() ) {
        var answer_feedback = jQuery( '#add-new-question' ).find( 'div.' + divFieldsClass ).find( '.answer_feedback' ).attr( 'value' );
        dataToPost += '&' + 'answer_feedback' + '=' + encodeURIComponent( answer_feedback );
      }

      var random_order = 'no';
      if ( jQuery( 'div#add-new-question' ).find( '.random_order' ).is(':checked') ) {
        random_order = 'yes'
      }
      dataToPost += '&' + 'random_order' + '=' + random_order;

      // Perform the AJAX call.
      jQuery.post(
        ajaxurl,
        {
          action : 'lesson_update_question',
          lesson_update_question_nonce : woo_localized_data.lesson_update_question_nonce,
          data : dataToPost
        },
        function( response ) {
          // Check for a valid response
          if ( response ) {
            jQuery.fn.updateQuestionCount( 1, '+' );
            jQuery( '#add-question-metadata table' ).append( response );
            jQuery.fn.resetAddQuestionForm();
            jQuery.fn.checkQuizGradeType( questionType );

            var max_questions = jQuery( '#show_questions' ).attr( 'max' );
            max_questions++;
            jQuery( '#show_questions' ).attr( 'max', max_questions );

            jQuery.fn.scrollToElement( '#lesson-quiz' );
          }
        }
      );
      return false;
    } else {
      jQuery( '#add_question' ).focus();
    }
  });

  /**
   * Add Multiple Questions Save Click Event - Ajax.
   *
   * @since 1.6.0
   * @access public
   */
  jQuery( '#add-new-question' ).on( 'click', 'a.add_multiple_save', function() {
    var dataToPost = '';
    var questionCategory = '';
    var questionNumber = 0;

    dataToPost += 'quiz_id' + '=' + jQuery( '#quiz_id' ).attr( 'value' );

    if ( jQuery( '#add-multiple-question-count' ).val() != '' ) {
      questionNumber = parseInt( jQuery( '#add-multiple-question-count' ).val() );
    } // End If Statement
    dataToPost += '&' + 'question_number' + '=' + questionNumber;

    var maxAllowed = parseInt( jQuery( '#add-multiple-question-count' ).attr( 'max' ) );

    // Only allow submission if selected number is not greater than the amount of questions in the category
    if( questionNumber > maxAllowed ) {
      alert( woo_localized_data.too_many_for_cat );
      jQuery( '#add-multiple-question-count' ).focus();
      return false;
    }

    if ( jQuery( '#add-multiple-question-category-options' ).val() != '' ) {
      questionCategory = jQuery( '#add-multiple-question-category-options' ).val();
    } // End If Statement
    dataToPost += '&' + 'question_category' + '=' + questionCategory;

    var questionCount = parseInt( jQuery( '#question_counter' ).attr( 'value' ) );
    dataToPost += '&' + 'question_count' + '=' + questionCount;

    if( questionCategory && questionNumber ) {
      // Perform the AJAX call.
      jQuery.post(
        ajaxurl,
        {
          action : 'lesson_add_multiple_questions',
          lesson_add_multiple_questions_nonce : woo_localized_data.lesson_add_multiple_questions_nonce,
          data : dataToPost
        },
        function( response ) {
          // Check for a valid response
          if ( response ) {
            jQuery( '#add-multiple-question-category-options' ).val('');
            jQuery( '#add-multiple-question-count' ).val('1');

            jQuery.fn.updateQuestionCount( questionNumber, '+' );
            jQuery( '#add-question-metadata table' ).append( response );

            jQuery.fn.updateQuestionOrder();

            var max_questions = jQuery( '#show_questions' ).attr( 'max' );
            max_questions += questionNumber;
            jQuery( '#show_questions' ).attr( 'max', max_questions );

            jQuery.fn.scrollToElement( '#lesson-quiz' );
          }
        }
      );
      return false;
    } else {
      jQuery( '#add-multiple-question-category-options' ).focus();
    }

  });

  /**
   * Edit Question Save Click Event - Ajax.
   *
   * @since 1.0.0
   * @access public
   */
  jQuery( '#add-question-metadata' ).on( 'click', 'a.question_table_save', function() {
    var dataToPost = '';
    var tableRowId = '';
    var validInput = jQuery.fn.validateQuestionInput( 'edit', jQuery(this) );
    if ( validInput ) {
      // Setup the data to post
      dataToPost += 'quiz_id' + '=' + jQuery( '#quiz_id' ).attr( 'value' );
      dataToPost += '&action=save';
      jQuery( this ).closest( 'td' ).children( 'input' ).each( function() {
        dataToPost += '&' + jQuery( this ).attr( 'name' ) + '=' + encodeURIComponent( jQuery( this ).attr( 'value' ) );
      });
      tableRowId = jQuery( this ).closest('td').find('span.question_original_counter').text();
      if ( jQuery( this ).closest('td').find( 'input.question_type' ).val() != '' ) {
        questionType = jQuery( this ).closest('td').find( 'input.question_type' ).val();
      } // End If Statement
      var divFieldsClass = 'question_default_fields';
      switch ( questionType ) {
        case 'multiple-choice':
          divFieldsClass = 'question_default_fields';
          break;
        case 'boolean':
          divFieldsClass = 'question_boolean_fields';
          break;
        case 'gap-fill':
          divFieldsClass = 'question_gapfill_fields';
          break;
        case 'essay-paste':
          divFieldsClass = 'question_essay_fields';
          break;
        case 'multi-line':
          divFieldsClass = 'question_multiline_fields';
          break;
        case 'single-line':
          divFieldsClass = 'question_singleline_fields';
          break;
        case 'file-upload':
          divFieldsClass = 'question_fileupload_fields';
          break;
      } // End Switch Statement
      // Handle Required Fields
      jQuery( this ).closest('td').find( 'div.question_required_fields' ).find( 'input' ).each( function() {
        if ( jQuery( this ).attr( 'type' ) != 'radio' ) {
          dataToPost += '&' + jQuery( this ).attr( 'name' ) + '=' + encodeURIComponent( jQuery( this ).attr( 'value' ) );
        } // End If Statement
      });

      // Handle textarea required field
      if ( jQuery( this ).closest('td').find( 'div.question_required_fields' ).find( 'textarea' ).val() != '' ) {
        dataToPost += '&' +  jQuery(this).closest('td').find( 'div.question_required_fields' ).find( 'textarea' ).attr( 'name' ) + '=' + encodeURIComponent( jQuery(this).closest('td').find( 'div.question_required_fields' ).find( 'textarea' ).val() );
      } // End If Statement

      // Handle Question Input Fields
      var radioCount = 0;
      jQuery( this ).closest('td').find( 'div.' + divFieldsClass ).find( 'input' ).each( function() {
        if ( jQuery( this ).attr( 'type' ) == 'radio' ) {
          // Only get the selected radio button
          if ( radioCount == 0 ) {
            dataToPost += '&' + jQuery( this ).attr( 'name' ) + '=' + encodeURIComponent( jQuery( 'input[name=' + jQuery( this ).attr( 'name' ) + ']:checked' ).attr( 'value' ) );
            radioCount++;
          } // End If Statement
        } else {
          dataToPost += '&' + jQuery( this ).attr( 'name' ) + '=' + encodeURIComponent( jQuery( this ).attr( 'value' ) );
        } // End If Statement
      });

      // Handle Question Textarea Fields
      if ( jQuery(this).closest('td').find( 'div.' + divFieldsClass ).find( 'textarea' ).val() != '' && divFieldsClass == 'question_multiline_fields' ) {
        dataToPost += '&' +  jQuery(this).closest('td').find( 'div.' + divFieldsClass ).find( 'textarea' ).attr( 'name' ) + '=' + encodeURIComponent( jQuery(this).closest('td').find( 'div.' + divFieldsClass ).find( 'textarea' ).val() );
      } // End If Statement
      if ( divFieldsClass == 'question_fileupload_fields' ) {
        jQuery(this).closest('td').find( 'div.' + divFieldsClass ).find( 'textarea' ).each( function() {
          dataToPost += '&' +  jQuery(this).attr( 'name' ) + '=' + encodeURIComponent( jQuery(this).val() );
        });
      } // End If Statement

      dataToPost += '&' + 'question_type' + '=' + questionType;
      questionGrade = jQuery( this ).closest('td').find( 'input.question_grade' ).val();
      dataToPost += '&' + 'question_grade' + '=' + questionGrade;

      var questionCount = parseInt( jQuery( '#question_counter' ).attr( 'value' ) );
      dataToPost += '&' + 'question_count' + '=' + questionCount;

      var answer_order = jQuery( this ).closest('td').find( '.answer_order' ).attr( 'value' );
      dataToPost += '&' + 'answer_order' + '=' + answer_order;

      var question_media = jQuery( this ).closest('td').find( '.question_media' ).attr( 'value' );
      dataToPost += '&' + 'question_media' + '=' + question_media;

      if ( '' != jQuery( this ).closest('td').find( '.answer_feedback' ).exists() ) {
        var answer_feedback = jQuery( this ).closest('td').find( '.answer_feedback' ).attr( 'value' );
        dataToPost += '&' + 'answer_feedback' + '=' + encodeURIComponent( answer_feedback );
      }

      var random_order = 'no';
      if ( jQuery( this ).closest('td').find( '.random_order' ).is(':checked') ) {
        random_order = 'yes'
      }
      dataToPost += '&' + 'random_order' + '=' + random_order;

      // Perform the AJAX call.
      jQuery.post(
        ajaxurl,
        {
          action : 'lesson_update_question',
          lesson_update_question_nonce : woo_localized_data.lesson_update_question_nonce,
          data : dataToPost
        },
        function( response ) {
          if ( response ) {

            // show the user the of the succesful update:
            var newQuestionTitle , newGradeTotal;

            // update the question title :
            newQuestionTitle = jQuery( '#question_' + tableRowId ).closest('tr').find('.question_required_fields input[name=question] ').val();
            jQuery( '#question_' + tableRowId ).closest('tr').prev().find('.question-title-column').html( newQuestionTitle );

            // update the grade total
            newGradeTotal = jQuery( '#question_' + tableRowId ).closest('tr').find('.question_required_fields input[name=question_grade] ').val();
            jQuery( '#question_' + tableRowId ).closest('tr').prev().find('.question-grade-column').html( newGradeTotal );

            // hide the update field
            jQuery( '#question_' + tableRowId ).closest('tr').addClass( 'hidden' );
          }
        }
      );
      return false;
    }
  });

  /**
   * Remove Question Click Event - Ajax.
   *
   * @since 1.0.0
   * @access public
   */
  jQuery( '#add-question-metadata' ).on( 'click', 'a.question_table_delete', function() {
    var dataToPost = '';
    var questionId = '';
    var quizId = '';
    var tableRowId = '';

    var confirmDelete = confirm( woo_localized_data.confirm_remove );

    if ( confirmDelete ) {
      // Setup data to post
      dataToPost += '&action=delete';
      jQuery( this ).closest('tr').next('tr').find('td').find( 'input' ).each( function() {
        if ( jQuery( this ).attr( 'name' ) == 'question_id' ) {
          questionId = jQuery( this ).attr( 'value' );
          dataToPost += '&question_id' + '=' + jQuery( this ).attr( 'value' );
        } // End If Statement
      });

      dataToPost += '&quiz_id' + '=' + jQuery( '#quiz_id' ).attr( 'value' );

      tableRowId = jQuery( this ).closest('tr').find('td.question-number span.number').text();
      var row_parent = jQuery( this ).closest( 'tbody' );

      // Perform the AJAX call.
      jQuery.post(
        ajaxurl,
        {
          action : 'lesson_update_question',
          lesson_update_question_nonce : woo_localized_data.lesson_update_question_nonce,
          data : dataToPost
        },
        function( response ) {
          if ( response ) {
            // Remove the html element for the deleted question
            jQuery( '#add-question-metadata > table > tbody > tr' ).children('td').each( function() {
              if ( jQuery(this).find('span.number').text() == tableRowId ) {
                jQuery(this).closest('tr').next('tr').remove();
                jQuery(this).closest('tr').remove();
                // Exit each() to prevent multiple row deletions
                return false;
              }
            });
            jQuery.fn.updateQuestionCount( 1, '-' );
            jQuery.fn.checkQuizGradeType( false );
            jQuery.fn.updateAnswerOrder( row_parent );

            var max_questions = parseInt( jQuery( '#show_questions' ).attr( 'max' ) );
            max_questions--;
            jQuery( '#show_questions' ).attr( 'max', max_questions );

            var show_questions_field = parseInt( jQuery( '#show_questions' ).val() );
            if( show_questions_field > max_questions ) {
              jQuery( '#show_questions' ).val( max_questions );
            }
          }
        }
      );
      return false;
    }
  });

  /**
   * Remove Multple Questions Row Click Event - Ajax.
   *
   * @since 1.6.0
   * @access public
   */
  jQuery( '#add-question-metadata' ).on( 'click', 'a.question_multiple_delete', function() {
    var dataToPost = '';

    var confirmDelete = confirm( woo_localized_data.confirm_remove_multiple );

    if ( confirmDelete ) {

      dataToPost += 'question_id' + '=' + jQuery( this ).attr( 'rel' );
      dataToPost += '&quiz_id' + '=' + jQuery( '#quiz_id' ).attr( 'value' );

      var tableRowId = jQuery( this ).closest('tr').find('td.question-number span.number').text();
      var total_number = jQuery( this ).closest('tr').find('td.question-number span.total-number').text();
      var row_parent = jQuery( this ).closest( 'tbody' );

      // Perform the AJAX call.
      jQuery.post(
        ajaxurl,
        {
          action : 'lesson_remove_multiple_questions',
          lesson_remove_multiple_questions_nonce : woo_localized_data.lesson_remove_multiple_questions_nonce,
          data : dataToPost
        },
        function( response ) {
          if ( response ) {

            // Remove the html element for the deleted question
            jQuery( '#add-question-metadata > table > tbody > tr' ).children('td').each( function() {
              if ( jQuery(this).find('span.number').text() == tableRowId ) {
                jQuery(this).closest('tr').remove();
                // Exit each() to prevent multiple row deletions
                return false;
              }
            });
            jQuery.fn.updateQuestionCount( total_number, '-' );

            var max_questions = parseInt( jQuery( '#show_questions' ).attr( 'max' ) );
            max_questions -= total_number;
            jQuery( '#show_questions' ).attr( 'max', max_questions );

            var show_questions_field = parseInt( jQuery( '#show_questions' ).val() );
            if( show_questions_field > max_questions ) {
              jQuery( '#show_questions' ).val( max_questions );
            }
          }
        }
      );
      return false;
    }
  });

  /**
   * Add Existing Questions Click Event - Ajax.
   *
   * @since 1.6.0
   * @access public
   */
  jQuery( '#add-new-question' ).on( 'click', 'a.add_existing_save', function() {
    var questions = '';
    var dataToPost = '';
    var i = 0;
    jQuery( '#existing-questions' ).find( 'input.existing-item' ).each( function() {
      if( jQuery( this ).is( ':checked' ) ) {
        var question_id = jQuery( this ).val();
        questions += question_id + ',';
        ++i;
      }
    });

    if( questions ) {

      dataToPost = 'questions=' + questions;
      dataToPost += '&quiz_id=' + jQuery( '#quiz_id' ).attr( 'value' );

      var questionCount = parseInt( jQuery( '#question_counter' ).attr( 'value' ) );
      dataToPost += '&question_count=' + questionCount;

      // Perform the AJAX call.
      jQuery.post(
        ajaxurl,
        {
          action : 'lesson_add_existing_questions',
          lesson_add_existing_questions_nonce : woo_localized_data.lesson_add_existing_questions_nonce,
          data : dataToPost
        },
        function( response ) {
          if ( response ) {

            jQuery.fn.updateQuestionCount( i, '+' );
            jQuery( '#add-question-metadata table' ).append( response );

            jQuery.fn.checkQuizGradeType();

            var max_questions = jQuery( '#show_questions' ).attr( 'max' );
            max_questions += i;
            jQuery( '#show_questions' ).attr( 'max', max_questions );

            jQuery.fn.scrollToElement( '#lesson-quiz' );

            jQuery( '#existing-questions' ).find( 'input.existing-item' ).each( function() {
              jQuery( this ).removeAttr( 'checked' );
            });
          }
        }
      );
      return false;
    }
  });

  jQuery( '#existing-filter-button' ).click( function() {
    jQuery.fn.filterExistingQuestions(1);
  });

  jQuery( '#existing-pagination' ).on( 'click', 'a', function() {
    var currentPage = parseInt( jQuery( '#existing-page' ).val() );
    var newPage = currentPage;
    if( jQuery( this ).hasClass( 'prev' ) ) {
      newPage = currentPage - 1;
    } else if( jQuery( this ).hasClass( 'next' ) ) {
      newPage = currentPage + 1;
    }
    newPage = parseInt( newPage );
    jQuery.fn.filterExistingQuestions( newPage );
  });

  jQuery( '#quiz-settings' ).on( 'change', '#pass_required', function() {
    var checked = jQuery(this).attr( 'checked' );
    if( 'checked' == checked ) {
      jQuery( '.form-field.quiz_passmark' ).removeClass( 'hidden' );
    } else {
      jQuery( '.form-field.quiz_passmark' ).addClass( 'hidden' );
      jQuery( '#quiz_passmark' ).val(0);
    }
  });

  jQuery( '#quiz-settings' ).on( 'change', '#random_question_order', function() {
    jQuery.fn.saveQuestionOrderRandom();
  });

  jQuery( '#add-question-main' ).on( 'blur', '.question_answer', function() {
    var answer_value = jQuery( this ).val();
    var answer_field = jQuery( this );

    dataToPost = '&answer_value=' + answer_value;
    jQuery.post(
      ajaxurl,
      {
        action : 'question_get_answer_id',
        data : dataToPost
      },
      function( response ) {
        if ( response ) {
          answer_field.attr( 'rel', response );
          jQuery.fn.updateAnswerOrder( answer_field.closest( 'div' ) );
        }
      }
    );

    return false;
  });

  jQuery( '#add-question-main' ).on( 'click', '.add_wrong_answer_option', function() {
    var question_counter = jQuery( this ).attr( 'rel' );
    var answer_count = jQuery('input[name="question_wrong_answers[]"]').length-1;
    answer_count++;
    var html = '<label class="answer" for="question_' + question_counter + '_wrong_answer_' + answer_count + '"><span>' + woo_localized_data.wrong_colon + '</span> <input type="text" id="question_' + question_counter + '_wrong_answer_' + answer_count + '" name="question_wrong_answers[]" value="" size="25" class="question_answer widefat" /> <a class="remove_answer_option"></a></label>';
    jQuery( this ).closest( 'div' ).before( html );
  });

  jQuery( '#add-question-main' ).on( 'click', '.add_right_answer_option', function() {
    var question_counter = jQuery( this ).attr( 'rel' );
    var answer_count = jQuery('input[name="question_right_answers[]"]').length-1;
    answer_count++;
    var html = '<label class="answer" for="question_' + question_counter + '_right_answer_' + answer_count + '"><span>' + woo_localized_data.right_colon + '</span> <input type="text" id="question_' + question_counter + '_right_answer_' + answer_count + '" name="question_right_answers[]" value="" size="25" class="question_answer widefat" /> <a class="remove_answer_option"></a></label>';
    jQuery( this ).closest( 'div' ).before( html );
  });

  jQuery( '#add-question-main' ).on( 'click', '.remove_answer_option', function() {
    jQuery( this ).closest( 'label.answer' ).remove();
  });

  jQuery( '.multiple-choice-answers' ).sortable( {
    items: "label.answer"
  });

  jQuery( '.multiple-choice-answers' ).bind( 'sortstop', function ( e, ui ) {
    jQuery.fn.updateAnswerOrder( jQuery( this ) );
  });

  jQuery( '#sortable-questions' ).sortable( {
    items: "tbody",
    'start': function (event, ui) {
      ui.placeholder.html("<tr><td colspan='5'>&nbsp;</td></tr>")
    }
  });

  jQuery( '#sortable-questions' ).bind( 'sortstop', function ( e, ui ) {
    jQuery.fn.updateQuestionOrder();
    jQuery.fn.updateQuestionRows();
  });

  jQuery('#add-question-main').on( 'click', '.upload_media_file_button', function( event ) {
    event.preventDefault();
    jQuery.fn.uploadQuestionMedia( jQuery( this ) );
  });

  jQuery('#add-question-main').on( 'click', '.delete_media_file_button', function( event ) {
    event.preventDefault();
    jQuery.fn.deleteQuestionMedia( jQuery( this ) );
  });

  jQuery('#add-question-main').on( 'click', '.question_media_preview', function( event ) {
    event.preventDefault();
    jQuery.fn.uploadQuestionMedia( jQuery( this ).closest( 'div' ).find( '.upload_media_file_button' ) );
  });

  jQuery( '#add-new-question .tab-content:not(:first)' ).addClass( 'hidden' );

  jQuery( '.add-question-tabs .nav-tab' ).click( function() {
    var tab_id = jQuery( this ).attr('id');
    var tab_content_id = tab_id + '-content';

    jQuery( '#add-new-question .nav-tab' ).removeClass( 'nav-tab-active' );
    jQuery( this ).addClass( 'nav-tab-active' );

    jQuery( '#add-new-question .tab-content' ).addClass( 'hidden' );
    jQuery( '#' + tab_content_id ).removeClass( 'hidden' );
  });

  jQuery( '#add-multiple-question-category-options' ).change( function() {
    var cat = jQuery( this ).val();
    var dataToPost = 'cat=' + cat;

    jQuery.post(
      ajaxurl,
      {
        action : 'get_question_category_limit',
        data : dataToPost
      },
      function( response ) {
        if ( response ) {
          var max = parseInt( response );
          if( max ) {
            jQuery( '#add-multiple-question-count' ).attr( 'max', max );
          }
        }
      }
    );
  });

  jQuery( '#existing-table th.check-column input' ).click( function () {
    jQuery( '#existing-questions' ).find( ':checkbox' ).attr( 'checked' , this.checked );
  });

  jQuery( 'tbody#existing-questions' ).on( 'click', 'tr td:not(.cb)', function() {
    jQuery( this ).closest( 'tr' ).find( ':checkbox' ).each( function() {
      jQuery( this ).prop( 'checked', ! jQuery( this ).prop( 'checked' ) );
    });
  });

  /***************************************************************************************************
   * 	5 - Load Chosen Dropdowns.
   ***************************************************************************************************/

  // Lessons Write Panel
  if ( jQuery( '#lesson-complexity-options' ).exists() ) { jQuery( '#lesson-complexity-options' ).select2({width:"resolve"}); }
  if ( jQuery( '#lesson-prerequisite-options' ).exists() ) { jQuery( '#lesson-prerequisite-options' ).select2({width:"resolve"}); }
  if ( jQuery( '#lesson-course-options' ).exists() ) { jQuery( '#lesson-course-options' ).select2({width:"resolve"}); }
  if ( jQuery( '#course-prerequisite-options' ).exists() ) { jQuery( '#course-prerequisite-options' ).select2({width:"resolve"}); }
  if ( jQuery( '#course-category-options' ).exists() ) { jQuery( '#course-category-options' ).select2({width:"resolve"}); }
  if ( jQuery( '#course-woocommerce-product-options' ).exists() && '-' != jQuery( '#course-woocommerce-product-options' ).val() ) { jQuery( '#course-woocommerce-product-options' ).select2({width:"resolve"}); }

  // Quiz edit panel
  if ( jQuery( '#add-question-type-options' ).exists() ) { jQuery( '#add-question-type-options' ).select2({width:"resolve"}); }
  if ( jQuery( '#add-question-category-options' ).exists() ) { jQuery( '#add-question-category-options' ).select2({width:"resolve"}); }
  if ( jQuery( '#add-multiple-question-options' ).exists() ) { jQuery( '#add-multiple-question-options' ).select2({width:"resolve"}); }

  // Courses Write Panel
  if ( jQuery( 'select#course-woocommerce-product-options' ).exists() ) { jQuery( 'select#course-woocommerce-product-options' ).select2({width:"resolve"}); }
  if ( jQuery( '#add-multiple-question-category-options' ).exists() ) { jQuery( '#add-multiple-question-category-options' ).select2({width:"resolve"}); }

  // Sensei Settings Panel
  jQuery( 'div.woothemes-sensei-settings form select' ).each( function() {
    if ( !jQuery( this ).hasClass( 'range-input' ) ) {
      jQuery( this).select2({width:"resolve"});;
    } // End If Statement
  });
}
