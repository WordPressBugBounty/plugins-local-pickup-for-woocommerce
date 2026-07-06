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
/**
 * Field component to select a pickup location.
 *
 * @since 1.0.0
 */
abstract class DSLPFW_Pickup_Location_Field {
    /** @var null|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location cached user default pickup location */
    protected $user_default_pickup_location = [];

    /** @var string the object type for the current storage instance */
    protected $object_type = '';

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

    /**
     * Returns the (current) user default pickup location.
     *
     * @since 1.0.0
     *
     * @param int $user_id the user (defaults to current user)
     * @return null|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location
     */
    protected function get_user_default_pickup_location( $user_id = 0 ) {
        $user_id = ( $user_id > 0 ? (int) $user_id : get_current_user_id() );
        if ( empty( $this->user_default_pickup_location[$user_id] ) ) {
            $this->user_default_pickup_location[$user_id] = dslpfw_get_user_default_pickup_location( $user_id );
        }
        return $this->user_default_pickup_location[$user_id];
    }

    /**
     * Gets the location select field HTML.
     *
     * @since 1.0.0
     *
     * @param string $object_id object ID, like cart key or package index
     * @param \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location|null $chosen_location optional, chosen pickup location
     * @param \WC_Product|null optional, the current product
     * @return string field HTML
     */
    protected function get_location_select_html( $object_id, $chosen_location = null, $product = null ) {
        $object_type = $this->get_object_type();
        $field_name = ( dslpfw_shipping_method()->dslpfw_is_per_item_selection_enabled() ? '_pickup_location_id' : '_shipping_method_pickup_location_id' );
        $field_html = '';
        $using_single_location = false;
        $selected_location = $this->get_product_pickup_location( $product );
        if ( $selected_location ) {
            // the product can only be picked up at only location
            $chosen_location = $selected_location;
            $using_single_location = true;
        } elseif ( !$chosen_location ) {
            // fallback to the user default pickup location, if a chosen location param was not provided
            $chosen_location = $this->get_user_default_pickup_location();
            if ( $chosen_location instanceof \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location && $product instanceof \WC_Product && !dslpfw_product_can_be_picked_up( $product, $chosen_location ) ) {
                $chosen_location = null;
            }
        }
        if ( !$using_single_location && !$chosen_location ) {
            $package = array();
            $available_count = $this->count_available_pickup_locations( $product );
            if ( 'package' === $object_type ) {
                $packages = WC()->shipping()->get_packages();
                if ( isset( $packages[$object_id] ) ) {
                    $package = $packages[$object_id];
                    $available_count = dslpfw_count_package_available_pickup_locations( $package );
                }
            }
            $context = array(
                'object_id'                 => $object_id,
                'object_type'               => $object_type,
                'product'                   => $product,
                'package'                   => $package,
                'available_locations_count' => $available_count,
            );
            if ( dslpfw_should_auto_select_pickup_location( $available_count, $context ) ) {
                $first_location_id = dslpfw_get_first_available_pickup_location_id( $product, $package );
                $chosen_location = ( $first_location_id > 0 ? dslpfw_get_pickup_location( $first_location_id ) : null );
            }
        }
        // the product can only be picked up at only location
        if ( $using_single_location ) {
            $field_html .= $this->get_single_location_select_html( $object_id, $chosen_location, $field_name );
        } else {
            $package = array();
            if ( 'package' === $object_type ) {
                $packages = WC()->shipping()->get_packages();
                if ( isset( $packages[$object_id] ) ) {
                    $package = $packages[$object_id];
                }
            }
            $field_html .= $this->get_simple_location_select_html(
                $object_id,
                $chosen_location,
                $object_type,
                $field_name,
                $product,
                $package
            );
        }
        return $field_html;
    }

