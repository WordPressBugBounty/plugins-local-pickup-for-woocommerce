<?php
/**
 * The public-facing functionality of the plugin for cart repeated and helping methods
 *
 * @link       https://www.thedotstore.com/
 * @since      1.0.0
 *
 * @package    DSLPFW_Local_Pickup_Woocommerce
 * @subpackage DSLPFW_Local_Pickup_Woocommerce/public
 *
 *
 * @author      theDotstore <support@thedotstore.com>
 */
namespace DotStore\DSLPFW_Local_Pickup_Woocommerce\Data_Store;

defined( 'ABSPATH' ) or exit;

 /**
 * Pickup data storage component for cart items.
 *
 * @since 1.0.0
 */
class DSLPFW_Cart_Item_Pickup_Data {

    private $object_id;

    /**
	 * Data storage constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $cart_item_key the ID of the cart item
	 */
	public function __construct( $cart_item_key ) {

		$this->object_id = $cart_item_key;
	}


    /**
	 * Get the cart item pickup data, if set.
	 *
	 * @since 1.0.0
	 *
	 * @param string $piece optionally get a specific pickup data key instead of the whole array (default)
	 * @return string|int|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location|array
	 */
	public function get_pickup_data( $piece = '' ) {
		return dslpfw()->get_dslpfw_session_object()->get_cart_item_pickup_data( $this->object_id, $piece );
	}

    /**
	 * Save pickup data to session.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $pickup_data
	 */
	public function set_pickup_data( array $pickup_data ) {
		dslpfw()->get_dslpfw_session_object()->set_cart_item_pickup_data( $this->object_id, $pickup_data );
	}


	/**
	 * Reset pickup data for the cart item (defaults to shipping).
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function delete_pickup_data() {
		dslpfw()->get_dslpfw_session_object()->delete_cart_item_pickup_data();
	}

    /**
	 * Get the cart item.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_cart_item() {

		$cart_item    = [];
		$cart_item_id = $this->object_id;

		if ( ! empty( $cart_item_id ) && ! WC()->cart->is_empty() ) {

			$cart_contents = WC()->cart->cart_contents;

			if ( isset( $cart_contents[ $cart_item_id ] ) ) {
				$cart_item = $cart_contents[ $cart_item_id ];
			}
		}

		return $cart_item;
	}


	/**
	 * Get the ID of the product for the cart.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	private function get_product_id() {

		$cart_item  = $this->get_cart_item();
		$product_id = isset( $cart_item['product_id'] ) ? abs( $cart_item['product_id'] ) : 0;

		if ( ! empty( $cart_item['variation_id'] ) ) {
			$product_id = abs( $cart_item['variation_id'] );
		}

		return $product_id;
	}


	/**
	 * Get the product object for the cart item.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @return null|\WC_Product
	 */
	public function get_product() {

		$product_id = $this->get_product_id();
		$product    = $product_id > 0 ? wc_get_product( $product_id ) : null;

		return $product instanceof \WC_Product ? $product : null;
	}


	/**
	 * Determines if the current product can be shipped, depending on the available shipping methods.
	 *
	 * If there are no shipping methods/rates available for the item's package, the item should be picked up instead.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function can_be_shipped() {

		return ! dslpfw()->get_dslpfw_products_object()->product_must_be_picked_up( $this->get_product() );
	}


	/**
	 * Checks whether a cart item may have shipping available that hasn't been calculated yet.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string $cart_item_id
	 * @return bool
	 */
	public function cart_item_may_have_shipping( $cart_item_id ) {

		$may_be_shipped = false;

		// shipping has not yet been calculated
		if ( ! WC()->customer->has_calculated_shipping() ) {

			$package = dslpfw()->get_dslpfw_packages_object()->get_cart_item_package( $cart_item_id );

			// package is currently set to ship via DSLPFW
			if ( isset( $package['ship_via'] ) && in_array( \DSLPFW_Local_Pickup_Woocommerce::DSLPFW_SHIPPING_METHOD_ID, $package['ship_via'], true ) ) {

				$zones = \WC_Shipping_Zones::get_zones();

				foreach ( $zones as $zone_id => $zone_data ) {

					$zone    = \WC_Shipping_Zones::get_zone( $zone_id );
					$methods = $zone->get_shipping_methods( true );

					// enabled shipping methods exist for a zone
					if ( ! empty( $methods ) ) {

						$may_be_shipped = true;
						break;
					}
				}
			}
		}

		return $may_be_shipped;
	}

}