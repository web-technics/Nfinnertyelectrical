<?php

namespace cnb\admin\apikey;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

/**
 * Somewhat simple router, here to ensure we stay in line with the rest.
 *
 * Since APIKey creation is handled via admin-post, the only thing we do here is render the overview.
 */
class CnbApiKeyRouter {
    public static function render() {
        ( new CnbApiKeyView() )->render();
    }
}
