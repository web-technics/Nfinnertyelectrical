<?php

namespace cnb\admin\action;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

use cnb\admin\api\CnbAdminCloud;
use cnb\admin\api\CnbAppRemote;
use cnb\notices\CnbAdminNotices;
use cnb\notices\CnbNotice;
use cnb\utils\CnbUtils;
use WP_Error;

class CnbActionController {

    /**
     * Used by the Ajax call inside button-overview
     *
     * @param $action_id string
     * @param $cnb_cloud_notifications array
     *
     * @return CnbAction|WP_Error|null
     */
    function deleteWithId( $action_id, &$cnb_cloud_notifications = array() ) {
        if ( ( new CnbUtils() )->cnb_check_ajax_referer( 'cnb_delete_action' ) ) {
            $action     = new CnbAction();
            $action->id = $action_id;

            return CnbAdminCloud::cnb_delete_action( $cnb_cloud_notifications, $action );
        }

        return null;
    }

    /**
     * The caller should call this via `jQuery.post(ajaxurl, data)`
     *
     * @return void
     */
    public static function delete_ajax() {
        $cnb_utils = new CnbUtils();
        // Action ID
        $action_id = $cnb_utils->get_post_val( 'id', null );
        $button_id = $cnb_utils->get_post_val( 'bid', null );

        $controller = new CnbActionController();
        $result     = $controller->deleteWithId( $action_id );
        // Instead of sending just the actual result (which is currently ignored anyway)
        // We sent both the result and an updated button so the preview code can re-render the button
        $return = array(
            'result' => $result,
            'button' => CnbAppRemote::cnb_remote_get_button_full( $button_id )->toArray( false )
        );
        wp_send_json( $return );
    }

    /**
     * This is the quick action where they can delete a single Action
     *
     * It is always called via/with $_GET parameters
     *
     * @return void
     */
    function delete() {
        $cnb_utils      = new CnbUtils();
        $id             = $cnb_utils->get_query_val( 'id', null );
        $bid            = $cnb_utils->get_query_val( 'bid', null );
        $nonce          = $cnb_utils->get_query_val( '_wpnonce', null );
        $action         = 'cnb_delete_action';
        $nonce_verified = wp_verify_nonce( $nonce, $action );

        if ( $nonce_verified ) {
            $cnb_cloud_notifications = array();
            $action                  = new CnbAction();
            $adminNotices            = CnbAdminNotices::get_instance();
            $action->id              = $id;
            CnbAdminCloud::cnb_delete_action( $cnb_cloud_notifications, $action );

            if ( ! $bid ) {
                $adminNotices->notices( $cnb_cloud_notifications );
            } else {
                // Create link
                $url      = admin_url( 'admin.php' );
                $new_link =
                    add_query_arg(
                        array(
                            'page'   => 'call-now-button',
                            'action' => 'edit',
                            'id'     => $bid,
                        ),
                        $url );
                $new_url  = esc_url_raw( $new_link );

                $message = '<p>You will be redirected back to the Button overview in 1 second...</p>';
                $message .= '<p>Or click here to go back immediately: <a href="' . $new_url . '">' . $new_url . '</a></p>';
                $message .= '<script type="text/javascript">setTimeout(function(){location.href="' . $new_url . '"} , 1000);   </script>';
                $adminNotices->renderSuccess( $message );
            }
        }
    }

    /**
     * This is called to create an Action
     * via `call-now-button.php#cnb_create_action`
     */
    public static function create() {
        $cnb_cloud_notifications = array();
        $nonce                   = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
        $action                  = 'cnb-action-edit';
        $nonce_verified          = wp_verify_nonce( $nonce, $action );
        $cbn_utils               = new CnbUtils();
        if ( $nonce_verified ) {
            $actions   = filter_input(
                INPUT_POST,
                'actions',
                FILTER_SANITIZE_STRING,
                FILTER_REQUIRE_ARRAY | FILTER_FLAG_NO_ENCODE_QUOTES );
            $action_id = filter_input( INPUT_POST, 'action_id', FILTER_SANITIZE_STRING );
            $action    = CnbAction::fromObject( $actions[ $action_id ] );

            // Do the processing
            $new_action    = CnbAdminCloud::cnb_create_action( $cnb_cloud_notifications, $action );
            $new_action_id = $new_action->id;

            $bid = filter_input( INPUT_POST, 'bid', FILTER_SANITIZE_STRING );
            if ( ! empty( $bid ) ) {
                // Tie this new Action to the provided Button
                $button = CnbAppRemote::cnb_remote_get_button_full( $bid );
                if ( ! ( $button instanceof WP_Error ) ) {
                    $button->actions[] = $new_action;

                    CnbAdminCloud::cnb_update_button( $cnb_cloud_notifications, $button );
                } else {
                    $message                   = CnbAdminCloud::cnb_admin_get_error_message( 'create', 'action', $button );
                    $cnb_cloud_notifications[] = $message;
                }
            }

            // redirect the user to the appropriate page
            $transient_id = 'cnb-' . wp_generate_uuid4();
            set_transient( $transient_id, $cnb_cloud_notifications, HOUR_IN_SECONDS );

            // Create link
            $bid = $cbn_utils->get_query_val( 'bid', null );
            $url = admin_url( 'admin.php' );

            if ( ! empty( $bid ) ) {
                $redirect_link =
                    add_query_arg(
                        array(
                            'page'   => 'call-now-button',
                            'action' => 'edit',
                            'id'     => $bid,
                            'tid'    => $transient_id,
                        ),
                        $url );
            } else {
                $redirect_link =
                    add_query_arg(
                        array(
                            'page'   => 'call-now-button-actions',
                            'action' => 'edit',
                            'id'     => $new_action_id,
                            'tid'    => $transient_id,
                            'bid'    => $bid
                        ),
                        $url );
            }
            $redirect_url = esc_url_raw( $redirect_link );
            wp_safe_redirect( $redirect_url );
            exit;
        } else {
            $url           = admin_url( 'admin.php' );
            $redirect_link =
                add_query_arg(
                    array(
                        'page' => CNB_SLUG
                    ),
                    $url );
            wp_die( esc_html__( 'Invalid nonce specified' ), esc_html__( 'Error' ), array(
                'response'  => 403,
                'back_link' => true,
            ) );
        }
    }

