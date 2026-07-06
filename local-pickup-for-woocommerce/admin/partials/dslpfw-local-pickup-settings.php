<?php

/**
 * Handles plugin rules listing
 * 
 * @since   1.0.0
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
require_once plugin_dir_path( __FILE__ ) . 'header/plugin-header.php';
$allowed_tooltip_html = wp_kses_allowed_html( 'post' )['span'];
$dslpfw_prefix = 'woocommerce_' . \DSLPFW_Local_Pickup_Woocommerce::DSLPFW_SHIPPING_METHOD_ID . '_';
$menu_page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS );
$dslpfw_submit = filter_input( INPUT_POST, 'submit', FILTER_SANITIZE_SPECIAL_CHARS );
global $wpdb;
if ( isset( $dslpfw_submit ) && isset( $menu_page ) && $menu_page === 'dslpfw-local-pickup-settings' ) {
    $dslpfw_save_settings_nonce = filter_input( INPUT_POST, 'dslpfw_save_global_settings_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
    if ( !empty( $dslpfw_save_settings_nonce ) || wp_verify_nonce( sanitize_text_field( $dslpfw_save_settings_nonce ), 'dslpfw_save_global_settings' ) ) {
        $fetch_data = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $pattern = "/dslpfw_/";
        $filtered_arr = [];
        foreach ( $fetch_data as $key => $value ) {
            if ( preg_match( $pattern, $key ) && !strpos( $key, '_nonce' ) ) {
                // for WC core value save
                $filtered_arr[str_replace( 'dslpfw_', '', $key )] = $value;
            }
        }
        update_option( "{$dslpfw_prefix}settings", $filtered_arr );
        $get_dslpfw_choose_locations = filter_input( INPUT_POST, 'dslpfw_choose_locations', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $dslpfw_choose_locations = ( isset( $get_dslpfw_choose_locations ) ? sanitize_text_field( $get_dslpfw_choose_locations ) : '' );
        $get_dslpfw_appointments_mode = filter_input( INPUT_POST, 'dslpfw_appointments_mode', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $dslpfw_appointments_mode = ( isset( $get_dslpfw_appointments_mode ) ? sanitize_text_field( $get_dslpfw_appointments_mode ) : '' );
        $get_dslpfw_appointment_duration = filter_input( INPUT_POST, 'dslpfw_appointment_duration', FILTER_SANITIZE_NUMBER_INT );
        $dslpfw_appointment_duration = ( isset( $get_dslpfw_appointment_duration ) ? sanitize_text_field( $get_dslpfw_appointment_duration ) : '' );
        update_option( "{$dslpfw_prefix}choose_locations", $dslpfw_choose_locations );
        update_option( "{$dslpfw_prefix}appointments_mode", $dslpfw_appointments_mode );
        update_option( "{$dslpfw_prefix}appointment_duration", $dslpfw_appointment_duration );
        // Time range data prepare start
        $filter = array(
            'dslpfw_default_pickup_hours' => array(
                'filter' => array(FILTER_SANITIZE_NUMBER_INT, FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                'flags'  => FILTER_REQUIRE_ARRAY,
            ),
        );
        $time_range_data = filter_input_array( INPUT_POST, $filter );
        $pickup_hours = new \DSLPFW_Local_Pickup_Location_Pickup_Hours();
        $final_time_data = $pickup_hours->get_field_value( 'dslpfw_default_pickup_hours', $time_range_data );
        update_option( "{$dslpfw_prefix}default_pickup_hours", $final_time_data );
        //Fee adjustment start
        $get_dslpfw_fee_adjustment = filter_input( INPUT_POST, 'dslpfw_fee_adjustment', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $dslpfw_fee_adjustment = ( isset( $get_dslpfw_fee_adjustment ) ? sanitize_text_field( $get_dslpfw_fee_adjustment ) : 'no' );
        $dslpfw_fee_adjustment = ( 'yes' === $dslpfw_fee_adjustment ? 'discount' : 'cost' );
        $get_dslpfw_fee_adjustment_amount = filter_input( INPUT_POST, 'dslpfw_fee_adjustment_amount', FILTER_SANITIZE_NUMBER_INT );
        $dslpfw_fee_adjustment_amount = ( isset( $get_dslpfw_fee_adjustment_amount ) && !empty( $get_dslpfw_fee_adjustment_amount ) ? intval( $get_dslpfw_fee_adjustment_amount ) : 0 );
        $get_dslpfw_fee_adjustment_type = filter_input( INPUT_POST, 'dslpfw_fee_adjustment_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $dslpfw_fee_adjustment_type = ( isset( $get_dslpfw_fee_adjustment_type ) ? sanitize_text_field( $get_dslpfw_fee_adjustment_type ) : 'no' );
        $dslpfw_fee_adjustment_type = ( 'yes' === $dslpfw_fee_adjustment_type ? 'percentage' : 'fixed' );
        $fee_adjustment_object = new \DSLPFW_Local_Pickup_Location_Fee_Adjustment();
        $fee_adjustment_object->set_value( $dslpfw_fee_adjustment, (float) $dslpfw_fee_adjustment_amount, $dslpfw_fee_adjustment_type );
        $fee_adjustment = $fee_adjustment_object->get_value();
        update_option( "{$dslpfw_prefix}default_fee_adjustment", $fee_adjustment );
        $get_dslpfw_apply_pickup_location_tax = filter_input( INPUT_POST, 'dslpfw_apply_pickup_location_tax', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $dslpfw_apply_pickup_location_tax = ( isset( $get_dslpfw_apply_pickup_location_tax ) ? sanitize_text_field( $get_dslpfw_apply_pickup_location_tax ) : 'no' );
        update_option( "{$dslpfw_prefix}apply_pickup_location_tax", $dslpfw_apply_pickup_location_tax );
        // phpcs:ignore
        //Fee adjustment end
    }
}
//Get updated data from shipping Settings APIs
$dslpfw_object = new DSLPFW_Local_Pickup_Woocommerce();
// We are making direct call to the class instead of dslpfw() to get the updated data on refresh everytime.
$shipping_obj = $dslpfw_object->dslpfw_get_shipping_method_instance();
$shipping_data = ( isset( $shipping_obj->settings ) && !empty( $shipping_obj->settings ) ? $shipping_obj->settings : array() );
$dslpfw_enabled = ( !empty( $shipping_data['enabled'] ) && isset( $shipping_data['enabled'] ) ? $shipping_data['enabled'] : 'no' );
$dslpfw_title = ( !empty( $shipping_data['title'] ) && isset( $shipping_data['title'] ) ? $shipping_data['title'] : esc_html__( 'DS Local Pickup', 'local-pickup-for-woocommerce' ) );
$dslpfw_display_country = ( !empty( $shipping_data['display_country'] ) && isset( $shipping_data['display_country'] ) ? $shipping_data['display_country'] : array() );
$dslpfw_choose_locations = get_option( "{$dslpfw_prefix}choose_locations", 'per-order' );
$dslpfw_appointments_mode = get_option( "{$dslpfw_prefix}appointments_mode", 'disabled' );
$dslpfw_appointment_duration = get_option( "{$dslpfw_prefix}appointment_duration", DAY_IN_SECONDS );
$dslpfw_default_pickup_hours = get_option( "{$dslpfw_prefix}default_pickup_hours", array() );
$pickup_hours_object = new \DSLPFW_Local_Pickup_Location_Pickup_Hours((array) $dslpfw_default_pickup_hours);
$dslpfw_fee_adjustment = get_option( "{$dslpfw_prefix}default_fee_adjustment", '' );
$fee_adjustment_object = new \DSLPFW_Local_Pickup_Location_Fee_Adjustment($dslpfw_fee_adjustment);
$dslpfw_apply_pickup_location_tax = get_option( "{$dslpfw_prefix}apply_pickup_location_tax", 'no' );
// phpcs:ignore
?>
<div class="dslpfw-section-left">
	<div class="dslpfw-main-table res-cl">
        <form method="post" action="#" enctype="multipart/form-data">
            <?php 
wp_nonce_field( 'dslpfw_save_global_settings', 'dslpfw_save_global_settings_nonce' );
?>

            <!-- Shipping Setting Start -->
            <div class="dslpfw-shipping-setting-section">
                <h2><?php 
esc_html_e( 'Local Pickup Settings', 'local-pickup-for-woocommerce' );
echo wp_kses( wc_help_tip( esc_html__( 'This section will allow customer to enable or disable shipping method with title changes.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?></h2>
                <table class="form-table table-outer dslpfw-rule-table dslpfw-table-tooltip" >
                    <tr>
                        <th scope="row">
                            <label>
                                <?php 
echo esc_html__( 'Enable Shipping', 'local-pickup-for-woocommerce' );
?>
                                <?php 
echo wp_kses( wc_help_tip( esc_html__( 'Enable or disable shipping method.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
                            </label>
                        </th>
                        <td>
                            <label class="switch">
                                <input type="hidden" name="dslpfw_enabled" value="no" />
                                <input type="checkbox" id="dslpfw_enabled" name="dslpfw_enabled" value="yes" <?php 
checked( $dslpfw_enabled, 'yes', true );
?> />
                                <div class="slider round"></div>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label>
                                <?php 
echo esc_html__( 'Shipping Title', 'local-pickup-for-woocommerce' );
?>
                                <?php 
echo wp_kses( wc_help_tip( esc_html__( 'The shipping method title that customers see during checkout.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
                            </label>
                        </th>
                        <td>
                            <input type="text" id="dslpfw_title" name="dslpfw_title" value="<?php 
echo esc_attr( $dslpfw_title );
?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label>
                                <?php 
echo esc_html__( 'Display Country', 'local-pickup-for-woocommerce' );
?>
                                <?php 
echo wp_kses( wc_help_tip( esc_html__( 'Local pickup will show on these selected countries, blank will show on all countries', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
                            </label>
                        </th>
                        <td>
                            <select class="multiselect wc-enhanced-select dslpfw-display-country" name="dslpfw_display_country[]" id="dslpfw_display_country" multiple="multiple" style="width: 620px;">
                                <?php 
$selected_countries = ( is_array( $dslpfw_display_country ) ? $dslpfw_display_country : (( $dslpfw_display_country === 'all' ? array() : (array) $dslpfw_display_country )) );
$all_countries = WC()->countries->get_countries();
foreach ( $all_countries as $country_code => $country_name ) {
    ?>
                                    <option value="<?php 
    echo esc_attr( $country_code );
    ?>" <?php 
    selected( in_array( $country_code, $selected_countries, true ), true );
    ?>>
                                        <?php 
    echo esc_html( $country_name );
    ?>
                                    </option>
                                <?php 
}
?>
                            </select>
                            <p class="description"><?php 
esc_html_e( 'Hold down the Ctrl (Windows) or Command (Mac) button to select multiple countries.', 'local-pickup-for-woocommerce' );
?></p>
                        </td>
                    </tr>
                </table>
            </div>
            <!-- Shipping Setting End -->

            <div class="spacer-3"></div>

            <!-- Checkout Display Start -->
            <div class="dslpfw-cart-checkout-setting-section">
                <h2><?php 
esc_html_e( 'Cart & Checkout Settings', 'local-pickup-for-woocommerce' );
echo wp_kses( wc_help_tip( esc_html__( 'Determine how pickup locations are shown to the customer at checkout.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?></h2>
                <table class="form-table table-outer dslpfw-rule-table dslpfw-table-tooltip">
                <?php 
?>
                        <tr>
                            <th scope="row">
                                <label>
                                    <?php 
echo esc_html__( 'Shipping Zones', 'local-pickup-for-woocommerce' );
?>
                                    <span class="dslpfw-pro-label"></span>
                                    <?php 
echo wp_kses( wc_help_tip( esc_html__( 'Select the shipping zones where the local pickup will be available.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
                                </label>
                            </th>
                            <td>
                                <?php 
// Fetch all shipping zones, including "Rest of the World" (ID 0)
$zones = WC_Shipping_Zones::get_zones();
$rest_of_world_zone = new WC_Shipping_Zone(0);
?>
                                <select class="dslpfw-shipping-zones wc-enhanced-select" disabled multiple="multiple" data-placeholder="<?php 
esc_attr_e( 'Select Shipping Zones', 'local-pickup-for-woocommerce' );
?>" style="width: 620px;">
                                    <?php 
foreach ( $zones as $zone_data ) {
    ?>
                                        <option><?php 
    echo esc_html( $zone_data['zone_name'] );
    ?></option>
                                    <?php 
}
?>
                                    <option><?php 
echo esc_html( $rest_of_world_zone->get_zone_name() );
?></option>
                                </select>
                            </td>
                        </tr><?php 
?>
                    <tr>
                        <th scope="row">
                            <label>
                                <?php 
echo esc_html__( 'Choosing Locations', 'local-pickup-for-woocommerce' );
?>
                                <?php 
echo wp_kses( wc_help_tip( esc_html__( 'Where to give option for pickup location.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
                            </label>
                        </th>
                        <td>
                            <select class="dslpfw-choose-locations" name="dslpfw_choose_locations">
                                <option value="per-order" <?php 
selected( $dslpfw_choose_locations, 'per-order', true );
?>><?php 
esc_html_e( 'Allow customers to choose location per order', 'local-pickup-for-woocommerce' );
?></option>          
                                <option value="per-item" <?php 
selected( $dslpfw_choose_locations, 'per-item', true );
?>><?php 
esc_html_e( 'Allow customers to choose location per product', 'local-pickup-for-woocommerce' );
?></option>          
                            </select>
                        </td>
                    </tr>
                    <?php 
?>
                        <tr class="show-per-order">
                            <th scope="row">
                                <label>
                                    <?php 
echo esc_html__( 'Cart Item Handling', 'local-pickup-for-woocommerce' );
?>
                                    <span class="dslpfw-pro-label"></span>
                                    <?php 
echo wp_kses( wc_help_tip( esc_html__( 'Apply pickup location for whole package or diffrent package based of product wise selection', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
                                </label>
                            </th>
                            <td>
                                <select class="dslpfw-cart-item-handling" name="dslpfw_cart_item_handling" disabled>
                                    <option value="automatic"><?php 
esc_html_e( 'Automatically group cart items into as few packages as possible', 'local-pickup-for-woocommerce' );
?></option>          
                                    <option value="customer"><?php 
esc_html_e( 'Allow customers to toggle pickup or shipping for each item in the cart', 'local-pickup-for-woocommerce' );
?></option>          
                                </select>
                            </td>
                        </tr>
                        <tr class="show-per-order">
                            <th scope="row">
                                <label>
                                    <?php 
echo esc_html__( 'Default Handling', 'local-pickup-for-woocommerce' );
?>
                                    <span class="dslpfw-pro-label"></span>
                                    <?php 
echo wp_kses( wc_help_tip( esc_html__( 'Choose whether cart items will be set as to be shipped or for pickup when customers first arrive at the cart or checkout page.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
                                </label>
                            </th>
                            <td>
                                <select class="dslpfw-default-handling" name="dslpfw_default_handling" disabled>
                                    <option value="pickup"><?php 
esc_html_e( 'Pick up items', 'local-pickup-for-woocommerce' );
?></option>          
                                    <option value="ship"><?php 
esc_html_e( 'Ship items', 'local-pickup-for-woocommerce' );
?></option>          
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label>
                                    <?php 
echo esc_html__( 'Location Sort Order', 'local-pickup-for-woocommerce' );
?>
                                    <span class="dslpfw-pro-label"></span>
                                    <?php 
echo wp_kses( wc_help_tip( esc_html__( 'Choose how the pickup location will be listed to the customer at checkout. Default is the default sort order determined by WordPress.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
                                </label>
                            </th>
                            <td>
                                <select class="dslpfw-locations-order" name="dslpfw_locations_order" disabled>
                                    <option value="default"><?php 
esc_html_e( 'Default', 'local-pickup-for-woocommerce' );
?></option>          
                                    <option value="alphabetical"><?php 
esc_html_e( 'Alphabetical by location name', 'local-pickup-for-woocommerce' );
?></option>          
                                    <option value="date_added"><?php 
esc_html_e( 'Most recently added location', 'local-pickup-for-woocommerce' );
?></option>          
                                    <option value="nearby_address"><?php 
esc_html_e( 'Nearest to customer address', 'local-pickup-for-woocommerce' );
?></option>          
                                </select>
                            </td>
                        </tr>
                        <?php 
?>
                </table>
            </div>
            <!-- Checkout Display End -->

            <div class="spacer-3"></div>

            <!-- Pickup Appointment Start -->
            <div class="dslpfw-pickup-appointment-section">
                <h2><?php 
esc_html_e( 'Pickup Appointments Settings', 'local-pickup-for-woocommerce' );
echo wp_kses( wc_help_tip( esc_html__( 'This section will allow the customer to schedule an appointment for pickup at a selected pickup location on checkout.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?></h2>
                <table class="form-table table-outer dslpfw-rule-table dslpfw-table-tooltip" >
                    <tr>
                        <th scope="row">
                            <label>
                                <?php 
echo esc_html__( 'Mode', 'local-pickup-for-woocommerce' );
?>
                                <?php 
echo wp_kses( wc_help_tip( esc_html__( 'Choose whether date and time selection is required for customer interaction.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
                            </label>
                        </th>
                        <td>
                            <select class="dslpfw-pickup-mode" name="dslpfw_appointments_mode">
                                <option value="disabled" <?php 
selected( $dslpfw_appointments_mode, 'disabled', true );
?>><?php 
esc_html_e( 'Do not offer appointments', 'local-pickup-for-woocommerce' );
?></option>
                                <option value="enabled" <?php 
selected( $dslpfw_appointments_mode, 'enabled', true );
?>><?php 
esc_html_e( 'Allow scheduled appointments', 'local-pickup-for-woocommerce' );
?></option>
                                <?php 
?>
                                    <option value="required_disabled"><?php 
esc_html_e( 'Require scheduled appointments 🔒', 'local-pickup-for-woocommerce' );
?></option>
                                    <?php 
?>
                            </select>
                        </td>
                    </tr>
                    <tr class="show-appointment-fields">
                        <th scope="row">
                            <label>
                                <?php 
echo esc_html__( 'Time range', 'local-pickup-for-woocommerce' );
?>
                                <?php 
echo wp_kses( wc_help_tip( esc_html__( 'Customers select preferred time slots within specified intervals for scheduling convenience.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
                            </label>
                        </th>
                        <td>
                            <select class="dslpfw-appointment-duration-field" name="dslpfw_appointment_duration">
                                <option value="86400" <?php 
selected( $dslpfw_appointment_duration, '86400', true );
?>>Anytime during open hours</option>
                                <option value="300" <?php 
selected( $dslpfw_appointment_duration, '300', true );
?>>5 minutes</option>
                                <option value="600" <?php 
selected( $dslpfw_appointment_duration, '600', true );
?>>10 minutes</option>
                                <option value="900" <?php 
selected( $dslpfw_appointment_duration, '900', true );
?>>15 minutes</option>
                                <option value="1800" <?php 
selected( $dslpfw_appointment_duration, '1800', true );
?>>30 minutes</option>
                                <option value="3600" <?php 
selected( $dslpfw_appointment_duration, '3600', true );
?>>60 minutes</option>
                            </select>
                        </td>
                    </tr>
                    <tr class="show-appointment-fields">
                        <th scope="row">
                            <label>
                                <?php 
echo esc_html__( 'Default Pickup Hours', 'local-pickup-for-woocommerce' );
?>
                                <?php 
echo wp_kses( wc_help_tip( esc_html__( 'Without set pickup hours, scheduled appointments might disable location selection; default schedules can be customized for each pickup point.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
                            </label>
                        </th>
                        <td>
                            <?php 
echo wp_kses( $pickup_hours_object->get_field_html( [
    'name' => 'dslpfw_default_pickup_hours',
] ), dslpfw()->dslpfw_allowed_html_tags() );
?>
                        </td>
                    </tr>
                    <?php 
?>
                </table>
            </div>
            <!-- Pickup Appointment End -->

            <div class="spacer-3"></div>

            <!-- Fee Adjustment -->
            <div class="dslpfw-fee-adjustment-section">
                <h2><?php 
esc_html_e( 'Fee Adjustment Settings', 'local-pickup-for-woocommerce' );
echo wp_kses( wc_help_tip( esc_html__( 'This section will configure for when a customer opts for order pickup, set a default cost or discount, and ensure appropriate taxation is applied.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?></h2>
                <table class="form-table table-outer dslpfw-rule-table dslpfw-table-tooltip">
                    <tr>
                        <th scope="row">
                            <label>
                                <?php 
echo esc_html__( 'Fee Adjustment', 'local-pickup-for-woocommerce' );
?>
                                <?php 
echo wp_kses( wc_help_tip( esc_html__( 'Zero default adjustment means no preset changes. Adjustments can be made per pickup location if needed.', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
                            </label>
                        </th>
                        <td>
                            <?php 
echo wp_kses( $fee_adjustment_object->get_field_html( [
    'name' => 'dslpfw_fee_adjustment',
] ), dslpfw()->dslpfw_allowed_html_tags() );
?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label>
                                <?php 
echo esc_html__( 'Pickup Location Tax', 'local-pickup-for-woocommerce' );
?>
                                <?php 
echo wp_kses( wc_help_tip( esc_html__( 'Apply the tax rate based on the pickup location than for the customer\'s given address', 'local-pickup-for-woocommerce' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
                            </label>
                        </th>
                        <td>
                            <label class="switch">
                                <input type="hidden" name="dslpfw_apply_pickup_location_tax" value="no" />
                                <input type="checkbox" id="dslpfw_apply_pickup_location_tax" name="dslpfw_apply_pickup_location_tax" value="yes" <?php 
checked( $dslpfw_apply_pickup_location_tax, 'yes', true );
?> />
                                <div class="slider round"></div>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="wcpoa-setting-btn wcpoa-general-submit">
                <?php 
submit_button();
?>
            </div>
        </from>
    </div>
</div>
<?php 
require_once plugin_dir_path( __FILE__ ) . 'header/plugin-footer.php';