<?php

namespace cnb\admin\domain;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

use cnb\admin\api\CnbAppRemotePayment;
use cnb\admin\models\CnbPlan;
use cnb\admin\profile\CnbProfileEdit;

class CnbDomainViewUpgradeOverview {

    private function getActiveCurrency( $user ) {
        $active_currency = null;
        if ( $user && ! is_wp_error( $user ) && isset( $user->stripeDetails ) && ! empty( $user->stripeDetails->currency ) ) {
            $active_currency = $user->stripeDetails->currency;
        }

        return $active_currency;
    }

    private function render_hidden_profile() {
        add_thickbox();
        echo '<div id="cnb_admin_page_domain_upgrade_profile" style="display: none;"><div>';
        $view = new CnbProfileEdit();
        $user = $view->render_form( true );
        echo '</div></div>';

        return $user;
    }

    /**
     * Render upgrade form
     *
     * @param $domain CnbDomain
     *
     * @return void
     */
    function render( $domain ) {
        if ( $domain->type !== 'FREE' ) { ?><p>Your domain is currently on the Premium
            <code><?php echo esc_html( $domain->type ) ?></code> plan.</p>
        <?php } ?>

        <h2>Select a plan that works best for <strong><?php echo esc_html( $domain->name ) ?></strong></h2>
        <?php
        $this->renderUpgradeForm( $domain );
        echo '<h3 class="cnb-center">All plans contain the following features:</h3>';
        $this->renderBenefits();
    }

    private function renderJsForUpgradeForm( $user ) {
        $active_currency = $this->getActiveCurrency( $user );
        $profile_set     = false;
        if ( $user && ! is_wp_error( $user ) && isset( $user->address ) && ! empty( $user->address->country ) ) {
            $profile_set = true;
        }
        echo '<script>';
        if ( ! $profile_set ) {
            // Unless a profile hasn't been set yet, in which case, ensure we ask customers for that first
            echo "
            jQuery(() => {
                jQuery('.button-upgrade').hide();
            })";
        } else {
            // Hide the "Next" buttons, we already have a profile
            echo "
            jQuery(() => {
                jQuery('.open-profile-details-modal').hide();
            })";
        }

        if ( $active_currency ) {
            // We already know the currency, so a "select currency" tab menu makes no sense
            echo "
            jQuery(() => {
                jQuery('.nav-tab-wrapper').hide();
            })";
        }
        echo '</script>';
    }

    private function renderStripeJs() {
        // Stripe integration
        // phpcs:ignore WordPress.WP
        echo '<script src="https://js.stripe.com/v3/"></script>';
        echo '<script>
            jQuery(() => {
            try {
                stripe = Stripe("' . esc_js( CnbAppRemotePayment::cnb_remote_get_stripe_key()->key ) . '");
            } catch(e) {
                // Do not show "Live Stripe.js integrations must use HTTPS", we deal with that particular error internally
                if (e && e.message.includes("Live Stripe.js integrations must use HTTPS")) {
                    return;
                }

                showMessage("error", e);
            }
            });
        </script>';
    }

