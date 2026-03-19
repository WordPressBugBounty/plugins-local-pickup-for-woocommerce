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
/**
 * WooCommerce Products and Product Categories handler for local pickup.
 *
 * @since 1.0.0
 */
class DSLPFW_Local_Pickup_WooCommerce_Products {
    /** @var array memoized flag for products that can be picked up */
    private $product_can_be_picked_up = array();

    /** @var array memoized flag for product that must be picked up */
    private $product_must_be_picked_up = array();

    /** @var string meta key for product local pickup availability */
    private $product_availability_meta;

    /** @var string meta key for product category local pickup availability */
    private $product_cat_availability_meta;

    /**
     * Products handler constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->product_availability_meta = '_dslpfw_product_availability';
        $this->product_cat_availability_meta = '_dslpfw_product_cat_availability';
    }

    /**
     * Get product availability meta key for html purpose
     * 
     * @since 1.0.0
     */
    public function get_product_availability_meta() {
        return $this->product_availability_meta;
    }

    /**
     * Get product category availability meta key for html purpose
     * 
     * @since 1.0.0
     */
    public function get_product_cat_availability_meta() {
        return $this->product_cat_availability_meta;
    }

    /**
     * Gets local pickup product availability status types.
     *
     * @since 1.0.0
     *
     * @param bool $with_labels whether to return an associative array with labels or keys only (default)
     * @return string[]|array associative array or array of strings
     */
    public function get_dslpfw_product_availability_types( $with_labels = false ) {
        $types = array(
            'inherit'    => esc_html__( 'Use category preferences', 'local-pickup-for-woocommerce' ),
            'allowed'    => esc_html__( 'Available for pickup', 'local-pickup-for-woocommerce' ),
            'disallowed' => esc_html__( 'Not available for pickup', 'local-pickup-for-woocommerce' ),
            'required'   => esc_html__( 'Required for pickup', 'local-pickup-for-woocommerce' ),
        );
        return ( true === $with_labels ? $types : array_keys( $types ) );
    }

    /**
     * Gets local pickup category availability status types.
     *
     * @since 1.0.0
     *
     * @param bool $with_labels whether to return an associative array with labels or keys only (default)
     * @return string[]|array associative array or array of strings
     */
    public function get_dslpfw_category_availability_types( $with_labels = false ) {
        $types = $this->get_dslpfw_product_availability_types( true );
        unset($types['inherit']);
        return ( true === $with_labels ? $types : array_keys( $types ) );
    }

    /**
     * Gets a product availability for local pickup.
     *
     * @since 1.0.0
     *
     * @param int|\WC_Product $product_id the product ID or object
     * @param bool $dont_inherit if true, ignores the inherited status and instead returns 'inherit'
     * @return string product availability status
     */
    public function get_local_pickup_product_availability( $product_id, $dont_inherit = false ) {
        $product = ( is_numeric( $product_id ) ? wc_get_product( $product_id ) : $product_id );
        /**
         * Filters the default pickup product availability.
         *
         * @since 1.0.0
         *
         * @param string $default one of 'inherit' (default), 'allowed', 'disallowed', 'required'
         * @param \WC_Product $product current product object being evaluated for availability
         */
        $default = apply_filters( 'dslpfw_default_pickup_product_availability', 'inherit', $product );
        if ( $product->is_type( 'variation' ) ) {
            $parent = wc_get_product( $product->get_parent_id() );
            $availability = ( $parent ? $parent->get_meta( $this->product_availability_meta ) : $default );
        } else {
            $availability = $product->get_meta( $this->product_availability_meta );
        }
        $availability = ( !empty( $availability ) && in_array( $availability, $this->get_dslpfw_product_availability_types(), true ) ? $availability : $default );
        if ( 'inherit' === $availability && !$dont_inherit ) {
            $availability = $this->get_product_inherited_availability( $product );
        }
        /**
         * Filters a product availability for local pickup.
         *
         * @since 1.0.0
         *
         * @param string $availability either 'allowed', 'disallowed' or 'required'
         * @param \WC_Product $product the product object
         */
        return apply_filters( 'dslpfw_local_pickup_product_availability', $availability, $product );
    }

