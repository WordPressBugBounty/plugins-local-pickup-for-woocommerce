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

/**
 * Local Pickup time adjustment.
 *
 * Helper object to adjust scheduling of a local pickup. It can be used to define
 * a lead time or a pickup deadline for scheduling a purchase order collection.
 *
 * This consists of units (integer) of time (an interval expressed as hours, days,
 * weeks or months). When a pickup location has set a lead time, customers in front
 * end that are scheduling a pickup for that location, they will be unable to choose
 * a slot that is before the lead time has past. When a pickup location has a
 * pickup deadline, the set value will be used as boundary for the calendar until
 * when it's possible to schedule a pickup collection.
 *
 * @since 1.0.0
 */
class DSLPFW_Local_Pickup_Location_Fee_Adjustment {

    /** @var int ID of the corresponding pickup location */
	private $location_id;

	/** @var null|float|string the fee adjustment value */
	protected $value;

    /**
	 * Fee adjustment constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param null|float|string $value for example "37%", "-50%", "10" or "-4.8" etc
	 * @param int $location_id optional, ID of the corresponding pickup location (useful to pass in hooks)
	 */
	public function __construct( $value = null, $location_id = 0 ) {

		if ( null !== $value ) {
			$this->value = $this->parse_value( $value );
		}

		$this->location_id = (int) $location_id;
	}

    /**
	 * Parse a string value to set the fee adjustment properties.
	 *
	 * @since 1.0.0
	 *
	 * @param int|float|string $value for example "37%", "-50%", "10" or "-4.8" etc
	 * @return float|string
	 */
	private function parse_value( $value ) {

        //We need to use this condition because float removes '%' sign and it conflict while get value
		if ( $this->is_percentage( $value ) ) {
			$pieces = explode( '%', $value );
			$amount = (float) $pieces[0];
			$value  = "{$amount}%";
		} else {
			$value  = (float) $value;
		}

		return $value;
	}

    /**
	 * Make a fee adjustment.
	 *
	 * Takes three arguments to output a numerical string as a standardized fee adjustment.
	 *
	 * @since 1.0.0
	 *
	 * @param string $adjustment either 'cost' or 'discount'
	 * @param int|float $amount an absolute number
	 * @param string $type either 'fixed' or 'percentage'
	 */
	public function set_value( $adjustment, $amount, $type ) {

		$amount = (float) $amount;

		if ( 'percentage' === $type ) {
			$adjustment = 'discount' === $adjustment ? "-{$amount}%" : "{$amount}%";
		} else {
			$adjustment = 'discount' === $adjustment ? "-{$amount}"  : (string) $amount;
		}

		$this->value = $adjustment;
	}


	/**
	 * Get the fee adjustment raw value.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_value() {
		return $this->value;
	}

    /**
	 * Whether the fee adjustment is a percentage.
	 *
	 * @since 1.0.0
	 *
	 * @param null|float|string check if a value is a percentage
	 * @return bool
	 */
	public function is_percentage( $value = null ) {
        
        $value = null !== $value ? $value : $this->value;

        if (!empty($value) && substr($value, -1) === '%') {
            // Remove the '%' sign and check if the remaining characters are digits
            $valuepart = substr($value, 0, -1);
            return is_numeric($valuepart);
        } else {
            return false;
        }
    }

    /**
	 * Whether the fee adjustment is a fixed amount.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_fixed() {
		return ! $this->is_percentage();
	}

    /**
	 * Whether the fee adjustment is invalid.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_null() {

		$amount = empty( $this->value ) ? null : $this->get_amount();

		return empty( $amount );
	}

    /**
	 * Whether the fee adjustment is a cost.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_cost() {
		return $this->get_amount() > 0;
	}


	/**
	 * Whether the fee adjustment is a discount.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_discount() {
		return $this->get_amount() < 0;
	}


	/**
	 * Get the amount of the fee adjustment as a number.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $absolute whether to return an absolute amount or relative (e.g. negative number)
	 * @return float
	 */
	public function get_amount( $absolute = false ) {

		$amount = $this->value;

		if ( null !== $amount && $this->is_percentage() ) {

			$pieces = explode( '%', $amount );
			$amount = $pieces[0];
		}

		if ( true === $absolute ) {
			$amount = abs( $amount );
		}

		return (float) $amount;
	}

    /**
	 * Get relative amount.
	 *
	 * @since 1.0.0
	 *
	 * @param int|float $base_amount the amount to calculate relative to
	 * @param bool $absolute whether to return an absolute amount or relative (e.g. negative number)
	 * @return float
	 */
	public function get_relative_amount( $base_amount, $absolute = false ) {

		$result      = 0;
		$base_amount = is_numeric( $base_amount ) ? (float) $base_amount : 0;

		if ( $base_amount >= 0 ) {

			$amount = $this->get_amount();
			$result = $base_amount > 0 || $amount > 0 ? $amount : 0;

			if ( $this->is_percentage() ) {

				$percentage = ( $base_amount * $this->get_amount( true ) ) / 100;
				$result     = $percentage;

				if ( $this->is_discount() ) {
					$result = "-{$percentage}";
				}
			}
		}

		return true === $absolute ? abs( $result ) : $result;
	}

    /**
	 * Get a fee adjustment input field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args array of input field arguments
	 * @return string HTML
	 */
	public function get_field_html( array $args ) {

		$args = wp_parse_args( $args, array(
			'name'     => '',
			'disabled' => false,
		) );

		if ( empty( $args['name'] ) || ! is_string( $args['name'] ) ) {
			return '';
		}

		ob_start();

		?>
            <div class="dslpfw-fee-adjustment-wrap">
                <div class="dslpfw_toggle_container">
                    <span class="dslpfw-type dslpfw-left-type"><?php echo esc_html__( 'Price', 'local-pickup-for-woocommerce' ); ?></span>
                    <label class="switch">
                        <input type="hidden" name="<?php echo esc_attr( $args['name'] ); ?>" value="no" />
                        <input type="checkbox" class="dslpfw-toggle-slider" name="<?php echo esc_attr( $args['name'] ); ?>" value="yes" <?php checked( true, $this->is_discount(), true )?> />
                        <div class="slider round"></div>
                    </label>
                    <span class="dslpfw-type dslpfw-right-type"><?php echo esc_html__( 'Discount', 'local-pickup-for-woocommerce' ); ?></span>
                </div>
                <div class="dslpfw-input-group">
                    <input type="number" name="<?php echo esc_attr( $args['name'] . '_amount' ); ?>" class="half-field" value="<?php echo esc_attr( $this->get_amount( true ) ); ?>" />
                </div>
                <div class="dslpfw_toggle_container">
                    <span class="dslpfw-type dslpfw-left-type"><?php echo esc_html__( 'Fixed', 'local-pickup-for-woocommerce' ); ?>(<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</span>
                    <label class="switch">
                        <input type="hidden" name="<?php echo esc_attr( $args['name'] ); ?>_type" value="no" />
                        <input type="checkbox" class="dslpfw-toggle-slider" name="<?php echo esc_attr( $args['name'] ); ?>_type" value="yes" <?php checked( true, $this->is_percentage(), true )?> />
                        <div class="slider round"></div>
                    </label>
                    <span class="dslpfw-type dslpfw-right-type"><?php echo esc_html__( 'Percentage', 'local-pickup-for-woocommerce' ); ?></span>
                </div>
            </div>
		<?php

		return ob_get_clean();
	}
}