<?php

/**
 * Plugin Name: WP AJAX & Session
 * Description: A sample plugin that illustrates how bad idea to use PHP session in WordPress environment.
 * Author:      Changwoo Nam
 * Author URI:  https://blog.changwoo.pe.kr/
 * Plugin URI:  https://github.com/chwnam/wp-ajax-session/
 * Version:     1.0.0
 */

if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	/**
	 * Start session or delete session ID.
	 */
	add_action( 'init', 'was_start_session' );

	function was_start_session() {

		// 'was_session' is came from AJAX
		$was_session = isset( $_GET['was_session'] ) ? boolval( $_GET['was_session'] ) : FALSE;

		// initialize the test.
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'was_init_test' ) {
			if ( $was_session ) {
				if ( ! session_id() ) {
					session_start();
					error_log( 'was_init_test >>>> session started. Session ID: ' . session_id() );
				}
			} else {
				// Destroy the session and clear the session cooke.
				if ( ! session_id() ) {
					session_start();
				}
				$session_id = session_id();
				session_unset();
				session_destroy();
				setcookie( session_name(), NULL, - 1, '/' );
				error_log( 'was_init_test >>>> session destroyed. Session ID: ' . $session_id );
			}
			return;
		}

		// test action
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'was_long_request' ) {
			if ( $was_session ) {
				if ( ! session_id() ) {
					session_start();
					error_log( 'was_long_request >>>> session started. Session ID: ' . session_id() );
				}
			} else {
				error_log( 'was_long_request >>>> session canceled.' );
			}
			return;
		}
	}
}


// AJAX for initializing the test.
add_action( 'wp_ajax_was_init_test', function () {
	wp_send_json_success();
} );


// AJAX test code. Intentionally delayed for a moment.
add_action( 'wp_ajax_was_long_request', 'was_long_request' );

function was_long_request() {
	$delay = isset( $_GET['delay'] ) ? intval( $_GET['delay'] ) : 10;
	if ( $delay <= 30 ) {
		$sequence = isset( $_GET['sequence'] ) ? intval( $_GET['sequence'] ) : 'unknown';
		sleep( $delay );
		error_log( 'was_long_request >>> done request #' . $sequence );
		wp_send_json_success( 'O.K. ' . $delay . ' delayed.' );
	} else {
		wp_send_json_error( 'Error. Requested delay is too long. Delay up to 30 seconds is allowed.' );
	}
}


// Admin menu.
add_action( 'admin_menu', 'was_admin_menu' );

function was_admin_menu() {
	add_menu_page(
		'WP AJAX &amp; Session Test',
		'WAS Test',
		'manage_options',
		'was',
		'was_output_admin_menu'
	);
}

function was_output_admin_menu() {
	?>
    <h1>WP Ajax &amp; Session Test</h1>
    <div class="wrapper">
        <div>
            <p>
                <button type="button" id="was-session" class="button button-secondary">Click Me! (Using session)
                </button>
            </p>
            <p>
                <button type="button" id="was-no-session" class="button button-secondary">Click Me! (Session not used)
                </button>
            </p>
        </div>
        <div>
            In this page, you can click two buttons. The first button calls some AJAX calls that uses PHP session,
            whereas
            the second one does the same thing but does not uses the session. Check what is different.
        </div>
        <div>
            Open the web browser's development console, and see response time.
        </div>
    </div>
    <script>
        (function ($) {

            var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';

            function wasAjaxSessionResponse(response) {
                if (response.success) {
                    console.log(response.data);
                }
            }

            function wasAjaxSessionTest(useSession, times) {
                var data = {
                    action: 'was_long_request',
                    was_session: useSession ? '1' : '0',
                    delay: 3,
                    sequence: 0
                };
                times = times || 3;
                for (var i = 0; i < times; ++i) {
                    data.sequence = i;
                    $.getJSON(ajaxUrl, data, wasAjaxSessionResponse);
                }
            }

            $(document).ready(function () {
                // Before we start the test, initialize the test by calling 'was_init_test' action.
                // The action will always return successful response.
                $('#was-session').click(function () {
                    $.getJSON(ajaxUrl, {
                        action: 'was_init_test',
                        was_session: '1'
                    }, function () {
                        wasAjaxSessionTest(true);
                    });
                });
                $('#was-no-session').click(function () {
                    $.getJSON(ajaxUrl, {
                        action: 'was_init_test',
                        was_session: '0'
                    }, function () {
                        wasAjaxSessionTest(false);
                    });
                });
            });
        })(jQuery);
    </script>
	<?php
}
