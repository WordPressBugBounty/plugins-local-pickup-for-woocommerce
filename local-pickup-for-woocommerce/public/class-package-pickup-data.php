<?php
/**
 * The public-facing functionality of the plugin for cart repeated and helping methods
 *
 * @link       https://www.thedotstore.com/
 * @since      1.0.0
 *
 * @package    DSLPFW_Local_Pickup_Woocommerce
 * @subpackage DSLPFW_Local_Pickup_Woocommerce/public
 *
 *
 * @author      theDotstore <support@thedotstore.com>
 */
namespace DotStore\DSLPFW_Local_Pickup_Woocommerce\Data_Store;

defined( 'ABSPATH' ) or exit;

 /**
 * Pickup data storage component for cart items.
 *
 * @since 1.0.0
 */
class DSLPFW_Package_Pickup_Data {

    private $object_id;

    /**
	 * Data storage constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param int|string $package_key key index of current package
	 */
	public function __construct( $package_key ) {

		$this->object_id = $package_key;
	}

    /**
	 * Gets the pickup location data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $piece specific data to get. Defaults to getting all available data.
	 * @return array|string
	 */
	public function get_pickup_data( $piece = '' ) {
		return dslpfw()->get_dslpfw_session_object()->get_package_pickup_data( $this->object_id, $piece );
	}

    /**
	 * Sets the pickup location data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $pickup_data pickup data
	 */
	public function set_pickup_data( array $pickup_data ) {
		dslpfw()->get_dslpfw_session_object()->set_package_pickup_data( $this->object_id, $pickup_data );
	}

    /**
	 * Deletes the pickup location data.
	 *
	 * @since 1.0.0
	 */
	public function delete_pickup_data() {
		dslpfw()->get_dslpfw_session_object()->delete_package_pickup_data( $this->object_id );
	}

    /**
	 * Get the current package.
	 *
	 * @since 1.0.0
	 *
	 * @return array associative array
	 */
	public function get_package() {

		$package    = [];
		$package_id = $this->object_id;

		if ( null !== $package_id ) {

			$packages = WC()->shipping()->get_packages();

			if ( ! empty( $packages[ $package_id ] ) ) {
				$package = $packages[ $package_id ];
			}
		}

		return $package;
	}

    /**
	 * Gets the value of a package key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key the key to retrieve a value for
	 * @param null|mixed $default the default value (optional)
	 *
	 * @return null|string|int|array
	 */
	public function get_package_key( $key = null, $default = null ) {

		$value   = $default;
		$package = $this->get_package();

		if ( '' !== $key && is_string( $key ) && ! empty( $package ) ) {
			$value = isset( $package[ $key ] ) ? $package[ $key ] : $value;
		}

		return $value;
	}

    /**
	 * Gets the ID of the pickup location associated with the package.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function get_pickup_location_id() {

        $query_args = array();
        $query_args['posts_per_page'] = 1;
        $query_args['nopaging'] = false;
        $query_args['fields'] = 'ids';

        $pickup_locations = dslpfw()->get_dslpfw_pickup_locations_object()->get_pickup_locations($query_args);

        $default_pickup_location_id = is_array($pickup_locations) && isset($pickup_locations[0]) && !empty($pickup_locations[0]) ? $pickup_locations[0] : 0;
        
		return $this->get_package_key( 'pickup_location_id', $default_pickup_location_id );
	}

    /**
	 * Gets the pickup location associated with the package.
	 *
	 * @since 1.0.0
	 *
	 * @return null|\DSLPFW_Local_Pickup_WooCommerce_Pickup_Location
	 */
	public function get_pickup_location() {

		$pickup_location_id = $this->get_pickup_location_id();
		$pickup_location_id = 0 === $pickup_location_id ? dslpfw()->get_dslpfw_packages_object()->get_package_only_pickup_location_id( $this->get_package() ) : $pickup_location_id;

		return $pickup_location_id > 0 ? dslpfw_get_pickup_location( $pickup_location_id ) : null;
	}
}
