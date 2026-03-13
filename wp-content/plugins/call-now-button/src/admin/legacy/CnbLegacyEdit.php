<?php

namespace cnb\admin\legacy;

use cnb\utils\CnbAdminFunctions;
use cnb\utils\CnbUtils;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

class CnbLegacyEdit {
    public static function render() {
        $cnb_options    = get_option( 'cnb' );
        $view           = new CnbLegacyEdit();
        $adminFunctions = new CnbAdminFunctions();
        $cnb_utils      = new CnbUtils();

        wp_enqueue_script( CNB_SLUG . '-legacy-edit' );

        add_action( 'cnb_header_name', array( $view, 'header' ) );

        do_action( 'cnb_header' );
        $view->render_welcome_banner(); ?>
        <div class="cnb-two-column-section">
            <div class="cnb-body-column">
                <div class="cnb-body-content">

                    <h2 class="nav-tab-wrapper">
                        <a href="<?php echo esc_url( $view->create_tab_url( 'basic_options' ) ) ?>"
                           class="nav-tab <?php echo esc_attr( $adminFunctions->is_active_tab( 'basic_options' ) ) ?>"
                           data-tab-name="basic_options">Basics</a>
                        <a href="<?php echo esc_url( $view->create_tab_url( 'extra_options' ) ) ?>"
                           class="nav-tab <?php echo esc_attr( $adminFunctions->is_active_tab( 'extra_options' ) ) ?>"
                           data-tab-name="extra_options">Presentation</a>
                    </h2>

                    <form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ) ?>"
                          class="cnb-container">
                        <?php settings_fields( 'cnb_options' ); ?>
                        <table class="form-table <?php echo esc_attr( $adminFunctions->is_active_tab( 'basic_options' ) ) ?>"
                               data-tab-name="basic_options">
                            <tr>
                                <th colspan="2"></th>
                            </tr>
                            <tr>
                                <th scope="row"><label for="cnb-active">Button status</label></th>
                                <td>
                                    <input type="hidden" name="cnb[active]" value="0"/>
                                    <input id="cnb-active" type="checkbox" name="cnb[active]"
                                           value="1" <?php checked( '1', $cnb_options['active'] ); ?>>
                                    <label for="cnb-active">Enable</label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="cnb-number">Phone number</label> <a
                                            href="<?php echo esc_url( $cnb_utils->get_support_url( 'wordpress-free/basics/phone-number/', 'legacy-basics-question-mark', 'phone-number' ) ) ?>"
                                            target="_blank" class="cnb-nounderscore">
                                        <span class="dashicons dashicons-editor-help"></span>
                                    </a></th>
                                <td><input type="text" id="cnb-number" name="cnb[number]"
                                           value="<?php echo esc_attr( $cnb_options['number'] ) ?>"/></td>
                            </tr>
                            <tr class="button-text">
                                <th scope="row"><label for="buttonTextField">Button text</label> <small
                                            style="font-weight: 400">(optional)</small> <a
                                            href="<?php echo esc_url( $cnb_utils->get_support_url( 'wordpress-free/basics/using-text-buttons/', 'legacy-basics-question-mark', 'using-text-buttons' ) ) ?>"
                                            target="_blank" class="cnb-nounderscore">
                                        <span class="dashicons dashicons-editor-help"></span>
                                    </a></th>
                                <td>
                                    <input id="buttonTextField" type="text" name="cnb[text]"
                                           value="<?php echo esc_attr( $cnb_options['text'] ) ?>" maxlength="30"/>
                                    <p class="description">Leave this field empty to only show an icon.</p>
                                </td>
                            </tr>
                        </table>

                        <table class="form-table <?php echo esc_attr( $adminFunctions->is_active_tab( 'extra_options' ) ) ?>"
                               data-tab-name="extra_options">
                            <tr>
                                <th colspan="2"></th>
                            </tr>

