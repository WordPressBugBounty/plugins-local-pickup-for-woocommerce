<?php
/**
 * WooCommerce Cart & Checkout Blocks integration.
 *
 * @package    DSLPFW_Local_Pickup_Woocommerce
 * @subpackage DSLPFW_Local_Pickup_Woocommerce/includes
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;

/**
 * Registers Store API data, update callbacks, and block scripts for Cart/Checkout Blocks.
 *
 * @since 1.2.1
 */
class DSLPFW_Local_Pickup_WooCommerce_Blocks implements IntegrationInterface {

	const IDENTIFIER = 'dslpfw-local-pickup';

	/**
	 * Register Store API extensions, update callback, and AJAX used by the Blocks UI.
	 *
	 * @since 1.2.1
	 */
	public static function register_store_api_and_ajax() {
		self::register_store_api();

		if ( function_exists( 'woocommerce_store_api_register_update_callback' ) ) {
			woocommerce_store_api_register_update_callback(
				array(
					'namespace' => self::IDENTIFIER,
					'callback'  => array( __CLASS__, 'handle_cart_update' ),
				)
			);
		}

		add_action( 'woocommerce_store_api_checkout_update_order_meta', array( __CLASS__, 'validate_checkout_order' ), 10, 1 );
		add_action( 'wp_ajax_dslpfw_blocks_get_appointment_times', array( __CLASS__, 'ajax_get_appointment_times' ) );
		add_action( 'wp_ajax_nopriv_dslpfw_blocks_get_appointment_times', array( __CLASS__, 'ajax_get_appointment_times' ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name() {
		return self::IDENTIFIER;
	}

	/**
	 * {@inheritdoc}
	 */
	public function initialize() {
		$this->register_scripts();
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_script_handles() {
		return array( 'dslpfw-blocks' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_editor_script_handles() {
		return array();
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_script_data() {
		$shipping_method = function_exists( 'dslpfw_shipping_method' ) ? dslpfw_shipping_method() : null;

		return array(
			'namespace'              => self::IDENTIFIER,
			'shippingMethodId'       => class_exists( '\DSLPFW_Local_Pickup_Woocommerce' )
				? \DSLPFW_Local_Pickup_Woocommerce::DSLPFW_SHIPPING_METHOD_ID
				: 'ds_local_pickup',
			'pickupSelectionMode'    => $shipping_method ? $shipping_method->pickup_selection_mode() : 'per-order',
			'appointmentsMode'       => $shipping_method ? $shipping_method->pickup_appointments_mode() : 'disabled',
			'anytimeAppointments'    => $shipping_method ? $shipping_method->is_anytime_appointments_enabled() : true,
			'displayShippingAddress' => $shipping_method ? (bool) $shipping_method->dslpfw_display_shipping_address_fields() : false,
			'ajaxUrl'                => admin_url( 'admin-ajax.php' ),
			'appointmentDataNonce'   => wp_create_nonce( 'dslpfw-get-pickup-location-appointment-data' ),
			'appointmentTimesNonce'  => wp_create_nonce( 'dslpfw-blocks-get-appointment-times' ),
			'dateFormat'             => wc_date_format(),
			'timeFormat'             => wc_time_format(),
			'startOfWeek'            => (int) get_option( 'start_of_week', 1 ),
			'datepickerTitle'        => esc_html__( 'Choose a pickup date', 'local-pickup-for-woocommerce' ),
			'i18n'                   => array(
				'selectLocation'       => __( 'Select a pickup location', 'local-pickup-for-woocommerce' ),
				'setForPickup'         => __( 'This item is set for shipping. Click here to pickup this item.', 'local-pickup-for-woocommerce' ),
				'setForShipping'       => __( 'This item is set for pickup. Click here to ship this item.', 'local-pickup-for-woocommerce' ),
				'clickToPickup'        => __( 'Click here to pickup this item.', 'local-pickup-for-woocommerce' ),
				'clickToShip'          => __( 'Click here to ship this item.', 'local-pickup-for-woocommerce' ),
				'scheduleAppointment'  => __( 'Schedule a pickup appointment', 'local-pickup-for-woocommerce' ),
				'optional'             => __( '(optional)', 'local-pickup-for-woocommerce' ),
				'pickupDate'           => _x( 'Pickup Date', 'Placeholder text for the datepicker field', 'local-pickup-for-woocommerce' ),
				'clear'                => _x( 'Clear', 'Clear a chosen pickup appointment date', 'local-pickup-for-woocommerce' ),
				'availableTimes'       => __( 'Available pickup times on %1$s (all times in %2$s):', 'local-pickup-for-woocommerce' ),
				'openingHours'         => __( 'Opening hours for pickup on %s:', 'local-pickup-for-woocommerce' ),
				'pleaseChooseLocation' => __( 'Please choose a pickup location', 'local-pickup-for-woocommerce' ),
				'note'                 => __( 'Note:', 'local-pickup-for-woocommerce' ),
				'updating'             => __( 'Updating…', 'local-pickup-for-woocommerce' ),
			),
		);
	}

	/**
	 * Register frontend script/style for blocks.
	 *
	 * @since 1.2.1
	 */
	private function register_scripts() {
		$script_path = 'public/js/dslpfw-blocks.js';
		$style_path  = 'public/css/dslpfw-blocks.css';
		$script_url  = DSLPFW_PLUGIN_URL . $script_path;
		$style_url   = DSLPFW_PLUGIN_URL . $style_path;
		$script_file = DSLPFW_PLUGIN_BASE_DIR . $script_path;
		$style_file  = DSLPFW_PLUGIN_BASE_DIR . $style_path;
		$version     = file_exists( $script_file ) ? (string) filemtime( $script_file ) : DSLPFW_PLUGIN_VERSION;

		wp_register_script(
			'dslpfw-blocks',
			$script_url,
			array(
				'jquery',
				'jquery-ui-datepicker',
				'wc-blocks-checkout',
				'wc-blocks-data-store',
				'wp-element',
				'wp-data',
				'wp-i18n',
				'wp-plugins',
				'wp-hooks',
			),
			$version,
			true
		);

		$jquery_ui_css = DSLPFW_PLUGIN_URL . 'public/css/jquery-ui.css';
		$datepicker_css = DSLPFW_PLUGIN_URL . 'public/css/local-pickup-woocommerce-datepicker.css';

		wp_register_style(
			'dslpfw-jquery-ui',
			$jquery_ui_css,
			array(),
			DSLPFW_PLUGIN_VERSION
		);
		wp_register_style(
			'dslpfw-datepicker',
			$datepicker_css,
			array( 'dslpfw-jquery-ui' ),
			DSLPFW_PLUGIN_VERSION
		);

		if ( file_exists( $style_file ) ) {
			wp_register_style(
				'dslpfw-blocks',
				$style_url,
				array( 'dslpfw-jquery-ui', 'dslpfw-datepicker' ),
				(string) filemtime( $style_file )
			);
			wp_enqueue_style( 'dslpfw-blocks' );
		}
	}

	/**
	 * Extend Store API cart / cart item responses.
	 *
	 * @since 1.2.1
	 */
	private static function register_store_api() {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CartItemSchema::IDENTIFIER,
				'namespace'       => self::IDENTIFIER,
				'data_callback'   => array( __CLASS__, 'extend_cart_item_data' ),
				'schema_callback' => array( __CLASS__, 'extend_cart_item_schema' ),
				'schema_type'     => ARRAY_A,
			)
		);

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => self::IDENTIFIER,
				'data_callback'   => array( __CLASS__, 'extend_cart_data' ),
				'schema_callback' => array( __CLASS__, 'extend_cart_schema' ),
				'schema_type'     => ARRAY_A,
			)
		);
	}

	/**
	 * Cart item extension payload.
	 *
	 * @param array $cart_item Cart item.
	 * @return array
	 */
	public static function extend_cart_item_data( $cart_item ) {
		if ( ! function_exists( 'dslpfw_shipping_method' ) || ! function_exists( 'dslpfw' ) ) {
			return array( 'enabled' => false );
		}

		$shipping_method = dslpfw_shipping_method();
		if ( ! $shipping_method || ! $shipping_method->is_available() ) {
			return array(
				'enabled' => false,
			);
		}

		$product = isset( $cart_item['data'] ) && $cart_item['data'] instanceof \WC_Product ? $cart_item['data'] : null;
		if ( ! $product || ! dslpfw_product_can_be_picked_up( $product ) ) {
			return array(
				'enabled' => false,
			);
		}

		$cart_item_key = isset( $cart_item['key'] ) ? $cart_item['key'] : ( isset( $cart_item['cart_item_key'] ) ? $cart_item['cart_item_key'] : '' );

		// Ensure session defaults exist for Store API requests (classic flow uses template_redirect).
		if ( $cart_item_key ) {
			dslpfw()->get_dslpfw_session_object()->set_cart_item_pickup_data( $cart_item_key, array() );
		}
		$pickup_data   = $cart_item_key ? dslpfw()->get_dslpfw_session_object()->get_cart_item_pickup_data( $cart_item_key ) : array();
		$must_pickup   = dslpfw_product_must_be_picked_up( $product );
		$can_ship      = ! $must_pickup;
		$handling      = isset( $pickup_data['handling'] ) ? $pickup_data['handling'] : ( $shipping_method->is_default_handling( 'pickup' ) || $must_pickup ? 'pickup' : 'ship' );

		if ( $must_pickup ) {
			$handling = 'pickup';
		}

		$location_id = ! empty( $pickup_data['pickup_location_id'] ) ? (int) $pickup_data['pickup_location_id'] : 0;
		$locations   = self::get_locations_for_product( $product );

		return array(
			'enabled'              => true,
			'perItemSelection'     => $shipping_method->dslpfw_is_per_item_selection_enabled(),
			'handling'             => $handling,
			'mustBePickedUp'       => $must_pickup,
			'canBeShipped'         => $can_ship,
			'showHandlingToggle'   => ! $must_pickup && $shipping_method->dslpfw_is_per_item_selection_enabled() && ! $shipping_method->is_item_handling_mode( 'automatic' ),
			'pickupLocationId'     => $location_id,
			'locations'            => $locations,
			'selectedLocation'     => self::format_location( $location_id > 0 ? dslpfw_get_pickup_location( $location_id ) : null ),
		);
	}

	/**
	 * Cart item extension schema.
	 *
	 * @return array
	 */
	public static function extend_cart_item_schema() {
		return array(
			'enabled'            => array( 'description' => 'Whether pickup UI applies.', 'type' => 'boolean', 'context' => array( 'view', 'edit' ), 'readonly' => true ),
			'perItemSelection'   => array( 'description' => 'Per-item selection mode.', 'type' => 'boolean', 'context' => array( 'view', 'edit' ), 'readonly' => true ),
			'handling'           => array( 'description' => 'pickup or ship.', 'type' => 'string', 'context' => array( 'view', 'edit' ), 'readonly' => true ),
			'mustBePickedUp'     => array( 'description' => 'Product requires pickup.', 'type' => 'boolean', 'context' => array( 'view', 'edit' ), 'readonly' => true ),
			'canBeShipped'       => array( 'description' => 'Product can ship.', 'type' => 'boolean', 'context' => array( 'view', 'edit' ), 'readonly' => true ),
			'showHandlingToggle' => array( 'description' => 'Show ship/pickup toggle.', 'type' => 'boolean', 'context' => array( 'view', 'edit' ), 'readonly' => true ),
			'pickupLocationId'   => array( 'description' => 'Selected location ID.', 'type' => array( 'integer', 'null' ), 'context' => array( 'view', 'edit' ), 'readonly' => true ),
			'locations'          => array( 'description' => 'Eligible locations.', 'type' => 'array', 'context' => array( 'view', 'edit' ), 'readonly' => true ),
			'selectedLocation'   => array( 'description' => 'Selected location details.', 'type' => array( 'object', 'null' ), 'context' => array( 'view', 'edit' ), 'readonly' => true ),
		);
	}

	/**
	 * Cart-level extension payload (packages / appointments).
	 *
	 * @return array
	 */
	public static function extend_cart_data() {
		if ( ! function_exists( 'dslpfw_shipping_method' ) || ! function_exists( 'dslpfw' ) ) {
			return array(
				'enabled'  => false,
				'packages' => array(),
			);
		}

		$shipping_method = dslpfw_shipping_method();
		if ( ! $shipping_method || ! $shipping_method->is_available() ) {
			return array(
				'enabled'  => false,
				'packages' => array(),
			);
		}

		$packages_data = array();
		$packages      = WC()->shipping() ? WC()->shipping()->get_packages() : array();

		foreach ( $packages as $package_id => $package ) {
			$chosen_methods = WC()->session ? WC()->session->get( 'chosen_shipping_methods', array() ) : array();
			$chosen         = isset( $chosen_methods[ $package_id ] ) ? $chosen_methods[ $package_id ] : '';
			$method_id      = $shipping_method->get_method_id();
			$has_lp_rate    = ! empty( $package['rates'][ $method_id ] );
			$is_pickup      = ( $chosen === $method_id )
				|| ( isset( $package['ship_via'] ) && in_array( $method_id, (array) $package['ship_via'], true ) );

			if ( ! $is_pickup && ! $has_lp_rate ) {
				continue;
			}

			$session          = dslpfw()->get_dslpfw_session_object()->get_package_pickup_data( $package_id );
			$pickup_location  = null;
			$location_id      = ! empty( $session['pickup_location_id'] ) ? (int) $session['pickup_location_id'] : 0;

			if ( $location_id <= 0 && ! empty( $package['pickup_location_id'] ) ) {
				$location_id = (int) $package['pickup_location_id'];
			}

			// Per-item: derive package location from the first pickup cart item when needed.
			if ( $location_id <= 0 && $shipping_method->dslpfw_is_per_item_selection_enabled() && ! empty( $package['contents'] ) ) {
				$cart_sessions = dslpfw()->get_dslpfw_session_object()->get_cart_item_pickup_data( null );
				foreach ( array_keys( $package['contents'] ) as $cart_item_key ) {
					if ( ! empty( $cart_sessions[ $cart_item_key ]['pickup_location_id'] ) ) {
						$location_id = (int) $cart_sessions[ $cart_item_key ]['pickup_location_id'];
						break;
					}
				}
			}

			if ( $location_id > 0 ) {
				$pickup_location = dslpfw_get_pickup_location( $location_id );
			}

			$packages_data[] = array(
				'packageId'           => (string) $package_id,
				'isPickup'            => ( $chosen === $method_id ) || $is_pickup,
				'chosenMethod'        => $chosen,
				'perOrderSelection'   => $shipping_method->dslpfw_is_per_order_selection_enabled(),
				'pickupLocationId'    => $location_id,
				'selectedLocation'    => self::format_location( $pickup_location ),
				'locations'           => self::get_locations_for_package( $package ),
				'pickupDate'          => isset( $session['pickup_date'] ) ? (string) $session['pickup_date'] : '',
				'appointmentOffset'   => isset( $session['appointment_offset'] ) ? (string) $session['appointment_offset'] : '',
				'appointmentsMode'    => $shipping_method->pickup_appointments_mode(),
				'anytimeAppointments' => $shipping_method->is_anytime_appointments_enabled(),
			);
		}

		return array(
			'enabled'             => true,
			'pickupSelectionMode' => $shipping_method->pickup_selection_mode(),
			'appointmentsMode'    => $shipping_method->pickup_appointments_mode(),
			'shippingMethodId'    => $shipping_method->get_method_id(),
			'packages'            => $packages_data,
		);
	}

	/**
	 * Cart extension schema.
	 *
	 * @return array
	 */
	public static function extend_cart_schema() {
		return array(
			'enabled'             => array( 'description' => 'Plugin enabled for cart.', 'type' => 'boolean', 'context' => array( 'view', 'edit' ), 'readonly' => true ),
			'pickupSelectionMode' => array( 'description' => 'per-item or per-order.', 'type' => 'string', 'context' => array( 'view', 'edit' ), 'readonly' => true ),
			'appointmentsMode'    => array( 'description' => 'Appointments mode.', 'type' => 'string', 'context' => array( 'view', 'edit' ), 'readonly' => true ),
			'shippingMethodId'    => array( 'description' => 'Local pickup method ID.', 'type' => 'string', 'context' => array( 'view', 'edit' ), 'readonly' => true ),
			'packages'            => array( 'description' => 'Pickup package data.', 'type' => 'array', 'context' => array( 'view', 'edit' ), 'readonly' => true ),
		);
	}

	/**
	 * Handle extensionCartUpdate from Blocks.
	 *
	 * @param array $data Update payload.
	 */
	public static function handle_cart_update( $data ) {
		$action = isset( $data['action'] ) ? sanitize_text_field( $data['action'] ) : '';

		switch ( $action ) {
			case 'set_cart_item_handling':
				self::update_cart_item_handling( $data );
				break;
			case 'set_package_handling':
				self::update_package_handling( $data );
				break;
			case 'set_package_items_handling':
				self::update_package_items_handling( $data );
				break;
		}
	}

	/**
	 * Update cart item handling / location.
	 *
	 * @param array $data Payload.
	 */
	private static function update_cart_item_handling( $data ) {
		$cart_item_key = isset( $data['cart_item_key'] ) ? sanitize_text_field( $data['cart_item_key'] ) : '';
		$handling      = isset( $data['handling'] ) ? sanitize_text_field( $data['handling'] ) : '';
		$location_id   = isset( $data['pickup_location_id'] ) ? absint( $data['pickup_location_id'] ) : 0;

		if ( ! $cart_item_key || ! in_array( $handling, array( 'ship', 'pickup' ), true ) || ! WC()->cart || WC()->cart->is_empty() ) {
			return;
		}

		$session_data = dslpfw()->get_dslpfw_session_object()->get_cart_item_pickup_data( $cart_item_key );

		if ( 'pickup' === $handling ) {
			$session_data['handling'] = 'pickup';
			if ( $location_id > 0 ) {
				$location = dslpfw_get_pickup_location( $location_id );
				if ( $location instanceof \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location ) {
					$session_data['pickup_location_id'] = $location->get_id();
				}
			}
			dslpfw()->get_dslpfw_session_object()->set_cart_item_pickup_data( $cart_item_key, $session_data );
		} else {
			dslpfw()->get_dslpfw_session_object()->set_cart_item_pickup_data(
				$cart_item_key,
				array(
					'handling'           => 'ship',
					'pickup_location_id' => 0,
				)
			);
		}
	}

	/**
	 * Update package pickup location / appointment.
	 *
	 * @param array $data Payload.
	 */
	private static function update_package_handling( $data ) {
		$package_id         = isset( $data['package_id'] ) ? sanitize_text_field( (string) $data['package_id'] ) : '';
		$pickup_date        = isset( $data['pickup_date'] ) ? sanitize_text_field( $data['pickup_date'] ) : '';
		$location_id        = isset( $data['pickup_location_id'] ) ? absint( $data['pickup_location_id'] ) : 0;
		$appointment_offset = isset( $data['appointment_offset'] ) ? sanitize_text_field( (string) $data['appointment_offset'] ) : '';

		if ( ! array_key_exists( 'package_id', $data ) ) {
			return;
		}

		$previous_pickup_date = dslpfw()->get_dslpfw_session_object()->get_package_pickup_data( $package_id, 'pickup_date' );

		dslpfw()->get_dslpfw_session_object()->set_package_pickup_data(
			$package_id,
			array(
				'pickup_date'        => $pickup_date,
				'pickup_location_id' => $location_id,
				'appointment_offset' => $previous_pickup_date === $pickup_date ? $appointment_offset : '',
			)
		);

		$package                = dslpfw()->get_dslpfw_packages_object()->get_shipping_package( $package_id );
		$package_cart_item_keys = ! empty( $package['contents'] ) ? array_keys( $package['contents'] ) : array();

		if ( dslpfw_shipping_method()->dslpfw_is_per_order_selection_enabled() ) {
			$cart_item_keys = array_keys( dslpfw()->get_dslpfw_session_object()->get_cart_item_pickup_data() );
		} else {
			$cart_item_keys = $package_cart_item_keys;
		}

		foreach ( (array) $cart_item_keys as $cart_item_key ) {
			$session_data = dslpfw()->get_dslpfw_session_object()->get_cart_item_pickup_data( $cart_item_key );

			if ( dslpfw_shipping_method()->dslpfw_is_per_order_selection_enabled()
				&& dslpfw_shipping_method()->is_item_handling_mode( 'automatic' )
				&& in_array( $cart_item_key, $package_cart_item_keys, true ) ) {
				$session_data['handling'] = 'pickup';
			}

			$pickup_location = dslpfw_get_pickup_location( $location_id );
			if ( $pickup_location instanceof \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location ) {
				$session_data['pickup_location_id'] = $pickup_location->get_id();
			}

			$session_data['pickup_date']        = $pickup_date;
			$session_data['appointment_offset'] = $previous_pickup_date === $pickup_date ? $appointment_offset : '';

			dslpfw()->get_dslpfw_session_object()->set_cart_item_pickup_data( $cart_item_key, $session_data );
		}
	}

	/**
	 * Update package items handling when shipping method changes.
	 *
	 * @param array $data Payload.
	 */
	private static function update_package_items_handling( $data ) {
		$handling   = isset( $data['handling'] ) ? sanitize_text_field( $data['handling'] ) : '';
		$package_id = isset( $data['package_id'] ) ? sanitize_text_field( (string) $data['package_id'] ) : '';

		if ( ! in_array( $handling, array( 'pickup', 'ship' ), true ) || ! array_key_exists( 'package_id', $data ) ) {
			return;
		}

		$package                = dslpfw()->get_dslpfw_packages_object()->get_shipping_package( $package_id );
		$package_cart_item_keys = ! empty( $package['contents'] ) ? array_keys( $package['contents'] ) : array();

		foreach ( $package_cart_item_keys as $cart_item_key ) {
			$session_data             = dslpfw()->get_dslpfw_session_object()->get_cart_item_pickup_data( $cart_item_key );
			$session_data['handling'] = $handling;
			dslpfw()->get_dslpfw_session_object()->set_cart_item_pickup_data( $cart_item_key, $session_data );
		}
	}

	/**
	 * Validate pickup requirements during Store API checkout.
	 *
	 * @param \WC_Order $order Order.
	 * @throws RouteException When validation fails.
	 */
	public static function validate_checkout_order( $order ) {
		if ( ! class_exists( RouteException::class ) ) {
			return;
		}

		$shipping_method = function_exists( 'dslpfw_shipping_method' ) ? dslpfw_shipping_method() : null;
		if ( ! $shipping_method || ! $shipping_method->is_available() || ! function_exists( 'dslpfw' ) ) {
			return;
		}

		$packages       = WC()->shipping() ? WC()->shipping()->get_packages() : array();
		$chosen_methods = WC()->session ? WC()->session->get( 'chosen_shipping_methods', array() ) : array();
		$method_id      = $shipping_method->get_method_id();

		foreach ( $packages as $package_id => $package ) {
			$chosen = isset( $chosen_methods[ $package_id ] ) ? $chosen_methods[ $package_id ] : '';
			if ( $chosen !== $method_id ) {
				continue;
			}

			$session     = dslpfw()->get_dslpfw_session_object()->get_package_pickup_data( $package_id );
			$location_id = ! empty( $session['pickup_location_id'] ) ? (int) $session['pickup_location_id'] : 0;

			if ( $shipping_method->dslpfw_is_per_item_selection_enabled() ) {
				$cart_sessions = dslpfw()->get_dslpfw_session_object()->get_cart_item_pickup_data( null );
				foreach ( (array) $package['contents'] as $cart_item_key => $cart_item ) {
					$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
					if ( ! $product instanceof \WC_Product || ! dslpfw_product_can_be_picked_up( $product ) ) {
						continue;
					}
					$handling = isset( $cart_sessions[ $cart_item_key ]['handling'] ) ? $cart_sessions[ $cart_item_key ]['handling'] : 'pickup';
					if ( 'pickup' !== $handling ) {
						continue;
					}
					$item_location = ! empty( $cart_sessions[ $cart_item_key ]['pickup_location_id'] ) ? (int) $cart_sessions[ $cart_item_key ]['pickup_location_id'] : 0;
					if ( $item_location <= 0 ) {
						throw new RouteException(
							'dslpfw_missing_pickup_location',
							esc_html__( 'Please select a pickup location for each item set for pickup.', 'local-pickup-for-woocommerce' ),
							400
						);
					}
				}
			} elseif ( $location_id <= 0 ) {
				throw new RouteException(
					'dslpfw_missing_pickup_location',
					sprintf(
						/* translators: %s: shipping method title */
						esc_html__( 'Please select a pickup location if you intend to use %s as shipping method.', 'local-pickup-for-woocommerce' ),
						esc_html( $shipping_method->get_method_title() )
					),
					400
				);
			}

			if ( 'required' === $shipping_method->pickup_appointments_mode() ) {
				$pickup_date = isset( $session['pickup_date'] ) ? $session['pickup_date'] : '';
				if ( empty( $pickup_date ) ) {
					throw new RouteException(
						'dslpfw_missing_pickup_appointment',
						esc_html__( 'Please schedule a pickup appointment.', 'local-pickup-for-woocommerce' ),
						400
					);
				}
			}
		}
	}

	/**
	 * AJAX: available appointment times for a location + date (JSON).
	 *
	 * @since 1.2.1
	 */
	public static function ajax_get_appointment_times() {
		check_ajax_referer( 'dslpfw-blocks-get-appointment-times', 'security' );

		$location_id = isset( $_POST['location_id'] ) ? absint( wp_unslash( $_POST['location_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$pickup_date = isset( $_POST['pickup_date'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$location = $location_id ? dslpfw_get_pickup_location( $location_id ) : null;
		if ( ! $location || empty( $pickup_date ) ) {
			wp_send_json_error( array( 'message' => 'Invalid request' ), 400 );
		}

		try {
			$chosen_datetime = new \DateTime( $pickup_date, $location->get_address()->get_timezone() );
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ), 400 );
		}

		$available_times = $location->get_appointments()->get_available_times( $chosen_datetime );
		$minimum_hours   = $location->get_appointments()->get_schedule_minimum_hours( $chosen_datetime );
		$opening_hours   = $location->get_pickup_hours()->get_schedule( $chosen_datetime->format( 'w' ), false, $minimum_hours );
		$timezone_string = $chosen_datetime->format( 'T' );
		if ( ! empty( intval( $timezone_string ) ) ) {
			$timezone_string = $chosen_datetime->format( 'e' );
		}

		$times = array();
		if ( ! empty( $available_times ) && ! dslpfw_shipping_method()->is_anytime_appointments_enabled() ) {
			$start_of_day = ( clone $available_times[0] )->setTime( 0, 0, 0 );
			foreach ( $available_times as $datetime ) {
				$offset  = $datetime->getTimestamp() - $start_of_day->getTimestamp();
				$times[] = array(
					'offset' => (string) $offset,
					'label'  => date_i18n( wc_time_format(), $datetime->getTimestamp() + $datetime->getOffset() ),
				);
			}
		}

		wp_send_json_success(
			array(
				'dayLabel'           => date_i18n( 'l', strtotime( $pickup_date ) ),
				'timezone'           => $timezone_string,
				'times'              => $times,
				'openingHours'       => array_values( (array) $opening_hours ),
				'anytime'            => dslpfw_shipping_method()->is_anytime_appointments_enabled(),
				'minimumHoursOffset' => (string) (int) $minimum_hours,
			)
		);
	}

	/**
	 * Format a pickup location for the Store API / JS.
	 *
	 * @param \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location|null $location Location.
	 * @return array|null
	 */
	private static function format_location( $location ) {
		if ( ! $location instanceof \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location ) {
			return null;
		}

		$address = $location->get_address();

		return array(
			'id'          => (int) $location->get_id(),
			'name'        => $location->get_name(),
			'label'       => $location->get_formatted_name(),
			'addressHtml' => $address ? wp_kses_post( $address->get_formatted_html( true ) ) : '',
			'description' => $location->get_description() ? wp_kses_post( html_entity_decode( $location->get_description() ) ) : '',
			'postcode'    => $address ? $address->get_postcode() : '',
			'city'        => $address ? $address->get_city() : '',
		);
	}

	/**
	 * Eligible locations for a product.
	 *
	 * @param \WC_Product $product Product.
	 * @return array
	 */
	private static function get_locations_for_product( $product ) {
		$locations = array();
		foreach ( dslpfw()->get_dslpfw_pickup_locations_object()->get_sorted_pickup_locations() as $pickup_location ) {
			if ( dslpfw_product_can_be_picked_up( $product, $pickup_location ) ) {
				$locations[] = self::format_location( $pickup_location );
			}
		}
		return array_values( array_filter( $locations ) );
	}

	/**
	 * Eligible locations for a package.
	 *
	 * @param array $package Package.
	 * @return array
	 */
	private static function get_locations_for_package( $package ) {
		$locations = array();
		foreach ( dslpfw()->get_dslpfw_pickup_locations_object()->get_sorted_pickup_locations() as $pickup_location ) {
			if ( dslpfw_package_can_use_pickup_location( $package, $pickup_location ) ) {
				$locations[] = self::format_location( $pickup_location );
			}
		}
		return array_values( array_filter( $locations ) );
	}
}
