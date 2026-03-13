<?php

namespace cnb\admin\domain;

// don't load directly
use cnb\utils\CnbUtils;

defined( 'ABSPATH' ) || die( '-1' );

class CnbDomainRouter {
    /**
     * Decides to either render the overview or the edit view
     *
     * @return void
     */
    public static function render() {
        $action = ( new CnbUtils() )->get_query_val( 'action', null );
        switch ( $action ) {
            case 'new':
            case 'edit':
                ( new CnbDomainViewEdit() )->render();
                break;
            // This is the quick action where they can delete a single Domain
            case 'delete':
                ( new CnbDomainController() )->delete();
                ( new CnbDomainView() )->render();
                break;
            case 'upgrade':
                ( new CnbDomainViewUpgrade() )->render();
                break;
            default:
                ( new CnbDomainView() )->render();
                break;
        }
    }
}
