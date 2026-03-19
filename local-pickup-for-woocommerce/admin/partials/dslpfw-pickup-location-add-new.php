<?php

/**
 * Page which can provide add new pickup location form
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.thedotstore.com/
 * @since      1.0.0
 *
 * @package    DSLPFW_Local_Pickup_Woocommerce
 * @subpackage DSLPFW_Local_Pickup_Woocommerce/admin/partials
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
require_once plugin_dir_path( __FILE__ ) . 'header/plugin-header.php';
$allowed_tooltip_html = wp_kses_allowed_html( 'post' )['span'];
$dslpfw_action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
$pickup_location_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
if ( !empty( $dslpfw_action ) && 'edit' === $dslpfw_action && !empty( $pickup_location_id ) ) {
    $pickup_location = new \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location($pickup_location_id);
    $btnValue = esc_html__( 'Update Location', 'local-pickup-for-woocommerce' );
    $dslpfw_pickup_location_title = $pickup_location->get_name();
    $dslpfw_pickup_location_status = $pickup_location->get_status();
    $dslpfw_pickup_location_note = html_entity_decode( $pickup_location->get_description() );
    $dslpfw_pickup_location_address_1 = $pickup_location->get_address()->get_address_1();
    $dslpfw_pickup_location_address_2 = $pickup_location->get_address()->get_address_2();
    $dslpfw_pickup_location_country = $pickup_location->get_address()->get_country();
    $dslpfw_pickup_location_state = $pickup_location->get_address()->get_state();
    $dslpfw_pickup_location_city = $pickup_location->get_address()->get_city();
    $dslpfw_pickup_location_postcode = $pickup_location->get_address()->get_postcode();
    $dslpfw_pickup_location_phone = $pickup_location->get_phone();
} else {
    $pickup_location = new \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location(0);
    $btnValue = esc_html__( 'Add Location', 'local-pickup-for-woocommerce' );
    $dslpfw_pickup_location_title = '';
    $dslpfw_pickup_location_status = 'draft';
    $dslpfw_pickup_location_note = '';
    $dslpfw_pickup_location_address_1 = '';
    $dslpfw_pickup_location_address_2 = '';
    $location = wc_get_base_location();
    $dslpfw_pickup_location_country = $location['country'];
    $dslpfw_pickup_location_state = $location['state'];
    $dslpfw_pickup_location_city = '';
    $dslpfw_pickup_location_postcode = '';
    $dslpfw_pickup_location_phone = '';
}
?>

<div class="dslpfw-section-left">
	<div class="dslpfw-main-table res-cl dslpfw-add-rule-page">
        <form method="POST" name="dslpfw_form" action="">
            <?php 
wp_nonce_field( 'dslpfw_rule_save_nonce', 'dslpfw_rule_save' );
?>
			<input type="hidden" name="dslpfw_post_type" value="<?php 
echo esc_attr( DSLPFW_POST_TYPE );
?>">
			<input type="hidden" name="dslpfw_post_id" value="<?php 
echo esc_attr( $pickup_location_id );
?>">

            <!-- Pickup Location tile & Address details -->
            <div class="dslpfw-rule-general-settings element-shadow">
                <h2><?php 
esc_html_e( 'Pickup Address', 'local-pickup-for-woocommerce' );
?></h2>
                <table class="form-table table-outer dslpfw-rule-table dslpfw-table-tooltip">
                    <tbody>
						<tr valign="top">
							<th class="titledesc" scope="row">
		                        <label for="dslpfw_pickup_location_status">
		                        	<?php 
esc_html_e( 'Status', 'local-pickup-for-woocommerce' );
?>
		                        	<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Enable or Disable this pickup address.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
	                        	</label>
							</th>
							<td class="forminp">
								<label class="switch">
									<input type="checkbox" id="dslpfw_pickup_location_status" name="dslpfw_pickup_location_status" value="on" <?php 
checked( $dslpfw_pickup_location_status, 'publish', true );
?> />
									<div class="slider round"></div>
								</label>
							</td>
						</tr>
                        <tr valign="top">
							<th class="titledesc" scope="row">
		                        <label for="dslpfw_pickup_location_title">
		                        	<?php 
esc_html_e( 'Pickup Title', 'local-pickup-for-woocommerce' );
?>
		                        	<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Title for pickup location for easily differentiate', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
	                        	</label>
							</th>
							<td class="forminp">
                                <input type="text" id="dslpfw_pickup_location_title" name="dslpfw_pickup_location_title" value="<?php 
echo esc_attr( $dslpfw_pickup_location_title );
?>" />
							</td>
						</tr>
                        <tr valign="top">
							<th class="titledesc" scope="row">
		                        <label for="dslpfw_pickup_location_address_1">
		                        	<?php 
esc_html_e( 'Address Line 1', 'local-pickup-for-woocommerce' );
?>
                                    <?php 
echo wp_kses( wc_help_tip( esc_html__( 'This address will shown as this pickup location\'s address', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
	                        	</label>
							</th>
							<td class="forminp">
                                <input type="text" id="dslpfw_pickup_location_address_1" name="dslpfw_pickup_location_address_1" value="<?php 
echo esc_attr( $dslpfw_pickup_location_address_1 );
?>" />
							</td>
						</tr>
                        <tr valign="top">
							<th class="titledesc" scope="row">
		                        <label for="dslpfw_pickup_location_address_2">
		                        	<?php 
esc_html_e( 'Address Line 2', 'local-pickup-for-woocommerce' );
?>
                                    <?php 
echo wp_kses( wc_help_tip( esc_html__( 'This address will shown as this pickup location\'s address', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
	                        	</label>
							</th>
							<td class="forminp">
                                <input type="text" id="dslpfw_pickup_location_address_2" name="dslpfw_pickup_location_address_2" value="<?php 
echo esc_attr( $dslpfw_pickup_location_address_2 );
?>" />
							</td>
						</tr>
                        <tr valign="top">
							<th class="titledesc" scope="row">
		                        <label for="dslpfw_pickup_location_city">
		                        	<?php 
esc_html_e( 'City', 'local-pickup-for-woocommerce' );
?>
		                        	<?php 
echo wp_kses( wc_help_tip( esc_html__( 'This city will shown as this pickup location\'s city', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
	                        	</label>
							</th>
							<td class="forminp">
                                <input type="text" id="dslpfw_pickup_location_city" name="dslpfw_pickup_location_city" value="<?php 
echo esc_attr( $dslpfw_pickup_location_city );
?>" />
							</td>
						</tr>
                        <tr valign="top">
							<th class="titledesc" scope="row">
		                        <label for="dslpfw_pickup_location_country_state">
		                        	<?php 
esc_html_e( 'Country & state', 'local-pickup-for-woocommerce' );
?>
		                        	<?php 
echo wp_kses( wc_help_tip( esc_html__( 'This state & country combination will shown as this pickup location\'s state & country', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
	                        	</label>
							</th>
							<td class="forminp">
                                <select id="dslpfw_pickup_location_country_state" name="dslpfw_pickup_location_country_state" class="wc-enhanced-select">
                                    <?php 
WC()->countries->country_dropdown_options( $dslpfw_pickup_location_country, ( empty( $dslpfw_pickup_location_state ) ? '*' : $dslpfw_pickup_location_state ) );
?>
                                </select>
							</td>
						</tr>
                        <tr valign="top">
							<th class="titledesc" scope="row">
		                        <label for="dslpfw_pickup_location_postcode">
		                        	<?php 
esc_html_e( 'Postcode', 'local-pickup-for-woocommerce' );
?>
		                        	<?php 
echo wp_kses( wc_help_tip( esc_html__( 'This postcode will shown as this pickup location\'s postcode', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
	                        	</label>
							</th>
							<td class="forminp">
                                <input type="text" id="dslpfw_pickup_location_postcode" name="dslpfw_pickup_location_postcode" value="<?php 
echo esc_attr( $dslpfw_pickup_location_postcode );
?>" />
							</td>
						</tr>
                        <tr valign="top">
							<th class="titledesc" scope="row">
		                        <label for="dslpfw_pickup_location_phone">
		                        	<?php 
esc_html_e( 'Phone', 'local-pickup-for-woocommerce' );
?>
		                        	<?php 
echo wp_kses( wc_help_tip( esc_html__( 'This phone number will shown as this pickup location\'s contact details', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
	                        	</label>
							</th>
							<td class="forminp">
                                <input type="text" id="dslpfw_pickup_location_phone" name="dslpfw_pickup_location_phone" value="<?php 
echo esc_attr( $dslpfw_pickup_location_phone );
?>" />
							</td>
						</tr>
                        <tr valign="top">
							<th class="titledesc" scope="row">
		                        <label for="dslpfw_pickup_location_note">
		                        	<?php 
esc_html_e( 'Note or Details', 'local-pickup-for-woocommerce' );
?>
		                        	<?php 
echo wp_kses( wc_help_tip( esc_html__( 'If we want to show any extra details about pickup location then we can use this note', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
	                        	</label>
							</th>
							<td class="forminp">
                                <?php 
wp_editor( $dslpfw_pickup_location_note, 'dslpfw_pickup_location_note', array(
    'media_buttons' => false,
    'teeny'         => true,
    'editor_height' => 168,
) );
?>
							</td>
						</tr>
                    </tbody>
                </table>
            </div>
            <div class="spacer-3"></div>
            
            <?php 
?>
                <!-- Pickup Appointment -->
                <div class="dslpfw-pickup-appointment-section dslpfw-upgrade-to-unlock">
                    <h2><?php 
esc_html_e( 'Pickup Appointments Settings', 'local-pickup-for-woocommerce' );
?><span class="dslpfw-pro-label"></span></h2>
                    <table class="form-table table-outer dslpfw-rule-table dslpfw-table-tooltip">
                        <tr>
                            <th scope="row">
                                <label>
                                    <?php 
echo esc_html__( 'Pickup Hours', 'local-pickup-for-woocommerce' );
?>
                                    <?php 
echo wp_kses( wc_help_tip( esc_html__( 'Without set pickup hours, scheduled appointments might disable location selection; The will be overridden default configuration.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
                                </label>
                            </th>
                            <td>
                                <div class="dslpfw-field-wrap">
                                    <label class="switch">
                                        <input type="checkbox" id="dslpfw_pickup_location_pickup_hours_status" name="dslpfw_pickup_location_pickup_hours_status" value="on" disabled />
                                        <div class="slider round"></div>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label>
                                    <?php 
echo esc_html__( 'Holiday Dates', 'local-pickup-for-woocommerce' );
?>
                                    <?php 
echo wp_kses( wc_help_tip( esc_html__( 'Exclude specific calendar days for pickup appointments. The will be overridden default configuration.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
                                </label>
                            </th>
                            <td>
                                <div class="dslpfw-field-wrap">
                                    <label class="switch">
                                        <input type="checkbox" id="dslpfw_pickup_location_holiday_dates_status" name="dslpfw_pickup_location_holiday_dates_status" value="on" disabled />
                                        <div class="slider round"></div>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label>
                                    <?php 
echo esc_html__( 'Lead Time', 'local-pickup-for-woocommerce' );
?>
                                    <?php 
echo wp_kses( wc_help_tip( esc_html__( 'Set a default lead time to determine the earliest pickup date. Set to zero for no lead time. The will be overridden default configuration.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
                                </label>
                            </th>
                            <td>
                                <div class="dslpfw-field-wrap">
                                    <label class="switch">
                                        <input type="checkbox" id="dslpfw_pickup_location_lead_time_status" name="dslpfw_pickup_location_lead_time_status" value="on" disabled />
                                        <div class="slider round"></div>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label>
                                    <?php 
echo esc_html__( 'Deadline', 'local-pickup-for-woocommerce' );
?>
                                    <?php 
echo wp_kses( wc_help_tip( esc_html__( 'Set a default deadline to determine the latest pickup date. The deadline is based on calendar days and does  consider pickup hours and holidays. Set to zero for no deadline. The will be overridden default configuration.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
                                </label>
                            </th>
                            <td>
                                <div class="dslpfw-field-wrap">
                                    <label class="switch">
                                        <input type="checkbox" id="dslpfw_pickup_location_deadline_status" name="dslpfw_pickup_location_deadline_status" value="on" disabled />
                                        <div class="slider round"></div>
                                    </label>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="spacer-3"></div>

                <!-- Fee Adjustment -->
                <div class="dslpfw-fee-adjustment-section dslpfw-upgrade-to-unlock">
                    <h2><?php 
esc_html_e( 'Fee Adjustment Settings', 'local-pickup-for-woocommerce' );
?><span class="dslpfw-pro-label"></span></h2>
                    <table class="form-table table-outer dslpfw-rule-table dslpfw-table-tooltip">
                        <tr>
                            <th scope="row">
                                <label>
                                    <?php 
echo esc_html__( 'Fee Adjustment', 'local-pickup-for-woocommerce' );
?>
                                    <?php 
echo wp_kses( wc_help_tip( esc_html__( 'By enabling a fee adjustment for this pickup location, any default value will be overridden when customers collect their purchases at this location.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
                                </label>
                            </th>
                            <td>
                                <div class="dslpfw-field-wrap">
                                    <label class="switch">
                                        <input type="checkbox" id="dslpfw_pickup_location_fee_adjustment_status" name="dslpfw_pickup_location_fee_adjustment_status" value="on" disabled />
                                        <div class="slider round"></div>
                                    </label>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="spacer-3"></div>
                
                <!-- Product Filter -->
                <div class="dslpfw-filter-section dslpfw-upgrade-to-unlock">
                    <h2><?php 
esc_html_e( 'Filter for Locations', 'local-pickup-for-woocommerce' );
?><span class="dslpfw-pro-label"></span></h2>
                    <?php 
$per_item_enabled = dslpfw_shipping_method()->dslpfw_is_per_item_selection_enabled();
?>
                    <table class="form-table table-outer dslpfw-rule-table dslpfw-table-tooltip">
                        <?php 
if ( $per_item_enabled ) {
    ?>
                            <tr>
                                <th scope="row">
                                    <label>
                                        <?php 
    echo esc_html__( 'Product', 'local-pickup-for-woocommerce' );
    ?>
                                        <?php 
    echo wp_kses( wc_help_tip( esc_html__( 'Either allow any product to be picked up at this location, or restrict this location to only allow pickup of certain products.', 'local-pickup-for-woocommerce' ) ), array(
        'span' => $allowed_tooltip_html,
    ) );
    ?>
                                    </label>
                                </th>
                                <td>
                                    <div class="dslpfw-field-wrap">
                                        <label class="switch">
                                            <input type="checkbox" id="dslpfw_pickup_location_products_status" name="dslpfw_pickup_location_products_status" value="on" disabled />
                                            <div class="slider round"></div>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label>
                                        <?php 
    echo esc_html__( 'Category', 'local-pickup-for-woocommerce' );
    ?>
                                        <?php 
    echo wp_kses( wc_help_tip( esc_html__( 'Products belonging to the chosen categories are available at this location.', 'local-pickup-for-woocommerce' ) ), array(
        'span' => $allowed_tooltip_html,
    ) );
    ?>
                                    </label>
                                </th>
                                <td>
                                    <div class="dslpfw-field-wrap">
                                        <label class="switch">
                                            <input type="checkbox" id="dslpfw_pickup_location_categories_status" name="dslpfw_pickup_location_categories_status" value="on" disabled />
                                            <div class="slider round"></div>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        <?php 
} else {
    ?>
                            <tr>
                                <td>
                                <?php 
    printf( 
        /* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
        esc_html__( 'In order to limit the products available at this location, you will need to allow customers to choose a location for each product in their cart. %1$sClick here%2$s to check setting.', 'local-pickup-for-woocommerce' ),
        '<a href="' . esc_url( add_query_arg( array(
            'page' => 'dslpfw-local-pickup-settings',
        ), admin_url( 'admin.php' ) ) ) . '" target="_blank">',
        '</a>'
     );
    ?>
                                </td>
                            </tr>
                        <?php 
}
?>
                    </table>
                </div>
                <?php 
?>
            
            <p class="submit">
                <input type="submit" name="submitRule" class="button button-primary" value="<?php 
echo esc_attr( $btnValue );
?>">
            </p>
        </form>
    </div>
</div>

<?php 
require_once plugin_dir_path( __FILE__ ) . 'header/plugin-footer.php';