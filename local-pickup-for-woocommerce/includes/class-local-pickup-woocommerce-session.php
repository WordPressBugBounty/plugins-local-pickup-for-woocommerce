<?php

/**
 * Handles cart and checkout items session data to use in plguin
 *
 * @link       https://www.thedotstore.com/
 * @since      1.0.0
 *
 * @package    DSLPFW_Local_Pickup_Woocommerce
 * @subpackage DSLPFW_Local_Pickup_Woocommerce/admin
 */
defined( 'ABSPATH' ) or exit;
/**
 * Session data handler.
 *
 * Handles cart and checkout items session data.
 *
 * @since 1.0.0
 */
class DSLPFW_Local_Pickup_WooCommerce_Session {
    /** @var array default cart item pickup data (associative array) */
    private $default_cart_item_pickup_data;

    /** @var array default package pickup data (associative array) */
    private $default_package_pickup_data;

    /**
     * Session handler constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->default_cart_item_pickup_data = array(
            'handling'           => dslpfw_shipping_method()->get_default_handling(),
            'pickup_location_id' => 0,
        );
        $this->default_package_pickup_data = array(
            'pickup_date'        => '',
            'appointment_offset' => '',
            'pickup_location_id' => 0,
        );
        // clear session data upon emptying the cart
        add_action( 'woocommerce_cart_emptied', array($this, 'clear_session_data') );
    }

    /**
     * Get a session item default pickup data.
     *
     * @since 1.0.0
     *
     * @param string $item the session item (either 'cart_item' or 'package')
     * @return array associative array
     */
    private function get_default_pickup_data( $item = '' ) {
        $data = array();
        if ( 'cart_item' === $item ) {
            $data = $this->default_cart_item_pickup_data;
        } elseif ( 'package' === $item ) {
            $data = $this->default_package_pickup_data;
        }
        return $data;
    }

    /**
     * Get saved session pickup data.
     *
     * @since 1.0.0
     *
     * @param string $item session item (either 'cart_item' or 'package')
     * @param null|int|string $item_id the session item key or unique identifier (optional: by default returns all data)
     * @param string $piece optional, to return the value for a specific session array key
     * @return array|int|string|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location
     */
    private function get_session_pickup_data( $item, $item_id = null, $piece = '' ) {
        $defaults = ( $item_id ? $this->get_default_pickup_data( $item ) : array() );
        $pickup_data = $defaults;
        if ( 'cart_item' === $item ) {
            $key = 'wc_ds_local_pickup_cart_items';
        } elseif ( 'package' === $item ) {
            $key = 'wc_ds_local_pickup_packages';
        }
        if ( !empty( $key ) ) {
            if ( null === $item_id ) {
                $pickup_data = WC()->session->get( $key, $defaults );
            } else {
                $item_id = ( 'package' === $item ? "package_{$item_id}" : $item_id );
                $pickup_data = WC()->session->get( $key, array(
                    $item_id => $defaults,
                ) );
                $pickup_data = ( isset( $pickup_data[$item_id] ) ? $pickup_data[$item_id] : $defaults );
                // return a specific piece of handling data
                if ( '' !== $piece ) {
                    if ( 'pickup_location' === $piece && isset( $pickup_data['pickup_location_id'] ) ) {
                        $pickup_data = ( $pickup_data['pickup_location_id'] > 0 ? dslpfw_get_pickup_location( $pickup_data['pickup_location_id'] ) : null );
                    } elseif ( isset( $pickup_data[$piece] ) ) {
                        $pickup_data = $pickup_data[$piece];
                    } elseif ( isset( $defaults[$piece] ) ) {
                        $pickup_data = $defaults[$piece];
                    } else {
                        $pickup_data = '';
                    }
                }
            }
        }
        return $pickup_data;
    }

