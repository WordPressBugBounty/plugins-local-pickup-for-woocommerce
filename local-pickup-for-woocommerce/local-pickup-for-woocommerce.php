<?php

/**
 * Plugin Name:     Local Pickup for WooCommerce
 * Plugin URI:      https://www.thedotstore.com/local-pickup-for-woocommerce/
 * Description:     Our plugin provides store owners to set up local pickup spots, and customers can easily choose their preferred pickup location when buying.
 * Version:         1.1.2
 * Author:          theDotstore
 * Author URI:      https://www.thedotstore.com/
 * License:         GPL-2.0+
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:     local-pickup-for-woocommerce
 * Domain Path:     /languages
 * 
 * 
 * @package           DSLPFW_Local_Pickup_Woocommerce
 * @link              https://www.thedotstore.com/
 * @author            theDotstore
 * @copyright         Copyright (c) 2012-2025, thDotstore.
 *
 * WC requires at least:    3.9.4
 * WP tested up to:         6.9
 * WC tested up to:         10.6.1
 * Requires PHP:            7.4
 * Requires at least:       5.0
 * Requires Plugins:        woocommerce
 */
// If this file is called directly, abort.
defined('ABSPATH') or exit;
if (function_exists('dslpfw_fs')) {
    dslpfw_fs()->set_basename(false, __FILE__);
}
else {
    // DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
    if (!function_exists('dslpfw_fs')) {
        // Create a helper function for easy SDK access.
        function dslpfw_fs()
        {
            global $dslpfw_fs;
            if (!isset($dslpfw_fs)) {
                // Activate multisite network integration.
                if (!defined('WP_FS__PRODUCT_14688_MULTISITE')) {
                    define('WP_FS__PRODUCT_14688_MULTISITE', true);
                }
                // Include Freemius SDK.
                require_once dirname(__FILE__) . '/freemius/start.php';
                // @phpstan-ignore-next-line
                $dslpfw_fs = fs_dynamic_init(array(
                    'id' => '14688',
                    'slug' => 'local-pickup-for-woocommerce',
                    'type' => 'plugin',
                    'public_key' => 'pk_888b9a587934ff650e53fb1ff57de',
                    'is_premium' => false,
                    'has_addons' => false,
                    'has_paid_plans' => true,
                    'trial' => array(
                        'days' => 14,
                        'is_require_payment' => true,
                    ),
                    'menu' => array(
                        'slug' => 'dslpfw-local-pickup-settings',
                        'first-path' => 'admin.php?page=dslpfw-local-pickup-settings',
                        'support' => false,
                        'network' => true,
                    ),
                    'is_live' => true,
                    'is_org_compliant' => true,
                ));
            }
            return $dslpfw_fs;
        }

        // Init Freemius.
        dslpfw_fs();
        // Signal that SDK was initiated.
        do_action('dslpfw_fs_loaded');
    }
}
/**
 * Currently plugin version.
 */
if (!defined('DSLPFW_PLUGIN_VERSION')) {
    define('DSLPFW_PLUGIN_VERSION', 'v1.1.2');
}
/**
 * Define the plugin's name if not already defined.
 */
if (!defined('DSLPFW_PLUGIN_NAME')) {
    define('DSLPFW_PLUGIN_NAME', 'Local Pickup');
}
/**
 * This code snippet for plugin LOGO URL
 */
if (!defined('DSLPFW_PLUGIN_LOGO_URL')) {
    define('DSLPFW_PLUGIN_LOGO_URL', dirname(__FILE__) . '/admin/images/local-pickup-for-woocommerce.png');
}
/** 
 * Plugin version type lable 
 */
if (!defined('DSLPFW_VERSION_LABEL')) {
    define('DSLPFW_VERSION_LABEL', 'FREE');
}
/**
 * Retrieve the basename of the main plugin file. 
 * This ensures that the constant always holds the accurate basename, even if the plugin file is renamed or moved.
 */
if (!defined('DSLPFW_PLUGIN_BASENAME')) {
    define('DSLPFW_PLUGIN_BASENAME', plugin_basename(__FILE__));
}
/**
 * Define the slug for the promotional feature if not already defined.
 * This code snippet establishes a standardized slug for the promotional bar feature used within the plugin.
 */
if (!defined('DSLPFW_PROMOTIONAL_SLUG')) {
    define('DSLPFW_PROMOTIONAL_SLUG', 'basic_local_pickup');
}
/**
 * The function is used to dynamically generate the URL of the directory containing the main plugin file.
 */
if (!defined('DSLPFW_PLUGIN_URL')) {
    define('DSLPFW_PLUGIN_URL', plugin_dir_url(__FILE__));
}
/**
 * The function is used to dynamically generate the base path of the directory containing the main plugin file.
 */
if (!defined('DSLPFW_PLUGIN_BASE_DIR')) {
    define('DSLPFW_PLUGIN_BASE_DIR', plugin_dir_path(__FILE__));
}
/**
 * Define the URL of the plugin store if not already defined.
 */
if (!defined('DSLPFW_STORE_URL')) {
    define('DSLPFW_STORE_URL', 'https://www.thedotstore.com/');
}
/**
 * Define the post type name for listing rule use.
 */
if (!defined('DSLPFW_POST_TYPE')) {
    define('DSLPFW_POST_TYPE', 'ds_pickup_location');
}
/**
 * Define the post type name for listing rule use.
 */
if (!defined('DSLPFW__DEV_MODE')) {
    define('DSLPFW__DEV_MODE', false);
}
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-local-pickup-woocommerce-activator.php
 */
if (!function_exists('dslpfw_activate_local_pickup_woocommerce')) {
    function dslpfw_activate_local_pickup_woocommerce()
    {
        require_once plugin_dir_path(__FILE__) . 'includes/class-local-pickup-woocommerce-activator.php';
        DSLPFW_Local_Pickup_Woocommerce_Activator::activate();
    }

}
/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-local-pickup-woocommerce-deactivator.php
 */
if (!function_exists('dslpfw_deactivate_local_pickup_woocommerce')) {
    function dslpfw_deactivate_local_pickup_woocommerce()
    {
        require_once plugin_dir_path(__FILE__) . 'includes/class-local-pickup-woocommerce-deactivator.php';
        DSLPFW_Local_Pickup_Woocommerce_Deactivator::deactivate();
    }

}
register_activation_hook(__FILE__, 'dslpfw_activate_local_pickup_woocommerce');
register_deactivation_hook(__FILE__, 'dslpfw_deactivate_local_pickup_woocommerce');
/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-local-pickup-woocommerce.php';
/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
if (!function_exists('dslpfw_run_local_pickup_woocommerce')) {
    function dslpfw_run_local_pickup_woocommerce()
    {
        dslpfw();
        dslpfw()->run();
    }

}
dslpfw_run_local_pickup_woocommerce();
/**
 * Check WooCommerce activation before activate our plugin
 */
if (!function_exists('dslpfw_deactivate_plugin')) {
    add_action('admin_init', 'dslpfw_deactivate_plugin');
    function dslpfw_deactivate_plugin()
    {
        if (is_multisite()) {
            $active_plugins = get_option('active_plugins', array());
            if (is_multisite()) {
                $network_active_plugins = get_site_option('active_sitewide_plugins', array());
                $active_plugins = array_merge($active_plugins, array_keys($network_active_plugins));
                $active_plugins = array_unique($active_plugins);
            }
            if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', $active_plugins), true)) {
                deactivate_plugins(plugin_basename(__FILE__));
            }
        }
        else {
            if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true)) {
                deactivate_plugins(plugin_basename(__FILE__));
            }
        }
    }

}