<?php

/**
 * Fired during plugin activation
 *
 * @link       https://cubixsol.com
 * @since      1.0.0
 *
 * @package    wc-discount
 * @subpackage wc-discount/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    wc-discount
 * @subpackage wc-discount/includes
 * @author     Cubix Solution <https://cubixsol.com>
 */
class Wc_Discount_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

        global $wpdb;
        $table = $wpdb->prefix."wc_discount";

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id INTEGER NOT NULL AUTO_INCREMENT,
            customer_id INTEGER,
            category_id INTEGER,
            price       VARCHAR(30),
            PRIMARY KEY (id))";

        require_once(ABSPATH.'wp-admin/includes/upgrade.php');
        dbDelta($sql);
		
        
        if ( !class_exists( 'WooCommerce' ) ) {
            deactivate_plugins( basename( __FILE__ ) );
            wp_die( __( "WooCommerce is required for this plugin to work properly. Please activate WooCommerce.", 'wc-discount' ), "", array( 'back_link' => 1 ) );
        }
        if ( is_plugin_active( 'wc-discount/wc-discount.php' ) ) {
            deactivate_plugins( basename( __FILE__ ) );
            wp_die( __( "Is everything fine? You already have the Premium version installed in your website. For any issues, kindly raise a ticket via <a target='_blank' href='https://www.webtoffee.com/support/'>support</a>", 'wc-discount' ), "", array( 'back_link' => 1 ) );
        }

        update_option( 'xa_pipe_plugin_installed_date', date( 'Y-m-d H:i:s' ) );
        set_transient( '_welcome_screen_activation_redirect', true, 30 );

	}

}