                            <tr>
                                <th scope="row"><label for="cnb-color">Button color</label></th>
                                <td><input id="cnb-color" name="cnb[color]" type="text"
                                           value="<?php echo esc_attr( $cnb_options['color'] ) ?>"
                                           class="cnb-color-field" data-default-color="#009900"/></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="cnb-icon-color">Icon color</label></th>
                                <td><input id="cnb-icon-color" name="cnb[iconcolor]" type="text"
                                           value="<?php echo esc_attr( $cnb_options['iconcolor'] ) ?>"
                                           class="cnb-iconcolor-field" data-default-color="#ffffff"/></td>
                            </tr>
                            <tr>
                                <th scope="row">Position <a
                                            href="<?php echo esc_url( $cnb_utils->get_support_url( 'wordpress-free/presentation/button-position/', 'legacy-presentation-question-mark', 'button-position' ) ) ?>"
                                            target="_blank" class="cnb-nounderscore">
                                        <span class="dashicons dashicons-editor-help"></span>
                                    </a></th>
                                <td class="appearance">
                                    <div class="appearance-options">
                                        <div class="cnb-radio-item">
                                            <input type="radio" id="appearance1" name="cnb[appearance]"
                                                   value="right" <?php checked( 'right', $cnb_options['appearance'] ); ?>>
                                            <label title="right" for="appearance1">Right corner</label>
                                        </div>
                                        <div class="cnb-radio-item">
                                            <input type="radio" id="appearance2" name="cnb[appearance]"
                                                   value="left" <?php checked( 'left', $cnb_options['appearance'] ); ?>>
                                            <label title="left" for="appearance2">Left corner</label>
                                        </div>
                                        <div class="cnb-radio-item">
                                            <input type="radio" id="appearance3" name="cnb[appearance]"
                                                   value="middle" <?php checked( 'middle', $cnb_options['appearance'] ); ?>>
                                            <label title="middle" for="appearance3">Center</label>
                                        </div>
                                        <div class="cnb-radio-item">
                                            <input type="radio" id="appearance4" name="cnb[appearance]"
                                                   value="full" <?php checked( 'full', $cnb_options['appearance'] ); ?>>
                                            <label title="full" for="appearance4">Full bottom</label>
                                        </div>

                                        <!-- Extra placement options -->
                                        <br class="cnb-extra-placement">
                                        <div class="cnb-radio-item cnb-extra-placement <?php echo $cnb_options['appearance'] == 'mright' ? 'cnb-extra-active' : ''; ?>">
                                            <input type="radio" id="appearance5" name="cnb[appearance]"
                                                   value="mright" <?php checked( 'mright', $cnb_options['appearance'] ); ?>>
                                            <label title="mright" for="appearance5">Middle right</label>
                                        </div>
                                        <div class="cnb-radio-item cnb-extra-placement <?php echo $cnb_options['appearance'] == 'mleft' ? 'cnb-extra-active' : ''; ?>">
                                            <input type="radio" id="appearance6" name="cnb[appearance]"
                                                   value="mleft" <?php checked( 'mleft', $cnb_options['appearance'] ); ?>>
                                            <label title="mleft" for="appearance6">Middle left </label>
                                        </div>
                                        <br class="cnb-extra-placement">
                                        <div class="cnb-radio-item cnb-extra-placement <?php echo $cnb_options['appearance'] == 'tright' ? 'cnb-extra-active' : ''; ?>">
                                            <input type="radio" id="appearance7" name="cnb[appearance]"
                                                   value="tright" <?php checked( 'tright', $cnb_options['appearance'] ); ?>>
                                            <label title="tright" for="appearance7">Top right corner</label>
                                        </div>
                                        <div class="cnb-radio-item cnb-extra-placement <?php echo $cnb_options['appearance'] == 'tleft' ? 'cnb-extra-active' : ''; ?>">
                                            <input type="radio" id="appearance8" name="cnb[appearance]"
                                                   value="tleft" <?php checked( 'tleft', $cnb_options['appearance'] ); ?>>
                                            <label title="tleft" for="appearance8">Top left corner</label>
                                        </div>
                                        <div class="cnb-radio-item cnb-extra-placement <?php echo $cnb_options['appearance'] == 'tmiddle' ? 'cnb-extra-active' : ''; ?>">
                                            <input type="radio" id="appearance9" name="cnb[appearance]"
                                                   value="tmiddle" <?php checked( 'tmiddle', $cnb_options['appearance'] ); ?>>
                                            <label title="tmiddle" for="appearance9">Center top</label>
                                        </div>
                                        <div class="cnb-radio-item cnb-extra-placement <?php echo $cnb_options['appearance'] == 'tfull' ? 'cnb-extra-active' : ''; ?>">
                                            <input type="radio" id="appearance10" name="cnb[appearance]"
                                                   value="tfull" <?php checked( 'tfull', $cnb_options['appearance'] ); ?>>
                                            <label title="tfull" for="appearance10">Full top</label>
                                        </div>
                                        <a href="#" id="button-more-placements">More placement options...</a>
                                        <!-- END extra placement options -->
                                    </div>

