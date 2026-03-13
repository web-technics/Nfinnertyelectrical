<?php

namespace cnb;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

use cnb\admin\api\CnbAppRemote;
use cnb\admin\legacy\CnbLegacyController;
use cnb\admin\settings\CnbSettingsController;
use cnb\utils\CnbAdminFunctions;
use cnb\utils\CnbUtils;

class CallNowButton {

    /**
     * Adds the plugin to the options menu
     */
    public static function register_admin_pages() {
        global $wp_version;

        $cnb_options       = get_option( 'cnb' );
        $utils             = new CnbUtils();
        $cnb_cloud_hosting = $utils->isCloudActive( $cnb_options );
        $plugin_title      = apply_filters( 'cnb_plugin_title', CNB_NAME );

        $menu_page_function = $cnb_cloud_hosting ?
            array( 'cnb\admin\button\CnbButtonRouter', 'render' ) :
            array( 'cnb\admin\legacy\CnbLegacyEdit', 'render' );

        $counter            = 0;
        $menu_page_title    = 'Call Now Button<span class="awaiting-mod" id="cnb-nav-counter" style="display: none">' . $counter . '</span>';
        $menu_page_position = $cnb_cloud_hosting ? 30 : 66;

        $legacyController   = new CnbLegacyController();
        $has_welcome_banner = $legacyController->show_welcome_banner() && ! $cnb_cloud_hosting;
        if ( $has_welcome_banner ) {
            $counter ++;
        }

        // Detect errors (specific, - Premium enabled, but API key is not present yet)
        if ( $cnb_cloud_hosting && ! array_key_exists( 'api_key', $cnb_options ) ) {
            $counter = '!';
        }

        if ( $counter ) {
            $menu_page_title = 'Call Now Bu...<span class="awaiting-mod" id="cnb-nav-counter">' . $counter . '</span>';
        }

        // Oldest WordPress only has "smartphone", no "phone" (this is added in a later version)
        $icon_url = version_compare( $wp_version, '5.5.0', '<' ) ? 'dashicons-smartphone' : 'dashicons-phone';

        add_menu_page(
            'Call Now Button - Overview',
            $menu_page_title,
            'manage_options',
            CNB_SLUG,
            $menu_page_function,
            $icon_url,
            $menu_page_position
        );

        if ( $cnb_cloud_hosting ) {
            // Button overview
            add_submenu_page( CNB_SLUG, $plugin_title, 'All buttons', 'manage_options', CNB_SLUG, array(
                'cnb\admin\button\CnbButtonRouter',
                'render'
            ) );

            add_submenu_page( CNB_SLUG, $plugin_title, 'Add New', 'manage_options', CNB_SLUG . '&action=new', array(
                'cnb\admin\button\CnbButtonRouter',
                'render'
            ) );

            if ( $cnb_options['advanced_view'] === 1 ) {
                // Domain overview
                add_submenu_page( CNB_SLUG, $plugin_title, 'Domains', 'manage_options', CNB_SLUG . '-domains', array(
                    'cnb\admin\domain\CnbDomainRouter',
                    'render'
                ) );

                // Action overview
                add_submenu_page( CNB_SLUG, $plugin_title, 'Actions', 'manage_options', CNB_SLUG . '-actions', array(
                    'cnb\admin\action\CnbActionRouter',
                    'render'
                ) );

                // Condition overview
                add_submenu_page( CNB_SLUG, $plugin_title, 'Conditions', 'manage_options', CNB_SLUG . '-conditions', array(
                    'cnb\admin\condition\CnbConditionRouter',
                    'render'
                ) );

                // Apikey overview
                add_submenu_page( CNB_SLUG, $plugin_title, 'API Keys', 'manage_options', CNB_SLUG . '-apikeys', array(
                    'cnb\admin\apikey\CnbApiKeyRouter',
                    'render'
                ) );

                // Profile edit
                add_submenu_page( CNB_SLUG, $plugin_title, 'Profile', 'manage_options', CNB_SLUG . '-profile', array(
                    'cnb\admin\profile\CnbProfileEdit',
                    'render'
                ) );
            } else {
                // Fake out Action overview
                if ( $utils->get_query_val( 'page' ) === 'call-now-button-actions' && $utils->get_query_val( 'action' ) ) {
                    add_submenu_page( CNB_SLUG, $plugin_title, 'Edit action', 'manage_options', CNB_SLUG . '-actions', array(
                        'cnb\admin\action\CnbActionRouter',
                        'render'
                    ) );
                }
                // Fake out Conditions overview
                if ( $utils->get_query_val( 'page' ) === 'call-now-button-conditions' && $utils->get_query_val( 'action' ) ) {
                    add_submenu_page( CNB_SLUG, $plugin_title, 'Edit condition', 'manage_options', CNB_SLUG . '-conditions', array(
                        'cnb\admin\condition\CnbConditionRouter',
                        'render'
                    ) );
                }
                // Fake out Domain upgrade page
                if ( $utils->get_query_val( 'page' ) === 'call-now-button-domains' && $utils->get_query_val( 'action' ) === 'upgrade' ) {
                    add_submenu_page( CNB_SLUG, $plugin_title, 'Upgrade domain', 'manage_options', CNB_SLUG . '-domains', array(
                        'cnb\admin\domain\CnbDomainRouter',
                        'render'
                    ) );
                }
            }
        } else {
            // Legacy edit
            add_submenu_page( CNB_SLUG, $plugin_title, 'My button', 'manage_options', CNB_SLUG, array(
                'cnb\admin\legacy\CnbLegacyEdit',
                'render'
            ) );

            add_submenu_page( CNB_SLUG, $plugin_title, 'Unlock features', 'manage_options', CNB_SLUG . '-upgrade', array(
                'cnb\admin\legacy\CnbLegacyUpgrade',
                'render'
            ) );
        }

        // Settings pages
        add_submenu_page( CNB_SLUG, $plugin_title, 'Settings', 'manage_options', CNB_SLUG . '-settings', array(
            'cnb\admin\settings\CnbSettingsRouter',
            'render'
        ) );
    }

