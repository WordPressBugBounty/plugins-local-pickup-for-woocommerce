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
 * Pickup location address object.
 *
 * - Normalizes an address and its parts as an object.
 * - Comes with helper methods to convert a country or a state code to their corresponding long names.
 *
 * @since 1.0.0
 */
class DSLPFW_Local_Pickup_Location_Pickup_Hours {


	/** @var int ID of the corresponding pickup location */
	private $location_id;

	/** @var array associative array with pickup availability schedule */
	private $schedule = array();

	/** @var int starting day of the week as numerical entity (0 = Sunday, 6 = Saturday, default 1 = Monday) */
	private $start_of_week;

    /**
	 * Pickup hours constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $pickup_availability_schedule the availability schedule
	 * @param int $location_id optional, ID of the corresponding pickup location
	 */
	public function __construct( $pickup_availability_schedule = array(), $location_id = 0 ) {

		$this->start_of_week = (int) get_option( 'start_of_week', 1 );

		if ( ! empty( $pickup_availability_schedule ) ) {
			$this->schedule = $this->parse_schedule( $pickup_availability_schedule );
		}

		$this->location_id = (int) $location_id;
	}

    /**
	 * Parse pickup hours to weekday schedule.
	 *
	 * @since 1.0.0
	 *
	 * @param array $pickup_schedule pickup hours to parse
	 * @return array validated schedule
	 */
	private function parse_schedule( $pickup_schedule ) {
        
		$week = array();

        if( !empty($pickup_schedule) ) {
            $week = $pickup_schedule;
        }

        // sort days (array keys) according to the set start day of the week.
		uksort( $week, array( $this, 'sort_days_by_start_of_week' ) );
        
		return $week;
	}

    /**
	 * Set a new schedule.
	 *
	 * @since 1.0.0
	 *
	 * @param array $value schedule that will be parsed first, then set in the current object property
	 */
	public function set_schedule( array $value ) {

		$this->schedule = $this->parse_schedule( $value );
	}

    /**
	 * Get schedule as raw value.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_value() {
		return $this->schedule;
	}

    /**
     * Check wheather day has time for pickup or not
     * 
     * @param int $week_day day to get schedule availibility 
     * 
     * @since 1.0.0
     * 
     * @return boolean
     */
    public function is_day_available( $week_day ){

        //We have done this check because we are getting sunday as 0 and which can be treated as false
        if( empty($week_day) && !is_numeric($week_day) ) {
            return false;
        }
        
        $all_days = $this->get_value();

        if( isset( $all_days[$week_day], $all_days[$week_day]['status'] ) 
            && !empty( $all_days[$week_day] )
            && !empty( $all_days[$week_day]['status'] ) 
            && 'yes' === $all_days[$week_day]['status'] ) {
            return true;
        } else {
            return false;
        }
    }

    /**
	 * Get schedule for a given day.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $day day to get schedule for (returns the raw schedule if null)
	 * @param bool $one_line optional: whether to format the output schedule in a single string (true) instead of an array (false), default false
	 * @param int|null optional: if a timestamp is specified will skip previous slots (used to offset pickup hours based on other parameters, lead time, etc.)
	 * @return array|string
	 */
	public function get_schedule( $day = null, $one_line = false, $minimum_hours = null ) {
        
		$schedule = $this->get_value();

		if ( is_numeric( $day ) && isset( $schedule[ $day ] ) ) {

			$opening_hours = (array) $schedule[ (int) $day ]['time_range'];
			$schedule      = array();

			if ( ! empty( $opening_hours ) ) {

				$time_format = wc_time_format();

				foreach ( $opening_hours as $time_start => $time_end ) {

					if ( null !== $minimum_hours ) {
						if ( $minimum_hours > 0 && (int) $time_end <= $minimum_hours ) {
							continue;
						} elseif ( $minimum_hours > (int) $time_start ) {
							$time_start = $minimum_hours;
						}
					}

					if ( $time_start === $time_end ) {
						$schedule[] = date_i18n( $time_format, $time_start );
					} else {
						/* translators: Placeholders: %1$s - %2$s opening hours as time from-to */
						$schedule[] = sprintf( esc_html__( 'from %1$s to %2$s', 'local-pickup-for-woocommerce' ), date_i18n( $time_format, $time_start ), date_i18n( $time_format, $time_end ) );
					}
				}
			}

			if ( true === $one_line ) {
				if ( ! empty( $schedule ) ) {
					/* translators: Conjunction used to join together the penultimate and last item of a list of opening hours for pickup. */
					array_splice( $schedule, -2, 2, implode( ' ' . esc_html__( 'and', 'local-pickup-for-woocommerce' ) . ' ', array_slice( $schedule, -2, 2 ) ) );
					$schedule = implode( ', ', $schedule );
				} else {
					$schedule = '';
				}
			}
		}

		return $schedule;
	}

