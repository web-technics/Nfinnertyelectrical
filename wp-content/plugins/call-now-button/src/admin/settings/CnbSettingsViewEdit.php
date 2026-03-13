<?php

namespace cnb\admin\settings;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

use cnb\admin\api\CnbAppRemote;
use cnb\admin\domain\CnbDomain;
use cnb\admin\domain\CnbDomainViewEdit;
use cnb\admin\legacy\CnbLegacyEdit;
use cnb\admin\models\CnbUser;
use cnb\utils\CnbAdminFunctions;
use cnb\notices\CnbAdminNotices;
use cnb\utils\CnbUtils;
use WP_Error;

class CnbSettingsViewEdit {
    function header() {
        echo 'Settings';
    }

    private function create_tab_url( $tab ) {
        $url = admin_url( 'admin.php' );

        return add_query_arg(
            array(
                'page' => 'call-now-button-settings',
                'tab'  => $tab
            ),
            $url );
    }

    /**
     * This is only rendered on the /legacy/ version of the Plugin
     *
     * @return void
     */
    private function render_legacy_options() {
        $view = new CnbLegacyEdit();
        ?>
        <tr>
            <th colspan="2"><h2>Tracking</h2></th>
        </tr>
        <?php
        $view->render_tracking();
        $view->render_conversions();
        ?>
        <tr>
            <th colspan="2"><h2>Button display</h2></th>
        </tr>
        <?php
        $view->render_zoom();
        $view->render_zindex();
    }

    /**
     * @param $cnb_user CnbUser
     *
     * @return void
     */
    private function render_account_options( $cnb_user ) {
        global $wp_version;
        $cnb_options             = get_option( 'cnb' );
        $show_advanced_view_only = array_key_exists( 'advanced_view', $cnb_options ) && $cnb_options['advanced_view'] === 1;
        $adminFunctions          = new CnbAdminFunctions();
        $cnb_utils               = new CnbUtils();

        ?>
        <table data-tab-name="account_options"
               class="form-table <?php echo esc_attr( $adminFunctions->is_active_tab( 'account_options' ) ) ?>">
            <tr>
                <th colspan="2"></th>
            </tr>
            <tr>
                <th scope="row">API key</th>
                <td>
                    <?php if ( is_wp_error( $cnb_user ) || $show_advanced_view_only ) { ?>
                        <label>
                            <input type="text" class="regular-text" name="cnb[api_key]"
                                   id="cnb_api_key"
                                   placeholder="e.g. b52c3f83-38dc-4493-bc90-642da5be7e39"/>
                        </label>
                        <p class="description">Get your API key at <a
                                    href="<?php echo esc_url( $cnb_utils->get_website_url( '', 'settings-account', 'get-api-key' ) ) ?>"><?php echo esc_html( CNB_WEBSITE ) ?></a>
                        </p>
                    <?php } ?>
                    <?php if ( is_wp_error( $cnb_user ) && ! empty( $cnb_options['api_key'] ) ) { ?>
                        <p><span class="dashicons dashicons-warning"></span> There is an API key,
                            but it seems to be invalid or outdated.</p>
                        <p class="description">Clicking "Disconnect account" will drop the API key
                            and disconnect the plugin from your account. You will lose access to
                            your buttons and Premium functionality until you reconnect with a
                            callnowbutton.com account.
                            <br>
                            <input type="button" name="cnb_api_key_delete" id="cnb_api_key_delete"
                                   class="button button-link"
                                   value="<?php esc_attr_e( 'Disconnect account' ) ?>"
                                   onclick="return cnb_delete_apikey();">
                        </p>
                    <?php } ?>
                    <?php if ( ! is_wp_error( $cnb_user ) && isset( $cnb_options['api_key'] ) ) {
                        $icon = version_compare( $wp_version, '5.5.0', '<' ) ? 'dashicons-yes' : 'dashicons-saved';
                        ?>
                        <p><strong><span class="dashicons <?php echo esc_attr( $icon ) ?>"></span>Success!</strong>
                            <Br>The plugin is connected to your callnowbutton.com account.</p>
                        <p class="description">Clicking "Disconnect account" will drop the API key
                            and disconnect the plugin from your account. You will lose access to
                            your buttons and Premium functionality until you reconnect with a
                            callnowbutton.com account.
                            <br>
                            <input type="button" name="cnb_api_key_delete" id="cnb_api_key_delete"
                                   class="button button-link"
                                   value="<?php esc_attr_e( 'Disconnect account' ) ?>"
                                   onclick="return cnb_delete_apikey();">
                        </p>
                        <input type="hidden" name="cnb[api_key]" id="cnb_api_key" value="delete_me"
                               disabled="disabled"/>
                    <?php } ?>
                </td>
            </tr>
            <?php if ( $cnb_user !== null && ! $cnb_user instanceof WP_Error ) { ?>
                <tr>
                    <th scope="row">Account owner</th>
                    <td>
                        <?php echo esc_html( $cnb_user->name ) ?>
                        <?php
                        if ( $cnb_user->email !== $cnb_user->name ) {
                            echo esc_html( ' (' . $cnb_user->email . ')' );
                        } ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Account ID</th>
                    <td><code><?php echo esc_html( $cnb_user->id ) ?></code></td>
                </tr>
            <?php } ?>
        </table>

        <?php
    }