                                    <div id="hideIconTR">
                                        <br>
                                        <input type="hidden" name="cnb[hideIcon]" value="0"/>
                                        <input id="hide_icon" type="checkbox" name="cnb[hideIcon]"
                                               value="1" <?php checked( '1', $cnb_options['hideIcon'] ); ?>>
                                        <label title="right" for="hide_icon">Remove icon</label>
                                    </div>
                                </td>
                            </tr>
                            <tr class="appearance">
                                <th scope="row"><label for="cnb-show">Limit appearance</label> <a
                                            href="<?php echo esc_url( $cnb_utils->get_support_url( 'wordpress-free/presentation/limit-appearance/', 'legacy-presentation-question-mark', 'limit-appearance' ) ) ?>"
                                            target="_blank" class="cnb-nounderscore">
                                        <span class="dashicons dashicons-editor-help"></span>
                                    </a></th>
                                <td>
                                    <input type="text" id="cnb-show" name="cnb[show]"
                                           value="<?php echo esc_attr( $cnb_options['show'] ) ?>"
                                           placeholder="E.g. 14, 345"/>
                                    <p class="description">Enter IDs of the posts &amp; pages, separated by commas
                                        (leave blank for all). <a
                                                href="<?php echo esc_url( $cnb_utils->get_support_url( 'wordpress-free/presentation/limit-appearance/', 'legacy-presentation-description', 'limit-appearance' ) ) ?>"
                                                target="_blank">Learn more...</a></p>
                                    <div class="cnb-radio-item">
                                        <input id="limit1" type="radio" name="cnb[limit]"
                                               value="include" <?php checked( 'include', $cnb_options['limit'] ); ?> />
                                        <label for="limit1">Limit to these posts and pages.</label>
                                    </div>
                                    <div class="cnb-radio-item">
                                        <input id="limit2" type="radio" name="cnb[limit]"
                                               value="exclude" <?php checked( 'exclude', $cnb_options['limit'] ); ?> />
                                        <label for="limit2">Exclude these posts and pages.</label>
                                    </div>
                                    <br>
                                    <div>
                                        <input type="hidden" name="cnb[frontpage]" value="0"/>
                                        <input id="frontpage" type="checkbox" name="cnb[frontpage]"
                                               value="1" <?php checked( '1', $cnb_options['frontpage'] ); ?>>
                                        <label title="right" for="frontpage">Hide button on front page</label>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <table class="form-table <?php echo esc_attr( $adminFunctions->is_active_tab( 'advanced_options' ) ) ?>">
                            <tr>
                                <th colspan="2"><h2>Advanced Settings</h2></th>
                            </tr>
                            <?php
                            $view->render_tracking();
                            $view->render_conversions();
                            $view->render_zoom();
                            $view->render_zindex();
                            ?>
                        </table>
                        <?php submit_button(); ?>

