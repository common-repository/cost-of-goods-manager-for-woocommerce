<?php

defined( 'ABSPATH' ) || exit;

add_action( 'woocommerce_checkout_update_order_meta', 'zcostofgoods_checkout_update_order_meta', 10, 1 );
add_action( 'woocommerce_payment_complete', 'zcostofgoods_checkout_update_order_meta', 10, 1 );

function zcostofgoods_checkout_update_order_meta( $order_id ) {
	$cog = get_post_meta( $order_id, '_zcostofgood_order_cost', true );
	if ( $cog != '' || $cog != false ) {
		return;
	}

	require_once( ZCOSTOFGOODS_PLUGIN_DIR . 'helper/class-zcostofgoods-core-admin.php' );
	$adm_admin = new ZCOSTOFGOODS_Core_Admin();

	$order = wc_get_order( $order_id );

	$include_payment_fees  = get_option( 'zcostofgoods_include_payment_fees' );
	$include_shipping_cost = get_option( 'zcostofgoods_include_shipping_total_cost' );
	$include_total_taxes   = get_option( 'zcostofgoods_include_total_taxes' );

	$timestamp = $order->get_date_created()->format( 'Y-m-d H:i:s' );

	$cost     = 0;
	$taxes    = 0;
	$shipping = 0;
	$fees     = 0;

	if ( $include_total_taxes == 'yes' ) {
		$taxes = $order->get_total_tax();
	}

	if ( $include_shipping_cost == 'yes' ) {
		$shipping = $order->get_shipping_total();
	}

	if ( $include_payment_fees == 'yes' ) {
		$fees = $order->get_total_fees();
	}

	foreach ( $order->get_items() as $item ) {
		$cost += $adm_admin->zcostofgoods_get_cost_of_good( $item->get_product_id(),
				$item->get_variation_id(), $timestamp ) * $item->get_quantity();
	}

	$order_total = $order->get_total();
	$cost        += $taxes + $shipping + $fees;
	$profit      = $order_total - $cost;
	$margin      = ( $profit / $order_total ) * 100;
	$markup      = $cost == 0 ? 0 : ( $profit / $cost ) * 100;

	update_post_meta( $order->get_id(),
		'_zcostofgood_order_cost',
		$cost );
	update_post_meta( $order->get_id(),
		'_zcostofgood_order_order_profit',
		$profit );
	update_post_meta( $order->get_id(),
		'_zcostofgood_order_order_margin',
		$margin );
	update_post_meta( $order->get_id(),
		'_zcostofgood_order_order_markup',
		$markup );
}
