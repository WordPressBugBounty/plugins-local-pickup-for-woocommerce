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
namespace DotStore\DSLPFW_Local_Pickup_Woocommerce\Pickup_Locations\Pickup_Location;

defined( 'ABSPATH' ) or exit;
use DotStore\DSLPFW_Local_Pickup_Woocommerce\Appointments\DSLPFW_Local_Pickup_WooCommerce_Appointment;
use DotStore\DSLPFW_Local_Pickup_Woocommerce\Appointments\DSLPFW_Local_Pickup_WooCommerce_Timezones;
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
class DSLPFW_Local_Pickup_Location_Appointments {
    /** @var \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location|null */
    private $pickup_location;

    /**
     * Pickup location appointments handler.
     *
     * @since 1.0.0
     *
     * @param \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $pickup_location pickup location object appointments should relate to
     */
    public function __construct( \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $pickup_location ) {
        $this->pickup_location = $pickup_location;
    }

    /**
     * Gets the pickup location.
     *
     * @since 1.0.0
     *
     * @return \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location|null
     */
    private function get_pickup_location() {
        return $this->pickup_location;
    }

    /**
     * Gets the duration of an appointment given the
     *
     * @since 1.0.0
     *
     * TODO in the future, if pickup locations become able to override the global setting, this method should retrieve the duration from a pickup location's meta and use the global setting as default fallback 
     *
     * @param \DateTime $date datetime object to get duration for that day
     * @return int duration as a partial timestamp
     */
    public function get_appointment_duration( \DateTime $date ) {
        if ( dslpfw_shipping_method()->is_anytime_appointments_enabled() ) {
            $start_date = $this->get_first_available_pickup_time( $date );
            $start_time = $end_time = $start_date->getTimestamp();
            $pickup_location = $this->get_pickup_location();
            if ( $pickup_location ) {
                $raw_schedule = $pickup_location->get_pickup_hours()->get_schedule();
                if ( !empty( $raw_schedule[$start_date->format( 'w' )] ) ) {
                    $end_hours = array_pop( $raw_schedule[$start_date->format( 'w' )] );
                    if ( !empty( $end_hours ) && is_numeric( $end_hours ) ) {
                        $end_time += $end_hours;
                    }
                }
            }
            $duration = max( 0, $end_time - $start_time );
        } else {
            $duration = dslpfw_shipping_method()->dslpfw_get_appointment_duration();
        }
        return $duration;
    }

    /**
     * Calculates the first available pickup time for this location.
     *
     * Takes into consideration the pickup location's:
     * - @see \DSLPFW_Local_Pickup_Location_Schedule_Adjustment lead time
     * - @see \DSLPFW_Local_Pickup_Location_Pickup_Hours business hours
     * - @see \DSLPFW_Local_Pickup_Location_Holiday_Dates holidays calendar
     *
     * @since 1.0.0
     *
     * @param \DateTime $start_date the requested date for which day we should display the first available appointment slot
     * @return \DateTime first available pickup time in the timezone of the supplied start date
     */
    public function get_first_available_pickup_time( \DateTime $start_date ) {
        $pickup_location = $this->get_pickup_location();
        if ( empty( $pickup_location ) ) {
            return $start_date;
        }
        // maybe convert start date to the location timezone because business hours are in the location timezone
        if ( !DSLPFW_Local_Pickup_WooCommerce_Timezones::is_same_timezone( $pickup_location->get_address()->get_timezone(), $start_date->getTimezone() ) ) {
            $first_pickup_time = ( clone $start_date )->setTimezone( $pickup_location->get_address()->get_timezone() );
            $convert_to_timezone = $start_date->getTimezone();
        } else {
            $first_pickup_time = clone $start_date;
            $convert_to_timezone = false;
        }
        // convert first pickup date back to the original timezone
        if ( $convert_to_timezone ) {
            $first_pickup_time->setTimezone( $convert_to_timezone );
        }
        return $first_pickup_time;
    }

