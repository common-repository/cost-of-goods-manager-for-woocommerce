<?php
/**
 *
 * Plugin Name: Cost of Goods Manager for WooCommerce
 * Plugin URI: https://www.bizswoop.com/wp/costofgoods
 * Description: Add cost of goods management functionality to products for your store to track cost, profit margin & markup.
 * Version: 1.0.9
 * Text Domain: zcost-of-goods
 * WC requires at least: 2.4.0
 * WC tested up to: 5.5.2
 * Author: BizSwoop a CPF Concepts, LLC Brand
 * Author URI: https://www.bizswoop.com
 */

defined( 'ABSPATH' ) || exit;

define( 'ZCOSTOFGOODS_BASE_FILE', __FILE__ );
define( 'ZCOSTOFGOODS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );


/* Loading Classes */
require_once( ZCOSTOFGOODS_PLUGIN_DIR . 'helper/class-zcostofgoods-core.php' );

register_activation_hook( __FILE__, array( 'ZCOSTOFGOODS_Core', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'ZCOSTOFGOODS_Core', 'plugin_deactivation' ) );

add_action( 'init', array( 'ZCOSTOFGOODS_Core', 'init' ) );

if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	require_once( ZCOSTOFGOODS_PLUGIN_DIR . 'helper/class-zcostofgoods-core-admin.php' );
	$adm_admin = new ZCOSTOFGOODS_Core_Admin();
}

require_once( ZCOSTOFGOODS_PLUGIN_DIR . 'cost-of-goods-functions.php' );
