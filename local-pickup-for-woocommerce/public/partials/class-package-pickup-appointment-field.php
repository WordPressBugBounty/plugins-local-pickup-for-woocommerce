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
namespace DotStore\DSLPFW_Local_Pickup_Woocommerce\Fields;

defined( 'ABSPATH' ) or exit;
use DotStore\DSLPFW_Local_Pickup_Woocommerce\Data_Store\DSLPFW_Package_Pickup_Data;
/**
 * Component to render items details for items in a shipping package.
 *
 * @since 1.0.0
 */
class DSLPFW_Package_Pickup_Appointment_Field extends DSLPFW_Pickup_Location_Field {
    /** @var int|string key index of current package this field is associated to */
    private $package_id;

    /** @var array associative array to cache the latest pickup date (values) associated to a pickup location id (keys) */
    private $location_pickup_date = [];

    /** @var DSLPFW_Package_Pickup_Data data store for the package */
    private $data_store;

    /**
     * Field constructor.
     *
     * @since 1.0.0
     *
     * @param int|string $package_id the package key index
     */
    public function __construct( $package_id ) {
        $this->object_type = 'package';
        $this->package_id = $package_id;
        $this->data_store = new DSLPFW_Package_Pickup_Data($package_id);
    }

    /**
     * Get the ID of the package for this field.
     *
     * @since 1.0.0
     *
     * @return int|string
     */
    private function get_package_id() {
        return $this->package_id;
    }

    /**
     * Get any set pickup appointment for the package pickup.
     *
     * @since 1.0.0
     *
     * @return string a date as a string
     */
    private function get_pickup_date() {
        $pickup_date = '';
        $pickup_location_id = $this->data_store->get_pickup_location_id();
        if ( 0 === $pickup_location_id || !$this->pickup_location_has_changed() ) {
            $pickup_date = $this->data_store->get_pickup_data( 'pickup_date' );
        }
        if ( empty( $pickup_date ) ) {
            $pickup_date = ( array_key_exists( $pickup_location_id, $this->location_pickup_date ) ? $this->location_pickup_date[$pickup_location_id] : '' );
        } else {
            $this->location_pickup_date[$pickup_location_id] = $pickup_date;
        }
        return $pickup_date;
    }

