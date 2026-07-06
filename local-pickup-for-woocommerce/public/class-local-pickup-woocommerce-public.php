<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.thedotstore.com/
 * @since      1.0.0
 *
 * @package    DSLPFW_Local_Pickup_Woocommerce
 * @subpackage DSLPFW_Local_Pickup_Woocommerce/public
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
use DotStore\DSLPFW_Local_Pickup_Woocommerce\Fields\DSLPFW_Cart_Item_Pickup_Location_Field;
use DotStore\DSLPFW_Local_Pickup_Woocommerce\Fields\DSLPFW_Cart_Item_Handling_Toggle;
use DotStore\DSLPFW_Local_Pickup_Woocommerce\Fields\DSLPFW_Package_Pickup_Location_Field;
use DotStore\DSLPFW_Local_Pickup_Woocommerce\Fields\DSLPFW_Package_Pickup_Items_Field;
use DotStore\DSLPFW_Local_Pickup_Woocommerce\Fields\DSLPFW_Package_Pickup_Appointment_Field;
use DotStore\DSLPFW_Local_Pickup_Woocommerce\Appointments\DSLPFW_Local_Pickup_WooCommerce_Appointment;
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    DSLPFW_Local_Pickup_Woocommerce
 * @subpackage DSLPFW_Local_Pickup_Woocommerce/public
 * @author     theDotstore <support@thedotstore.com>
 */
class DSLPFW_Local_Pickup_Woocommerce_Public {
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /** 
     * To prevent duplicate HTML output in checkout form
     * 
     * @since    1.0.0
     * @access   private
     * @var array memoization helper to prevent duplicate HTML output in checkout form */
    private static $pickup_package_form_output = [];

    /**  
     * @since    1.0.0
     * @access   private
     * @var bool flag if packages have been counted yet */
    private static $dslpfw_packages_count_output = false;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        /**
         * The class responsible for defining all method for pickup location CPT
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-cart-item-pickup-data.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-package-pickup-data.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/class-pickup-location-field.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/class-cart-item-pickup-location-field.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/class-cart-item-handling-toggle.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/class-package-pickup-location-field.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/class-package-pickup-items-field.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/class-package-pickup-appointment-field.php';
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in DSLPFW_Local_Pickup_Woocommerce_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The DSLPFW_Local_Pickup_Woocommerce_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_style(
            'jquery-ui',
            plugin_dir_url( __FILE__ ) . 'css/jquery-ui.css',
            array(),
            $this->version,
            'all'
        );
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'css/local-pickup-woocommerce-public.css',
            array('jquery-ui'),
            $this->version,
            'all'
        );
        wp_enqueue_style(
            $this->plugin_name . '-datepicker',
            plugin_dir_url( __FILE__ ) . 'css/local-pickup-woocommerce-datepicker.css',
            array($this->plugin_name, 'jquery-ui'),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in DSLPFW_Local_Pickup_Woocommerce_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The DSLPFW_Local_Pickup_Woocommerce_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        $script_deps = array('jquery', 'jquery-ui-datepicker');
        if ( wp_script_is( 'selectWoo', 'registered' ) ) {
            $script_deps[] = 'selectWoo';
        }
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'js/local-pickup-woocommerce-public.js',
            $script_deps,
            $this->version,
            true
        );
        $nearby_pickup_locations_enabled = false;
        $shipping_method = dslpfw_shipping_method();
        wp_localize_script( $this->plugin_name, 'dslpfw_front_vars', array(
            'ajaxurl'                                             => admin_url( 'admin-ajax.php' ),
            'shipping_method_id'                                  => \DSLPFW_Local_Pickup_Woocommerce::DSLPFW_SHIPPING_METHOD_ID,
            'is_cart'                                             => is_cart(),
            'is_checkout'                                         => is_checkout(),
            'pickup_selection_mode'                               => $shipping_method->pickup_selection_mode(),
            'dslpfw_set_cart_item_handling_nonce'                 => wp_create_nonce( 'dslpfw-set-cart-item-handling' ),
            'dslpfw_set_package_handling_nonce'                   => wp_create_nonce( 'dslpfw-set-package-handling' ),
            'dslpfw_set_package_items_handling_nonce'             => wp_create_nonce( 'dslpfw-set-package-items-handling' ),
            'dslpfw_get_pickup_location_appointment_data_nonce'   => wp_create_nonce( 'dslpfw-get-pickup-location-appointment-data' ),
            'dslpfw_get_pickup_location_opening_hours_list_nonce' => wp_create_nonce( 'dslpfw-get-pickup-location-opening-hours-list' ),
            'dslpfw_display_shipping_address_fields'              => $shipping_method->dslpfw_display_shipping_address_fields(),
            'date_format'                                         => wc_date_format(),
            'start_of_week'                                       => get_option( 'start_of_week', 1 ),
            'datepicker_title'                                    => esc_html__( 'Choose a pickup date', 'local-pickup-for-woocommerce' ),
            'nearby_pickup_locations_enabled'                     => $nearby_pickup_locations_enabled,
        ) );
    }

    /**
     * Add the cart item key to the cart item data.
     *
     * We will need a copy of the cart key to associate pickup choices with the corresponding cart item later.
     *
     * @see dslpfw_add_cart_item_pickup_location_field_callback()
     *
     * @since 1.0.0
     */
    public function dslpfw_set_cart_item_keys() {
        if ( (is_cart() || is_checkout()) && !WC()->cart->is_empty() ) {
            $cart_contents = WC()->cart->cart_contents;
            foreach ( array_keys( WC()->cart->cart_contents ) as $cart_item_key ) {
                if ( !isset( $cart_contents[$cart_item_key]['cart_item_key'] ) ) {
                    $cart_contents[$cart_item_key]['cart_item_key'] = $cart_item_key;
                }
                dslpfw()->get_dslpfw_session_object()->set_cart_item_pickup_data( $cart_item_key, [] );
            }
            WC()->cart->cart_contents = $cart_contents;
            //On load changes of backend shows on front end side for only our custom shipping method
            $packages = WC()->cart->get_shipping_packages();
            foreach ( $packages as $package_key => $package ) {
                if ( isset( $package['ship_via'] ) && in_array( dslpfw_shipping_method_id(), $package['ship_via'], true ) ) {
                    $session_key = 'shipping_for_package_' . $package_key;
                    WC()->session->__unset( $session_key );
                }
            }
        }
    }

