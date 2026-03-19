<?php
/**
 * Product and category meta related data to use in plguin
 *
 * @link       https://www.thedotstore.com/
 * @since      1.0.0
 *
 * @package    DSLPFW_Local_Pickup_Woocommerce
 * @subpackage DSLPFW_Local_Pickup_Woocommerce/admin
 */

defined( 'ABSPATH' ) or exit;

use DotStore\DSLPFW_Local_Pickup_Woocommerce\Appointments\DSLPFW_Local_Pickup_WooCommerce_Appointment;

/**
 * WooCommerce Products and Product Categories handler for local pickup.
 *
 * @since 1.0.0
 */
class DSLPFW_Local_Pickup_WooCommerce_Orders {

    /** @var \DSLPFW_Local_Pickup_WooCommerce_Order_Items order items handler instance */
	private $dslpfw_order_items;


    /**
	 * Orders pickup location data handler constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

         /**
         * The class responsible for order related check for our plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-local-pickup-woocommerce-order-items.php';
        $this->dslpfw_order_items = new DSLPFW_Local_Pickup_WooCommerce_Order_Items();

		// hide order shipping address when Local Pickup WooCommerce is the shipping method
		add_filter( 'woocommerce_order_hide_shipping_address', array( $this, 'dslpfw_hide_order_shipping_address' ) );

		// use the Pickup Location as the taxable address
		add_filter( 'woocommerce_customer_taxable_address', array( $this, 'dslpfw_set_customer_taxable_address' ) );

        // add order pickup data to order items table in My Account > View Order and Emails
		add_action( 'woocommerce_order_details_after_order_table_items', array( $this, 'dslpfw_add_order_pickup_data' ), 5, 1 );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'dslpfw_add_order_pickup_data' ), 5, 3 );
    }

    /**
	 * Get the order items handler object.
	 *
	 * @since 1.0.0
	 *
	 * @return \DSLPFW_Local_Pickup_WooCommerce_Order_Items
	 */
	public function get_dslpfw_order_items_object() {
		return $this->dslpfw_order_items;
	}

    /**
	 * Gets any order pickup location IDs from the given order.
	 *
	 * Note: this does not return additional pickup data like pickup date or pickup items.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WP_Post|\WC_Order|\WC_Order_Refund $order the order as object, post object or ID
	 * @return null[]|int[] associative array of pickup location IDs for each shipping order item or null if location not found
	 */
	public function get_order_pickup_location_ids( $order ) {

		$order        = $order instanceof \WP_Post || is_numeric( $order ) ? wc_get_order( $order ) : $order;
		$location_ids = [];

		if ( 
            ( $order instanceof \WC_Order || $order instanceof \WC_Order_Refund ) && 
            ( ! class_exists( 'WC_Subscription' ) || ! $order instanceof \WC_Subscription )
        ) {

			$order_shipping_items = $order->get_shipping_methods();

			foreach ( $order_shipping_items as $shipping_item_id => $shipping_item ) {

				// note: we still include invalid / not found IDs
				if ( dslpfw_shipping_method_id() === $shipping_item['method_id'] ) {
					$pickup_location_id                = $this->get_dslpfw_order_items_object()->get_order_item_pickup_location_id( $shipping_item_id );
					$location_ids[ $shipping_item_id ] = is_numeric( $pickup_location_id ) ? (int) $pickup_location_id : null;
				}
			}
		}

		return $location_ids;
	}

    /**
	 * Gets any order pickup locations from the given order.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WP_Post|\WC_Order $order the order object, post or ID
	 * @return null[]|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location[] associative array of pickup location objects for each shipping order item or null if location not found
	 */
	public function get_order_pickup_locations( $order ) {

		$pickup_locations = array();
        $pickup_location_ids = $this->get_order_pickup_location_ids( $order );

		if ( $pickup_location_ids ) {

			foreach ( $pickup_location_ids as $shipping_item_id => $pickup_location_id ) {
				// note: we will still list invalid / not found locations
				$pickup_locations[ $shipping_item_id ] = is_numeric( $pickup_location_id ) ? dslpfw_get_pickup_location( $pickup_location_id ) : null;
			}
		}

		return $pickup_locations;
	}

