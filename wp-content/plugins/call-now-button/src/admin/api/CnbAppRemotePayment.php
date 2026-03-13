<?php

namespace cnb\admin\api;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

use cnb\admin\models\CnbPlan;
use WP_Error;

class CnbAppRemotePayment {

    /**
     * @return CnbPlan[]|WP_Error
     */
    public static function cnb_remote_get_plans() {
        $rest_endpoint = '/v1/stripe/plans';

        return CnbPlan::fromObjects( CnbAppRemote::cnb_remote_get( $rest_endpoint, false ) );
    }

    public static function cnb_remote_get_stripe_key() {
        $rest_endpoint = '/v1/stripe/key';

        return CnbAppRemote::cnb_remote_get( $rest_endpoint );
    }

    public static function cnb_remote_post_subscription( $planId, $domainId, $callbackUri = null ) {
        $callbackUri = $callbackUri === null
            ? get_site_url()
            : $callbackUri;

        $body = array(
            'plan'        => $planId,
            'domain'      => $domainId,
            'callbackUri' => $callbackUri
        );

        $rest_endpoint = '/v1/subscription';

        return CnbAppRemote::cnb_remote_post( $rest_endpoint, $body );
    }

    public static function cnb_remote_get_subscription_session( $subscriptionSessionId ) {
        $rest_endpoint = '/v1/subscription/session/' . $subscriptionSessionId;

        return CnbAppRemote::cnb_remote_get( $rest_endpoint );
    }

    public static function cnb_remote_get_subscription( $subscriptionId ) {
        $rest_endpoint = '/v1/subscription/' . $subscriptionId;

        return CnbAppRemote::cnb_remote_get( $rest_endpoint );
    }
}