    public static function plugin_meta( $links, $file ) {
        $cnb_options       = get_option( 'cnb' );
        $cnb_utils         = new CnbUtils();
        $cnb_cloud_hosting = $cnb_utils->isCloudActive( $cnb_options );

        if ( $file == CNB_BASENAME ) {

            $url = admin_url( 'admin.php' );

            $button_link =
                add_query_arg(
                    array(
                        'page' => 'call-now-button'
                    ),
                    $url );

            $settings_link =
                add_query_arg(
                    array(
                        'page' => 'call-now-button-settings'
                    ),
                    $url );

            $link_name     = $cnb_cloud_hosting ? __( 'All buttons' ) : __( 'My button' );
            $cnb_new_links = array(
                sprintf( '<a href="%s">%s</a>', esc_url( $button_link ), $link_name ),
                sprintf( '<a href="%s">%s</a>', esc_url( $settings_link ), __( 'Settings' ) ),
                sprintf( '<a href="%s">%s</a>', esc_url( $cnb_utils->get_support_url('', 'wp-plugins-page', 'support') ), __( 'Support' ) )
            );
            array_push(
                $links,
                $cnb_new_links[0],
                $cnb_new_links[1],
                $cnb_new_links[2]
            );
        }

        return $links;
    }

    public static function plugin_add_action_link( $links ) {
        $cnb_options       = get_option( 'cnb' );
        $cnb_cloud_hosting = ( new CnbUtils() )->isCloudActive( $cnb_options );

        $link_name   = $cnb_cloud_hosting ? 'All buttons' : 'My button';
        $url         = admin_url( 'admin.php' );
        $button_link =
            add_query_arg(
                array(
                    'page' => 'call-now-button'
                ),
                $url );
        $button_url  = esc_url( $button_link );
        $button      = sprintf( '<a href="%s">%s</a>', $button_url, $link_name );
        array_unshift( $links, $button );

        if ( ! $cnb_cloud_hosting ) {
            $link_name    = 'Get Premium';
            $upgrade_link =
                add_query_arg(
                    array(
                        'page' => 'call-now-button-upgrade'
                    ),
                    $url );
            $upgrade_url  = esc_url( $upgrade_link );
            $upgrade      = sprintf( '<a style="font-weight: bold;" href="%s">%s</a>', $upgrade_url, $link_name );
            array_unshift( $links, $upgrade );
        }

        return $links;
    }

