<?php

namespace cnb\admin\condition;

// don't load directly
use cnb\utils\CnbUtils;

defined( 'ABSPATH' ) || die( '-1' );

class CnbConditionRouter {
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
                ( new CnbConditionViewEdit() )->render();
                break;
            // This is the quick action where they can delete a single Condition
            case 'delete':
                ( new CnbConditionController() )->delete();
                ( new CnbConditionView() )->render();
                break;
            default:
                ( new CnbConditionView() )->render();
                break;
        }
    }
}