    /**
	 * Check whether an order has associated pickup locations.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WC_Order|\WP_Post|\WC_Order_Refund $order an order ID or object, post object or refund
	 * @return bool
	 */
	public function order_has_pickup_locations( $order ) {

		$pickup_locations = $this->get_order_pickup_location_ids( $order );

		return ! empty( $pickup_locations );
	}

    /**
	 * Don't require the shipping address if Local Pickup WooCommerce is the only shipping method.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $hidden_shipping_methods_for_address array of shipping methods that don't require a shipping address
	 * @return string[]
	 */
	public function dslpfw_hide_order_shipping_address( $hidden_shipping_methods_for_address ) {

		$hidden_shipping_methods_for_address[] = dslpfw_shipping_method_id();
        
		return $hidden_shipping_methods_for_address;
	}

    /**
	 * Filters the customer taxable address.
	 *
	 * If applying taxes for a chosen pickup location, will use the location address for tax calculation purposes instead of the customer defined.
	 *
	 * @see \DSLPFW_Local_Pickup_WooCommerce_Shipping::apply_pickup_location_tax()
	 * @see \DSLPFW_Local_Pickup_WooCommerce_Orders::dslpfw_use_pickup_location_taxable_address()
	 * @see \DSLPFW_Local_Pickup_WooCommerce_Orders::dslpfw_get_pickup_location_taxable_address()
	 *
	 * TODO If multiple pickup locations are chosen for different packages, there is currently no way to calculate taxes with multiple addresses, hence only the address from the first available location will be chosen. {FN 2017-07-07}
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $taxable_address associative array, defaults to customer address
	 * @return array
	 */
	public function dslpfw_set_customer_taxable_address( $taxable_address ) {

		// bail if we don't or can't use the pickup location's taxable address
		if ( ! $this->dslpfw_use_pickup_location_taxable_address() ) {
			return $taxable_address;
		}

		// in admin, ensure the address is set for the given order in context, if applicable
        $order = wc_get_order( get_the_ID() );
		if ( is_admin() && $order ) {
			return $this->dslpfw_set_customer_taxable_address_for_order( $taxable_address, $order );
		}

		$found_address = false;

		// 1. try to get an address from the package data - which may not be set yet
        $packages = WC()->shipping()->get_packages();
		if ( $packages ) {

			foreach ( $packages as $package ) {

                $found_address = $this->dslpfw_get_pickup_location_taxable_address( $package );
				if ( $found_address ) {
					break;
				}
			}
		}

		if ( WC()->session ) {

			// 2. if the packages array is not built yet, try to look in Local Pickup WooCommerce packages session (which normally would match what will go in WooCommerce)
			if ( ! $found_address && WC()->session->get( 'wc_ds_local_pickup_packages' ) ) {
				$found_address = $this->dslpfw_get_pickup_location_taxable_address( WC()->session->get( 'wc_ds_local_pickup_packages' ) );
			}

			// 3. if the packages in Local Pickup WooCommerce aren't finalized, try looking in the Local Pickup WooCommerce cart session (an earlier session)
			if ( ! $found_address && WC()->session->get( 'wc_ds_local_pickup_cart_items' ) ) {
				$found_address = $this->dslpfw_get_pickup_location_taxable_address( WC()->session->get( 'wc_ds_local_pickup_cart_items' ) );
			}
		}

		// 4. if we can't still determine an address, perhaps check if the cart contains an item that should be picked up, and use the address of the default location before customer makes a choice
		if ( ! $found_address && WC()->cart ) {

			$preferred_location = dslpfw_get_user_default_pickup_location();
			$preferred_address  = $preferred_location ? $preferred_location->get_address() : null;

			// don't bother if the customer does not have a preferred address
			if ( $preferred_address ) {

				foreach ( WC()->cart->cart_contents as $cart_item ) {

					$product = isset( $cart_item['data'] ) && $cart_item['data'] instanceof \WC_Product ? $cart_item['data'] : null;

					// check if the product _must_ be picked up or _can_ be picked up _and_ the default handling is to pickup
					if ( $product && ( dslpfw_product_must_be_picked_up( $product ) || ( dslpfw_shipping_method()->is_default_handling( 'pickup' ) && dslpfw_product_can_be_picked_up( $product ) ) ) ) {

						$found_address = $preferred_address;
						break;
					}
				}
			}
		}

		// 5. by this point either we have a proper address or not (applies tax determined by WooCommerce otherwise)
		if ( $found_address instanceof \DSLPFW_Local_Pickup_Location_Address ) {
			$taxable_address = array(
				$found_address->get_country(),
				$found_address->get_state(),
				$found_address->get_postcode(),
				$found_address->get_city(),
			);
		}

		return $taxable_address;
	}

