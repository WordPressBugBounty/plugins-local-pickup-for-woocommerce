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
use DotStore\DSLPFW_Local_Pickup_Woocommerce\Pickup_Locations\Pickup_Location as Pickup_Location;
/**
 * Local Pickup Location object.
 *
 * This object represents a single location with its specific configuration and properties, some required as the address (at least a country/state must be defined), others optional as phone, pickup hours and calendar to schedule a pickup, products available at the location and so on.
 * Each property has helper methods to set, get and delete the related object meta.
 *
 * @since 1.0.0
 */
class DSLPFW_Local_Pickup_WooCommerce_Pickup_Location {
    /** @var int location (post) unique ID */
    protected $id = 0;

    /** @var string location name (post title) */
    protected $name = '';

    /** @var string location (post) slug */
    protected $slug = '';

    /** @var string location (post) slug */
    protected $status = '';

    /** @var string location as post */
    protected $post = '';

    /** @var \DSLPFW_Local_Pickup_Location_Address location address */
    protected $address;

    /** @var string address post meta key name */
    protected $address_meta = 'dslpfw_pickup_location';

    /** @var string phone number post meta key name */
    protected $phone_meta = 'dslpfw_pickup_location_phone';

    /** @var string products availability post meta key name */
    protected $products_status_meta = 'dslpfw_pickup_location_products_status';

    protected $products_meta = 'dslpfw_pickup_location_products';

    protected $categories_status_meta = 'dslpfw_pickup_location_categories_status';

    protected $categories_meta = 'dslpfw_pickup_location_categories';

    /** @var string pickup hours post meta key name */
    protected $pickup_hours_status_meta = 'dslpfw_pickup_location_pickup_hours_status';

    protected $pickup_hours_meta = 'dslpfw_pickup_location_pickup_hours';

    /** @var string holiday dates post meta key name */
    protected $holiday_dates_status_meta = 'dslpfw_pickup_location_holiday_dates_status';

    protected $holiday_dates_meta = 'dslpfw_pickup_location_holiday_dates';

    /** @var string scheduled appointment required lead time post meta key name */
    protected $lead_time_status_meta = 'dslpfw_pickup_location_lead_time_status';

    protected $lead_time_meta = 'dslpfw_pickup_location_lead_time';

    /** @var string pickup deadline to schedule an appointment post meta key name */
    protected $deadline_status_meta = 'dslpfw_pickup_location_deadline_status';

    protected $deadline_meta = 'dslpfw_pickup_location_deadline';

    /** @var string fee adjustment post meta key name */
    protected $fee_adjustment_status_meta = 'dslpfw_pickup_location_fee_adjustment_status';

    protected $fee_adjustment_meta = 'dslpfw_pickup_location_fee_adjustment';

    /** @var null|int[] cached array of product IDs to flag products available at this location */
    protected $dslpfw_products;

    /** @var null|int[] cached array of product categories IDs to flag categories compatible with the current location */
    protected $dslpfw_product_categories;

    /**
     * Location constructor.
     *
     * @since 1.0.0
     *
     * @param int|\WP_Post|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $id the post or location ID, object
     */
    public function __construct( $id ) {
        if ( is_numeric( $id ) ) {
            $post = get_post( (int) $id );
            $this->post = ( $post instanceof \WP_Post ? $post : null );
        } elseif ( is_object( $id ) ) {
            $this->post = $id;
        }
        if ( $this->post instanceof \WP_Post ) {
            // set post type data
            $this->id = (int) $this->post->ID;
            $this->name = $this->post->post_title;
            $this->slug = $this->post->post_name;
            $this->status = $this->post->post_status;
        }
    }

    /**
     * Get the location ID.
     *
     * @since 1.0.0
     *
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get the location name.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get the location slug.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_slug() {
        return $this->slug;
    }

    /**
     * Get the location slug.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_status() {
        return $this->status;
    }

    /**
     * Get the post object.
     *
     * @since 1.0.0
     *
     * @return null|\WP_Post
     */
    public function get_post() {
        return $this->post;
    }

    /**
     * Get location optional notes.
     *
     * @since 1.0.0
     *
     * @return string HTML
     */
    public function get_description() {
        $description = ( $this->post instanceof \WP_Post ? $this->post->post_content : '' );
        /**
         * Filter the pickup location description notes.
         *
         * @since 1.0.0
         *
         * @param string $description pickup location notes
         * @param \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $pickup_location the current pickup location
         */
        return apply_filters( 'dslpfw_pickup_location_description', $description, $this );
    }