    /**
     * Sets the pickup handling for cart items to respect their product-level settings.
     *
     * @since 1.0.0
     *
     * @param array $item_data the product item data (e.g. used in variations)
     * @param array $cart_item the product as a cart item array
     * @return array unfiltered item data (see method description)
     */
    public function dslpfw_set_cart_item_pickup_handling( $item_data, $cart_item ) {
        if ( isset( $cart_item['cart_item_key'] ) ) {
            $product_id = ( !empty( $cart_item['product_id'] ) ? $cart_item['product_id'] : 0 );
            $product = wc_get_product( $product_id );
            if ( $product ) {
                if ( dslpfw_product_must_be_picked_up( $product ) ) {
                    $handling = 'pickup';
                } elseif ( !dslpfw_product_can_be_picked_up( $product ) ) {
                    $handling = 'ship';
                }
                // only update handling if there are product restrictions
                if ( !empty( $handling ) ) {
                    $pickup_data = dslpfw()->get_dslpfw_session_object()->get_cart_item_pickup_data( $cart_item['cart_item_key'] );
                    // only update handling if it is different than the current value
                    if ( empty( $pickup_data['handling'] ) || $handling !== $pickup_data['handling'] ) {
                        $pickup_data['handling'] = $handling;
                        dslpfw()->get_dslpfw_session_object()->set_cart_item_pickup_data( $cart_item['cart_item_key'], $pickup_data );
                    }
                }
            }
        }
        return $item_data;
    }

    /**
     * Render the pickup location selection box on the cart summary.
     *
     * This callback is performed as an action rather than a filter to echo some content.
     *
     * @see Cart::set_cart_item_keys()
     * @see Checkout::add_checkout_item_pickup_location_field()
     *
     * @since 1.0.0
     *
     * @param array $item_data the product item data (e.g. used in variations)
     * @param array $cart_item the product as a cart item array
     *
     * @return array unfiltered item data (see method description)
     */
    public function dslpfw_add_cart_item_pickup_location_field_callback( $item_data, $cart_item ) {
        if ( isset( $cart_item['cart_item_key'] ) && in_the_loop() && is_cart() ) {
            $ds_local_pickup = dslpfw_shipping_method();
            WC()->session->set( 'shipping_name', sanitize_text_field( $ds_local_pickup->get_method_title() ) );
            if ( $ds_local_pickup->is_available() ) {
                // Show dropdown of pickup locations
                $product_field = new DSLPFW_Cart_Item_Pickup_Location_Field($cart_item['cart_item_key']);
                echo wp_kses( $product_field->get_html(), dslpfw()->dslpfw_allowed_html_tags() );
                // Show dropdown of pickup locations
                $toggle_dropdown = new DSLPFW_Cart_Item_Handling_Toggle($cart_item['cart_item_key']);
                echo wp_kses( $toggle_dropdown->get_html(), dslpfw()->dslpfw_allowed_html_tags() );
            }
        }
        return $item_data;
    }