    /**
	 * Whether there are opening hours set.
	 *
	 * @since 1.0.0
	 *
	 * @param null|int $day optional, day of the week in 'w' format (from 0 to 6) or null to check if there's availability for any day of the week
	 * @param array $hours optional, a time range as an associative array to check whether the schedule matches certain times within the specified day
	 * @return bool
	 */
	public function has_schedule( $day = null, $hours = array() ) {
        
		$has_schedule = false;
		$schedule     = array();
		$from_time    = ! empty( $hours ) ? max( 0,              (int) key( $hours ) )     : 0;              // beginning of the day
		$within_time  = ! empty( $hours ) ? min( DAY_IN_SECONDS, (int) current( $hours ) ) : DAY_IN_SECONDS; // end of the day (24 hr)

		// when a day is specified we check if there's any slots for that day to begin with (otherwise the outcome will return false)
		if ( is_numeric( $day ) ) {
			$day              = (int) $day;
			$schedule[ $day ] = isset( $this->schedule[ $day ] ) && $this->is_day_available($day) ? $this->schedule[ $day ] : array();
		} else {
			$schedule         = $this->schedule;
		}

		// loop time ranges per day and check if they are within the specified $time ranges we need to check for (default range is any)
		if ( ! empty( $schedule ) ) {

			foreach ( $schedule as $times ) {

				if ( isset( $times['time_range'] ) && ! empty( $times['time_range'] ) && is_array( $times['time_range'] ) ) {

					foreach ( $times['time_range'] as $start_time => $end_time ) {

						if ( $end_time >= $from_time && $start_time <= $within_time ) {

							$has_schedule = true;
							break;
						}
					}
				}
			}
		}

		return $has_schedule;
	}

    /**
	 * Convert a numerical representation of a day of the week to its name as a string.
	 *
	 * Useful to convert a number to an entity that `strtotime()` can understand.
	 *
	 * @since 1.0.0
	 *
	 * @param int $day must be an integer between 0 (Sunday) and 6 (Saturday)
	 * @return string|null day of the week or null on error
	 */
	protected function get_day_of_week_data( $day = -1 ) {

		$days_of_the_week = array(
			0 => 'Sunday',
			1 => 'Monday',
			2 => 'Tuesday',
			3 => 'Wednesday',
			4 => 'Thursday',
			5 => 'Friday',
			6 => 'Saturday',
		);

		return isset( $days_of_the_week[ (int) $day ] ) ? $days_of_the_week[ (int) $day ] : $days_of_the_week;
	}

