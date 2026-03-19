<?php
/**
 * Order details with pickup location data for Local Pickup for WooCommerce.
 *
 * @link       https://www.thedotstore.com/
 * @since      1.0.0
 *
 */

defined( 'ABSPATH' ) or exit;

/**
 * Local Pickup for WooCommerce order pickup details template file.
 *
 * @type \WC_Order $order Order being displayed
 * @type array $pickup_data Pickup data for given order
 * @type \DSLPFW_Local_Pickup_WooCommerce_Shipping $shipping_method Local Pickup Shipping Method instance
 *
 * @version 1.0.0
 * @since 1.0.0
 */

$shipping_method = $shipping_method; // we have done this to avoid the phpcs warning of undefined variable as this is a template file and variables are passed from the email class.
$pickup_data = $pickup_data; // we have done this to avoid the phpcs warning of undefined variable as this is a template file and variables are passed from the email class.
?>
<tr class="ds-local-pickup">
	<th><?php echo esc_html( $shipping_method->get_method_title() ); ?>:</th>
	<td>
		<?php $package_number = 1; ?>
		<?php $packages_count = count( $pickup_data ); ?>
		<?php foreach ( $pickup_data as $pickup_meta ) : ?>

			<div>
				<?php if ( $packages_count > 1 ) : ?>
                    <h5>
                        <?php 
                        if ( is_rtl() ) {
                            printf( '#%2$s %1$s', esc_html( $shipping_method->get_method_title() ), intval( $package_number ) );
                        } else {
                            printf( '%1$s #%2$s', esc_html( $shipping_method->get_method_title() ), intval( $package_number ) );
                        } ?>
                    </h5>
				<?php endif; ?>

				<?php foreach ( $pickup_meta as $label => $value ) : ?>
					<?php if ( is_rtl() ) : ?>
						<small><?php echo wp_kses_post( $value ); ?> <strong>:<?php echo esc_html( $label ); ?></strong></small><br />
					<?php else : ?>
						<small><strong><?php echo esc_html( $label ); ?>:</strong> <?php echo wp_kses_post( $value ); ?></small><br />
					<?php endif; ?>
				<?php endforeach; ?>

				<?php if ( $packages_count > 1 && $package_number <=$packages_count ) : ?>
					<br />
				<?php endif; ?>

				<?php $package_number++; ?>
			</div>

		<?php endforeach; ?>
	</td>
</tr>
