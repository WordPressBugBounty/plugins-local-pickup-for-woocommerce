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
class DSLPFW_Cart_Item_Handling_Toggle {

    /** @var string $cart_item_key the ID of the cart item for this field */
	private $cart_item_key;

    /** @var string $object_type type of pickup location selection */
    private $object_type;

    /** @var DSLPFW_Cart_Item_Pickup_Data $data_store the data store for the cart item */
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

		$field_html        = '';
		$cart_item_id      = '';
		$product           = null;
		$ds_local_pickup = dslpfw_shipping_method();
        $product = $this->data_store->get_product();

		if ( $product && ! dslpfw_product_must_be_picked_up( $product ) ) {

			if ( dslpfw_product_can_be_picked_up( $product ) ) {

				$cart_item_id        = $this->get_cart_item_id();
				$pickup_data         = $this->data_store->get_pickup_data();
				$should_be_picked_up = ( isset( $pickup_data['handling'] ) && 'pickup' === $pickup_data['handling'] ) || ! $this->data_store->can_be_shipped();

				ob_start();

				?>
				<div
					id="handling-toggle-for-<?php echo esc_attr( $cart_item_id ); ?>"
					class="dslpfw-pickup-location-field-toggle pickup-location-cart-item-field"
					data-pickup-object-id="<?php echo esc_attr( $cart_item_id ); ?>">

				<?php if ( ! $this->hiding_item_handling_toggle() ) : ?>

					<?php
					$item_handling_labels = [
						/* translators: Placeholders: %1$s - opening <a> link tag, %2$s - closing </a> link tag */
						'set_for_pickup'   => sprintf( esc_html__( 'This item is set for shipping. %1$sClick here to pickup this item%2$s.', 'local-pickup-for-woocommerce' ), '<a class="dslpfw-local-pickup-enable"  href="#">', '</a>' ),
						/* translators: Placeholders: %1$s - opening <a> link tag, %2$s - closing </a> link tag */
						'set_for_shipping' => sprintf( esc_html__( 'This item is set for pickup. %1$sClick here to ship this item%2$s.', 'local-pickup-for-woocommerce' ), '<a class="dslpfw-local-pickup-disable" href="#">', '</a>' ),
					];
                    if ( isset( $item_handling_labels['set_for_pickup'], $item_handling_labels['set_for_shipping'] ) ) : ?>

						<small class="<?php echo $should_be_picked_up ? 'dslpfw-item-handling-hide' : 'dslpfw-item-handling-show'; ?>"><?php echo wp_kses_post( $item_handling_labels['set_for_pickup'] ); ?></small>
						<small class="<?php echo ! $should_be_picked_up ? 'dslpfw-item-handling-hide' : 'dslpfw-item-handling-show'; ?>"><?php echo wp_kses_post( $item_handling_labels['set_for_shipping'] ); ?></small>

					<?php endif; ?>

				<?php else : ?>

					<?php // if the customer control toggle to switch between ship and pickup is disabled, force handling into session
						$this->data_store->set_pickup_data( [
							'handling'           => $should_be_picked_up ? 'pickup' : $ds_local_pickup->get_default_handling(),
							'pickup_location_id' => isset( $pickup_data['pickup_location_id'] ) ? $pickup_data['pickup_location_id'] : 0,
							'pickup_date'        => isset( $pickup_data['pickup_date'] ) ? $pickup_data['pickup_date'] : '',
							'appointment_offset' => isset( $pickup_data['appointment_offset'] ) ? $pickup_data['appointment_offset'] : '',
						] );
					?>

				<?php endif; ?>

				<?php

				// display if not forced to pick up and item handling links have not been displayed despite cart item susceptible to be shipped
				if ( empty( $item_handling_labels ) && $this->data_store->cart_item_may_have_shipping( $cart_item_id ) ) {

					$note_text    = esc_html__( 'Shipping may be available.', 'local-pickup-for-woocommerce' );
					$note_tooltip = is_checkout() ? esc_html__( 'Enter or update your full address to see if shipping options are available.', 'local-pickup-for-woocommerce' ) : esc_html__( 'Enter your full address on the checkout page to see if shipping is available.', 'local-pickup-for-woocommerce' );

					printf( '<small>%1$s <span class="wc-dslpfw-help-tip" data-tip="%2$s"></span></small>', esc_html( $note_text ), esc_attr( $note_tooltip ) );
				}

				?>

				</div>
				<?php

				$field_html .= ob_get_clean();

			} elseif ( $product->needs_shipping() ) {

				// display a shipping handling notice only for non-virtual items
				$field_html .= '<br /><em><small>' . esc_html__( 'This item can only be shipped', 'local-pickup-for-woocommerce' ) . '</small></em>';
			}
		}

		/**
		 * Filter the cart item handling toggle HTML.
		 *
		 * @since 1.0.0
		 *
		 * @param string $field_html HTML
		 * @param string $cart_item_id the current cart item ID
		 * @param \WC_Product|null $product the cart item product
		 */
		return apply_filters( 'dslpfw_get_cart_item_handling_toggle_html', $field_html, $cart_item_id, $product );
	}

    /**
	 * Determines whether the item handling toggle should be hidden to customers in frontend.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function hiding_item_handling_toggle() {

		$ds_local_pickup   = dslpfw_shipping_method();
		$hiding            = false;

		$is_automatic_handling = $ds_local_pickup && $ds_local_pickup->dslpfw_is_per_order_selection_enabled() && $ds_local_pickup->is_item_handling_mode( 'automatic' );

		// when you have a product that must be picked and a product that cannot be picked up,
		// a minimum of 2 shipping packages is required and automatic handling by itself is not enough,
		// so we allow each item to be toggled as well
		$shipping_and_pickup_required = dslpfw()->get_dslpfw_packages_object()->are_shipping_and_pickup_required();

		if ( ! $this->data_store->can_be_shipped() || ( $is_automatic_handling && ! $shipping_and_pickup_required ) ) {
			$hiding = true;
		}

		return $hiding;
	}

}