    public static function options_init() {
        // This ensures that we can validate and change/manipulate the "cnb" options before saving
        register_setting(
            'cnb_options',
            'cnb',
            array(
                'type'              => 'array',
                'description'       => 'Settings for the Legacy and Cloud version of the Call Now Button',
                'sanitize_callback' => array( 'cnb\admin\settings\CnbSettingsController', 'validate_options' ),
                'default'           => CnbSettingsController::get_defaults()
            ) );
    }

    public static function unregister_options() {
        unregister_setting( 'cnb_options', 'cnb' );
    }

    public static function register_styles_and_scripts() {
        wp_register_style(
            CNB_SLUG . '-styling',
            plugins_url( '../resources/style/call-now-button.css', __FILE__ ),
            false,
            CNB_VERSION );
        // Original: https://code.jquery.com/ui/1.13.0/themes/base/jquery-ui.min.css
        wp_register_style(
            CNB_SLUG . '-jquery-ui',
            plugins_url( '../resources/style/jquery-ui.min.css', __FILE__ ),
            false,
            '1.13.0' );
        // Original: https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.12/css/intlTelInput.min.css
        wp_register_style(
            CNB_SLUG . '-intl-tel-input',
            plugins_url( '../resources/style/intlTelInput.min.css', __FILE__ ),
            false,
            '1.13.0' );
        wp_register_style(
            CNB_SLUG . '-client',
            CnbAppRemote::cnb_get_static_base() . '/css/main.css',
            array(),
            CNB_VERSION );

        wp_register_script(
            CNB_SLUG . '-call-now-button',
            plugins_url( '../resources/js/call-now-button.js', __FILE__ ),
            array( 'wp-color-picker' ),
            CNB_VERSION,
            true );
        wp_register_script(
            CNB_SLUG . '-dismiss',
            plugins_url( '../resources/js/dismiss.js', __FILE__ ),
            array( 'jquery', CNB_SLUG . '-call-now-button' ),
            CNB_VERSION,
            true );
        wp_register_script(
            CNB_SLUG . '-timezone-picker-fix',
            plugins_url( '../resources/js/timezone-picker-fix.js', __FILE__ ),
            array( 'jquery', CNB_SLUG . '-call-now-button' ),
            CNB_VERSION,
            true );
        wp_register_script(
            CNB_SLUG . '-action-type-to-icon-text',
            plugins_url( '../resources/js/action-type-to-icon-text.js', __FILE__ ),
            array( 'jquery', CNB_SLUG . '-call-now-button' ),
            CNB_VERSION,
            true );

        wp_register_script(
            CNB_SLUG . '-form-to-json',
            plugins_url( '../resources/js/form-to-json.js', __FILE__ ),
            array( 'jquery', CNB_SLUG . '-call-now-button' ),
            CNB_VERSION,
            true );
        wp_register_script(
            CNB_SLUG . '-preview',
            plugins_url( '../resources/js/preview.js', __FILE__ ),
            array( 'jquery', CNB_SLUG . '-call-now-button' ),
            CNB_VERSION,
            true );
        wp_register_script(
            CNB_SLUG . '-domain-upgrade',
            plugins_url( '../resources/js/domain-upgrade.js', __FILE__ ),
            array( 'jquery', CNB_SLUG . '-call-now-button' ),
            CNB_VERSION,
            true );
        wp_register_script(
            CNB_SLUG . '-settings',
            plugins_url( '../resources/js/settings.js', __FILE__ ),
            array( CNB_SLUG . '-call-now-button' ),
            CNB_VERSION,
            true );
        wp_register_script(
            CNB_SLUG . '-action-edit-scheduler',
            plugins_url( '../resources/js/action-edit-scheduler.js', __FILE__ ),
            array( CNB_SLUG . '-call-now-button' ),
            CNB_VERSION,
            true );
        wp_register_script(
            CNB_SLUG . '-action-edit',
            plugins_url( '../resources/js/action-edit.js', __FILE__ ),
            array( CNB_SLUG . '-call-now-button' ),
            CNB_VERSION,
            true );
        wp_register_script(
            CNB_SLUG . '-form-bulk-rewrite',
            plugins_url( '../resources/js/form-bulk-rewrite.js', __FILE__ ),
            array( CNB_SLUG . '-call-now-button' ),
            CNB_VERSION,
            true );
        wp_register_script(
            CNB_SLUG . '-profile',
            plugins_url( '../resources/js/profile.js', __FILE__ ),
            array( CNB_SLUG . '-call-now-button' ),
            CNB_VERSION,
            true );
        wp_register_script(
            CNB_SLUG . '-legacy-edit',
            plugins_url( '../resources/js/legacy-edit.js', __FILE__ ),
            array( CNB_SLUG . '-call-now-button' ),
            CNB_VERSION,
            true );
        wp_register_script(
            CNB_SLUG . '-jquery-ui-touch-punch',
            plugins_url( '../resources/js/jquery.ui.touch-punch.js', __FILE__ ),
            array( CNB_SLUG . '-call-now-button', 'jquery-ui-sortable' ),
            'v1.0.8',
            true );
        wp_register_script(
            CNB_SLUG . '-condition-edit',
            plugins_url( '../resources/js/condition-edit.js', __FILE__ ),
            array( CNB_SLUG . '-call-now-button' ),
            CNB_VERSION,
            true );
        wp_register_script(
            CNB_SLUG . '-settings-activated',
            plugins_url( '../resources/js/settings-activated.js', __FILE__ ),
            array( CNB_SLUG . '-call-now-button' ),
            CNB_VERSION,
            true );

        // Special case: since the preview functionality depends on this,
        // and the source is always changing - we include it as external script
        wp_register_script(
            CNB_SLUG . '-client',
            CnbAppRemote::cnb_get_static_base() . '/js/client.js',
            array(),
            CNB_VERSION,
            true );

        // Original: https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.12/js/intlTelInput.min.js
        wp_register_script(
            CNB_SLUG . '-intl-tel-input',
            plugins_url( '../resources/js/intlTelInput.min.js', __FILE__ ),
            null,
            '17.0.12',
            true );
    }

