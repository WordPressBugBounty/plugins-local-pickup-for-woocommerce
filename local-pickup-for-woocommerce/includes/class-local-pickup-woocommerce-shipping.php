<?php

/**
 * The file that defines the local pickup shipping class
 *
 * A class definition that includes custom shipping class functionality for local pickup.
 *
 * @link        https://www.thedotstore.com/
 * @since       1.0.0
 * @author      theDotstore
 * @package     DSLPFW_Local_Pickup_Woocommerce
 * @subpackage  DSLPFW_Local_Pickup_Woocommerce/includes
 */
defined( 'ABSPATH' ) or exit;
/**
 * The Local Pickup WooCommerce shipping method class.
 *
 * Uses WooCommerce Shipping Method API to add a new shipping method.
 *
 * The core API requires to use the same class for both admin and frontend, hence there are settings and frontend functionality in the same class.
 *
 * This class tries to limit its responsibility to handle shipping method settings in both back end (along with plugin UI) and front end, where it also instantiates additional classes where it delegates actual checkout and shipping logic.
 *
 * @since 1.0.0
 */
class DSLPFW_Local_Pickup_WooCommerce_Shipping extends \WC_Shipping_Method {
    // We have list out all the properties of the class here as PHP 8+ compatibility
    public $shipping_zones;

    public $display_country;

    public $choose_locations;

    public $cart_item_handling;

    public $default_handling;

    public $locations_order;

    public $appointments_mode;

    public $appointment_duration;

    public $default_pickup_hours;

    public $default_holiday_dates;

    public $default_lead_time_count;

    public $default_lead_time_interval;

    public $default_deadline_count;

    public $default_deadline_interval;

    public $fee_adjustment;

    public $fee_adjustment_amount;

    public $fee_adjustment_type;

    public $apply_pickup_location_tax;

    public $dslpfw_prefix;

    /**
     * Initialize the Local Pickup WooCommerce shipping method class.
     *
     * @since 1.0.0
     */
    public function __construct() {
        parent::__construct();
        $this->id = \DSLPFW_Local_Pickup_Woocommerce::DSLPFW_SHIPPING_METHOD_ID;
        $this->method_title = esc_html__( 'DS Local Pickup', 'local-pickup-for-woocommerce' );
        $this->method_description = esc_html__( 'Local Pickup is a shipping method which allows customers to pick up their orders at a specified pickup location.', 'local-pickup-for-woocommerce' );
        $this->dslpfw_prefix = \DSLPFW_Local_Pickup_Woocommerce::DSLPFW_PREFIX;
        // load and init shipping method settings
        $this->dslpfw_handle_settings();
        // set Local Pickup as the default shipping method
        add_filter(
            'woocommerce_shipping_chosen_method',
            array($this, 'set_default_shipping_method'),
            1,
            3
        );
        /**
         * Local Pickup shipping method init.
         *
         * @since 1.0.0
         *
         * @param \DSLPFW_Local_Pickup_WooCommerce_Shipping $shipping_method instance of this class
         */
        do_action( 'dslpfw_wc_shipping_ds_local_pickup_init', $this );
    }

    /**
     * Handle shipping method settings.
     *
     * @since 1.0.0
     */
    private function dslpfw_handle_settings() {
        $this->form_fields = $this->get_settings_fields();
        // load the settings
        $this->init_settings();
        // init user settings
        foreach ( $this->settings as $setting_key => $setting ) {
            $this->{$setting_key} = $setting;
        }
        // save settings in admin when updated
        add_action( "woocommerce_update_options_shipping_{$this->id}", [$this, 'process_admin_options'] );
    }

    public function process_admin_options() : void {
        // process other standard options
        parent::process_admin_options();
    }

