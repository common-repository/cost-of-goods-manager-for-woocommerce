<?php

defined( 'ABSPATH' ) || exit;

class ZCOSTOFGOODS_Core_Admin {
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init_hooks' ), 1 );
	}


	public function init_hooks() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'load_resources' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'load_resources' ) );

		/*Ajax Post*/
		add_action( 'wp_ajax_zcostofgoods_wc_notice_dismissed', array( $this, 'zcostofgoods_wc_notice_dismissed' ) );


		add_action( 'woocommerce_product_options_general_product_data', array(
			$this,
			'zcostofgoods_product_options'
		) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'zcostofgoods_product_save_fields' ), 10, 2 );


		add_action( 'woocommerce_variation_options_pricing', array(
			$this,
			'zcostofgoods_add_custom_field_to_variations'
		), 10, 3 );
		add_action( 'woocommerce_save_product_variation',
			array( $this, 'zcostofgoods_save_custom_field_variations' ), 10, 2 );
		add_filter( 'woocommerce_available_variation',
			array( $this, 'zcostofgoods_add_custom_field_variation_data' ) );

		add_filter( 'manage_edit-product_columns',
			array( $this, 'zcostofgoods_costs_column' ), 20 );
		add_action( 'manage_posts_custom_column', array( $this, 'zcostofgoods_populate_costs' ) );


		add_action( 'woocommerce_admin_order_item_headers', array( $this, 'zcostofgoods_admin_order_items_headers' ) );
		add_action( 'woocommerce_admin_order_item_values', array( $this, 'zcostofgoods_admin_order_item_values' ) );
		add_action( 'woocommerce_admin_order_totals_after_total', array(
			$this,
			'zcostofgoods_admin_order_totals_after_total'
		), 90 );
		add_action( 'woocommerce_get_sections_products',
			array( $this, 'zcostofgoods_woocommerce_get_sections_products' ) );
		add_action( 'woocommerce_get_settings_products',
			array( $this, 'zcostofgoods_woocommerce_get_settings_products' ), 10, 2 );
		add_action( 'woocommerce_admin_field_cogbutton',
			array( $this, 'zcostsofgoods_woocommerce_admin_field_button' ), 10, 1 );
		add_action( 'woocommerce_admin_field_cogloading',
			array( $this, 'zcostsofgoods_woocommerce_admin_field_button' ), 10, 1 );
		add_action( 'woocommerce_save_product_variation',
			array( $this, 'zcostofgoods_save_product_variation' ), 10, 2 );
		add_action( 'woocommerce_update_product',
			array( $this, 'zcostofgoods_update_product' ), 10, 1 );


		/*Admin Post*/

		add_action( 'admin_post_zcostofgoods_recalculate_orders_cog',
			array( $this, 'zcostofgoods_recalculate_orders_cog' ), 10 );
		add_action( 'admin_post_zcostofgoods_calc_orders_cog',
			array( $this, 'zcostofgoods_calculate_orders_cog' ), 10 );

		add_action( 'add_meta_boxes', array( $this, 'zcostofgoods_add_meta_boxes' ), 2 );

	}

	public function admin_init() {
	}

	public function zcostofgoods_add_meta_boxes() {
		if ( get_option( 'zcostofgoods_enable_cost_of_goods', 'yes' ) == 'yes' ) {
			add_meta_box( 'order_cost_of_goods', __( 'Cost of Goods', 'woocommerce' ), function () {
				global $post;
				echo '<div id="cost-of-goods-box">';

				$cost   = get_post_meta( $post->ID, '_zcostofgood_order_cost', true );
				$profit = get_post_meta( $post->ID, '_zcostofgood_order_order_profit', true );
				$margin = get_post_meta( $post->ID, '_zcostofgood_order_order_margin', true );
				$markup = get_post_meta( $post->ID, '_zcostofgood_order_order_markup', true );

				echo '<table width="100%">';
				echo '<thead><tr><td width="50%"></td><td width="50%"></td></tr>';
				echo '<tbody>';
				echo '<tr><td>Cost</td><td>' . get_woocommerce_currency_symbol() . $cost . '</td></tr>';
				echo '<tr><td>Profit</td><td>' . get_woocommerce_currency_symbol() . $profit . '</td></tr>';
				echo '<tr><td>Margin</td><td>' . number_format( (float) $margin, 2 ) . '%</td></tr>';
				echo '<tr><td>Markup</td><td>' . number_format( (float) $markup, 2 ) . '%</td></tr>';
				echo '</tbody>';
				echo '</table>';


				echo '</div>';
			}, 'shop_order', 'side', 'high' );
			?>
            <style>
                #cost-of-goods-box {
                    min-height: 30px;
                    text-align: center;
                    vertical-align: middle;
                }
            </style>
			<?php
		}
	}

	public function zcostofgoods_recalculate_orders_cog() {
		$this->zcostofgoods_clear_cog_table();

		$products = wc_get_products( array(
			'numberposts' => - 1,
		) );

		foreach ( $products as $product ) {
			$product_id = $product->get_id();
			$cog        = get_post_meta( $product_id, 'zcost_of_goods_cost', true );
			if ( $cog == false || $cog == '' ) {
				continue;
			} else {
				$this->zcostofgoods_insert_new_cog_entry( array(
					'cost_of_goods' => $cog,
					'product_id'    => $product_id
				) );
			}

			if ( $product->product_type == 'variable' ) {
				$variations = $product->get_children();
				foreach ( $variations as $key => $variation_id ) {
					$cog = get_post_meta( $variation_id, 'zcost_of_goods_cost', true );
					if ( $cog == false || $cog == '' ) {
						continue;
					} else {
						$this->zcostofgoods_insert_new_cog_entry( array(
							'cost_of_goods' => $cog,
							'product_id'    => $variation_id
						) );
					}
				}
			}
		}

		$this->zcostofgoods_calculate_orders_cog( true );

		ob_clean();

		return print( admin_url( "admin.php?page=wc-settings&tab=products&section=cost_of_goods" ) );
	}

	public function zcostofgoods_calculate_orders_cog( $recalculate = false ) {
		$include_payment_fees  = get_option( 'zcostofgoods_include_payment_fees' );
		$include_shipping_cost = get_option( 'zcostofgoods_include_shipping_total_cost' );
		$include_total_taxes   = get_option( 'zcostofgoods_include_total_taxes' );

		$orders = wc_get_orders(
			array(
				'numberposts' => - 1,
			)
		);

		foreach ( $orders as $order ) {
			$order_calculated_cog = get_post_meta( $order->get_id(), '_zcostofgood_order_cost', true );

			$timestamp = $order->get_date_created()->format( 'Y-m-d H:i:s' );

			if ( $order_calculated_cog != '' && $recalculate == false ) {
				continue;
			}

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
				$cost += $this->zcostofgoods_get_cost_of_good( $item->get_product_id(), $item->get_variation_id(), $timestamp ) * $item->get_quantity();
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

		ob_clean();

		return print( admin_url( "admin.php?page=wc-settings&tab=products&section=cost_of_goods" ) );
	}

	public function zcostofgoods_wc_notice_dismissed() {
		$current_user_id = get_current_user_id();
		update_option( 'zcostofgoods_wc_notice_' . $current_user_id,
			current_time( 'timestamp' ) );
	}

	public function load_resources() {
		global $wp_scripts;

		// Register admin styles.
        wp_register_style( 'zcostofgoods_style',
			plugins_url( 'css/style.css',
				ZCOSTOFGOODS_BASE_FILE ),
			array(),
			'1.0.1' );

		wp_enqueue_style( 'zcostofgoods_style' );

		$register_scripts = array(
			'zcostofgoods_script' => array(
				'src'     => plugins_url( 'js/script.js',
					ZCOSTOFGOODS_BASE_FILE ),
				'deps'    => array( 'jquery' ),
				'version' => '1.0.1',
			)
		);

		foreach ( $register_scripts as $name => $props ) {
			wp_register_script( $name,
				$props['src'],
				$props['deps'],
				$props['version'],
				true );
		}
		wp_enqueue_script( 'zcostofgoods_script' );

		wp_localize_script( 'ajax-script',
			'ajax_object',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'we_value' => 1234
			) );
	}

	public function zcostofgoods_product_options() {
		echo '<div class="options_group">';

		woocommerce_wp_text_input( array(
			'id'       => 'zcost_of_goods_cost',
			'value'    => get_post_meta( get_the_ID(),
				'zcost_of_goods_cost',
				true ),
			'label'    => __( 'Cost of Goods ('
			                  . get_woocommerce_currency_symbol()
			                  . ')',
				'zcost-of-goods' ),
			'desc_tip' => true,
		) );

		echo '</div>';
	}

	public function zcostofgoods_product_save_fields( $id, $post ) {
		update_post_meta( $id,
			'zcost_of_goods_cost',
			sanitize_text_field( $_POST['zcost_of_goods_cost'] ) );
	}

	public function zcostofgoods_add_custom_field_to_variations( $loop, $variation_data, $variation ) {
		$value = get_post_meta( $variation->ID, 'zcost_of_goods_cost', true );

		woocommerce_wp_text_input( array(
				'id'            => 'zcost_of_goods_cost['
				                   . $loop . ']',
				'class'         => 'zcostofgoods_full_width',
				'wrapper_class' => 'zcostofgoods_float_left zcostofgoods_full_width',
				'label'         => __( 'Cost of Goods ('
				                       . get_woocommerce_currency_symbol()
				                       . ')',
					'zcost-of-goods' ),
				'value'         => $value,
				'data_type'     => 'price'
			)
		);
	}

	public function zcostofgoods_save_custom_field_variations( $variation_id, $i ) {
		$custom_field = $_POST['zcost_of_goods_cost'][ $i ];
		if ( isset( $custom_field ) ) {
			update_post_meta( $variation_id,
				'zcost_of_goods_cost',
				sanitize_text_field( $custom_field ) );
		}
	}

	public function zcostofgoods_add_custom_field_variation_data( $variations ) {
		$variations['zcost_of_goods_cost']
			= '<div class="woocommerce_custom_field">' . __( 'Cost of Goods ('
			                                                 . get_woocommerce_currency_symbol()
			                                                 . ') ',
				'zcost-of-goods' )
			  . ': <span>' . get_post_meta( $variations['variation_id'],
				'zcost_of_goods_cost',
				true ) . '</span></div>';

		return $variations;
	}

	public function zcostofgoods_costs_column( $columns_array ) {
		return array_slice( $columns_array, 0, 6, true )
		       + array( 'cost' => __( 'Cost', 'zcost-of-goods' ) )
		       + array_slice( $columns_array, 6, null, true );
	}

	public function zcostofgoods_populate_costs( $column_name ) {
		if ( $column_name == 'cost' ) {
			$x       = get_post_meta( get_the_ID(), 'zcost_of_goods_cost', true );
			$product = wc_get_product( get_the_ID() );
			if ( $product->has_child() ) {
				$variations = $product->get_children();
				$costs      = array();
				foreach ( $variations as $variation ) {
					array_push( $costs, get_post_meta( $variation, 'zcost_of_goods_cost', true ) );
				}
				$costs = array_diff( $costs, array( "" ) );
				sort( $costs );
				if ( count( $costs ) == 0 ) {
					echo '–';
				} elseif ( count( $costs ) > 1 && $costs[0] !== $costs[ count( $costs ) - 1 ] ) {
					echo wc_price( $costs[0] ) . ' – ' . wc_price( $costs[ count( $costs ) - 1 ] );
				} else {
					echo wc_price( $costs[0] );
				}
			} else {
				if ( $x != '' ) {
					echo wc_price( $x );
				} else {
					echo '–';
				}
			}
		}
	}

	public function zcostofgoods_admin_order_items_headers( $order ) {
		?>
        <th class="item_cost_of_goods"><?php echo __( 'Cost of Goods',
				'zcost-of-goods' ); ?></th>
		<?php
	}

	public function zcostofgoods_admin_order_item_values( $item ) {
		if ( isset( $item ) && ! is_bool( $item ) ) {
			global $post_id;
			$order        = wc_get_order( $post_id );
			$item_id      = $item->id;
			$variation_id = $item->get_variation_id();
			$timestamp    = $order->get_date_created()->format( 'Y-m-d H:i:s' );

			$cog = $this->zcostofgoods_get_cost_of_good( $item_id, $variation_id, $timestamp );
			?>
            <td class="item_cost_of_goods" width="10%">
                <div class="view">
                    <span class="woocommerce-Price-amount amount"><?php echo wc_price( $cog ); ?></span>
                </div>
            </td>
			<?php
		}
	}

	public function zcostofgoods_admin_order_totals_after_total( $order_id ) {
		$order         = wc_get_order( $order_id );
		$cost_of_goods = 0;
		$timestamp     = $order->get_date_created()->format( 'Y-m-d H:i:s' );

		foreach ( $order->get_items() as $item ) {
			$cost_of_goods += $this->zcostofgoods_get_cost_of_good( $item->get_product_id(),
					$item->get_variation_id(), $timestamp )
			                  * $item->get_quantity();
		}
		?>
        <tbody>
        <tr>
            <td class="label"><?php echo __( 'Cost of Goods',
					'zcost-of-goods' ); ?>:
            </td>
            <td width="1%"></td>
            <td class="total">
                <span class="woocommerce-Price-amount amount"><?php echo wc_price( $cost_of_goods ); ?></span>
            </td>
        </tr>
        </tbody>
		<?php
	}

	public function zcostofgoods_woocommerce_get_sections_products( $sections ) {
		$sections['cost_of_goods'] = __( 'Cost of Goods', 'zcost-of-goods' );

		return $sections;
	}

	public function zcostofgoods_woocommerce_get_settings_products( $settings, $current_section ) {
		if ( 'cost_of_goods' == $current_section ) {
			$settings = array(
				array(
					'title' => __( 'General Settings', 'zcost-of-goods' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'product_cost_of_goods_general_options',
				),

				array(
					'title'   => __( 'Include total fees', 'zcost-of-goods' ),
					'desc'    => __( 'The cost related to the payment gateway used will be included in the total product cost',
						'zcost-of-goods' ),
					'id'      => 'zcostofgoods_include_payment_fees',
					'default' => 'no',
					'type'    => 'checkbox',
				),

				array(
					'title'   => __( 'Include shipping total cost',
						'zcost-of-goods' ),
					'desc'    => __( 'Shipping costs will be included in the total product cost',
						'zcost-of-goods' ),
					'id'      => 'zcostofgoods_include_shipping_total_cost',
					'default' => 'no',
					'type'    => 'checkbox',
				),

				array(
					'title'   => __( 'Include taxes cost for each product',
						'zcost-of-goods' ),
					'desc'    => __( 'Tax costs will be included in the total product cost',
						'zcost-of-goods' ),
					'id'      => 'zcostofgoods_include_total_taxes',
					'default' => 'no',
					'type'    => 'checkbox',
				),

				array(
					'title'   => __( 'Order meta box', 'zcost-of-goods' ),
					'desc'    => __( 'Enable Cost of Goods meta box on Order details', 'zcost-of-goods' ),
					'id'      => 'zcostofgoods_enable_cost_of_goods',
					'default' => 'yes',
					'type'    => 'checkbox',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'product_cost_of_goods_action_end',
				),

				array(
					'title' => __( 'Apply cost to previous orders',
						'zcost-of-goods' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'product_cost_of_goods_actions',
				),

				array(
					'title'  => __( 'Apply costs to orders that do not have costs set.',
						'zcost-of-goods' ),
					'type'   => 'cogbutton',
					'action' => 'zcostofgoods_calc_orders_cog',
					'text'   => __( 'Apply Costs', 'zcost-of-goods' ),
					'method' => 'admin-post',
					'class'  => 'button',
					'desc'   => '',
					'id'     => 'product_cost_of_goods_calculate',
				),


				array(
					'title'  => __( 'Apply and override the costs on all your orders.',
						'zcost-of-goods' ),
					'type'   => 'cogbutton',
					'action' => 'zcostofgoods_recalculate_orders_cog',
					'text'   => __( 'Apply Costs', 'zcost-of-goods' ),
					'method' => 'admin-post',
					'class'  => 'button',
					'desc'   => '',
					'id'     => 'product_cost_of_goods_recalculate',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'product_cost_of_goods_action_end',
				),

				array(
					'title' => __( 'We are calculating Cost of Goods for each order, please wait.<br/> This operation might take a new minutes, do NOT refresh the page.' ),
					'type'  => 'cogloading',
					'id'    => 'product_cost_of_goods_action_end',
				),
			);
		}

		return $settings;
	}

	public function zcostsofgoods_woocommerce_admin_field_button( $value ) {
		if ( 'cogbutton' == $value['type'] ) {
			$tooltip_html = $value['tooltip_html'];
			$action       = $value['method'] == 'admin-post'
				? admin_url( 'admin-post.php' ) : admin_url( 'admin-ajax.php' );
			?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?><?php echo $tooltip_html; // WPCS: XSS ok.
						?></label>
                </th>
                <td class="forminp">
                    <a href="<?php echo $action; ?>"
                       id="<?php echo $value['id']; ?>" type="submit"
                       class="<?php echo $value['class']; ?> zcostofgoods_cog_order_button"
                       data-action="<?php echo $value['action']; ?>"><?php echo $value['text']; ?></a><?php echo $value['desc']; ?>
                </td>
            </tr>
			<?php
		}
		if ( 'cogloading' == $value['type'] ) {
			$tooltip_html = $value['tooltip_html'];
			$action       = $value['method'] == 'admin-post'
				? admin_url( 'admin-post.php' ) : admin_url( 'admin-ajax.php' );
			?>
            <div id="zcostofgoods_loading_screen" class="zcostofgoods_loading_screen">

                <img alt="loading"
                     src="<?php echo plugin_dir_url( ZCOSTOFGOODS_BASE_FILE ) . 'asset/wpspin_light-2x.gif'; ?>"/>
                <span style="margin-left: 1rem; font-size: 1rem; font-weight: bold; line-height: 1.5;"><?php echo $value['title']; ?></span>
            </div>
			<?php
		}
	}

	public function zcostofgoods_insert_new_cog_entry( $entry ) {
		global $wpdb;

		$table_name_product_cog = $wpdb->prefix . 'zcostofgoods_product_cog';

		$latest_cog = $this->zcostofgoods_get_last_cog_entry( $entry['product_id'] );

		if ( ! isset( $latest_cog ) ) {
			$timestamp = '1970-01-01 00:00:00';
		} else {
			$timestamp = current_time( 'mysql' );
		}

		$a = $wpdb->insert( $table_name_product_cog,
			array(
				'timestamp'     => $timestamp,
				'user_id'       => get_current_user_id(),
				'product_id'    => $entry['product_id'],
				'cost_of_goods' => number_format( str_replace( ',', '.', $entry['cost_of_goods'] ), 2 ),
			) );
	}

	public function zcostofgoods_clear_cog_table() {
		global $wpdb;

		$table_name_product_cog = $wpdb->prefix . 'zcostofgoods_product_cog';

		$wpdb->get_row( "DELETE FROM $table_name_product_cog;" );
	}

	public function zcostofgoods_save_product_variation( $variation_id, $i ) {
		$cog = get_post_meta( $variation_id, 'zcost_of_goods_cost', true );

		$latest_cog = $this->zcostofgoods_get_last_cog_entry( $variation_id );
		if ( ( isset( $latest_cog ) && $latest_cog->cost_of_goods != $cog )
		     || ! isset( $latest_cog ) ) {
			$this->zcostofgoods_insert_new_cog_entry( array(
				'cost_of_goods' => $cog,
				'product_id'    => $variation_id
			) );
		}
	}

	public function zcostofgoods_update_product( $product_id ) {
		$cog = get_post_meta( $product_id,
			'zcost_of_goods_cost',
			true );

		$latest_cog = $this->zcostofgoods_get_last_cog_entry( $product_id );

		if ( ( isset( $latest_cog ) && $latest_cog->cost_of_goods != $cog )
		     || ! isset( $latest_cog ) ) {
			$this->zcostofgoods_insert_new_cog_entry( array(
				'cost_of_goods' => $cog,
				'product_id'    => $product_id
			) );
		}
	}

	public function zcostofgoods_get_last_cog_entry( $product_id ) {
		global $wpdb;

		$table_name_product_cog = $wpdb->prefix . 'zcostofgoods_product_cog';

		$result
			= $wpdb->get_row( "SELECT * FROM $table_name_product_cog WHERE product_id='$product_id' ORDER BY timestamp DESC LIMIT 1;" );

		return $result;
	}

	public function zcostofgoods_get_latest_cog_entry_before_timestamp( $product_id, $timestamp ) {
		global $wpdb;

		$table_name_product_cog = $wpdb->prefix . 'zcostofgoods_product_cog';

		$result
			= $wpdb->get_row( "SELECT * FROM $table_name_product_cog WHERE product_id='$product_id' AND timestamp<='$timestamp' ORDER BY timestamp DESC LIMIT 1;" );

		return $result;
	}

	public function zcostofgoods_get_cost_of_good( $item_id, $variation_id, $timestamp ) {
		$variation_cost_result = $this->zcostofgoods_get_latest_cog_entry_before_timestamp( $variation_id, $timestamp );

		if ( isset( $variation_cost_result ) ) {
			return $variation_cost_result->cost_of_goods;
		}

		$variation_cost_result = $this->zcostofgoods_get_latest_cog_entry_before_timestamp( $item_id, $timestamp );

		if ( isset( $variation_cost_result ) ) {
			return $variation_cost_result->cost_of_goods;
		}

		return 0;
	}

}
