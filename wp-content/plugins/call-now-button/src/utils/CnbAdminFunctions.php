<?php

namespace cnb\utils;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

class CnbAdminFunctions {
    /**
     * Get the active tab name (?tab=<name>)
     *
     * @return string
     */
    function get_active_tab_name() {
        $cnb_utils = new CnbUtils();

        return $cnb_utils->get_query_val( 'tab', 'basic_options' );
    }

    /**
     * Returns the CSS class used for active tabs
     *
     * @param $tab_name string name of tab to check
     *
     * @return string
     */
    function is_active_tab( $tab_name ) {
        $active_tab = $this->get_active_tab_name();

        return $active_tab === $tab_name ? 'nav-tab-active' : '';
    }

    /**
     * Return an array of all ButtonTypes
     *
     * @return string[] array of ButtonTypes to their nice names
     */
    function cnb_get_button_types() {
        return array(
            'SINGLE' => 'Single button',
            'FULL'   => 'Buttonbar',
            'MULTI'  => 'Multibutton',
        );
    }

    /**
     * Return an array of all ActionTypes
     *
     * Note(s):
     * - This is NOT in alphabetical order, but rather in order of
     *   what feels more likely to be choosen
     * - HOURS is missing, since that is not implemented yet
     *
     * @return string[] array of ActionType to their nice names
     */
    function cnb_get_action_types() {
        return array(
            'PHONE'    => 'Phone',
            'EMAIL'    => 'Email',
            'ANCHOR'   => 'Anchor (smooth scroll)',
            'LINK'     => 'Link',
            'MAP'      => 'Google Maps',
            'WHATSAPP' => 'WhatsApp',
            'SMS'      => 'SMS/Text',
            'FACEBOOK' => 'Facebook Messenger',
            'SIGNAL'   => 'Signal',
            'TELEGRAM' => 'Telegram',
        );
    }

    function cnb_get_condition_filter_types() {
        return array(
            'INCLUDE' => 'Include',
            'EXCLUDE' => 'Exclude',
        );
    }

    function cnb_get_condition_match_types() {
        return array(
            'SIMPLE'    => 'Page path is:',
            'EXACT'     => 'Page URL is:',
            'SUBSTRING' => 'Page URL contains:',
            'REGEX'     => 'Page URL matches RegEx:',
        );
    }

    /**
     * @param array $original Array of "daysOfWeek", index 0 == Monday, values should be strings and contain "true"
     * in order to be evaulated correctly.
     *
     * @return array cleaned up array with proper booleans for the days.
     */
    function cnb_create_days_of_week_array( $original ) {
        // If original does not exist, leave it as it is
        if ( ! is_array( $original ) ) {
            return $original;
        }

        // Default everything is NOT selected, then we enable only those days that are passed in via $original
        $result = array( false, false, false, false, false, false, false );
        foreach ( $result as $day_of_week_index => $day_of_week ) {
            $day_of_week_is_enabled       = isset( $original[ $day_of_week_index ] ) && ( $original[ $day_of_week_index ] === 'true' || $original[ $day_of_week_index ] === true );
            $result[ $day_of_week_index ] = $day_of_week_is_enabled;
        }

        return $result;
    }

    /**
     * <p>Echo the promobox.</p>
     * <p>The CTA block is optional and displays only when there's a link provided or $cta_button_text = 'none'.</p>
     * <p>Defaut CTA text is "Let's go". Default <code>$icon</code> is flag (value should be a dashicon name)</p>
     *
     * <p><strong>NOTE: $body and $cta_pretext are NOT escaped and are assumed to be pre-escaped (or contain no User input)</strong></p>
     *
     * @param $color string
     * @param $headline string
     * @param $body string Assumed to be pre-escaped HTML, so this will not be (re)escaped
     * @param $icon string
     * @param $cta_pretext string Assumed to be pre-escaped HTML, so this will not be (re)escaped
     * @param $cta_button_text string
     * @param $cta_button_link string URL
     * @param $cta_footer_notice
     *
     * @return void It <code>echo</code>s html output of the promobox
     */
    function cnb_promobox( $color, $headline, $body, $icon = 'flag', $cta_pretext = null, $cta_button_text = 'Let\'s go', $cta_button_link = null, $cta_footer_notice = null ) {
        echo '
        <div id="cnb_upgrade_box" class="cnb-promobox">
            <div class="cnb-promobox-header cnb-promobox-header-' . esc_attr( $color ) . '">
                <span class="dashicons dashicons-' . esc_attr( $icon ) . '"></span>
                <h2 class="hndle">' .
             esc_html( $headline )
             . '</h2>
            </div>
            <div class="inside">
                <div class="cnb-promobox-copy">
                    <div class="cnb_promobox_item">' .
             // phpcs:ignore WordPress.Security
             $body
             . '</div>
                    <div class="clear"></div>';
        if ( ! is_null( $cta_button_link ) || $cta_button_text == 'none' ) {
            echo '
                    <div class="cnb-promobox-action">
                        <div class="cnb-promobox-action-left">' .
                 // phpcs:ignore WordPress.Security
                 $cta_pretext
                 . '</div>';
            if ( $cta_button_text != 'none' && $cta_button_link != 'disabled' ) {
                echo '
                        <div class="cnb-promobox-action-right">
                            <a class="button button-primary button-large" href="' . esc_url( $cta_button_link ) . '">' . esc_html( $cta_button_text ) . '</a>
                        </div>';
            } elseif ( $cta_button_link == 'disabled' ) {
                echo '
                        <div class="cnb-promobox-action-right">
                            <button class="button button-primary button-large" disabled>' . esc_html( $cta_button_text ) . '</a>
                        </div>';
            }
            echo '
                        <div class="clear"></div>';
            if ( ! is_null( $cta_footer_notice ) ) {
                echo '<div class="nonessential" style="padding-top: 5px;">' . esc_html( $cta_footer_notice ) . '</div>';
            }
            echo '
                    </div>
                    ';
        }
        echo '
                </div>
            </div>
        </div>
    ';
    }

    /**
     *
     * Returns the url for the Upgrade to cloud page
     *
     * @return string upgrade page url
     */
    function cnb_legacy_upgrade_page() {
        $url = admin_url( 'admin.php' );

        return add_query_arg( 'page', 'call-now-button-upgrade', $url );
    }
}
