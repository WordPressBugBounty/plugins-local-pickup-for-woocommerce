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
?>
<div class="dslpfw-section-left">
	<div class="dslpfw-main-table res-cl">
		<div class="dots-getting-started-main">
            <div class="getting-started-content">
                <span><?php esc_html_e( 'How to Get Started', 'local-pickup-for-woocommerce' ); ?></span>
                <h3><?php esc_html_e( 'Welcome to Local Pickup Plugin', 'local-pickup-for-woocommerce' ); ?></h3>
                <p><?php esc_html_e( 'Thank you for choosing our top-rated WooCommerce Local Pickup plugin. Our user-friendly interface makes setting up local pickup straightforward.', 'local-pickup-for-woocommerce' ); ?></p>
                <p>
                    <?php 
                    echo sprintf(
                        esc_html__('To help you get started, watch the quick tour video on the right. For more help, explore our help documents or visit our %s for detailed video tutorials.', 'local-pickup-for-woocommerce'),
                        '<a href="' . esc_url('https://www.youtube.com/@Dotstore16') . '" target="_blank">' . esc_html__('YouTube channel', 'local-pickup-for-woocommerce') . '</a>',
                    );
                    ?>
                </p>
                <div class="getting-started-actions">
                    <a href="<?php echo esc_url(add_query_arg(array('page' => 'dslpfw-local-pickup-settings'), admin_url('admin.php'))); ?>" class="quick-start"><?php esc_html_e( 'Setup Local Pickup', 'local-pickup-for-woocommerce' ); ?><span class="dashicons dashicons-arrow-right-alt"></span></a>
                    <a href="https://docs.thedotstore.com/article/951-beginners-guide-for-local-pickup" target="_blank" class="setup-guide"><span class="dashicons dashicons-book-alt"></span><?php esc_html_e( 'Read the Setup Guide', 'local-pickup-for-woocommerce' ); ?></a>
                </div>
            </div>
            <div class="getting-started-video">
                <iframe width="960" height="600" src="<?php echo esc_url('https://www.youtube.com/embed/nRk_wJlHjQw'); ?>" title="<?php esc_attr_e( 'Plugin Tour', 'local-pickup-for-woocommerce' ); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </div>
	</div>
</div>
<?php
require_once( plugin_dir_path( __FILE__ ) . 'header/plugin-footer.php' );