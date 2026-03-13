<?php

namespace cnb\admin\models;

use cnb\utils\CnbUtils;
use stdClass;
use WP_Error;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

class CnbPlan {
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $nickname;

    /**
     * @var string (always FREE)
     */
    public $domainType;
    /**
     * @var string (EUR/USD)
     */
    public $currency;
    /**
     * @var string (monthly/yearly)
     */
    public $interval;
    /**
     * @var float
     */
    public $price;

    /**
     * If a stdClass is passed, it is transformed into a CnbButton.
     * a WP_Error is ignored and return immediatly
     * a null if converted into an (empty) CnbButton
     *
     * @param $object stdClass|array|WP_Error|null
     *
     * @return CnbPlan|WP_Error
     */
    public static function fromObject( $object ) {
        if ( is_wp_error( $object ) ) {
            return $object;
        }

        $plan             = new CnbPlan();
        $plan->id         = CnbUtils::getPropertyOrNull( $object, 'id' );
        $plan->nickname   = CnbUtils::getPropertyOrNull( $object, 'nickname' );
        $plan->domainType = CnbUtils::getPropertyOrNull( $object, 'domainType' );
        $plan->currency   = CnbUtils::getPropertyOrNull( $object, 'currency' );
        $plan->interval   = CnbUtils::getPropertyOrNull( $object, 'interval' );
        $plan->price      = floatval( CnbUtils::getPropertyOrNull( $object, 'price' ) );

        return $plan;
    }

    /**
     * @param $objects stdClass[]|WP_Error|null
     *
     * @return CnbPlan[]|WP_Error
     */
    public static function fromObjects( $objects ) {
        if ( is_wp_error( $objects ) ) {
            return $objects;
        }

        return array_map(
            function ( $object ) {
                return self::fromObject( $object );
            },
            $objects
        );
    }
}
