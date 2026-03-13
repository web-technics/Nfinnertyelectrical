<?php

namespace cnb\admin\action;

// don't load directly
use cnb\utils\CnbUtils;

defined( 'ABSPATH' ) || die( '-1' );

class CnbActionRouter {
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
                ( new CnbActionViewEdit() )->render();
                break;
            // This is the quick action where they can delete a single Action
            case 'delete':
                ( new CnbActionController() )->delete();
                ( new CnbActionView() )->render();
                break;
            default:
                ( new CnbActionView() )->render();
                break;
        }
    }
}