    /**
     * Set a cart item for shipping or pickup, along with pickup data
     *
     * @since 1.0.0
     */
    public function set_cart_item_handling_callback() {
        check_ajax_referer( 'dslpfw-set-cart-item-handling', 'security' );
        $get_cart_item_key = filter_input( INPUT_POST, 'cart_item_key', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $cart_item_key = ( isset( $get_cart_item_key ) ? sanitize_text_field( $get_cart_item_key ) : '' );
        $get_pickup_data = filter_input(
            INPUT_POST,
            'pickup_data',
            FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            FILTER_REQUIRE_ARRAY
        );
        $pickup_data = ( !empty( $get_pickup_data ) ? array_map( 'sanitize_text_field', wp_unslash( $get_pickup_data ) ) : '' );
        if ( isset( $pickup_data['handling'] ) && in_array( $pickup_data['handling'], array('ship', 'pickup'), true ) && !WC()->cart->is_empty() && !empty( $cart_item_key ) ) {
            $session_data = dslpfw()->get_dslpfw_session_object()->get_cart_item_pickup_data( $cart_item_key );
            // designate item for pickup
            if ( 'pickup' === $pickup_data['handling'] ) {
                $session_data['handling'] = 'pickup';
                if ( !empty( $pickup_data['pickup_location_id'] ) ) {
                    $pickup_location = dslpfw_get_pickup_location( $pickup_data['pickup_location_id'] );
                    if ( $pickup_location instanceof \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location ) {
                        $session_data['pickup_location_id'] = $pickup_location->get_id();
                    }
                }
                dslpfw()->get_dslpfw_session_object()->set_cart_item_pickup_data( $cart_item_key, $session_data );
                // remove any pickup information previously set
            } elseif ( 'ship' === $pickup_data['handling'] ) {
                dslpfw()->get_dslpfw_session_object()->set_cart_item_pickup_data( $cart_item_key, array(
                    'handling'           => 'ship',
                    'pickup_location_id' => 0,
                ) );
            }
            wp_send_json_success();
        }
    }

    /**
     * Set a package pickup data, when meant for pickup.
     *
     * @since 1.0.0
     */
    public function set_package_handling_callback() {
        check_ajax_referer( 'dslpfw-set-package-handling', 'security' );
        $get_package_id = filter_input( INPUT_POST, 'package_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $package_id = ( isset( $get_package_id ) ? sanitize_text_field( $get_package_id ) : '' );
        $get_pickup_date = filter_input( INPUT_POST, 'pickup_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $pickup_date = ( isset( $get_pickup_date ) ? sanitize_text_field( $get_pickup_date ) : '' );
        $get_pickup_location_id = filter_input( INPUT_POST, 'pickup_location_id', FILTER_SANITIZE_NUMBER_INT );
        $pickup_location_id = ( !empty( $get_pickup_location_id ) ? intval( $get_pickup_location_id ) : 0 );
        $get_appointment_offset = filter_input( INPUT_POST, 'appointment_offset', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $appointment_offset = ( isset( $get_appointment_offset ) ? sanitize_text_field( $get_appointment_offset ) : '' );
        if ( is_numeric( $package_id ) || is_string( $package_id ) && '' !== $package_id ) {
            $previous_pickup_date = dslpfw()->get_dslpfw_session_object()->get_package_pickup_data( $package_id, 'pickup_date' );
            dslpfw()->get_dslpfw_session_object()->set_package_pickup_data( $package_id, [
                'pickup_date'        => $pickup_date,
                'pickup_location_id' => (int) $pickup_location_id,
                'appointment_offset' => ( $previous_pickup_date === $pickup_date ? $appointment_offset : '' ),
            ] );
            $package = dslpfw()->get_dslpfw_packages_object()->get_shipping_package( $package_id );
            $package_cart_item_keys = ( !empty( $package ) ? array_keys( $package['contents'] ) : [] );
            if ( dslpfw_shipping_method()->dslpfw_is_per_order_selection_enabled() ) {
                // if per-item selection is disabled, set all items to this package's location ID
                $cart_item_keys = array_keys( dslpfw()->get_dslpfw_session_object()->get_cart_item_pickup_data() );
            } else {
                // otherwise, set package pickup data to all items in the same package
                $cart_item_keys = $package_cart_item_keys;
            }
            if ( !empty( $cart_item_keys ) ) {
                foreach ( $cart_item_keys as $cart_item_key ) {
                    $session_data = dslpfw()->get_dslpfw_session_object()->get_cart_item_pickup_data( $cart_item_key );
                    // set cart item handling for items in this package
                    if ( dslpfw_shipping_method()->dslpfw_is_per_order_selection_enabled() && dslpfw_shipping_method()->is_item_handling_mode( 'automatic' ) && in_array( $cart_item_key, $package_cart_item_keys, true ) ) {
                        $session_data['handling'] = 'pickup';
                    }
                    $pickup_location = dslpfw_get_pickup_location( $pickup_location_id );
                    if ( $pickup_location instanceof \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location ) {
                        $session_data['pickup_location_id'] = $pickup_location->get_id();
                    }
                    $session_data['pickup_date'] = $pickup_date;
                    $session_data['appointment_offset'] = $appointment_offset;
                    dslpfw()->get_dslpfw_session_object()->set_cart_item_pickup_data( $cart_item_key, $session_data );
                }
            }
            wp_send_json_success();
        }
        wp_send_json_error();
    }

    /**
     * Sets the handling for items in a package when the shipping method is changed.
     *
     * @since 1.0.0
     */
    public function set_package_items_handling_callback() {
        check_ajax_referer( 'dslpfw-set-package-items-handling', 'security' );
        $get_handling = filter_input( INPUT_POST, 'handling', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $handling = ( isset( $get_handling ) ? sanitize_text_field( $get_handling ) : '' );
        $get_package_id = filter_input( INPUT_POST, 'package_id', FILTER_SANITIZE_NUMBER_INT );
        $package_id = ( !empty( $get_package_id ) ? intval( $get_package_id ) : 0 );
        if ( (is_numeric( $package_id ) || is_string( $package_id ) && '' !== $package_id) && in_array( $handling, ['pickup', 'ship'], true ) ) {
            $package = dslpfw()->get_dslpfw_packages_object()->get_shipping_package( $package_id );
            $package_cart_item_keys = ( !empty( $package ) ? array_keys( $package['contents'] ) : [] );
            if ( !empty( $package_cart_item_keys ) ) {
                foreach ( $package_cart_item_keys as $cart_item_key ) {
                    $session_data = dslpfw()->get_dslpfw_session_object()->get_cart_item_pickup_data( $cart_item_key );
                    // set cart item handling for items in this package
                    $session_data['handling'] = $handling;
                    dslpfw()->get_dslpfw_session_object()->set_cart_item_pickup_data( $cart_item_key, $session_data );
                }
            }
        }
        wp_send_json_success();
    }

    /**
     * Perhaps disables the cart page shipping calculator by toggling a WordPress option value.
     *
     * If in the cart totals there is only one package and is meant for pickup, we don't need the shipping calculator.
     *
     * @since 1.0.0
     *
     * @param string $default_setting the option default setting
     * @return string 'yes' or 'no'
     */
    public function dslpfw_disable_shipping_calculator( $default_setting ) {
        if ( 'no' !== $default_setting && is_cart() && !is_admin() ) {
            $packages = WC()->cart->get_shipping_packages();
            $package = ( count( $packages ) > 0 ? current( $packages ) : [] );
            if ( isset( $package['ship_via'][0] ) && $package['ship_via'][0] === dslpfw_shipping_method_id() ) {
                $default_setting = 'no';
            }
        }
        return $default_setting;
    }

    /**
     * Outputs the pickup location information and appointments box next to pickup packages in checkout form.
     *
     * @since 1.0.0
     *
     * @param string|\WC_Shipping_Rate $shipping_rate the chosen shipping method for the package
     * @param int|string $package_index the current package index
     */
    public function dslpfw_output_pickup_package_form( $shipping_rate, $package_index ) {
        $local_pickup_method = dslpfw_shipping_method();
        $lp_method_id = ( $local_pickup_method && $local_pickup_method->is_available() ? $local_pickup_method->get_method_id() : null );
        $package = dslpfw()->get_dslpfw_packages_object()->get_shipping_package( $package_index );
        $is_lp = $shipping_rate === $lp_method_id || $shipping_rate instanceof \WC_Shipping_Rate && $shipping_rate->method_id === $lp_method_id;
        $is_only_available_shipping_method = true;
        $package_contains_must_pick_up_products_only = true;
        if ( $is_lp && !array_key_exists( $package_index, self::$pickup_package_form_output ) ) {
            // record that the current package has been evaluated for the current thread
            self::$pickup_package_form_output[$package_index] = true;
            if ( $this->should_output_pickup_form( $package_index, $package ) ) {
                $pickup_location_field = new DSLPFW_Package_Pickup_Location_Field($package_index);
                echo wp_kses( $pickup_location_field->get_html(), dslpfw()->dslpfw_allowed_html_tags() );
                $appointment_field = new DSLPFW_Package_Pickup_Appointment_Field($package_index);
                echo wp_kses( $appointment_field->get_html(), dslpfw()->dslpfw_allowed_html_tags() );
                $pickup_items_field = new DSLPFW_Package_Pickup_Items_Field($package_index);
                echo wp_kses( $pickup_items_field->get_html(), dslpfw()->dslpfw_allowed_html_tags() );
            }
        }
        //Checks if Local Pickup WooCommerce is the only available shipping option.
        if ( !dslpfw_shipping_method()->is_enabled() ) {
            $is_only_available_shipping_method = false;
        }
        foreach ( \WC_Shipping_Zones::get_zones() as $zone ) {
            foreach ( $zone['shipping_methods'] as $shipping_method ) {
                // a single zone with a shipping method is enough to break and return false
                if ( method_exists( $shipping_method, 'is_enabled' ) && $shipping_method->is_enabled() ) {
                    $is_only_available_shipping_method = false;
                }
            }
        }
        //Checks whether the package contains must be picked up products only.
        if ( isset( $package['contents'] ) && is_array( $package['contents'] ) ) {
            foreach ( $package['contents'] as $item ) {
                // a single item that must not be picked up is enough to determine that the full package must not be picked up
                if ( isset( $item['data'] ) && $item['data'] instanceof \WC_Product && !dslpfw_product_must_be_picked_up( $item['data'] ) ) {
                    $package_contains_must_pick_up_products_only = false;
                }
            }
        }
        if ( !empty( $package['rates'] ) && 1 === count( $package['rates'] ) && $lp_method_id === key( $package['rates'] ) && !WC()->customer->has_calculated_shipping() && !$is_only_available_shipping_method && !$package_contains_must_pick_up_products_only ) {
            sprintf( '<p>%s</p>', esc_html__( 'Enter your address to see all available shipping options.', 'local-pickup-for-woocommerce' ) );
        }
    }

    /**
     * Determines if the pickup form should be output.
     *
     * @see Checkout::output_pickup_package_form()
     *
     * @since 1.0.0
     *
     * @param int|string $package_index package index
     * @param array $package package data
     * @return bool
     */
    private function should_output_pickup_form( $package_index, $package ) {
        $do_output = false;
        $chosen_methods = WC()->session->get( 'chosen_shipping_methods', [] );
        $available_methods = ( !empty( $package['rates'] ) ? count( $package['rates'] ) : 0 );
        // 1. The current package has a selected method matching Local Pickup WooCommerce stored in session:
        if ( isset( $chosen_methods[$package_index] ) && $chosen_methods[$package_index] === dslpfw_shipping_method_id() ) {
            $do_output = true;
            // 2. There is only one shipping method available for the current package and it matches Local Pickup WooCommerce:
        } elseif ( 1 === $available_methods && dslpfw_shipping_method_id() === key( $package['rates'] ) ) {
            $do_output = true;
            // 3. There's at least an item that mandates pickup but no items that require shipping (there shouldn't be other options than Local Pickup WooCommerce to choose from):
        } elseif ( dslpfw()->get_dslpfw_packages_object()->is_pickup_required() && !dslpfw()->get_dslpfw_packages_object()->are_shipping_and_pickup_required() ) {
            $do_output = true;
            // 4. Shipping costs are hidden until an address is entered and one pickup location per order is enabled:
        } elseif ( 'yes' === get_option( 'woocommerce_shipping_cost_requires_address' ) && dslpfw_shipping_method()->dslpfw_is_per_order_selection_enabled() ) {
            $do_output = 0 === get_current_user_id() && !WC()->customer->has_calculated_shipping() || 1 === $available_methods && dslpfw()->get_dslpfw_packages_object()->package_can_be_picked_up( $package );
            // however, if there is more than one shipping method available, and default is to ship, do not show the location fields just yet
            if ( $do_output && $available_methods > 1 && dslpfw_shipping_method()->is_default_handling( 'ship' ) ) {
                $do_output = false;
            }
        }
        return $do_output;
    }

    /**
     * Workaround for a WC glitch which might display item details in wrong places while doing AJAX.
     *
     * @since 1.0.0
     *
     * @param array $item_details items in package meant for the current shipment
     * @param array $package the current package array object
     * @return array
     */
    public function dslpfw_hide_pickup_package_item_details( $item_details, $package ) {
        if ( !empty( $package['pickup_location_id'] ) || isset( $package['ship_via'] ) && dslpfw_shipping_method_id() ) {
            $item_details = [];
        }
        return $item_details;
    }

    /**
     * Add a flag to mark the total number of packages meant for shipping, and the total number of packages meant for pickup.
     *
     * This can be useful to JS scripts that need to quickly grab the count, for example to toggle the visibility of the shipping address fields.
     *
     * @since 1.0.0
     */
    public function dslpfw_packages_count() {
        if ( true !== self::$dslpfw_packages_count_output && is_checkout() ) {
            $packages = WC()->shipping()->get_packages();
            $shipping_method_id = dslpfw_shipping_method_id();
            $packages_to_ship = 0;
            $packages_to_pickup = 0;
            if ( $packages ) {
                foreach ( $packages as $package ) {
                    if ( isset( $package['ship_via'] ) && in_array( $shipping_method_id, $package['ship_via'], true ) ) {
                        $packages_to_pickup++;
                    } else {
                        $packages_to_ship++;
                    }
                }
            }
            ?>
            <tr>
                <td>&nbsp;</td>
                <td>
                    <input type="hidden" id="dslpfw-packages-to-ship" value="<?php 
            echo esc_attr( $packages_to_ship );
            ?>" />
                    <input type="hidden" id="dslpfw-packages-to-pickup" value="<?php 
            echo esc_attr( $packages_to_pickup );
            ?>" />
                </td>
            </tr>
            <?php 
            self::$dslpfw_packages_count_output = true;
        }
    }

    /**
     * Render the pickup location selection box on the checkout items summary.
     *
     * @see Cart::add_cart_item_pickup_location_field()
     *
     * @since 1.0.0
     *
     * @param string $product_qty_html HTML intended to output the item quantity to be ordered
     * @param array $cart_item the cart item object as array
     * @param string $cart_item_key the cart item identifier
     * @return string HTML
     */
    public function dslpfw_add_checkout_item_pickup_location_field_callback( $product_qty_html, $cart_item, $cart_item_key ) {
        if ( is_checkout() ) {
            $ds_local_pickup = dslpfw_shipping_method();
            if ( $ds_local_pickup->is_available() ) {
                $product_field = new DSLPFW_Cart_Item_Pickup_Location_Field($cart_item_key);
                $product_qty_html .= $product_field->get_html();
                $handling_toggle = new DSLPFW_Cart_Item_Handling_Toggle($cart_item_key);
                $product_qty_html .= $handling_toggle->get_html();
            }
        }
        return $product_qty_html;
    }

    /**
     * Adds template variables to the packages array:
     * - tags to the first and last packages of each handling type (Shipping or Local Pickup) to group them in the UI
     * - total cost of all pickup-only packages to the first pickup-only package
     *
     * @since 1.0.0
     *
     * @param array $packages shipping packages array
     * @return array
     */
    public function dslpfw_add_template_variables_to_packages( $packages ) {
        $pickup_only_packages = [];
        $pickup_packages = [];
        $shipping_packages = [];
        $pickup_only_packages_total_cost = 0;
        $pickup_only_packages_total_discount = 0;
        $ds_local_pickup = dslpfw_shipping_method_id();
        foreach ( $packages as $key => $package ) {
            $chosen_shipping_method = wc_get_chosen_shipping_method_for_package( $key, $package );
            // shipping
            if ( $chosen_shipping_method !== $ds_local_pickup ) {
                $shipping_packages[] = $package;
                // pickup-only
            } elseif ( !empty( $package['rates'] ) && 1 === count( $package['rates'] ) && $ds_local_pickup === key( $package['rates'] ) ) {
                $pickup_only_packages[] = $package;
                $shipping_method = $package['rates'][$ds_local_pickup];
                $pickup_only_packages_total_cost += $shipping_method->cost;
                // get discount (not persisted in the shipping method)
                if ( empty( (float) $shipping_method->cost ) ) {
                    $pickup_location = dslpfw()->get_dslpfw_packages_object()->get_package_pickup_location( $package );
                    $fee_adjustment = ( $pickup_location ? $pickup_location->get_fee_adjustment() : null );
                    if ( $fee_adjustment ) {
                        $base = ( !empty( $package['contents_cost'] ) ? $package['contents_cost'] : 0 );
                        $cost = $fee_adjustment->get_relative_amount( $base );
                        if ( $cost < 0 ) {
                            $pickup_only_packages_total_discount += $cost;
                        }
                    }
                }
                // pickup or ship
            } else {
                $pickup_packages[] = $package;
            }
        }
        if ( !empty( $pickup_only_packages ) ) {
            $pickup_only_packages[0]['dslpfw_total_only_cost'] = $pickup_only_packages_total_cost + $pickup_only_packages_total_discount;
        }
        $pickup_packages = array_merge( $pickup_only_packages, $pickup_packages );
        if ( !empty( $pickup_packages ) ) {
            $pickup_packages[0]['dslpfw_pickup_package_start'] = true;
            $pickup_packages[count( $pickup_packages ) - 1]['dslpfw_pickup_package_end'] = true;
        }
        if ( !empty( $shipping_packages ) ) {
            $shipping_packages[0]['dslpfw_shipping_package_start'] = true;
            $shipping_packages[count( $shipping_packages ) - 1]['dslpfw_shipping_package_end'] = true;
        }
        return array_merge( $pickup_packages, $shipping_packages );
    }

    public function dslpfw_group_method_label_callback( $label, $package_id, $package ) {
        if ( !isset( $package['rates'] ) || empty( $package['rates'] ) ) {
            return $label;
        }
        $chosen_shipping_method = wc_get_chosen_shipping_method_for_package( $package_id, $package );
        $ds_local_pickup = dslpfw_shipping_method_id();
        if ( $chosen_shipping_method === $ds_local_pickup ) {
            if ( !is_rtl() ) {
                /* translators: Placeholders: %1$s - shipping method name, %2$d package count number */
                $label = ( $package_id + 1 > 1 ? sprintf( _x( '%1$s %2$d', 'shipping packages', 'local-pickup-for-woocommerce' ), trim( dslpfw_shipping_method()->get_method_title() ), $package_id + 1 ) : trim( dslpfw_shipping_method()->get_method_title() ) );
            } else {
                $label = trim( dslpfw_shipping_method()->get_method_title() );
            }
        }
        return $label;
    }

    /**
     * Sends all the necessary pickup location data to schedule an appointment.
     *
     * The data is sent to jQuery DatePicker to build the front-end pickup appointment calendar.
     *
     * @since 1.0.0
     */
    public function get_pickup_location_appointment_data_callback() {
        check_ajax_referer( 'dslpfw-get-pickup-location-appointment-data', 'security' );
        $get_location_id = filter_input( INPUT_POST, 'location_id', FILTER_SANITIZE_NUMBER_INT );
        $location_id = ( !empty( $get_location_id ) ? intval( $get_location_id ) : null );
        if ( $location_id ) {
            $location = dslpfw_get_pickup_location( $location_id );
            if ( $location && 'publish' === $location->get_post()->post_status ) {
                try {
                    // local time now is from when we start building our calendar with available dates
                    $start_time = new \DateTime('now', $location->get_address()->get_timezone());
                } catch ( \Exception $e ) {
                    wp_send_json_error( [
                        'message' => sprintf( 'Error instantiating DateTime: %1$s', $e->getMessage() ),
                    ] );
                    wp_die();
                }
                // the optional lead time is used to offset the first available date by some days
                $first_pickup_time = $location->get_appointments()->get_first_available_pickup_time( $start_time );
                $first_pickup_date = ( clone $first_pickup_time )->setTime( 0, 0, 0 );
                // if no deadline is specified, we'll build a year-long calendar
                $pickup_days = apply_filters( 'dslpfw_frontend_deadline_max_limit', 365 );
                // the iteration date will be relative to the start time (adjusted by lead time offset) as long as there's deadline left
                $iteration_date = clone $first_pickup_date;
                // variables used in the while loop below to compile available and unavailable days in calendar
                $available_days = 0;
                $unavailable_days = 0;
                $unavailable_dates = [];
                // the iteration date is progressively bumped ahead until there is a sufficient amount of days available for pickup (or a reasonable limit is met at one year length);
                // simultaneously, the unavailable dates are collected: these will be passed to JS to black out specific dates (public holidays, days without opening hours)
                do {
                    if ( $iteration_date->format( 'Y-m-d' ) === $first_pickup_date->format( 'Y-m-d' ) ) {
                        $minimum_hours = $location->get_appointments()->get_schedule_minimum_hours( $iteration_date );
                        // if anytime appointments are enabled, set calendar day to the first pickup time so that
                        // the day can be considered as a day that has available times in calendar_day_has_available_times()
                        $calendar_day = ( dslpfw_shipping_method()->is_anytime_appointments_enabled() ? clone $first_pickup_time : clone $iteration_date );
                    } else {
                        $minimum_hours = null;
                        $calendar_day = clone $iteration_date;
                    }
                    $has_available_times = false;
                    if ( dslpfw_shipping_method()->is_anytime_appointments_enabled() ) {
                        $end_date = clone $calendar_day;
                        $has_available_times = dslpfw()->get_dslpfw_appointments_object()->is_appointment_time_available(
                            $start_time,
                            $location,
                            null,
                            // appointment duration is not used when anytime appointments are enabled
                            $calendar_day,
                            $end_date->setTime( 23, 59, 59 )
                        );
                    } else {
                        $has_available_times = !empty( $location->get_appointments()->get_available_times( $calendar_day ) );
                    }
                    $is_public_holiday = false;
                    if ( $has_available_times && !$is_public_holiday && $location->get_pickup_hours()->has_schedule( $iteration_date->format( 'w' ) ) && !empty( $location->get_pickup_hours()->get_schedule( $iteration_date->format( 'w' ), false, $minimum_hours ) ) ) {
                        $available_days++;
                    } else {
                        $unavailable_dates[] = $iteration_date->format( 'Y-m-d' );
                        $unavailable_days++;
                    }
                    $total_days = $unavailable_days + $available_days;
                    $iteration_date->add( new \DateInterval('P1D') );
                } while ( $total_days < $pickup_days );
                // we cut these additional dates because:
                // - the final iteration date, because it's bumped one day ahead than it should at the end of the previous while loop
                $unavailable_dates[] = $iteration_date->format( 'Y-m-d' );
                // - the day after the final iteration date to rule out any rare glitch that could make an unavailable date selectable
                $unavailable_dates[] = ( clone $iteration_date )->add( new \DateInterval('P1D') )->format( 'Y-m-d' );
                // - the day before the start date to rule out any rare glitch that could make a day in the past available
                $unavailable_dates[] = ( clone $first_pickup_date )->sub( new \DateInterval('P1D') )->format( 'Y-m-d' );
                usort( $unavailable_dates, [$this, 'sort_calendar_dates'] );
                $return_data = [
                    'address'             => ( !empty( trim( $location->get_description() ) ) ? wp_kses_post( $location->get_address()->get_formatted_html( true ) . "\n" . '<br />' . "\n" . $location->get_description() ) : $location->get_address()->get_formatted_html( true ) ),
                    'calendar_start'      => $first_pickup_date->getTimestamp(),
                    'calendar_end'        => $iteration_date->getTimestamp(),
                    'unavailable_dates'   => array_unique( $unavailable_dates ),
                    'default_date'        => $first_pickup_date->getTimestamp(),
                    'auto_select_default' => 1 === $available_days && 'required' === dslpfw_shipping_method()->pickup_appointments_mode(),
                ];
                wp_send_json_success( $return_data );
            }
        }
        wp_send_json_error();
    }

    /**
     * Sorts calendar dates (`usort` callback helper method).
     *
     * @see get_pickup_location_appointment_data_callbacka()
     *
     * @since 1.0.0
     *
     * @param string $date_a first date to compare
     * @param string $date_b second date to compare
     * @return int
     */
    private function sort_calendar_dates( $date_a, $date_b ) {
        return (int) strtotime( $date_a ) - (int) strtotime( $date_b );
    }

    /**
     * Get a list of opening hours for any given day of the week.
     *
     * @since 1.0.0
     */
    public function get_pickup_location_opening_hours_list_callback() {
        check_ajax_referer( 'dslpfw-get-pickup-location-opening-hours-list', 'security' );
        $get_location = filter_input( INPUT_POST, 'location', FILTER_SANITIZE_NUMBER_INT );
        $location_id = ( isset( $get_location ) ? intval( $get_location ) : null );
        $get_package_id = filter_input( INPUT_POST, 'package_id', FILTER_SANITIZE_NUMBER_INT );
        $package_id = ( !empty( $get_package_id ) ? intval( $get_package_id ) : 0 );
        $get_pickup_date = filter_input( INPUT_POST, 'pickup_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $pickup_date = ( isset( $get_pickup_date ) ? sanitize_text_field( $get_pickup_date ) : '' );
        if ( $location_id ) {
            $list = '';
            $location = dslpfw_get_pickup_location( $location_id );
            if ( $location && $pickup_date ) {
                try {
                    $chosen_datetime = new \DateTime($pickup_date, $location->get_address()->get_timezone());
                } catch ( \Exception $e ) {
                    return '';
                }
                $day = gmdate( 'w', strtotime( $pickup_date ) );
                // get day of week from date (0-6, starting from sunday)
                $minimum_hours = $location->get_appointments()->get_schedule_minimum_hours( $chosen_datetime );
                $opening_hours = $location->get_pickup_hours()->get_schedule( $day, false, $minimum_hours );
                if ( $opening_hours ) {
                    ob_start();
                    ?>
        
                    <?php 
                    if ( !empty( $opening_hours ) ) {
                        ?>
        
                        <small class="pickup-location-field-label"><?php 
                        /* translators: Placeholder: %s - day of the week name */
                        printf( esc_html__( 'Opening hours for pickup on %s:', 'local-pickup-for-woocommerce' ), '<strong>' . esc_html( date_i18n( 'l', strtotime( $pickup_date ) ) ) . '</strong>' );
                        ?></small>
                        <ul>
                            <?php 
                        foreach ( $opening_hours as $time_string ) {
                            ?>
                                <li><small><?php 
                            echo esc_html( $time_string );
                            ?></small></li>
                            <?php 
                        }
                        ?>
                        </ul>
                        <input type="hidden" name="_shipping_method_pickup_appointment_offset[<?php 
                        echo esc_attr( $package_id );
                        ?>]" value="<?php 
                        echo esc_attr( (int) $minimum_hours );
                        ?>" />
        
                    <?php 
                    }
                    ?>
        
                    <?php 
                    $list .= ob_get_clean();
                }
            }
            if ( !empty( $list ) ) {
                wp_send_json_success( $list );
            } else {
                wp_send_json_error();
            }
        }
    }

    /**
     * Calculate any pickup location discounts when doing cart totals.
     *
     * @since 1.0.0
     */
    public function dslpfw_apply_pickup_fee_discount() {
        $cart = WC()->cart;
        $ds_local_pickup = dslpfw_shipping_method();
        if ( $cart->cart_contents_total > 0 && !$cart->is_empty() && $ds_local_pickup->is_available() ) {
            $packages = WC()->shipping()->get_packages();
            $total_discount = 0;
            foreach ( $packages as $package_key => $package ) {
                $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
                $ds_local_pickup_id = $ds_local_pickup->get_method_id();
                // skip packages not set for pickup
                if ( !isset( $chosen_methods[$package_key] ) || $chosen_methods[$package_key] !== $ds_local_pickup_id ) {
                    continue;
                }
                $chosen_location = dslpfw()->get_dslpfw_packages_object()->get_package_pickup_location( $package );
                // address a situation where the user has a saved preferred location but this is not recorded yet to session for price adjustment calculation purposes
                if ( !$chosen_location && isset( $package['rates'] ) && array_key_exists( $ds_local_pickup_id, $package['rates'] ) ) {
                    $chosen_location = dslpfw_get_user_default_pickup_location();
                    // sanity check: the default location cannot be used if item cannot be picked up at preferred location
                    if ( $chosen_location && isset( $package['contents'] ) && is_array( $package['contents'] ) ) {
                        foreach ( $package['contents'] as $item ) {
                            $product = ( isset( $item['data'] ) ? $item['data'] : null );
                            if ( !$product || !dslpfw()->get_dslpfw_products_object()->product_can_be_picked_up( $product, $chosen_location ) ) {
                                $chosen_location = null;
                                break;
                            }
                        }
                    }
                }
                if ( $chosen_location && isset( $package['contents_cost'] ) && $package['contents_cost'] > 0 ) {
                    $package_costs = $package['contents_cost'];
                    $price_adjustment = $chosen_location->get_fee_adjustment();
                    if ( $price_adjustment && $price_adjustment->is_discount() ) {
                        $discount_amount = $price_adjustment->get_amount( true );
                        // if the discount is a percentage, then calculate over the package contents
                        if ( $price_adjustment->is_percentage() ) {
                            $discount_amount = $price_adjustment->get_relative_amount( $package_costs, true );
                        }
                        $total_discount += ( $discount_amount > 0 ? $discount_amount : 0 );
                    }
                }
            }
            if ( $total_discount > 0 ) {
                // sanity check: the total discount shouldn't amount to more than the total cart costs, although WooCommerce wouldn't let a new order to have a negative total
                $total_discount = ( $total_discount >= $cart->cart_contents_total ? $cart->cart_contents_total : $total_discount );
                // normalize discount as fee amount (necessary in case of tax inclusive shops)
                $total_discount = $this->get_pickup_discount_fee_amount( $total_discount );
                WC()->cart->add_fee( 
                    /* translators: Placeholder: %s - shipping method title (e.g. Local Pickup) */
                    sprintf( esc_html__( '%s discount', 'local-pickup-for-woocommerce' ), $ds_local_pickup->get_method_title() ),
                    "-{$total_discount}",
                    false
                 );
            }
        }
    }

    /**
     * Normalizes a pickup discount amount as a fee amount to apply to the cart.
     *
     * If the prices are tax inclusive and we're displaying prices including taxes, we need some special handling, otherwise the discount will include these while applying the fee below.
     * The trick is to pass to WooCommerce a number that will be turned into the desired discount after tax is applied to it.
     * For this purpose, we will consider our discount fee tax inclusive and apply reverse taxes to remove it from the discount amount.
     *
     * @since 1.0.0
     *
     * @param int|float $pickup_discount discount amount
     * @return int|float the same discount amount, which may be altered in case of tax inclusive settings
     */
    private function get_pickup_discount_fee_amount( $pickup_discount ) {
        if ( wc_prices_include_tax() && 'incl' === get_option( 'woocommerce_tax_display_cart' ) ) {
            $cart_content_taxes = WC()->cart->get_cart_contents_taxes();
            if ( !empty( $cart_content_taxes ) ) {
                $tax_rates = [];
                $compound = false;
                $percentage = 0;
                foreach ( array_keys( $cart_content_taxes ) as $tax_id ) {
                    $tax_rate = \WC_Tax::_get_tax_rate( $tax_id );
                    /** normalize taxes as expected by {@see \WC_Tax::calc_inclusive_tax()} */
                    foreach ( (array) $tax_rate as $key => $data ) {
                        if ( 'tax_rate' === $key ) {
                            $key = 'rate';
                        } else {
                            $key = str_replace( 'tax_rate_', '', $key );
                        }
                        if ( in_array( $key, ['compound', 'shipping'], true ) ) {
                            $value = wc_bool_to_string( $data );
                            // set a flag so we know there are compound rates used
                            if ( !$compound && 'compound' === $key && 'yes' === $value ) {
                                $compound = true;
                            }
                        } else {
                            $value = $data;
                            if ( 'rate' === $key && $value && (float) $value > 0 ) {
                                $percentage += (float) $value / 100;
                            }
                        }
                        $tax_rates[$tax_id][$key] = $value;
                    }
                }
                // when compound taxes are found, we use WooCommerce internals to calculate the taxes out of the tax-inclusive discount amount
                if ( $compound ) {
                    // we need to apply a filter momentarily to avoid some rounding errors
                    add_filter(
                        'woocommerce_tax_round',
                        [$this, 'round_pickup_discount'],
                        1,
                        2
                    );
                    $tax_amounts = \WC_Tax::calc_tax( $pickup_discount, $tax_rates, true );
                    // remove our internal filter to restore normal behavior for tax rounding calculations
                    remove_filter( 'woocommerce_tax_round', [$this, 'round_pickup_discount'], 1 );
                    // this is just a sum of all the applied taxes subtracted from pickup discount: they will be applied again when the fee is added to cart below
                    $pickup_discount -= \WC_Tax::get_tax_total( $tax_amounts );
                    // when no compound taxes are found, the calculation is much simpler with a reverse percentage
                } else {
                    $reverse_percentage = 1 + $percentage;
                    $pickup_discount /= $reverse_percentage;
                }
            }
        }
        return $pickup_discount;
    }

    /**
     * Validate our local pickup order upon checkout.
     *
     * The exceptions are converted into customer error notices by WooCommerce.
     *
     * @since 1.0.0
     *
     * @param array $posted_data checkout data (does not include package data, see $_POST)
     * @throws \Exception
     */
    public function dslpfw_validate_checkout( $posted_data ) {
        $local_pickup_method = dslpfw_shipping_method();
        $shipping_methods = ( isset( $posted_data['shipping_method'] ) ? (array) $posted_data['shipping_method'] : [] );
        $exception_message = '';
        // check if there are any packages comes wtih our local pickup
        $local_pickup_packages = ( !empty( $shipping_methods ) ? array_keys( $shipping_methods, $local_pickup_method->get_method_id(), true ) : null );
        if ( $local_pickup_packages ) {
            $pickup_data_filter = array(
                '_shipping_method_pickup_location_id'        => array(
                    'filter' => FILTER_SANITIZE_NUMBER_INT,
                    'flags'  => FILTER_REQUIRE_ARRAY,
                ),
                '_shipping_method_pickup_date'               => array(
                    'filter' => FILTER_SANITIZE_NUMBER_INT,
                    'flags'  => FILTER_REQUIRE_ARRAY,
                ),
                '_shipping_method_pickup_appointment_offset' => array(
                    'filter' => FILTER_SANITIZE_NUMBER_INT,
                    'flags'  => FILTER_REQUIRE_ARRAY,
                ),
            );
            $dslpfw_pickup_data = filter_input_array( INPUT_POST, $pickup_data_filter );
            $pickup_location_ids = ( isset( $dslpfw_pickup_data['_shipping_method_pickup_location_id'] ) ? $dslpfw_pickup_data['_shipping_method_pickup_location_id'] : [] );
            $pickup_dates = ( isset( $dslpfw_pickup_data['_shipping_method_pickup_date'] ) ? $dslpfw_pickup_data['_shipping_method_pickup_date'] : [] );
            $appointment_offsets = ( isset( $dslpfw_pickup_data['_shipping_method_pickup_appointment_offset'] ) ? $dslpfw_pickup_data['_shipping_method_pickup_appointment_offset'] : [] );
            $cart_item_pickup_location_ids = array();
            if ( $local_pickup_method->dslpfw_is_per_item_selection_enabled() ) {
                $cart_location_filter = array(
                    '_pickup_location_id' => array(
                        'filter' => FILTER_SANITIZE_NUMBER_INT,
                        'flags'  => FILTER_REQUIRE_ARRAY,
                    ),
                );
                $posted_cart_locations = filter_input_array( INPUT_POST, $cart_location_filter );
                if ( !empty( $posted_cart_locations['_pickup_location_id'] ) && is_array( $posted_cart_locations['_pickup_location_id'] ) ) {
                    $cart_item_pickup_location_ids = $posted_cart_locations['_pickup_location_id'];
                }
            }
            foreach ( $local_pickup_packages as $package_id ) {
                $error_messages = [];
                // a pickup location has not been chosen (per-order selection mode):
                if ( !$local_pickup_method->dslpfw_is_per_item_selection_enabled() && empty( $pickup_location_ids[$package_id] ) ) {
                    /* translators: Placeholder: %s - user assigned name for Local Pickup WooCommerce shipping method */
                    $error_messages['pickup_location_id'] = sprintf( esc_html__( 'Please select a pickup location if you intend to use %s as shipping method.', 'local-pickup-for-woocommerce' ), $local_pickup_method->get_method_title() );
                }
                // per-item selection: each pickup item must have a location chosen
                if ( $local_pickup_method->dslpfw_is_per_item_selection_enabled() ) {
                    $session_pickup_data = dslpfw()->get_dslpfw_session_object()->get_cart_item_pickup_data( null );
                    $packages = WC()->shipping()->get_packages();
                    if ( !empty( $packages[$package_id]['contents'] ) ) {
                        foreach ( $packages[$package_id]['contents'] as $cart_item_key => $cart_item ) {
                            $product = ( isset( $cart_item['data'] ) ? $cart_item['data'] : null );
                            if ( !$product instanceof \WC_Product || !dslpfw_product_can_be_picked_up( $product ) ) {
                                continue;
                            }
                            $handling = ( isset( $session_pickup_data[$cart_item_key]['handling'] ) ? $session_pickup_data[$cart_item_key]['handling'] : 'pickup' );
                            if ( 'pickup' !== $handling ) {
                                continue;
                            }
                            $location_id = 0;
                            if ( !empty( $cart_item_pickup_location_ids[$cart_item_key] ) ) {
                                $location_id = (int) $cart_item_pickup_location_ids[$cart_item_key];
                            } elseif ( !empty( $session_pickup_data[$cart_item_key]['pickup_location_id'] ) ) {
                                $location_id = (int) $session_pickup_data[$cart_item_key]['pickup_location_id'];
                            } elseif ( !empty( $cart_item['pickup_location_id'] ) ) {
                                $location_id = (int) $cart_item['pickup_location_id'];
                            } else {
                                $single_location = dslpfw()->get_dslpfw_products_object()->get_product_pickup_location( $product );
                                if ( $single_location ) {
                                    $location_id = (int) $single_location->get_id();
                                }
                            }
                            if ( $location_id <= 0 ) {
                                $error_messages['pickup_location_id'] = esc_html__( 'Please select a pickup location for each item set for local pickup.', 'local-pickup-for-woocommerce' );
                                break;
                            }
                        }
                    }
                }
                // the selected appointment time is no longer available:
                if ( !empty( $pickup_location_ids[$package_id] ) && !empty( $pickup_dates[$package_id] ) ) {
                    $appointment_offset = ( !empty( $appointment_offsets[$package_id] ) ? (int) $appointment_offsets[$package_id] : 0 );
                    try {
                        $pickup_location = dslpfw_get_pickup_location( (int) $pickup_location_ids[$package_id] );
                        $now = new \DateTime();
                        $start_date = new \DateTime(gmdate( 'Y-m-d H:i:s', strtotime( $pickup_dates[$package_id] ) + $appointment_offset ), $pickup_location->get_address()->get_timezone());
                        $appointment_duration = $pickup_location->get_appointments()->get_appointment_duration( $start_date );
                        $end_date = ( clone $start_date )->modify( sprintf( "+ %d seconds", $appointment_duration ) );
                        if ( !dslpfw()->get_dslpfw_appointments_object()->is_appointment_time_available(
                            $now,
                            $pickup_location,
                            $appointment_duration,
                            $start_date,
                            $end_date
                        ) ) {
                            // remove selected pickup date and time so that only available times are shown
                            $data_store = new DSLPFW_Package_Pickup_Data($package_id);
                            // @phpstan-ignore-line
                            $data_store->set_pickup_data( array_merge( $data_store->get_pickup_data(), [
                                'pickup_date'        => '',
                                'appointment_offset' => '',
                            ] ) );
                            // force WooCommerce to refresh checkout totals and render the appointment field again
                            WC()->session->set( 'refresh_totals', true );
                            throw new Exception('Appointment time not available');
                        }
                    } catch ( \Exception $e ) {
                        $error_messages['pickup_time'] = esc_html__( 'Oops! That appointment time is no longer available. Please select a new appointment.', 'local-pickup-for-woocommerce' );
                    }
                }
                /**
                 * Filter validation of pickup errors at checkout.
                 *
                 * @since 1.0.0
                 *
                 * @param array $errors associative array of errors and predefined messages - leave empty to pass validation
                 * @param int|string $package_key the current package key for the package being evaluated for pickup data
                 * @param array $posted_data posted data incoming from form submission
                 */
                $error_messages = apply_filters(
                    'dslpfw_validate_pickup_checkout',
                    $error_messages,
                    $package_id,
                    $posted_data
                );
                if ( !empty( $error_messages ) && is_array( $error_messages ) ) {
                    $exception_message = implode( '<br />', $error_messages );
                }
            }
            // set the user preferred pickup location (we can choose only one)
            if ( !empty( $pickup_location_ids ) && is_array( $pickup_location_ids ) ) {
                $pickup_location_id = current( $pickup_location_ids );
                if ( is_numeric( $pickup_location_id ) ) {
                    dslpfw_set_user_default_pickup_location( $pickup_location_id );
                }
            }
        }
        if ( '' !== $exception_message ) {
            throw new \Exception($exception_message);
            //phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        } elseif ( $session = dslpfw()->get_dslpfw_session_object() ) {
            //phpcs:ignore
            $session->delete_default_handling();
        }
    }

}
