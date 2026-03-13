<?php

namespace cnb\admin\api;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

use cnb\admin\action\CnbAction;
use cnb\admin\apikey\CnbApiKey;
use cnb\admin\button\CnbButton;
use cnb\admin\condition\CnbCondition;
use cnb\admin\domain\CnbDomain;
use cnb\admin\models\CnbUser;
use JsonSerializable;
use WP_Error;

class CnbAppRemote {

    /**
     * By creating a proxy method, we can easily stub this for testing
     *
     * @return string Site URL with optional path appended.
     */
    function get_site_url() {
        return \get_site_url();
    }

    /**
     * Return a cleaned up version of the Site URL.
     *
     * Removes protocol, port and path (and lowercases it)
     *
     * Example:
     * https://www.TestDomain.com:8080/test becomes www.testdomain.com
     *
     * @return string
     */
    public function cnb_clean_site_url() {
        $siteUrl = $this->get_site_url();

        $url = wp_parse_url( $siteUrl, PHP_URL_HOST );
        if ( $url ) {
            return
                preg_replace( '/^www\./', '',
                    trim(
                        strtolower( $url ) )
                    , 1 );
        }

        // Fallback behavior
        // Order:
        // 1: Strip everything after // (so to remove a potential protocol like http(s)://
        // 2: Strip the port if found, via :1234
        // 3: Strip everything after /, so that "example.org/test" becomes "example.org"
        // 4: Lowercase & trim everything
        // 5: Remove a potential "www." prefix

        return
            preg_replace( '/^www\./', '',
                trim(
                    strtolower( preg_replace( '/\/.*/', '',
                        preg_replace( '/:\d+/', '',
                            preg_replace( '/.*\/\//', '', $siteUrl, 1 ), 1 ), 1 ) )
                )
                , 1 );
    }

    /**
     * @return string usually "https://api.callnowbutton.com"
     */
    public static function cnb_get_api_base() {
        $cnb_options = get_option('cnb');

        return isset( $cnb_options['api_base'] ) ? $cnb_options['api_base'] : 'https://api.callnowbutton.com';
    }

    /**
     * @return string usually "https://user.callnowbutton.com"
     */
    public static function cnb_get_user_base() {
        return str_replace( 'api', 'user', CnbAppRemote::cnb_get_api_base() );
    }

    /**
     * @return string usually "https://static.callnowbutton.com"
     */
    public static function cnb_get_static_base() {
        return str_replace( 'api', 'static', CnbAppRemote::cnb_get_api_base() );
    }

    /**
     * @return int 0 if not found, otherwise the current cache key
     */
    public static function cnb__get_transient_base() {
        $val = get_transient( self::cnb_get_api_base() );
        if ( $val ) {
            return (int) $val;
        }

        return 0;
    }

    /**
     * Set the cache key.
     *
     * @param string|int|null $time Should not be added, but can be used to force a base (mostly used for testing).
     */
    public static function cnb_incr_transient_base($time = null) {
        /** @noinspection PhpTernaryExpressionCanBeReducedToShortVersionInspection */
        $value = $time ? $time : time();
        set_transient( self::cnb_get_api_base(), $value );
    }

    public static function cnb_get_transient_base() {
        return self::cnb__get_transient_base() . self::cnb_get_api_base();
    }

    public static function cnb_remote_get_args( $authenticated = true ) {
        global $cnb_api_key;
        $cnb_options = get_option('cnb');
        $api_key = isset( $cnb_options['api_key'] ) ? $cnb_options['api_key'] : false;

        // Special case, we also need to be able to temporaraly overwrite the API key
        // This is done by functions by setting the special global "$cnb_api_key"
        if (isset($cnb_api_key) && !empty($cnb_api_key)) {
            $api_key = $cnb_api_key;
        }

        $headers = array(
            'Content-Type' => 'application/json',
            'X-CNB-Plugin-Version' => CNB_VERSION,
        );

        if ( $authenticated ) {
            if ( ! $api_key ) {
                return new WP_Error( 'CNB_API_NOT_SETUP_YET' );
            }
            $header_name  = 'X-CNB-Api-Key';
            $header_value = $api_key;

            $headers[ $header_name ] = $header_value;
        }

        return array(
            'headers' => $headers,
        );
    }

