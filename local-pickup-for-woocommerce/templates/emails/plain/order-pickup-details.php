<?php
/**
 * Local Pickup for WooCommerce order pickup details template for email (Plain format).
 *
 * @link       https://www.thedotstore.com/
 * @since      1.0.0
 *
 */

defined( 'ABSPATH' ) or exit;

/**
 * Local Pickup for WooCommerce plain text emails order pickup details template file.
 *
 * @type \WC_Order $order Order being displayed
 * @type array $pickup_data Pickup data for given order
 * @type \DSLPFW_Local_Pickup_WooCommerce_Shipping $shipping_method Local Pickup WooCommerce Shipping Method instance
 * @type bool $sent_to_admin Whether the email is being sent to an admin
 *
 * @version 1.0.0
 * @since 1.0.0
 */

$packages_count = count( $pickup_data ); //phpcs:ignore
$package_number = 1;
$shipping_method = $shipping_method;
echo "\n\n";
echo $packages_count === 1 ? wp_strip_all_tags( $shipping_method->get_method_title() ) . "\n\n" : ''; //phpcs:ignore

foreach ( $pickup_data as $pickup_meta ) { //phpcs:ignore

	if ( $packages_count > 1 ) {
		echo is_rtl() ? sprintf(  '#%2$s %1$s' . "\n\n", esc_html( wp_strip_all_tags( $shipping_method->get_method_title() ) ), intval( $package_number ) ) : sprintf(  '%1$s #%2$s' . "\n\n", esc_html( wp_strip_all_tags( $shipping_method->get_method_title() ) ), intval( $package_number ) );
	}

	foreach ( $pickup_meta as $label => $value ) {
		$value = str_replace( '&times;', 'x', $value );
		echo esc_html( wp_strip_all_tags( is_rtl() ? $value . ' :' . $label . ' -' : '- ' . $label . ': ' .  $value ) . "\n" );
	}

	$package_number++;

	if ( $packages_count > 1 && $package_number <= $packages_count ) {
		echo "\n\n";
	}
}