    /**
     * Gets the single location select field HTML.
     *
     * @since 1.0.0
     *
     * @param string $object_id object ID, like cart key or package index
     * @param \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location|null $chosen_location, chosen pickup location
     * @param string $field_name HTML field name
     * @return string field HTML
     */
    private function get_single_location_select_html( $object_id, $chosen_location, $field_name ) {
        if ( dslpfw_shipping_method()->dslpfw_is_per_order_selection_enabled() ) {
            if ( 'package' === $this->get_object_type() ) {
                // set this package for pickup with the current chosen location, since we've made sure already it's determined
                $pickup_data = dslpfw()->get_dslpfw_session_object()->get_package_pickup_data( $object_id );
                $pickup_data['handling'] = 'pickup';
                $pickup_data['pickup_location_id'] = $chosen_location->get_id();
                dslpfw()->get_dslpfw_session_object()->set_package_pickup_data( $object_id, $pickup_data );
                $package = dslpfw()->get_dslpfw_packages_object()->get_shipping_package( $object_id );
                $package_cart_item_keys = ( !empty( $package ) ? array_keys( $package['contents'] ) : [] );
                if ( !empty( $package_cart_item_keys ) ) {
                    foreach ( $package_cart_item_keys as $cart_item_key ) {
                        $session_data = dslpfw()->get_dslpfw_session_object()->get_cart_item_pickup_data( $cart_item_key );
                        // set cart item handling for items in this package
                        $session_data['handling'] = 'pickup';
                        $session_data['pickup_location_id'] = $chosen_location->get_id();
                        dslpfw()->get_dslpfw_session_object()->set_cart_item_pickup_data( $cart_item_key, $session_data );
                    }
                }
            }
        }
        ob_start();
        ?>

		<?php 
        if ( dslpfw_shipping_method()->dslpfw_is_per_order_selection_enabled() ) {
            ?>

			<?php 
            if ( is_checkout() ) {
                ?>
				<?php 
                echo esc_html( $chosen_location->get_name() );
                ?>
				<input type="hidden"
				       name="<?php 
                echo sanitize_html_class( $field_name );
                ?>[<?php 
                echo esc_attr( esc_attr( $object_id ) );
                ?>]"
				       value="<?php 
                echo esc_attr( $chosen_location->get_id() );
                ?>">
			<?php 
            } else {
                ?>
				<small><?php 
                /* translators: Placeholder: %s - pickup location name */
                printf( esc_html__( 'Pickup Location: %s', 'local-pickup-for-woocommerce' ), esc_html( $chosen_location->get_name() ) );
                ?></small>
			<?php 
            }
            ?>

		<?php 
        } else {
            ?>

			<?php 
            if ( 'cart-item' === $this->get_object_type() && $chosen_location ) {
                $session_data = dslpfw()->get_dslpfw_session_object()->get_cart_item_pickup_data( $object_id );
                if ( empty( $session_data['pickup_location_id'] ) ) {
                    $session_data['handling'] = ( !empty( $session_data['handling'] ) ? $session_data['handling'] : 'pickup' );
                    $session_data['pickup_location_id'] = $chosen_location->get_id();
                    dslpfw()->get_dslpfw_session_object()->set_cart_item_pickup_data( $object_id, $session_data );
                }
            }
            ?>

			<small><abbr
					title="<?php 
            echo esc_attr( $chosen_location->get_address()->get_formatted_html( true ) );
            ?>"><?php 
            /* translators: Placeholder: %s - pickup location name */
            printf( esc_html__( 'Available for pickup at: %s', 'local-pickup-for-woocommerce' ), esc_html( $chosen_location->get_name() ) );
            ?></abbr></small>

			<?php 
            if ( is_checkout() ) {
                ?>
				<input type="hidden"
				       name="<?php 
                echo sanitize_html_class( $field_name );
                ?>[<?php 
                echo esc_attr( $object_id );
                ?>]"
				       value="<?php 
                echo esc_attr( $chosen_location->get_id() );
                ?>">
			<?php 
            }
            ?>

		<?php 
        }
        ?>

		<?php 
        return ob_get_clean();
    }