    /**
     * Get location phone number.
     *
     * @since 1.0.0
     *
     * @param bool $html optional: whether to return a HTML phone link; default (false) will return just the phone number string
     * @return string
     */
    public function get_phone( $html = false ) {
        $phone = ( $this->id > 0 ? get_post_meta( $this->id, $this->phone_meta, true ) : '' );
        $phone = ( is_string( $phone ) ? $phone : '' );
        /**
         * Filter the pickup location phone number.
         *
         * @since 1.0.0
         *
         * @param string $phone a phone number or empty string if not set
         * @param \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $pickup_location the current pickup location object
         */
        $phone = apply_filters( 'dslpfw_pickup_location_phone', $phone, $this );
        if ( $html === true && !empty( $phone ) ) {
            $phone = '<a href="tel:' . esc_attr( $phone ) . '">' . $phone . '</a>';
        }
        return $phone;
    }

    /**
     * Whether the location has a phone number.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function has_phone() {
        $phone = trim( $this->get_phone( false ) );
        return !empty( $phone );
    }

    /**
     * Delete the location phone number.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function delete_phone() {
        return $this->id > 0 && delete_post_meta( $this->id, $this->phone_meta );
    }

    /**
     * Get the location address.
     *
     * @since 1.0.0
     *
     * @param null|string $piece optional, to return an address piece instead of the whole object
     * @return string|\DSLPFW_Local_Pickup_Location_Address address object or string piece
     */
    public function get_address( $piece = null ) {
        if ( !$this->address instanceof \DSLPFW_Local_Pickup_Location_Address ) {
            $address_array = array();
            if ( $this->id > 0 ) {
                $address_array = array(
                    'name'      => $this->name,
                    'country'   => get_post_meta( $this->id, "{$this->address_meta}_country", true ),
                    'state'     => get_post_meta( $this->id, "{$this->address_meta}_state", true ),
                    'city'      => get_post_meta( $this->id, "{$this->address_meta}_city", true ),
                    'postcode'  => get_post_meta( $this->id, "{$this->address_meta}_postcode", true ),
                    'address_1' => get_post_meta( $this->id, "{$this->address_meta}_address_1", true ),
                    'address_2' => get_post_meta( $this->id, "{$this->address_meta}_address_2", true ),
                );
            }
            $this->address = new \DSLPFW_Local_Pickup_Location_Address($address_array);
        }
        /**
         * Filter the pickup location address.
         *
         * @since 1.0.0
         *
         * @param \DSLPFW_Local_Pickup_Location_Address address object
         * @param string|null $piece whether an address piece was requested
         * @param \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location the pickup location
         */
        $address = apply_filters(
            'dslpfw_pickup_location_address',
            $this->address,
            $piece,
            $this
        );
        // @phpstan-ignore-line
        if ( is_string( $piece ) ) {
            $address = $address->get_array();
            $address = ( isset( $address[$piece] ) ? $address[$piece] : '' );
        }
        return $address;
    }

    /**
     * Returns the pickup location formatted name.
     *
     * @since 1.0.0
     *
     * @param string $context context where the name is intended to be displayed
     * @return string
     */
    public function get_formatted_name( $context = 'frontend' ) {
        if ( 'admin' === $context ) {
            // append the location ID, a bit like other WooCommerce objects do in admin panels or search field results (e.g. products, orders)
            $name = trim( sprintf( ( is_rtl() ? '(#%2$s) %1$s' : '%1$s (#%2$s)' ), esc_html( $this->name ), esc_html( $this->id ) ) );
        } elseif ( 'frontend' === $context ) {
            // for customer facing purposes, some locations can have the same name, so to help differentiating them the ID is not useful, but the state (or city if no state) and postcode are
            $address = $this->get_address();
            $meta = trim( implode( ' ', array_unique( array(( $address->has_state() ? $address->get_state() : $address->get_city() ), $address->get_postcode()) ) ) );
            $name = trim( sprintf( ( is_rtl() ? '%2$s %1$s' : '%1$s %2$s' ), esc_html( $this->name ), ( !empty( $meta ) ? " ({$meta}) " : '' ) ) );
        } else {
            // just use the plain name if context is not a recognized one
            $name = $this->name;
        }
        /**
         * Filters the pickup location formatted name.
         *
         * @since 1.0.0
         *
         * @param string $name pickup location formatted name
         * @param string $context context where the name should be displayed
         * @param \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $pickup_location pickup location object
         */
        return (string) apply_filters(
            'dslpfw_pickup_location_option_label',
            $name,
            $context,
            $this
        );
    }