    /**
	 * Determines whether to use the pickup location address as the taxable address, or not.
	 *
	 * Helper method: @see \DSLPFW_Local_Pickup_WooCommerce_Orders::dslpfw_set_customer_taxable_address()
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function dslpfw_use_pickup_location_taxable_address() {

		$use_location_tax  = false;
		$ds_local_pickup = dslpfw_shipping_method();

		if ( $ds_local_pickup && $ds_local_pickup->apply_pickup_location_tax() && $ds_local_pickup->is_available() ) {

			if ( is_admin() ) {

				// in admin context, it's up to the order to have recorded pickup location data or not, so we don't check anything here, only if the setting applies
				$use_location_tax = true;

			} else {

				/**
				 * We cannot use the wc_get_chosen_shipping_method_for_package() here because that would trigger a call
				 * to wc_shipping_methods_have_changed() before we are done handling the packages and rates for specific
				 * DSLPFW circumstances, causing an issue where the user's chosen rate/method is reset.
				 */
				$shipping_methods = function_exists( 'wc_get_chosen_shipping_method_ids' ) ? wc_get_chosen_shipping_method_ids() : [];
				$methods_count    = is_array( $shipping_methods ) ? count( $shipping_methods ) : 0;
				// ensures there are no packages meant for shipping
				$has_local_pickup = 0 === $methods_count || in_array( dslpfw_shipping_method_id(), $shipping_methods, false ); //phpcs:ignore
				// this check is for when we have a single pickup location in the system which comes pre-selected at checkout and may have not been recorded in the shipping methods IDs in session yet
				$has_pickup_packages = $ds_local_pickup->is_default_handling( 'pickup' )
				    && dslpfw()->get_dslpfw_pickup_locations_object()->get_pickup_locations_count() === 1  // we have in total only 1 pickup location published
				    && dslpfw()->get_dslpfw_packages_object()->get_packages_for_pickup_count() >= 0   // there are perhaps one or more packages for pickup
				    && dslpfw()->get_dslpfw_packages_object()->get_packages_for_shipping_count() === 0; // there are no packages for shipping

