<?php

namespace cnb\admin\legacy;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

use cnb\notices\CnbAdminNotices;

class CnbLegacyController {

    public function show_welcome_banner() {
        $dismiss_value    = 'welcome-panel';
        $dismissed_option = CnbAdminNotices::get_instance()->get_dismiss_option_name( $dismiss_value );
        $is_dismissed     = CnbAdminNotices::get_instance()->is_dismissed( $dismissed_option );

        return ! $is_dismissed;
    }
}
