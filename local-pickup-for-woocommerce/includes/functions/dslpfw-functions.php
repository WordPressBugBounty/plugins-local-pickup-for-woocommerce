<?php
/**
 *  Local Pickup for WooCommerce
 *
 *
 * @author      SkyVerge
 * @copyright   Copyright (c) 2012-2023, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

// Product function start

/**
 * Gets a product availability for local pickup.
 *
 * @since 1.0.0
 *
 * @param int|\WC_Product|\WP_Post $product the product ID or object
 * @param bool $dont_inherit if true, ignores the inherited status and instead returns 'inherit'
 * @return string product availability status
 */
function dslpfw_get_product_availability( $product, $dont_inherit = false ) {
	return dslpfw()->get_dslpfw_products_object()->get_local_pickup_product_availability( $product, $dont_inherit );
}


/**
 * Get a product category availability for local pickup.
 *
 * @since 1.0.0
 *
 * @param int|\WP_Term $product_cat the product category term ID or object
 * @return string product category availability status
 */
function dslpfw_get_product_cat_availability( $product_cat ) {
	return dslpfw()->get_dslpfw_products_object()->get_local_pickup_product_cat_availability( $product_cat );
}

/**
 * Check whether a product can be collected for local pickup.
 *
 * @since 1.0.0
 *
 * @param int|\WC_Product|\WP_Post $product product ID or object
 * @param null|int|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $pickup_location optional: Pickup Location ID or object to check specifically
 * @return bool
 */
function dslpfw_product_can_be_picked_up( $product, $pickup_location = null ) {
	return dslpfw()->get_dslpfw_products_object()->product_can_be_picked_up( $product, $pickup_location );
}

/**
 * Check whether a product must be collected at a pickup location and can't be shipped.
 *
 * @since 1.0.0
 *
 * @param int|\WC_Product|\WP_Post $product product ID or object
 * @param null|int|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $pickup_location optional: Pickup Location ID or object to check specifically
 * @return bool
 */
function dslpfw_product_must_be_picked_up( $product, $pickup_location = null ) {
	return dslpfw()->get_dslpfw_products_object()->product_must_be_picked_up( $product, $pickup_location );
}


/**
 * Check whether a product can only be shipped (ie. with a shipping method different than Local Pickup WooCommerce).
 *
 * @since 1.0.0
 *
 * @param int|\WC_Product|\WP_Post $product product ID or object
 * @return bool
 */
function dslpfw_product_must_be_shipped( $product ) {

    $product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
    $product_needs_shipping = $product instanceof \WC_Product ? $product->needs_shipping() : false;

	return $product_needs_shipping && ! dslpfw_product_can_be_picked_up( $product );
}

// Product funciton end

// User Functions Start

/**
 * Set a default / preferred pickup location for user.
 *
 * @since 1.0.0
 *
 * @param int|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $pickup_location a pickup location ID or object
 * @param int|\WP_User $user optional: a WordPress user (leave default null to use the current user)
 * @return bool
 */
function dslpfw_set_user_default_pickup_location( $pickup_location, $user = null ) {

	$success = false;

	if ( is_numeric( $user ) ) {
		$user = get_user_by( 'id', $user );
	} elseif ( null === $user ) {
		$user = wp_get_current_user();
	}

	if ( $user instanceof \WP_User ) {

		if ( is_numeric( $pickup_location ) ) {
			$pickup_location = dslpfw_get_pickup_location( $pickup_location );
		}

		if ( $pickup_location instanceof \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location ) {
			$success = update_user_meta( $user->ID, '_default_pickup_location', $pickup_location->get_id() );
		}
	}

	return (bool) $success;
}

/**
 * Gets the default / preferred pickup location for user.
 *
 * @since 1.0.0
 *
 * @param int|\WP_User $user optional: user ID or object (or leave default null to get data for the current logged in user)
 * @return null|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location
 */
function dslpfw_get_user_default_pickup_location( $user = null ) {

	$pickup_location = null;

	if ( is_numeric( $user ) ) {
		$user = get_user_by( 'id', $user );
	} elseif ( null === $user ) {
		$user = wp_get_current_user();
	}

	if ( $user instanceof \WP_User ) {

		$pickup_location_id = get_user_meta( $user->ID, '_default_pickup_location', true );

		if ( is_numeric( $pickup_location_id ) && $pickup_location_id > 0 ) {
			$pickup_location = dslpfw_get_pickup_location( $pickup_location_id );
		}
	}

	/**
	 * Filters the default / preferred pickup location for user.
	 *
	 * @since 1.0.0
	 *
	 * @param null|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location     $pickup_location    the pickup location
	 * @param \WP_User  $user   the user object
	 */
	return apply_filters( 'dslpfw_get_user_default_pickup_location', $pickup_location, $user );
}

// User Functions End

// Pickup Locations Functions Start

/**
 * Main function for returning a pickup location.
 *
 * @since 1.0.0
 *
 * @param int|\WP_Post|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $pickup_location the pickup location post, post id or main object
 * @return null|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location a pickup location object
 */
function dslpfw_get_pickup_location( $pickup_location ) {
	return dslpfw()->get_dslpfw_pickup_locations_object()->get_pickup_location( $pickup_location );
}


/**
 * Main function for returning pickup locations.
 *
 * @since 1.0.0
 *
 * @param array $args optional array of arguments passed to` get_posts()`
 * @return \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location[] array of location objects or empty array if none found
 */
function dslpfw_get_pickup_locations( $args = array() ) {
	return dslpfw()->get_dslpfw_pickup_locations_object()->get_pickup_locations( $args );
}

// Pickup Locations Functions End



/**
 * Get the shipping method.
 *
 * @since 1.0.0
 *
 * @return \DSLPFW_Local_Pickup_WooCommerce_Shipping
 */
function dslpfw_shipping_method() {
    return dslpfw()->dslpfw_get_shipping_method_instance();
}

/**
 * Get the shipping method ID.
 *
 * @since 1.0.0
 *
 * @return string
 */
function dslpfw_shipping_method_id() {
    return dslpfw_shipping_method()->get_method_id();
}

/**
 * Get the appointments mode setting.
 *
 * @since 1.0.0
 *
 * @return string either 'disabled', 'enabled' or 'required'
 */
function dslpfw_appointments_mode() {

    $appointments = 'disabled';
    $shipping_method = dslpfw_shipping_method();
    
    if ( $shipping_method instanceof \DSLPFW_Local_Pickup_WooCommerce_Shipping ) {
        $appointments = $shipping_method->pickup_appointments_mode();
    }

    return $appointments;
}