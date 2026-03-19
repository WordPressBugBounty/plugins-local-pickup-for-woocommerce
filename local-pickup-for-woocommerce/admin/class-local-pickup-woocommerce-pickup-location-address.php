<?php

/**
 * Admin side pickup location custom post type address meta store and get methods
 *
 * @link       https://www.thedotstore.com/
 * @since      1.0.0
 *
 * @package    DSLPFW_Local_Pickup_Woocommerce
 * @subpackage DSLPFW_Local_Pickup_Woocommerce/admin
 */

defined( 'ABSPATH' ) or exit;

use DotStore\DSLPFW_Local_Pickup_Woocommerce\Appointments\DSLPFW_Local_Pickup_WooCommerce_Timezones;

/**
 * Pickup location address object.
 *
 * - Normalizes an address and its parts as an object.
 * - Comes with helper methods to convert a country or a state code to their corresponding long names.
 *
 * @since 1.0.0
 */
class DSLPFW_Local_Pickup_Location_Address {

    /** @var int ID of the corresponding pickup location */
	private $location_id;

    /* @var string place name */
	private $name = '';

    /** @var string address line 1 */
	private $address_1 = '';

	/** @var string address line 2 */
	private $address_2 = '';

	/** @var string city */
	private $city = '';

    /** @var string state */
	private $state = '';

	/** @var string country */
	private $country = '';

	/** @var string postcode */
	private $postcode = '';

    /**
	 * Address constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $address address in array format
	 * @param int $location_id optional, ID of the corresponding pickup location (useful to pass in hooks)
	 */
	public function __construct( $address = array(), $location_id = 0 ) {

		$address = $this->parse_address( $address );

		$this->set_properties( $address );

		$this->location_id = (int) $location_id;
	}

    /**
	 * Set address pieces for the current object.
	 *
	 * @since 1.0.0
	 *
	 * @param array $address associative array
	 */
	private function set_properties( $address ) {

		foreach ( $address as $key => $value ) {

			if ( 'location_id' !== $key && property_exists( $this, $key ) ) {

				// country and state codes must be exactly 2-digits long
				if ( in_array( $key, array( 'country', 'state' ), true ) ) {

					$this->$key = '';

					if ( strlen( $key ) !== 2 ) {
						$this->$key = strtoupper( $value );
					}

				} else {

					$this->$key = $value;
				}
			}
		}
	}

    /**
	 * Parse address.
	 *
	 * @since 1.0.0
	 *
	 * @param array $address associative array
	 * @return array
	 */
	private function parse_address( $address = array() ) {

		$address = wp_parse_args( $address, array(
			'name'      => '',
			'country'   => '',
			'state'     => '',
			'postcode'  => '',
			'city'      => '',
			'address_1' => '',
			'address_2' => '',
		) );

		return $address;
	}

    /**
	 * Set address.
	 *
	 * @since 1.0.0
	 *
	 * @param array $address associative array
	 */
	public function set_address( array $address ) {

		$address = $this->parse_address( $address );

		$this->set_properties( $address );
	}

    /**
	 * Get the address in array format.
	 *
	 * @since 1.0.0
	 *
	 * @return array associative array
	 */
	public function get_array() {
		return array(
			'country'   => $this->get_country(),
			'state'     => $this->get_state(),
			'postcode'  => $this->get_postcode(),
			'city'      => $this->get_city(),
			'address_1' => $this->get_address_1(),
			'address_2' => $this->get_address_2(),
		);
	}

    /**
	 * Get the country code.
	 *
	 * @since 1.0.0
	 *
	 * @return string a two or three characters code
	 */
	public function get_country() {

		return $this->country;
	}

    /**
	 * Get the country name.
	 *
	 * @since 1.0.0
	 *
	 * @return string country full name
	 */
	public function get_country_name() {

		$country = $this->get_country();

		if ( ! empty( $country ) ) {
			$countries = WC()->countries->get_countries();
			$country    = isset( $countries[ $country ] ) ? $countries[ $country ] : '';
		}

		return stripslashes( $country );
	}