    public static function cnb_remote_handle_response( $response ) {
        global $wp_version;
        if ( $response instanceof WP_Error ) {
            if (version_compare( $wp_version, '5.6.0', '>=' )){
                $error = new WP_Error( 'CNB_UNKNOWN_REMOTE_ERROR', 'There was an issue communicating with the CallNowButton API. Please see the detailed error message from the response below.' );
                $error->merge_from( $response );
                return $error;
            }
            return $response;
        }
        if ( $response['response']['code'] == 403 ) {
            return new WP_Error( 'CNB_API_KEY_INVALID', $response['response']['message'] );
        }
        if ( $response['response']['code'] == 404 ) {
            return new WP_Error( 'CNB_ENTITY_NOT_FOUND', $response['response']['message'] );
        }
        // 402 == Payment required
        if ( $response['response']['code'] == 402 ) {
            $body = json_decode( $response['body'] );

            return new WP_Error( 'CNB_PAYMENT_REQUIRED', $response['response']['message'], $body->message );
        }
        if ( $response['response']['code'] != 200 ) {
            return new WP_Error( 'CNB_ERROR', $response['response']['message'], $response['body'] );
        }

        return json_decode( $response['body'] );
    }

    /**
     * DELETE, PATCH support.
     *
     * Includes Trace support
     *
     * @param $url string
     * @param $parsed_args array
     *
     * @return array|WP_Error
     */
    public static function cnb_wp_request( $url, $parsed_args ) {
        $http = _wp_http_get_object();

        $timer    = new RemoteTrace( $url );
        $response = $http->request( $url, $parsed_args );
        $timer->end();

        return $response;
    }

    /**
     * DELETE is missing from WordPress Core.
     *
     * This is inspired by https://developer.wordpress.org/reference/functions/wp_remote_post/
     *
     * @param $url string
     * @param $args array
     *
     * @return array|WP_Error
     */
    public static function wp_remote_delete( $url, $args = array() ) {
        $defaults    = array( 'method' => 'DELETE' );
        $parsed_args = wp_parse_args( $args, $defaults );

        return self::cnb_wp_request( $url, $parsed_args );
    }

    /**
     * PATCH is missing from WordPress Core.
     *
     * This is inspired by https://developer.wordpress.org/reference/functions/wp_remote_post/
     *
     * @param $url string
     * @param $args array
     *
     * @return array|WP_Error
     */
    public static function wp_remote_patch( $url, $args = array() ) {
        $defaults    = array( 'method' => 'PATCH' );
        $parsed_args = wp_parse_args( $args, $defaults );

        return self::cnb_wp_request( $url, $parsed_args );
    }

    /**
     * @param $rest_endpoint string
     * @param $body array|JsonSerializable will be JSON encoded, can be `array` (or class with `JsonSerializable`?)
     *
     * @return mixed|WP_Error
     */
    public static function cnb_remote_patch( $rest_endpoint, $body ) {
        $args = self::cnb_remote_get_args();
        if ( $args instanceof WP_Error ) {
            return $args;
        }

        if ( $body != null ) {
            $args['body'] = wp_json_encode( $body );
        }

        $url      = self::cnb_get_api_base() . $rest_endpoint;
        $response = self::wp_remote_patch( $url, $args );
        self::cnb_incr_transient_base();
        do_action('cnb_after_button_changed');

        return self::cnb_remote_handle_response( $response );
    }

