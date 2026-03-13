<?php

namespace cnb\admin\condition;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

use cnb\admin\api\CnbAdminCloud;
use cnb\admin\api\CnbAppRemote;
use cnb\notices\CnbAdminNotices;
use cnb\notices\CnbNotice;
use cnb\utils\CnbUtils;
use WP_Error;

class CnbConditionController {

    /**
     * Used by the Ajax call inside button-overview
     *
     * @param $condition_id string
     *
     * @return CnbCondition|WP_Error|null
     */
    private function deleteWithId( $condition_id ) {
        if ( ( new CnbUtils() )->cnb_check_ajax_referer( 'cnb_delete_condition' ) ) {
            $condition     = new CnbCondition();
            $condition->id = $condition_id;

            $ignore_notifications = array();

            return CnbAdminCloud::cnb_delete_condition( $ignore_notifications, $condition );
        }

        return null;
    }

    /**
     * Via the quick actions, be able to delete a Condition
     *
     * This also means we CANNOT redirect, as we're already halfway into rendering the page,
     * so we add the notification to the adminNotices handler
     *
     * @return CnbCondition
     */
    function delete() {
        $cnb_utils      = new CnbUtils();
        $id             = $cnb_utils->get_query_val( 'id', null );
        $nonce          = $cnb_utils->get_query_val( '_wpnonce', null );
        $action         = 'cnb_delete_condition';
        $nonce_verified = wp_verify_nonce( $nonce, $action );

        if ( $nonce_verified ) {
            $adminNotices            = CnbAdminNotices::get_instance();
            $cnb_cloud_notifications = array();
            $condition               = new CnbCondition();
            $condition->id           = $id;
            $result                  = CnbAdminCloud::cnb_delete_condition( $cnb_cloud_notifications, $condition );
            $adminNotices->notices( $cnb_cloud_notifications );

            return $result;
        }

        return null;
    }

    /**
     * Called via jQuery.post
     *
     * @return void
     */
    public static function delete_ajax() {
        $cnb_utils = new CnbUtils();
        $id        = $cnb_utils->get_post_val( 'id', null );

        $controller = new CnbConditionController();
        $result     = $controller->deleteWithId( $id );
        // Instead of sending just the actual result (which is currently ignored anyway)
        // We sent both the result and an updated button so the preview code can re-render the button
        $return = array(
            'result' => $result,
        );
        wp_send_json( $return );

    }

    /**
     * @param $cnb_cloud_notifications array
     * @param $conditions CnbCondition[]
     *
     * @return void
     */
    private static function create_and_update_post( $cnb_cloud_notifications, $conditions ) {
        $cnb_utils = new CnbUtils();

        // redirect the user to the appropriate page
        $transient_id = 'cnb-' . wp_generate_uuid4();
        set_transient( $transient_id, $cnb_cloud_notifications, HOUR_IN_SECONDS );

        // Create link
        $bid = $cnb_utils->get_post_val( 'bid', null );
        $url = admin_url( 'admin.php' );
        if ( $bid ) {
            $redirect_link =
                add_query_arg(
                    array(
                        'page'   => 'call-now-button',
                        'action' => 'edit',
                        'id'     => $bid,
                        'tid'    => $transient_id,
                        'tab'    => 'visibility',
                    ),
                    $url );
        } else {
            $redirect_link =
                add_query_arg(
                    array(
                        'page'   => 'call-now-button-conditions',
                        'action' => 'edit',
                        'id'     => $conditions[0]->id,
                        'tid'    => $transient_id,
                        'bid'    => $bid
                    ),
                    $url );
        }
        $redirect_url = esc_url_raw( $redirect_link );
        wp_safe_redirect( $redirect_url );
        exit;
    }

