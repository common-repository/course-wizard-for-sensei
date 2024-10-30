jQuery( document ).ready(function( e ) {

  cwsAjaxInit( 1 );

  cwsAjaxLoadHistory();

  // Cache <script> resources loaded in AJAX.
  jQuery.ajaxPrefilter('script', function(options) { options.cache = true; });

  jQuery( '#cws-wizard-screen' ).on('click', '.cws-link', function(e) {
    return jQuery(this).css('pointer-events') == 'none' ? e.preventDefault() : cwsAjaxLink(this);
  });
  jQuery( '#cws-wizard-screen' ).on( 'submit', '.cws-form', cwsAjaxForm );

  jQuery( '#cws-wizard-screen' ).on('click', '.cws-back-button', function(e) {
    // Slide to right.
    cwsTransition = -1;
  });
  jQuery( '#cws-wizard-screen' ).on('click', '.cws-order-items-link', function(e) {
    // Slide to left.
    cwsTransition = 1;
  });
   jQuery( '.cws-wizard' ).on('click', '.cws-preview-button', function(e) {
    // Show Preview.
    jQuery( '#cws-preview.wp-full-overlay-main, .cws-preview-button' ).toggleClass( 'show' );

     e.preventDefault();
  });
});

// Fade out, fade in, default.
cwsTransition = 0;

/**
 * Add $_GET var to URL
 *
 * @param form
 * @param cssClass
 */
function cwsAddGetVarToUrl( form, cssClass ) {

  var getVars = '';

  if ( ! cssClass ) {

    cssClass = '.cws-is-get-var';
  }

  jQuery( form ).find( cssClass ).each(function(i,el) {

    getVars += '&' + el.name + '=' + el.value;
  });

  // console.log( getVars );

  form.action += getVars;
}


/**
 * AJAX init
 *
 * @param jQueryReady
 */
cwsAjaxInit = function( jQueryReady ) {

  jQuery( '.cws-select2' ).each( function(i,el) {

    var select = jQuery( el );

    if ( select.hasClass( 'cws-select-filter' ) ) {

      // Select filter AJAX.
      select.on( 'change', cwsSelectFilterAjax );
    }

    select.select2({
      width:"100%",
      dropdownParent: select.parent()
    });
  });

  cwsCoursesReady();

  cwsSenseiModulesReady();

  cwsSenseiLessonsReady();

  cwsSenseiQuestionsReady();

  jQuery( '.sortable-lesson-list' ).on('click', '.cws-order-remove-link', function(e) {
    // Uses localized string from PHP.
    var deleteConfirm = confirm( cwsL10n.lessonRemoveConfirmDelete );

    if ( deleteConfirm ) {
      // Remove AND delete lesson.
      this.href += '&cws_action=delete';
    }
  });

  // @since 1.5.0.
  jQuery( '.sortable-module-list' ).on('click', '.module .cws-order-remove-link', function(e) {
    // Uses localized string from PHP.
    var deleteConfirm = confirm( cwsL10n.moduleRemoveConfirmDelete );

    if ( deleteConfirm ) {
      // Remove AND delete module + lessons.
      this.href += '&cws_action=delete';
    }
  });

  // Fix WordPress editor AJAX.
  if ( jQuery( '#cws-editor' ).length ) {

    // Move content to textarea.
    var content = document.getElementById( 'cws-editor' ).value;

    if ( ! jQueryReady && tinymce && tinymce.activeEditor ) {

      tinymce.activeEditor.setContent(content);
    }

    jQuery( 'textarea[name=cws-editor-textarea]' ).val(content);

    // Move editor to wrapper.
    jQuery( '.cws-editor-hidden' ).show();
  }

  if ( jQueryReady ) {
    return jQueryReady;
  }

  cwsPreviewReload( cwsPreviewUrl );

  cwsSenseiLessonMetadataReady();

  return jQueryReady;
};


// Select filter AJAX.
function cwsSelectFilterAjax() {

  var filteredObject = jQuery( '#cws_filter_object' ).val(),
    filteredSelect = jQuery( '.cws-' + filteredObject + '-select-filtered' );

  if ( ! filteredObject ||
    ! filteredSelect.length ) {

    return;
  }

  var objectId = jQuery( this ).val();

  if ( ! objectId ) {

    return;
  }

  data = 'action=cws_ajax_form&' + this.name + '=' + objectId + '&cws_filter_object=' + filteredObject;

  // Add $_GET screen params as data, we'll have $_POST on the other side.
  data += cwsUrl.substring( cwsUrl.indexOf( '&page=course-wizard' ) + 19 );

  jQuery.post( ajaxurl, data, function( response, s, xhr ) {
    if ( ! response ) {
      return;
    }

    // Update select options and open dropdown.
    filteredSelect.html( response ).focus().select2( 'open' );
  });
}