    /**
     * Detect whether the pickup location ID was updated by the user.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    private function pickup_location_has_changed() {
        $package_session_data = $this->data_store->get_pickup_data();
        $pickup_location_id = $this->data_store->get_pickup_location_id();
        return !empty( $package_session_data['pickup_location_id'] ) && $pickup_location_id !== (int) $package_session_data['pickup_location_id'];
    }

    /**
     * Get the field HTML.
     *
     * @since 1.0.0
     *
     * @return string HTML
     */
    public function get_html() {
        $field_html = '';
        $shipping_method = dslpfw_shipping_method();
        $mode = $shipping_method->pickup_appointments_mode();
        $chosen_location = $this->data_store->get_pickup_location();
        $chosen_date = $this->get_pickup_date();
        $chosen_offset = $this->data_store->get_pickup_data( 'appointment_offset' );
        if ( $chosen_location && is_checkout() && 'disabled' !== $shipping_method->pickup_appointments_mode() ) {
            ob_start();
            ?>
			<div
				id="pickup-appointment-field-for-<?php 
            echo esc_attr( $this->get_package_id() );
            ?>"
				class="form-row pickup-location-field pickup-location-field-<?php 
            echo sanitize_html_class( $shipping_method->pickup_selection_mode() );
            ?> pickup-location-<?php 
            echo sanitize_html_class( $this->get_object_type() );
            ?>-field"
				data-pickup-object-id="<?php 
            echo esc_attr( $this->get_package_id() );
            ?>">

				<div class="dslpfw-pickup-location-appointment update_totals_on_change">

					<div class="pickup-location-calendar">

						<small class="pickup-location-field-label">
							<?php 
            esc_html_e( 'Schedule a pickup appointment (optional)', 'local-pickup-for-woocommerce' );
            ?>
						</small>

						<input
							type="hidden"
							id="dslpfw-pickup-date-<?php 
            echo esc_attr( $this->get_package_id() );
            ?>"
							class="dslpfw-pickup-location-appointment-date-alt"
							name="_shipping_method_pickup_date[<?php 
            echo esc_attr( $this->get_package_id() );
            ?>]"
							value="<?php 
            echo esc_attr( $chosen_date );
            ?>"
						/>

						<div class="form-row" style="white-space: nowrap;position: relative;">
                            <?php 
            ?>
                                <input
                                    type="text"
                                    readonly="readonly"
                                    placeholder="<?php 
            echo esc_attr_x( 'Pickup Date', 'Placeholder text for the datepicker field', 'local-pickup-for-woocommerce' );
            ?>"
                                    id="dslpfw-datepicker-<?php 
            echo esc_attr( $this->get_package_id() );
            ?>"
                                    class="dslpfw-pickup-location-appointment-date input-text"
                                    value="<?php 
            echo esc_attr( $chosen_date );
            ?>"
                                    data-location-id="<?php 
            echo esc_attr( $chosen_location->get_id() );
            ?>"
                                    data-package-id="<?php 
            echo esc_attr( $this->get_package_id() );
            ?>"
                                    data-pickup-date="<?php 
            echo esc_attr( $chosen_date );
            ?>"
                                /> 
                                <?php 
            ?>
						</div>

						<?php 
            ?>
                            <small class="pickup-location-field-label">
                                <a href="#" id="dslpfw-date-clear-<?php 
            echo esc_attr( $this->get_package_id() );
            ?>">
                                    <?php 
            echo esc_html_x( 'Clear', 'Clear a chosen pickup appointment date', 'local-pickup-for-woocommerce' );
            ?>
                                </a>
                            </small>
                            <?php 
            ?>

						<div class="dslpfw-pickup-location-schedule" <?php 
            if ( empty( $chosen_date ) ) {
                echo ' style="display:none;" ';
            }
            ?>>
							<?php 
            try {
                $chosen_datetime = ( !empty( $chosen_date ) && is_string( $chosen_date ) ? new \DateTime($chosen_date, $chosen_location->get_address()->get_timezone()) : null );
                $available_times = ( !empty( $chosen_date ) ? $chosen_location->get_appointments()->get_available_times( $chosen_datetime ) : [] );
            } catch ( \Exception $e ) {
                $available_times = [];
            }
            $chosen_day = ( !empty( $chosen_datetime ) ? $chosen_datetime->format( 'w' ) : null );
            $minimum_hours = ( !empty( $chosen_datetime ) ? $chosen_location->get_appointments()->get_schedule_minimum_hours( $chosen_datetime ) : null );
            $opening_hours = ( null !== $chosen_day ? $chosen_location->get_pickup_hours()->get_schedule( $chosen_day, false, $minimum_hours ) : null );
            if ( !empty( $chosen_datetime ) ) {
                $timezone_string = $chosen_datetime->format( 'T' );
                // check if it is an offset
                if ( !empty( intval( $timezone_string ) ) ) {
                    // use full name instead
                    $timezone_string = $chosen_datetime->format( 'e' );
                }
            }
            if ( !dslpfw_shipping_method()->is_anytime_appointments_enabled() ) {
                ?>
								<small class="pickup-location-field-label">
									<?php 
                printf( 
                    /* translators: Placeholder: %1$s - day of the week name, %2$s - location timezone */
                    esc_html__( 'Available pickup times on %1$s (all times in %2$s):', 'local-pickup-for-woocommerce' ),
                    '<strong>' . esc_html( date_i18n( 'l', strtotime( $chosen_date ) ) ) . '</strong>',
                    ( !empty( $timezone_string ) ? esc_html( $timezone_string ) : esc_html__( 'the location timezone', 'local-pickup-for-woocommerce' ) )
                 );
                ?>
								</small>
								<?php 
                if ( $available_times ) {
                    $start_of_the_day = ( clone $available_times[0] )->setTime( 0, 0, 0 );
                    ?>
									<select
										id="dslpfw-pickup-appointment-offset-<?php 
                    echo esc_attr( $this->get_package_id() );
                    ?>"
										class="dslpfw-pickup-location-appointment-offset"
										name="_shipping_method_pickup_appointment_offset[<?php 
                    echo esc_attr( $this->get_package_id() );
                    ?>]"
										style="width:100%;">
										<?php 
                    foreach ( $available_times as $datetime ) {
                        ?>

											<?php 
                        $offset = $datetime->getTimestamp() - $start_of_the_day->getTimestamp();
                        ?>

											<?php 
                        // all in the same line to avoid empty space and new lines to show up in the browser tooltip for the selected option
                        ?>
											<option value="<?php 
                        echo esc_attr( $offset );
                        ?>" <?php 
                        selected( $offset, $chosen_offset );
                        ?>><?php 
                        echo esc_html( date_i18n( wc_time_format(), $datetime->getTimestamp() + $datetime->getOffset() ) );
                        ?></option>

										<?php 
                    }
                    ?>
									</select>

									<?php 
                }
            } elseif ( !empty( $opening_hours ) ) {
                ?>
								<small class="pickup-location-field-label">
									<?php 
                printf( 
                    /* translators: Placeholder: %s - day of the week name */
                    esc_html__( 'Opening hours for pickup on %s:', 'local-pickup-for-woocommerce' ),
                    '<strong>' . esc_html( date_i18n( 'l', strtotime( $chosen_date ) ) ) . '</strong>'
                 );
                ?>
								</small>
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
								<input
									type="hidden"
									name="_shipping_method_pickup_appointment_offset[<?php 
                echo esc_attr( $this->get_package_id() );
                ?>]"
									value="<?php 
                echo esc_attr( (int) $minimum_hours );
                ?>"
								/>
								<?php 
            }
            ?>
						</div>

					</div>

				</div>

			</div>
			<?php 
            $field_html .= ob_get_clean();
        }
        /**
         * Filter the package pickup appointment field HTML.
         *
         * @since 1.0.0
         *
         * @param string $field_html input field HTML
         * @param int|string $package_id the current package identifier
         * @param array $package the current package array
         */
        return (string) apply_filters(
            'dslpfw_get_package_pickup_appointment_field_html',
            $field_html,
            $this->get_package_id(),
            $this->data_store->get_package()
        );
    }

}