    public static function cnb_remote_delete( $rest_endpoint, $body = null ) {
        $args = self::cnb_remote_get_args();
        if ( $args instanceof WP_Error ) {
            return $args;
        }

        if ( $body != null ) {
            $args['body'] = wp_json_encode( $body );
        }

        $url      = self::cnb_get_api_base() . $rest_endpoint;
        $response = self::wp_remote_delete( $url, $args );
        self::cnb_incr_transient_base();
        do_action('cnb_after_button_changed');

        return self::cnb_remote_handle_response( $response );
    }

    public static function cnb_remote_post( $rest_endpoint, $body = null, $authenticated = true ) {
        $args = self::cnb_remote_get_args( $authenticated );
        if ( $args instanceof WP_Error ) {
            return $args;
        }

        if ( $body != null ) {
            $args['body'] = wp_json_encode( $body );
        }

        $url      = self::cnb_get_api_base() . $rest_endpoint;
        $timer    = new RemoteTrace( $url );
        $response = wp_remote_post( $url, $args );
        self::cnb_incr_transient_base();
        do_action('cnb_after_button_changed');
        $timer->end();

        return self::cnb_remote_handle_response( $response );
    }

    public static function cnb_remote_get( $rest_endpoint, $authenticated = true ) {
        $cnb_get_cache = new CnbGet();
        $args          = self::cnb_remote_get_args( $authenticated );
        if ( $args instanceof WP_Error ) {
            return $args;
        }

        $url      = self::cnb_get_api_base() . $rest_endpoint;
        $timer    = new RemoteTrace( $url );
        $response = $cnb_get_cache->get( $url, $args );
        $timer->setCacheHit( $cnb_get_cache->isLastCallCached() );
        $timer->end();

        return self::cnb_remote_handle_response( $response );
    }

    /**
     * @return CnbUser|WP_Error
     */
    public static function cnb_remote_get_user_info() {
        $rest_endpoint = '/v1/user';

        return CnbUser::fromObject( self::cnb_remote_get( $rest_endpoint ) );
    }

    /**
     * @param $user CnbUser
     *
     * @return CnbUser|WP_Error
     */
    public static function cnb_remote_update_user_info( $user ) {
        $rest_endpoint = '/v1/user';

        return CnbUser::fromObject( self::cnb_remote_patch( $rest_endpoint, $user ) );
    }

    /**
     * This returns the domain matching the WordPress domain
     * @return CnbDomain|WP_Error
     */
    public static function cnb_remote_get_wp_domain() {
        $cnbAppRemote  = new CnbAppRemote();
        $rest_endpoint = '/v1/domain/byName/' . $cnbAppRemote->cnb_clean_site_url();

        return CnbDomain::fromObject( self::cnb_remote_get( $rest_endpoint ) );
    }

    /**
     * @param $id
     *
     * @return CnbDomain|WP_Error
     */
    public static function cnb_remote_get_domain( $id ) {
        $rest_endpoint = '/v1/domain/' . $id;

        return CnbDomain::fromObject( self::cnb_remote_get( $rest_endpoint ) );
    }

    /**
     * @return CnbDomain[]|WP_Error
     */
    public static function cnb_remote_get_domains() {
        $rest_endpoint = '/v1/domain';

        return CnbDomain::fromObjects(self::cnb_remote_get( $rest_endpoint ));
    }

    /**
     * @param $id string
     *
     * @return CnbButton|WP_Error
     */
    public static function cnb_remote_get_button_full( $id ) {
        $rest_endpoint = '/v1/button/' . $id . '/full';

        return CnbButton::fromObject( self::cnb_remote_get( $rest_endpoint ) );
    }

    /**
     * This does not (yet) actually return CnbButton, but a stdclass that resembles it.
     *
     * @return CnbButton[]|WP_Error
     */
    public static function cnb_remote_get_buttons() {
        $rest_endpoint = '/v1/button';

        return CnbButton::fromObjects( self::cnb_remote_get( $rest_endpoint ) );
    }

