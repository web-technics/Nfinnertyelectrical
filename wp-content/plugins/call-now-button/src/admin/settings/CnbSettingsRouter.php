<?php

namespace cnb\admin\settings;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

class CnbSettingsRouter {

    /**
     * Decides to either render the overview or the edit view
     *
     * @return void
     */
    public static function render() {
        $controller = new CnbSettingsController();

        $activation = $controller->parseApiAndOttHeader();

        if ( $activation->activation_attempt ) {
            $view = new CnbApiKeyActivatedView();
            $view->setActivation( $activation );
            $view->render();

            return;
        }

        $view = new CnbSettingsViewEdit();
        $view->render();
    }
}