				$use_location_tax = $has_local_pickup || $has_pickup_packages;
			}
		}

		return $use_location_tax;
	}

    /**
	 * Sets the taxable address for an order that has already been made.
	 *
	 * @since 1.0.0
	 *
	 * @param array $taxable_address
	 * @param \WC_Order $order
	 * @return array
	 */
	public function dslpfw_set_customer_taxable_address_for_order( $taxable_address, $order ) {

		$found_address = $this->dslpfw_get_pickup_location_taxable_address( $this->dslpfw_get_order_pickup_data( $order, true ) );

		if ( $found_address instanceof \DSLPFW_Local_Pickup_Location_Address ) {
			$taxable_address = array(
				$found_address->get_country(),
				$found_address->get_state(),
				$found_address->get_postcode(),
				$found_address->get_city(),
			);
		}

		return $taxable_address;
	}

    /**
	 * Attempts to return a pickup location address for tax purposes from an array of data.
	 *
	 * @see \DSLPFW_Local_Pickup_WooCommerce_Orders::dslpfw_set_customer_taxable_address()
	 *
	 * @since 1.0.0
	 *
	 * @param array $package_data
	 * @return null|\DSLPFW_Local_Pickup_Location_Address
	 */
	private function dslpfw_get_pickup_location_taxable_address( $package_data ) {

		$found_address = null;

		if ( is_array( $package_data ) ) {

			foreach ( $package_data as $package ) {

				// loop packages as we might not have yet a chosen pickup location
				if ( is_array( $package ) && ! empty( $package['pickup_location_id'] ) ) {

                    $pickup_location = dslpfw_get_pickup_location( $package['pickup_location_id'] );
                    
					// sanity check to skip WooCommerce packages that may still exhibit an pickup location ID key but no longer meant for pickup
					if ( ! ( isset( $package['handling'] ) || isset( $package['lookup_area'] ) || ( isset( $package['ship_via'] ) && dslpfw_shipping_method_id() === $package['ship_via'] ) ) ) {
						continue;
					}

					$address = $pickup_location->get_address();

					// TODO We can only use one taxable address at one time, but in niche cases there could be multiple locations in different tax areas, so these are currently unresolved {FN 2017-07-07}
					if ( $address instanceof \DSLPFW_Local_Pickup_Location_Address ) {

						$found_address = $address;
						break;
					}
				}
			}
		}

		return $found_address;
	}

    /**
	 * Add order pickup data to customer order views and emails.
	 *
	 * This method is used as hook callback for both `woocommerce_order_items_table` and `woocommerce_email_after_order_table` actions.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Order $order order object to output pickup data for
	 * @param bool $sent_to_admin when callback is for an email, whether this is sent to an admin
	 * @param bool $plan_text when callback is for an email, whether this is sent as plan text
	 */
	public function dslpfw_add_order_pickup_data( $order, $sent_to_admin = false, $plan_text = false ) {

		$pickup_data = $this->dslpfw_get_order_pickup_data( $order );

		if ( ! empty( $pickup_data ) ) {

			$current_action = current_action();

			if ( 'woocommerce_order_items_table' === $current_action || 'woocommerce_order_details_after_order_table_items' === $current_action ) {

				wc_get_template( 'orders/order-pickup-details.php', array(
					'order'           => $order,
					'pickup_data'     => $pickup_data,
					'shipping_method' => dslpfw_shipping_method(),
				), '', plugin_dir_path( dirname( __FILE__ ) ) . '/templates/' );

			} elseif ( 'woocommerce_email_after_order_table' === $current_action ) {

				$template = true === $plan_text ? 'emails/plain/order-pickup-details.php' : 'emails/order-pickup-details.php';

				wc_get_template( $template, array(
					'order'           => $order,
					'pickup_data'     => $pickup_data,
					'shipping_method' => dslpfw_shipping_method(),
					'sent_to_admin'   => $sent_to_admin,
				), '', plugin_dir_path( dirname( __FILE__ ) ) . '/templates/' );
			}
		}
	}

    /**
	 * Get pickup data for order.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WP_Post|\WC_Order $order order ID, object or post object
	 * @param bool $raw whether output an array intended for machine reading or human reading (default)
	 * @return array associative array of key values for each pickup package in order
	 */
	public function dslpfw_get_order_pickup_data( $order, $raw = false ) {

		$order            = $order instanceof \WP_Post || is_numeric( $order ) ? wc_get_order( $order ) : $order;
		$pickup_data      = array();
		$pickup_locations = $this->get_order_pickup_location_ids( $order );

		if ( ! empty( $pickup_locations ) ) {

			$shipping_items        = array_keys( $order->get_shipping_methods() );
			$pickup_shipping_items = array_keys( $pickup_locations );

			// loop all shipping items
			foreach ( $shipping_items as $shipping_item_id ) {

				// check if the shipping item is among those meant for pickup
				if ( in_array( $shipping_item_id, $pickup_shipping_items, false ) ) { //phpcs:ignore

					// try to get appointment
					try {
						$pickup_appointment = new DSLPFW_Local_Pickup_WooCommerce_Appointment( $shipping_item_id );
					} catch ( Exception $exception ) {
						$pickup_appointment = false;
					}

					$order_items_handler = $this->get_dslpfw_order_items_object();
					$pickup_location_id  = $order_items_handler->get_order_item_pickup_location_id( $shipping_item_id );
					$pickup_items        = $order_items_handler->get_order_item_pickup_items( $shipping_item_id );

					if ( true === $raw ) {

						$pickup_data[ $shipping_item_id ] = [
							'pickup_location_id' => (int) $pickup_location_id,
							'pickup_items'       => is_array( $pickup_items ) ? $pickup_items : [],
							/** {@see Appointment::get_start()} should always return a {@see \DateTime} object for existing appointments */
							'pickup_date'        => $pickup_appointment ? $pickup_appointment->get_start()->format( 'Y-m-d' ) : '',
							// TODO extend raw data to parse pickup appointment time {FN 2019-11-27}
						];

					} else {

						$pickup_location         = $order_items_handler->get_order_item_pickup_location( $shipping_item_id );
						$pickup_location_name    = $order_items_handler->get_order_item_pickup_location_name( $shipping_item_id );
						$pickup_location_address = $order_items_handler->get_order_item_pickup_location_address( $shipping_item_id, 'plain' );
						$pickup_location_phone   = $order_items_handler->get_order_item_pickup_location_phone( $shipping_item_id, true );
						$pickup_location_notes   = $pickup_location ? html_entity_decode($pickup_location->get_description()) : '';

						$pickup_data[ $shipping_item_id ][ esc_html__( 'Pickup Location', 'local-pickup-for-woocommerce' ) ] = $pickup_location_name;
						$pickup_data[ $shipping_item_id ][ esc_html__( 'Address', 'local-pickup-for-woocommerce' ) ] = $pickup_location_address;

						if ( ! empty( $pickup_location_phone ) ) {
							$pickup_data[ $shipping_item_id ][ esc_html__( 'Phone', 'local-pickup-for-woocommerce' ) ] = $pickup_location_phone;
						}

						if ( ! empty( $pickup_location_notes ) ) {
							$pickup_data[ $shipping_item_id ][ esc_html__( 'Note', 'local-pickup-for-woocommerce' ) ] = $pickup_location_notes;
						}

						// check for pickup appointment/date historical data
						if ( ! empty( $pickup_appointment ) && ! empty( $pickup_appointment_start = $pickup_appointment->get_start() ) ) { //phpcs:ignore

							$pickup_data[ $shipping_item_id ][ esc_html__( 'Pickup Date', 'local-pickup-for-woocommerce' ) ] = date_i18n( wc_date_format(), $pickup_appointment_start->getTimestamp() + $pickup_appointment_start->getOffset() );

							if ( $pickup_appointment->has_pickup_time() ) {

								// get time from appointment
								$pickup_data[ $shipping_item_id ][ esc_html__( 'Pickup Time', 'local-pickup-for-woocommerce' ) ] = $pickup_appointment_start->format( wc_time_format() );

							// we are dealing with an anytime appointment with or without lead time, so let's show the schedule if pickup location is available
							} elseif ( ! empty( $pickup_location ) ) {

								// anytime appointments have a start date that is either equal to the start of the day or the minimum pickup time for that date
								$pickup_minimum_time = $pickup_appointment_start->getTimestamp() - ( clone $pickup_appointment_start )->setTime( 0, 0, 0 )->getTimestamp();

								$pickup_data[ $shipping_item_id ][ esc_html__( 'Pickup Time', 'local-pickup-for-woocommerce' ) ] = $pickup_location->get_pickup_hours()->get_schedule( $pickup_appointment_start->format( 'w' ), true, $pickup_minimum_time );
							}
						}

                        $order_items = $order->get_items();
						if ( ! empty( $pickup_items ) && $order_items ) {

							$items_to_pickup = array();

							foreach ( $order_items as $order_item_id => $order_item_data ) {

								if ( in_array( $order_item_id, $pickup_items, false ) ) { //phpcs:ignore

									$name = isset( $order_item_data['name'] ) ? $order_item_data['name'] : null;
									$qty  = isset( $order_item_data['qty'] ) ? $order_item_data['qty'] : null;

									if ( $name && $qty ) {
										$items_to_pickup[] = is_rtl() ? '&times; ' . $qty . ' ' . $name : $name . ' &times; ' . $qty;
									}
								}
							}

							if ( ! empty( $items_to_pickup ) ) {
								$pickup_data[ $shipping_item_id ][ esc_html__( 'Items to Pickup', 'local-pickup-for-woocommerce' ) ] = implode( ', ', $items_to_pickup );
							}
						}
					}
				}
			}
		}

		/**
		 * Filter an order pickup data.
		 *
		 * @since 1.0.0
		 *
		 * @param array $pickup_data array of pickup data. Empty if there is no associated data
		 * @param \WC_Order $order a WooCommerce Order object
		 * @param bool $raw whether we are returning an array meant for human display (false) or raw data (true)
		 */
		return apply_filters( 'dslpfw_get_order_pickup_data', $pickup_data, $order, $raw );
	}
}