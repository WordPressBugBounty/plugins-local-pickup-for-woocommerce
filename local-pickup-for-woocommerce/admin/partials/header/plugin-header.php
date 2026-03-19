<?php

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
global $dslpfw_fs;
$plugin_slug = DSLPFW_PROMOTIONAL_SLUG;
$current_page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
$dslpfw_local_pickup_settings = ( isset( $current_page ) && 'dslpfw-local-pickup-settings' === $current_page ? 'active' : '' );
$dslpfw_rules_list = ( isset( $current_page ) && 'dslpfw-pickup-location-list' === $current_page ? 'active' : '' );
$dslpfw_settings_menu = ( isset( $current_page ) && 'dslpfw-import-export' === $current_page ? 'active' : '' );
$dslpfw_free_dashboard = ( isset( $current_page ) && $current_page === 'dslpfw-pro-dashboard' ? 'active' : '' );
$dslpfw_get_started = ( isset( $current_page ) && 'dslpfw-get-started' === $current_page ? 'active' : '' );
$dslpfw_import_export = ( isset( $current_page ) && 'dslpfw-import-export' === $current_page ? 'active' : '' );
$dslpfw_account_page = ( isset( $current_page ) && $dslpfw_fs->get_menu_slug() . '-account' === $current_page ? 'active' : '' );
$dslpfw_display_submenu = ( !empty( $dslpfw_settings_menu ) && 'active' === $dslpfw_settings_menu ? 'display:inline-block' : 'display:none' );
//enable this when you want to use promotional bar
$dslpfw_admin_object = new DSLPFW_Local_Pickup_Woocommerce_Admin('', '');
?>
<div class="wrap">
    <div id="dotsstoremain" class="dslpfw-section">
        <div class="all-pad">
            <?php 
//enable this when you want to use promotional bar
$dslpfw_admin_object->dslpfw_get_promotional_bar( $plugin_slug );
?>
            <hr class="wp-header-end" />
            <header class="dots-header">
                <div class="dots-plugin-details">
                    <div class="dots-header-left">
                        <div class="dots-logo-main">
                            <img src="<?php 
echo esc_url( dslpfw_fs()->get_local_icon_url() );
?>" alt="<?php 
esc_attr_e( 'Plugin LOGO', 'local-pickup-for-woocommerce' );
?>"/>
                        </div>
                        <div class="plugin-name">
                            <div class="title"><?php 
echo esc_html( DSLPFW_PLUGIN_NAME );
?></div>
                        </div>
                        <span class="version-label <?php 
echo esc_attr( $plugin_slug );
?>"><?php 
echo esc_html( DSLPFW_VERSION_LABEL );
?></span>
                        <span class="version-number"><?php 
echo esc_html( DSLPFW_PLUGIN_VERSION );
?></span>
                    </div>
                    <div class="dots-header-right">
                        <div class="button-dots">
                            <a target="_blank" href="<?php 
echo esc_url( 'http://www.thedotstore.com/support/' );
?>">
                                <?php 
esc_html_e( 'Support', 'local-pickup-for-woocommerce' );
?>
                            </a>
                        </div>
                        <div class="button-dots">
                            <a target="_blank" href="<?php 
echo esc_url( 'https://www.thedotstore.com/feature-requests/' );
?>">
                                <?php 
esc_html_e( 'Suggest', 'local-pickup-for-woocommerce' );
?>
                            </a>
                        </div>
                        <div class="button-dots <?php 
echo ( dslpfw_fs()->is__premium_only() && dslpfw_fs()->can_use_premium_code() ? '' : 'last-link-button' );
?>">
                            <a target="_blank" href="<?php 
echo esc_url( 'https://docs.thedotstore.com/article/769-introduction-of-local-pickup-for-woocommerce' );
?>">
                                <?php 
esc_html_e( 'Help', 'local-pickup-for-woocommerce' );
?>
                            </a>
                        </div>
                        <div class="button-dots">
                            <?php 
?>
                                <a class="dots-upgrade-btn" target="_blank" href="javascript:void(0);"><?php 
esc_html_e( 'Upgrade Now', 'local-pickup-for-woocommerce' );
?></a>
                                <?php 
?>
                        </div>
                    </div>
                </div>
                <div class="dots-bottom-menu-main">
                    <div class="dots-menu-main">
                        <nav>
                            <ul>
                                <li>
                                    <a class="dotstore_plugin <?php 