    private function render_advanced_options( $cnb_cloud_domain, $cnb_user ) {
        $cnb_options = get_option( 'cnb' );

        $adminFunctions     = new CnbAdminFunctions();
        $cnbAppRemote       = new CnbAppRemote();
        $cnb_clean_site_url = $cnbAppRemote->cnb_clean_site_url();
        $cnb_cloud_domains  = CnbAppRemote::cnb_remote_get_domains();
        $status             = CnbSettingsController::getStatus( $cnb_options );
        ?>
        <table data-tab-name="advanced_options"
               class="form-table <?php echo esc_attr( $adminFunctions->is_active_tab( 'advanced_options' ) ) ?>">
            <?php if ( isset( $cnb_cloud_domain ) && ! ( $cnb_cloud_domain instanceof WP_Error ) && $status === 'cloud' ) {
                ?>
                <tr>
                    <th colspan="2"><h2>Domain settings</h2></th>
                </tr>
                <?php
                ( new CnbDomainViewEdit() )->render_form_advanced( $cnb_cloud_domain, false );
            } ?>
            <tr class="when-cloud-enabled cnb_advanced_view">
                <th colspan="2"><h2>For power users</h2></th>
            </tr>
            <tr class="when-cloud-enabled cnb_advanced_view">
                <th><label for="cnb-advanced-view">Advanced view</label></th>
                <td>
                    <input type="hidden" name="cnb[advanced_view]" value="0"/>
                    <input id="cnb-advanced-view" class="cnb_toggle_checkbox" type="checkbox"
                           name="cnb[advanced_view]"
                           value="1" <?php checked( '1', $cnb_options['advanced_view'] ); ?> />
                    <label for="cnb-advanced-view" class="cnb_toggle_label">Toggle</label>
                    <span data-cnb_toggle_state_label="cnb-advanced-view"
                          class="cnb_toggle_state cnb_toggle_false">(Disabled)</span>
                    <span data-cnb_toggle_state_label="cnb-advanced-view"
                          class="cnb_toggle_state cnb_toggle_true">Enabled</span>
                    <p class="description">For power users only.</p>
                </td>
            </tr>
            <?php if ( $status === 'cloud' ) { ?>
                <tr class="cnb_advanced_view">
                    <th><label for="cnb-show-traces">Show traces</label></th>
                    <td>
                        <input type="hidden" name="cnb[footer_show_traces]" value="0"/>
                        <input id="cnb-show-traces" class="cnb_toggle_checkbox" type="checkbox"
                               name="cnb[footer_show_traces]"
                               value="1" <?php checked( '1', $cnb_options['footer_show_traces'] ); ?> />
                        <label for="cnb-show-traces" class="cnb_toggle_label">Toggle</label>
                        <span data-cnb_toggle_state_label="cnb-show-traces"
                              class="cnb_toggle_state cnb_toggle_false">(Disabled)</span>
                        <span data-cnb_toggle_state_label="cnb-show-traces"
                              class="cnb_toggle_state cnb_toggle_true">Enabled</span>
                        <p class="description">Display API calls and timings in the footer.</p>
                    </td>
                </tr>
                <?php if ( ! ( $cnb_user instanceof WP_Error ) && isset( $cnb_cloud_domain ) && $status === 'cloud' ) { ?>
                    <tr class="when-cloud-enabled">
                        <th scope="row"><label for="cnb[cloud_use_id]">JavaScript snippet</label></th>
                        <td>
                            <div>
                                <?php if ( $cnb_cloud_domain instanceof WP_Error ) {
                                    CnbAdminNotices::get_instance()->warning( 'Almost there! Create your domain using the button at the top of this page.' )
                                    ?>
                                <?php } ?>
                                <?php if ( isset( $cnb_options['cloud_use_id'] ) ) { ?>
                                    <label><select name="cnb[cloud_use_id]" id="cnb[cloud_use_id]">


                                            <option
                                                    value="<?php echo esc_attr( $cnb_user->id ) ?>"
                                                <?php selected( $cnb_user->id, $cnb_options['cloud_use_id'] ) ?>
                                            >
                                                Full account (all domains)
                                            </option>

                                            <?php
                                            $loop_domains = array_filter( $cnb_cloud_domains, function ( $domain ) use ( $cnb_options, $cnb_clean_site_url ) {
                                                if ( $cnb_options['advanced_view'] != 0 ) {
                                                    return true;
                                                } // In case of advanced mode, show all
                                                if ( $domain->name === $cnb_clean_site_url ) {
                                                    return true;
                                                } // Always show the current domain
                                                if ( $domain->id === $cnb_options['cloud_use_id'] ) {
                                                    return true;
                                                } // If a previous weird option was selected, allow it

                                                return false;
                                            } );
                                            foreach ( $loop_domains as $domain ) { ?>
                                                <option
                                                        value="<?php echo esc_attr( $domain->id ) ?>"
                                                    <?php selected( $domain->id, $cnb_options['cloud_use_id'] ) ?>
                                                >
                                                    <?php echo esc_html( $domain->name ) ?>
                                                    (single domain)
                                                </option>
                                            <?php } ?>

                                        </select></label>
                                <?php } ?>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
                <tr class="when-cloud-enabled cnb_advanced_view">
                    <th><label for="cnb-all-domains">Show all buttons</label></th>
                    <td>
                        <input type="hidden" name="cnb[show_all_buttons_for_domain]" value="0"/>
                        <input id="cnb-all-domains" class="cnb_toggle_checkbox" type="checkbox"
                               name="cnb[show_all_buttons_for_domain]"
                               value="1" <?php checked( '1', $cnb_options['show_all_buttons_for_domain'] ); ?> />
                        <label for="cnb-all-domains" class="cnb_toggle_label">Toggle</label>
                        <span data-cnb_toggle_state_label="cnb-all-domains"
                              class="cnb_toggle_state cnb_toggle_false">(Disabled)</span>
                        <span data-cnb_toggle_state_label="cnb-all-domains"
                              class="cnb_toggle_state cnb_toggle_true">Enabled</span>
                        <p class="description">When checked, the "All Buttons" overview shows all
                            buttons for this account, not just for the current domain.</p>
                    </td>
                </tr>
                <tr class="when-cloud-enabled cnb_advanced_view">
                    <th><label for="cnb[api_base]">API endpoint</label></th>
                    <td><label>
                            <input type="text" id="cnb[api_base]" name="cnb[api_base]"
                                   class="regular-text"
                                   value="<?php echo esc_attr( CnbAppRemote::cnb_get_api_base() ) ?>"/>
                        </label>
                        <p class="description">The API endpoint to use to communicate with the
                            CallNowButton Cloud service.<br/>
                            <strong>Do not change this unless you know what you're doing!</strong>
                        </p>
                    </td>
                </tr>
                <tr class="cnb_advanced_view">
                    <th><label for="cnb-api-caching">API caching</label></th>
                    <td>
                        <input type="hidden" name="cnb[api_caching]" value="0"/>
                        <input id="cnb-api-caching" class="cnb_toggle_checkbox" type="checkbox"
                               name="cnb[api_caching]"
                               value="1" <?php checked( '1', $cnb_options['api_caching'] ); ?> />
                        <label for="cnb-api-caching" class="cnb_toggle_label">Toggle</label>
                        <span data-cnb_toggle_state_label="cnb-api-caching"
                              class="cnb_toggle_state cnb_toggle_false">(Disabled)</span>
                        <span data-cnb_toggle_state_label="cnb-api-caching"
                              class="cnb_toggle_state cnb_toggle_true">Enabled</span>
                        <p class="description">Cache API requests (using WordPress transients)</p>
                    </td>
                </tr>
            <?php } // end of cloud check ?>
        </table>
        <?php
    }

    /**
     * @param $use_cloud boolean
     * @param $cnb_cloud_domain CnbDomain
     *
     * @return void
     */
    private function render_promos( $use_cloud, $cnb_cloud_domain ) {
        echo '<div class="cnb-postbox-container cnb-side-column">';
        if ( ! $use_cloud ) {
            ( new CnbAdminFunctions() )->cnb_promobox(
                'purple',
                'Phones off at 6pm?',
                '<p>Sign up to enable a scheduler that allows you to set the days and hours that you are available.</p>' .
                '<p>You can even replace it with an email button during your off-hours so people can still contact you.</p>',
                'clock',
                '<strong>Use the scheduler!</strong>',
                'Learn more',
                ( new CnbAdminFunctions() )->cnb_legacy_upgrade_page()
            );
            ( new CnbAdminFunctions() )->cnb_promobox(
                'green',
                'More buttons!',
                'Switch to Premium to enable lots of buttons. Coupled with advanced page selection options you can get really creative.</p>' .
                '<p>If you need more phone numbers on a single page, then the Multibutton&trade; and the Buttonbar&trade; give you exactly what you need.</p>',
                'cloud',
                '<strong>Try it out!</strong>',
                'Learn more',
                ( new CnbAdminFunctions() )->cnb_legacy_upgrade_page()
            );
            ( new CnbAdminFunctions() )->cnb_promobox(
                'brown',
                'Get Premium for FREE!',
                'Premium adds a ton of extra power to the Call Now Button.</p>' .
                '<p>The Premium Free plan shows a little branding with your buttons but gives you access to all features.</p>' .
                '<p>Try it out and enjoy scheduling, multiple buttons, more button types, animations and much more!</p>',
                'money-alt',
                '',
                'See what\'s incuded',
                ( new CnbAdminFunctions() )->cnb_legacy_upgrade_page()
            );
        }
        if ( $use_cloud && isset( $cnb_cloud_domain ) && ! is_wp_error( $cnb_cloud_domain ) && $cnb_cloud_domain->type !== 'PRO' ) {
            ( new CnbAdminFunctions() )->cnb_promobox(
                'purple',
                '50% off the annual plan!',
                '<p>Remove the <em>powered by</em> branding from your buttons!</p>' .
                '<p>Benefit from this temporary offer and enjoy unlimited access to all features and publish your buttons without branding.</p>',
                'flag',
                '<strong>&euro;<span class="eur-per-month"></span>/$<span class="usd-per-month"></span> per month</strong>',
                'Upgrade',
                ( new CnbUtils() )->get_cnb_domain_upgrade( $cnb_cloud_domain )
            );
        }
        echo '</div>';
    }

    /**
     * @param $cloud_successful boolean
     * @param $cnb_cloud_domain CnbDomain
     *
     * @return void
     */
    private function render_premium_option( $cloud_successful, $cnb_cloud_domain ) {
        $cnb_options = get_option( 'cnb' );
        ?>
        <tr>
            <th colspan="2"></th>
        </tr>
        <tr>
            <th scope="row">
                <label for="cnb_cloud_enabled">Premium
                    <?php if ( $cnb_options['cloud_enabled'] == 0 ) { ?>
                        <a href="<?php echo esc_url( ( new CnbAdminFunctions() )->cnb_legacy_upgrade_page() ) ?>"
                           class="cnb-nounderscore">
                            <span class="dashicons dashicons-editor-help"></span>
                        </a>
                    <?php } ?>
                    <label>
            </th>
            <td>
                <input type="hidden" name="cnb[cloud_enabled]" value="0"/>
                <input id="cnb_cloud_enabled" class="cnb_toggle_checkbox" name="cnb[cloud_enabled]"
                       type="checkbox"
                       value="1" <?php checked( '1', $cnb_options['cloud_enabled'] ); ?> />
                <label for="cnb_cloud_enabled" class="cnb_toggle_label">Enable Premium</label>
                <span data-cnb_toggle_state_label="cnb_cloud_enabled"
                      class="cnb_toggle_state cnb_toggle_false">(Inactive)</span>
                <span data-cnb_toggle_state_label="cnb_cloud_enabled"
                      class="cnb_toggle_state cnb_toggle_true">Active</span>
                <?php if ( $cnb_options['cloud_enabled'] == 0 ) { ?>
                    <p class="description">Free and paid options available.
                        <a href="<?php echo esc_url( ( new CnbAdminFunctions() )->cnb_legacy_upgrade_page() ) ?>">Learn
                            more</a>
                    </p>
                <?php } ?>

                <?php if ( $cnb_options['cloud_enabled'] == 1 && $cloud_successful && $cnb_cloud_domain->type !== 'PRO' ) { ?>
                    <p class="description">Free and paid options available.
                        <a href="<?php echo esc_url( ( new CnbUtils() )->get_cnb_domain_upgrade( $cnb_cloud_domain ) ) ?>">Learn
                            more</a>
                    </p>
                <?php } ?>
            </td>
        </tr>
        <?php
    }

    function render() {
        $cnb_options = get_option( 'cnb' );

        $adminFunctions = new CnbAdminFunctions();

        wp_enqueue_script( CNB_SLUG . '-settings' );
        wp_enqueue_script( CNB_SLUG . '-timezone-picker-fix' );

        add_action( 'cnb_header_name', array( $this, 'header' ) );

        $cnb_cloud_domain = null;
        $cnb_user         = null;
        $use_cloud        = ( new CnbUtils() )->is_use_cloud( $cnb_options );
        $status           = CnbSettingsController::getStatus( $cnb_options );

        if ( $use_cloud ) {
            $cnb_user = CnbAppRemote::cnb_remote_get_user_info();

            if ( ! ( $cnb_user instanceof WP_Error ) ) {
                // Let's check if the domain already exists
                $cnb_cloud_domain = CnbAppRemote::cnb_remote_get_wp_domain();
                CnbDomain::setSaneDefault( $cnb_cloud_domain );
            }
        }

        do_action( 'cnb_header' );

        $cloud_successful = $status === 'cloud' && isset( $cnb_cloud_domain ) && ! ( $cnb_cloud_domain instanceof WP_Error );
        if ( ! $cloud_successful ) { ?>
            <script>
                jQuery(() => {
                    const counter = jQuery("#cnb-nav-counter")
                    if (counter.length && counter.text() === '!') {
                        counter.hide();
                    }
                });
            </script>
        <?php } ?>

        <div class="cnb-two-column-section">
            <div class="cnb-body-column">
                <div class="cnb-body-content">
                    <h2 class="nav-tab-wrapper">
                        <a data-tab-name="basic_options"
                           href="<?php echo esc_url( $this->create_tab_url( 'basic_options' ) ) ?>"
                           class="nav-tab <?php echo esc_attr( $adminFunctions->is_active_tab( 'basic_options' ) ) ?>">General</a>
                        <?php if ( $use_cloud ) { ?>
                            <a data-tab-name="account_options"
                               href="<?php echo esc_url( $this->create_tab_url( 'account_options' ) ) ?>"
                               class="nav-tab <?php echo esc_attr( $adminFunctions->is_active_tab( 'account_options' ) ) ?>">Account</a>
                            <a data-tab-name="advanced_options"
                               href="<?php echo esc_url( $this->create_tab_url( 'advanced_options' ) ) ?>"
                               class="nav-tab <?php echo esc_attr( $adminFunctions->is_active_tab( 'advanced_options' ) ) ?>">Advanced</a>
                        <?php } ?>
                    </h2>
                    <form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ) ?>"
                          class="cnb-container">
                        <?php settings_fields( 'cnb_options' ); ?>
                        <table data-tab-name="basic_options"
                               class="form-table <?php echo esc_attr( $adminFunctions->is_active_tab( 'basic_options' ) ) ?>">
                            <?php
                            $this->render_premium_option( $cloud_successful, $cnb_cloud_domain );
                            if ( $status !== 'cloud' ) {
                                $this->render_legacy_options();
                            }

                            if ( $cloud_successful ) {
                                $domain_edit = new CnbDomainViewEdit();
                                $domain_edit->render_form_plan_details( $cnb_cloud_domain );
                                $domain_edit->render_form_tracking( $cnb_cloud_domain );
                                $domain_edit->render_form_button_display( $cnb_cloud_domain );
                            } ?>
                        </table>
                        <?php if ( $status === 'cloud' ) {
                            $this->render_account_options( $cnb_user );
                            $this->render_advanced_options( $cnb_cloud_domain, $cnb_user );
                        }
                        ?>
                        <?php submit_button(); ?>
                    </form>
                </div>
            </div>
            <?php $this->render_promos( $use_cloud, $cnb_cloud_domain ); ?>
        </div>

        <?php
        do_action( 'cnb_footer' );
    }
}