    /**
     * @return CnbButton[]|WP_Error
     */
    public static function cnb_remote_get_buttons_full() {
        $rest_endpoint = '/v1/button/full';

        return CnbButton::fromObjects( self::cnb_remote_get( $rest_endpoint ) );
    }

    /**
     * @param $id string
     *
     * @return CnbAction|WP_Error
     */
    public static function cnb_remote_get_action( $id ) {
        $rest_endpoint = '/v1/action/' . $id;

        return CnbAction::fromObject(self::cnb_remote_get( $rest_endpoint ));
    }

    /**
     * @return CnbAction[]|WP_Error
     */
    public static function cnb_remote_get_actions() {
        $rest_endpoint = '/v1/action';

        return CnbAction::fromObjects(self::cnb_remote_get( $rest_endpoint ));
    }

    /**
     * @param $id string
     *
     * @return CnbButton|null
     */
    public static function cnb_remote_get_button_for_action( $id ) {
        $rest_endpoint = '/v1/action/button/' . $id;

        $result = self::cnb_remote_get( $rest_endpoint );
        if ( is_array( $result ) && count( $result ) ) {
            return CnbButton::fromObject( $result[0] );
        }

        return null;
    }

    /**
     * @return CnbCondition[]|WP_Error
     */
    public static function cnb_remote_get_conditions() {
        $rest_endpoint = '/v1/condition';

        return CnbCondition::fromObjects(self::cnb_remote_get( $rest_endpoint ));
    }


    /**
     * @param $id string
     *
     * @return CnbCondition|WP_Error
     */
    public static function cnb_remote_get_condition( $id ) {
        $rest_endpoint = '/v1/condition/' . $id;

        return CnbCondition::fromObject(self::cnb_remote_get( $rest_endpoint ));
    }

    /**
     * @param $id string
     *
     * @return CnbButton|WP_Error|null
     */
    public static function cnb_remote_get_button_for_condition( $id ) {
        $rest_endpoint = '/v1/condition/button/' . $id;

        $result = self::cnb_remote_get( $rest_endpoint );
        if ( is_array( $result ) && count( $result ) ) {
            return CnbButton::fromObject( $result[0] );
        }

        return null;
    }

    /**
     * @param $ott string a one-time token to retrieve an API key
     *
     * @return CnbApiKey|WP_Error
     */
    public static function cnb_remote_get_apikey_via_ott( $ott ) {
        $rest_endpoint = '/v1/apikey/ott/' . $ott;

        return CnbApiKey::fromObject(self::cnb_remote_get( $rest_endpoint, false ));
    }

    /**
     * @return CnbApiKey[]|WP_Error
     */
    public static function cnb_remote_get_apikeys() {
        $rest_endpoint = '/v1/apikey';

        return CnbApiKey::fromObjects( self::cnb_remote_get( $rest_endpoint ) );
    }

    /**
     * @param $button CnbButton
     *
     * @return CnbButton|WP_Error
     */
    public static function cnb_remote_update_button( $button ) {
        // Find the ID in the options
        if ( ! $button->id ) {
            return new WP_Error( 'CNB_BUTTON_ID_MISSING', 'buttonId expected, but not found' );
        }

        $rest_endpoint = '/v1/button/' . $button->id;

        return CnbButton::fromObject( self::cnb_remote_patch( $rest_endpoint, $button ) );
    }

    /**
     * @param $domain CnbDomain
     *
     * @return CnbDomain|WP_Error
     */
    public static function cnb_remote_update_domain( $domain ) {
        // Find the ID in the options
        if ( ! $domain->id ) {
            return new WP_Error( 'CNB_DOMAIN_ID_MISSING', 'domainId expected, but not found' );
        }

        $rest_endpoint = '/v1/domain/' . $domain->id;

        return CnbDomain::fromObject( self::cnb_remote_patch( $rest_endpoint, $domain ) );
    }

