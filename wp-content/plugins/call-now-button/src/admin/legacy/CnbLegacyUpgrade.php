<?php

namespace cnb\admin\legacy;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

use cnb\utils\CnbAdminFunctions;
use cnb\CnbHeaderNotices;
use cnb\utils\CnbUtils;

class CnbLegacyUpgrade {
    function header() {
        echo 'Unlock extra features';
    }

    function standard_plugin_promobox() {
        ?>
        <div class="cnb-body-column hide-on-mobile">
            <?php
            ( new CnbAdminFunctions() )->cnb_promobox(
                'grey',
                'Standard plugin',
                '<p>&check; One button<br>
                &check; Phone<br><br>
                &check; Circular (single action)<br>
                &check; Buttonbar (single action)<br>
                &check; Action label<br>
                &nbsp;<br>
                </p>
                <hr>
                <p>
                &check; Placement options<br>
                &check; For mobile devices<br>
                &check; Include or exclude pages<br>
                &nbsp;<br>
                &nbsp;<br>
                &nbsp;
                </p>
                <hr>
                <p>
                &check; Google Analytics tracking<br>
                &check; Google Ads conversion tracking<br>
                </p>
                <hr>
                <p>
                &check; Adjust the button size<br>
                &check; Flexible z-index<br>
                &nbsp;
                </p>',
                'admin-plugins',
                '<strong>Free</strong>',
                'Currently active',
                'disabled'
            );
            ?>
        </div>
    <?php }

    function premium_plugin_promobox() {
        $cnb_utils = new CnbUtils();
        ?>
        <div class="cnb-body-column">
            <?php
            ( new CnbAdminFunctions() )->cnb_promobox(
                'purple',
                'Premium',
                '
                <p><strong>&check; Lots of buttons!</strong><br>
                &check; Phone, SMS/Text, Email, Maps, URLs, Anchors (with smooth scroll)<br>
                &check; WhatsApp, Facebook Messenger, Telegram, Signal<br>
                &check; Circular button (single & multi action)<br>
                &check; Buttonbar (multi action)<br>
                &check; Action labels<br>
                &check; WhatsApp chat modal<a href="' . esc_url($cnb_utils->get_support_url('wordpress/buttons/whatsapp-modal/', 'question-mark', 'whatsapp-modal' ) ) . '" target="_blank" class="cnb-nounderscore"><span class="dashicons dashicons-editor-help"></span></a><br>
                </p>
                <hr>
                <p>
                &check; Placement options<br>
                &check; For mobile and desktop/laptop<br>
                &check; Advanced page targeting<br>
                &check; Scheduling<br>
                &check; Button animations (to draw attention)<br>
                &check; Icon selection<br>
                </p>
                <hr>
                <p>
                &check; Google Analytics tracking<br>
                &check; Google Ads conversion tracking<br>
                </p>
                <hr>
                <p>
                &check; Adjust the button size<br>
                &check; Flexible z-index<br>
                &check; Live button preview</p>
                <hr>
                <p class="cnb_align_center"><strong style="text-decoration:underline">FREE</strong> with subtle branding. PRO from &euro;<span class="eur-per-month"></span>/$<span class="usd-per-month"></span> per month.</p>',
                'cloud',
                CnbHeaderNotices::cnb_settings_email_activation_input(),
                'none'
            );
            ?>
        </div>
    <?php }

    function upgrade_faq() { ?>
        <div style="max-width:600px;margin:0 auto">
            <h1 class="cnb-center">FAQ</h1>
            <h3>Can I really get Premium for Free?</h3>
            <p>Yes. You can use all premium features of the Call Now Button for free. No credit card is required. You
                only need an account for that. The difference with the paid Premium plans is that a small "Powered by"
                notice is added to your buttons.</p>
            <h3>Does the Premium plan require an account?</h3>
            <p>Yes. We want the Call Now Button to be accessible to all website owners. Even those that do not have a
                WordPress powered website. The Premium version of the Call Now Button can be used by everyone. You can
                continue to manage your buttons from your WordPress instance, but you could also do this via our web
                app. And should you ever move to a different CMS, your button(s) will just move with you.</p>
            <h3>What is the "powered by" notice on the Free Premium plan?</h3>
            <p>Call Now Button Premium is available for a small yearly or annual fee, but it is also possible to get it
                for <em>free</em>. The free option introduces a small notice to your buttons that says "Powered by Call
                Now Button". It's very delicate and will not distract the the visitor from your content.</p>
        </div>
    <?php }

    public static function render() {
        wp_enqueue_script( CNB_SLUG . '-settings' );
        $view = new CnbLegacyUpgrade();

        add_action( 'cnb_header_name', array( $view, 'header' ) );
        do_action( 'cnb_header' );
        ?>

        <div class="cnb-one-column-section">
            <div class="cnb-body-content">
                <div class="cnb-two-promobox-row">
                    <?php $view->standard_plugin_promobox() ?>
                    <?php $view->premium_plugin_promobox() ?>
                </div>
                <?php $view->upgrade_faq() ?>
            </div>
        </div>
        <hr>
        <?php
        do_action( 'cnb_footer' );
    }
}