                    </form>
                </div>
            </div>
            <div class="cnb-postbox-container cnb-side-column">
                <div class="cnb-on-active-tab <?php echo esc_attr( $adminFunctions->is_active_tab( 'basic_options' ) ) ?>">
                    <?php
                    ( new CnbAdminFunctions() )->cnb_promobox(
                        'purple',
                        'Unlock extra power',
                        '<p><span class="cnb-purple">&check;</span> More buttons<br>
                <span class="cnb-purple">&check;</span> Text/SMS, Email, Links, Directions<br>
                <span class="cnb-purple">&check;</span> Signal, Telegram, FB Messenger<br>
                <span class="cnb-purple">&check;</span> WhatsApp with Chat modal<br>
                <span class="cnb-purple">&check;</span> Scheduling<br>
                <span class="cnb-purple">&check;</span> Multi action buttons<br>
                <span class="cnb-purple">&check;</span> Change icons<br>
                <span class="cnb-purple">&check;</span> Button animations<br>
                <span class="cnb-purple">&check;</span> Live previews</p>
                <p>Get all of this and much more in <strong>Premium</strong></p>',
                        'unlock',
                        '',
                        'Get Premium Free',
                        ( new CnbAdminFunctions() )->cnb_legacy_upgrade_page()
                    );
                    ?>
                    <?php
                    ( new CnbAdminFunctions() )->cnb_promobox(
                        'green',
                        'A button for everything!',
                        '<p>&check; SMS/Text<br>
                &check; Email<br>
                &check; Messenger, Telegram, Signal<br>
                &check; WhatsApp with Chat modal<br>
                &check; Directions<br>
                &check; Smooth scroll anchors<br>
                &check; Links</p>',
                        'format-chat',
                        '<strong>It\'s all in Premium!</strong>',
                        'Learn more',
                        ( new CnbAdminFunctions() )->cnb_legacy_upgrade_page()
                    );
                    ?>
                </div>
                <div class="cnb-on-active-tab <?php echo esc_attr( $adminFunctions->is_active_tab( 'extra_options' ) ) ?>">
                    <?php
                    ( new CnbAdminFunctions() )->cnb_promobox(
                        'blue',
                        'Powerful page targeting',
                        '<p>Do you need more flexibility in selecting the pages where you want a button to appear?</p>
                  <p>Sign up to unlock 4 methods for selecting the right pages:</p>
                  <p>&check; Exact URL<br>
                  &check; Path begins with ...<br>
                  &check; URL contains<br>
                  &check; RegEx</p>',
                        'visibility',
                        '',
                        'Learn more',
                        ( new CnbAdminFunctions() )->cnb_legacy_upgrade_page()
                    );
                    ( new CnbAdminFunctions() )->cnb_promobox(
                        'blue',
                        'Go Premium for FREE!',
                        'Premium adds a ton of extra power to the Call Now Button.</p>
                  <p>The Premium Free plan shows a little branding with your buttons but gives you access to all features.</p>
                  <p>Try it out and enjoy scheduling, multiple buttons, more button types, animations and much more!</p>',
                        'money-alt',
                        '',
                        'Try Premium',
                        ( new CnbAdminFunctions() )->cnb_legacy_upgrade_page()
                    );
                    ?>
                </div>
            </div>
        </div>

