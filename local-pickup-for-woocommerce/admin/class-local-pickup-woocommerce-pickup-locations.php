<?php

/**
 * Admin side pickup location custom post type store and get methods
 *
 * @link       https://www.thedotstore.com/
 * @since      1.0.0
 *
 * @package    DSLPFW_Local_Pickup_Woocommerce
 * @subpackage DSLPFW_Local_Pickup_Woocommerce/admin
 */
defined( 'ABSPATH' ) or exit;
/**
 * Local Pickup Locations handler class.
 *
 * This class handles general pickup locations related functionality.
 *
 * @since 1.0.0
 */
class DSLPFW_Local_Pickup_WooCommerce_Pickup_Locations {
    /** @var array memoized pickup locations by ID (when getting a single location by its ID) */
    private $pickup_locations_by_id = array();

    /** @var array memoized queried pickup locations (when using get_posts() to query multiple locations at once) */
    private $queried_pickup_locations = array();

    /** @var array memoized pickup locations area codes */
    private $pickup_locations_country_state_codes = array();

    /** @var array memoized pickup location IDs by queried distance */
    private $locations_by_distance = array();

    /**
     * Pickup locations handler constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->load_dependencies();
    }

    /**
     * Loads the pickup locations objects.
     *
     * @since 1.0.0
     */
    private function load_dependencies() {
        /**
         * The class responsible for defining all method for pickup location CPT
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-local-pickup-woocommerce-pickup-location.php';
        /**
         * The class responsible for defining all method for pickup location CPT Address meta data (We will use this for Geo Data as well in future)
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-local-pickup-woocommerce-pickup-location-address.php';
        /**
         * The class responsible for defining all method for pickup hours data
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-local-pickup-woocommerce-pickup-location-pickup-hours.php';
        /**
         * The class responsible for defining all method for appointment data for pickup location
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-local-pickup-woocommerce-pickup-location-appointments.php';
        /**
         * The class responsible for defining all method for fee adjustment data for pickup location
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-local-pickup-woocommerce-pickup-location-fee-adjustment.php';
    }

    /**
     * Get area codes for registered pickup locations.
     *
     * @since 1.0.0
     *
     * @return string[] array of country:state codes
     */
    public function get_available_pickup_location_country_state_codes() {
        // To-Do: We will use our CPT with publish status and get country meta data array here to fulfill this method data.
        return 'needs to be implemented';
    }

    /**
     * Get a pickup location.
     *
     * @since 1.0.0
     *
     * @param int|\WP_Post|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $location_id a location identifier
     * @return null|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location the pickup location object or false if none found
     */
    public function get_pickup_location( $location_id = null ) {
        $location_post = null;
        if ( null !== $location_id && is_numeric( $location_id ) && isset( $this->pickup_locations_by_id[(int) $location_id] ) ) {
            $pickup_location = $this->pickup_locations_by_id[(int) $location_id];
        } else {
            $location_post = $location_id;
            if ( 0 !== $location_post && empty( $location_post ) && isset( $GLOBALS['post'] ) ) {
                $location_post = $GLOBALS['post'];
            } elseif ( is_numeric( $location_post ) ) {
                $location_post = get_post( (int) $location_id );
            } elseif ( $location_id instanceof \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location ) {
                $location_post = get_post( $location_id->get_id() );
            } elseif ( !$location_id instanceof \WP_Post ) {
                $location_post = null;
            }
            // if no acceptable post is found, bail out
            if ( !$location_post || DSLPFW_POST_TYPE !== get_post_type( $location_post ) ) {
                $pickup_location = null;
            } else {
                // set a pickup location object
                $pickup_location = new \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location($location_post);
                $this->pickup_locations_by_id[(int) $location_post->ID] = $pickup_location;
            }
        }
        if ( $pickup_location instanceof \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location ) {
            $location_id = $pickup_location->get_id();
            $location_post = $pickup_location->get_post();
        }
        /**
         * Filters a pickup location before returning it.
         *
         * @since 1.0.0
         *
         * @param \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $pickup_location the pickup location object
         * @param null|\WP_Post $location_post the pickup $pickup_location post object
         * @param int|string|\WP_Post|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $location_id the requested location id
         */
        $pickup_location = apply_filters(
            'dslpfw_get_pickup_location',
            $pickup_location,
            $location_post,
            $location_id
        );
        return ( $pickup_location instanceof \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location ? $pickup_location : null );
    }

    /**
     * Get pickup locations.
     *
     * @since 1.0.0
     *
     * @param array $args optional array of arguments, passed to `get_posts()`
     * @return int|int[]|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location[] $plans array of pickup location objects, IDs or count
     */
    public function get_pickup_locations( $args = array() ) {
        $args = wp_parse_args( $args, array(
            'nopaging'         => true,
            'posts_per_page'   => -1,
            'post_status'      => 'publish',
            'suppress_filters' => false,
            'order'            => 'ASC',
        ) );
        $args['post_type'] = DSLPFW_POST_TYPE;
        // unique key for caching the results of the given query from passed args
        $cache_key = http_build_query( $args );
        if ( !isset( $this->queried_pickup_locations[$cache_key] ) ) {
            $count = isset( $args['count'] ) && true === $args['count'];
            $pickup_locations_posts = get_posts( $args );
            $this->queried_pickup_locations[$cache_key] = ( $count ? 0 : array() );
            if ( !empty( $pickup_locations_posts ) ) {
                if ( $count ) {
                    $found_locations = count( $pickup_locations_posts );
                } elseif ( isset( $args['fields'] ) && 'ids' === $args['fields'] ) {
                    $found_locations = $pickup_locations_posts;
                    if ( !empty( $args['custom_order'] ) && is_array( $args['custom_order'] ) ) {
                        $sorted_locations = array();
                        foreach ( $args['custom_order'] as $pickup_location_id ) {
                            if ( in_array( $pickup_location_id, $found_locations, false ) ) {
                                //phpcs:ignore
                                $sorted_locations[] = $pickup_location_id;
                            }
                        }
                        $found_locations = $sorted_locations;
                    }
                } else {
                    $found_locations = array();
                    foreach ( $pickup_locations_posts as $post ) {
                        $pickup_location = $this->get_pickup_location( $post );
                        if ( $pickup_location ) {
                            $found_locations[$pickup_location->get_id()] = $pickup_location;
                        }
                    }
                    if ( !empty( $args['custom_order'] ) && is_array( $args['custom_order'] ) ) {
                        $sorted_locations = array();
                        foreach ( $args['custom_order'] as $pickup_location_id ) {
                            if ( array_key_exists( $pickup_location_id, $found_locations ) ) {
                                $sorted_locations[$pickup_location_id] = $found_locations[$pickup_location_id];
                            }
                        }
                        $found_locations = $sorted_locations;
                    }
                }
                $this->queried_pickup_locations[$cache_key] = $found_locations;
            }
        }
        return $this->queried_pickup_locations[$cache_key];
    }

    /**
     * Count existing pickup locations.
     *
     * @since 1.0.0
     *
     * @param array $args optional additional args passed to get_posts() later
     * @return int
     */
    public function get_pickup_locations_count( $args = array() ) {
        $args['count'] = true;
        $args['fields'] = 'ids';
        $count = $this->get_pickup_locations( $args );
        return max( 0, ( is_array( $count ) ? count( $count ) : (int) $count ) );
    }

    /**
     * Returns all locations available.
     *
     * This should be only used when simple dropdown is active and locations are less than a hundred or will cause performance issues.
     *
     * @since 1.0.0
     *
     * @param array $query_args optional query arguments
     * @return \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location[]
     */
    public function get_sorted_pickup_locations( $query_args = array() ) {
        $sort_order = 'default';
        switch ( $sort_order ) {
            case 'alphabetical':
                $query_args['orderby'] = 'title';
                $query_args['order'] = 'ASC';
                break;
            case 'date_added':
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
                break;
            case 'nearby_address':
                $query_args['orderby'] = 'menu_order';
                $query_args['order'] = 'ASC';
                break;
            default:
                $query_args['orderby'] = 'menu_order';
                $query_args['order'] = 'ASC';
                break;
        }
        return $this->get_pickup_locations( $query_args );
    }

}
