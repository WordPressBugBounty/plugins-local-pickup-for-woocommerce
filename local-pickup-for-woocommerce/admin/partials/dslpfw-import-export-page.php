<?php
/**
 * Handles plugin rules listing
 * 
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once( plugin_dir_path( __FILE__ ) . 'header/plugin-header.php' );

$allowed_tooltip_html = wp_kses_allowed_html( 'post' )['span'];
?>
<div class="dslpfw-section-left">
	<div class="dslpfw-main-table res-cl dslpfw-import-export-section">
		<h2><?php esc_html_e( 'Import / Export Pickup Locations', 'local-pickup-for-woocommerce' ); ?></h2>
        <table class="form-table table-outer dslpfw-rule-table dslpfw-table-tooltip">
            <?php if( current_user_can( 'manage_woocommerce' ) ) { ?>
                <tr>
                    <th scope="row">
                        <label>
                            <?php echo esc_html__( 'Export Pickup Locations', 'local-pickup-for-woocommerce' ); ?>
                            <?php echo wp_kses( wc_help_tip( esc_html__( 'Export the pickup locations and it\'s settings from this site as a .json/.csv file. This allows you to easily import the configuration into another site.', 'local-pickup-for-woocommerce' ) ), array( 'span' => $allowed_tooltip_html ) ); ?>
                        </label>
                    </th>
                    <td>
                        <form method="post">
                            <div class="dslpfw_main_container">
                                <div class="dslpfw_toggle_container export_settings_container">
                                    <span class="dslpfw-type dslpfw-json-type"><?php echo esc_html__( 'JSON', 'local-pickup-for-woocommerce' ); ?></span>
                                    <label class="switch">
                                        <input type="checkbox" class="dslpfw-ie-type" id="dslpfw_export_type" name="dslpfw_export_type" value="on" />
                                        <div class="slider round"></div>
                                    </label>
                                    <span class="dslpfw-type dslpfw-csv-type"><?php echo esc_html__( 'CSV', 'local-pickup-for-woocommerce' ); ?></span>
                                </div>
                                <p class="dslpfw_button_container export_settings_container">
                                    <input type="button" name="dslpfw_export_settings" id="dslpfw_export_settings" class="button button-primary" value="<?php esc_attr_e( 'Export', 'local-pickup-for-woocommerce' ); ?>" />
                                </p>
                                <p class="dslpfw_content_container export_settings_container">
                                    <?php wp_nonce_field( 'dslpfw-export-action-nonce', 'dslpfw_export_action_nonce' ); ?>
                                    <input type="hidden" name="dslpfw_export_action" value="<?php echo esc_attr('dslpfw_export_pickup_locations_action'); ?>" />
                                </p>
                            </div>
                        </form>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label>
                            <?php echo esc_html__( 'Import Pickup Locations', 'local-pickup-for-woocommerce' ); ?>
                            <?php echo wp_kses( wc_help_tip( esc_html__( 'Import the pickup locations and it\'s settings from a .json/.csv file. This file can be obtained by exporting the settings on another site using the form above.', 'local-pickup-for-woocommerce' ) ), array( 'span' => $allowed_tooltip_html ) ); ?>
                        </label>
                    </th>
                    <td>
                        <form method="post" enctype="multipart/form-data">
                            <div class="dslpfw_main_container">
                                <div class="dslpfw_toggle_container import_settings_container">
                                    <span class="dslpfw-type dslpfw-json-type"><?php echo esc_html__( 'JSON', 'local-pickup-for-woocommerce' ); ?></span>
                                    <label class="switch">
                                        <input type="checkbox" class="dslpfw-ie-type" id="dslpfw_import_type" name="dslpfw_import_type" value="on" />
                                        <div class="slider round"></div>
                                    </label>
                                    <span class="dslpfw-type dslpfw-csv-type"><?php echo esc_html__( 'CSV', 'local-pickup-for-woocommerce' ); ?></span>
                                </div>
                                <div class="dslpfw-import-file">
                                    <input type="file" name="import_file" id="import_file" data-placeholder="<?php echo esc_attr__( 'No file selected', 'local-pickup-for-woocommerce' ); ?>"/>
                                    <span class='button'><?php echo esc_html__( 'Choose', 'local-pickup-for-woocommerce' ); ?></span>
                                    <span class='label' data-js-label></label>
                                </div>
                                <p class="dslpfw_button_container import_settings_container">
                                    <input type="button" name="dslpfw_import_setting" id="dslpfw_import_setting" class="button button-primary" value="<?php esc_attr_e( 'Import', 'local-pickup-for-woocommerce' ); ?>" />
                                </p>
                                <p class="dslpfw_content_container import_settings_container">
                                    <?php wp_nonce_field( 'dslpfw-import-action-nonce', 'dslpfw_import_action_nonce' ); ?>
                                    <input type="hidden" name="dslpfw_import_action" value="<?php echo esc_attr('dslpfw_import_pickup_locations_action'); ?>" />
                                </p>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php } else { ?>
                <tr>
                    <th><?php esc_html_e( 'Sorry! You are not allowed to perform this action.', 'local-pickup-for-woocommerce' ); ?></th>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>
<?php
require_once( plugin_dir_path( __FILE__ ) . 'header/plugin-footer.php' );