    /**
     * Save or update an item's pickup data to session.
     *
     * @since 1.0.0
     *
     * @param string $item type of local pickup WooCommerce session item (e.g. 'cart_item', 'package')
     * @param string|int $item_id the session item unique identifier (e.g. package ID or cart item key)
     * @param array $pickup_data data to save to session
     */
    private function set_session_pickup_data( $item, $item_id, $pickup_data ) {
        if ( 'cart_item' === $item ) {
            $session_key = 'wc_ds_local_pickup_cart_items';
            $pickup_data = wp_parse_args( $pickup_data, $this->get_cart_item_pickup_data( $item_id ) );
        } elseif ( 'package' === $item ) {
            $session_key = 'wc_ds_local_pickup_packages';
            $pickup_data = wp_parse_args( $pickup_data, $this->get_package_pickup_data( $item_id ) );
        }
        if ( !empty( $session_key ) ) {
            $item_id = ( 'package' === $item ? "package_{$item_id}" : $item_id );
            $session_data = WC()->session->get( $session_key, array() );
            WC()->session->set( $session_key, array_merge( $session_data, array(
                (string) $item_id => $pickup_data,
            ) ) );
        }
    }

    /**
     * Get a cart item pickup data.
     *
     * @since 1.0.0
     *
     * @param string $cart_item_id the unique identifier of a cart item
     * @param string $piece optionally get a specific pickup data key instead of the whole array (default)
     * @return string|int|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location|array
     */
    public function get_cart_item_pickup_data( $cart_item_id = null, $piece = '' ) {
        return $this->get_session_pickup_data( 'cart_item', $cart_item_id, $piece );
    }

    /**
     * Get a package pickup data.
     *
     * @since 1.0.0
     *
     * @param string $package_id the unique identifier of a package
     * @param string $piece optionally get a specific pickup data key instead of the whole array (default)
     * @return string|int|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location|array
     */
    public function get_package_pickup_data( $package_id = null, $piece = '' ) {
        return $this->get_session_pickup_data( 'package', $package_id, $piece );
    }

    /**
     * Save cart item pickup data to session.
     *
     * @since 1.0.0
     *
     * @param string $cart_item_id a cart item unique identifier
     * @param array $pickup_data array of item handling data
     */
    public function set_cart_item_pickup_data( $cart_item_id, array $pickup_data ) {
        $this->set_session_pickup_data( 'cart_item', $cart_item_id, $pickup_data );
    }

    /**
     * Save package pickup data to session.
     *
     * @since 1.0.0
     *
     * @param string $package_id a package unique identifier
     * @param array $pickup_data array of item handling data
     */
    public function set_package_pickup_data( $package_id, array $pickup_data ) {
        $this->set_session_pickup_data( 'package', $package_id, $pickup_data );
    }

    /**
     * Reset pickup data for a cart item to its defaults.
     *
     * @since 1.0.0
     *
     * @param string $cart_item_id the cart item unique identifier
     */
    public function delete_cart_item_pickup_data( $cart_item_id ) {
        $this->set_cart_item_pickup_data( $cart_item_id, $this->get_default_pickup_data( 'cart_item' ) );
    }

    /**
     * Reset pickup data for a package to its defaults.
     *
     * @since 1.0.0
     *
     * @param string $package_id the package unique identifier
     */
    public function delete_package_pickup_data( $package_id ) {
        $this->set_package_pickup_data( $package_id, $this->get_default_pickup_data( 'package' ) );
    }

    /**
     * Sets the default handling override.
     *
     * @since 1.0.0
     *
     * @param string $handling should be one of 'pickup' or 'ship', or empty to void default handling
     */
    public function set_default_handling( $handling ) {
        WC()->session->set( 'wc_ds_local_pickup_default_handling', 'ship' );
    }

    /**
     * Returns the default handling override.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_default_handling() {
        return ( WC()->session ? WC()->session->get( 'wc_ds_local_pickup_default_handling', '' ) : '' );
    }

    /**
     * Deletes the default handling override.
     *
     * @since 1.0.0
     */
    public function delete_default_handling() {
        $this->set_default_handling( '' );
    }

    /**
     * Wipe all pickup data related to cart and checkout items.
     *
     * @since 1.0.0
     */
    public function clear_session_data() {
        if ( WC()->session ) {
            WC()->session->set( 'wc_ds_local_pickup_cart_items', array() );
            WC()->session->set( 'wc_ds_local_pickup_packages', array() );
            WC()->session->set( 'wc_ds_local_pickup_default_handling', '' );
        }
    }

}
