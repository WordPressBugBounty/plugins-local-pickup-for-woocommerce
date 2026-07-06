<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.thedotstore.com/
 * @since      1.0.0
 *
 * @package    DSLPFW_Local_Pickup_Woocommerce
 * @subpackage DSLPFW_Local_Pickup_Woocommerce/admin
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    DSLPFW_Local_Pickup_Woocommerce
 * @subpackage DSLPFW_Local_Pickup_Woocommerce/admin
 * @author     theDotstore <support@thedotstore.com>
 */
use Automattic\WooCommerce\Utilities\CartCheckoutUtils as CartCheckoutUtils;
class DSLPFW_Local_Pickup_Woocommerce_Admin {
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
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles( $hook ) {
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
            $this->plugin_name . '-main',
            plugin_dir_url( __FILE__ ) . 'css/local-pickup-woocommerce-admin.css',
            array(),
            $this->version,
            'all'
        );
        if ( strpos( $hook, '_page_dslpfw' ) !== false ) {
            wp_enqueue_style(
                'jquery-ui',
                plugin_dir_url( __FILE__ ) . 'css/jquery-ui.css',
                array(),
                $this->version,
                'all'
            );
            wp_enqueue_style(
                $this->plugin_name . '-datepicker',
                plugin_dir_url( __FILE__ ) . 'css/local-pickup-woocommerce-datepicker.css',
                array($this->plugin_name . '-main', 'jquery-ui'),
                $this->version,
                'all'
            );
            wp_enqueue_style(
                $this->plugin_name . '-slider',
                plugin_dir_url( __FILE__ ) . 'css/local-pickup-woocommerce-slider.css',
                array($this->plugin_name . '-main', 'jquery-ui'),
                $this->version,
                'all'
            );
            wp_enqueue_style( 'woocommerce_admin_styles' );
        }
        wp_enqueue_style(
            $this->plugin_name . '-header',
            plugin_dir_url( __FILE__ ) . 'css/dslpfw-header.css',
            array(),
            $this->version,
            'all'
        );
        wp_enqueue_style(
            $this->plugin_name . '-footer',
            plugin_dir_url( __FILE__ ) . 'css/dslpfw-footer.css',
            array(),
            $this->version,
            'all'
        );
        wp_enqueue_style(
            $this->plugin_name . '-responsive',
            plugin_dir_url( __FILE__ ) . 'css/local-pickup-woocommerce-admin-responsive.css',
            array(),
            $this->version,
            'all'
        );
        wp_enqueue_style(
            $this->plugin_name . '-plugin-setup-wizard',
            plugin_dir_url( __FILE__ ) . 'css/plugin-setup-wizard.css',
            array(),
            'all'
        );
        wp_enqueue_style(
            $this->plugin_name . '-promotional-bar',
            plugin_dir_url( __FILE__ ) . 'css/local-pickup-woocommerce-promotional-bar.css',
            array(),
            'all'
        );
        wp_enqueue_style(
            $this->plugin_name . '-font-awesome',
            plugin_dir_url( __FILE__ ) . 'css/font-awesome.min.css',
            array(),
            $this->version,
            'all'
        );
        wp_enqueue_style(
            $this->plugin_name . 'upgrade-dashboard-style',
            plugin_dir_url( __FILE__ ) . 'css/upgrade-dashboard.css',
            array(),
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts( $hook ) {
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
        if ( strpos( $hook, '_page_dslpfw' ) !== false ) {
            wp_enqueue_script( 'jquery-tiptip' );
            wp_enqueue_script( 'jquery-blockui' );
            wp_enqueue_script(
                $this->plugin_name . '-select2',
                plugin_dir_url( __FILE__ ) . 'js/select2.min.js',
                array('jquery', 'jquery-ui-dialog', 'jquery-ui-accordion'),
                $this->version,
                false
            );
            wp_enqueue_script( 'jquery-ui-slider' );
            wp_enqueue_script( 'selectWoo' );
        }
        if ( false !== strpos( $hook, 'dslpfw-pickup-location-list' ) && self::dslpfw_is_pickup_location_sortable_list() ) {
            wp_enqueue_script( 'jquery-ui-sortable' );
        }
        if ( strpos( $hook, '_page_dslpfw-import-export' ) !== false ) {
            wp_enqueue_script(
                $this->plugin_name . '-import-export',
                plugin_dir_url( __FILE__ ) . 'js/dslpfw-import-export.js',
                array('jquery', 'jquery-tiptip'),
                time(),
                true
            );
            wp_localize_script( $this->plugin_name . '-import-export', 'dslpfw_import_export_vars', array(
                'ajaxurl'         => admin_url( 'admin-ajax.php' ),
                'file_upload_msg' => esc_html__( "Please upload {ext} file", 'local-pickup-for-woocommerce' ),
            ) );
        }
        wp_enqueue_script(
            $this->plugin_name . 'freemius_pro',
            'https://checkout.freemius.com/checkout.min.js',
            array('jquery'),
            $this->version,
            true
        );
        wp_enqueue_script(
            $this->plugin_name . '-help-scout-beacon-js',
            plugin_dir_url( __FILE__ ) . 'js/help-scout-beacon.js',
            array('jquery'),
            $this->version,
            false
        );
        $admin_script_deps = array(
            'jquery',
            'jquery-tiptip',
            'selectWoo',
            'wc-enhanced-select',
            'jquery-blockui',
            'jquery-ui-datepicker'
        );
        if ( false !== strpos( $hook, 'dslpfw-pickup-location-list' ) && self::dslpfw_is_pickup_location_sortable_list() ) {
            $admin_script_deps[] = 'jquery-ui-sortable';
        }
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'js/local-pickup-woocommerce-admin.js',
            $admin_script_deps,
            $this->version,
            false
        );
        wp_localize_script( $this->plugin_name, 'dslpfw_vars', array(
            'ajaxurl'                          => admin_url( 'admin-ajax.php' ),
            'setup_wizard_ajax_nonce'          => wp_create_nonce( 'wizard_ajax_nonce' ),
            'status_change_listing_ajax_nonce' => wp_create_nonce( 'status-change-listing-ajax-nonce' ),
            'dslpfw_ajax_nonce'                => wp_create_nonce( 'dslpfw_global_nonce' ),
            'dslpfw_product_search_nonce'      => wp_create_nonce( 'dslpfw-search-products' ),
            'select2_product_placeholder'      => esc_html__( 'All products', 'local-pickup-for-woocommerce' ),
            'select2_category_placeholder'     => esc_html__( 'All categories', 'local-pickup-for-woocommerce' ),
            'select2_product_type_placeholder' => esc_html__( 'Both product types', 'local-pickup-for-woocommerce' ),
            'select2_per_product_ajax'         => absint( apply_filters( 'dslpfw_json_product_search_limit', 10 ) ),
            'select2_per_category_ajax'        => absint( apply_filters( 'dslpfw_json_category_search_limit', 10 ) ),
            'delete_confirmation_message'      => esc_html__( 'Do you really want to proceed with the deletion?', 'local-pickup-for-woocommerce' ),
            'pickup_location_sort_enabled'     => self::dslpfw_is_pickup_location_sortable_list(),
            'pickup_location_sort_success'     => esc_html__( 'Pickup location order has been updated.', 'local-pickup-for-woocommerce' ),
        ) );
        wp_enqueue_script(
            'dslpfw-promotioanl-bar',
            plugin_dir_url( __FILE__ ) . 'js/local-pickup-woocommerce-promotional-bar.js',
            array('jquery'),
            $this->version,
            false
        );
        wp_localize_script( 'dslpfw-promotioanl-bar', 'dslpfw_pb_vars', array(
            'dpb_api_url' => esc_url( DSLPFW_STORE_URL . 'wp-content/plugins/dots-dynamic-promotional-banner/bar-response.php' ),
        ) );
    }

    public function dslpfw_enqueue_shipping_zones_script( $hook ) {
        wp_enqueue_script(
            'dslpfw-shipping-zones',
            plugin_dir_url( __FILE__ ) . 'js/dslpfw-shipping-zones.js',
            array('jquery'),
            $this->version,
            false
        );
    }

    public function dslpfw_dot_store_menu() {
        global $GLOBALS;
        $parent_menu = 'dots_store';
        if ( empty( $GLOBALS['admin_page_hooks']['dots_store'] ) ) {
            add_menu_page(
                'Dotstore Plugins',
                esc_html__( 'Dotstore Plugins', 'local-pickup-for-woocommerce' ),
                'null',
                'dots_store',
                array($this, 'dot_store_menu_page'),
                'dashicons-marker',
                25
            );
        }
        add_submenu_page(
            $parent_menu,
            DSLPFW_PLUGIN_NAME,
            DSLPFW_PLUGIN_NAME,
            'manage_options',
            'dslpfw-local-pickup-settings',
            array($this, 'dslpfw_local_pickup_settings')
        );
        $dslpfw_hook = add_submenu_page(
            $parent_menu,
            'Pickup Locations',
            'Pickup Locations',
            'manage_options',
            'dslpfw-pickup-location-list',
            array($this, 'dslpfw_pickup_location_list_page')
        );
        // inlcude screen options
        add_action( "load-{$dslpfw_hook}", array($this, "dslpfw_rule_screen_options") );
        add_submenu_page(
            'dots_store',
            'Get Premium',
            'Get Premium',
            'manage_options',
            'dslpfw-pro-dashboard',
            array($this, 'dslpfw_free_user_upgrade_page')
        );
        add_submenu_page(
            $parent_menu,
            'Import / Export',
            'Import / Export',
            'manage_options',
            'dslpfw-import-export',
            array($this, 'dslpfw_import_export_page')
        );
        add_submenu_page(
            $parent_menu,
            'Getting Started',
            'Getting Started',
            'manage_options',
            'dslpfw-get-started',
            array($this, 'dslpfw_get_started_page')
        );
        //Remove footer WP version
        $get_page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $page = ( !empty( $get_page ) ? sanitize_text_field( $get_page ) : '' );
        if ( !empty( $page ) && false !== strpos( $page, 'dslpfw' ) ) {
            remove_filter( 'update_footer', 'core_update_footer' );
        }
    }

