<?php

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

require_once dirname( __FILE__ ) . '/autoload.php';
require_once dirname( __FILE__ ) . '/utils/cnb-backwards-compatible.php';

// Only include the WP_CLI suite when it is available
if ( class_exists( 'WP_CLI' ) && class_exists( 'WP_CLI_Command' ) ) {
    require_once dirname( __FILE__ ) . '/cli/CNB_CLI.php';
}

add_action( 'plugins_loaded', array( 'cnb\CallNowButton', 'registerGlobalActions' ) );
add_action( 'plugins_loaded', array( 'cnb\CallNowButton', 'registerHeaderAndFooter' ) );
add_action( 'plugins_loaded', array( 'cnb\CallNowButton', 'registerPostActions' ) );
add_action( 'plugins_loaded', array( 'cnb\CallNowButton', 'registerAjax' ) );

// Ensure we are excluded from certain Caching plugins
add_action( 'plugins_loaded', array( 'cnb\cache\CacheHandler', 'exclude' ) );

// This queues the front-end to be rendered (`wp_loaded` should only fire on the front-end facing site)
add_action( 'wp_loaded', array( 'cnb\renderer\RendererFactory', 'register' ) );