/**
 * AJAX Load History.
 */
function cwsAjaxLoadHistory() {
  // Load body after browser history.
  if ( history.pushState ) window.setTimeout( function() {
    window.addEventListener('popstate', function(e) {
      cwsAjaxLink( document.URL );
    }, false);
  }, 1);
}

// URL + preview URL.
var cwsUrl = cwsPreviousUrl = cwsUrlBeforeRedirect = cwsPreviewUrl = '';


/**
 * AJAX link
 *
 * @param link
 * @returns {boolean}
 */
function cwsAjaxLink( link ) {

  cwsPreviousUrl = cwsUrl;

  if ( typeof link === 'string' ) {
    cwsUrlBeforeRedirect = cwsUrl = link;
  } else {
    if ( jQuery( link ).hasClass( 'cws-no-ajax' ) ) {
      // No AJAX, do submit form.
      return true;
    }

    cwsUrlBeforeRedirect = cwsUrl = link.href;
  }

  data = 'action=cws_ajax_form';

  // Add $_GET screen params as data, we'll have $_POST on the other side.
  data += cwsUrl.substring( cwsUrl.indexOf( '&page=course-wizard' ) + 19 );

  jQuery.post( ajaxurl, data, cwsAjaxSuccess );

  return false;
}


/**
 * AJAX form.
 *
 * @link https://codex.wordpress.org/AJAX_in_Plugins
 * @param e
 * @returns {boolean}
 */
function cwsAjaxForm( e ) {

  if ( jQuery( this ).hasClass( 'cws-no-ajax' ) ) {

    var data = jQuery( this ).serialize();

    this.action += ( this.action.indexOf( '?' ) > -1 ? '&' : '?' ) + data;

    console.log(this.action);

    // No AJAX, do submit form.
    return true;
  }

  // Fix WordPress editor AJAX.
  if ( jQuery( '#cws-editor' ).length ) {

    var content = '';

    // Get WordPress editor content.
    if ( jQuery( '#wp-cws-editor-id-wrap' ).hasClass('html-active') ) {

      // Text (HTML) tab is active, get content from textarea.
      content = jQuery( '#cws-editor-id' ).val();
    } else {

      //Visual tab is active, get content from TinyMCE.
      content = tinymce.activeEditor.getContent();
    }

    jQuery( '#cws-editor' ).val( content );
  }

  var data = jQuery( this ).serialize();

  data += '&action=cws_ajax_form';

  cwsAddGetVarToUrl( this, '.cws-is-ajax-get-var' );

  cwsPreviousUrl = cwsUrl;

  cwsUrlBeforeRedirect = cwsUrl = this.action;

  // Add $_GET screen params as data, we'll have $_POST on the other side.
  data += cwsUrl.substring( cwsUrl.indexOf( '&page=course-wizard' ) + 19 );

  cwsAddGetVarToUrl( this );

  // Since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
  jQuery.post(ajaxurl, data, cwsAjaxSuccess);

  // Prevent the form from reloading the page.
  e.preventDefault();

  return false;
}


/**
 * AJAX success
 *
 * @param response
 * @param s
 * @param xhr
 */
function cwsAjaxSuccess( response, s, xhr ) {

 if (response === '') {
    cwsPreviewReload( cwsPreviewUrl );

    // Is AJAX silent.
    return;
  }

  cwsDoTransition(response);

  jQuery( '.cws-editor-hidden' ).hide();

  var redirectUrl = xhr.getResponseHeader("X-Redirect-Url");

  if (redirectUrl) {
    cwsUrlBeforeRedirect = cwsUrl;

    cwsUrl = redirectUrl;
  }

  if (document.URL !== cwsUrl) {
    // Update URL.
    history.pushState(null, document.title, cwsUrl);
  }
}

function cwsDoTransition(response) {
  var cwsScreen = jQuery( '#cws-wizard-screen' );

  var cwsScreenContentAjax = jQuery( '.cws-wizard-screen-ajax-content' );

  var screenContent = response;

  if ( cwsTransition === 0 ) {
    cwsScreen.fadeOut('fast', function() {
      cwsScreenContentAjax.html(screenContent);
      cwsScreen.fadeIn('fast');

      cwsAjaxInit();
    });

    return;
  }

  var cwsScreenTransition = jQuery( '#cws-wizard-screen-transition' ),
    cwsWizard = jQuery( '.cws-wizard' );

  cwsScreenTransition.html( cwsScreen.html() );
  cwsScreenContentAjax.html(screenContent);

  var cwsTransitionBack = ( cwsTransition < 0 ) ? ' back' : '';

  cwsWizard.addClass( 'cws-before-transition' + cwsTransitionBack );

  window.setTimeout( function() {

    cwsWizard.addClass( 'cws-is-transition' + cwsTransitionBack );
  }, 1);

  window.setTimeout( function() {

    cwsScreenTransition.html('');

    cwsWizard.removeClass( 'cws-is-transition cws-before-transition' + cwsTransitionBack );

    cwsTransition = 0;

    cwsAjaxInit();
  }, 251);
}


