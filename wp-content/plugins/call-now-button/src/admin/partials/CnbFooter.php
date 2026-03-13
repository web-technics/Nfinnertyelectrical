<?php

namespace cnb;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

use cnb\admin\api\RemoteTracer;
use cnb\utils\CnbUtils;

class CnbFooter {
    public static function render() {
        self::cnb_show_feedback_collection();
        self::cnb_show_api_traces();
        echo '</div> <!-- /wrap -->'; // This is started in CnbHeader::
    }

    private static function cnb_show_feedback_collection() {
        $cnb_options = get_option( 'cnb' );
        $cnb_utils   = new CnbUtils();

        $url          = admin_url( 'admin.php' );
        $upgrade_link =
            add_query_arg(
                array( 'page' => 'call-now-button-upgrade' ),
                $url );

        ?>
        <div class="feedback-collection">
            <div class="cnb-clear"></div>
            <p class="cnb-url cnb-center"><a
                        href="<?php echo esc_url( $cnb_utils->get_website_url( '', 'footer-links', 'branding' ) ) ?>"
                        target="_blank">Call Now Button<?php if ( $cnb_utils->isCloudActive( $cnb_options ) ) {
                        echo '<span class="cnb_footer_beta">PREMIUM</span>';
                    } ?></a></p>
            <p class="cnb-center">Version <?php echo esc_attr( CNB_VERSION ) ?>
            <p class="cnb-center cnb-spacing">
                <a href="<?php echo esc_url( $cnb_utils->get_support_url( '', 'footer-links', 'support' ) ) ?>"
                   target="_blank"
                   title="Support">Support</a> &middot;
                <a href="<?php echo esc_url( $cnb_utils->get_support_url( 'feature-request/', 'footer-links', 'suggestions' ) ) ?>"
                   target="_blank" title="Feature Requests">Suggestions</a>
                <?php if ( ! $cnb_utils->isCloudActive( $cnb_options ) ) { ?>
                    &middot; <strong><a href="<?php echo esc_url( $upgrade_link ) ?>"
                                        title="Unlock features">Upgrade</a></strong>
                <?php } ?>
            </p>
        </div>
        <?php
    }

    private static function cnb_show_api_traces() {
        $cnb_options = get_option( 'cnb' );
        if ( isset( $cnb_options['footer_show_traces'] ) && $cnb_options['footer_show_traces'] == 1 &&
             isset( $cnb_options['advanced_view'] ) && $cnb_options['advanced_view'] == 1 ) {
            $cnb_remoted_traces = RemoteTracer::getInstance();
            if ( $cnb_remoted_traces ) {
                echo '<p>';
                $traces = $cnb_remoted_traces->getTraces();
                echo '<strong>' . count( $traces ) . '</strong> remote calls executed';
                $totaltime = 0.0;
                foreach ( $traces as $trace ) {
                    $totaltime += (float) $trace->getTime();
                }
                echo ' in <strong>' . esc_html( $totaltime ) . '</strong>sec:<br />';

                echo '<ul>';
                foreach ( $traces as $trace ) {
                    echo '<li>';
                    echo '<code>' . esc_html( $trace->getEndpoint() ) . '</code> in <strong>' . esc_html( $trace->getTime() ) . '</strong>sec';
                    if ( $trace->isCacheHit() ) {
                        echo ' (from cache)';
                    }
                    echo '.</li>';
                }
                echo '</ul>';

                echo '</p>';
            }
        }
    }
}