    /**
     * Gets the simple (not enhanced search) location select HTML.
     *
     * @since 1.0.0
     *
     * @param string $object_id object ID, like cart key or package index
     * @param \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location|null $chosen_location, chosen pickup location
     * @param string $object_type either `cart-item` or `package`
     * @param string $field_name HTML field name
     * @return string field HTML
     */
    private function get_simple_location_select_html(
        $object_id,
        $chosen_location,
        $object_type,
        $field_name,
        $product = null,
        $package = array()
    ) {
        $pickup_locations = dslpfw()->get_dslpfw_pickup_locations_object()->get_sorted_pickup_locations();
        $chosen_location = ( array_key_exists( ( $chosen_location ? $chosen_location->get_id() : null ), $pickup_locations ) ? $chosen_location : null );
        $eligible_locations = array();
        foreach ( $pickup_locations as $pickup_location ) {
            if ( $this->can_be_picked_up( $pickup_location ) ) {
                // @phpstan-ignore-line
                $eligible_locations[$pickup_location->get_id()] = $pickup_location;
            }
        }
        if ( !empty( $package ) ) {
            foreach ( $eligible_locations as $location_id => $pickup_location ) {
                if ( !dslpfw_package_can_use_pickup_location( $package, $pickup_location ) ) {
                    unset($eligible_locations[$location_id]);
                }
            }
        }
        if ( $chosen_location && !isset( $eligible_locations[$chosen_location->get_id()] ) && $this->can_be_picked_up( $chosen_location ) ) {
            // @phpstan-ignore-line
            $eligible_locations[$chosen_location->get_id()] = $chosen_location;
        }
        $nearby_location_ids = array();
        $context = array(
            'object_id'   => $object_id,
            'object_type' => $object_type,
            'product'     => $product,
            'package'     => $package,
        );
        $placeholder = esc_html__( 'Select a pickup location', 'local-pickup-for-woocommerce' );
        /**
         * Filters the placeholder text for the pickup location dropdown.
         *
         * @since 1.1.3
         *
         * @param string $placeholder   placeholder text.
         * @param string $object_id     object ID, like cart key or package index.
         * @param string $object_type   either `cart-item` or `package`.
         */
        $placeholder = apply_filters(
            'dslpfw_pickup_location_select_placeholder',
            $placeholder,
            $object_id,
            $object_type
        );
        // Implement filter to restrict address characters (#103803 issue)
        $restricted_addresschars = apply_filters( 'dslpfw_restricted_address_characters', [
            '-',
            ',',
            '.',
            '#',
            '°'
        ] );
        ob_start();
        ?>

		<select
			name="<?php 
        echo sanitize_html_class( $field_name );
        ?>[<?php 
        echo esc_attr( $object_id );
        ?>]"
			class="pickup-location-list"
			data-placeholder="<?php 
        echo esc_attr( $placeholder );
        ?>"
			data-pickup-object-type="<?php 
        echo esc_attr( $object_type );
        ?>"
			data-pickup-object-id="<?php 
        echo esc_attr( $object_id );
        ?>" >
			<option value=""><?php 
        echo esc_html( $placeholder );
        ?></option>
			<?php 
        foreach ( $eligible_locations as $pickup_location ) {
            ?>
					<option
						<?php 
            $address = $pickup_location->get_address();
            ?>
						data-name="<?php 
            echo esc_attr( $pickup_location->get_name() );
            ?>"
						data-postcode="<?php 
            echo esc_attr( $address->get_postcode() );
            ?>"
						data-city="<?php 
            echo esc_attr( $address->get_city() );
            ?>"
						data-address="<?php 
            echo esc_attr( str_replace( $restricted_addresschars, '', $address->get_street_address( 'string', ' ' ) ) );
            ?>"
						data-address-formatted="<?php 
            echo esc_attr( wp_strip_all_tags( $address->get_formatted_html( true ) ) );
            ?>"
						data-nearby="<?php 
            echo ( empty( $nearby_location_ids ) || in_array( (int) $pickup_location->get_id(), $nearby_location_ids, true ) ? '1' : '0' );
            ?>"
						value="<?php 
            echo esc_attr( $pickup_location->get_id() );
            ?>"
						<?php 
            selected( $pickup_location->get_id(), ( $chosen_location ? $chosen_location->get_id() : null ), true );
            ?>>
						<?php 
            echo esc_html( $pickup_location->get_formatted_name() );
            ?>
					</option>
			<?php 
        }
        ?>
		</select>

		<?php 
        return ob_get_clean();
    }

    /**
     * Returns a default pickup location for a product if the product has only one pickup location available.
     *
     * @since 1.0.0
     *
     * @param \WC_Product $product
     * @return null|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location
     */
    private function get_product_pickup_location( $product ) {
        $shipping_method = dslpfw_shipping_method();
        $products = dslpfw()->get_dslpfw_products_object();
        $product_pickup_location = null;
        // per-item checkout display
        if ( $product instanceof \WC_Product ) {
            $product_pickup_location = $products->get_product_pickup_location( $product );
            // per-order checkout display
        } elseif ( null === $product && $this instanceof Package_Pickup_Location_Field && $shipping_method && is_cart() ) {
            // check the following conditions, or the pickup location display in cart will be confusing
            if ( !($shipping_method->dslpfw_is_per_order_selection_enabled() && dslpfw()->get_dslpfw_pickup_locations_object()->get_pickup_locations_count() > 1) ) {
                $package = $this->data_store->get_package();
                // @phpstan-ignore-line
                $contents = ( isset( $package['contents'] ) ? $package['contents'] : [] );
                $location_ids = [];
                foreach ( $contents as $item ) {
                    $package_product = ( isset( $item['data'] ) ? $item['data'] : null );
                    if ( $package_product instanceof \WC_Product ) {
                        $available_locations = $products->get_product_pickup_locations( $package_product, [
                            'fields' => 'ids',
                        ] );
                        $location_ids[] = ( !empty( $available_locations ) ? current( $available_locations ) : 0 );
                    }
                }
                $location_ids = array_unique( $location_ids );
                $product_pickup_location = ( 1 === count( $location_ids ) ? dslpfw_get_pickup_location( current( $location_ids ) ) : null );
            }
        }
        return ( $product_pickup_location instanceof \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location ? $product_pickup_location : null );
    }

    /**
     * Counts pickup locations available for the current field context.
     *
     * @since 1.1.3
     *
     * @param \WC_Product|null $product optional product to filter locations.
     * @return int
     */
    protected function count_available_pickup_locations( $product = null ) {
        if ( $product instanceof \WC_Product ) {
            return dslpfw()->get_dslpfw_products_object()->get_product_pickup_locations_count( $product );
        }
        $count = 0;
        foreach ( dslpfw()->get_dslpfw_pickup_locations_object()->get_sorted_pickup_locations() as $pickup_location ) {
            if ( $this->can_be_picked_up( $pickup_location ) ) {
                // @phpstan-ignore-line
                $count++;
            }
        }
        return $count;
    }

}