    /**
     * @param $button CnbButton
     *
     * @return CnbButton|WP_Error
     */
    public static function cnb_remote_delete_button( $button ) {
        if ( ! $button->id ) {
            return new WP_Error( 'CNB_BUTTON_ID_MISSING', 'buttonId expected, but not found' );
        }

        $rest_endpoint = '/v1/button/' . $button->id;

        $delete_result = CnbDeleteResult::fromObject(self::cnb_remote_delete( $rest_endpoint ));
        if ( $delete_result->is_success()) {
            return CnbButton::fromObject( $delete_result->object );
        }

        return $delete_result->get_error();
    }

    /**
     * @param $domain CnbDomain
     *
     * @return CnbDomain|WP_Error
     */
    public static function cnb_remote_delete_domain( $domain ) {
        if ( ! $domain->id ) {
            return new WP_Error( 'CNB_DOMAIN_ID_MISSING', 'domainId expected, but not found' );
        }

        $rest_endpoint = '/v1/domain/' . $domain->id;

        $delete_result = CnbDeleteResult::fromObject(self::cnb_remote_delete( $rest_endpoint ));
        if ( $delete_result->is_success()) {
            return CnbDomain::fromObject( $delete_result->object );
        }

        return $delete_result->get_error();
    }

    /**
     * @param $condition CnbCondition
     *
     * @return CnbCondition|WP_Error
     */
    public static function cnb_remote_delete_condition( $condition ) {
        // Find the ID in the options
        if ( ! $condition->id ) {
            return new WP_Error( 'CNB_CONDITION_ID_MISSING', 'conditionId expected, but not found' );
        }

        $rest_endpoint = '/v1/condition/' . $condition->id;

        $delete_result = CnbDeleteResult::fromObject(self::cnb_remote_delete( $rest_endpoint ));
        if ( $delete_result->is_success()) {
            return CnbCondition::fromObject( $delete_result->object );
        }

        return $delete_result->get_error();
    }

    /**
     * @param $action CnbAction
     *
     * @return CnbAction|WP_Error
     */
    public static function cnb_remote_delete_action( $action ) {
        // Find the ID in the options
        if ( ! $action->id ) {
            return new WP_Error( 'CNB_ACTION_ID_MISSING', 'actionId expected, but not found' );
        }

        $rest_endpoint = '/v1/action/' . $action->id;

        $delete_result = CnbDeleteResult::fromObject(self::cnb_remote_delete( $rest_endpoint ));
        if ( $delete_result->is_success()) {
            return CnbAction::fromObject( $delete_result->object );
        }

        return $delete_result->get_error();
    }

    /**
     * @param CnbApiKey $apikey
     *
     * @return CnbApiKey|WP_Error
     */
    public static function cnb_remote_delete_apikey( $apikey ) {
        // Find the ID in the options
        $apikeyId = $apikey->id;

        if ( ! $apikeyId ) {
            return new WP_Error( 'CNB_APIKEY_ID_MISSING', 'apikeyId expected, but not found' );
        }

        $rest_endpoint = '/v1/apikey/' . $apikeyId;

        $delete_result = CnbDeleteResult::fromObject(self::cnb_remote_delete( $rest_endpoint ));
        if ( $delete_result->is_success()) {
            return CnbApiKey::fromObject( $delete_result->object );
        }

        return $delete_result->get_error();
    }

    /**
     * @param $action CnbAction
     *
     * @return CnbAction|WP_Error
     */
    public static function cnb_remote_update_action( $action ) {
        // Find the action ID in the options
        if ( ! $action->id ) {
            return new WP_Error( 'CNB_ACTION_ID_MISSING', 'actionId expected, but not found' );
        }

        $rest_endpoint = '/v1/action/' . $action->id;

        return CnbAction::fromObject( self::cnb_remote_patch( $rest_endpoint, $action ) );
    }