    /**
	 * Get the state code.
	 *
	 * @since 1.0.0
	 *
	 * @return string a two or three characters code
	 */
	public function get_state() {

		return $this->state;
	}

    /**
	 * Checks whether the address has a state code.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function has_state() {

		$state = $this->get_state();

		return ! empty( $state );
	}

    /**
	 * Get the state name.
	 *
	 * @since 1.0.0
	 *
	 * @return string state full name
	 */
	public function get_state_name() {

		$state = $this->get_state();
        $country = $this->get_country();

		if ( ! empty( $state ) && ! empty( $country ) ) {
			$states = WC()->countries->get_states( $country );
			$state  = isset( $states[ $state ] ) ? $states[ $state ] : '';
		}

		return stripslashes( $state );
	}

    /**
	 * Get a country-state code for this address. We can use this to select in wc-selecrt-enhaced dropdwon also
	 *
	 * @since 1.0.0
	 *
	 * @param string $sep Optional separator, defaults to ":" colon standard used in WooCommerce
	 *
	 * @return string single 2-digit code or couple of 2-digit codes separated by $sep
	 */
	public function get_country_state_code( $sep = ':' ) {

		$code = '';

		if ( '' !== $this->country ) {
			$code = '' === $this->state ? $this->country : $this->country . $sep . $this->state;
		}

		return $code;
	}

    /**
	 * Get the postcode.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_postcode() {
		return stripslashes( $this->postcode );
	}

    /**
	 * Checks whether the location has a postcode.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function has_postcode() {

		$postcode = $this->get_postcode();

		return ! empty( $postcode );
	}

    /**
	 * Get the address line 1.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_address_1() {
		return stripslashes( $this->address_1 );
	}


	/**
	 * Get the address line 2.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_address_2() {
		return stripslashes( $this->address_2 );
	}

    /**
	 * Get the city.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_city() {
		return stripslashes( $this->city );
	}

    /**
	 * Get the address.
	 *
	 * @since 1.0.0
	 *
	 * @param string $format either 'array' or 'string'
	 * @param string $separator optional, valid for 'string' return format, default `<br>` HTML line break
	 * @return array|string|null address in the specified $format
	 */
	public function get_street_address( $format = 'array', $separator = '<br>' ) {

		$address = array_unique( array(
			$this->get_address_1(),
			$this->get_address_2(),
		) );

		if ( 'array' === $format ) {
			return $address;
		} elseif ( 'string' === $format && is_string( $separator ) ) {
			return implode( $separator, $address );
		}

		return null;
	}

    /**
	 * Checks whether the location has a city defined.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function has_city() {

		$city = $this->get_city();

		return ! empty( $city );
	}

    /**
	 * Get the place name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name() {
		return stripslashes( $this->name );
	}


	/**
	 * Gets the ID of a pickup location associated with the address, if any
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function get_pickup_location_id() {

		return $this->location_id;
	}

    /**
	 * Get the address in HTML according to location's country format.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $one_line whether to return address as a single line (true) or multiple lines with line breaks (false, default)
	 * @return string HTML
	 */
	public function get_formatted_html( $one_line = false ) {

		$formatted = '';

		if ( $this->get_country() ) {

			// pass empty defaults to WC otherwise we might get a bunch of notices
			$formatted = WC()->countries->get_formatted_address( array_merge( array(
				'first_name' => '',
				'last_name'  => '',
				'country'    => '',
				'state'      => '',
			), $this->get_array() ) );

			if ( true === $one_line ) {
				$formatted = str_replace( array( '<br>', '<br/>', '<br />', "\n" ), ' ', $formatted );
			}
		}

		return $formatted;
	}

    /**
	 * Gets the timezone matching the address.
	 *
	 * @since 1.0.0
	 *
	 * @return \DateTimeZone defaults to site timezone
	 */
	public function get_timezone() {

		return DSLPFW_Local_Pickup_WooCommerce_Timezones::get_timezone_from_address( $this );
	}
}