function cwsPreviewReload( preview_url ) {

  var iframe = document.getElementById('cws-preview-iframe');

  if ((!preview_url && iframe.src) ||
    // Use iframe.contentWindow.location.href instead of iframe.src to get current src.
    (preview_url && iframe.contentWindow.location.href === preview_url)) {

    if ( cwsUrlBeforeRedirect.indexOf( 'cws_action=' ) === -1 ||
      cwsUrlBeforeRedirect.indexOf( 'cws_course=' ) === -1 ) {

      // URL is not an action, has no course set.
      return;
    }

    // Save scroll position before reloading iframe.
    // @link https://stackoverflow.com/questions/1192228/scrolling-an-iframe-with-javascript
    var x = iframe.contentWindow.scrollX;
    var y = iframe.contentWindow.scrollY;
    iframe.contentWindow.location.reload(true);

    // Restore scroll position.
    iframe.onload = function() {
      iframe.contentWindow.scrollTo( x, y );
    }

  } else {
    iframe.src = preview_url;
  }
}


/**
 * Disable links while AJAX (do NOT use disabled attribute).
 * And show spinner.
 *
 * @link http://stackoverflow.com/questions/5985839/bug-with-firefox-disabled-attribute-of-input-not-resetting-when-refreshing
 *
 * @link https://stackoverflow.com/questions/3497229/jquery-can-i-retrieve-event-xhr-options-from-ajaxstart-or-ajaxstop
 */
jQuery(document).ajaxSend(function(e, xhr, options) {

  if ( ! options.data ||
    options.data.indexOf( 'action=cws_ajax_form' ) < 0 ) {

    // Is WordPress heartbeat AJAX, die.
    return;
  }

  jQuery('input[type="submit"],input[type="button"],a').css('pointer-events', 'none');

  jQuery('.cws-wizard .spinner').addClass('is-active');

}).ajaxComplete(function(e, xhr, options) {

  if ( ! options.data ||
    options.data.indexOf( 'action=cws_ajax_form' ) < 0 ) {

    // Is WordPress heartbeat AJAX, die.
    return;
  }

  jQuery('input[type="submit"],input[type="button"],a').css('pointer-events', '');

  jQuery('.cws-wizard .spinner').removeClass('is-active');
}).ajaxError(function(e, xhr, options, error) {

  if ( ! options.data ||
    options.data.indexOf( 'action=cws_ajax_form' ) < 0 ) {

    // Is WordPress heartbeat AJAX, die.
    return;
  }

  var cwsScreen = jQuery( '#cws-wizard-screen' );

  var code = xhr.status,
    errorMsg = '<div class="notice error cws-ajax-error"><p>AJAX error. ' + code + ' ';

  if (code === 0) {
    errorMsg += 'Check your Network';

  } else if (status == 'parsererror') {
    errorMsg += 'JSON parse failed';
  } else if (status == 'timeout') {
    errorMsg += 'Request Timeout';
  } else if (status == 'abort') {
    errorMsg += 'Request Aborted';
  } else {
    errorMsg += error;
  }

  errorMsg += '.</p></div>';

  // @link https://stackoverflow.com/questions/6102636/html-code-as-iframe-source-rather-than-a-url
  cwsScreen.html( errorMsg + '<iframe width="100%" height="100%" srcdoc="' + xhr.responseText + '"></iframe>' );
});


/**
 * Courses ready JS.
 */
function cwsCoursesReady() {

  jQuery( '.cws-course-edit-link' ).click( function( e ) {

    // Edit button was clicked.
    if ( ! jQuery( '#cws-select-post' ).val() ) {

      // No course was selected.
      e.preventDefault();
      e.stopImmediatePropagation();
      e.stopPropagation();

      // Focus on course select input.
      jQuery( '#cws-select-post' ).focus();

      return false;
    }

    // Append selected Course ID to URL.
    this.href += jQuery( '#cws-select-post' ).val();
  });
}


/**
 * Sensei Modules ready JS.
 *
 * @see modules-admin.js
 */