    /**
     * @param $domain CnbDomain
     *
     * @return void
     */
    public function renderUpgradeForm( $domain ) {
        $user = $this->render_hidden_profile();
        $this->renderStripeJs();
        $this->renderJsForUpgradeForm( $user );
        $plans           = CnbAppRemotePayment::cnb_remote_get_plans();
        $active_currency = $this->getActiveCurrency( $user );
        ?>
        <form id="wp_domain_upgrade" method="post">
            <input type="hidden" name="cnb_domain_id" id="cnb_domain_id" value="<?php echo esc_attr( $domain->id ) ?>">
            <h2 class="nav-tab-wrapper">
                <a
                        href="#"
                        data-cnb-currency="eur"
                        class="cnb-currency-select cnb-currency-eur nav-tab
                        <?php if ( $active_currency !== 'usd' ) { ?>nav-tab-active<?php } ?>">
                    Euro (&euro;)</a>
                <a
                        href="#"
                        data-cnb-currency="usd"
                        class="cnb-currency-select
                        cnb-currency-usd
                        nav-tab
                        <?php if ( $active_currency === 'usd' ) { ?>nav-tab-active<?php } ?>">
                    US Dollar ($)</a>
            </h2>
            <div class="cnb-message notice"><p class="cnb-error-message"></p></div>
            <div class="cnb-price-plans">
                <div class="currency-box
                            currency-box-eur
                            cnb-flexbox
                            <?php if ( $active_currency !== 'usd' ) { ?>currency-box-active<?php } ?>">
                    <?php
                    $plan   = $this->get_plan( $plans, 'powered-by-eur-yearly' );
                    $plan_x = floor( $plan->price / 12.0 );
                    $plan_y = round( ( $plan->price / 12.0 ) - floor( $plan->price / 12.0 ), 2 ) * 100;

                    $plan_month   = $this->get_plan( $plans, 'powered-by-eur-monthly' );
                    $annual_discount = $plan->price/(12*$plan_month->price)*100;
                    ?>
                    <div class="pricebox">
                        <h3 class="yearly"><span class="cnb-premium-label">PRO </span>Yearly <span class="cnb-green">Save <?php echo esc_html( $annual_discount ); ?>%!</span></h3>
                        <div class="benefit">All button branding removed</div>
                        <div class="plan-amount"><span class="currency">€</span><span
                                    class="euros"><?php echo esc_html( $plan_x ) ?></span><span
                                    class="cents">.<?php echo esc_html( $plan_y ) ?></span><span class="timeframe">/month</span>
                        </div>
                        <div class="billingprice">
                            Billed at €<?php echo esc_html( $plan->price ); ?> annually
                        </div>
                        <?php $this->get_profile_edit_modal_link(
                            'button button-primary',
                            'Upgrade',
                            'Enter or verify your information',
                            'powered-by-eur-yearly' ); ?>
                        <a class="button button-primary button-upgrade powered-by-eur-yearly" href="#"
                           onclick="cnb_get_checkout('<?php echo esc_js( $plan->id ) ?>')">Upgrade</a>
                    </div>

                    <?php
                    $plan   = $this->get_plan( $plans, 'powered-by-eur-monthly' );
                    $plan_x = floor( $plan->price );
                    $plan_y = round( ( $plan->price ) - floor( $plan->price ), 2 ) * 100;
                    ?>
                    <div class="pricebox">
                        <h3 class="monthly"><span class="cnb-premium-label">PRO </span>Monthly</h3>
                        <div class="benefit">All button branding removed</div>
                        <div class="plan-amount"><span class="currency">€</span><span
                                    class="euros"><?php echo esc_html( $plan_x ) ?></span><span
                                    class="cents">.<?php echo esc_html( $plan_y ) ?></span><span class="timeframe">/month</span>
                        </div>
                        <div class="billingprice">
                            Billed monthly
                        </div>
                        <?php $this->get_profile_edit_modal_link(
                            'button button-secondary',
                            'Upgrade',
                            'Enter or verify your information',
                            'powered-by-eur-monthly' ); ?>
                        <a class="button button-secondary button-upgrade powered-by-eur-monthly" href="#"
                           onclick="cnb_get_checkout('<?php echo esc_js( $plan->id ) ?>')">Upgrade</a>
                    </div>
                </div>
                <div class="currency-box
                            currency-box-usd
                            cnb-flexbox
                            <?php if ( $active_currency === 'usd' ) { ?>currency-box-active<?php } ?>">
                    <?php
                    $plan   = $this->get_plan( $plans, 'powered-by-usd-yearly' );
                    $plan_x = floor( $plan->price / 12.0 );
                    $plan_y = round( ( $plan->price / 12.0 ) - floor( $plan->price / 12.0 ), 2 ) * 100;

                    $plan_month   = $this->get_plan( $plans, 'powered-by-usd-monthly' );
                    $annual_discount = $plan->price/(12*$plan_month->price)*100;
                    ?>
                    <div class="pricebox">
                        <h3 class="yearly"><span class="cnb-premium-label">PRO </span>Yearly <span class="cnb-green">Save <?php echo esc_html( $annual_discount ); ?>%!</span></h3>
                        <div class="benefit">All button branding removed</div>
                        <div class="plan-amount"><span class="currency">$</span><span
                                    class="euros"><?php echo esc_html( $plan_x ) ?></span><span
                                    class="cents">.<?php echo esc_html( $plan_y ) ?></span><span class="timeframe">/month</span>
                        </div>
                        <div class="billingprice">
                            Billed at $<?php echo esc_html( $plan->price ) ?> annually
                        </div>
                        <?php $this->get_profile_edit_modal_link(
                            'button button-primary',
                            'Upgrade',
                            'Enter or verify your information',
                            'powered-by-usd-yearly' ); ?>
                        <a class="button button-primary button-upgrade powered-by-usd-yearly" href="#"
                           onclick="cnb_get_checkout('<?php echo esc_js( $plan->id ) ?>')">Upgrade</a>
                    </div>
                    <?php
                    $plan   = $this->get_plan( $plans, 'powered-by-usd-monthly' );
                    $plan_x = floor( $plan->price );
                    $plan_y = round( ( $plan->price ) - floor( $plan->price ), 2 ) * 100;
                    ?>
                    <div class="pricebox">
                        <h3 class="monthly"><span class="cnb-premium-label">PRO </span>Monthly</h3>
                        <div class="benefit">All button branding removed</div>
                        <div class="plan-amount"><span class="currency">$</span><span
                                    class="euros"><?php echo esc_html( $plan_x ) ?></span><span
                                    class="cents">.<?php echo esc_html( $plan_y ) ?></span><span class="timeframe">/month</span>
                        </div>
                        <div class="billingprice">
                            Billed monthly
                        </div>
                        <?php $this->get_profile_edit_modal_link(
                            'button button-secondary',
                            'Upgrade',
                            'Enter or verify your information',
                            'powered-by-usd-monthly' ); ?>
                        <a class="button button-secondary button-upgrade powered-by-usd-monthly" href="#"
                           onclick="cnb_get_checkout('<?php echo esc_js( $plan->id ) ?>')">Upgrade</a>
                    </div>
                </div>
            </div>
        </form>
        <?php
    }

