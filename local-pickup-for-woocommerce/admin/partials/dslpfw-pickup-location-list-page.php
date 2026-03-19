<?php

/**
 * Provide a pickup location view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.thedotstore.com/
 * @since      1.0.0
 *
 * @package    DSLPFW_Local_Pickup_Woocommerce
 * @subpackage DSLPFW_Local_Pickup_Woocommerce/admin/partials
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * DSLPFW_Pickup_Location_Listing class.
 */
if ( !class_exists( 'DSLPFW_Pickup_Location_Listing' ) ) {
    class DSLPFW_Pickup_Location_Listing {
        /** @var string address post meta key name */
        protected static $address_meta = 'dslpfw_pickup_location';

        /**
         * Display output
         *
         * @since 1.0.0
         *
         * @uses dslpfw_save_method
         * @uses dslpfw_add_rule_form
         * @uses dslpfw_delete_method
         * @uses dslpfw_duplicate_method
         * @uses dslpfw_list_methods_screen
         *
         * @access   public
         */
        public static function dslpfw_listing_output() {
            $action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $post_id_request = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
            if ( isset( $action ) && !empty( $action ) ) {
                if ( 'add' === $action ) {
                    self::dslpfw_save_method();
                    self::dslpfw_add_rule_form();
                } elseif ( 'edit' === $action ) {
                    self::dslpfw_save_method( $post_id_request );
                    self::dslpfw_add_rule_form();
                } elseif ( 'delete' === $action ) {
                    self::dslpfw_delete_method( $post_id_request );
                } elseif ( 'duplicate' === $action ) {
                    self::dslpfw_duplicate_method( $post_id_request );
                } else {
                    self::dslpfw_list_methods_screen();
                }
            } else {
                self::dslpfw_list_methods_screen();
            }
        }

        /**
         * Save pickup location data
         *
         * @param int $post_id
         * @since    1.0.0
         *
         */
        public static function dslpfw_save_method( $post_id = 0 ) {
            $dslpfw_rule_save = filter_input( INPUT_POST, 'dslpfw_rule_save', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            //It will only work for after add new rule save value
            if ( wp_verify_nonce( sanitize_text_field( $dslpfw_rule_save ), 'dslpfw_rule_save_nonce' ) ) {
                //Get datas
                $get_dslpfw_pickup_location_status = filter_input( INPUT_POST, 'dslpfw_pickup_location_status', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                $get_dslpfw_pickup_location_title = filter_input( INPUT_POST, 'dslpfw_pickup_location_title', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                $get_dslpfw_pickup_location_address_1 = filter_input( INPUT_POST, 'dslpfw_pickup_location_address_1', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                $get_dslpfw_pickup_location_address_2 = filter_input( INPUT_POST, 'dslpfw_pickup_location_address_2', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                $get_dslpfw_pickup_location_city = filter_input( INPUT_POST, 'dslpfw_pickup_location_city', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                $get_dslpfw_pickup_location_country_state = filter_input( INPUT_POST, 'dslpfw_pickup_location_country_state', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                $get_dslpfw_pickup_location_postcode = filter_input( INPUT_POST, 'dslpfw_pickup_location_postcode', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                $get_dslpfw_pickup_location_phone = filter_input( INPUT_POST, 'dslpfw_pickup_location_phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                $get_dslpfw_pickup_location_note = filter_input( INPUT_POST, 'dslpfw_pickup_location_note', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                //Sanitize and validate
                $dslpfw_pickup_location_status = ( !empty( $get_dslpfw_pickup_location_status ) ? sanitize_text_field( $get_dslpfw_pickup_location_status ) : 'off' );
                $dslpfw_pickup_location_title = ( !empty( $get_dslpfw_pickup_location_title ) ? sanitize_text_field( $get_dslpfw_pickup_location_title ) : '' );
                $dslpfw_pickup_location_note = ( !empty( $get_dslpfw_pickup_location_note ) ? wp_kses_post( $get_dslpfw_pickup_location_note ) : '' );
                // These are used below for dyanmic variable to preapre array of address and store it
                //phpcs:disable
                $dslpfw_pickup_location_address_1 = ( !empty( $get_dslpfw_pickup_location_address_1 ) ? sanitize_text_field( $get_dslpfw_pickup_location_address_1 ) : '' );
                $dslpfw_pickup_location_address_2 = ( !empty( $get_dslpfw_pickup_location_address_2 ) ? sanitize_text_field( $get_dslpfw_pickup_location_address_2 ) : '' );
                $dslpfw_pickup_location_city = ( !empty( $get_dslpfw_pickup_location_city ) ? sanitize_text_field( $get_dslpfw_pickup_location_city ) : '' );
                $dslpfw_pickup_location_postcode = ( !empty( $get_dslpfw_pickup_location_postcode ) ? sanitize_text_field( $get_dslpfw_pickup_location_postcode ) : '' );
                //phpcs:enable
                $dslpfw_pickup_location_phone = ( !empty( $get_dslpfw_pickup_location_phone ) ? sanitize_text_field( $get_dslpfw_pickup_location_phone ) : '' );
                // Seperate country and state
                //phpcs:disable
                $pieces = ( !empty( $get_dslpfw_pickup_location_country_state ) ? explode( ':', $get_dslpfw_pickup_location_country_state ) : array() );
                $dslpfw_pickup_location_country = ( isset( $pieces[0] ) ? sanitize_text_field( $pieces[0] ) : '' );
                $dslpfw_pickup_location_state = ( isset( $pieces[1] ) ? sanitize_text_field( $pieces[1] ) : '' );
                if ( isset( $dslpfw_pickup_location_status ) && !empty( $dslpfw_pickup_location_status ) && "on" === $dslpfw_pickup_location_status ) {
                    $post_status = 'publish';
                } else {
                    $post_status = 'draft';
                }
                $dslpfw_count = self::dslpfw_count_method();
                if ( '' === $post_id || 0 === $post_id ) {
                    $dslpfw_args = array(
                        'post_title'   => wp_strip_all_tags( $dslpfw_pickup_location_title ),
                        'post_content' => $dslpfw_pickup_location_note,
                        'post_status'  => $post_status,
                        'post_type'    => DSLPFW_POST_TYPE,
                        'menu_order'   => $dslpfw_count + 1,
                    );
                    $post_id = wp_insert_post( $dslpfw_args );
                    $message_type = 'created';
                } else {
                    $dslpfw_args = array(
                        'ID'           => intval( $post_id ),
                        'post_title'   => wp_strip_all_tags( $dslpfw_pickup_location_title ),
                        'post_content' => $dslpfw_pickup_location_note,
                        'post_status'  => $post_status,
                        'post_type'    => DSLPFW_POST_TYPE,
                    );
                    $post_id = wp_update_post( $dslpfw_args );
                    $message_type = 'saved';
                }
                if ( '' !== $post_id && $post_id > 0 ) {
                    $pickup_location = new \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location($post_id);
                    update_post_meta( $post_id, 'dslpfw_pickup_location_status', $dslpfw_pickup_location_status );
                    //Address store
                    $pieces = self::get_address_pieces();
                    $keys = ( !empty( $pieces ) ? array_keys( $pieces ) : array() );
                    $address = array();
                    foreach ( $keys as $key ) {
                        // all other address pieces
                        $address[$key] = ${self::$address_meta . '_' . $key};
                    }
                    $pickup_location->set_address( $address );
                    //Phone store
                    $pickup_location->set_phone( $dslpfw_pickup_location_phone );
                }
                wp_safe_redirect( add_query_arg( array(
                    'page'    => 'dslpfw-pickup-location-list',
                    'action'  => 'edit',
                    'id'      => $post_id,
                    'message' => $message_type,
                ), admin_url( 'admin.php' ) ) );
                exit;
            }
        }

        /**
         * Count total pickup location
         *
         * @return int $dslpfw_list
         * @since    1.0.0
         *
         */
        public static function dslpfw_count_method() {
            $dslpfw_args = array(
                'post_type'      => DSLPFW_POST_TYPE,
                'post_status'    => array('publish', 'draft'),
                'posts_per_page' => -1,
            );
            $dslpfw_query = new WP_Query($dslpfw_args);
            $dslpfw_list = $dslpfw_query->posts;
            return count( $dslpfw_list );
        }

        /**
         * Add pickup location data
         *
         * @since    1.0.0
         */
        public static function dslpfw_add_rule_form() {
            require_once plugin_dir_path( __FILE__ ) . 'dslpfw-pickup-location-add-new.php';
        }

        /**
         * dslpfw_list_methods_screen function.
         *
         * @since    1.0.0
         *
         * @uses DSLPFW_Pickup_Location_List_Table class
         * @uses DSLPFW_Pickup_Location_List_Table::process_bulk_action()
         * @uses DSLPFW_Pickup_Location_List_Table::prepare_items()
         * @uses DSLPFW_Pickup_Location_List_Table::search_box()
         * @uses DSLPFW_Pickup_Location_List_Table::display()
         *
         * @access public
         *
         */
        public static function dslpfw_list_methods_screen() {
            if ( !class_exists( 'DSLPFW_Pickup_Location_List_Table' ) ) {
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'list-tables/class-attribute-stock-list-table.php';
            }
            $link = add_query_arg( array(
                'page'   => 'dslpfw-pickup-location-list',
                'action' => 'add',
            ), admin_url( 'admin.php' ) );
            require_once plugin_dir_path( __FILE__ ) . 'header/plugin-header.php';
            ?>
                <form method="post" enctype="multipart/form-data">
                    <div class="dslpfw-section-left">
                        <div class="dslpfw-main-table res-cl dslpfw-add-rule-page">
                            <h1 class="wp-heading-inline"><?php 
            esc_html_e( 'Pickup Locations', 'local-pickup-for-woocommerce' );
            ?></h1>
                            <a class="page-title-action dots-btn-with-brand-color" href="<?php 
            echo esc_url( $link );
            ?>"><?php 
            esc_html_e( 'Add New', 'local-pickup-for-woocommerce' );
            ?></a>
                            <?php 
            //We have usef GET here because of 'prepare_items' funciton need search term while pagination else pagination not work properly
            $request_s = filter_input( INPUT_GET, 's', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            if ( isset( $request_s ) && !empty( $request_s ) ) {
                /* translators: %s is replaced with "string" which show searched string */
                echo sprintf( '<span class="subtitle">' . esc_html__( 'Search results for &#8220;%s&#8221;', 'local-pickup-for-woocommerce' ) . '</span>', esc_html( $request_s ) );
            }
            wp_nonce_field( 'pickup_location_list_action', 'pickup_location_list' );
            $DSLPFW_Pickup_Location_List_Table = new DSLPFW_Pickup_Location_List_Table();
            $DSLPFW_Pickup_Location_List_Table->process_bulk_action();
            $DSLPFW_Pickup_Location_List_Table->prepare_items();
            $DSLPFW_Pickup_Location_List_Table->search_box( esc_html__( 'Search', 'local-pickup-for-woocommerce' ), 'shipping-method' );
            $DSLPFW_Pickup_Location_List_Table->display();
            ?>
                        </div>
                    </div>
                </form>
            <?php 
            require_once plugin_dir_path( __FILE__ ) . 'header/plugin-footer.php';
        }

        /**
         * Delete pickup location
         *
         * @param int $id
         *
         * @access   public
         *
         * @since    1.0.0
         *
         */
        public static function dslpfw_delete_method( $id ) {
            $_wpnonce = filter_input( INPUT_GET, '_wpnonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $getnonce = wp_verify_nonce( $_wpnonce, 'del_' . $id );
            if ( isset( $getnonce ) && 1 === $getnonce ) {
                wp_delete_post( $id );
                wp_safe_redirect( add_query_arg( array(
                    'page'    => 'dslpfw-pickup-location-list',
                    'message' => 'deleted',
                ), admin_url( 'admin.php' ) ) );
                exit;
            }
        }

        /**
         * Duplicate pickup location data
         *
         * @param int $id
         *
         * @access   public
         *
         * @since    1.0.0
         *
         */
        public static function dslpfw_duplicate_method( $id ) {
            $_wpnonce = filter_input( INPUT_GET, '_wpnonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $getnonce = wp_verify_nonce( $_wpnonce, 'duplicate_' . $id );
            if ( isset( $getnonce ) && 1 === $getnonce ) {
                // Get all the original post data
                $post = get_post( $id );
                // Get current user and make it new post user (for duplicate post)
                $current_user = wp_get_current_user();
                $new_post_author = $current_user->ID;
                // If post data exists, duplicate the data into new duplicate post
                if ( isset( $post ) && null !== $post ) {
                    $dslpfw_rule_count = self::dslpfw_count_method();
                    $args = array(
                        'comment_status' => $post->comment_status,
                        'ping_status'    => $post->ping_status,
                        'post_author'    => $new_post_author,
                        'post_content'   => $post->post_content,
                        'post_excerpt'   => $post->post_excerpt,
                        'post_name'      => $post->post_name,
                        'post_parent'    => $post->post_parent,
                        'post_password'  => $post->post_password,
                        'post_status'    => 'draft',
                        'post_title'     => $post->post_title . ' (duplicate)',
                        'post_type'      => DSLPFW_POST_TYPE,
                        'to_ping'        => $post->to_ping,
                        'menu_order'     => $dslpfw_rule_count + 1,
                    );
                    // Duplicate the post by wp_insert_post() function
                    $duplicate_post_id = wp_insert_post( $args );
                    // Get all postmeta from original post
                    $post_meta_data = get_post_meta( $id );
                    if ( 0 !== count( $post_meta_data ) ) {
                        foreach ( $post_meta_data as $meta_key => $meta_data ) {
                            $meta_value = maybe_unserialize( $meta_data[0] );
                            update_post_meta( $duplicate_post_id, $meta_key, $meta_value );
                        }
                    }
                    $admin_url = admin_url( 'admin.php' );
                    //Redirect after duplicate rule
                    wp_safe_redirect( add_query_arg( array(
                        'page'    => 'dslpfw-pickup-location-list',
                        'action'  => 'edit',
                        'id'      => $duplicate_post_id,
                        'message' => 'duplicated',
                    ), $admin_url ) );
                    exit;
                }
            }
        }

        /**
         * Get address field pieces.
         *
         * @since 1.0.0
         *
         * @return array associative array of keys and labels
         */
        private static function get_address_pieces() {
            return array(
                'address_1' => esc_html__( 'Address Line 1', 'local-pickup-for-woocommerce' ),
                'address_2' => esc_html__( 'Address Line 2', 'local-pickup-for-woocommerce' ),
                'city'      => esc_html__( 'City', 'local-pickup-for-woocommerce' ),
                'state'     => esc_html__( 'State', 'local-pickup-for-woocommerce' ),
                'country'   => esc_html__( 'Country', 'local-pickup-for-woocommerce' ),
                'postcode'  => esc_html__( 'Postcode', 'local-pickup-for-woocommerce' ),
            );
        }

    }

}