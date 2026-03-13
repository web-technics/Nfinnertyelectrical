<?php

namespace cnb\admin\domain;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

use cnb\admin\api\CnbAppRemote;
use cnb\admin\api\CnbAppRemotePayment;
use cnb\admin\profile\CnbProfileEdit;
use cnb\notices\CnbNotice;

class CnbDomainViewUpgrade {
    function header() {
        echo 'Upgrade the Call Now Button';
    }

    /**
     * @return CnbDomain
     */
    private function get_domain() {
        $domain_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );
        $domain    = new CnbDomain();
        if ( strlen( $domain_id ) > 0 && $domain_id != 'new' ) {
            $domain = CnbAppRemote::cnb_remote_get_domain( $domain_id );
        }

        return $domain;
    }

    /**
     * @param $domain CnbDomain
     *
     * @return CnbNotice
     */
    private function get_upgrade_notice( $domain ) {
        $upgradeStatus    = filter_input( INPUT_GET, 'upgrade', FILTER_SANITIZE_STRING );
        $checkoutSesionId = filter_input( INPUT_GET, 'checkout_session_id', FILTER_SANITIZE_STRING );
        if ( $upgradeStatus === 'success?payment=success' ) {
            // Get checkout Session Details
            $session = CnbAppRemotePayment::cnb_remote_get_subscription_session( $checkoutSesionId );
            if ( ! is_wp_error( $session ) ) {
                // This results in a subscription (via ->subscriptionId), get that for ->type
                $subscription = CnbAppRemotePayment::cnb_remote_get_subscription( $session->subscriptionId );

                // This increases the cache ID if needed, since the Domain cache might have changed
                CnbAppRemote::cnb_incr_transient_base();

                return new CnbNotice( 'success', '<p>Your domain <strong>' . esc_html( $domain->name ) . '</strong> has been successfully upgraded to <strong>' . esc_html( $subscription->type ) . '</strong>!</p>' );
            } else {
                return new CnbNotice( 'warning', '<p>Something is going on upgrading domain <strong>' . esc_html( $domain->name ) . '</strong>.</p><p>Error: ' . esc_html( $session->get_error_message() ) . '!</p>' );
            }
        }

        return null;
    }

    function render_content() {
        $domain = CnbDomain::setSaneDefault( $this->get_domain() );

        // Bail out in case of error
        if ( is_wp_error( $domain ) ) {
            return;
        }

        // See if the domain is JUST upgraded
        $notice = $this->get_upgrade_notice( $domain );
        if ( $notice ) {
            // And if so, refetch the domain
            $domain = CnbDomain::setSaneDefault( $this->get_domain() );
        }
        wp_enqueue_script( CNB_SLUG . '-domain-upgrade' );

        // Print the content
        if ( $notice && $domain->type != 'PRO' ) {
            // Probably upgraded, but not reflected yet on the API side. Warn about this
            ( new CnbDomainViewUpgradeInProgress() )->render( $domain );
        } else if ( $domain->type == 'PRO' ) {
            ( new CnbDomainViewUpgradeFinished() )->render( $domain, $notice );
        } else {
            ( new CnbDomainViewUpgradeOverview() )->render( $domain );
        }
    }

    public function render() {
        wp_enqueue_script( CNB_SLUG . '-profile' );

        add_action( 'cnb_header_name', array( $this, 'header' ) );
        do_action( 'cnb_header' );
        $this->render_content();
        do_action( 'cnb_footer' );
    }
}