    public function renderBenefits() {
        echo '
        <div class="cnb-flexbox cnb-plan-features">
            <ul class="cnb-checklist">
                <li><strong>Phone</strong> <span class="only-big-screens">buttons</span></li>
                <li><strong>Email</strong> <span class="only-big-screens">buttons</span></li>
                <li><strong>SMS/text</strong> <span class="only-big-screens">buttons</span></li>
                <li><strong>WhatsApp</strong> <span class="only-big-screens">buttons</span></li>
                <li><strong>Messenger</strong> <span class="only-big-screens">buttons</span></li>
                <li><strong>Signal</strong> <span class="only-big-screens">buttons</span></li>
                <li><strong>Telegram</strong> <span class="only-big-screens">buttons</span></li>
                <li><strong>Location</strong> <span class="only-big-screens">buttons</span></li>
                <li><strong>Links</strong> <span class="only-big-screens">buttons</span></li>
                <li><strong>Smooth scroll</strong> <span class="only-big-screens">buttons</span></li>
            </ul>
            <ul class="cnb-checklist">
                <li><strong>Multiple buttons</strong><br>Add up to 8 buttons to a single page!</li>
                <li><strong>Circular action button</strong><br>The famous single action button</li>
                <li><strong>Multi action buttons</strong><br>Multibutton&trade; (expandable single button)<br>Buttonbar&trade;
                    (Add up to 5 actions to a full width button)
                </li>
                <li><strong>WhatsApp modal</strong><br>A chat-like modal to kickstart the conversation</li>
            </ul>
            <ul class="cnb-checklist">
                <li><strong>Button animations</strong><br>Draw more attention to your buttons with subtle
                    animations
                </li>
                <li><strong>Buttons slide-in</strong><br>Buttons don\'t just appear but smoothly slide into the page.
                </li>
                <li><strong>Advanced page targeting options</strong><br>Ability to select full URLs, entire
                    folders or even url parameters
                </li>
                <li><strong>Scheduling</strong><br>Select days and times your buttons should be visible</li>
                <li><strong>And so much more!</strong></li>
            </ul>
        </div>';
    }

    /**
     * @param $plans CnbPlan[]
     * @param $name string
     *
     * @return CnbPlan|null
     */
    private function get_plan( $plans, $name ) {
        foreach ( $plans as $plan ) {
            if ( $plan->nickname === $name ) {
                return $plan;
            }
        }

        return null;
    }

    /**
     * Echo the link required for the profile modal
     *
     * @param $additional_classes
     * @param $link_text
     * @param $modal_header
     * @param $data_title
     *
     * @return void
     */
    private function get_profile_edit_modal_link(
        $additional_classes = null,
        $link_text = 'Enter or verify your information',
        $modal_header = null,
        $data_title = ''
    ) {
        if ( ! $modal_header ) {
            $modal_header = $link_text;
        }
        $url      = admin_url( 'admin.php' );
        $full_url = add_query_arg(
            array(
                'TB_inline' => 'true',
                'inlineId'  => 'cnb_admin_page_domain_upgrade_profile',
                'height'    => '525'
            ),
            $url );
        printf(
            '<a href="%1$s" title="%2$s" class="thickbox open-profile-details-modal %4$s" onclick="cnb_btn=\'%5$s\'">%3$s</a>',
            esc_url( $full_url ),
            esc_html( $modal_header ),
            esc_html( $link_text ),
            esc_attr( $additional_classes ),
            esc_attr( $data_title )
        );
    }
}
