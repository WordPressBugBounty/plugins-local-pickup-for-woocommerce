<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.thedotstore.com/
 * @since      1.0.0
 *
 * @package    DSLPFW_Local_Pickup_Woocommerce
 * @subpackage DSLPFW_Local_Pickup_Woocommerce/includes
 */
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    DSLPFW_Local_Pickup_Woocommerce
 * @subpackage DSLPFW_Local_Pickup_Woocommerce/includes
 * @author     theDotstore <support@thedotstore.com>
 */
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Utilities\OrderUtil;
use DotStore\DSLPFW_Local_Pickup_Woocommerce\Appointments\DSLPFW_Local_Pickup_WooCommerce_Appointments;
defined( 'ABSPATH' ) or exit;
if ( !class_exists( 'DSLPFW_Local_Pickup_Woocommerce' ) ) {
    class DSLPFW_Local_Pickup_Woocommerce {
        /**
         * The loader that's responsible for maintaining and registering all hooks that power
         * the plugin.
         *
         * @since    1.0.0
         * @access   protected
         * @var      DSLPFW_Local_Pickup_Woocommerce_Loader    $loader    Maintains and registers all hooks for the plugin.
         */
        protected $loader;

        /**
         * The unique identifier of this plugin.
         *
         * @since    1.0.0
         * @access   protected
         * @var      string    $plugin_name    The string used to uniquely identify this plugin.
         */
        protected $plugin_name;

        /**
         * The current version of the plugin.
         *
         * @since    1.0.0
         * @access   protected
         * @var      string    $version    The current version of the plugin.
         */
        protected $version;

        /** @var bool|null whether HPOS is enabled (null = not yet resolved) */
        private $hpos_enabled = null;

        /** shipping method ID */
        const DSLPFW_SHIPPING_METHOD_ID = 'ds_local_pickup';

        /** shipping method class name */
        const DSLPFW_SHIPPING_METHOD_CLASS_NAME = 'DSLPFW_Local_Pickup_WooCommerce_Shipping';

        /**Shipping data store prefix */
        const DSLPFW_PREFIX = 'woocommerce_' . self::DSLPFW_SHIPPING_METHOD_ID . '_';

        /** @var \DSLPFW_Local_Pickup_Woocommerce single instance of this plugin */
        protected static $instance;

        /** @var \DSLPFW_Local_Pickup_WooCommerce_Pickup_Locations pickup locations handler object */
        private $dslpfw_pickup_locations;

        /** @var \DSLPFW_Local_Pickup_WooCommerce_Products products handler object */
        private $dslpfw_products;

        /** @var \DSLPFW_Local_Pickup_WooCommerce_Orders orders handler object */
        private $dslpfw_orders;

        /** @var \DSLPFW_Local_Pickup_WooCommerce_Packages packages handler object */
        private $dslpfw_packages;

        /** @var \DSLPFW_Local_Pickup_WooCommerce_Session session handler object */
        private $dslpfw_session;

        /** @var Appointments appointments handler object */
        private $dslpfw_appointments;

        /** @var bool whether the shipping method has been loaded while doing AJAX */
        private static $ajax_loaded = false;

        /** @var string|\DSLPFW_Local_Pickup_WooCommerce_Shipping Local pickup WooCommerce shipping class name or object */
        private $shipping_method;

        /**
         * Define the core functionality of the plugin.
         *
         * Set the plugin name and the plugin version that can be used throughout the plugin.
         * Load the dependencies, define the locale, and set the hooks for the admin area and
         * the public-facing side of the site.
         *
         * @since    1.0.0
         */
        public function __construct() {
            if ( defined( 'DSLPFW_PLUGIN_VERSION' ) ) {
                $this->version = DSLPFW_PLUGIN_VERSION;
            } else {
                $this->version = '1.0.0';
            }
            $this->plugin_name = 'local-pickup-for-woocommerce';
            $this->load_dependencies();
            $this->set_locale();
            $this->define_admin_hooks();
            $this->define_public_hooks();
            $this->shipping_method = self::DSLPFW_SHIPPING_METHOD_CLASS_NAME;
            $prefix = ( is_network_admin() ? 'network_admin_' : '' );
            add_filter( "{$prefix}plugin_action_links_" . DSLPFW_PLUGIN_BASENAME, [$this, 'dslpfw_plugin_action_links'], 20 );
            add_filter(
                'plugin_row_meta',
                array($this, 'dslpfw_plugin_row_meta_action_links'),
                20,
                3
            );
            // Add class to WooCommerce Shipping Methods
            add_filter( 'woocommerce_shipping_methods', [$this, 'dslpfw_add_shipping_method'], 20 );
            // make sure one instance of the Shipping class is set
            add_action( 'dslpfw_wc_shipping_ds_local_pickup_init', [$this, 'dslpfw_set_shipping_method'] );
            // HPOS & Block Cart/Checkout Compatibility declare
            add_action( 'before_woocommerce_init', [$this, 'dslpfw_handle_features_compatibility'] );
            // Cart & Checkout Blocks integration — register hooks early; class loads when WC Blocks is ready.
            add_action( 'woocommerce_blocks_loaded', [$this, 'dslpfw_register_blocks_store_api'] );
            add_action( 'woocommerce_blocks_cart_block_registration', [$this, 'dslpfw_register_blocks_integration'] );
            add_action( 'woocommerce_blocks_checkout_block_registration', [$this, 'dslpfw_register_blocks_integration'] );
            // Fixes for #104808 ticket issue
            add_action( 'init', [$this, 'dslpfw_init_plugin'], 15 );
            // Wizard Start
            dslpfw_fs()->add_action( 'connect/before', [$this, 'dslpfw_load_plugin_setup_wizard_connect_before'] );
            dslpfw_fs()->add_action( 'connect/after', [$this, 'dslpfw_load_plugin_setup_wizard_connect_after'] );
            // Wizard End
            //License/Account page Start
            dslpfw_fs()->add_filter( 'hide_account_tabs', [$this, 'dslpfw_hide_account_tab'] );
            dslpfw_fs()->add_action( 'after_account_details', [$this, 'dslpfw_load_plugin_header_after_account'] );
            dslpfw_fs()->add_action( 'hide_billing_and_payments_info', [$this, 'dslpfw_hide_billing_and_payments_info'] );
            dslpfw_fs()->add_action( 'hide_freemius_powered_by', [$this, 'dslpfw_hide_freemius_powered_by'] );
            dslpfw_fs()->add_filter( 'plugin_icon', [$this, 'dslpfw_custom_icon'] );
            //License/Account page End
        }

        public function dslpfw_init_plugin() {
            // load helper functions
            require_once plugin_dir_path( dirname( __FILE__ ) ) . '/includes/functions/dslpfw-functions.php';
            /**
             * The class responsible for product related check for our plugin.
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-local-pickup-woocommerce-session.php';
            $this->dslpfw_session = new DSLPFW_Local_Pickup_WooCommerce_Session();
            /**
             * The class responsible for product related check for our plugin.
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-local-pickup-woocommerce-products.php';
            $this->dslpfw_products = new DSLPFW_Local_Pickup_WooCommerce_Products();
            /**
             * The class responsible for order related check for our plugin.
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-local-pickup-woocommerce-orders.php';
            $this->dslpfw_orders = new DSLPFW_Local_Pickup_WooCommerce_Orders();
            /**
             * The class responsible for defining all method for pickup location CPT (Main class)
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-local-pickup-woocommerce-pickup-locations.php';
            $this->dslpfw_pickup_locations = new DSLPFW_Local_Pickup_WooCommerce_Pickup_Locations();
            /**
             * The class responsible for defining all method for pickup location CPT (Main class)
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-local-pickup-woocommerce-packges.php';
            $this->dslpfw_packages = new DSLPFW_Local_Pickup_WooCommerce_Packages();
            // init appointments handler object
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/appointments/class-local-pickup-woocommerce-appointments.php';
            $this->dslpfw_appointments = new DSLPFW_Local_Pickup_WooCommerce_Appointments();
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/appointments/class-local-pickup-woocommerce-timezones.php';
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/appointments/class-local-pickup-woocommerce-appointment.php';
        }

        /**
         * Load the required dependencies for this plugin.
         *
         * Include the following files that make up the plugin:
         *
         * - DSLPFW_Local_Pickup_Woocommerce_Loader. Orchestrates the hooks of the plugin.
         * - DSLPFW_Local_Pickup_Woocommerce_i18n. Defines internationalization functionality.
         * - DSLPFW_Local_Pickup_Woocommerce_Admin. Defines all hooks for the admin area.
         * - DSLPFW_Local_Pickup_Woocommerce_Public. Defines all hooks for the public side of the site.
         *
         * Create an instance of the loader which will be used to register the hooks
         * with WordPress.
         *
         * @since    1.0.0
         * @access   private
         */
        private function load_dependencies() {
            /**
             * The class responsible for orchestrating the actions and filters of the core plugin.
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-local-pickup-woocommerce-loader.php';
            $this->loader = new DSLPFW_Local_Pickup_Woocommerce_Loader();
            /**
             * The class responsible for defining internationalization functionality of the plugin.
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-local-pickup-woocommerce-i18n.php';
            /**
             * The class responsible for defining all actions that occur in the admin area.
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-local-pickup-woocommerce-admin.php';
            /**
             * The class responsible for defining all actions that occur in the public-facing side of the site.
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-local-pickup-woocommerce-public.php';
        }

        /**
         * Define the locale for this plugin for internationalization.
         *
         * Uses the DSLPFW_Local_Pickup_Woocommerce_i18n class in order to set the domain and to register the hook
         * with WordPress.
         *
         * @since    1.0.0
         * @access   private
         */
        private function set_locale() {
            $plugin_i18n = new DSLPFW_Local_Pickup_Woocommerce_i18n();
            $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
        }

        /**
         * Register all of the hooks related to the admin area functionality
         * of the plugin.
         *
         * @since    1.0.0
         * @access   private
         */
        private function define_admin_hooks() {
            $page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $plugin_admin = new DSLPFW_Local_Pickup_Woocommerce_Admin($this->get_plugin_name(), $this->get_version());
            $this->loader->add_action( 'admin_menu', $plugin_admin, 'dslpfw_dot_store_menu' );
            $this->loader->add_action( 'admin_head', $plugin_admin, 'dslpfw_remove_admin_submenus' );
            $this->loader->add_filter(
                'set-screen-option',
                $plugin_admin,
                'dslpfw_set_screen_options',
                10,
                3
            );
            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'dslpfw_enqueue_shipping_zones_script' );
            if ( !empty( $page ) && (false !== strpos( $page, 'dslpfw' ) || false !== strpos( $page, 'local-pickup-for-woocommerce' )) ) {
                $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
                $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
                $this->loader->add_filter( 'admin_footer_text', $plugin_admin, 'dslpfw_admin_footer_review' );
            }
            $this->loader->add_action( 'wp_ajax_dslpfw_plugin_setup_wizard_submit', $plugin_admin, 'dslpfw_plugin_setup_wizard_submit' );
            $this->loader->add_action( 'admin_init', $plugin_admin, 'dslpfw_send_wizard_data_after_plugin_activation' );
            // If we make this to 'init' hook then REST API works but on save rule it throw error.
            $this->loader->add_action( 'init', $plugin_admin, 'dslpfw_post_type_define' );
            $this->loader->add_action( 'admin_notices', $plugin_admin, 'dslpfw_display_action_message' );
            // Change status on listing of pickup location
            $this->loader->add_action( 'wp_ajax_dslpfw_change_status_from_list', $plugin_admin, 'dslpfw_change_status_from_list_callback' );
            // Save pickup location sort order from listing page
            $this->loader->add_action( 'wp_ajax_dslpfw_pickup_location_sort_order', $plugin_admin, 'dslpfw_pickup_location_sort_order_callback' );
            // Get product data for pickup location
            $this->loader->add_action( 'wp_ajax_dslpfw_json_search_products', $plugin_admin, 'dslpfw_json_search_products_callback' );
            // Import/Export Pickup Locations AJAX
            $this->loader->add_action( 'wp_ajax_dslpfw_export_pickup_locations_action', $plugin_admin, 'dslpfw_export_pickup_locations_action_callback' );
            $this->loader->add_action( 'wp_ajax_dslpfw_import_pickup_locations_action', $plugin_admin, 'dslpfw_import_pickup_locations_action_callback' );
            // Remove our custom shipping method section from WC shipping tab
            $this->loader->add_filter( 'woocommerce_get_sections_shipping', $plugin_admin, 'dslpfw_remove_section' );
            $this->loader->add_action( 'init', $plugin_admin, 'dslpfw_redirect_shipping_function' );
            /**
             * Admin side data handling for Pickup Locations
             */
            // add a Pickup Location for each shipping item to edit the Pickup Location ID
            $this->loader->add_action(
                'woocommerce_before_order_itemmeta',
                $plugin_admin,
                'dslpfw_show_order_shipping_item_pickup_data',
                1,
                2
            );
            /**
             * Product data
             */
            // Add Pickup Locations fields for Products and Product Categories (To-Do: need to start from this )
            $this->loader->add_action(
                'woocommerce_product_options_shipping',
                $plugin_admin,
                'dslpfw_add_product_pickup_locations_options',
                -1
            );
            // The low priority here is to rule out an issue with Subscriptions.
            $this->loader->add_action( 'product_cat_add_form_fields', $plugin_admin, 'dslpfw_add_product_category_pickup_locations_options' );
            $this->loader->add_action( 'product_cat_edit_form_fields', $plugin_admin, 'dslpfw_edit_product_category_pickup_locations_options' );
            // Store product meta from shipping tab section
            $this->loader->add_action( 'woocommerce_process_product_meta', $plugin_admin, 'dslpfw_save_product_pickup_availability' );
            // save or update Pickup Location fields for Products and Product Categories
            $this->loader->add_action(
                'create_term',
                $plugin_admin,
                'dslpfw_save_product_cat_pickup_availability',
                10,
                1
            );
            $this->loader->add_action(
                'edit_term',
                $plugin_admin,
                'dslpfw_save_product_cat_pickup_availability',
                10,
                1
            );
            // For category level pickup availablity conflict message
            $this->loader->add_action( 'admin_notices', $plugin_admin, 'dslpfw_add_product_category_conflict_notice' );
            // Add action
            $this->loader->add_filter( 'bulk_actions-edit-shop_order', $plugin_admin, 'dslpfw_add_export_bulk_actions' );
            $this->loader->add_filter( 'bulk_actions-woocommerce_page_wc-orders', $plugin_admin, 'dslpfw_add_export_bulk_actions' );
            // Handle action
            $this->loader->add_filter(
                'handle_bulk_actions-edit-shop_order',
                $plugin_admin,
                'dslpfw_handle_export_bulk_action',
                10,
                3
            );
            $this->loader->add_filter(
                'handle_bulk_actions-woocommerce_page_wc-orders',
                $plugin_admin,
                'dslpfw_handle_export_bulk_action',
                10,
                3
            );
            // For block based cart and checkout page notice
            $this->loader->add_action( 'admin_notices', $plugin_admin, 'dslpfw_add_block_based_cart_checkout_notice' );
        }

        /**
         * Register all of the hooks related to the public-facing functionality
         * of the plugin.
         *
         * @since    1.0.0
         * @access   private
         */
        private function define_public_hooks() {
            $plugin_public = new DSLPFW_Local_Pickup_Woocommerce_Public($this->get_plugin_name(), $this->get_version());
            $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
            $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
            /** 
             * AJAX calls 
             */
            // set a cart item for shipping or pickup
            $this->loader->add_action( 'wp_ajax_dslpfw_set_cart_item_handling', $plugin_public, 'set_cart_item_handling_callback' );
            $this->loader->add_action( 'wp_ajax_nopriv_dslpfw_set_cart_item_handling', $plugin_public, 'set_cart_item_handling_callback' );
            // set a package pickup data
            $this->loader->add_action( 'wp_ajax_dslpfw_set_package_handling', $plugin_public, 'set_package_handling_callback' );
            $this->loader->add_action( 'wp_ajax_nopriv_dslpfw_set_package_handling', $plugin_public, 'set_package_handling_callback' );
            // set the handling for items in a package when the shipping method is changed
            $this->loader->add_action( 'wp_ajax_dslpfw_set_package_items_handling', $plugin_public, 'set_package_items_handling_callback' );
            $this->loader->add_action( 'wp_ajax_nopriv_dslpfw_set_package_items_handling', $plugin_public, 'set_package_items_handling_callback' );
            // get location pickup appointment data
            $this->loader->add_action( 'wp_ajax_dslpfw_get_pickup_location_appointment_data', $plugin_public, 'get_pickup_location_appointment_data_callback' );
            $this->loader->add_action( 'wp_ajax_nopriv_dslpfw_get_pickup_location_appointment_data', $plugin_public, 'get_pickup_location_appointment_data_callback' );
            // get opening hours for a given location
            $this->loader->add_action( 'wp_ajax_dslpfw_get_pickup_location_opening_hours_list', $plugin_public, 'get_pickup_location_opening_hours_list_callback' );
            $this->loader->add_action( 'wp_ajax_nopriv_dslpfw_get_pickup_location_opening_hours_list', $plugin_public, 'get_pickup_location_opening_hours_list_callback' );
            /**
             * Cart page hooks
             */
            // set the cart item keys as cart item properties
            $this->loader->add_action( 'template_redirect', $plugin_public, 'dslpfw_set_cart_item_keys' );
            // set the default handling data based on product-level settings
            $this->loader->add_action(
                'woocommerce_get_item_data',
                $plugin_public,
                'dslpfw_set_cart_item_pickup_handling',
                10,
                2
            );
            // add a selector next to each product in cart to designate for pickup
            // Note: this is normally a filter, we use an action to echo some content instead
            $this->loader->add_action(
                'woocommerce_get_item_data',
                $plugin_public,
                'dslpfw_add_cart_item_pickup_location_field_callback',
                99,
                2
            );
            // perhaps disable the shipping calculator if the first and sole item in the cart totals is for pickup
            $this->loader->add_filter( 'option_woocommerce_enable_shipping_calc', $plugin_public, 'dslpfw_disable_shipping_calculator' );
            /**
             * Checkout page hooks
             */
            // to output the checkout item pickup location selector we need a different hook than the one used in cart page
            $this->loader->add_filter(
                'woocommerce_checkout_cart_item_quantity',
                $plugin_public,
                'dslpfw_add_checkout_item_pickup_location_field_callback',
                999,
                3
            );
            // add pickup location information and a pickup appointment field to each package meant for pickup
            $this->loader->add_action(
                'woocommerce_after_shipping_rate',
                $plugin_public,
                'dslpfw_output_pickup_package_form',
                999,
                2
            );
            // Remove WooCommerce displaying pickup item details in wrong places in the checkout form
            $this->loader->add_filter(
                'woocommerce_shipping_package_details_array',
                $plugin_public,
                'dslpfw_hide_pickup_package_item_details',
                10,
                2
            );
            // output hidden counters for packages by handling type for JS use
            $this->loader->add_action(
                'woocommerce_review_order_after_cart_contents',
                $plugin_public,
                'dslpfw_packages_count',
                40
            );
            // if there are any chosen pickup locations that warrant a discount, apply the total discount as a negative fee
            $this->loader->add_action( 'woocommerce_cart_calculate_fees', $plugin_public, 'dslpfw_apply_pickup_fee_discount' );
            // handle checkout validation upon submission
            $this->loader->add_action(
                'woocommerce_after_checkout_validation',
                $plugin_public,
                'dslpfw_validate_checkout',
                999
            );
            // add tags to the first and last packages of each handling type (Shipping or Local Pickup)
            $this->loader->add_filter( 'woocommerce_shipping_packages', $plugin_public, 'dslpfw_add_template_variables_to_packages' );
            // Merge our shipping method name with the shipping package name
            $this->loader->add_filter(
                'woocommerce_shipping_package_name',
                $plugin_public,
                'dslpfw_group_method_label_callback',
                10,
                5
            );
        }

        /**
         * Run the loader to execute all of the hooks with WordPress.
         *
         * @since    1.0.0
         */
        public function run() {
            $this->loader->run();
        }

        /**
         * The name of the plugin used to uniquely identify it within the context of
         * WordPress and to define internationalization functionality.
         *
         * @since     1.0.0
         * @return    string    The name of the plugin.
         */
        public function get_plugin_name() {
            return $this->plugin_name;
        }

        /**
         * The reference to the class that orchestrates the hooks with the plugin.
         *
         * @since     1.0.0
         * @return    DSLPFW_Local_Pickup_Woocommerce_Loader    Orchestrates the hooks of the plugin.
         */
        public function get_loader() {
            return $this->loader;
        }

        /**
         * Retrieve the version number of the plugin.
         *
         * @since     1.0.0
         * @return    string    The version number of the plugin.
         */
        public function get_version() {
            return $this->version;
        }

        public function dslpfw_plugin_action_links( $actions ) {
            $custom_actions = array();
            if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
                // Define custom action links with appropriate URLs and labels.
                $custom_actions = array(
                    'configure' => sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( array(
                        'page' => 'dslpfw-local-pickup-settings',
                    ), admin_url( 'admin.php' ) ) ), esc_html__( 'Settings', 'local-pickup-for-woocommerce' ) ),
                    'docs'      => sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( 'https://docs.thedotstore.com/article/769-introduction-of-local-pickup-for-woocommerce' ), esc_html__( 'Docs', 'local-pickup-for-woocommerce' ) ),
                    'support'   => sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( 'https://www.thedotstore.com/support' ), esc_html__( 'Support', 'local-pickup-for-woocommerce' ) ),
                );
            }
            // Merge the custom action links with the existing action links.
            return array_merge( $custom_actions, $actions );
        }

        /**
         * Add review stars in plugin row meta
         *
         * @since 1.0.0
         */
        public function dslpfw_plugin_row_meta_action_links( $plugin_meta, $plugin_file, $plugin_data ) {
            if ( isset( $plugin_data['TextDomain'] ) && $plugin_data['TextDomain'] !== 'local-pickup-for-woocommerce' ) {
                return $plugin_meta;
            }
            $url = '';
            $url = esc_url( 'https://wordpress.org/plugins/local-pickup-for-woocommerce/#reviews' );
            $plugin_meta[] = sprintf( '<a href="%s" target="_blank" style="color:#f5bb00;">%s</a>', $url, esc_html( '★★★★★' ) );
            return $plugin_meta;
        }

        /**
         * Allowed html tags used for wp_kses function
         *
         * @param array add custom tags (Not used)
         *
         * @return array
         * @since     1.0.0
         *
         */
        public static function dslpfw_allowed_html_tags() {
            $allowed_tags = array(
                'a'        => array(
                    'href'         => array(),
                    'title'        => array(),
                    'id'           => array(),
                    'class'        => array(),
                    'target'       => array(),
                    'data-tooltip' => array(),
                    'data-day-id'  => array(),
                    'data-handler' => array(),
                    'data-event'   => array(),
                    'aria-current' => array(),
                    'data-date'    => array(),
                ),
                'ul'       => array(
                    'class'                 => array(),
                    'role'                  => array(),
                    'tabindex'              => array(),
                    'id'                    => array(),
                    'aria-expanded'         => array(),
                    'aria-hidden'           => array(),
                    'aria-activedescendant' => array(),
                ),
                'li'       => array(
                    'class'         => array(),
                    'role'          => array(),
                    'id'            => array(),
                    'data-selected' => array(),
                    'tabindex'      => array(),
                    'aria-selected' => array(),
                ),
                'div'      => array(
                    'class'                 => array(),
                    'id'                    => array(),
                    'style'                 => array(),
                    'data-pickup-object-id' => array(),
                    'role'                  => array(),
                    'aria-live'             => array(),
                    'aria-atomic'           => array(),
                    'aria-relevant'         => array(),
                ),
                'select'   => array(
                    'rel-id'                  => array(),
                    'id'                      => array(),
                    'name'                    => array(),
                    'class'                   => array(),
                    'multiple'                => array(),
                    'style'                   => array(),
                    'data-placeholder'        => array(),
                    'data-pickup-object-type' => array(),
                    'data-pickup-object-id'   => array(),
                    'tabindex'                => array(),
                    'aria-hidden'             => array(),
                    'aria-label'              => array(),
                    'data-handler'            => array(),
                    'data-event'              => array(),
                ),
                'option'   => array(
                    'id'                     => array(),
                    'selected'               => array(),
                    'name'                   => array(),
                    'value'                  => array(),
                    'data-name'              => array(),
                    'data-postcode'          => array(),
                    'data-nearby'            => array(),
                    'data-city'              => array(),
                    'data-address'           => array(),
                    'data-address-formatted' => array(),
                ),
                'input'    => array(
                    'id'                    => array(),
                    'value'                 => array(),
                    'name'                  => array(),
                    'class'                 => array(),
                    'type'                  => array(),
                    'data-index'            => array(),
                    'tabindex'              => array(),
                    'autocomplete'          => array(),
                    'autocorrect'           => array(),
                    'autocapitalize'        => array(),
                    'spellcheck'            => array(),
                    'role'                  => array(),
                    'aria-autocomplete'     => array(),
                    'aria-expanded'         => array(),
                    'aria-owns'             => array(),
                    'aria-activedescendant' => array(),
                    'readonly'              => array(),
                    'placeholder'           => array(),
                    'required'              => array(),
                    'data-location-id'      => array(),
                    'data-package-id'       => array(),
                    'data-pickup-date'      => array(),
                    'checked'               => array(),
                ),
                'textarea' => array(
                    'id'    => array(),
                    'name'  => array(),
                    'class' => array(),
                ),
                'br'       => array(),
                'p'        => array(),
                'b'        => array(
                    'style' => array(),
                    'role'  => array(),
                ),
                'em'       => array(),
                'strong'   => array(),
                'i'        => array(
                    'class' => array(),
                    'style' => array(),
                ),
                'span'     => array(
                    'class'                 => array(),
                    'style'                 => array(),
                    'dir'                   => array(),
                    'aria-expanded'         => array(),
                    'role'                  => array(),
                    'aria-autocomplete'     => array(),
                    'aria-owns'             => array(),
                    'aria-activedescendant' => array(),
                    'aria-hidden'           => array(),
                    'aria-labelledby'       => array(),
                    'aria-readonly'         => array(),
                    'title'                 => array(),
                    'data-date'             => array(),
                ),
                'small'    => array(
                    'class' => array(),
                    'style' => array(),
                ),
                'label'    => array(
                    'class' => array(),
                    'id'    => array(),
                    'for'   => array(),
                ),
                'button'   => array(
                    'type'  => array(),
                    'class' => array(),
                    'title' => array(),
                ),
                'abbr'     => array(
                    'class' => array(),
                    'title' => array(),
                    'style' => array(),
                ),
                'h2'       => array(
                    'class' => array(),
                ),
                'table'    => array(
                    'class' => array(),
                ),
                'thead'    => array(),
                'tbody'    => array(),
                'tr'       => array(),
                'th'       => array(
                    'scope' => array(),
                    'class' => array(),
                ),
                'td'       => array(
                    'class'        => array(),
                    'data-handler' => array(),
                    'data-event'   => array(),
                    'data-month'   => array(),
                    'data-year'    => array(),
                ),
            );
            return $allowed_tags;
        }

        /**
         * Declares compatibility with specific WooCommerce features.
         *
         * @internal
         *
         * @since 1.0.0
         */
        public function dslpfw_handle_features_compatibility() {
            if ( !class_exists( FeaturesUtil::class ) ) {
                return;
            }
            FeaturesUtil::declare_compatibility( 'custom_order_tables', DSLPFW_PLUGIN_BASENAME, true );
            FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', DSLPFW_PLUGIN_BASENAME, true );
        }

        /**
         * Load Cart/Checkout Blocks class and register Store API endpoints.
         *
         * @since 1.2.1
         */
        public function dslpfw_register_blocks_store_api() {
            $this->dslpfw_include_blocks_class();
            if ( class_exists( 'DSLPFW_Local_Pickup_WooCommerce_Blocks' ) ) {
                DSLPFW_Local_Pickup_WooCommerce_Blocks::register_store_api_and_ajax();
            }
        }

        /**
         * Register the Blocks IntegrationInterface instance with Cart/Checkout.
         *
         * @since 1.2.1
         *
         * @param object $registry Integration registry from WooCommerce Blocks.
         */
        public function dslpfw_register_blocks_integration( $registry ) {
            $this->dslpfw_include_blocks_class();
            if ( class_exists( 'DSLPFW_Local_Pickup_WooCommerce_Blocks' ) && is_object( $registry ) && method_exists( $registry, 'register' ) ) {
                $registry->register( new DSLPFW_Local_Pickup_WooCommerce_Blocks() );
            }
        }

        /**
         * Include the blocks integration class once WooCommerce Blocks interfaces exist.
         *
         * @since 1.2.1
         */
        private function dslpfw_include_blocks_class() {
            if ( class_exists( 'DSLPFW_Local_Pickup_WooCommerce_Blocks', false ) ) {
                return;
            }
            if ( !interface_exists( '\\Automattic\\WooCommerce\\Blocks\\Integrations\\IntegrationInterface' ) ) {
                return;
            }
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-local-pickup-woocommerce-blocks.php';
        }

        /**
         * Start plugin setup wizard before license activation screen
         *
         * @since    1.0.0
         */
        public function dslpfw_load_plugin_setup_wizard_connect_before() {
            require_once DSLPFW_PLUGIN_BASE_DIR . 'admin/partials/dslpfw-plugin-setup-wizard.php';
            ?>
            <div class="tab-panel" id="step5">
                <div class="ds-wizard-wrap">
                    <div class="ds-wizard-content">
                        <h2 class="cta-title"><?php 
            echo esc_html__( 'Activate Plugin', 'local-pickup-for-woocommerce' );
            ?></h2>
                    </div>
            <?php 
        }

        /**
         * End plugin setup wizard after license activation screen
         *
         * @since    1.0.0
         */
        public function dslpfw_load_plugin_setup_wizard_connect_after() {
            ?>
            </div>
            </div>
            </div>
            </div>
            <?php 
        }

        /**
         * Hide freemius account tab
         *
         * @since    1.0.0
         */
        public function dslpfw_hide_account_tab() {
            return true;
        }

        /**
         * Include plugin header on freemius account page
         *
         * @since    1.0.0
         */
        public function dslpfw_load_plugin_header_after_account() {
            require_once DSLPFW_PLUGIN_BASE_DIR . 'admin/partials/header/plugin-header.php';
        }

        /**
         * Hide billing and payments details from freemius account page
         *
         * @since    1.0.0
         */
        public function dslpfw_hide_billing_and_payments_info() {
            return true;
        }

        /**
         * Hide powerd by popup from freemius account page
         *
         * @since    1.0.0
         */
        public function dslpfw_hide_freemius_powered_by() {
            return true;
        }

        /**
         * Plugin LOGO URL
         *
         * @since    1.0.0
         */
        public function dslpfw_custom_icon() {
            return DSLPFW_PLUGIN_LOGO_URL;
        }

        /**
         * Adds the Shipping Method to WooCommerce.
         *
         * @internal
         *
         * @since 1.0.0
         *
         * @param string[]|\WC_Shipping_Method[] $methods array of hipping method class names or objects
         * @return string[]|\WC_Shipping_Method[]
         */
        public function dslpfw_add_shipping_method( $methods ) {
            // Check for supported plugins are active or not
            // dslpfw_deactivate_plugin();
            if ( !array_key_exists( self::DSLPFW_SHIPPING_METHOD_ID, $methods ) ) {
                // Since the shipping method is always constructed, we'll pass it in to the register filter so it doesn't have to be re-instantiated;
                // so, the following will be either the class name, or the class object if we've already instantiated it.
                // $methods[ self::DSLPFW_SHIPPING_METHOD_ID ] = $this->dslpfw_get_shipping_method_instance();
                $methods[self::DSLPFW_SHIPPING_METHOD_ID] = \DSLPFW_Local_Pickup_WooCommerce_Shipping::class;
            }
            return $methods;
        }

        /**
         * Sets the Local Pickup WooCommerce shipping method.
         *
         * In this way, if shipping methods are loaded more than once during a request,
         * we can avoid instantiating the class a second time and duplicating action hooks.
         *
         * @since 1.0.0
         *
         * @param \DSLPFW_Local_Pickup_WooCommerce_Shipping $ds_local_pickup Local Pickup WooCommerce shipping class
         */
        public function dslpfw_set_shipping_method( \DSLPFW_Local_Pickup_WooCommerce_Shipping $ds_local_pickup ) {
            if ( !$this->shipping_method instanceof \DSLPFW_Local_Pickup_WooCommerce_Shipping ) {
                $this->shipping_method = $ds_local_pickup;
            }
        }

        /**
         * Gets the Local Pickup WooCommerce shipping method main instance.
         *
         * @since 1.0.0
         *
         * @return \DSLPFW_Local_Pickup_WooCommerce_Shipping Local pickup WooCommerce shipping method
         */
        public function dslpfw_get_shipping_method_instance() {
            if ( !$this->shipping_method instanceof \DSLPFW_Local_Pickup_WooCommerce_Shipping ) {
                if ( !class_exists( 'DSLPFW_Local_Pickup_WooCommerce_Shipping' ) ) {
                    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-local-pickup-woocommerce-shipping.php';
                }
                $this->shipping_method = new \DSLPFW_Local_Pickup_WooCommerce_Shipping();
            }
            return $this->shipping_method;
        }

        /**
         * Gets the pickup locations handler object.
         *
         * @since 1.0.0
         *
         * @return \DSLPFW_Local_Pickup_WooCommerce_Pickup_Locations
         */
        public function get_dslpfw_pickup_locations_object() {
            return $this->dslpfw_pickup_locations;
        }

        /**
         * Gets the products handler object.
         *
         * @since 1.0.0
         *
         * @return \DSLPFW_Local_Pickup_WooCommerce_Products
         */
        public function get_dslpfw_products_object() {
            return $this->dslpfw_products;
        }

        /**
         * Gets the session handler object.
         *
         * @since 1.0.0
         *
         * @return \DSLPFW_Local_Pickup_WooCommerce_Session
         */
        public function get_dslpfw_session_object() {
            return $this->dslpfw_session;
        }

        /**
         * Gets the packages handler object.
         *
         * @since 1.0.0
         *
         * @return \DSLPFW_Local_Pickup_WooCommerce_Packages
         */
        public function get_dslpfw_packages_object() {
            return $this->dslpfw_packages;
        }

        /**
         * Gets the orders handler object.
         *
         * @since 1.0.0
         *
         * @return \DSLPFW_Local_Pickup_WooCommerce_Orders
         */
        public function get_dslpfw_orders_object() {
            return $this->dslpfw_orders;
        }

        /**
         * Gets the appointments handler object.
         *
         * @since 1.0.0
         *
         * @return Appointments
         */
        public function get_dslpfw_appointments_object() {
            return $this->dslpfw_appointments;
        }

        /**
         * Gets the main Local Pickup WooCommerce instance.
         *
         * Ensures only one instance loaded at one time.
         *
         * @see \dslpfw()
         *
         * @since 1.0.0
         *
         * @return \DSLPFW_Local_Pickup_Woocommerce
         */
        public static function instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Ensures the shipping method class is loaded.
         *
         * @since 1.0.0
         */
        public function dslpfw_load_shipping_method() {
            $this->dslpfw_get_shipping_method_instance();
        }

        /**
         * Get message text using type
         *
         * @param array add custom tags
         *
         * @return array
         * @since     1.0.0
         *
         */
        public function dslpfw_updated_message( $message ) {
            if ( empty( $message ) ) {
                return false;
            }
            $query_args = array(
                'dslpfw_message' => $message,
            );
            $current_url = '';
            if ( isset( $_SERVER['REQUEST_URI'] ) ) {
                $current_url = filter_input(
                    INPUT_SERVER,
                    'REQUEST_URI',
                    FILTER_SANITIZE_URL,
                    FILTER_VALIDATE_URL
                );
            }
            // Redirect to the current URL with the custom query parameter
            wp_safe_redirect( add_query_arg( $query_args, home_url( $current_url ) ) );
            exit;
        }

        /**
         * Saves errors or messages to WooCommerce Log (woocommerce/logs/plugin-{plugin_slug}-{YEAR-MONTH-DAY}-{HASH}.txt)
         *
         * @since 1.0.0
         * @param string $message error or message to save to log
         * @param string $type type of error or message to save to log (default: 'error')
         */
        public function dslpfw_log( $message, $type = 'error' ) {
            if ( 'emergency' === $type ) {
                wc_get_logger()->emergency( $message );
            } elseif ( 'alert' === $type ) {
                wc_get_logger()->alert( $message );
            } elseif ( 'critical' === $type ) {
                wc_get_logger()->critical( $message );
            } elseif ( 'error' === $type ) {
                wc_get_logger()->error( $message );
            } elseif ( 'warning' === $type ) {
                wc_get_logger()->warning( $message );
            } elseif ( 'notice' === $type ) {
                wc_get_logger()->notice( $message );
            } elseif ( 'info' === $type ) {
                wc_get_logger()->info( $message );
            } elseif ( 'debug' === $type ) {
                wc_get_logger()->debug( $message );
            }
        }

        /**
         * Determines whether HPOS is enabled.
         *
         * The result is resolved once and cached in $this->hpos_enabled for the
         * lifetime of the request so repeated calls are essentially free.
         *
         * @link https://woocommerce.com/document/high-performance-order-storage/
         * @link https://github.com/woocommerce/woocommerce/wiki/High-Performance-Order-Storage-Upgrade-Recipe-Book#detecting-whether-hpos-tables-are-being-used-in-the-store
         *
         * @since 1.0.0
         *
         * @return bool
         */
        public function dslpfw_is_hpos_enabled() {
            return is_callable( [OrderUtil::class, 'custom_orders_table_usage_is_enabled'] ) && OrderUtil::custom_orders_table_usage_is_enabled();
        }

    }

}
/**
 * Returns the One True Instance of Local Pickup WooCommerce class object.
 *
 * @since 1.0.0
 *
 * @return \DSLPFW_Local_Pickup_Woocommerce
 */
if ( !function_exists( 'dslpfw' ) ) {
    function dslpfw() {
        return \DSLPFW_Local_Pickup_Woocommerce::instance();
    }

}