    /**
     * Gets an array of available pickup times for this location on a given date.
     *
     * Takes into consideration the appointment duration and the pickup location's:
     * - @see \DSLPFW_Local_Pickup_Location_Schedule_Adjustment lead time
     * - @see \DSLPFW_Local_Pickup_Location_Schedule_Adjustment deadline
     * - @see \DSLPFW_Local_Pickup_Location_Pickup_Hours business hours
     * - @see \DSLPFW_Local_Pickup_Location_Holiday_Dates holidays calendar
     *
     * @since 1.0.0
     *
     * @param \DateTime $which_date start of the day for which we want to get available times
     * @return \DateTime[] in the timezone of the supplied start date
     */
    public function get_available_times( \DateTime $which_date ) {
        $available_times = [];
        $pickup_location = $this->get_pickup_location();
        if ( $pickup_location ) {
            // clone the given date to avoid changing the original object and set date to start of day
            $date = ( clone $which_date )->setTime( 0, 0, 0 );
            $shipping_method = dslpfw_shipping_method();
            $location_timezone = $pickup_location->get_address()->get_timezone();
            $duration = $shipping_method->dslpfw_get_appointment_duration();
            try {
                // get the first possible pickup time from now (now is the start date for the lead time calculation)
                $possible_first_pickup_time = $this->get_first_available_pickup_time( new \DateTime('now', $location_timezone) );
                $first_pickup_date = ( clone $possible_first_pickup_time )->setTime( 0, 0, 0 );
                $duration_interval = new \DateInterval("PT{$duration}S");
                $deadline_last_pickup_time = false;
            } catch ( \Exception $e ) {
                return [];
            }
            if ( $date->format( 'Y-m-d' ) === $first_pickup_date->format( 'Y-m-d' ) ) {
                // date is the first available date, first time has to consider lead time
                $first_pickup_time = $possible_first_pickup_time;
            } elseif ( $date > $first_pickup_date ) {
                // date is after the first available date, first time will be the opening time
                $first_pickup_time = $date;
            } else {
                // date is before the first available date, no pickup times are available
                $first_pickup_time = false;
            }
            // given date is not before lead time or after the deadline
            if ( $first_pickup_time && (!$deadline_last_pickup_time || $date < $deadline_last_pickup_time) ) {
                $schedules = $pickup_location->get_pickup_hours()->get_schedule();
                $week_day = $date->format( 'w' );
                $is_public_holiday = false;
                // not a holiday and has business hours set
                if ( !empty( $schedules[$week_day] ) && isset( $schedules[$week_day]['time_range'] ) && !empty( $schedules[$week_day]['time_range'] ) && !$is_public_holiday && $pickup_location->get_pickup_hours()->is_day_available( $week_day ) ) {
                    // get schedules for the day of the week
                    $day_schedules = $schedules[$week_day]['time_range'];
                    foreach ( $day_schedules as $opening_time_offset => $closing_time_offset ) {
                        try {
                            $opening_time = ( clone $date )->add( new \DateInterval("PT{$opening_time_offset}S") );
                            $closing_time = ( clone $date )->add( new \DateInterval("PT{$closing_time_offset}S") );
                        } catch ( \Exception $e ) {
                            continue;
                        }
                        $next_pickup_time = $opening_time;
                        $last_pickup_time = ( clone $closing_time )->sub( $duration_interval );
                        // check whether next pickup time is at most the last possible pickup time for this range and occurs before the deadline, if a deadline is set
                        while ( $next_pickup_time <= $last_pickup_time && (!$deadline_last_pickup_time || $next_pickup_time <= $deadline_last_pickup_time) ) {
                            if ( $next_pickup_time >= $first_pickup_time ) {
                                $available_times[] = $next_pickup_time;
                            }
                            // add duration in seconds
                            $next_pickup_time = ( clone $next_pickup_time )->add( $duration_interval );
                        }
                    }
                }
            }
        }
        return $available_times;
    }

    /**
     * Removes pickup times that already have the allowed number of appointments scheduled.
     *
     * @since 1.0.0
     *
     * @param \DateTime[] $available_times available pickup times for this pickup location
     * @param array $scheduled_appointments number of scheduled appointments organized by their start time
     * @param int $max_appointments max number of appointments that can be scheduled for a given pickup time
     * @param int $appointment_duration appointment duration in seconds
     * @return \DateTime[]
     */
    private function filter_available_times(
        $available_times,
        $scheduled_appointments,
        $max_appointments,
        $appointment_duration
    ) {
        // use an iterator to move through the array of scheduled appointments as we loop over the available pickup times
        $appointments_iterator = ( new \ArrayObject($scheduled_appointments) )->getIterator();
        $filtered_times = [];
        foreach ( $available_times as $pickup_time ) {
            $number_of_appointments = 0;
            // continue checking scheduled appointments from the previous position in the array
            // check scheduled appointments that occur before or during the current pickup time
            while ( $appointments_iterator->key() && $appointments_iterator->key() - $pickup_time->getTimestamp() < $appointment_duration ) {
                // only count scheduled appointments that occur during the current pickup time
                if ( $appointments_iterator->key() >= $pickup_time->getTimestamp() ) {
                    $number_of_appointments += $appointments_iterator->current();
                }
                $appointments_iterator->next();
            }
            // keep the pickup time if the current number of scheduled appointments is less than the max allowed
            if ( $number_of_appointments < $max_appointments ) {
                $filtered_times[] = $pickup_time;
            }
        }
        return $filtered_times;
    }

    /**
     * Returns the schedule minimum available time based on lead time.
     *
     * @since 1.0.0
     *
     * @param \DateTime $chosen_time the chosen pickup date
     * @return null|int the minimum time (as in hours in seconds) or null if no minimum
     */
    public function get_schedule_minimum_hours( $chosen_time ) {
        $minimum_hours = null;
        $pickup_location = $this->get_pickup_location();
        if ( $pickup_location ) {
            try {
                $first_pickup_time = $this->get_first_available_pickup_time( new \DateTime('now', $pickup_location->get_address()->get_timezone()) );
            } catch ( \Exception $e ) {
                return $minimum_hours;
            }
            // is it the same day?
            if ( $first_pickup_time && $first_pickup_time->format( 'Y-m-d' ) === $chosen_time->format( 'Y-m-d' ) ) {
                $minimum_hours = $first_pickup_time->getTimestamp() - $chosen_time->getTimestamp();
                // plugin schedule system advances hours by quarters, so we round the minutes to the nearest quarter
                $quarter = 15 * MINUTE_IN_SECONDS;
                // 15 minutes in seconds
                $minimum_hours = ceil( $minimum_hours / $quarter ) * $quarter;
            }
        }
        return $minimum_hours;
    }

}