function cwsSenseiModulesReady() {
  /**
   * Add select to the modules select boxes
   */
  // module order screen
  jQuery( '#module-order-course' ).select2({width:"resolve"});
  // lesson edit screen modules selection
  jQuery( 'select#lesson-module-options' ).select2({width:"resolve"});

  /**
   * Sortable functionality
   */
  jQuery( '.sortable-module-list' ).sortable();
  jQuery( '.sortable-tab-list' ).disableSelection();

  jQuery( '.sortable-module-list' ).bind( 'sortstop', function ( e, ui ) {
    var orderString = '';

    jQuery( this ).find( '.cws-order-item' ).each( function ( i, e ) {
      if ( i > 0 ) { orderString += ','; }
      orderString += jQuery( this ).find( 'span' ).attr( 'rel' );

      jQuery( this ).removeClass( 'alternate' );
      jQuery( this ).removeClass( 'first' );
      jQuery( this ).removeClass( 'last' );
      if( i == 0 ) {
        jQuery( this ).addClass( 'first alternate' );
      } else {
        var r = ( i % 2 );
        if( 0 == r ) {
          jQuery( this ).addClass( 'alternate' );
        }
      }

    });

    var oldOrderString = jQuery( 'input[name="module-order"]' ).attr( 'value' );

    jQuery( 'input[name="module-order"]' ).attr( 'value', orderString );

    if ( orderString && oldOrderString !== orderString ) {

      // Save lesson order immediately using AJAX.
      jQuery( this ).closest( '.cws-form' ).submit();
    }
  });
}


/**
 * Sensei Lessons ready JS.
 */
function cwsSenseiLessonsReady() {


  jQuery.fn.fixOrderingList = function( container, type ) {

    container.find( '.' + type ).each( function( i, e ) {
      jQuery( this ).removeClass( 'alternate' );
      jQuery( this ).removeClass( 'first' );
      jQuery( this ).removeClass( 'last' );
      if( i == 0 ) {
        jQuery( this ).addClass( 'first alternate' );
      } else {
        var r = ( i % 2 );
        if( 0 == r ) {
          jQuery( this ).addClass( 'alternate' );
        }
      }
    });
  };

  /***** Lesson reordering *****/

  jQuery( '.sortable-lesson-list' ).sortable();
  jQuery( '.sortable-tab-list' ).disableSelection();

  jQuery( '.sortable-lesson-list' ).bind( 'sortstop', function ( e, ui ) {
    var orderString = '';

    var module_id = jQuery( this ).attr( 'data-module_id' );
    var order_input = 'lesson-order';
    if( 0 != module_id ) {
      order_input = 'lesson-order-module-' + module_id;
    }


    jQuery( this ).find( '.lesson' ).each( function ( i, e ) {
      if ( i > 0 ) { orderString += ','; }
      orderString += jQuery( this ).find( 'span' ).attr( 'rel' );
    });

    var oldOrderString = jQuery( 'input[name="' + order_input + '"]' ).attr( 'value' );

    jQuery( 'input[name="' + order_input + '"]' ).attr( 'value', orderString );

    jQuery.fn.fixOrderingList( jQuery( this ), 'lesson' );

    if ( orderString && oldOrderString !== orderString ) {

      // Save lesson order immediately using AJAX.
      jQuery( this ).closest( '.cws-form' ).submit();
    }
  });
}


/**
 * Sensei Questions ready JS.
 */
function cwsSenseiQuestionsReady() {

  // Update question count in Add form with real value from Order form.
  jQuery( '#cws_add_question_count' ).attr( 'value',
    parseInt( jQuery( '#question_counter' ).attr( 'value' ) )
  );

  /**
   * Sortable functionality
   */
  jQuery( '.sortable-question-list' ).sortable();
  jQuery( '.sortable-tab-list' ).disableSelection();

  jQuery( '.sortable-question-list' ).bind( 'sortstop', function ( e, ui ) {
    var orderString = '';

    jQuery( this ).find( '.question' ).each( function ( i, e ) {
      if ( i > 0 ) { orderString += ','; }
      orderString += jQuery( this ).find( 'span' ).attr( 'rel' );

      jQuery( this ).removeClass( 'alternate' );
      jQuery( this ).removeClass( 'first' );
      jQuery( this ).removeClass( 'last' );
      if( i == 0 ) {
        jQuery( this ).addClass( 'first alternate' );
      } else {
        var r = ( i % 2 );
        if( 0 == r ) {
          jQuery( this ).addClass( 'alternate' );
        }
      }

    });

    var oldOrderString = jQuery( 'input[name="question-order"]' ).attr( 'value' );

    jQuery( 'input[name="question-order"]' ).attr( 'value', orderString );

    if ( orderString && oldOrderString !== orderString ) {

      // Save lesson order immediately using AJAX.
      jQuery( this ).closest( '.cws-form' ).submit();
    }
  });
}