    /**
     * @param $button CnbButton Single Button object
     *
     * @return CnbButton|WP_Error
     */
    public static function cnb_remote_update_wp_button( $button ) {
        if ( ! $button->id ) {
            return new WP_Error( 'CNB_BUTTON_ID_MISSING', 'buttonId expected, but not found' );
        }

        return CnbButton::fromObject( self::cnb_remote_update_button( $button ) );
    }

    /**
     * @param $domain CnbDomain
     *
     * @return CnbDomain|WP_Error
     */
    public static function cnb_remote_create_domain( $domain ) {
        if ( $domain->id ) {
            return new WP_Error( 'CNB_DOMAIN_ID_FOUND', 'no domainId expected, but one was given' );
        }

        $rest_endpoint = '/v1/domain';

        return CnbDomain::fromObject( self::cnb_remote_post( $rest_endpoint, $domain ) );
    }

    /**
     * @param $button CnbButton Single Button object
     *
     * @return CnbButton|WP_Error
     */
    public static function cnb_remote_create_button( $button ) {
        if ( $button->id ) {
            return new WP_Error( 'CNB_BUTTON_ID_FOUND', 'no buttonId expected, but one was given' );
        }

        $rest_endpoint = '/v1/button';

        return CnbButton::fromObject( self::cnb_remote_post( $rest_endpoint, $button ) );
    }

    /**
     * @param $action CnbAction
     *
     * @return CnbAction|WP_Error
     */
    public static function cnb_remote_create_action( $action ) {
        if ( $action->id ) {
            return new WP_Error( 'CNB_ACTION_ID_FOUND', 'no actionId expected, but one was given' );
        }

        $rest_endpoint = '/v1/action';

        return CnbAction::fromObject( self::cnb_remote_post( $rest_endpoint, $action ) );
    }

    /**
     * @param $condition CnbCondition
     *
     * @return CnbCondition|WP_Error
     */
    public static function cnb_remote_create_condition( $condition ) {
        if ( $condition->id ) {
            return new WP_Error( 'CNB_CONDITION_ID_FOUND', 'no conditionId expected, but one was given' );
        }

        $rest_endpoint = '/v1/condition';

        return CnbCondition::fromObject( self::cnb_remote_post( $rest_endpoint, $condition ) );
    }

    /**
     * @param $condition CnbCondition
     *
     * @return CnbCondition|WP_Error
     */
    public static function cnb_remote_update_condition( $condition ) {
        if ( ! $condition->id ) {
            return new WP_Error( 'CNB_CONDITION_ID_MISSING', 'conditionId expected, but not found' );
        }

        $rest_endpoint = '/v1/condition/' . $condition->id;

        return CnbCondition::fromObject( self::cnb_remote_patch( $rest_endpoint, $condition ) );
    }

    /**
     * @param $apikey CnbApiKey
     *
     * @return CnbApiKey|WP_Error
     */
    public static function cnb_remote_create_apikey( $apikey ) {
        $rest_endpoint = '/v1/apikey';

        return CnbApiKey::fromObject( self::cnb_remote_post( $rest_endpoint, $apikey ) );
    }

    public static function cnb_remote_create_billing_portal() {
        $rest_endpoint = '/v1/stripe/createBillingPortal';

        return self::cnb_remote_post( $rest_endpoint );
    }

    /**
     * Data model:
     * {
     * "email": "jasper+wp-signup-test-02@studiostacks.com",
     * "domain": "http://www.button.local:8000/",
     * "adminUrl": "http://www.button.local:8000/wp-admin"
     * }
     */
    public static function cnb_remote_email_activation( $admin_email, $admin_url ) {
        $cnbAppRemote = new CnbAppRemote();
        $body         = array(
            'email'    => $admin_email,
            'domain'   => $cnbAppRemote->cnb_clean_site_url(),
            'adminUrl' => $admin_url
        );

        $rest_endpoint = '/v1/user/wp';

        return self::cnb_remote_post( $rest_endpoint, $body, false );
    }
}