        <?php
        do_action( 'cnb_footer' );
    }

    function create_tab_url( $tab ) {
        $url = admin_url( 'admin.php' );

        return add_query_arg(
            array(
                'page'   => 'call-now-button',
                'action' => 'edit',
                'tab'    => $tab
            ),
            $url );
    }

    function render_tracking() {
        $cnb_options = get_option( 'cnb' );
        $cnb_utils   = new CnbUtils();
        ?>
        <tr>
            <th scope="row">Click tracking <a
                        href="<?php echo esc_url( $cnb_utils->get_support_url( 'wordpress-free/settings/click-tracking/', 'legacy-settings-question-mark', 'click-tracking' ) ) ?>"
                        target="_blank" class="cnb-nounderscore">
                    <span class="dashicons dashicons-editor-help"></span>
                </a></th>
            <td>
                <div class="cnb-radio-item">
                    <input id="tracking3" type="radio" name="cnb[tracking]"
                           value="0" <?php checked( '0', $cnb_options['tracking'] ); ?> />
                    <label for="tracking3">Disabled</label>
                </div>
                <div class="cnb-radio-item">
                    <input id="tracking4" type="radio" name="cnb[tracking]"
                           value="3" <?php checked( '3', $cnb_options['tracking'] ); ?> />
                    <label for="tracking4">Latest Google Analytics (gtag.js)</label>
                </div>
                <div class="cnb-radio-item">
                    <input id="tracking1" type="radio" name="cnb[tracking]"
                           value="2" <?php checked( '2', $cnb_options['tracking'] ); ?> />
                    <label for="tracking1">Google Universal Analytics (analytics.js)</label>
                </div>
                <div class="cnb-radio-item">
                    <input id="tracking2" type="radio" name="cnb[tracking]"
                           value="1" <?php checked( '1', $cnb_options['tracking'] ); ?> />
                    <label for="tracking2">Classic Google Analytics (ga.js)</label>
                </div>
                <p class="description">Using Google Tag Manager? Set up click tracking in GTM. <a
                            href="<?php echo esc_url( $cnb_utils->get_support_url( 'wordpress-free/settings/google-tag-manager-event-tracking/', 'legacy-settings-description', 'google-tag-manager-event-tracking' ) ) ?>"
                            target="_blank">Learn how to do this...</a></p>
            </td>
        </tr>
        <?php
    }

    function render_conversions() {
        $cnb_options = get_option( 'cnb' );
        $cnb_utils   = new CnbUtils();
        ?>
        <tr>
            <th scope="row">Google Ads <a
                        href="<?php echo esc_url( $cnb_utils->get_support_url( 'wordpress-free/settings/google-ads/', 'legacy-settings-question-mark', 'google-ads' ) ) ?>"
                        target="_blank" class="cnb-nounderscore">
                    <span class="dashicons dashicons-editor-help"></span>
                </a></th>
            <td class="conversions">
                <div class="cnb-radio-item">
                    <input id="cnb_conversions_0" name="cnb[conversions]" type="radio"
                           value="0" <?php checked( '0', $cnb_options['conversions'] ); ?> /> <label
                            for="cnb_conversions_0">Off </label>
                </div>
                <div class="cnb-radio-item">
                    <input id="cnb_conversions_1" name="cnb[conversions]" type="radio"
                           value="1" <?php checked( '1', $cnb_options['conversions'] ); ?> /> <label
                            for="cnb_conversions_1">Conversion Tracking using Google's global site tag </label>
                </div>
                <div class="cnb-radio-item">
                    <input id="cnb_conversions_2" name="cnb[conversions]" type="radio"
                           value="2" <?php checked( '2', $cnb_options['conversions'] ); ?> /> <label
                            for="cnb_conversions_2">Conversion Tracking using JavaScript</label>
                </div>
                <p class="description">Select this option if you want to track clicks on the button as Google Ads
                    conversions. This option requires the Event snippet to be present on the page. <a
                            href="<?php echo esc_url( $cnb_utils->get_support_url( 'wordpress-free/settings/google-ads/', 'legacy-settings-description', 'google-ads' ) ) ?>"
                            target="_blank">Learn more...</a></p>
            </td>
        </tr>
        <?php
    }

    function render_zoom() {
        $cnb_options = get_option( 'cnb' );
        ?>
        <tr class="zoom">
            <th scope="row"><label for="cnb_slider">Button size <span id="cnb_slider_value"></span></label></th>
            <td>
                <label class="cnb_slider_value">Smaller&nbsp;&laquo;&nbsp;</label>
                <input type="range" min="0.7" max="1.3" name="cnb[zoom]"
                       value="<?php echo esc_attr( $cnb_options['zoom'] ) ?>" class="slider" id="cnb_slider" step="0.1">
                <label class="cnb_slider_value">&nbsp;&raquo;&nbsp;Bigger</label>
            </td>
        </tr>
        <?php
    }

    function render_zindex() {
        $cnb_options = get_option( 'cnb' );
        $cnb_utils   = new CnbUtils();
        ?>
        <tr class="z-index">
            <th scope="row"><label for="cnb_order_slider">Order (<span id="cnb_order_value"></span>)</label> <a
                        href="<?php echo esc_url( $cnb_utils->get_support_url( 'wordpress-free/settings/set-order/', 'legacy-settings-question-mark', 'Order' ) ) ?>"
                        target="_blank"
                        class="cnb-nounderscore">
                    <span class="dashicons dashicons-editor-help"></span>
                </a></th>
            <td>
                <label class="cnb_slider_value">Backwards&nbsp;&laquo;&nbsp;</label>
                <input type="range" min="1" max="10" name="cnb[z-index]"
                       value="<?php echo esc_attr( $cnb_options['z-index'] ) ?>" class="slider2" id="cnb_order_slider"
                       step="1">
                <label class="cnb_slider_value">&nbsp;&raquo;&nbsp;Front</label>
                <p class="description">The default (and recommended) value is all the way to the front so the button
                    sits on top of everything else. In case you have a specific usecase where you want something else to
                    sit in front of the Call Now Button (e.g. a chat window or a cookie notice) you can move this
                    backwards one step at a time to adapt it to your situation.</p>
            </td>
        </tr>

        <?php
    }

    function header() {
        echo esc_html( CNB_NAME ) . ' <span class="cnb-version">v' . esc_html( CNB_VERSION ) . '</span>';
    }

    function render_welcome_banner() {
        $legacyController = new CnbLegacyController();
        $cnb_utils        = new CnbUtils();
        if ( ! $legacyController->show_welcome_banner() ) {
            return;
        }
        $dismiss_value = 'welcome-panel';

        $url          = admin_url( 'admin.php' );
        $upgrade_link =
            add_query_arg(
                array( 'page' => 'call-now-button-upgrade' ),
                $url );

        $dismiss_url = add_query_arg( array(
            CNB_SLUG . '_dismiss' => $dismiss_value
        ), $url );

        ?>
        <div id="welcome-banner"
             class="welcome-banner is-dismissible notice-call-now-button"
             data-dismiss-url="<?php echo esc_url( $dismiss_url ) ?>">
            <div class="welcome-banner-content">
                <h2>Welcome to Call&nbsp;Now&nbsp;Button version&nbsp;1.1</h2>
                <div class="welcome-banner-column-container">
                    <div class="welcome-banner-column">
                        <h3>Some cool stats!</h3>
                        <div class="welcome-column-box">
                            <p class="cnb-mobile-inline">🎉 The #1 click-to-call button on WordPress for 10 years!</p>
                            <p class="cnb-mobile-inline">🚀 200k+ active installations and growing every day!</p>
                            <p class="cnb-mobile-inline">❤️ Loved by our users and rated 4.9!</p>
                            <p class="cnb-mobile-inline">💎 Call Now Button <strong>Premium</strong> is SO GOOD!!
                            </p>
                        </div>
                    </div>
                    <div class="welcome-banner-column">
                        <h3>What's in Premium?</h3>
                        <p class="cnb-mobile-inline">+ Create multiple buttons</p>
                        <p class="cnb-mobile-inline">+ WhatsApp, SMS/text, Email, Maps and Links</p>
                        <p class="cnb-mobile-inline">+ WhatsApp chat modal</p>
                        <p class="cnb-mobile-inline">+ Multi action buttons</p>
                        <p class="cnb-mobile-inline">+ Button scheduler</p>
                        <p class="cnb-mobile-inline">+ Icon selection</p>
                        <p class="cnb-mobile-inline">+ Advanced page targeting</p>
                        <p class="cnb-mobile-inline">+ Live preview</p>
                    </div>
                    <div class="welcome-banner-column">
                        <a class="button button-primary button-hero" href="<?php echo esc_url( $upgrade_link ) ?>">Get
                            Premium Free</a>

                        <p><a href="<?php echo esc_url( $upgrade_link ) ?>">More info about Premium</a></p>
                        <h3>Other resources</h3>
                        <p>
                            <a href="<?php echo esc_url( $cnb_utils->get_support_url( 'wordpress-free/', 'welcome-banner', 'Help center' ) ) ?>">Help
                                center</a></p>
                        <p>
                            <a href="<?php echo esc_url( $cnb_utils->get_support_url( 'wordpress-free/#faq', 'welcome-banner', 'FAQ' ) ) ?>">FAQ</a>
                        </p>
                    </div>
                </div>
            </div>
            <button type="button" class="notice-dismiss"><span
                        class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.' ) ?></span></button>
        </div>
    <?php }

}
