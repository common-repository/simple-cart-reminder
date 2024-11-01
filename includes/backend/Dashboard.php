<?php

class SCR_ITJ_Dashboard {
	protected $settings;
	protected static $initd = false;

	/**
	 * init pluggable functions.
	 *
	 * @return  void
	 */
	public static function init() {
		// Do nothing if pluggable functions already initd.
		if ( self::$initd ) {
			return;
		}

		if ($_GET["post_type"] !== SCR_ITJ_post_type) {
			return;
		}

		add_action( 'admin_head', [ __CLASS__, 'posttype_admin_css' ] );
		add_action( 'admin_menu', [ __CLASS__, 'remove_submenu' ], 99999 );

		# Called only in /wp-admin/edit.php pages
		add_action( 'load-edit.php', function () {
			add_filter( 'views_edit-simple_cart_reminder', array(
				__CLASS__,
				'wce_tabs'
			) ); // talk is my custom post type
		} );
		add_action( 'manage_posts_extra_tablenav', [ __CLASS__, 'run_extentions_csscarts' ], 99999, 1 );
		add_action( 'admin_notices', [ __CLASS__, 'feedback_notice' ] );
		self::$initd = true;
	}


	public static function wpshout_add_cron_interval( $schedules ) {
		$schedules['everyminute'] = array(
				'interval'  => 60, // time in seconds
				'display'   => 'Every Minute'
		);
		return $schedules;
	}

	public static function feedback_notice() {
		?>
		<div class="notice notice-success is-dismissible">
				<p><?php _e('👋 <a target="blank_" href="https://api.itservicejung.de/feedback?plugin='.SCR_ITJ_post_type.'">We need your suggestion on how we can improve <b>'.SCR_ITJ_plugin_name.'</b>!</a> 😛 ', 'sample-text-domain' ); ?></p>
		</div>
	<?php

	}

	// 3.- Remove custom escheduled event on plugin deactiviation
	public static function cyb_plugin_deactivation() {
		wp_clear_scheduled_hook( 'simple_cart_reminder_cron_job' );
	}


	public static function run_extentions_csscarts( $which ) {
		global $typenow;
		global $wpdb;

		$POSTID = -1;

		if ( 'simple_cart_reminder' === $typenow && 'bottom' === $which ) {
			echo '<div class="csscart-brand">';

			//Doku
			//https://docs.woocommerce.com/wc-apidocs/class-WC_Session_Handler.html

			//Holle mir alle Sessions von woocommerce
			// $query = "SELECT session_value, session_expiry FROM wp_woocommerce_sessions";
			// $sessionArray = $wpdb->get_results( $query, ARRAY_A );
			//
			// foreach ( $sessionArray as $key => $value ) {
			//
			// 	$dateex = $value["session_expiry"];
			// 	$json = unserialize($value['session_value']);
			// 	$customer = unserialize($json["customer"]);
			//
			// 	echo '<pre>';
			// 			var_dump("id: ".$customer["id"]." ".$customer["email"]." ".date("Y-m-d H:i:s", $dateex));
			// 	echo '</pre>';
			//
			// }

			echo '</div>';

			?>
			<?php
		}
	}



	public static function remove_submenu() {
		global $submenu;
		unset( $submenu['edit.php?post_type=simple_cart_reminder'][11] );
		unset( $submenu['edit.php?post_type=simple_cart_reminder'][12] );
	}

	public static function posttype_admin_css() {
		global $post_type;
		$post_types = array(
			/* set post types */
			'simple_cart_reminder',
		);
		if ( in_array( $post_type, $post_types ) ) {
			echo '<style type="text/css">#post-preview, #view-post-btn,.updated p a,.view{display: none;}</style>';
		}
	}


# echo the tabs
	public static function wce_tabs() {
		echo '
		  <h2 class="nav-tab-wrapper">
		    <a class="nav-tab nav-tab-active" href="edit.php?post_type=simple_cart_reminder">' . esc_html__( 'Email Templates','scrlang' ) . '</a>
		    <!--<a class="nav-tab" href="edit.php?post_type=simple_cart_reminder&page=wce_scheduled_email">' . esc_html__( 'Configuration','scrlang' ) . '</a>
		    <a class="nav-tab" href="edit.php?post_type=simple_cart_reminder&page=statistic">' . esc_html__( 'Statistics','scrlang' ) . '</a>-->
		  </h2>
		 ';
	}

}