echo esc_attr( $dslpfw_local_pickup_settings );
?>" href="<?php 
echo esc_url( add_query_arg( array(
    'page' => 'dslpfw-local-pickup-settings',
), admin_url( 'admin.php' ) ) );
?>"><?php 
esc_html_e( 'Global Settings', 'local-pickup-for-woocommerce' );
?></a>
                                </li>
                                <li>
                                    <a class="dotstore_plugin <?php 
echo esc_attr( $dslpfw_rules_list );
?>" href="<?php 
echo esc_url( add_query_arg( array(
    'page' => 'dslpfw-pickup-location-list',
), admin_url( 'admin.php' ) ) );
?>"><?php 
esc_html_e( 'Pickup Locations', 'local-pickup-for-woocommerce' );
?></a>
                                </li>
                                <li>
                                    <a class="dotstore_plugin <?php 
echo esc_attr( $dslpfw_settings_menu );
?>" href="<?php 
echo esc_url( add_query_arg( array(
    'page' => 'dslpfw-import-export',
), admin_url( 'admin.php' ) ) );
?>"><?php 
esc_html_e( 'Settings', 'local-pickup-for-woocommerce' );
?></a>
                                </li>
                                <?php 
if ( dslpfw_fs()->is__premium_only() && dslpfw_fs()->can_use_premium_code() ) {
    ?>
                                    <li>
                                        <a class="dotstore_plugin <?php 
    echo esc_attr( $dslpfw_account_page );
    ?>" href="<?php 
    echo esc_url( $dslpfw_fs->get_account_url() );
    ?>"><?php 
    esc_html_e( 'License', 'local-pickup-for-woocommerce' );
    ?></a>
                                    </li>
                                    <?php 
} else {
    ?>
                                    <li>
                                        <a class="dotstore_plugin dots_get_premium <?php 
    echo esc_attr( $dslpfw_free_dashboard );
    ?>" href="<?php 
    echo esc_url( add_query_arg( array(
        'page' => 'dslpfw-pro-dashboard',
    ), admin_url( 'admin.php' ) ) );
    ?>"><?php 
    esc_html_e( 'Get Premium', 'local-pickup-for-woocommerce' );
    ?></a>
                                    </li>
                                    <?php 
}
?>
                            </ul>
                        </nav>
                    </div>
                    <div class="dots-getting-started">
                        <a href="<?php 
echo esc_url( add_query_arg( array(
    'page' => 'dslpfw-get-started',
), admin_url( 'admin.php' ) ) );
?>" class="<?php 
echo esc_attr( $dslpfw_get_started );
?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 4.75a7.25 7.25 0 100 14.5 7.25 7.25 0 000-14.5zM3.25 12a8.75 8.75 0 1117.5 0 8.75 8.75 0 01-17.5 0zM12 8.75a1.5 1.5 0 01.167 2.99c-.465.052-.917.44-.917 1.01V14h1.5v-.845A3 3 0 109 10.25h1.5a1.5 1.5 0 011.5-1.5zM11.25 15v1.5h1.5V15h-1.5z" fill="#a0a0a0"></path></svg></a>
                    </div>
                </div>
            </header>
            <!-- Upgrade to pro popup -->
            <?php 
if ( !(dslpfw_fs()->is__premium_only() && dslpfw_fs()->can_use_premium_code()) ) {
    require_once DSLPFW_PLUGIN_BASE_DIR . 'admin/partials/dots-upgrade-popup.php';
}
?>
            <div class="dots-settings-inner-main">
                <div class="dots-settings-left-side">
                    <div class="dotstore-submenu-items" style="<?php 
echo esc_attr( $dslpfw_display_submenu );
?>">
                    <ul>
                        <li><a class="<?php 
echo esc_attr( $dslpfw_import_export );
?>" href="<?php 
echo esc_url( add_query_arg( array(
    'page' => 'dslpfw-import-export',
), admin_url( 'admin.php' ) ) );
?>"><?php 
esc_html_e( 'Import / Export', 'local-pickup-for-woocommerce' );
?></a></li>
                        <li><a href="<?php 
echo esc_url( 'https://www.thedotstore.com/plugins/' );
?>" target="_blank"><?php 
esc_html_e( 'Shop Plugins', 'local-pickup-for-woocommerce' );
?></a></li>
                    </ul>
                </div>
                <!-- <hr class="wp-header-end" /> -->