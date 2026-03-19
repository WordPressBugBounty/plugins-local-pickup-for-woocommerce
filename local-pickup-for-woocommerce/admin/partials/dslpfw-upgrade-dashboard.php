<?php
/**
 * Handles free plugin user dashboard
 * 
 * @package Woocommerce_Conditional_Product_Fees_For_Checkout_Pro
 * @since   3.9.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once( plugin_dir_path( __FILE__ ) . 'header/plugin-header.php' );

// Get product details from Freemius via API
$annual_plugin_price = '';
$monthly_plugin_price = '';
$plugin_details = array(
    'product_id' => 61841,
);

$api_url = add_query_arg(wp_rand(), '', DSLPFW_STORE_URL . 'wp-json/dotstore-product-fs-data/v2/dotstore-product-fs-data');
$final_api_url = add_query_arg($plugin_details, $api_url);

if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
    $api_response = vip_safe_wp_remote_get( $final_api_url, 3, 1, 20 );
} else {
    $api_response = wp_remote_get( $final_api_url ); // phpcs:ignore
}

if ( ( !is_wp_error($api_response)) && (200 === wp_remote_retrieve_response_code( $api_response ) ) ) {
	$api_response_body = wp_remote_retrieve_body($api_response);
	$plugin_pricing = json_decode( $api_response_body, true );

	if ( isset( $plugin_pricing ) && ! empty( $plugin_pricing ) ) {
		$first_element = reset( $plugin_pricing );
        if ( ! empty( $first_element['price_data'] ) ) {
            $first_price = reset( $first_element['price_data'] )['annual_price'];
        } else {
            $first_price = "0";
        }

        if( "0" !== $first_price ){
        	$annual_plugin_price = $first_price;
        	$monthly_plugin_price = round( intval( $first_price  ) / 12 );
        }
	}
}

// Set plugin key features content
$plugin_key_features = array(
    array(
        'title' => esc_html__( 'Flexible Pickup Locations', 'local-pickup-for-woocommerce' ),
        'description' => esc_html__( 'Enable customers to select from various nearby pickup points, enhancing convenience and flexibility.', 'local-pickup-for-woocommerce' ),
        'popup_image' => esc_url( DSLPFW_PLUGIN_URL . 'admin/images/pro-features-img/feature-screenshot-1.png' ),
        'popup_content' => array(
        	esc_html__( 'Customers can conveniently select from various nearby pickup points, such as designated stores or lockers.', 'local-pickup-for-woocommerce' )
        ),
        'popup_examples' => array(
            esc_html__( 'They can choose their preferred location, whether it\'s a local grocery store or a nearby pickup locker.', 'local-pickup-for-woocommerce' ),
            esc_html__( 'This option allows customers to avoid shipping charges by opting for local pickup near a participating store.', 'local-pickup-for-woocommerce' ),
        )
    ),
    array(
        'title' => esc_html__( 'Public Holidays Selection', 'local-pickup-for-woocommerce' ),
        'description' => esc_html__( 'Choose standard public holidays to exclude from the pickup schedule, ensuring accurate availability.', 'local-pickup-for-woocommerce' ),
        'popup_image' => esc_url( DSLPFW_PLUGIN_URL . 'admin/images/pro-features-img/feature-screenshot-2.png' ),
        'popup_content' => array(
        	esc_html__( 'A bakery owner can prevent scheduling on public holidays to avoid inconvenience.', 'local-pickup-for-woocommerce' ),
        ),
        'popup_examples' => array(
            esc_html__( 'In the plugin settings, they can select standard holidays like New Year\'s Day, Christmas, and Thanksgiving.', 'local-pickup-for-woocommerce' ),
            esc_html__( 'This ensures the delivery schedule is adjusted automatically on these dates, preventing customers from booking deliveries.', 'local-pickup-for-woocommerce' )
        )
    ),
    array(
        'title' => esc_html__( 'Product Filter for Pickup Location', 'local-pickup-for-woocommerce' ),
        'description' => esc_html__( 'For better management, filter and assign specific products or categories to designated pickup locations.', 'local-pickup-for-woocommerce' ),
        'popup_image' => esc_url( DSLPFW_PLUGIN_URL . 'admin/images/pro-features-img/feature-screenshot-3.png' ),
        'popup_content' => array(
        	esc_html__( 'A grocery store can offer specific pickup locations based on customer purchase products.', 'local-pickup-for-woocommerce' ),
        ),
        'popup_examples' => array(
            esc_html__( 'In the plugin settings, the store owner configures pickup points for certain products or categories, like fresh produce or dairy.', 'local-pickup-for-woocommerce' ),
            esc_html__( 'Customers buying items from these categories are presented with relevant pickup locations, ensuring easy access to the products they need.', 'local-pickup-for-woocommerce' )
        )
    ),
    array(
        'title' => esc_html__( 'Fee Adjustment for Pickup Location', 'local-pickup-for-woocommerce' ),
        'description' => esc_html__( 'Modify fees based on chosen pickup locations, allowing precise cost control.', 'local-pickup-for-woocommerce' ),
        'popup_image' => esc_url( DSLPFW_PLUGIN_URL . 'admin/images/pro-features-img/feature-screenshot-4.png' ),
        'popup_content' => array(
        	esc_html__( 'A retailer can adjust pickup fees based on the selected store by the customer.', 'local-pickup-for-woocommerce' ),
        ),
        'popup_examples' => array(
            esc_html__( 'In the plugin settings, they can set a $0 pickup fee for Store A and a $3 fee for Store B.', 'local-pickup-for-woocommerce' ),
            esc_html__( 'This setup ensures customers choosing Store A incur no extra charges, while those opting for Store B are charged an additional $3, allowing the retailer to cover specific handling costs.', 'local-pickup-for-woocommerce' ),
        )
    ),
    array(
        'title' => esc_html__( 'Discounts Adjustment for Pickup Location', 'local-pickup-for-woocommerce' ),
        'description' => esc_html__( 'Adjust discounts according to the selected pickup location to offer tailored pricing.', 'local-pickup-for-woocommerce' ),
        'popup_image' => esc_url( DSLPFW_PLUGIN_URL . 'admin/images/pro-features-img/feature-screenshot-5.png' ),
        'popup_content' => array(
        	esc_html__( 'A retailer can offer pickup discounts based on the store the customer selects.', 'local-pickup-for-woocommerce' ),
        ),
        'popup_examples' => array(
            esc_html__( 'In the plugin settings, they configure a $0 discount for Store A and a $5 discount for Store B.', 'local-pickup-for-woocommerce' ),
            esc_html__( 'This ensures customers selecting Store A receive no discount, while those choosing Store B enjoy a $5 discount, providing an incentive to use specific locations.', 'local-pickup-for-woocommerce' )
        )
    ),
    array(
        'title' => esc_html__( 'Schedule Appointment for Pickup', 'local-pickup-for-woocommerce' ),
        'description' => esc_html__( 'Let customers book specific pickup times, enhancing service precision and customer satisfaction.', 'local-pickup-for-woocommerce' ),
        'popup_image' => esc_url( DSLPFW_PLUGIN_URL . 'admin/images/pro-features-img/feature-screenshot-6.png' ),
        'popup_content' => array(
        	esc_html__( 'Customers can schedule a specific pickup time during checkout by selecting the "Schedule Pickup" option and choosing a preferred date and time.', 'local-pickup-for-woocommerce' ),
        ),
        'popup_examples' => array(
            esc_html__( 'The system ensures the selected slot is available and schedules the order accordingly.', 'local-pickup-for-woocommerce' ),
            esc_html__( 'This guarantees that customer can pick up their order at their convenience without waiting. If no date and time are selected, the system prompts the customer to choose one.', 'local-pickup-for-woocommerce' )
        )
    ),
    array(
        'title' => esc_html__( 'Set Pickup Lead Time and Deadline', 'local-pickup-for-woocommerce' ),
        'description' => esc_html__( 'Define default lead times and pick-up deadlines, ensuring a smooth and timely process.', 'local-pickup-for-woocommerce' ),
        'popup_image' => esc_url( DSLPFW_PLUGIN_URL . 'admin/images/pro-features-img/feature-screenshot-7.png' ),
        'popup_content' => array(
        	esc_html__( 'Establishing lead time and deadlines helps manage order preparation and scheduling.', 'local-pickup-for-woocommerce' ),
        ),
        'popup_examples' => array(
            esc_html__( 'The lead time is the period between when an order is placed and when it\'s ready for pickup.', 'local-pickup-for-woocommerce' ),
            esc_html__( 'Setting a deadline defines the maximum number of days customers can schedule pickups in advance.', 'local-pickup-for-woocommerce' ),
            esc_html__( 'This ensures orders are prepared on time and prevents scheduling too far ahead, aiding in logistics and inventory management.', 'local-pickup-for-woocommerce' ),
        )
    ),
    array(
        'title' => esc_html__( 'Set Pickup Locations Ordering', 'local-pickup-for-woocommerce' ),
        'description' => esc_html__( 'Arrange pickup locations alphabetically in the dropdown menu for easy and quick selection.', 'local-pickup-for-woocommerce' ),
        'popup_image' => esc_url( DSLPFW_PLUGIN_URL . 'admin/images/pro-features-img/feature-screenshot-8.png' ),
        'popup_content' => array(
        	esc_html__( 'Customers can select a pickup location from an alphabetically ordered dropdown list during checkout.', 'local-pickup-for-woocommerce' ),
        ),
        'popup_examples' => array(
            esc_html__( 'This arrangement makes it easy for them to find and choose their preferred location, streamlining the checkout process and enhancing the user experience.', 'local-pickup-for-woocommerce' ),
            esc_html__( 'Displaying pickup locations in a logical order ensures customers can quickly locate the most convenient option.', 'local-pickup-for-woocommerce' ),
        )
    )
);
?>
	<div class="dotstore-upgrade-dashboard">
		<div class="premium-benefits-section">
			<h2><?php esc_html_e( 'Upgrade to Unlock Premium Features', 'local-pickup-for-woocommerce' ); ?></h2>
			<p><?php esc_html_e( 'Upgrade to the premium version to access advanced features and simplify local pickup operations!', 'local-pickup-for-woocommerce' ); ?></p>
		</div>
		<div class="premium-plugin-details">
			<div class="premium-key-fetures">
				<h3><?php esc_html_e( 'Discover Our Top Key Features', 'local-pickup-for-woocommerce' ); ?></h3>
				<ul>
					<?php 
					if ( isset( $plugin_key_features ) && ! empty( $plugin_key_features ) ) {
						foreach( $plugin_key_features as $key_feature ) {
							?>
							<li>
								<h4><?php echo esc_html( $key_feature['title'] ); ?><span class="premium-feature-popup"></span></h4>
								<p><?php echo esc_html( $key_feature['description'] ); ?></p>
								<div class="feature-explanation-popup-main">
									<div class="feature-explanation-popup-outer">
										<div class="feature-explanation-popup-inner">
											<div class="feature-explanation-popup">
												<span class="dashicons dashicons-no-alt popup-close-btn" title="<?php esc_attr_e('Close', 'local-pickup-for-woocommerce'); ?>"></span>
												<div class="popup-body-content">
													<div class="feature-content">
														<h4><?php echo esc_html( $key_feature['title'] ); ?></h4>
														<?php 
														if ( isset( $key_feature['popup_content'] ) && ! empty( $key_feature['popup_content'] ) ) {
															foreach( $key_feature['popup_content'] as $feature_content ) {
																?>
																<p><?php echo esc_html( $feature_content ); ?></p>
																<?php
															}
														}
														?>
														<ul>
															<?php 
															if ( isset( $key_feature['popup_examples'] ) && ! empty( $key_feature['popup_examples'] ) ) {
																foreach( $key_feature['popup_examples'] as $feature_example ) {
																	?>
																	<li><?php echo esc_html( $feature_example ); ?></li>
																	<?php
																}
															}
															?>
														</ul>
													</div>
													<div class="feature-image">
														<img src="<?php echo esc_url( $key_feature['popup_image'] ); ?>" alt="<?php echo esc_attr( $key_feature['title'] ); ?>">
													</div>
												</div>
											</div>		
										</div>
									</div>
								</div>
							</li>
							<?php
						}
					}
					?>
				</ul>
			</div>
			<div class="premium-plugin-buy">
				<div class="premium-buy-price-box">
					<div class="price-box-top">
						<div class="pricing-icon">
							<img src="<?php echo esc_url( DSLPFW_PLUGIN_URL . 'admin/images/premium-upgrade-img/pricing-1.svg' ); ?>" alt="<?php esc_attr_e( 'Personal Plan', 'local-pickup-for-woocommerce' ); ?>">
						</div>
						<h4><?php esc_html_e( 'Personal', 'local-pickup-for-woocommerce' ); ?></h4>
					</div>
					<div class="price-box-middle">
						<?php
						if ( ! empty( $annual_plugin_price ) ) {
							?>
							<div class="monthly-price-wrap"><?php echo esc_html( '$' . $monthly_plugin_price ); ?><span class="seprater">/</span><span><?php esc_html_e( 'month', 'local-pickup-for-woocommerce' ); ?></span></div>
							<div class="yearly-price-wrap"><?php echo sprintf( esc_html__( 'Pay $%s today. Renews in 12 months.', 'local-pickup-for-woocommerce' ), esc_html( $annual_plugin_price ) ); ?></div>
							<?php	
						}
						?>
						<span class="for-site"><?php esc_html_e( '1 site', 'local-pickup-for-woocommerce' ); ?></span>
						<p class="price-desc"><?php esc_html_e( 'Great for website owners with a single WooCommerce Store', 'local-pickup-for-woocommerce' ); ?></p>
					</div>
					<div class="price-box-bottom">
						<a href="javascript:void(0);" class="upgrade-now"><?php esc_html_e( 'Get The Premium Version', 'local-pickup-for-woocommerce' ); ?></a>
						<p class="trusted-by"><?php esc_html_e( 'Trusted by 100,000+ store owners and WP experts!', 'local-pickup-for-woocommerce' ); ?></p>
					</div>
				</div>
				<div class="premium-satisfaction-guarantee premium-satisfaction-guarantee-2">
					<div class="money-back-img">
						<img src="<?php echo esc_url(DSLPFW_PLUGIN_URL . 'admin/images/premium-upgrade-img/14-Days-Money-Back-Guarantee.png'); ?>" alt="<?php esc_attr_e('14-Day money-back guarantee', 'local-pickup-for-woocommerce'); ?>">
					</div>
					<div class="money-back-content">
						<h2><?php esc_html_e( '14-Day Satisfaction Guarantee', 'local-pickup-for-woocommerce' ); ?></h2>
						<p><?php esc_html_e( 'You are fully protected by our 100% Satisfaction Guarantee. If over the next 14 days you are unhappy with our plugin or have an issue that we are unable to resolve, we\'ll happily consider offering a 100% refund of your money.', 'local-pickup-for-woocommerce' ); ?></p>
					</div>
				</div>
				<div class="plugin-customer-review">
					<h3><?php esc_html_e( 'Exactly what I needed for local pickups!', 'local-pickup-for-woocommerce' ); ?></h3>
					<p>
						<?php echo wp_kses( __( 'We have over 20 pickup locations, and this <strong>plugin made it easy to set up multiple pickup locations</strong>. I highly <strong>recommend it for implementing local pickup</strong> options on your WooCommerce site.', 'local-pickup-for-woocommerce' ), array(
				                'strong' => array(),
				            ) ); 
			            ?>
		            </p>
					<div class="review-customer">
						<div class="customer-img">
							<img src="<?php echo esc_url(DSLPFW_PLUGIN_URL . 'admin/images/premium-upgrade-img/customer-profile-img.png'); ?>" alt="<?php esc_attr_e('Customer Profile Image', 'local-pickup-for-woocommerce'); ?>">
						</div>
						<div class="customer-name">
							<span><?php esc_html_e( 'Nick Ogle', 'local-pickup-for-woocommerce' ); ?></span>
							<div class="customer-rating-bottom">
								<div class="customer-ratings">
									<span class="dashicons dashicons-star-filled"></span>
									<span class="dashicons dashicons-star-filled"></span>
									<span class="dashicons dashicons-star-filled"></span>
									<span class="dashicons dashicons-star-filled"></span>
									<span class="dashicons dashicons-star-filled"></span>
								</div>
								<div class="verified-customer">
									<span class="dashicons dashicons-yes-alt"></span>
									<?php esc_html_e( 'Verified Customer', 'local-pickup-for-woocommerce' ); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="upgrade-to-pro-faqs">
			<h2><?php esc_html_e( 'FAQs', 'local-pickup-for-woocommerce' ); ?></h2>
			<div class="upgrade-faqs-main">
				<div class="upgrade-faqs-list">
					<div class="upgrade-faqs-header">
						<h3><?php esc_html_e( 'Do you offer support for the plugin? What’s it like?', 'local-pickup-for-woocommerce' ); ?></h3>
					</div>
					<div class="upgrade-faqs-body">
						<p>
						<?php 
							echo sprintf(
							    esc_html__('Yes! You can read our %s or submit a %s. We are very responsive and strive to do our best to help you.', 'local-pickup-for-woocommerce'),
							    '<a href="' . esc_url('https://docs.thedotstore.com/collection/765-local-pickup') . '" target="_blank">' . esc_html__('knowledge base', 'local-pickup-for-woocommerce') . '</a>',
							    '<a href="' . esc_url('https://www.thedotstore.com/support-ticket/') . '" target="_blank">' . esc_html__('support ticket', 'local-pickup-for-woocommerce') . '</a>',
							);

						?>
						</p>
					</div>
				</div>
				<div class="upgrade-faqs-list">
					<div class="upgrade-faqs-header">
						<h3><?php esc_html_e( 'What payment methods do you accept?', 'local-pickup-for-woocommerce' ); ?></h3>
					</div>
					<div class="upgrade-faqs-body">
						<p><?php esc_html_e( 'You can pay with your credit card using Stripe checkout. Or your PayPal account.', 'local-pickup-for-woocommerce' ); ?></p>
					</div>
				</div>
				<div class="upgrade-faqs-list">
					<div class="upgrade-faqs-header">
						<h3><?php esc_html_e( 'What’s your refund policy?', 'local-pickup-for-woocommerce' ); ?></h3>
					</div>
					<div class="upgrade-faqs-body">
						<p><?php esc_html_e( 'We have a 14-day money-back guarantee.', 'local-pickup-for-woocommerce' ); ?></p>
					</div>
				</div>
				<div class="upgrade-faqs-list">
					<div class="upgrade-faqs-header">
						<h3><?php esc_html_e( 'I have more questions…', 'local-pickup-for-woocommerce' ); ?></h3>
					</div>
					<div class="upgrade-faqs-body">
						<p>
						<?php 
							echo sprintf(
							    esc_html__('No problem, we’re happy to help! Please reach out at %s.', 'local-pickup-for-woocommerce'),
							    '<a href="' . esc_url('mailto:hello@thedotstore.com') . '" target="_blank">' . esc_html('hello@thedotstore.com') . '</a>',
							);

						?>
						</p>
					</div>
				</div>
			</div>
		</div>
		<div class="upgrade-to-premium-btn">
			<a href="javascript:void(0);" target="_blank" class="upgrade-now"><?php esc_html_e( 'Get The Premium Version', 'local-pickup-for-woocommerce' ); ?><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="crown" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" class="svg-inline--fa fa-crown fa-w-20 fa-3x" width="22" height="20"><path fill="#000" d="M528 448H112c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h416c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16zm64-320c-26.5 0-48 21.5-48 48 0 7.1 1.6 13.7 4.4 19.8L476 239.2c-15.4 9.2-35.3 4-44.2-11.6L350.3 85C361 76.2 368 63 368 48c0-26.5-21.5-48-48-48s-48 21.5-48 48c0 15 7 28.2 17.7 37l-81.5 142.6c-8.9 15.6-28.9 20.8-44.2 11.6l-72.3-43.4c2.7-6 4.4-12.7 4.4-19.8 0-26.5-21.5-48-48-48S0 149.5 0 176s21.5 48 48 48c2.6 0 5.2-.4 7.7-.8L128 416h384l72.3-192.8c2.5.4 5.1.8 7.7.8 26.5 0 48-21.5 48-48s-21.5-48-48-48z" class=""></path></svg></a>
		</div>
	</div>
</div>
</div>
</div>
</div>
<?php 
