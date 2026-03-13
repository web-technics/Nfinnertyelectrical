<?php

namespace cnb\admin\models;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

use cnb\utils\CnbUtils;
use JsonSerializable;
use stdClass;
use WP_Error;

class CnbUser implements JsonSerializable {
    /**
     * @var string UUID of the User
     */
    public $id;

    /**
     * @var boolean
     */
    public $active;
    /**
     * @var string Name of the User
     */
    public $name;

    /**
     * Usually the same as admin_email
     *
     * @var string email address of the User
     */
    public $email;

    /**
     * @var string
     */
    public $companyName;

    /**
     * @var CnbUserAddress
     */
    public $address;

    /**
     * @var array{CnbUserTaxId}
     */
    public $taxIds = array();

    /**
     * @var CnbUserStripeDetails
     */
    public $stripeDetails;
    /**
     * transient variable (not sent to the API)
     * @var int
     */
    public $euvatbusiness;

    /**
     * If a stdClass is passed, it is transformed into a CnbButton.
     * a WP_Error is ignored and return immediatly
     * a null if converted into an (empty) CnbButton
     *
     * @param $object stdClass|array|WP_Error|null
     *
     * @return CnbUser|WP_Error
     */
    public static function fromObject( $object ) {
        if ( is_wp_error( $object ) ) {
            return $object;
        }

        $user              = new CnbUser();
        $user->active      = CnbUtils::getPropertyOrNull( $object, 'active' );
        $user->id          = CnbUtils::getPropertyOrNull( $object, 'id' );
        $user->name        = CnbUtils::getPropertyOrNull( $object, 'name' );
        $user->email       = CnbUtils::getPropertyOrNull( $object, 'email' );
        $user->companyName = CnbUtils::getPropertyOrNull( $object, 'companyName' );
        $address           = CnbUserAddress::fromObject( CnbUtils::getPropertyOrNull( $object, 'address' ) );
        $user->address     = $address;
        $taxIds            = CnbUserTaxId::fromObject( CnbUtils::getPropertyOrNull( $object, 'taxIds' ) );
        $user->taxIds      = $taxIds;
        // This is only set via the form, but is used for some checks (but not submitted to the API)
        $user->euvatbusiness = CnbUtils::getPropertyOrNull( $object, 'euvatbusiness' );
        $stripeDetails       = CnbUserStripeDetails::fromObject( CnbUtils::getPropertyOrNull( $object, 'stripeDetails' ) );
        $user->stripeDetails = $stripeDetails;

        return $user;
    }

    public function toArray() {
        // Note, we do not export "euvatbusiness", since that is only used internally
        return array(
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'companyName' => $this->companyName,
            'address'     => $this->address,
            'taxIds'      => $this->taxIds,
        );
    }

    public function jsonSerialize() {
        return $this->toArray();
    }
}

class CnbUserTaxId implements JsonSerializable {
    public $value;
    public $type;
    /**
     * @var CnbUserTaxIdVerification
     */
    public $verification;

    /**
     * @param $object stdClass|array|WP_Error|null
     *
     * @return CnbUserTaxId[]|WP_Error
     */
    public static function fromObject( $object ) {
        if ( is_wp_error( $object ) ) {
            return $object;
        }
        $userTaxIds = array();
        foreach ( $object as $taxId ) {
            $userTaxId               = new CnbUserTaxId();
            $userTaxId->value        = CnbUtils::getPropertyOrNull( $taxId, 'value' );
            $userTaxId->type         = CnbUtils::getPropertyOrNull( $taxId, 'type' );
            $userTaxId->verification = CnbUserTaxIdVerification::fromObject( CnbUtils::getPropertyOrNull( $taxId, 'verification' ) );
            $userTaxIds[]            = $userTaxId;
        }

        return $userTaxIds;
    }

    public function toArray() {
        return array(
            'value'        => $this->value,
            'type'         => $this->type,
            'verification' => $this->verification
        );
    }

    public function jsonSerialize() {
        return $this->toArray();
    }
}

class CnbUserTaxIdVerification implements JsonSerializable {
    /**
     * @var string either "verified" or "pending"
     */
    public $status;

    /**
     * @param $object stdClass|array|WP_Error|null
     *
     * @return CnbUserTaxIdVerification|WP_Error
     */
    public static function fromObject( $object ) {
        if ( is_wp_error( $object ) ) {
            return $object;
        }

        $userTaxIdVerification         = new CnbUserTaxIdVerification();
        $userTaxIdVerification->status = CnbUtils::getPropertyOrNull( $object, 'status' );

        return $userTaxIdVerification;
    }

    public function toArray() {
        return array(
            'status' => $this->status,
        );
    }

    public function jsonSerialize() {
        return $this->toArray();
    }
}

class CnbUserAddress implements JsonSerializable {
    public $line1;
    public $line2;
    public $postalCode;
    public $city;
    public $state;
    public $country;

    /**
     * @param $object stdClass|array|WP_Error|null
     *
     * @return CnbUserAddress|WP_Error
     */
    public static function fromObject( $object ) {
        if ( is_wp_error( $object ) ) {
            return $object;
        }
        $address             = new CnbUserAddress();
        $address->line1      = CnbUtils::getPropertyOrNull( $object, 'line1' );
        $address->line2      = CnbUtils::getPropertyOrNull( $object, 'line2' );
        $address->postalCode = CnbUtils::getPropertyOrNull( $object, 'postalCode' );
        $address->city       = CnbUtils::getPropertyOrNull( $object, 'city' );
        $address->state      = CnbUtils::getPropertyOrNull( $object, 'state' );
        $address->country    = CnbUtils::getPropertyOrNull( $object, 'country' );

        return $address;
    }

    public function toArray() {
        return array(
            'line1'      => $this->line1,
            'line2'      => $this->line2,
            'postalCode' => $this->postalCode,
            'city'       => $this->city,
            'state'      => $this->state,
            'country'    => $this->country,
        );
    }

    public function jsonSerialize() {
        return $this->toArray();
    }
}

class CnbUserStripeDetails implements JsonSerializable {
    public $customerId;
    public $subscriptions = array();
    public $currency;

    /**
     * @param $object stdClass|array|WP_Error|null
     *
     * @return CnbUserStripeDetails|WP_Error
     */
    public static function fromObject( $object ) {
        if ( is_wp_error( $object ) ) {
            return $object;
        }
        $stripeDetails             = new CnbUserStripeDetails();
        $stripeDetails->customerId = CnbUtils::getPropertyOrNull( $object, 'customerId' );
        $stripeDetails->currency   = CnbUtils::getPropertyOrNull( $object, 'currency' );

        return $stripeDetails;
    }

    public function toArray() {
        return array(
            'customerId'    => $this->customerId,
            'subscriptions' => $this->subscriptions,
            'currency'      => $this->currency,
        );
    }

    public function jsonSerialize() {
        return $this->toArray();
    }
}
