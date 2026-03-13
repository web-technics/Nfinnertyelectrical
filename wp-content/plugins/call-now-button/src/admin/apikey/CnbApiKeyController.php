<?php

namespace cnb\admin\apikey;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

use cnb\admin\api\CnbAdminCloud;
use cnb\admin\api\CnbAppRemote;
use cnb\notices\CnbAdminNotices;
use cnb\notices\CnbNotice;
use cnb\utils\CnbUtils;

class CnbApiKeyController {
    /**
     * This is called via add_action to create a new API key
     */
    public static function create() {
        $nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
        if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $nonce, 'cnb_apikey_create' ) ) {

            // sanitize the input
            $apikey_data = filter_input(
                INPUT_POST,
                'apikey',
                FILTER_SANITIZE_STRING,
                FILTER_REQUIRE_ARRAY );

            $apikey       = new CnbApiKey();
            $apikey->name = $apikey_data['name'];

            // do the processing
            $cnb_cloud_notifications = array();
            CnbAdminCloud::cnb_create_apikey( $cnb_cloud_notifications, $apikey );

            // redirect the user to the appropriate page
            $transient_id = 'cnb-' . wp_generate_uuid4();
            set_transient( $transient_id, $cnb_cloud_notifications, HOUR_IN_SECONDS );

            // Create link
            $url           = admin_url( 'admin.php' );
            $redirect_link =
                add_query_arg(
                    array(
                        'page' => 'call-now-button-apikeys',
                        'tid'  => $transient_id
                    ),
                    $url );
            $redirect_url  = esc_url_raw( $redirect_link );
            wp_safe_redirect( $redirect_url );
            exit;
        } else {
            wp_die( esc_html__( 'Invalid nonce specified' ), esc_html__( 'Error' ), array(
                'response'  => 403,
                'back_link' => true,
            ) );
        }
    }

    /**
     * This is the quick action where they can delete a single Action
     *
     * It is always called via/with $_GET parameters
     *
     * @return void
     */
    public function delete() {
        $cnb_utils      = new CnbUtils();
        $id             = $cnb_utils->get_query_val( 'id', null );
        $nonce          = $cnb_utils->get_query_val( '_wpnonce', null );
        $action         = 'cnb_delete_apikey';
        $nonce_verified = wp_verify_nonce( $nonce, $action );
        if ( $nonce_verified ) {
            $cnb_cloud_notifications = array();
            $apikey                  = new CnbApiKey();
            $adminNotices            = CnbAdminNotices::get_instance();
            $apikey->id              = $id;
            CnbAdminCloud::cnb_delete_apikey( $cnb_cloud_notifications, $apikey );

            $adminNotices->notices( $cnb_cloud_notifications );
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
     * so in this case: bulk-cnb_list_apikeys
     *
     * @return void
     */
    public static function handle_bulk_actions() {
        $cnb_utils      = new CnbUtils();
        $nonce          = $cnb_utils->get_post_val( '_wpnonce' );
        $action         = 'bulk-cnb_list_apikeys';
        $nonce_verified = wp_verify_nonce( $nonce, $action );
        if ( $nonce_verified ) {
            $entityIds = filter_input( INPUT_POST, 'cnb_list_apikey', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
            if ( $cnb_utils->get_post_val( 'bulk-action' ) === 'delete' ) {
                foreach ( $entityIds as $entityId ) {
                    $apikey     = new CnbApiKey();
                    $apikey->id = $entityId;
                    CnbAppRemote::cnb_remote_delete_apikey( $apikey );
                }

                // Create notice for link
                $notice       = new CnbNotice( 'success', '<p>' . count( $entityIds ) . ' Api key(s) deleted.</p>' );
                $transient_id = 'cnb-' . wp_generate_uuid4();
                set_transient( $transient_id, array( $notice ), HOUR_IN_SECONDS );

                // Create link
                $url           = admin_url( 'admin.php' );
                $redirect_link =
                    add_query_arg(
                        array(
                            'page' => 'call-now-button-apikeys',
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
                        'link_text' => esc_html( 'Go back to the API Key overview' ),
                        'link_url'  => esc_url_raw( admin_url( 'admin.php' ) . '?page=' . CNB_SLUG . '-apikeys' ),
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