    /**
     * Set this location address.
     *
     * @since 1.0.0
     *
     * @param array|\DSLPFW_Local_Pickup_Location_Address $address the address as an associative array or object
     */
    public function set_address( $address ) {
        if ( $this->id > 0 ) {
            if ( is_array( $address ) ) {
                $address = new \DSLPFW_Local_Pickup_Location_Address($address, $this->id);
            }
            if ( $address instanceof \DSLPFW_Local_Pickup_Location_Address ) {
                $this->address = $address;
                // we need to store the address also as post meta to help WordPress admin search location by address pieces
                foreach ( $address->get_array() as $piece => $value ) {
                    update_post_meta( $this->id, "{$this->address_meta}_{$piece}", sanitize_text_field( $value ) );
                }
            }
        }
    }

    /**
     * Set location phone number.
     *
     * @since 1.0.0
     *
     * @param string $phone_number a phone number string
     * @return bool
     */
    public function set_phone( $phone_number ) {
        $success = false;
        if ( $this->id > 0 && is_string( $phone_number ) ) {
            $success = update_post_meta( $this->id, $this->phone_meta, trim( $phone_number ) );
        }
        return (bool) $success;
    }

    /**
     * Get location pickup hours for scheduling a pickup appointment.
     *
     * @since 1.0.0
     *
     * @return \DSLPFW_Local_Pickup_Location_Pickup_Hours
     */
    public function get_pickup_hours() {
        $pickup_hours_meta = array();
        $default_pickup_hours = dslpfw_shipping_method()->get_default_pickup_hours();
        if ( !empty( $default_pickup_hours ) && is_array( $default_pickup_hours ) ) {
            $pickup_hours_meta = $default_pickup_hours;
        }
        $pickup_hours_object = new \DSLPFW_Local_Pickup_Location_Pickup_Hours($pickup_hours_meta, $this->id);
        /**
         * Filter the pickup location pickup hours.
         *
         * @since 1.0.0
         * @param \DSLPFW_Local_Pickup_Location_Pickup_Hours $pickup_hours_object the pickup hours schedule object
         * @param array $pickup_hours_meta the pickup hours schedule as an associative array
         * @param \DSLPFW_Local_Pickup_WooCommerce_Pickup_Locations $pickup_location the pickup location
         */
        return apply_filters(
            'dslpfw_pickup_location_pickup_hours',
            $pickup_hours_object,
            $pickup_hours_meta,
            $this
        );
        // @phpstan-ignore-line
    }

    /**
     * Check if this location has pickup hours set.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function has_pickup_hours() {
        return $this->id > 0 && $this->get_pickup_hours()->has_schedule();
    }

    /**
     * Gets the fee adjustment for this location.
     *
     * @since 1.0.0
     *
     * @return \DSLPFW_Local_Pickup_Location_Fee_Adjustment
     */
    public function get_fee_adjustment() {
        $fee_adjustment_meta = null;
        $default_fee_adjustment = dslpfw_shipping_method()->get_default_fee_adjustment();
        if ( '0' !== $default_fee_adjustment && !empty( $default_fee_adjustment ) ) {
            $fee_adjustment_meta = $default_fee_adjustment;
        }
        $fee_adjustment_object = new \DSLPFW_Local_Pickup_Location_Fee_Adjustment($fee_adjustment_meta, $this->id);
        /**
         * Filters the fee adjustment for the pickup location.
         *
         * @since 1.0.0
         *
         * @param \DSLPFW_Local_Pickup_Location_Fee_Adjustment $fee_adjustment_object a fee adjustment instance
         * @param string $fee_adjustment_meta the value passed to the fee adjustment helper object
         * @param \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $pickup_location the pickup location
         */
        return apply_filters(
            'dslpfw_pickup_location_fee_adjustment',
            $fee_adjustment_object,
            $fee_adjustment_meta,
            $this
        );
    }

    /**
     * Check if this location has a fee adjustment set.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function has_fee_adjustment() {
        return !$this->get_fee_adjustment()->is_null();
    }

    /**
     * Helper method to get a normalized data array from field data(POST).
     *
     * @since 1.0.0
     *
     * @param string $field_name field name
     * @param array $field_data field posted data
     * @return array
     */
    public function get_field_value( $field_name, $field_data ) {
        $final_time_data = array();
        if ( isset( $field_data[$field_name] ) && count( $field_data[$field_name] ) > 0 ) {
            $final_time_data = $field_data[$field_name];
        }
        return $final_time_data;
    }

    /**
     * Gets the appointments handler for this pickup location.
     *
     * @since 1.0.0
     *
     * @return Pickup_Location\DSLPFW_Local_Pickup_Location_Appointments
     */
    public function get_appointments() {
        return new Pickup_Location\DSLPFW_Local_Pickup_Location_Appointments($this);
    }

}