    /**
     * Gets the availability for a product inherited from its categories.
     *
     * In the event of multiple categories with conflicting availabilities, enforce the following priority:
     * 1) Pickup Required
     * 2) Pickup Disallowed
     * 3) Pickup Allowed
     *
     * @since 1.0.0
     *
     * @param \WC_Product $product
     * @return string product availability status
     */
    public function get_product_inherited_availability( $product ) {
        $inherited_availability = 'allowed';
        if ( $product->is_type( 'variation' ) ) {
            $product = wc_get_product( $product->get_parent_id() );
            // sanity check: bail in the unlikely possibility the parent product can't be determined
            if ( !$product ) {
                return $inherited_availability;
            }
        }
        $category_ids = $product->get_category_ids();
        if ( is_array( $category_ids ) && !empty( $category_ids ) ) {
            foreach ( $category_ids as $category_id ) {
                $category_availability = $this->get_local_pickup_product_cat_availability( $category_id );
                if ( 'allowed' !== $category_availability ) {
                    $inherited_availability = $category_availability;
                    if ( 'required' === $inherited_availability ) {
                        break;
                    }
                }
            }
        }
        return $inherited_availability;
    }

    /**
     * Get a product category availability for local pickup.
     *
     * @since 1.0.0
     *
     * @param int|\WP_Term $product_cat the product category term ID or object
     * @return string product category availability status
     */
    public function get_local_pickup_product_cat_availability( $product_cat ) {
        $term_id = ( $product_cat instanceof \WP_Term ? $product_cat->term_id : $product_cat );
        $availability = ( $term_id > 0 ? get_term_meta( $term_id, $this->product_cat_availability_meta, true ) : null );
        $availability = ( in_array( $availability, $this->get_dslpfw_category_availability_types(), true ) ? $availability : 'allowed' );
        /**
         * Filter a product category availability for local pickup.
         *
         * @since 1.0.0
         *
         * @param string $availability either 'allowed', 'disallowed' or 'required'
         * @param int $term_id product category \WP_Term ID
         */
        return apply_filters( 'dslpfw_local_pickup_product_category_availability', $availability, $term_id );
    }

    /**
     * Check whether a product can be picked up.
     *
     * @since 1.0.0
     *
     * @param int|\WC_Product $product product ID or object
     * @param null|int|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $pickup_location optional: Pickup Location ID or object to check specifically
     * @return bool
     */
    public function product_can_be_picked_up( $product, $pickup_location = null ) {
        $can_be = true;
        $product_id = ( $product instanceof \WC_Product ? $product->get_id() : $product );
        $pickup_location = ( null !== $pickup_location ? dslpfw_get_pickup_location( $pickup_location ) : null );
        $pickup_location_id = ( $pickup_location ? $pickup_location->get_id() : 0 );
        if ( is_numeric( $product_id ) && $product_id > 0 ) {
            if ( !isset( $this->product_can_be_picked_up[$pickup_location_id] ) || !array_key_exists( $product_id, $this->product_can_be_picked_up[$pickup_location_id] ) ) {
                $product = ( is_numeric( $product ) ? wc_get_product( $product ) : $product );
                $availability = $this->get_local_pickup_product_availability( $product );
                $can_be = $product instanceof \WC_Product && 'disallowed' !== $availability;
                if ( !isset( $this->product_can_be_picked_up[$pickup_location_id] ) ) {
                    $this->product_can_be_picked_up[$pickup_location_id] = array();
                }
                // check if the product can be picked up at all
                $can_be = $can_be && $product->needs_shipping();
                // check if we allow the user to select a pickup location for each product
                // (we only restrict available products per location if per item selection is enabled)
                if ( dslpfw_shipping_method()->dslpfw_is_per_item_selection_enabled() ) {
                    // check if product is available for pickup at any of the locations
                    if ( !$pickup_location && $can_be ) {
                        $available_locations = $this->get_product_pickup_locations( $product, array(
                            'fields' => 'ids',
                        ) );
                        if ( empty( $available_locations ) ) {
                            $can_be = false;
                        }
                    }
                }
                $this->product_can_be_picked_up[$pickup_location_id][$product_id] = $can_be;
            } else {
                $can_be = $this->product_can_be_picked_up[$pickup_location_id][$product_id];
            }
        }
        /**
         * Filter whether a product can be picked up.
         *
         * If a pickup location is specified, the check is for that specific location.
         *
         * @since 1.0.0
         *
         * @param bool  $can_be   whether the product can be picked up
         * @param int|\WC_Product   $product    product ID or object
         * @param null|int|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location     $pickup_location    optional: a pickup location object or ID
         */
        return (bool) apply_filters(
            'dslpfw_product_can_be_picked_up',
            $can_be,
            $product,
            $pickup_location
        );
    }

