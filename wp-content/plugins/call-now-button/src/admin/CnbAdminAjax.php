<?php

namespace cnb\admin;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

use cnb\admin\api\CnbAppRemote;
use cnb\admin\api\CnbAppRemotePayment;
use cnb\admin\models\CnbUser;
use cnb\admin\profile\CnbProfileController;
use WP_Error;

class CnbAdminAjax {
    /**
     * part of domain-upgrade
     *
     * @return void
     */
    public static function domain_upgrade_get_checkout() {
        $planId   = filter_input( INPUT_POST, 'planId', FILTER_SANITIZE_STRING );
        $domainId = filter_input( INPUT_POST, 'domainId', FILTER_SANITIZE_STRING );

        $url             = admin_url( 'admin.php' );
        $redirect_link   =
            add_query_arg(
                array(
                    'page'    => 'call-now-button-domains',
                    'action'  => 'upgrade',
                    'id'      => $domainId,
                    'upgrade' => 'success'
                ),
                $url );
        $callbackUri     = esc_url_raw( $redirect_link );
        $checkoutSession = CnbAppRemotePayment::cnb_remote_post_subscription( $planId, $domainId, $callbackUri );

        if ( is_wp_error( $checkoutSession ) ) {
            $custom_message_data = $checkoutSession->get_error_data( 'CNB_ERROR' );
            if ( ! empty( $custom_message_data ) ) {
                $custom_message_obj = json_decode( $custom_message_data );
                $message            = $custom_message_obj->message;
                // Strip "request_id"
                if ( stripos( $message, '; request-id' ) !== 0 ) {
                    $message = preg_replace( '/; request-id.*/i', '', $message );
                }
                // Replace "customer" with "domain"
                $message = str_replace( 'customer', 'domain', $message );
                wp_send_json( array(
                    'status'  => 'error',
                    'message' => $message
                ) );
            } else {
                wp_send_json( array(
                    'status'  => 'error',
                    'message' => $checkoutSession->get_error_message()
                ) );
            }
        } else {
            // Get link based on Stripe checkoutSessionId
            wp_send_json( array(
                'status'  => 'success',
                'message' => $checkoutSession->checkoutSessionId
            ) );
        }
        wp_die();
    }

    /**
     * called via jQuery.post
     * @return void
     */
    public static function settings_profile_save() {
        $data = array();
        // Security note: the nonce will be checked via update_user (below),
        // and we sanitize the data via filter_var below
        // phpcs:ignore WordPress.Security
        wp_parse_str( $_POST['data'], $data );
        $controller = new CnbProfileController();
        $nonce      = filter_var( $data['_wpnonce'], FILTER_SANITIZE_STRING );
        $profile    = filter_var( $data['user'], FILTER_SANITIZE_STRING,
            FILTER_REQUIRE_ARRAY | FILTER_FLAG_NO_ENCODE_QUOTES );
        $user       = CnbUser::fromObject( $profile );

        $result = $controller->update_user( $nonce, $user );
        wp_send_json( $result );
    }

    public static function cnb_email_activation() {
        $admin_url = esc_url( admin_url( 'admin.php' ) );

        $custom_email = trim( filter_input( INPUT_POST, 'admin_email', FILTER_SANITIZE_STRING ) );
        if ( is_email( $custom_email ) ) {
            $data = CnbAppRemote::cnb_remote_email_activation( $custom_email, $admin_url );
        } else {
            $data = new WP_Error( 'CNB_EMAIL_INVALID', __( 'Please enter a valid e-mail address.' ) );
            if ( empty( $custom_email ) ) {
                $data = new WP_Error( 'CNB_EMAIL_EMPTY', __( 'Please enter a valid e-mail address.' ) );
            }
        }
        wp_send_json( $data );
    }

    private static function cnb_time_format_( $time ) {
        $time_format    = get_option( 'time_format' );
        $time_formatted = strtotime( $time );

        return date_i18n( $time_format, $time_formatted );
    }

    public static function time_format() {
        $start = trim( filter_input( INPUT_POST, 'start', FILTER_SANITIZE_STRING ) );
        $stop  = trim( filter_input( INPUT_POST, 'stop', FILTER_SANITIZE_STRING ) );
        wp_send_json( array(
                'start' => self::cnb_time_format_( $start ),
                'stop'  => self::cnb_time_format_( $stop ),
            )
        );
    }

    public static function get_plans() {
        $plans                = CnbAppRemotePayment::cnb_remote_get_plans();
        $eur_yearly_plan      = array_filter( $plans, function ( $plan ) {
            return $plan->nickname === 'powered-by-eur-yearly';
        } );
        $eur_yearly_plan      = array_pop( $eur_yearly_plan );
        $eur_yearly_per_month = round( $eur_yearly_plan->price / 12.0, 2 );

        $usd_yearly_plan      = array_filter( $plans, function ( $plan ) {
            return $plan->nickname === 'powered-by-usd-yearly';
        } );
        $usd_yearly_plan      = array_pop( $usd_yearly_plan );
        $usd_yearly_per_month = round( $usd_yearly_plan->price / 12.0, 2 );

        wp_send_json( array(
            'eur_per_month' => $eur_yearly_per_month,
            'usd_per_month' => $usd_yearly_per_month,
        ) );
    }
}
