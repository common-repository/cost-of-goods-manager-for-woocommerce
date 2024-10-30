<?php

defined( 'ABSPATH' ) || exit;

class ZCOSTOFGOODS_Core {
	private static $initiated = false;

	public static function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$current_user_id            = get_current_user_id();
			$dismissed_notice_timestamp = get_option( 'zcostofgoods_wc_notice_' . $current_user_id, null );
			if ( isset( $dismissed_notice_timestamp ) ) {
				$dismissed_notice_date = date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-d H:i:s', $dismissed_notice_timestamp ) . '+15 days' ) );
				$today                 = date( 'Y-m-d H:i:s' );

				if ( $dismissed_notice_date <= $today ) {
					add_action( 'admin_notices', function () {
						?>
                        <div class="notice notice-error is-dismissible zcostofgoods_wc_notice"
                             data-link="<?php echo admin_url( 'admin-ajax.php' ); ?>">
                            <p>To enable more advanced features for Cost of Goods WordPress WooCommerce activate the
                                WooCommerce
                                plugin</p>
                        </div>
						<?php
					} );
				}
			} else {
				add_action( 'admin_notices', function () {
					?>
                    <div class="notice notice-error is-dismissible zcostofgoods_wc_notice"
                         data-link="<?php echo admin_url( 'admin-ajax.php' ); ?>">
                        <p>To enable more advanced features for Cost of Goods WordPress WooCommerce activate the
                            WooCommerce
                            plugin</p>
                    </div>
					<?php
				} );
			}
		}

		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	private static function init_hooks() {
	}

	public static function plugin_activation() {
		self::create_product_cog_table();
	}

	public static function create_product_cog_table() {
		global $wpdb;

		$table_name_product_cog = $wpdb->prefix . 'zcostofgoods_product_cog';

		$charset_collate = $wpdb->get_charset_collate();

		$sql_assignments = "CREATE TABLE $table_name_product_cog(
				id mediumint( 9 ) NOT null AUTO_INCREMENT,
				timestamp datetime NOT null,
				user_id mediumint( 9 ) NOT null,
				product_id text NOT null,
				cost_of_goods text NOT null,
				PRIMARY KEY( id )
			)$charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql_assignments );
	}

	public static function plugin_deactivation() {
	}
}
