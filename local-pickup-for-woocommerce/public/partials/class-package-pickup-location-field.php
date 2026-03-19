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
 * Field component to select a pickup location for a cart item.
 *
 * @since 1.0.0
 */
class DSLPFW_Package_Pickup_Location_Field extends DSLPFW_Pickup_Location_Field {

    /** @var int|string key index of current package this field is associated to */
	private $package_id;

    /** @var object DSLPFW_Package_Pickup_Data */
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
	 * Returns the current product object, if there is only a single one in package.
	 *
	 * @since 1.0.0
	 *
	 * @return \WC_Product|null
	 */
	private function get_single_product() {

		$product = null;
		$package = $this->data_store->get_package();

		if ( ! empty( $package['contents'] ) && 1 === count( $package['contents'] ) ) {
			$content = current( $package['contents'] );
			$product = isset( $content['data'] ) && $content['data'] instanceof \WC_Product ? $content['data'] : null;
		}

		return $product;
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
        $chosen_location = $this->data_store->get_pickup_location();

		ob_start();

		?>
		<div
			id="pickup-location-field-for-<?php echo esc_attr( $this->get_package_id() ); ?>"
			class="form-row pickup-location-field pickup-location-field-<?php echo sanitize_html_class( $shipping_method->pickup_selection_mode() ); ?> pickup-location-<?php echo sanitize_html_class( $this->get_object_type() ); ?>-field"
			data-pickup-object-id="<?php echo esc_attr( $this->get_package_id() ); ?>">

			<?php // display the selected location, or location select field ?>
			<?php if ( $shipping_method->dslpfw_is_per_order_selection_enabled() ) : ?>

                <?php echo wp_kses( $this->get_location_select_html( $this->get_package_id(), $chosen_location, $this->get_single_product() ), dslpfw()->dslpfw_allowed_html_tags() );  ?>

            <?php elseif ( $chosen_location ) : ?>

                <?php // record the chosen pickup location ID ?>

                <input
                    type="hidden"
                    name="_shipping_method_pickup_location_id[<?php echo esc_attr( $this->get_package_id() ); ?>]"
                    value="<?php echo esc_attr( $chosen_location->get_id() ); ?>"
                    data-package-id="<?php echo esc_attr( $this->get_package_id() ); ?>"
                />

            <?php endif; ?>

            <?php if ( $chosen_location ) : ?>

                <?php // display pickup location name, address & description ?>

                <div class="pickup-location-address">

                    <?php if ( is_cart() && $shipping_method->dslpfw_is_per_item_selection_enabled() ) : ?>
                        <p><?php /* translators: Placeholder: %s - the name of the pickup location */
                        echo sprintf( esc_html__( 'Pickup Location: %s', 'local-pickup-for-woocommerce' ), esc_html( $chosen_location->get_name() ) ); ?></p>
                    <?php endif; ?>

                    <?php $address = $chosen_location->get_address()->get_formatted_html( true ); ?>
                    <small><?php echo ! empty( $address ) ? wp_kses_post( $address . '<br />' ) : ''; ?></small>
                    <?php $description = $chosen_location->get_description(); 
                    if( ! empty( $description ) ) { ?>
                        <small class="pickup-location-note"><strong><?php esc_html_e( 'Note:', 'local-pickup-for-woocommerce' ); ?></strong> <?php echo wp_kses_post( html_entity_decode( $description ) ); ?></small>
                    <?php } ?>
                </div>

            <?php elseif ( is_checkout() ) : ?>

                <?php // the customer has previously selected items for pickup without specifying a location ?>

                <em><?php esc_html_e( 'Please choose a pickup location', 'local-pickup-for-woocommerce' ); ?></em>

            <?php endif; ?>

		</div>
		<?php

		$field_html .= ob_get_clean();

		/**
		 * Filter the package pickup location field HTML.
		 *
		 * @since 1.0.0
		 *
		 * @param string $field_html input field HTML
		 * @param int|string $package_id the current package identifier
		 * @param array $package the current package array
		 */
		return apply_filters( 'dslpfw_get_pickup_location_package_field_html', $field_html, $this->get_package_id(), $this->data_store->get_package() );
	}

    /**
	 * Determines if the current object can be picked up, or must be shipped.
	 *
	 * @since 1.0.0
	 *
	 * @param \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $pickup_location pickup location to check
	 * @return bool
	 */
    //phpcs:disable
	protected function can_be_picked_up( $pickup_location ) { 
		return true;
	}
    //phpcs:enable

}