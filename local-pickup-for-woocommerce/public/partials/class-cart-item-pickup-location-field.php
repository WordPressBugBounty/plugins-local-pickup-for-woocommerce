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

use DotStore\DSLPFW_Local_Pickup_Woocommerce\Data_Store\DSLPFW_Cart_Item_Pickup_Data;

/**
 * Field component to select a pickup location for a cart item.
 *
 * @since 1.0.0
 */
class DSLPFW_Cart_Item_Pickup_Location_Field extends DSLPFW_Pickup_Location_Field {

    /** @var string $cart_item_key the ID of the cart item for this field */
	private $cart_item_key;

    /** @var object DSLPFW_Cart_Item_Pickup_Data */
	private $data_store;

	/**
	 * Field constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $cart_item_key the current cart item key
	 */
	public function __construct( $cart_item_key ) {

		$this->object_type   = 'cart-item';
		$this->cart_item_key = $cart_item_key;
		$this->data_store    = new DSLPFW_Cart_Item_Pickup_Data( $cart_item_key );
	}

    /**
	 * Get the cart item ID.
	 *
	 * @since 1.0.0
	 *
	 * @return string|int
	 */
	public function get_cart_item_id() {
		return $this->cart_item_key;
	}

    /**
	 * Get the field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @return string HTML
	 */
	public function get_html() {

        $field_html         = '';
		$cart_item_id       = '';
		$product            = null;
        $ds_local_pickup = dslpfw_shipping_method();
        $product = $this->data_store->get_product();

		if ( $ds_local_pickup->dslpfw_is_per_item_selection_enabled() // only display the item location select if enabled
		     && $product
		     && dslpfw_product_can_be_picked_up( $product ) ) {

			$cart_item_id        = $this->get_cart_item_id();
			$pickup_data         = $this->data_store->get_pickup_data();
			$should_be_picked_up = ( isset( $pickup_data['handling'] ) && 'pickup' === $pickup_data['handling'] ) || ! $this->data_store->can_be_shipped();
			$must_be_picked_up   = dslpfw_product_must_be_picked_up( $product );

			if ( ! empty( $pickup_data['pickup_location_id'] ) ) {
				$chosen_pickup_location = dslpfw_get_pickup_location( (int) $pickup_data['pickup_location_id'] );
			} else {
				$chosen_pickup_location = $this->get_user_default_pickup_location();
			}

			if ( $ds_local_pickup->dslpfw_is_per_item_selection_enabled() && ! dslpfw_product_can_be_picked_up( $product, $chosen_pickup_location ) ) {

				$chosen_pickup_location = null;
			}

			ob_start();

			?>
			<div
				id="pickup-location-field-for-<?php echo esc_attr( $cart_item_id ); ?>"
				class="form-row dslpfw-pickup-location-field pickup-location-cart-item-field"
				data-pickup-object-id="<?php echo esc_attr( $cart_item_id ); ?>">

				<?php // if the item is set to be shipped, hide the select instead of removing it, to preserve the chosen location ?>
				<div class="<?php echo $must_be_picked_up || $should_be_picked_up ? 'dslpfw-location-show' : 'dslpfw-location-hide'; ?> dslpfw-location-wrap">
					<?php echo wp_kses( $this->get_location_select_html( $cart_item_id, $chosen_pickup_location, $this->data_store->get_product() ), dslpfw()->dslpfw_allowed_html_tags() ); ?>
				</div>

			</div>
			<?php

			$field_html .= ob_get_clean();
		}

		/**
		 * Filter the cart item pickup location field HTML.
		 *
		 * @since 1.0.0
		 *
		 * @param string $field_html HTML
		 * @param string $cart_item_id the current cart item ID
		 * @param \WC_Product|null $product the cart item product
		 */
		return apply_filters( 'dslpfw_get_pickup_location_cart_item_field_html', $field_html, $cart_item_id, $product );
	}

    /**
	 * Determines if the current product can be picked up, or must be shipped.
	 *
	 * @since 1.0.0
	 *
	 * @param \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $pickup_location pickup location to check
	 * @return bool
	 */
	protected function can_be_picked_up( $pickup_location ) {
		return $this->data_store->get_product() ? dslpfw_product_can_be_picked_up( $this->data_store->get_product(), $pickup_location ) : true;
	}

    /**
	 * Gets the object type for this storage.
	 *
	 * This lets us differentiate between cart items and packages in the JS.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_object_type() {
		return $this->object_type;
	}

}