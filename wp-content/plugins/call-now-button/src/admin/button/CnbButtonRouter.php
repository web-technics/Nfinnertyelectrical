<?php

namespace cnb\admin\button;

// don't load directly
use cnb\utils\CnbUtils;

defined( 'ABSPATH' ) || die( '-1' );

class CnbButtonRouter {

    /**
     * Decides to either render the overview or the edit view
     *
     * @return void
     */
    public static function render() {
        $action = ( new CnbUtils() )->get_query_val( 'action', null );
        switch ( $action ) {
            case 'edit':
                ( new CnbButtonViewEdit() )->render();
                break;
            case 'enable':
            case 'disable':
                ( new CnbButtonController() )->enable_disable();
                ( new CnbButtonView() )->render();
                break;
            case 'delete':
                ( new CnbButtonController() )->delete();
                ( new CnbButtonView() )->render();
                break;
            case 'new':
            default:
                ( new CnbButtonView() )->render();
                break;
        }
    }
}
