<?php

namespace cnb\renderer;

use cnb\utils\CnbUtils;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

class RendererFactory {

    /**
     * @return string
     */
    function getRenderer() {
        $cnb_options = get_option( 'cnb' );

        return ( new CnbUtils() )->is_use_cloud( $cnb_options ) ? 'cloud' : 'modern';
    }

    /**
     * If we're in the "wp_loaded" hook, we don't NEED is_admin(), but it's good to have as a safety check
     *
     * @return bool
     */
    function shouldRender() {
        $cnb_options = get_option( 'cnb' );

        return ! is_admin() && ( new CnbUtils() )->isButtonActive( $cnb_options );
    }

    /**
     * Find the proper renderer and load it. The renderer is responsible for adding itself to the proper hooks.
     *
     * Proper hooks are (probably/likely) `wp_head` and `wp_footer`
     *
     * This function should be scheduled on the front-end (only), usually via the `wp_loaded` hook.
     *
     * @return void
     */
    public static function register() {
        $factory = new RendererFactory();

        if ( $factory->shouldRender() ) {
            switch ( $factory->getRenderer() ) {
                case 'cloud':
                    $cloud_rendere = new CloudRenderer();
                    $cloud_rendere->register();
                    break;
                case 'modern':
                    $modernRendere = new ModernRenderer();
                    $modernRendere->register();
                    break;
                default:
                    $noop_renderer = new NoopRenderer();
                    $noop_renderer->register();
            }
        } else {
            $noop_renderer = new NoopRenderer();
            $noop_renderer->register();
        }
    }
}