    /**
     * Get shipping method settings form fields
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function get_settings_fields() {
        $form_fields = [
            'enabled'         => [
                'title'   => esc_html__( 'Enable', 'local-pickup-for-woocommerce' ),
                'type'    => 'checkbox',
                'default' => 'no',
            ],
            'title'           => [
                'id'      => 'title',
                'title'   => esc_html__( 'Title', 'local-pickup-for-woocommerce' ),
                'type'    => 'text',
                'default' => esc_html__( 'Local Pickup', 'local-pickup-for-woocommerce' ),
            ],
            'display_country' => [
                'id'      => 'display_country',
                'title'   => esc_html__( 'Display Country', 'local-pickup-for-woocommerce' ),
                'type'    => 'multiselect',
                'default' => array(),
            ],
        ];
        /**
         * Filter Local Pickup shipping method settings fields.
         *
         * @since 1.0.0
         *
         * @param array $form_fields settings fields
         */
        return (array) apply_filters( 'wc_' . $this->id . '_settings', $form_fields );
    }

    /**
     * Get the shipping method ID.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_method_id() {
        return $this->id;
    }

    /**
     * Check whether the shipping method is available at checkout.
     *
     * @since 1.0.0
     *
     * @param array $package optional, a package as an array
     * @return bool
     */
    public function is_available( $package = array() ) {
        // WC shipping must be enabled, the shipping method must be enabled and there must be at least one pickup location published
        $is_available = wc_shipping_enabled() && $this->is_enabled();
        $display_country = $this->get_option( 'display_country', array() );
        if ( empty( $package ) ) {
            return $is_available;
        }
        if ( $is_available && !empty( $display_country ) && isset( $package['destination'] ) && isset( $package['destination']['country'] ) && !empty( $package['destination']['country'] ) && !in_array( $package['destination']['country'], $display_country, true ) ) {
            $is_available = false;
        }
        /* @see woocommerce/includes/abstracts/abstract-wc-shipping-method.php; only use $this if using WC 3.2+ */
        return (bool) apply_filters(
            "dslpfw_{$this->id}_is_available",
            $is_available,
            $package,
            $this
        );
    }

    /**
     * Get the shipping method name.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_method_title() {
        // looks for a user entered title first, defaults to parent method title which is filtered
        return $this->get_option( 'title', parent::get_method_title() );
    }

    /**
     * Returns the handling mode for cart items.
     *
     * @since 1.0.0
     *
     * @return string either 'automatic' or 'customer'
     */
    public function item_handling_mode() {
        return 'automatic';
    }

    /**
     * Checks whether an handling mode (automatic, customer) is the current handling mode for cart items.
     *
     * @since 1.0.0
     *
     * @param string $handling
     * @return bool
     */
    public function is_item_handling_mode( $handling ) {
        return $handling === $this->item_handling_mode();
    }

    /**
     * Returns the pickup selection mode.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function pickup_selection_mode() {
        return ( $this->dslpfw_is_per_item_selection_enabled() ? 'per-item' : 'per-order' );
    }

    /**
     * Gets the default pickup/shipping handling.
     *
     * @since 1.0.0
     *
     * @return string either 'pickup' or 'ship'
     */
    public function get_default_handling() {
        // For free version default we are select pickup even if others are available because of fee adjustment calculation
        return 'pickup';
    }

    /**
     * Checks if the default handling is the specified one.
     *
     * @since 1.0.0
     *
     * @param string $handling default handling to check
     * @return bool
     */
    public function is_default_handling( $handling ) {
        return $handling === $this->get_default_handling();
    }

    /**
     * Determines if per-item pickup selection is enabled.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function dslpfw_is_per_item_selection_enabled() {
        /**
         * Filters whether per-item pickup selection is enabled.
         *
         * @since 1.0.0
         *
         * @param bool $is_enabled
         */
        return (bool) apply_filters( "dslpfw_{$this->id}_per_item_selection_enabled", 'per-item' === get_option( "{$this->dslpfw_prefix}choose_locations" ) );
        // phpcs:ignore
    }

    /**
     * Determines if per-order pickup selection is enabled.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function dslpfw_is_per_order_selection_enabled() {
        return !$this->dslpfw_is_per_item_selection_enabled();
    }

    /**
     * Returns the pickup appointments mode from user's settings.
     *
     * @since 1.0.0
     *
     * @return string 
     */
    public function pickup_appointments_mode() {
        $default = 'disabled';
        $option = get_option( "{$this->dslpfw_prefix}appointments_mode", $default );
        return ( in_array( $option, array('disabled', 'enabled'), true ) ? $option : $default );
    }

    /**
     * Get the default pickup location business hours.
     *
     * This might be overridden by individual pickup locations.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_default_pickup_hours() {
        // we don't use $this->get_option() as this is a composite option handled differently
        return (array) get_option( "{$this->dslpfw_prefix}default_pickup_hours", array() );
    }

    /**
     * Get the global public holidays.
     *
     * This might be overridden by individual pickup locations.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_default_holiday_dates() {
        // we don't use $this->get_option() as this is a composite option handled differently
        return (array) get_option( "{$this->dslpfw_prefix}default_holiday_dates", array() );
    }

    /**
     * Get the global pickup deadline.
     *
     * This might be overridden by individual pickup locations.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_default_pickup_deadline() {
        // we don't use $this->get_option() as this is a composite option handled differently
        return (string) get_option( "{$this->dslpfw_prefix}default_deadline", '1 months' );
    }

    /**
     * Evaluates whether appointments could have any duration for pickup.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function is_anytime_appointments_enabled() {
        $duration = get_option( "{$this->dslpfw_prefix}appointment_duration", DAY_IN_SECONDS );
        return !is_numeric( $duration ) || $duration <= 0 || $duration >= DAY_IN_SECONDS;
    }

    /**
     * Gets the expected duration in seconds for pickup appointments.
     *
     * @since 1.0.0
     *
     * @return int a partial timestamp representing hours/minutes in seconds for a given appointment
     */
    public function dslpfw_get_appointment_duration() {
        $duration = get_option( "{$this->dslpfw_prefix}appointment_duration", DAY_IN_SECONDS );
        return ( is_numeric( $duration ) ? (int) $duration : DAY_IN_SECONDS );
    }

    /**
     * Determines whether shipping address fields should not be hidden regardless of pickup status.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function dslpfw_display_shipping_address_fields() {
        /**
         * Toggles whether to show shipping address fields even when all packages are for pickup.
         *
         * @since 1.0.0
         *
         * @param bool $dslpfw_display_shipping_address_fields default false
         */
        return (bool) apply_filters( 'dslpfw_display_shipping_address_fields', false );
    }

    /**
     * Gets the max number of appointments per slot.
     * To-Do: we will use this method to apply limit customer to use specific appointment limit
     *
     * @since 1.0.0
     *
     * @return int
     */
    public function get_default_max_customers_appointment_limits() {
        return 10;
    }

    /**
     * Get the default fee adjustment when completing a purchase with pickup.
     *
     * This might be overridden by individual pickup locations.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_default_fee_adjustment() {
        $default = '';
        $value = get_option( "{$this->dslpfw_prefix}default_fee_adjustment", $default );
        return ( is_string( $value ) || is_numeric( $value ) ? $value : $default );
    }

    /**
     * Whether applying the tax rate for the pickup location rather than the customer's given address.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function apply_pickup_location_tax() {
        return 'yes' === get_option( "{$this->dslpfw_prefix}apply_pickup_location_tax", 'no' );
        // phpcs:ignore
    }

    /**
     * Sets the default shipping method for a package.
     *
     * The filter we're hooking to runs in WooCommerce multiple times, but initially when a package has no shipping method set.
     * However, to determine the default method, WooCommerce may simply choose the first shipping method in the array of shipping rates available to a package.
     * If the default handling in Local Pickup WooCommerce is to ship a package, then we should prevent WooCommerce from selecting Local Pickup WooCommerce, and choose the next available shipping method instead.
     *
     * @see \wc_get_default_shipping_method_for_package() for WooCommerce versions >= 3.2.0
     * @see \WC_Shipping::get_default_method() for WooCommerce versions <= 3.4.6
     * @see \WC_Shipping::calculate_shipping() for WooCommerce versions <= 3.1.2
     *
     * Please note that since Local Pickup WooCommerce does not use shipping zones, it may still end up being used as the default method as WooCommerce may still try to set the first available method.
     *
     *
     * @since 1.0.0
     *
     * @param string $chosen_shipping_method the default shipping method, normally with an instance suffix
     * @param array $package_shipping_rates shipping rates available for a package
     * @param string $default_shipping_method the raw shipping method before it was filtered
     * @return string default shipping method for a package, when not user set
     */
    public function set_default_shipping_method( $chosen_shipping_method, $package_shipping_rates = array(), $default_shipping_method = '' ) {
        if ( empty( $default_shipping_method ) && $this->is_available() && $this->dslpfw_is_per_order_selection_enabled() ) {
            switch ( $this->get_default_handling() ) {
                // set to pickup if available
                case 'pickup':
                    $chosen_shipping_method = ( array_key_exists( $this->id, $package_shipping_rates ) ? $this->id : $chosen_shipping_method );
                    break;
                // try to prevent from having pickup chosen by WooCommerce later
                case 'ship':
                    if ( !empty( $package_shipping_rates ) ) {
                        foreach ( array_keys( $package_shipping_rates ) as $shipping_method_id ) {
                            if ( $this->id === $shipping_method_id ) {
                                continue;
                            }
                            $chosen_shipping_method = $shipping_method_id;
                            break;
                        }
                    }
                    if ( $this->id === $chosen_shipping_method ) {
                        $chosen_shipping_method = '';
                    }
                    break;
            }
        }
        return $chosen_shipping_method;
    }

    /**
     * Calculate shipping costs for local pickup of packages at chosen location.
     *
     * Extends parent method:
     * @see \WC_Shipping_Method::calculate_shipping()
     * @uses \WC_Shipping_Method::add_rate()
     *
     * @since 1.0.0
     *
     * @param array $package package data as associative array
     */
    public function calculate_shipping( $package = [] ) {
        global $wp_query;
        $cost = 0;
        $label = $this->get_method_title();
        $get_request_method = filter_input( INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $request_method = ( isset( $get_request_method ) ? sanitize_text_field( $get_request_method ) : '' );
        $get_query_string = filter_input( INPUT_SERVER, 'QUERY_STRING', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $query_string = ( isset( $get_query_string ) ? sanitize_text_field( $get_query_string ) : '' );
        // the action of removing an item from the customer's cart is made by a GET request with remove_item as the query string
        $is_remove_action = !empty( $request_method ) && 'GET' === $request_method && isset( $query_string ) && !empty( $query_string ) && strpos( $query_string, 'remove_item' ) !== false;
        if ( $is_remove_action || !empty( $request_method ) && 'POST' === $request_method || is_cart() || is_checkout() || $wp_query && defined( 'WC_DOING_AJAX' ) && 'update_order_review' === $wp_query->get( 'wc-ajax' ) ) {
            $pickup_location = dslpfw()->get_dslpfw_packages_object()->get_package_pickup_location( $package );
            // address a situation where the user has a saved preferred location but this is not recorded yet to session for fee adjustment calculation purposes
            if ( !$pickup_location ) {
                // verify first that pickup is possible among the listed methods
                $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
                $dslpfw_id = $this->get_method_id();
                if ( !empty( $chosen_methods ) && in_array( $dslpfw_id, $chosen_methods, false ) ) {
                    //phpcs:ignore
                    // look for a preferred pickup location
                    $pickup_location = dslpfw_get_user_default_pickup_location();
                    // then, verify that the items in package can be picked up at the default location
                    if ( $pickup_location && isset( $package['contents'] ) && is_array( $package['contents'] ) ) {
                        foreach ( $package['contents'] as $item ) {
                            $product = ( isset( $item['data'] ) ? $item['data'] : null );
                            if ( !$product || !dslpfw()->get_dslpfw_products_object()->product_can_be_picked_up( $product, $pickup_location ) ) {
                                $pickup_location = null;
                                break;
                            }
                        }
                    }
                } else {
                    // We need to show default pickup location when default handling is not pickup and we have only one pickup location published
                    $ds_local_pickup = dslpfw_shipping_method();
                    if ( !$ds_local_pickup->is_default_handling( 'pickup' ) && dslpfw()->get_dslpfw_pickup_locations_object()->get_pickup_locations_count() === 1 ) {
                        $pickup_locations = dslpfw_get_pickup_locations();
                        $pickup_location_id = current( $pickup_locations )->get_id();
                        $pickup_location = dslpfw_get_pickup_location( $pickup_location_id );
                    }
                }
            }
            $fee_adjustment = ( $pickup_location ? $pickup_location->get_fee_adjustment() : null );
            if ( $fee_adjustment ) {
                $base = ( !empty( $package['contents_cost'] ) ? $package['contents_cost'] : 0 );
                $cost = $fee_adjustment->get_relative_amount( $base );
            }
            $cost = ( !empty( $cost ) ? $cost : 0 );
            // for packages that have other methods available as well (radio buttons will be displayed),
            // we need to display the discount in the label as WooCommerce does not handle negative values in the 'cost' property of a shipping rate;
            /* translators: Placeholder: %s - local pickup discount amount */
            $discount = ( $cost < 0 ? sprintf( esc_html__( '%s (discount!)', 'local-pickup-for-woocommerce' ), wc_price( $cost ) ) : '' );
            if ( !empty( $discount ) ) {
                if ( !is_rtl() ) {
                    $label = trim( $this->get_method_title() ) . ': ' . $discount;
                } else {
                    $label = $discount . ' :' . trim( $this->get_method_title() );
                }
            }
        }
        // register the rate for this package
        $this->add_rate( array(
            'id'       => $this->get_method_id(),
            'label'    => wp_strip_all_tags( $label ),
            'cost'     => ( $cost > 0 ? $cost : 0 ),
            'taxes'    => ( $cost > 0 ? '' : false ),
            'calc_tax' => 'per_order',
        ) );
    }

}