    private static function create_and_update( $closure, $action ) {
        $nonce          = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
        $nonce_verified = wp_verify_nonce( $nonce, $action );
        if ( $nonce_verified ) {
            // sanitize the input
            $conditions = filter_input(
                INPUT_POST,
                'conditions',
                FILTER_SANITIZE_STRING,
                FILTER_REQUIRE_ARRAY | FILTER_FLAG_NO_ENCODE_QUOTES );

            $processed_conditions = array();
            if ( is_array( $conditions ) ) {
                foreach ( $conditions as $condition ) {
                    $processed_conditions[] = CnbCondition::fromObject( $condition );
                }
            }

            $closure( $processed_conditions );
        } else {
            wp_die( esc_html__( 'Invalid nonce specified' ), esc_html__( 'Error' ), array(
                'response'  => 403,
                'back_link' => true,
            ) );
        }
    }

    /**
     * This is called to create the condition (via POST admin-post.php)
     * via `call-now-button.php#cnb_admin_create_condition`
     */
    public static function create() {
        /**
         * @param $conditions CnbCondition[]
         *
         * @return void
         */
        $inner  = function ( $conditions ) {
            $cnb_utils               = new CnbUtils();
            $cnb_cloud_notifications = array();
            /** @var $result CnbCondition[] */
            $result = array();
            foreach ( $conditions as $condition ) {
                // do the processing
                $result[] = CnbAdminCloud::cnb_create_condition( $cnb_cloud_notifications, $condition );
            }

            $bid = $cnb_utils->get_post_val( 'bid', null );
            if ( $bid ) {
                // we don't care about the Condition success notification..
                $cnb_cloud_notifications = array_filter( $cnb_cloud_notifications, function ( $item ) {
                    return $item->type != 'success';
                } );
                $button                  = CnbAppRemote::cnb_remote_get_button_full( $bid );
                $button->conditions      = array_merge( $button->conditions, $result );
                CnbAdminCloud::cnb_update_button( $cnb_cloud_notifications, $button );
            }

            self::create_and_update_post( $cnb_cloud_notifications, $result );

        };
        $action = 'cnb_create_condition';
        self::create_and_update( $inner, $action );
    }

    /**
     * This is called to update the condition
     * via `call-now-button.php#cnb_update_condition`
     */
    public static function update() {
        /**
         * @param $conditions CnbCondition[]
         *
         * @return void
         */
        $inner  = function ( $conditions ) {
            $cnb_cloud_notifications = array();
            $result                  = array();
            foreach ( $conditions as $condition ) {
                // do the processing
                $result[] = CnbAdminCloud::cnb_update_condition( $cnb_cloud_notifications, $condition );
            }
            self::create_and_update_post( $cnb_cloud_notifications, $result );
        };
        $action = 'cnb_update_condition';
        self::create_and_update( $inner, $action );
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
     * so in this case: bulk-cnb_list_conditions
     *
     * @return void
     */
    public static function handle_bulk_actions() {
        $cnb_utils      = new CnbUtils();
        $nonce          = $cnb_utils->get_post_val( '_wpnonce' );
        $action         = 'bulk-cnb_list_conditions';
        $nonce_verified = wp_verify_nonce( $nonce, $action );

        if ( $nonce_verified ) {
            $entityIds = filter_input( INPUT_POST, 'cnb_list_condition', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
            if ( $cnb_utils->get_post_val( 'bulk-action' ) === 'delete' ) {
                $cnb_cloud_notifications = array();
                foreach ( $entityIds as $entityId ) {
                    $condition     = new CnbCondition();
                    $condition->id = $entityId;
                    CnbAdminCloud::cnb_delete_condition( $cnb_cloud_notifications, $condition );
                }

                // Create notice for link (and yes - we ignore the content of $cnb_cloud_notifications here, we just use it to count)
                $notice       = new CnbNotice( 'success', '<p>' . count( $cnb_cloud_notifications ) . ' Condition(s) deleted.</p>' );
                $transient_id = 'cnb-' . wp_generate_uuid4();
                set_transient( $transient_id, array( $notice ), HOUR_IN_SECONDS );

                // Create link
                $url           = admin_url( 'admin.php' );
                $redirect_link =
                    add_query_arg(
                        array(
                            'page' => 'call-now-button-conditions',
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
                        'link_text' => esc_html( 'Go back to the Conditions overview' ),
                        'link_url'  => esc_url_raw( admin_url( 'admin.php' ) . '?page=' . CNB_SLUG . '-conditions' ),
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
