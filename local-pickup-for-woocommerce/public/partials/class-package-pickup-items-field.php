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

namespace DotStore\DSLPFW_Local_Pickup_Woocommerce\Fields;

defined( 'ABSPATH' ) or exit;

use DotStore\DSLPFW_Local_Pickup_Woocommerce\Data_Store\DSLPFW_Package_Pickup_Data;

/**
 * Component to render items details for items in a shipping package.
 *
 * @since 1.0.0
 */
class DSLPFW_Package_Pickup_Items_Field extends DSLPFW_Pickup_Location_Field {

    /** @var int|string key index of current package this field is associated to */
	private $package_id;

    /** @var DSLPFW_Package_Pickup_Data data store for the package */
    private $data_store;
    
    /**
	 * Field constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param int|string $package_id the package key index
	 */
	public function __construct( $package_id ) {

		$this->object_type = 'package';
		$this->package_id  = $package_id;
		$this->data_store  = new DSLPFW_Package_Pickup_Data( $package_id );
	}

    /**
	 * Get the ID of the package for this field.
	 *
	 * @since 1.0.0
	 *
	 * @return int|string
	 */
	private function get_package_id() {
		return $this->package_id;
	}

    /**
	 * Get the package cart items.
	 *
	 * This is useful later when submitting the checkout form to associate a order line items to a package and thus an order shipping item.
	 * @see \DSLPFW_Local_Pickup_WooCommerce_Order_Items::dslpfw_link_order_line_item_to_package()
	 *
	 * @since 1.0.0
	 *
	 * @return int[]|string[]
	 */
	private function get_cart_items() {

		$items   = [];
		$package = $this->data_store->get_package();

		if ( ! empty( $package['contents'] ) && is_array( $package['contents'] ) ) {
			foreach ( array_keys( $package['contents'] ) as $cart_item_key  ) {
				$items[] = $cart_item_key;
			}
		}

		return $items;
	}

    /**
	 * Get cart item details for the current package.
	 *
	 * @since 1.0.0
	 *
	 * @return array associative array of product names and quantities
	 */
	private function get_cart_items_details() {

		$items   = [];
		$package = $this->data_store->get_package();

		if ( ! empty( $package['contents'] ) && is_array( $package['contents'] ) ) {

			foreach ( $package['contents'] as $cart_item_key => $cart_item ) {

				if ( isset( $cart_item['data'], $cart_item['quantity'] ) ) {

					$item_product = $cart_item['data'] instanceof \WC_Product ? $cart_item['data'] : null;
					$item_qty     = max( 0, abs( $cart_item['quantity'] ) );

					if ( $item_product && $item_qty > 0 ) {

						/* translators: Placeholders: %1$s product name, %2$s product quantity - e.g. "Product name x2" */
						$items[ $cart_item_key ] = sprintf( esc_html__( '%1$s &times; %2$s', 'local-pickup-for-woocommerce' ), $item_product->get_name(), $item_qty );
					}
				}
			}
		}

		/**
		 * Filter the pickup package details.
		 *
		 * @since 1.0.0
		 *
		 * @param array $items an array of item keys and name/quantity details as strings
		 * @param array $package the package for pickup the details are meant for
		 */
		return apply_filters( 'dslpfw_shipping_package_details_array', $items, $package );
	}

    /**
	 * Get the field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @return string HTML
	 */
	public function get_html() {

		$field_html      = '';
		$shipping_method = dslpfw_shipping_method();

		ob_start();

		?>
		<div id="pickup-items-field-for-<?php echo esc_attr( $this->get_package_id() ); ?>" class="form-row pickup-location-field pickup-location-field-<?php echo sanitize_html_class( $shipping_method->pickup_selection_mode() ); ?> pickup-location-<?php echo sanitize_html_class( $this->get_object_type() ); ?>-field" data-pickup-object-id="<?php echo esc_attr( $this->get_package_id() ); ?>">

			<?php // display the item details list ?>
			<?php $item_details = $this->get_cart_items_details(); ?>
			<?php if ( ! empty( $item_details ) && is_array( $item_details ) ) : ?>
				<p class="woocommerce-shipping-contents"><small><?php echo esc_html( implode( ', ', $item_details ) ); ?></small></p>
			<?php endif; ?>

		</div>

        <!-- Record cart items to pickup -->
		<input type="hidden" name="dslpfw_pickup_items[<?php echo esc_attr( $this->get_package_id() ); ?>]" value="<?php echo esc_attr( implode( ',', $this->get_cart_items() ) ); ?>" data-pickup-object-id="<?php echo esc_attr( $this->get_package_id() ); ?>" />

		<?php

		$field_html .= ob_get_clean();

		/**
		 * Filter the package items details HTML.
		 *
		 * @since 1.0.0
		 *
		 * @param string $field_html input field HTML
		 * @param int|string $package_id the current package identifier
		 * @param array $package the current package array
		 */
		return apply_filters( 'dslpfw_get_package_pickup_items_field_html', $field_html, $this->get_package_id(), $this->data_store->get_package() );
	}
}