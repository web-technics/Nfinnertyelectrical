<?php

namespace cnb\admin\settings;

use cnb\admin\api\CnbAdminCloud;
use cnb\admin\api\CnbAppRemote;
use cnb\admin\domain\CnbDomainViewEdit;
use cnb\admin\domain\CnbDomainViewUpgradeFinished;
use cnb\admin\domain\CnbDomainViewUpgradeOverview;
use cnb\admin\models\CnbActivation;
use cnb\admin\models\CnbUser;
use cnb\notices\CnbAdminNotices;
use WP_Error;

class CnbApiKeyActivatedView {
    /**
     * @var CnbActivation
     */
    private $activation;

    function header() {
        echo 'Premium activation';
    }

    /**
     * @param $error WP_Error
     *
     * @return void
     */
    private function renderButtonError( $error ) {
        $notice = CnbAdminCloud::cnb_admin_get_error_message( 'create', 'Button', $error );
        CnbAdminNotices::get_instance()->renderNotice( $notice );
    }

    /**
     *
     * @return void
     */
    private function renderButtonCreated() {
        $message = '<p>Your existing button has been migrated.</p>';
        CnbAdminNotices::get_instance()->renderSuccess( $message );
    }

    private function getAllButtonsLink() {
        $url = admin_url( 'admin.php' );

        return add_query_arg(
            array(
                'page' => 'call-now-button',
            ),
            $url );
    }

    private function getNewButtonLink() {
        $url = admin_url( 'admin.php' );

        return add_query_arg(
            array(
                'page'   => 'call-now-button',
                'action' => 'new'
            ),
            $url );
    }

    private function getSettingsLink() {
        $url = admin_url( 'admin.php' );

        return add_query_arg(
            array(
                'page' => 'call-now-button-settings',
            ),
            $url );
    }

    private function renderButtonInfo() {
        $button = $this->activation->button;

        if ( is_wp_error( $button ) ) {
            $this->renderButtonError( $button );

            return;
        }

        // If the activation was not successful, don't assume anything about the button
        if ( ! $this->activation->success ) {
            return;
        }

        // If a button is created, tell the user
        if ( $button ) {
            $this->renderButtonCreated();
        }
    }

    private function renderActivationSuccess() {
        echo '<div style="text-align: center;">';
        echo '<div style="width:200px;margin: 0 auto;">';
        ( new CnbDomainViewUpgradeFinished() )->echoBigYaySvg();
        echo '</div>';
        echo '<h1>You have successfully upgraded to Call Now Button PREMIUM</h1>';
        echo '</div>';
    }

    private function renderGetStarted() {
        $domain = $this->activation->domain;
        if ( $domain === null ) {
            $domain = CnbAppRemote::cnb_remote_get_wp_domain();
        }
        $nonce_field    = wp_nonce_field( 'cnb_update_domain_timezone', '_wpnonce', true, false );
        $timezoneSelect = ( new CnbDomainViewEdit() )->getTimezoneSelect( $domain );
        echo sprintf( '
            <div class="cnb-get-started cnb-plan-features cnb-center top-50">
            <h1 class="cnb-center">Let\'s get started</h1>
            <div class="cnb-flexbox">
              <div class="box">
                <h2>Is this your time zone?</h2>
                <div>
                    %4$s
                    %5$s
                </div>
              </div>
              <div class="box">
                <h2>Manage your buttons</h2>
                <p>
                  <a class="button button-primary" href="%1$s">Create new</a>
                  <a class="button premium-button" href="%2$s">Button overview</a>
                </p>
              </div>
              <div class="box">
                <h2>Check your Settings</h2>
                <p><a class="button premium-button" href="%3$s">Open settings
                  </a></p>
              </div>
            </div>
            </div>',
            esc_url( $this->getNewButtonLink() ),
            esc_url( $this->getAllButtonsLink() ),
            esc_url( $this->getSettingsLink() ),
            // phpcs:ignore WordPress.Security
            $timezoneSelect,
            // phpcs:ignore WordPress.Security
            $nonce_field );
    }

    /**
     *
     * @return void
     */
    private function renderUpgradeToPro() {
        $domain = $this->activation->domain;
        if ( $domain === null ) {
            $domain = CnbAppRemote::cnb_remote_get_wp_domain();
        }
        if ( $domain->type !== 'FREE' ) {
            // Already upgraded, so skip all of this
            return;
        }
        echo '<div class="cnb-plan-features cnb-center top-50">';
        echo '<h1>Upgrade to PRO to remove the branding</h1>';
        ( new CnbDomainViewUpgradeOverview() )->renderUpgradeForm( $domain );
        echo '</div>';
    }

    /**
     * @param $user CnbUser
     *
     * @return void
     */
    private function renderActivationFailure( $user ) {
        if ( ! is_wp_error( $user ) ) {
            echo '<div style="text-align: center"><h2>Premium is already active</h2></div>';

            return;
        }

        echo '<h1>You tried to activate the Premium version, but something went wrong.</h1>';
    }

    private function renderBenefits() {
        echo '<div>';
        echo '<h2 class="cnb-center">You now have access to the following functionality:</h2>';
        ( new CnbDomainViewUpgradeOverview() )->renderBenefits();
        echo '</div>';
    }

    private function renderActivationStatus() {
        $user = CnbAppRemote::cnb_remote_get_user_info();
        if ( $this->activation->success ) {
            $this->renderActivationSuccess();
        }
        if ( ! $this->activation->success && ! is_wp_error( $user ) ) {
            echo '<div style="text-align: center"><h1>Call Now Button Premium is already active</h1></div>';
        }
        if ( $this->activation->success || ! is_wp_error( $user ) ) {
            $this->renderBenefits();
            $this->renderGetStarted();
            $this->renderUpgradeToPro();
        } else {
            $this->renderActivationFailure( $user );
        }
    }

    public function render() {
        add_action( 'cnb_header_name', array( $this, 'header' ) );
        wp_enqueue_script( CNB_SLUG . '-settings-activated' );
        wp_enqueue_script( CNB_SLUG . '-profile' );
        wp_enqueue_script( CNB_SLUG . '-domain-upgrade' );
        wp_enqueue_script( CNB_SLUG . '-timezone-picker-fix' );

        do_action( 'cnb_header' );

        $this->renderActivationStatus();


        // Link to Button (if present)
        $this->renderButtonInfo();

        do_action( 'cnb_footer' );
    }

    /**
     * @param CnbActivation $activation
     *
     * @return void
     */
    public function setActivation( $activation ) {
        $this->activation = $activation;
    }
}