    /**
     * Remove section from shipping settings because we have added new menu in woocommece section
     *
     * @param array $sections
     *
     * @return array $sections
     *
     * @since    1.0.0
     */
    public function dslpfw_remove_section( $sections ) {
        unset($sections[\DSLPFW_Local_Pickup_Woocommerce::DSLPFW_SHIPPING_METHOD_ID]);
        return $sections;
    }

    /**
     * Redirect to shipping list page
     *
     * @since    1.0.0
     */
    public function dslpfw_redirect_shipping_function() {
        $get_section = filter_input( INPUT_GET, 'section', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $get_section = ( !empty( $get_section ) ? sanitize_text_field( $get_section ) : '' );
        if ( !empty( $get_section ) && \DSLPFW_Local_Pickup_Woocommerce::DSLPFW_SHIPPING_METHOD_ID === $get_section ) {
            wp_safe_redirect( add_query_arg( array(
                'page' => 'dslpfw-local-pickup-settings',
            ), admin_url( 'admin.php' ) ) );
            exit;
        }
    }

    /**
     * Redirect to listing page from dotStore menu page
     *
     * @since    1.0.0
     */
    public function dot_store_menu_page() {
        wp_safe_redirect( admin_url( 'admin.php?page=dslpfw-local-pickup-settings' ) );
        exit;
    }

    /**
     * Screen option for pickup location list
     *
     * @since    1.0.0
     */
    public function dslpfw_rule_screen_options() {
        $args = array(
            'label'   => esc_html__( 'Rules Per Page', 'local-pickup-for-woocommerce' ),
            'default' => 10,
            'option'  => 'dslpfw_rule_per_page',
        );
        add_screen_option( 'per_page', $args );
        //For discplay listing table
        if ( !class_exists( 'DSLPFW_Pickup_Location_List_Table' ) ) {
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/list-tables/class-pickup-location-list-table.php';
        }
        new DSLPFW_Pickup_Location_List_Table();
    }

    /**
     * Add screen option for per page
     *
     * @param bool   $status
     * @param string $option
     * @param int    $value
     *
     * @return int $value
     * @since 4.2.0
     *
     */
    public function dslpfw_set_screen_options( $status, $option, $value ) {
        $dpad_screens = array('dslpfw_rule_per_page');
        if ( 'dslpfw_rule_per_page' === $option ) {
            $value = ( !empty( $value ) && $value > 0 ? $value : get_option( 'posts_per_page' ) );
        }
        if ( in_array( $option, $dpad_screens, true ) ) {
            return $value;
        }
        return $status;
    }

    /**
     * Register Admin fee list page output.
     *
     * @since    1.0.0
     */
    public function dslpfw_pickup_location_list_page() {
        require_once plugin_dir_path( __FILE__ ) . '/partials/dslpfw-pickup-location-list-page.php';
        $dslpfw_lising_obj = new DSLPFW_Pickup_Location_Listing();
        $dslpfw_lising_obj->dslpfw_listing_output();
    }

    /**
     * Import Export Setting page
     *
     * @since    1.0.0
     */
    public function dslpfw_local_pickup_settings() {
        require_once plugin_dir_path( __FILE__ ) . '/partials/dslpfw-local-pickup-settings.php';
    }

    /**
     * Premium version info page
     *
     * @since    1.0.0
     */
    public function dslpfw_free_user_upgrade_page() {
        require_once plugin_dir_path( __FILE__ ) . '/partials/dslpfw-upgrade-dashboard.php';
    }

    /**
     * Import Export Setting page
     *
     * @since    1.0.0
     */
    public function dslpfw_import_export_page() {
        require_once plugin_dir_path( __FILE__ ) . '/partials/dslpfw-import-export-page.php';
    }

    /**
     * Quick guide page
     *
     * @since    1.0.0
     */
    public function dslpfw_get_started_page() {
        require_once plugin_dir_path( __FILE__ ) . 'partials/dslpfw-get-started-page.php';
    }

    /**
     * Plugin footer review link with text
     *
     * @since    1.0.0
     */
    public function dslpfw_admin_footer_review() {
        $url = '';
        $url = esc_url( 'https://wordpress.org/plugins/local-pickup-for-woocommerce/#reviews' );
        $html = sprintf( wp_kses( __( '<strong>We need your support</strong> to keep updating and improving the plugin. Please <a href="%1$s" target="_blank">help us by leaving a good review</a> :) Thanks!', 'local-pickup-for-woocommerce' ), array(
            'strong' => array(),
            'a'      => array(
                'href'   => array(),
                'target' => 'blank',
            ),
        ) ), esc_url( $url ) );
        echo wp_kses_post( $html );
    }

    /**
     * Remove submenu from admin screeen
     *
     * @since    1.0.0
     */
    public function dslpfw_remove_admin_submenus() {
        //Remove inner pages from menu list
        remove_submenu_page( 'dots_store', 'dots_store' );
        remove_submenu_page( 'dots_store', 'dslpfw-import-export' );
        remove_submenu_page( 'dots_store', 'dslpfw-get-started' );
        remove_submenu_page( 'dots_store', 'dslpfw-pickup-location-list' );
        remove_submenu_page( 'dots_store', 'dslpfw-pro-dashboard' );
        //CSS for dotstore icon
        echo '<style>
            .toplevel_page_dots_store .dashicons-marker::after{content:"";border:3px solid;position:absolute;top:14px;left:15px;border-radius:50%;opacity: 0.6;}
            li.toplevel_page_dots_store:hover .dashicons-marker::after,li.toplevel_page_dots_store.current .dashicons-marker::after{opacity: 1;}
            .folded .toplevel_page_dots_store .dashicons-marker::after{left:14.5px;}
            @media only screen and (max-width: 960px){
                .toplevel_page_dots_store .dashicons-marker::after{left: 14px;}
            }
        </style>';
    }

    /**
     * Get and save plugin setup wizard data
     * 
     * @since    1.0.0
     * 
     */
    public function dslpfw_plugin_setup_wizard_submit() {
        check_ajax_referer( 'wizard_ajax_nonce', 'nonce' );
        $survey_list = filter_input( INPUT_GET, 'survey_list', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if ( !empty( $survey_list ) && 'Select One' !== $survey_list ) {
            update_option( 'dslpfw_where_hear_about_us', $survey_list );
        }
        wp_die();
    }

    /**
     * Gets the local pickup availability input field HTML.
     *
     * @since 1.0.0
     *
     * @param string $field_name the field name used in field attributes
     * @param string $field_value the field value
     * @param string $description the field description text, default empty
     * @param bool $for_category if this input is for a category setting, defaults to false
     * @return string input field HTML
     */
    private function get_local_pickup_availability_input_html(
        $field_name,
        $field_value = 'inherit',
        $description = '',
        $for_category = false
    ) {
        $availability_types = ( $for_category ? dslpfw()->get_dslpfw_products_object()->get_dslpfw_category_availability_types( true ) : dslpfw()->get_dslpfw_products_object()->get_dslpfw_product_availability_types( true ) );
        ob_start();
        ?>
		<select id="<?php 
        echo esc_attr( $field_name );
        ?>" name="<?php 
        echo esc_attr( $field_name );
        ?>" class="select short dslpfw_local_pickup_availability">
			<?php 
        foreach ( $availability_types as $type => $label ) {
            ?>
				<option value="<?php 
            echo esc_attr( $type );
            ?>" <?php 
            selected( $field_value, $type, true );
            ?>><?php 
            echo esc_html( $label );
            ?></option>
			<?php 
        }
        ?>
		</select>
		<?php 
        if ( !empty( $description ) ) {
            global $post;
            ?>

			<?php 
            if ( null === $post ) {
                ?>
				<p class="description"><?php 
                echo esc_html( $description );
                ?></p>
			<?php 
            } else {
                ?>
				<?php 
                echo wp_kses_post( wc_help_tip( $description ) );
                ?>
			<?php 
            }
            ?>

		<?php 
        }
        return ob_get_clean();
    }

    /**
     * Adds Pickup availability to a product shipping tab section.
     *
     * @since 1.0.0
     */
    public function dslpfw_add_product_pickup_locations_options() {
        global $post;
        $product = wc_get_product( $post );
        if ( !$product instanceof \WC_Product ) {
            return;
        }
        ?>
		<div class="options_group dslpfw-product-pickup-locations">
			<p class="form-field dslpfw-local-pickup-product-availability_field ">
				<?php 
        $name = dslpfw()->get_dslpfw_products_object()->get_product_availability_meta();
        $value = dslpfw_get_product_availability( $product, true );
        $desc = esc_html__( 'Choose whether local pickup is available for this product, or if local pickup is the only type of shipment possible.', 'local-pickup-for-woocommerce' );
        ?>
				<label for="<?php 
        echo esc_attr( $name );
        ?>"><?php 
        esc_html_e( 'Pickup Availability', 'local-pickup-for-woocommerce' );
        ?></label>
				<?php 
        echo wp_kses( $this->get_local_pickup_availability_input_html( $name, esc_html( $value ), $desc ), array(
            'select' => array(
                'name'  => true,
                'id'    => true,
                'class' => true,
            ),
            'option' => array(
                'value'    => true,
                'selected' => true,
            ),
            'p'      => array(
                'class' => true,
            ),
        ) );
        ?>
			</p>
		</div>
		<?php 
    }

    /**
     * Save or update a product local pickup availability.
     *
     * @since 1.0.0
     *
     * @param int $post_id  product post ID
     */
    public function dslpfw_save_product_pickup_availability( $post_id ) {
        $meta_key = dslpfw()->get_dslpfw_products_object()->get_product_availability_meta();
        $get_availability = filter_input( INPUT_POST, $meta_key, FILTER_SANITIZE_SPECIAL_CHARS );
        $pickup_availability = ( !empty( $get_availability ) ? sanitize_text_field( $get_availability ) : null );
        $product = wc_get_product( $post_id );
        if ( $pickup_availability && $product && in_array( $pickup_availability, dslpfw()->get_dslpfw_products_object()->get_dslpfw_product_availability_types(), true ) ) {
            $product->update_meta_data( $meta_key, $pickup_availability );
            $product->save_meta_data();
        }
    }

    /**
     * Adds Pickup availability to a product categories.
     *
     * @since 1.0.0
     */
    public function dslpfw_add_product_category_pickup_locations_options() {
        $name = dslpfw()->get_dslpfw_products_object()->get_product_cat_availability_meta();
        ?>
        <div class="form-field term-pickup-availability-wrap">
            <label for="<?php 
        echo esc_attr( $name );
        ?>"><?php 
        esc_html_e( 'Pickup Availability', 'local-pickup-for-woocommerce' );
        ?></label>
            <?php 
        echo wp_kses( $this->get_local_pickup_availability_input_html(
            $name,
            'allowed',
            esc_html__( 'Choose whether local pickup is possible for this category of products, or if local pickup is the only type of shipment possible. Individual products may override this setting.', 'local-pickup-for-woocommerce' ),
            true
        ), array(
            'select' => array(
                'name'  => true,
                'id'    => true,
                'class' => true,
            ),
            'option' => array(
                'value'    => true,
                'selected' => true,
            ),
            'p'      => array(
                'class' => true,
            ),
        ) );
        ?>
        </div>
        <?php 
    }

    /**
     * Adds Pickup availability to a product categories edit page.
     *
     * @since 1.0.0
     */
    public function dslpfw_edit_product_category_pickup_locations_options() {
        global $tag;
        if ( !$tag ) {
            return;
        }
        $name = dslpfw()->get_dslpfw_products_object()->get_product_cat_availability_meta();
        $value = dslpfw_get_product_cat_availability( $tag );
        ?>
		<tr class="form-field term-name-wrap">
			<th scope="row">
                <label for="<?php 
        echo esc_attr( $name );
        ?>">
                    <?php 
        esc_html_e( 'Pickup Availability', 'local-pickup-for-woocommerce' );
        ?>
                </label>
            </th>
			<td>
                <?php 
        echo wp_kses( $this->get_local_pickup_availability_input_html(
            $name,
            $value,
            esc_html__( 'Choose whether local pickup is possible for this category of products, or if local pickup is the only type of shipment possible. Individual products may override this setting.', 'local-pickup-for-woocommerce' ),
            true
        ), array(
            'select' => array(
                'name'  => true,
                'id'    => true,
                'class' => true,
            ),
            'option' => array(
                'value'    => true,
                'selected' => true,
            ),
            'p'      => array(
                'class' => true,
            ),
        ) );
        ?>
            </td>
		</tr>
		<?php 
    }

    /**
     * Save or update a product category pickup availability.
     *
     * @since 1.0.0
     *
     * @param int $term_id              product category term ID
     */
    public function dslpfw_save_product_cat_pickup_availability( $term_id ) {
        $meta_key = dslpfw()->get_dslpfw_products_object()->get_product_cat_availability_meta();
        $get_availability = filter_input( INPUT_POST, $meta_key, FILTER_SANITIZE_SPECIAL_CHARS );
        $pickup_availability = ( !empty( $get_availability ) ? sanitize_text_field( $get_availability ) : null );
        if ( $pickup_availability && in_array( $pickup_availability, dslpfw()->get_dslpfw_products_object()->get_dslpfw_category_availability_types(), true ) ) {
            update_term_meta( $term_id, $meta_key, $pickup_availability );
        }
    }

    /**
     * Adds an admin notice if the current product has categories which have conflicting pickup settings.
     *
     * @since 1.0.0
     */
    public function dslpfw_add_product_category_conflict_notice() {
        if ( 'product' !== get_current_screen()->id ) {
            return;
        }
        $product = WC()->product_factory->get_product( get_the_ID() );
        if ( !$product || 'inherit' !== dslpfw_get_product_availability( $product, true ) ) {
            return;
        }
        $required = false;
        $disallowed = false;
        $category_ids = $product->get_category_ids();
        if ( is_array( $category_ids ) && !empty( $category_ids ) ) {
            foreach ( $category_ids as $category_id ) {
                $cat_availability = dslpfw_get_product_cat_availability( $category_id );
                $required = ( 'required' === $cat_availability ? true : $required );
                $disallowed = ( 'disallowed' === $cat_availability ? true : $disallowed );
                if ( $required && $disallowed ) {
                    ?>
                     <div class="error notice">
                        <p><?php 
                    esc_html_e( '2 or more categories are conflicting pickup availability setting. Please check and resolve it else this product will work as pickup only.', 'local-pickup-for-woocommerce' );
                    ?></p>
                    </div>
                    <?php 
                    return;
                }
            }
        }
    }

    /**
     * Send setup wizard data to sendinblue
     * 
     * @since    1.0.0
     * 
     */
    public function dslpfw_send_wizard_data_after_plugin_activation() {
        $send_wizard_data = filter_input( INPUT_GET, 'send-wizard-data', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if ( isset( $send_wizard_data ) && !empty( $send_wizard_data ) ) {
            if ( !get_option( 'dslpfw_data_submited_in_sendiblue' ) ) {
                $dslpfw_where_hear = get_option( 'dslpfw_where_hear_about_us' );
                $get_user = dslpfw_fs()->get_user();
                $data_insert_array = array();
                if ( isset( $get_user ) && !empty( $get_user ) ) {
                    $data_insert_array = array(
                        'user_email'              => $get_user->email,
                        'ACQUISITION_SURVEY_LIST' => $dslpfw_where_hear,
                    );
                }
                $feedback_api_url = DSLPFW_STORE_URL . 'wp-json/dotstore-sendinblue-data/v2/dotstore-sendinblue-data?' . wp_rand();
                $query_url = $feedback_api_url . '&' . http_build_query( $data_insert_array );
                if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
                    $response = vip_safe_wp_remote_get(
                        $query_url,
                        3,
                        1,
                        20
                    );
                } else {
                    $response = wp_remote_get( $query_url );
                    //phpcs:ignore
                }
                if ( !is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
                    update_option( 'dslpfw_data_submited_in_sendiblue', '1' );
                    delete_option( 'dslpfw_where_hear_about_us' );
                }
            }
        }
    }

    /**
     * Custom post type for plugin data store and configuration
     *
     * @since    1.0.0
     */
    public function dslpfw_post_type_define() {
        $labels = array(
            'name'          => _x( 'Pickup Locations', 'Post Type General Name', 'local-pickup-for-woocommerce' ),
            'singular_name' => _x( 'Pickup Location', 'Post Type Singular Name', 'local-pickup-for-woocommerce' ),
        );
        $args = array(
            'label'               => esc_html__( 'Pickup Location', 'local-pickup-for-woocommerce' ),
            'labels'              => $labels,
            'supports'            => array('title'),
            'hierarchical'        => false,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_nav_menus'   => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'capability_type'     => 'pickup_location',
            'show_in_rest'        => true,
            'map_meta_cap'        => true,
            'rewrite'             => false,
            'query_var'           => false,
        );
        register_post_type( DSLPFW_POST_TYPE, $args );
    }

    /**
     * Change status of pickup location callback - AJAX
     * 
     * @since 1.0.0
     */
    public function dslpfw_change_status_from_list_callback() {
        check_ajax_referer( 'status-change-listing-ajax-nonce', 'security' );
        $get_dslpfw_id = filter_input( INPUT_POST, 'dslpfw_id', FILTER_SANITIZE_NUMBER_INT );
        $get_dslpfw_status = filter_input( INPUT_POST, 'dslpfw_status', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $dslpfw_id = ( isset( $get_dslpfw_id ) ? absint( $get_dslpfw_id ) : 0 );
        $dslpfw_status = ( isset( $get_dslpfw_status ) ? sanitize_text_field( $get_dslpfw_status ) : false );
        if ( empty( $dslpfw_id ) ) {
            wp_send_json_error( esc_html__( 'Rule ID is not there!', 'local-pickup-for-woocommerce' ) );
        }
        if ( 'true' === $dslpfw_status ) {
            $post_args = array(
                'ID'          => $dslpfw_id,
                'post_status' => 'publish',
                'post_type'   => DSLPFW_POST_TYPE,
            );
            $post_update = wp_update_post( $post_args );
            if ( $post_update > 0 ) {
                update_post_meta( $dslpfw_id, 'dslpfw_pickup_location_status', 'on' );
            } else {
                wp_send_json_error( esc_html__( 'Status not changed! Error occurred!', 'local-pickup-for-woocommerce' ) );
            }
        } else {
            $post_args = array(
                'ID'          => $dslpfw_id,
                'post_status' => 'draft',
                'post_type'   => DSLPFW_POST_TYPE,
            );
            $post_update = wp_update_post( $post_args );
            if ( $post_update > 0 ) {
                update_post_meta( $dslpfw_id, 'dslpfw_pickup_location_status', 'off' );
            } else {
                wp_send_json_error( esc_html__( 'Status not changed! Error occurred!', 'local-pickup-for-woocommerce' ) );
            }
        }
        wp_send_json_success( esc_html__( 'Status has been changed!', 'local-pickup-for-woocommerce' ), 200 );
    }

    /**
     * Save pickup location sort order from the listing page.
     *
     * @since 1.1.3
     */
    public function dslpfw_pickup_location_sort_order_callback() {
        check_ajax_referer( 'dslpfw_global_nonce', 'nonce' );
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You do not have permission to update pickup location order.', 'local-pickup-for-woocommerce' ) );
        }
        $get_paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );
        $get_location_order = filter_input(
            INPUT_GET,
            'locationOrderArray',
            FILTER_SANITIZE_NUMBER_INT,
            FILTER_REQUIRE_ARRAY
        );
        $paged = ( !empty( $get_paged ) ? absint( $get_paged ) : 1 );
        $location_order_array = ( !empty( $get_location_order ) ? array_map( 'absint', wp_unslash( $get_location_order ) ) : array() );
        if ( empty( $location_order_array ) ) {
            wp_send_json_error( esc_html__( 'No pickup locations found to sort.', 'local-pickup-for-woocommerce' ) );
        }
        $all_location_ids = get_posts( array(
            'post_type'              => DSLPFW_POST_TYPE,
            'post_status'            => 'any',
            'posts_per_page'         => -1,
            'orderby'                => array(
                'menu_order' => 'ASC',
                'post_date'  => 'DESC',
            ),
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ) );
        if ( empty( $all_location_ids ) ) {
            wp_send_json_error( esc_html__( 'No pickup locations found to sort.', 'local-pickup-for-woocommerce' ) );
        }
        $per_page = $this->get_pickup_location_list_per_page();
        $offset = (max( 1, $paged ) - 1) * $per_page;
        foreach ( $location_order_array as $index => $location_id ) {
            if ( !isset( $all_location_ids[$offset + $index] ) ) {
                break;
            }
            $all_location_ids[$offset + $index] = $location_id;
        }
        foreach ( $all_location_ids as $menu_order => $location_id ) {
            wp_update_post( array(
                'ID'         => (int) $location_id,
                'menu_order' => (int) $menu_order + 1,
            ) );
        }
        wp_send_json_success( esc_html__( 'Pickup location order has been updated.', 'local-pickup-for-woocommerce' ) );
    }

    /**
     * Gets the number of pickup locations shown per admin list page.
     *
     * @since 1.1.3
     *
     * @return int
     */
    private function get_pickup_location_list_per_page() {
        $per_page = get_user_option( 'dslpfw_rule_per_page' );
        if ( empty( $per_page ) ) {
            $per_page = get_option( 'posts_per_page' );
        }
        return max( 1, (int) $per_page );
    }

    /**
     * Whether drag-and-drop sorting is enabled on the pickup locations list.
     *
     * @since 1.1.3
     *
     * @return bool
     */
    public static function dslpfw_is_pickup_location_sortable_list() {
        $get_action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $get_search = filter_input( INPUT_GET, 's', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $get_orderby = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $get_status = filter_input( INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if ( !empty( $get_action ) || !empty( $get_search ) || !empty( $get_orderby ) || !empty( $get_status ) ) {
            return false;
        }
        return true;
    }

    /**
     * Based on message type prepare notice
     * 
     * @since    1.0.0
     * 
     */
    public function dslpfw_display_action_message() {
        $message = filter_input( INPUT_GET, 'dslpfw_message', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $message = ( isset( $message ) ? sanitize_text_field( $message ) : '' );
        if ( !empty( $message ) ) {
            if ( 'created' === $message ) {
                $updated_message = esc_html__( "Pickup location has been created.", 'local-pickup-for-woocommerce' );
            } elseif ( 'saved' === $message ) {
                $updated_message = esc_html__( "Pickup location has been updated.", 'local-pickup-for-woocommerce' );
            } elseif ( 'deleted' === $message ) {
                $updated_message = esc_html__( "Pickup location has been deleted.", 'local-pickup-for-woocommerce' );
            } elseif ( 'duplicated' === $message ) {
                $updated_message = esc_html__( "Pickup location has been duplicated.", 'local-pickup-for-woocommerce' );
            } elseif ( 'disabled' === $message ) {
                $updated_message = esc_html__( "Pickup location has been disabled.", 'local-pickup-for-woocommerce' );
            } elseif ( 'enabled' === $message ) {
                $updated_message = esc_html__( "Pickup location has been enabled.", 'local-pickup-for-woocommerce' );
            }
            if ( 'failed' === $message ) {
                $failed_messsage = esc_html__( "There was an error with saving data.", 'local-pickup-for-woocommerce' );
            } elseif ( 'nonce_check' === $message ) {
                $failed_messsage = esc_html__( "There was an error with security check.", 'local-pickup-for-woocommerce' );
            }
            if ( !empty( $updated_message ) ) {
                echo sprintf( '<div id="message" class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $updated_message ) );
            }
            if ( !empty( $failed_messsage ) ) {
                echo sprintf( '<div id="message" class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html( $failed_messsage ) );
            }
        }
    }

    /**
     * Get dynamic promotional bar of plugin
     *
     * @param   String  $plugin_slug  slug of the plugin added in the site option
     * @since    1.0.0
     * 
     * @return  null
     */
    public function dslpfw_get_promotional_bar( $plugin_slug = '' ) {
        $promotional_bar_upi_url = DSLPFW_STORE_URL . 'wp-json/dpb-promotional-banner/v2/dpb-promotional-banner?' . wp_rand();
        $promotional_banner_request = wp_remote_get( $promotional_bar_upi_url );
        //phpcs:ignore
        if ( empty( $promotional_banner_request->errors ) ) {
            $promotional_banner_request_body = $promotional_banner_request['body'];
            $promotional_banner_request_body = json_decode( $promotional_banner_request_body, true );
            echo '<div class="dynamicbar_wrapper">';
            if ( !empty( $promotional_banner_request_body ) && is_array( $promotional_banner_request_body ) ) {
                foreach ( $promotional_banner_request_body as $promotional_banner_request_body_data ) {
                    $promotional_banner_id = $promotional_banner_request_body_data['promotional_banner_id'];
                    $promotional_banner_cookie = $promotional_banner_request_body_data['promotional_banner_cookie'];
                    $promotional_banner_image = $promotional_banner_request_body_data['promotional_banner_image'];
                    $promotional_banner_description = $promotional_banner_request_body_data['promotional_banner_description'];
                    $promotional_banner_button_group = $promotional_banner_request_body_data['promotional_banner_button_group'];
                    $dpb_schedule_campaign_type = $promotional_banner_request_body_data['dpb_schedule_campaign_type'];
                    $promotional_banner_target_audience = $promotional_banner_request_body_data['promotional_banner_target_audience'];
                    if ( !empty( $promotional_banner_target_audience ) ) {
                        $plugin_keys = array();
                        if ( is_array( $promotional_banner_target_audience ) ) {
                            foreach ( $promotional_banner_target_audience as $list ) {
                                $plugin_keys[] = $list['value'];
                            }
                        } else {
                            $plugin_keys[] = $promotional_banner_target_audience['value'];
                        }
                        $display_banner_flag = false;
                        if ( in_array( 'all_customers', $plugin_keys, true ) || in_array( $plugin_slug, $plugin_keys, true ) ) {
                            $display_banner_flag = true;
                        }
                    }
                    if ( true === $display_banner_flag ) {
                        if ( 'default' === $dpb_schedule_campaign_type ) {
                            $banner_cookie_show = filter_input( INPUT_COOKIE, 'banner_show_' . $promotional_banner_cookie, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                            $banner_cookie_visible_once = filter_input( INPUT_COOKIE, 'banner_show_once_' . $promotional_banner_cookie, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                            $flag = false;
                            if ( empty( $banner_cookie_show ) && empty( $banner_cookie_visible_once ) ) {
                                setcookie( 'banner_show_' . $promotional_banner_cookie, 'yes', time() + 86400 * 7 );
                                //phpcs:ignore
                                setcookie( 'banner_show_once_' . $promotional_banner_cookie, 'yes' );
                                //phpcs:ignore
                                $flag = true;
                            }
                            $banner_cookie_show = filter_input( INPUT_COOKIE, 'banner_show_' . $promotional_banner_cookie, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                            if ( !empty( $banner_cookie_show ) || true === $flag ) {
                                $banner_cookie = filter_input( INPUT_COOKIE, 'banner_' . $promotional_banner_cookie, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                                $banner_cookie = ( isset( $banner_cookie ) ? $banner_cookie : '' );
                                if ( empty( $banner_cookie ) && 'yes' !== $banner_cookie ) {
                                    ?>
                            	<div class="dpb-popup <?php 
                                    echo ( isset( $promotional_banner_cookie ) ? esc_attr( $promotional_banner_cookie ) : 'default-banner' );
                                    ?>">
                                    <?php 
                                    if ( !empty( $promotional_banner_image ) ) {
                                        ?>
                                        <img src="<?php 
                                        echo esc_url( $promotional_banner_image );
                                        ?>"/>
                                        <?php 
                                    }
                                    ?>
                                    <div class="dpb-popup-meta">
                                        <p>
                                            <?php 
                                    echo wp_kses_post( str_replace( array('<p>', '</p>'), '', $promotional_banner_description ) );
                                    if ( !empty( $promotional_banner_button_group ) ) {
                                        foreach ( $promotional_banner_button_group as $promotional_banner_button_group_data ) {
                                            ?>
                                                    <a href="<?php 
                                            echo esc_url( $promotional_banner_button_group_data['promotional_banner_button_link'] );
                                            ?>" target="_blank"><?php 
                                            echo esc_html( $promotional_banner_button_group_data['promotional_banner_button_text'] );
                                            ?></a>
                                                    <?php 
                                        }
                                    }
                                    ?>
                                    	</p>
                                    </div>
                                    <a href="javascript:void(0);" data-bar-id="<?php 
                                    echo esc_attr( $promotional_banner_id );
                                    ?>" data-popup-name="<?php 
                                    echo ( isset( $promotional_banner_cookie ) ? esc_attr( $promotional_banner_cookie ) : 'default-banner' );
                                    ?>" class="dpbpop-close"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10"><path id="Icon_material-close" data-name="Icon material-close" d="M17.5,8.507,16.493,7.5,12.5,11.493,8.507,7.5,7.5,8.507,11.493,12.5,7.5,16.493,8.507,17.5,12.5,13.507,16.493,17.5,17.5,16.493,13.507,12.5Z" transform="translate(-7.5 -7.5)" fill="#acacac"/></svg></a>
                                </div>
                                <?php 
                                }
                            }
                        } else {
                            $banner_cookie_show = filter_input( INPUT_COOKIE, 'banner_show_' . $promotional_banner_cookie, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                            $banner_cookie_visible_once = filter_input( INPUT_COOKIE, 'banner_show_once_' . $promotional_banner_cookie, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                            $flag = false;
                            if ( empty( $banner_cookie_show ) && empty( $banner_cookie_visible_once ) ) {
                                setcookie( 'banner_show_' . $promotional_banner_cookie, 'yes' );
                                //phpcs:ignore
                                setcookie( 'banner_show_once_' . $promotional_banner_cookie, 'yes' );
                                //phpcs:ignore
                                $flag = true;
                            }
                            $banner_cookie_show = filter_input( INPUT_COOKIE, 'banner_show_' . $promotional_banner_cookie, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                            if ( !empty( $banner_cookie_show ) || true === $flag ) {
                                $banner_cookie = filter_input( INPUT_COOKIE, 'banner_' . $promotional_banner_cookie, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                                $banner_cookie = ( isset( $banner_cookie ) ? $banner_cookie : '' );
                                if ( empty( $banner_cookie ) && 'yes' !== $banner_cookie ) {
                                    ?>
                    			<div class="dpb-popup <?php 
                                    echo ( isset( $promotional_banner_cookie ) ? esc_attr( $promotional_banner_cookie ) : 'default-banner' );
                                    ?>">
                                    <?php 
                                    if ( !empty( $promotional_banner_image ) ) {
                                        ?>
                                            <img src="<?php 
                                        echo esc_url( $promotional_banner_image );
                                        ?>"/>
                                        <?php 
                                    }
                                    ?>
                                    <div class="dpb-popup-meta">
                                        <p>
                                            <?php 
                                    echo wp_kses_post( str_replace( array('<p>', '</p>'), '', $promotional_banner_description ) );
                                    if ( !empty( $promotional_banner_button_group ) ) {
                                        foreach ( $promotional_banner_button_group as $promotional_banner_button_group_data ) {
                                            ?>
                                                    <a href="<?php 
                                            echo esc_url( $promotional_banner_button_group_data['promotional_banner_button_link'] );
                                            ?>" target="_blank"><?php 
                                            echo esc_html( $promotional_banner_button_group_data['promotional_banner_button_text'] );
                                            ?></a>
                                                    <?php 
                                        }
                                    }
                                    ?>
                                        </p>
                                    </div>
                                    <a href="javascript:void(0);" data-popup-name="<?php 
                                    echo ( isset( $promotional_banner_cookie ) ? esc_attr( $promotional_banner_cookie ) : 'default-banner' );
                                    ?>" class="dpbpop-close"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10"><path id="Icon_material-close" data-name="Icon material-close" d="M17.5,8.507,16.493,7.5,12.5,11.493,8.507,7.5,7.5,8.507,11.493,12.5,7.5,16.493,8.507,17.5,12.5,13.507,16.493,17.5,17.5,16.493,13.507,12.5Z" transform="translate(-7.5 -7.5)" fill="#acacac"/></svg></a>
                                </div>
                                <?php 
                                }
                            }
                        }
                    }
                }
            }
            echo '</div>';
        }
    }

    public function dslpfw_json_search_products_callback() {
        check_ajax_referer( 'dslpfw-search-products', 'security' );
        $search = filter_input( INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $search = ( !empty( $search ) ? sanitize_text_field( wc_clean( wp_unslash( $search ) ) ) : '' );
        if ( empty( $search ) ) {
            wp_send_json_error( esc_html__( 'No search term provided.', 'local-pickup-for-woocommerce' ) );
        }
        $posts_per_page = filter_input( INPUT_GET, 'posts_per_page', FILTER_VALIDATE_INT );
        $posts_per_page = ( !empty( $posts_per_page ) ? intval( $posts_per_page ) : 0 );
        $offset = filter_input( INPUT_GET, 'offset', FILTER_VALIDATE_INT );
        $offset = ( !empty( $offset ) ? intval( $offset ) : 1 );
        $display_pid = filter_input( INPUT_GET, 'display_pid', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $display_pid = ( !empty( $display_pid ) && 'true' === $display_pid ? true : false );
        $args = array(
            'post_type' => 'product',
            's'         => $search,
            'number'    => $posts_per_page,
            'offset'    => $posts_per_page * ($offset - 1),
            'orderby'   => 'title',
            'order'     => 'ASC',
        );
        add_filter(
            'posts_where',
            array($this, 'dslpfw_posts_where'),
            10,
            2
        );
        $products = new WP_Query($args);
        remove_filter(
            'posts_where',
            array($this, 'dslpfw_posts_where'),
            10,
            2
        );
        $results = array();
        if ( !empty( $products->posts ) && count( $products->posts ) > 0 ) {
            foreach ( $products->posts as $product ) {
                /* translators: Placeholders: %1$s - product name, %2$d product ID */
                $results[$product->ID] = ( $display_pid ? sprintf( esc_html__( '%1$s(#%2$d)', 'local-pickup-for-woocommerce' ), rawurldecode( wp_strip_all_tags( get_the_title( $product->ID ) ) ), $product->ID ) : rawurldecode( wp_strip_all_tags( get_the_title( $product->ID ) ) ) );
            }
        }
        wp_send_json( $results );
    }

    /**
     * Search product by title in admin
     * 
     * @since    1.0.0
     */
    public function dslpfw_posts_where( $where, $wp_query ) {
        global $wpdb;
        $search_term = $wp_query->get( 'search_pro_title' );
        if ( !empty( $search_term ) ) {
            $search_term_like = $wpdb->esc_like( $search_term );
            $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( $search_term_like ) . '%\'';
        }
        return $where;
    }

    /**
     * Export pickup locations data funcitonality
     *
     * @since  1.0.0
     * 
     */
    public function dslpfw_export_pickup_locations_action_callback() {
        WP_Filesystem();
        global $wp_filesystem;
        //Check ajax nonce reference
        check_ajax_referer( 'dslpfw-export-action-nonce', 'security' );
        $export_type = filter_input( INPUT_POST, 'export_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $export_type = ( !empty( $export_type ) ? sanitize_text_field( $export_type ) : 'json' );
        $file_path = $this->get_file_path() . $this->get_file_name() . '.csv';
        if ( empty( $pickup_location_ids ) ) {
            $pickup_location_ids = dslpfw_get_pickup_locations( array(
                'post_status'    => 'any',
                'fields'         => 'ids',
                'posts_per_page' => -1,
            ) );
        }
        if ( !empty( $pickup_location_ids ) ) {
            //Get post meta data
            $data = $this->export_pickup_locations_data( $pickup_location_ids );
            //File path where our csv./json file will be saved
            $file_path = $this->get_file_path();
            if ( 'csv' === $export_type ) {
                //Create CSV file
                $csv_data = array();
                //Header Data
                $csv_headers = $this->get_csv_headers();
                $csv_data[] = array_values( $csv_headers );
                //Prepare row data
                foreach ( $data as $single_data ) {
                    $csv_data[] = array_values( $single_data );
                }
                // File name
                $file_name = $this->get_file_name() . '.csv';
                //Remove all previous CSV files
                $files = glob( "{$file_path}/*.csv" );
                foreach ( $files as $csv_file ) {
                    wp_delete_file( $csv_file );
                }
                // Open the CSV file for writing
                $csv_file = fopen( $file_path . $file_name, 'w' );
                //phpcs:ignore
                // Write data to the CSV file
                foreach ( $csv_data as $row ) {
                    fputcsv( $csv_file, $row );
                    //phpcs:ignore
                }
                // Close the CSV file
                fclose( $csv_file );
                //phpcs:ignore
                wp_send_json_success( array(
                    'message'       => esc_html__( 'Data has been Exported in CSV!', 'local-pickup-for-woocommerce' ),
                    'download_path' => $this->get_file_path( 'download' ) . $file_name,
                ) );
            } else {
                $file_name = $this->get_file_name() . '.json';
                //Remove all previous JSON files
                $files = glob( "{$file_path}/*.json" );
                foreach ( $files as $csv_file ) {
                    wp_delete_file( $csv_file );
                }
                //Create JSON file
                $json_data = wp_json_encode( $data );
                //Save new data to JSON file
                $wp_filesystem->put_contents( $file_path . $file_name, $json_data );
                wp_send_json_success( array(
                    'message'       => esc_html__( 'Data has been Exported in JSON!', 'local-pickup-for-woocommerce' ),
                    'download_path' => $this->get_file_path( 'download' ) . $file_name,
                ) );
            }
        } else {
            // tell the user there were no Pickup Locations to export
            wp_send_json_error( array(
                'message' => esc_html__( 'No Pickup Locations found matching the criteria to export.', 'local-pickup-for-woocommerce' ),
            ) );
        }
    }

    /**
     * Get the export file name.
     *
     * @since 1.0.0
     *
     * @return string
     */
    private function get_file_name() {
        // file name default: blog_name_pickup_locations_YYYY_MM_DD_HH_MM_SS
        $file_name = str_replace( '-', '_', sanitize_file_name( strtolower( get_bloginfo( 'name' ) . '_pickup_locations_' . date_i18n( 'Y_m_d_H_i_s', time() ) ) ) );
        /**
         * Filter the pickup locations export file name.
         *
         * @since 1.0.0
         *
         * @param string $file_name the CSV file name
         */
        return apply_filters( 'dslpfw_export_pickup_locations_file_name', $file_name );
    }

    /**
     * Get CSV file headers.
     *
     * @since 1.0.0
     *
     * @return array
     */
    private function get_csv_headers() {
        $headers = array(
            'id'          => 'id',
            'status'      => 'status',
            'name'        => 'name',
            'country'     => 'country',
            'postcode'    => 'postcode',
            'state'       => 'state',
            'city'        => 'city',
            'address_1'   => 'address_1',
            'address_2'   => 'address_2',
            'phone'       => 'phone',
            'description' => 'description',
        );
        /**
         * Filter the Pickup Locations CSV export file row headers.
         *
         * @since 1.0.0
         *
         * @param array $csv_headers Associative array
         */
        return (array) apply_filters( 'dslpfw_export_pickup_locations_headers', $headers );
    }

    /**
     * Get file path.
     *
     * @since 1.0.0
     *
     * @param string $type save|download
     * 
     * @return string
     */
    private function get_file_path( $type = 'save' ) {
        $return_path = '';
        //File path details
        $path_data = wp_get_upload_dir();
        if ( 'save' === $type ) {
            $return_path = $path_data['basedir'] . '/dslpfw_export_data/';
            // Create directory if not exists
            if ( !file_exists( $return_path ) ) {
                mkdir( $return_path, 0777, true );
                //phpcs:ignore
            }
        } else {
            $return_path = $path_data['baseurl'] . '/dslpfw_export_data/';
        }
        return $return_path;
    }

    /**
     * Get file path.
     *
     * @since 1.0.0
     *
     * @param int[] $pickup_location_ids array of \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location IDs
     * 
     * @return string
     */
    private function export_pickup_locations_data( array $pickup_location_ids ) {
        $data = array();
        foreach ( $pickup_location_ids as $pickup_location_id ) {
            $pickup_location = dslpfw_get_pickup_location( $pickup_location_id );
            if ( $pickup_location->get_id() > 0 ) {
                $single_data = array();
                $headers = $this->get_csv_headers();
                $columns = array_keys( $headers );
                // Get all data for this pickup location
                if ( !empty( $columns ) ) {
                    foreach ( $columns as $column_name ) {
                        switch ( $column_name ) {
                            case 'id':
                            case 'name':
                                $method = "get_{$column_name}";
                                $value = ( method_exists( $pickup_location, $method ) ? $pickup_location->{$method}() : '' );
                                break;
                            case 'description':
                                $method = "get_{$column_name}";
                                $value = ( method_exists( $pickup_location, $method ) ? html_entity_decode( $pickup_location->{$method}() ) : '' );
                                break;
                            case 'status':
                                $post = $pickup_location->get_post();
                                $value = ( $post instanceof \WP_Post ? $post->post_status : '' );
                                break;
                            case 'country':
                            case 'state':
                            case 'city':
                            case 'postcode':
                            case 'address_1':
                            case 'address_2':
                                $value = $pickup_location->get_address( $column_name );
                                break;
                            case 'phone':
                                $value = $pickup_location->get_phone();
                                break;
                            case 'products':
                            case 'product_categories':
                                break;
                            case 'pickup_hours':
                                break;
                            case 'fee_adjustment':
                            case 'pickup_lead_time':
                            case 'pickup_deadline':
                                break;
                            case 'holiday_dates':
                                break;
                            case 'products_status':
                            case 'categories_status':
                            case 'fee_adjustment_status':
                            case 'pickup_hours_status':
                            case 'holiday_dates_status':
                            case 'lead_time_status':
                            case 'deadline_status':
                                break;
                            default:
                                /**
                                 * Filter Pickup Location CSV data custom column.
                                 *
                                 * @since 1.0.0
                                 *
                                 * @param string $value the value that should be returned for this column, default empty string
                                 * @param string $key the matching key of this column
                                 * @param \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $pickup_location pickup location object
                                 */
                                $value = apply_filters(
                                    "dslpfw_export_pickup_locations_{$column_name}_column",
                                    '',
                                    $column_name,
                                    $pickup_location
                                );
                                break;
                        }
                        $single_data[$column_name] = $value;
                    }
                }
                if ( !empty( $single_data ) ) {
                    foreach ( $headers as $header_key ) {
                        if ( !isset( $single_data[$header_key] ) ) {
                            $single_data[$header_key] = '';
                        }
                        $value = '';
                        // strict string comparison, as values like '0' are valid
                        if ( '' !== $single_data[$header_key] ) {
                            $value = $single_data[$header_key];
                        }
                        // escape spreadsheet sensitive characters with a single quote, to prevent CSV injections, by prepending a single quote `'`.
                        $data[$pickup_location->get_id()][$header_key] = $this->escape_value( $value );
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Escape sensitive characters with a single quote, to prevent CSV injections.
     *
     * @since 1.0.0
     *
     * @param string|mixed $value
     * @return string|mixed
     */
    private function escape_value( $value ) {
        if ( is_string( $value ) ) {
            $first_char = ( isset( $value[0] ) ? $value[0] : '' );
            if ( '' !== $first_char && in_array( $first_char, array(
                '=',
                '+',
                '-',
                '@'
            ), true ) ) {
                $value = "'{$value}";
            }
        }
        return $value;
    }

    /**
     * Unescape a string that may have been escaped with slashes, a single quote or back tick.
     *
     * @since 1.0.0
     *
     * @param string|mixed $value
     * @return string|mixed
     */
    private function unescape_value( $value ) {
        $first_char = ( is_string( $value ) && isset( $value[0] ) ? $value[0] : '' );
        if ( '' !== $first_char && in_array( $first_char, array("'", '`', "\\'"), true ) ) {
            $value = substr( $value, 1 );
        }
        return ( is_string( $value ) ? trim( stripslashes( $value ) ) : $value );
    }

    /**
     * Import pickup locations data funcitonality
     *
     * @since  1.0.0
     * 
     */
    public function dslpfw_import_pickup_locations_action_callback() {
        WP_Filesystem();
        global $wp_filesystem;
        //Check ajax nonce reference
        check_ajax_referer( 'dslpfw-import-action-nonce', 'security' );
        $import_type = filter_input( INPUT_POST, 'import_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $file_import_file_args = array(
            'import_file' => array(
                'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'flags'  => FILTER_FORCE_ARRAY,
            ),
        );
        //We are using filter_var_array to get the file data because it is $_FILES array not $_GET or $_POST.
        $attached_import_files_arr = filter_var_array( $_FILES, $file_import_file_args );
        if ( isset( $attached_import_files_arr['import_file']['error'] ) && $attached_import_files_arr['import_file']['error'] > 0 ) {
            /* translators: Placeholders: %s - import file error while uploading */
            wp_send_json_error( array(
                'message' => sprintf( esc_html__( 'There was a problem uploading the file: %s', 'local-pickup-for-woocommerce' ), '<em>' . esc_html( $this->get_file_upload_error( $attached_import_files_arr['import_file']['error'] ) ) . '</em>' ),
            ) );
        }
        $import_file = $attached_import_files_arr['import_file']['tmp_name'];
        if ( empty( $import_file ) ) {
            wp_send_json_error( array(
                'message' => esc_html__( 'Please upload a file to import', 'local-pickup-for-woocommerce' ),
            ) );
        }
        $attached_import_files_arr_explode = explode( '.', $attached_import_files_arr['import_file']['name'] );
        $extension = end( $attached_import_files_arr_explode );
        if ( $extension !== $import_type ) {
            /* translators: Placeholders: %s - import file type (extension) name */
            wp_send_json_error( array(
                'message' => sprintf( esc_html__( 'Please upload a valid %s extension file', 'local-pickup-for-woocommerce' ), strtoupper( $import_type ) ),
            ) );
        }
        // Initialize the result array
        $dslpfw_rule_data_decoded = array();
        //Check type of import file and prepare array from data
        if ( 'csv' === $import_type ) {
            $csv_data = $this->parse_file_csv( $import_file );
            array_shift( $csv_data );
            // The first row of the CSV file can be used as keys (header)
            $csv_header = array_values( $this->get_csv_headers() );
            // Loop through the CSV data and create an associative array
            foreach ( $csv_data as $row ) {
                if ( false !== $row ) {
                    $dslpfw_rule_data_decoded[] = array_combine( $csv_header, $row );
                }
            }
        }
        if ( 'json' === $import_type ) {
            //Fetch data from file (this same method we have not use in CSV as it conflict HTML in data)
            $dslpfw_rule_data = $wp_filesystem->get_contents( $import_file );
            $dslpfw_rule_data_decoded = json_decode( $dslpfw_rule_data, true );
        }
        if ( !empty( $dslpfw_rule_data_decoded ) ) {
            foreach ( $dslpfw_rule_data_decoded as $pickup_location_data ) {
                $dslpfw_pickup_location_args = array(
                    'post_title'   => $pickup_location_data['name'],
                    'post_status'  => $pickup_location_data['status'],
                    'post_content' => wp_kses_post( $pickup_location_data['description'] ),
                    'post_type'    => DSLPFW_POST_TYPE,
                );
                $fount_post = post_exists(
                    $pickup_location_data['name'],
                    '',
                    '',
                    DSLPFW_POST_TYPE
                );
                if ( $fount_post > 0 && !empty( $fount_post ) ) {
                    $dslpfw_pickup_location_args['ID'] = $fount_post;
                    $pickup_location_id = wp_update_post( $dslpfw_pickup_location_args );
                } else {
                    $pickup_location_id = wp_insert_post( $dslpfw_pickup_location_args );
                }
                $pickup_location = dslpfw_get_pickup_location( $pickup_location_id );
                if ( $pickup_location ) {
                    $pickup_location = $this->import_pickup_location_data( $pickup_location, $pickup_location_data );
                }
            }
        } else {
            /* translators: Placeholders: %s - import file type (extension) name */
            wp_send_json_error( array(
                'message' => sprintf( esc_html__( 'Blank  %s file uploaded.', 'local-pickup-for-woocommerce' ), strtoupper( $import_type ) ),
            ) );
        }
        wp_send_json_success( array(
            'message' => esc_html__( 'Pickup location data has been Imported!', 'local-pickup-for-woocommerce' ),
        ) );
    }

    /**
     * Get an error message for file upload failure.
     *
     * @see https://php.net/manual/en/features.file-upload.errors.php
     *
     * @since 1.0.0
     *
     * @param int $error_code a PHP error code
     * @return string error message
     */
    private function get_file_upload_error( $error_code ) {
        switch ( $error_code ) {
            case 1:
            case 2:
                return esc_html__( 'The file uploaded exceeds the maximum file size allowed.', 'local-pickup-for-woocommerce' );
            case 3:
                return esc_html__( 'The file was only partially uploaded. Please try again.', 'local-pickup-for-woocommerce' );
            case 4:
                return esc_html__( 'No file was uploaded.', 'local-pickup-for-woocommerce' );
            case 6:
                return esc_html__( 'Missing a temporary folder to store the file. Please contact your host.', 'local-pickup-for-woocommerce' );
            case 7:
                return esc_html__( 'Failed to write file to disk. Perhaps a permissions error, please contact your host.', 'local-pickup-for-woocommerce' );
            case 8:
                return esc_html__( 'A PHP Extension stopped the file upload. Please contact your host.', 'local-pickup-for-woocommerce' );
            default:
                return esc_html__( 'Unknown error.', 'local-pickup-for-woocommerce' );
        }
    }

    /**
     * Parse a file with CSV data into an array.
     *
     * @since 1.0.0
     *
     * @param resource $file_handle file to process as a resource
     * @return null|array array data or null on read error
     */
    private function parse_file_csv( $file_handle ) {
        if ( is_readable( $file_handle ) ) {
            $csv_data = array();
            // get the data from file
            $file_contents = fopen( $file_handle, 'r' );
            // phpcs:ignore
            // handle character encoding
            $enc = mb_detect_encoding( $file_handle, 'UTF-8, ISO-8859-1', true );
            if ( $enc ) {
                setlocale( LC_ALL, 'en_US.' . $enc );
            }
            while ( !feof( $file_contents ) ) {
                $row = fgetcsv( $file_contents );
                $csv_data[] = $row;
            }
            fclose( $file_contents );
            //phpcs:ignore
            return $csv_data;
        }
        return null;
    }

    /**
     * Update pickup location data.
     *
     * @since 1.0.0
     *
     * @param \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $pickup_location the pickup location being updated
     * @param array $import_data associative array with import data
     * @return null|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location
     */
    private function import_pickup_location_data( \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $pickup_location, array $import_data ) {
        // bail out if there's nothing to import
        if ( empty( $import_data ) ) {
            return null;
        }
        $ds_local_pickup = dslpfw_shipping_method();
        if ( $ds_local_pickup && $pickup_location instanceof \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location ) {
            $location_name = ( is_string( $import_data['name'] ) ? sanitize_text_field( $this->unescape_value( $import_data['name'] ) ) : '' );
            // import address
            $pickup_location->set_address( wp_parse_args( array(
                'name'      => $location_name,
                'address_1' => ( isset( $import_data['address_1'] ) ? sanitize_text_field( $this->unescape_value( $import_data['address_1'] ) ) : '' ),
                'address_2' => ( isset( $import_data['address_2'] ) ? sanitize_text_field( $this->unescape_value( $import_data['address_2'] ) ) : '' ),
                'postcode'  => ( isset( $import_data['postcode'] ) ? sanitize_text_field( $this->unescape_value( $import_data['postcode'] ) ) : '' ),
                'city'      => ( isset( $import_data['city'] ) ? sanitize_text_field( $this->unescape_value( $import_data['city'] ) ) : '' ),
                'state'     => ( isset( $import_data['state'] ) ? strtoupper( sanitize_text_field( $this->unescape_value( $import_data['state'] ) ) ) : '' ),
                'country'   => ( isset( $import_data['country'] ) ? strtoupper( sanitize_text_field( $this->unescape_value( $import_data['country'] ) ) ) : '' ),
            ), $pickup_location->get_address()->get_array() ) );
            // import location phone number
            $phone = ( !empty( $import_data['phone'] ) ? sanitize_text_field( $this->unescape_value( $import_data['phone'] ) ) : null );
            if ( is_string( $phone ) && '' !== $phone ) {
                $pickup_location->set_phone( $phone );
            } else {
                $pickup_location->delete_phone();
            }
        }
        return $pickup_location;
    }

    /**
     * Get a special composite field for handling order shipping item pickup data.
     *
     * @since 1.0.0
     *
     * @param int $item_id order shipping item ID
     * @param array $item order shipping item array
     */
    public function dslpfw_show_order_shipping_item_pickup_data( $item_id, $item ) {
        global $post, $theorder;
        if ( dslpfw()->dslpfw_is_hpos_enabled() ) {
            $order = $theorder;
        } else {
            $order = wc_get_order( $post );
        }
        $get_order_id = filter_input( INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT );
        $order_id = ( isset( $get_order_id ) && !empty( $get_order_id ) ? intval( $get_order_id ) : 0 );
        if ( empty( $order ) && !empty( $order_id ) && wp_doing_ajax() ) {
            $order = wc_get_order( $order_id );
        }
        $shipping_method = $item['method_id'] ?? null;
        //Check order must be simple order not subscription and must be contain shipping method as our Local Pickup
        if ( $order instanceof \WC_Order && (!class_exists( 'WC_Subscription' ) || !$order instanceof \WC_Subscription) && dslpfw_shipping_method_id() === $shipping_method ) {
            $ds_local_pickup = dslpfw();
            $items_to_choose = $order->get_items();
            $items_to_pickup = ( !empty( $item['_dslpfw_pickup_items'] ) ? array_map( 'absint', maybe_unserialize( $item['_dslpfw_pickup_items'] ) ) : [] );
            $appointment = $ds_local_pickup->get_dslpfw_appointments_object()->get_shipping_item_appointment( $item_id );
            $pickup_date = ( $appointment ? $appointment->get_start() : null );
            $appointments_mode = $ds_local_pickup->dslpfw_get_shipping_method_instance()->pickup_appointments_mode();
            ?>
            <div id="dslpfw-order-shipping-item-pickup-data-<?php 
            echo esc_attr( $item_id );
            ?>" class="dslpfw-order-shipping-item-pickup-data view">
                <table class="display_meta">
                    <tbody>
                        <tr>
                            <th>
                                <?php 
            esc_html_e( 'Location:', 'local-pickup-for-woocommerce' );
            ?>
                            </th>
                            <td class="pickup-location">
                                <p><?php 
            echo esc_html( $ds_local_pickup->get_dslpfw_orders_object()->get_dslpfw_order_items_object()->get_order_item_pickup_location_name( $item_id ) );
            ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <?php 
            esc_html_e( 'Address:', 'local-pickup-for-woocommerce' );
            ?>
                            </th>
                            <td class="pickup-address">
                                <?php 
            echo esc_html( $ds_local_pickup->get_dslpfw_orders_object()->get_dslpfw_order_items_object()->get_order_item_pickup_location_address( $item_id, 'plain' ) );
            ?>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <?php 
            esc_html_e( 'Phone:', 'local-pickup-for-woocommerce' );
            ?>
                            </th>
                            <td class="pickup-phone">
                                <?php 
            echo wp_kses_post( $ds_local_pickup->get_dslpfw_orders_object()->get_dslpfw_order_items_object()->get_order_item_pickup_location_phone( $item_id, true ) );
            ?>
                            </td>
                        </tr>

                        <?php 
            // display appointment information if appointments are not disabled
            ?>
						<?php 
            if ( 'disabled' !== $appointments_mode ) {
                ?>

							<tr>
								<th>
                                    <label>
                                        <?php 
                esc_html_e( 'Date:', 'local-pickup-for-woocommerce' );
                ?>
                                    </label>
                                </th>
								<td class="pickup-date">
									<div class="value">
										<?php 
                if ( $pickup_date && ($appointment && $appointment->is_anytime()) ) {
                    ?>
											<?php 
                    echo esc_html( date_i18n( wc_date_format(), $pickup_date->getTimestamp() + $pickup_date->getOffset() ) );
                    ?>
										<?php 
                } elseif ( $pickup_date ) {
                    ?>
											<?php 
                    /* translators: %1$s - the formatted date, %2$s - the formatted time */
                    echo esc_html( sprintf( esc_html__( '%1$s at %2$s', 'local-pickup-for-woocommerce' ), date_i18n( wc_date_format(), $pickup_date->getTimestamp() + $pickup_date->getOffset() ), date_i18n( wc_time_format(), $pickup_date->getTimestamp() + $pickup_date->getOffset() ) ) );
                    ?>
										<?php 
                } else {
                    ?>
											&mdash;
										<?php 
                }
                ?>
									</div>
								</td>
							</tr>

						<?php 
            }
            ?>

                        <tr>
							<th>
                                <label>
                                    <?php 
            esc_html_e( 'Items:', 'local-pickup-for-woocommerce' );
            ?>
                                </label>
                            </th>
							<td class="pickup-items">
								<div class="value">
									<?php 
            $items = [];
            ?>
									<?php 
            foreach ( $items_to_choose as $id => $item_data ) {
                ?>
										<?php 
                if ( isset( $item_data['name'], $item_data['qty'] ) && in_array( $id, $items_to_pickup, true ) ) {
                    ?>
											<?php 
                    $items[] = ( is_rtl() ? '&times; ' . $item_data['qty'] . ' ' . $item_data['name'] : $item_data['name'] . ' &times; ' . $item_data['qty'] );
                    ?>
										<?php 
                }
                ?>
									<?php 
            }
            ?>
									<?php 
            echo ( !empty( $items ) ? esc_html( implode( ', ', $items ) ) : '&mdash;' );
            ?>
								</div>
							</td>
						</tr>
                    </tbody>
                </table>
            </div>
            <?php 
        }
    }

    /**
     * Add export pickup details bulk action to the orders list table
     * 
     * @param array $bulk_actions
     * @return array
     * 
     * @since 1.1.2
     */
    public function dslpfw_add_export_bulk_actions( $bulk_actions ) {
        $bulk_actions['export_pickup_details'] = esc_html__( 'Export Pickup Details', 'local-pickup-for-woocommerce' );
        return $bulk_actions;
    }

    /**
     * Handle export pickup details bulk action
     * 
     * @param string $redirect_to
     * @param string $action
     * @param array $post_ids
     * @return string
     * 
     * @since 1.1.2
     */
    public function dslpfw_handle_export_bulk_action( $redirect_to, $action, $post_ids ) {
        if ( $action !== 'export_pickup_details' ) {
            return $redirect_to;
        }
        // Security check
        if ( !current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions', 'local-pickup-for-woocommerce' ) );
        }
        // CSV Headers
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=pickup-orders-export-' . gmdate( 'Y-m-d' ) . '.csv' );
        $output = fopen( 'php://output', 'w' );
        $separator = ",";
        // Using comma as default delimiter for international compatibility
        $enclosure = '"';
        // Using double quotes as default enclosure for international compatibility
        $escape = '\\';
        // Using backslash as default escape character for international compatibility
        $dslpfw_pickup_details_headers = apply_filters( 'dslpfw_pickup_details_headers', array(
            'Order Number',
            'Customer Email',
            'Pickup Location',
            'Pickup Date and Time',
            'Products List (product: quantity)'
        ) );
        // CSV Column headers
        fputcsv(
            $output,
            $dslpfw_pickup_details_headers,
            $separator,
            $enclosure,
            $escape
        );
        // Process each selected order
        foreach ( $post_ids as $post_id ) {
            $order = wc_get_order( $post_id );
            if ( !$order ) {
                continue;
            }
            $shipping_items = $order->get_items( 'shipping' );
            $shipping_item = reset( $shipping_items );
            if ( $shipping_item ) {
                $shipping_item_id = $shipping_item->get_id();
                // Get pickup metadata
                $pickup_location = wc_get_order_item_meta( $shipping_item_id, '_dslpfw_pickup_location_name' );
                $pickup_end = wc_get_order_item_meta( $shipping_item_id, '_dslpfw_pickup_appointment_end' );
                // Format pickup end date
                $pickup_date = ( $pickup_end ? gmdate( 'Y-m-d H:i', $pickup_end ) : '' );
                // Get and format products with quantities
                $products_with_quantities = array();
                foreach ( $order->get_items() as $item ) {
                    $products_with_quantities[] = $item->get_name() . ' : ' . $item->get_quantity();
                }
                $products_list = implode( " | ", $products_with_quantities );
                $dslpfw_pickup_details_data = apply_filters(
                    'dslpfw_pickup_details_data',
                    array(
                        $order->get_id(),
                        $order->get_billing_email(),
                        $pickup_location,
                        $pickup_date,
                        $products_list
                    ),
                    $order,
                    $shipping_item_id
                );
                // Write the CSV line
                fputcsv(
                    $output,
                    $dslpfw_pickup_details_data,
                    $separator,
                    $enclosure,
                    $escape
                );
            }
        }
        fclose( $output );
        // phpcs:ignore
        exit;
    }

    /**
     * Check if the checkout page is a block based checkout page
     * 
     * @return bool
     * 
     * @since 1.1.2
     */
    public function dslpfw_is_block_checkout() {
        $checkout_page_id = get_option( 'woocommerce_checkout_page_id' );
        if ( !$checkout_page_id ) {
            return false;
        }
        $content = get_post_field( 'post_content', $checkout_page_id );
        return has_block( 'woocommerce/checkout', $content );
    }

    /**
     * Check if the cart page is a block based cart page
     * 
     * @return bool
     * 
     * @since 1.1.2
     */
    public function dslpfw_is_block_cart() {
        $cart_page_id = get_option( 'woocommerce_cart_page_id' );
        if ( !$cart_page_id ) {
            return false;
        }
        $content = get_post_field( 'post_content', $cart_page_id );
        return has_block( 'woocommerce/cart', $content );
    }

    /**
     * Add a notice to the admin screen if the checkout or cart page is a block based page
     * 
     * @since 1.1.2
     */
    public function dslpfw_add_block_based_cart_checkout_notice() {
        if ( strpos( get_current_screen()->id, 'dslpfw' ) !== false && ($this->dslpfw_is_block_checkout() || $this->dslpfw_is_block_cart()) ) {
            ?>
            <div class="error notice is-dismissible">
                <p><?php 
            echo sprintf(
                esc_html__( '%2$s%1$s for WooCommerce%3$s does not support block based cart and checkout page. This may impact the shopper experience. Please use the default shortcode based cart and checkout page.', 'local-pickup-for-woocommerce' ),
                esc_html( DSLPFW_PLUGIN_NAME ),
                '<strong>',
                '</strong>'
            );
            ?></p>
            </div>
            <?php 
        }
    }

}