    public static function update() {
        $nonce          = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
        $action         = 'cnb-action-edit';
        $nonce_verified = wp_verify_nonce( $nonce, $action );
        $cnb_utils      = new CnbUtils();
        if ( $nonce_verified ) {
            // sanitize the input
            $actions                 = filter_input(
                INPUT_POST,
                'actions',
                FILTER_SANITIZE_STRING,
                FILTER_REQUIRE_ARRAY | FILTER_FLAG_NO_ENCODE_QUOTES );
            $result                  = '';
            $cnb_cloud_notifications = array();

            foreach ( $actions as $action ) {
                $processed_action = CnbAction::fromObject( $action );
                // do the processing
                $result = CnbAdminCloud::cnb_update_action( $cnb_cloud_notifications, $processed_action );
            }

            // redirect the user to the appropriate page
            $transient_id = 'cnb-' . wp_generate_uuid4();
            set_transient( $transient_id, $cnb_cloud_notifications, HOUR_IN_SECONDS );

            // Create link
            $bid = $cnb_utils->get_query_val( 'bid', null );
            $url = admin_url( 'admin.php' );
            if ( ! empty( $bid ) ) {
                $redirect_link =
                    add_query_arg(
                        array(
                            'page'   => 'call-now-button',
                            'action' => 'edit',
                            'id'     => $bid,
                            'tid'    => $transient_id,
                        ),
                        $url );
            } else {
                $redirect_link =
                    add_query_arg(
                        array(
                            'page'   => CNB_SLUG . '-actions',
                            'action' => 'edit',
                            'id'     => $result->id,
                            'tid'    => $transient_id,
                            'bid'    => $bid
                        ),
                        $url );
            }
            $redirect_url = esc_url_raw( $redirect_link );
            wp_safe_redirect( $redirect_url );
            exit;
        } else {
            $url       = admin_url( 'admin.php' );
            $back_link =
                add_query_arg(
                    array(
                        'page' => CNB_SLUG . '-actions',
                    ),
                    $url );

            wp_die( esc_html__( 'Invalid nonce specified' ), esc_html__( 'Error' ), array(
                'response'  => 403,
                'back_link' => true,
            ) );
        }
    }

    /**
     * This is very similar to the <code>delete()</code> function above.
     *
     * This always has to come via a $_POST request (specifically, via admin-post.php),
     * so this should end in a redirect (or an error via wp_die)
     *
     * Big differences are:
     * - This handles multiple IDs, versus 1
     * - Instead of rendering the Notice, is it stored and the user redirected
     *
     * nonce name via WP_List_Table = bulk-{plural}
     * so in this case: bulk-cnb_list_actions
     *
     * @return void
     */
    public static function handle_bulk_actions() {
        $cnb_utils      = new CnbUtils();
        $nonce          = $cnb_utils->get_post_val( '_wpnonce' );
        $action         = 'bulk-cnb_list_actions';
        $nonce_verified = wp_verify_nonce( $nonce, $action );
        if ( $nonce_verified ) {
            $actionIds = filter_input( INPUT_POST, 'cnb_list_action', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
            if ( $cnb_utils->get_post_val( 'bulk-action' ) === 'delete' ) {
                $cnb_cloud_notifications = array();
                foreach ( $actionIds as $actionId ) {
                    $cnbAction     = new CnbAction();
                    $cnbAction->id = $actionId;
                    CnbAdminCloud::cnb_delete_action( $cnb_cloud_notifications, $cnbAction );
                }
                // Create notice for link (and yes - we ignore the content of $cnb_cloud_notifications here, we just use it to count)
                $notice       = new CnbNotice( 'success', '<p>' . count( $cnb_cloud_notifications ) . ' Action(s) deleted.</p>' );
                $transient_id = 'cnb-' . wp_generate_uuid4();
                set_transient( $transient_id, array( $notice ), HOUR_IN_SECONDS );

                // Create link
                $url           = admin_url( 'admin.php' );
                $redirect_link =
                    add_query_arg(
                        array(
                            'page' => 'call-now-button-actions',
                            'tid'  => $transient_id
                        ),
                        $url );
                $redirect_url  = esc_url_raw( $redirect_link );
                wp_safe_redirect( $redirect_url );
            } else {
                wp_die(
                    esc_html__( 'Unknown Bulk action specified' ),
                    esc_html__( 'Cannot process Bulk action' ),
                    array(
                        'response'  => 403,
                        'link_text' => esc_html( 'Go back to the Actions overview' ),
                        'link_url'  => esc_url_raw( admin_url( 'admin.php' ) . '?page=' . CNB_SLUG . '-actions' ),
                    )
                );
            }
        } else {
            wp_die(
                esc_html__( 'Invalid nonce specified' ),
                esc_html__( 'Error' ),
                array(
                    'response'  => 403,
                    'back_link' => true,
                )
            );
        }
    }
}