    /**
     * Check whether a product must be picked up.
     *
     * @since 1.0.0
     *
     * @param int|\WC_Product $product product object or ID
     * @param null|int|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $pickup_location optional: Pickup Location ID or object to check specifically
     * @return bool
     */
    public function product_must_be_picked_up( $product, $pickup_location = null ) {
        $product = ( is_numeric( $product ) ? wc_get_product( $product ) : $product );
        // must-pick-up settings are saved on the parent product
        $product = ( 0 !== $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : $product );
        $must_be = false;
        $product_id = ( $product instanceof \WC_Product ? $product->get_id() : $product );
        $pickup_location = ( null !== $pickup_location ? dslpfw_get_pickup_location( $pickup_location ) : null );
        $pickup_location_id = ( $pickup_location ? $pickup_location->get_id() : 0 );
        if ( is_numeric( $product_id ) && $product_id > 0 ) {
            if ( !isset( $this->product_must_be_picked_up[$pickup_location_id] ) || !array_key_exists( $product_id, $this->product_must_be_picked_up[$pickup_location_id] ) ) {
                $availability = ( $product ? $this->get_local_pickup_product_availability( $product ) : null );
                $must_be = $product instanceof \WC_Product && 'required' === $availability;
                if ( !isset( $this->product_must_be_picked_up[$pickup_location_id] ) ) {
                    $this->product_must_be_picked_up[$pickup_location_id] = array();
                }
                $this->product_must_be_picked_up[$pickup_location_id][$product_id] = $must_be && $product->needs_shipping();
                if ( $pickup_location && $this->product_must_be_picked_up[$pickup_location_id][$product_id] ) {
                    $product_ids = [];
                    $this->product_must_be_picked_up[$pickup_location_id][$product_id] = empty( $product_ids ) || in_array( $product_id, $product_ids, false );
                    //phpcs:ignore
                }
                $must_be = $this->product_must_be_picked_up[$pickup_location_id][$product_id];
            } else {
                $must_be = $this->product_must_be_picked_up[$pickup_location_id][$product_id];
            }
        }
        /**
         * Filter whether a product must be picked up.
         *
         * If a pickup location is specified, the check is for that specific location.
         *
         * @since 1.0.0
         *
         * @param bool  $must_be     whether the product must be picked up
         * @param int|\WC_Product   $product    product ID or object
         * @param null|int|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location     $pickup_location    optional: a pickup location object or ID
         */
        return (bool) apply_filters(
            'dslpfw_product_must_be_picked_up',
            $must_be,
            $product,
            $pickup_location
        );
    }

    /**
     * Returns pickup locations where a product is available at.
     *
     * @since 1.0.0
     *
     * @param int|\WC_Product $product a product ID or object
     * @param array $args optional array of arguments passed to `get_posts()`
     * @return \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location[] array of pickup locations
     */
    public function get_product_pickup_locations( $product, $args = array() ) {
        $available_locations = array();
        $pickup_locations = dslpfw_get_pickup_locations( $args );
        foreach ( $pickup_locations as $pickup_location ) {
            if ( $this->product_can_be_picked_up( $product, $pickup_location ) ) {
                $available_locations[] = $pickup_location;
            }
        }
        return $available_locations;
    }

    /**
     * Returns a single pickup location when only one pickup location is available for the product.
     *
     * This method should be used to determine whether there is a sole pickup location available in the installation and this product can use it.
     *
     * @since 1.0.0
     *
     * @param int|\WC_Product $product a product ID or object
     * @return null|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location the unique pickup location or null if there is more than one location possible or product cannot be picked up
     */
    public function get_product_pickup_location( $product ) {
        $product = ( is_numeric( $product ) ? wc_get_product( $product ) : $product );
        $pickup_location = null;
        if ( $product instanceof \WC_Product && 1 === $this->get_product_pickup_locations_count( $product ) ) {
            $pickup_locations = $this->get_product_pickup_locations( $product, array(
                'fields' => 'ids',
            ) );
            $pickup_location = ( !empty( $pickup_locations ) && 1 === count( $pickup_locations ) ? dslpfw_get_pickup_location( current( $pickup_locations ) ) : null );
        }
        return ( $pickup_location instanceof \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location ? $pickup_location : null );
    }

    /**
     * Returns the number of locations a product can be picked up at.
     *
     * @since 1.0.0
     *
     * @param int|\WC_Product $product a product ID or object
     * @param array $args optional array of arguments to adjust the product query (default empty)
     * @return int
     */
    public function get_product_pickup_locations_count( $product, $args = array() ) {
        $args['fields'] = 'ids';
        return count( $this->get_product_pickup_locations( $product, $args ) );
    }

}
