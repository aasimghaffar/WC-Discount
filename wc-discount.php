<?php
/**
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://cubixsol.com
 * @since             1.0.0
 * @package           wc-discount
 *
 * @wordpress-plugin
 * Plugin Name:       Wc Discount
 * Plugin URI:        https://cubixsol.com/plugins
 * Description:       Woocommerce Category Discount plugin allows you to manage Product    Discount by Category base to the Specific User Products. It is also working with Woocommerce Variation Products.
 * Version:           1.0.0
 * Author:            Cubix Solution
 * Author URI:        https://cubixsol.com/contact-us
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wc-discount
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'VERSION', '1.0.0' );
// ini_set('display_errors', 1);

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wc-discount-activator.php
 */
function activate_wc_discount() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-discount-activator.php';
	Wc_Discount_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wc-discount-deactivator.php
 */
function deactivate_wc_discount() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-discount-deactivator.php';
	Wc_Discount_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wc_discount' );
register_deactivation_hook( __FILE__, 'deactivate_wc_discount' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wc-discount.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wc_discount() {
	$plugin = new Wc_Discount();
	$plugin->run();
}
run_wc_discount();
