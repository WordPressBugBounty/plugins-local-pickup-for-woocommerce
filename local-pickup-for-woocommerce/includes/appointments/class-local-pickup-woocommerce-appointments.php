<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.thedotstore.com/
 * @since      1.0.0
 *
 * @package    DSLPFW_Local_Pickup_Woocommerce
 * @subpackage DSLPFW_Local_Pickup_Woocommerce/includes
 */
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    DSLPFW_Local_Pickup_Woocommerce
 * @subpackage DSLPFW_Local_Pickup_Woocommerce/includes
 * @author     theDotstore <support@thedotstore.com>
 */
namespace DotStore\DSLPFW_Local_Pickup_Woocommerce\Appointments;

defined( 'ABSPATH' ) or exit;
use DotStore\DSLPFW_Local_Pickup_Woocommerce\Appointments\DSLPFW_Local_Pickup_WooCommerce_Appointment;
class DSLPFW_Local_Pickup_WooCommerce_Appointments {
    /**
     * Gets an array of appointments for the given order.
     *
     * @since 1.0.0
     *
     * @param int|\WC_Order $order_id the ID of an order ar an order object
     * @return DSLPFW_Local_Pickup_WooCommerce_Appointment[]
     */
    public function get_order_appointments( $order_id ) {
        $appointments = [];
        $order = ( $order_id instanceof \WC_Order ? $order_id : wc_get_order( $order_id ) );
        if ( $order ) {
            foreach ( $order->get_shipping_methods() as $shipping_item_id => $shipping_item ) {
                $appointment = $this->get_shipping_item_appointment( $shipping_item );
                if ( $appointment ) {
                    $appointments[$shipping_item_id] = $appointment;
                }
            }
        }
        return $appointments;
    }

    /**
     * Gets the appointment object associated with the given shipping item.
     *
     * @since 1.0.0
     *
     * @param int|\WC_Order_Item_Shipping $shipping_item_id the ID of a shipping item or a shipping item object
     * @return null|DSLPFW_Local_Pickup_WooCommerce_Appointment the appointment object or null if the shipping item is invalid or doesn't have an appointment date
     */
    public function get_shipping_item_appointment( $shipping_item_id ) {
        // an invalid $shipping_item_id would create an empty appointment object
        $appointment = ( $shipping_item_id ? new DSLPFW_Local_Pickup_WooCommerce_Appointment($shipping_item_id) : null );
        return $appointment;
    }

    /**
     * Determines whether the appointment defined by the given start date and end date
     * is available considering the pickup location's settings.
     *
     * TODO: consider splitting this method in two {WV 2020-04-23}
     *  - one that accepts $pickup_location, $appointment_duration, $start_date, $end_date for fixed (not anytime) appointment times
     *  - one that accepts $pickup_location, $order_created_date, $appointment_date for anytime appointment times
     *
     * @since 1.0.0
     *
     * @param \DateTime $calendar_day used to calculate the first available pickup time for anytime appointments
     * @param \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location $pickup_location the pickup location object
     * @param int|null $appointment_duration the duration of an appointment in this pickup location (unless "anytime" is used)
     * @param \DateTime $start_date the selected appointment start time
     * @param \DateTime $end_date the selected appointment end time
     * @return bool
     */
    public function is_appointment_time_available(
        $calendar_day,
        $pickup_location,
        $appointment_duration,
        $start_date,
        $end_date
    ) {
        $is_available_appointment_time = false;
        if ( dslpfw_shipping_method()->is_anytime_appointments_enabled() ) {
            $shipping_method = dslpfw_shipping_method();
            if ( $start_date >= $pickup_location->get_appointments()->get_first_available_pickup_time( $calendar_day ) ) {
                $is_available_appointment_time = true;
            }
        } else {
            $appointment_ranges = [];
            $current_start_date = null;
            $current_end_date = null;
            // group available appointment times into range of times where an appointment can be defined
            foreach ( $pickup_location->get_appointments()->get_available_times( $start_date ) as $appointment_start ) {
                try {
                    $appointment_end = ( clone $appointment_start )->add( new \DateInterval(sprintf( 'PT%dS', $appointment_duration )) );
                } catch ( \Exception $e ) {
                    continue;
                }
                // start a new range if one is not defined or the current appointment start time is greater than the current range's end time
                if ( null === $current_start_date || null === $current_end_date || $appointment_start > $current_end_date ) {
                    $appointment_ranges[$appointment_start->getTimestamp()] = $appointment_end->getTimestamp();
                    $current_start_date = $appointment_start;
                    $current_end_date = $appointment_end;
                    // expand the current range to include the current appointment end time
                } else {
                    $appointment_ranges[$current_start_date->getTimestamp()] = $appointment_end->getTimestamp();
                    $current_end_date = $appointment_end;
                }
            }
            // the appointment time is available if the start and end times are contained in one of the appointment ranges
            foreach ( $appointment_ranges as $range_start => $range_end ) {
                if ( $start_date->getTimestamp() >= $range_start && $end_date->getTimestamp() <= $range_end ) {
                    $is_available_appointment_time = true;
                    break;
                }
            }
        }
        return $is_available_appointment_time;
    }

}