    public static function registerGlobalActions() {
        add_action( 'admin_menu', array( 'cnb\CallNowButton', 'register_admin_pages' ) );
        add_filter( 'plugin_row_meta', array( 'cnb\CallNowButton', 'plugin_meta' ), 10, 2 );
        add_filter( 'plugin_action_links_' . CNB_BASENAME, array( 'cnb\CallNowButton', 'plugin_add_action_link' ) );

        add_action( 'admin_init', array( 'cnb\CallNowButton', 'options_init' ) );
        add_action( 'admin_init', array( 'cnb\CallNowButton', 'register_styles_and_scripts' ) );
        add_filter( 'option_cnb', array( 'cnb\admin\settings\CnbSettingsController', 'post_option_cnb' ) );

        // This updates the internal version number, called by CnbAdminNotices::action_admin_init
        add_action( 'cnb_update_' . CNB_VERSION, array( 'cnb\utils\CnbUtils', 'update_version' ) );
    }

    public static function registerHeaderAndFooter() {
        // Generic header/footer
        add_action( 'cnb_header', array( 'cnb\CnbHeader', 'render' ) );
        add_action( 'cnb_footer', array( 'cnb\CnbFooter', 'render' ) );
    }

    /**
     * Page specific actions
     * @return void
     */
    public static function registerPostActions() {
        add_action( 'admin_post_cnb_create_single_button', array( 'cnb\admin\button\CnbButtonController', 'create' ) );
        add_action( 'admin_post_cnb_create_multi_button', array( 'cnb\admin\button\CnbButtonController', 'create' ) );
        add_action( 'admin_post_cnb_create_full_button', array( 'cnb\admin\button\CnbButtonController', 'create' ) );

        add_action( 'admin_post_cnb_update_single_button', array( 'cnb\admin\button\CnbButtonController', 'update' ) );
        add_action( 'admin_post_cnb_update_multi_button', array( 'cnb\admin\button\CnbButtonController', 'update' ) );
        add_action( 'admin_post_cnb_update_full_button', array( 'cnb\admin\button\CnbButtonController', 'update' ) );

        add_action( 'admin_post_cnb_buttons_bulk', array(
            'cnb\admin\button\CnbButtonController',
            'handle_bulk_actions'
        ) );

        add_action( 'admin_post_cnb_apikey_create', array( 'cnb\admin\apikey\CnbApiKeyController', 'create' ) );
        add_action( 'admin_post_cnb_apikey_bulk', array(
            'cnb\admin\apikey\CnbApiKeyController',
            'handle_bulk_actions'
        ) );

        add_action( 'admin_post_cnb_create_condition', array(
            'cnb\admin\condition\CnbConditionController',
            'create'
        ) );
        add_action( 'admin_post_cnb_update_condition', array(
            'cnb\admin\condition\CnbConditionController',
            'update'
        ) );
        add_action( 'admin_post_cnb_conditions_bulk', array(
            'cnb\admin\condition\CnbConditionController',
            'handle_bulk_actions'
        ) );

        add_action( 'admin_post_cnb_create_action', array( 'cnb\admin\action\CnbActionController', 'create' ) );
        add_action( 'admin_post_cnb_update_action', array( 'cnb\admin\action\CnbActionController', 'update' ) );
        add_action( 'admin_post_cnb_actions_bulk', array(
            'cnb\admin\action\CnbActionController',
            'handle_bulk_actions'
        ) );

        add_action( 'admin_post_cnb_create_domain', array( 'cnb\admin\domain\CnbDomainController', 'create' ) );
        add_action( 'admin_post_cnb_update_domain', array( 'cnb\admin\domain\CnbDomainController', 'update' ) );
        add_action( 'admin_post_cnb_domains_bulk', array(
            'cnb\admin\domain\CnbDomainController',
            'handle_bulk_actions'
        ) );

        add_action( 'admin_post_cnb_profile_edit', array( 'cnb\admin\profile\CnbProfileController', 'update' ) );
    }

    public static function registerAjax() {
        add_action( 'wp_ajax_cnb_time_format', array( 'cnb\admin\CnbAdminAjax', 'time_format' ) );
        add_action( 'wp_ajax_cnb_settings_profile_save', array( 'cnb\admin\CnbAdminAjax', 'settings_profile_save' ) );
        add_action( 'wp_ajax_cnb_delete_action', array( 'cnb\admin\action\CnbActionController', 'delete_ajax' ) );
        add_action( 'wp_ajax_cnb_delete_condition', array(
            'cnb\admin\condition\CnbConditionController',
            'delete_ajax'
        ) );
        add_action( 'wp_ajax_cnb_get_checkout', array( 'cnb\admin\CnbAdminAjax', 'domain_upgrade_get_checkout' ) );
        add_action( 'wp_ajax_cnb_email_activation', array( 'cnb\admin\CnbAdminAjax', 'cnb_email_activation' ) );
        add_action( 'wp_ajax_cnb_domain_timezone_change', array(
            'cnb\admin\domain\CnbDomainController',
            'updateTimezone'
        ) );
        add_action( 'wp_ajax_cnb_get_plans', array( 'cnb\admin\CnbAdminAjax', 'get_plans' ) );
    }
}