    /**
	 * Get a pickup hours input field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args an array of arguments
	 * @return string HTML
	 */
	public function get_field_html( array $args ) {

		$args = wp_parse_args( $args, array(
			'name' => '',
		) );

		if ( empty( $args['name'] ) || ! is_string( $args['name'] ) ) {
			return '';
		}

        // Get data for pickup hours
		$schedule = $this->get_value();

        // Get list of days
        $days_array = $this->get_day_of_week_data();

        // Sort them by WordPress general setting
        uksort( $days_array, array( $this, 'sort_days_by_start_of_week' ) );

		ob_start();
		?>
		<div class="time-range-main">
            <?php foreach( $days_array as $day_id => $day_name ){ 
                $day_status = !empty($schedule[$day_id]['status']) ? sanitize_text_field($schedule[$day_id]['status']) : 'no'; ?>
                <div class="dslpfw-time-range-group element-shadow">
                    <h2 class="dslpfw-group-header">
                        <div class="dslpfw-title-group"> 
                            <label class="switch">
                                <input type="hidden" name="<?php echo esc_attr( $args['name'] ); ?>[<?php echo esc_attr($day_id); ?>][status]" value="no" />
                                <input type="checkbox" name="<?php echo esc_attr( $args['name'] ); ?>[<?php echo esc_attr($day_id); ?>][status]" value="yes" <?php checked( $day_status, 'yes', true )?> />
                                <div class="slider round"></div>
                            </label>
                            <a href="#" class="add-pickup-time-range button" data-day-id="<?php echo esc_attr($day_id); ?>">+ <?php esc_html_e('Add', 'local-pickup-for-woocommerce'); ?></a>
                            <span class="group-title"><?php echo esc_html($day_name); ?></span>
                        </div> 
                        <div class="dslpfw-group-actions">
                            <a href="#" title="Toggle" class="dslpfw-action dslpfw-toggle-group <?php echo empty($schedule[$day_id]['time_range']) ? esc_attr("dslpfw-toggle-group-hide") : esc_attr("dslpfw-toggle-group-show"); ?>" >
                                <?php esc_html_e('Expand', 'local-pickup-for-woocommerce'); ?>
                            </a> 
                        </div>
                    </h2>
                    <div class="time-range-wrap">
                        <?php if( !empty($schedule[$day_id]['time_range'])) {
                            foreach( $schedule[$day_id]['time_range'] as $start_time => $end_time ) { ?>
                                <div class="time-range">
                                    <div class="time-text-wrap">
                                        <p><span class="pickup-start-time"></span> - <span class="pickup-end-time"></span></p>
                                        <div class="action-buttons">
                                            <a href="#" class="delete-pickup-time-range"><i class="dashicons dashicons-trash"></i></a>
                                        </div>
                                    </div>
                                    <div class="sliders-step">
                                        <div class="slider-range"></div>
                                        <input type="hidden" name="<?php echo esc_attr( $args['name'] ); ?>[<?php echo esc_attr($day_id); ?>][start][]" class="pickup_hours_start" value="<?php echo esc_attr($start_time); ?>" />
                                        <input type="hidden" name="<?php echo esc_attr( $args['name'] ); ?>[<?php echo esc_attr($day_id); ?>][end][]" class="pickup_hours_end" value="<?php echo esc_attr($end_time); ?>" />
                                    </div>
                                </div>
                                <?php 
                            } 
                        } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
        <div class="time-range-clone" style="display:none">
            <div class="time-range">
                <div class="time-text-wrap">
                    <p><span class="pickup-start-time"></span> - <span class="pickup-end-time"></span></p>
                    <div class="action-buttons">
                        <a href="#" class="delete-pickup-time-range"><i class="dashicons dashicons-trash"></i></a>
                    </div>
                </div>
                <div class="sliders-step">
                    <div class="slider-range"></div>
                    <input type="hidden" name="<?php echo esc_attr( $args['name'] ); ?>[{day}][start][]" class="pickup_hours_start" />
                    <input type="hidden" name="<?php echo esc_attr( $args['name'] ); ?>[{day}][end][]" class="pickup_hours_end" />
                </div>
            </div>
        </div>
		<?php

		return ob_get_clean();
	}

    /**
	 * Helper method to get a normalized pickup hours array from field data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $field_name field name
	 * @param array $field_data field posted data
	 * @return array
	 */
	public function get_field_value( $field_name, $field_data ) {

        //Remove placeholder from array
        unset($field_data[$field_name]['{day}']);
        
        $final_time_data = array();
        
        if( isset( $field_data[$field_name] ) && count($field_data[$field_name]) > 0 ) {
            foreach( $field_data[$field_name] as $day_id => $day_data ){

                //Status
                $final_time_data[$day_id]['status'] = !empty($day_data['status']) && isset($day_data['status']) && 'yes' === $day_data['status'] ? $day_data['status'] : 'no';

                //Start and end time
                if( isset($day_data['start']) && count($day_data['start']) > 0 ){
                    foreach( $day_data['start'] as $key => $time ){
                        $final_time_data[$day_id]['time_range'][$time] = $day_data['end'][$key];
                    }
                }
            }
        }
        
        return $final_time_data;
    }

    /**
	 * Sorts days according to the set starting day of the week.
	 *
	 * @since 1.0.0
	 *
	 * @param int $day_1 first day to compare as an integer representation
	 * @param int $day_2 second day to compare as an integer representation
	 * @return int 1 if any day is greater than start of week or -1
	 */
	private function sort_days_by_start_of_week( $day_1, $day_2 ) {

        $indexA = ($day_1 - $this->start_of_week + 7) % 7;
        $indexB = ($day_2 - $this->start_of_week + 7) % 7;

        return ($indexA < $indexB) ? -1 : 1;
	}
}
