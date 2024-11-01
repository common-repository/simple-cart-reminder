<?php


class SCR_ITJ_Ajax {

	protected static $initd = false;

	/**
	 * init pluggable functions.
	 *
	 * @return  void
	 */
	public static function init() {

		if ( self::$initd ) {
			return;
		}
		add_action( 'wp_ajax_wce_change_status_via_ajax', array( __CLASS__, 'change_status_via_ajax' ) );
		add_action( 'wp_ajax_wccs_change_status_via_ajax', array( __CLASS__, 'change_status_wccs_via_ajax' ) );//Scheduler an oder aus
		add_action( 'wp_ajax_wccs_save_emailfrom_via_ajax', array( __CLASS__, 'save_emailfrom_via_ajax' ) );//save email from value
		add_action( 'wp_ajax_wccs_send_test_email_via_ajax', array( __CLASS__, 'sendTestEmail' ) ); //Sende test email button
		add_action( 'wccs_send_test_email', array( __CLASS__, 'test_cart_reminder_email' ) );

		// State that initialization completed.
		self::$initd = true;
	}

	//Empfange ajax button von jquery
	public static function sendTestEmail() {

		$wcce_testemail = isset($_POST['wcce_testemail']) ? sanitize_email($_POST['wcce_testemail']) : '';
		$post_id = isset($_POST['post_id']) ? absint( $_POST['post_id'] ) : 0;
		$success = false;

		//Check Postid
		if ($post_id != 0) {
			$success = self::test_cart_reminder_email($wcce_testemail, $post_id);
	  }
		//Check function succes
		if($success) {
			$result = __("Test email was send to: ",'scrlang').$wcce_testemail;
			update_post_meta( $post_id, 'wcce_test_email', $wcce_testemail ); //Save test Email
		} else {
			$result = __("Error: Test email can't send to",'scrlang').$wcce_testemail.__(", please check your input or mail server. ",'scrlang');
		}
		wp_send_json_success($result );
	}

	public static function save_emailfrom_via_ajax() {
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$wcce_email_from = isset( $_POST['wcce_email_from'] ) ?  sanitize_email($_POST['wcce_email_from']) : 'info@example.com';
		$result  = update_post_meta( $post_id, 'wcce_email_from', $wcce_email_from );
		wp_send_json_success($result );
	}

	public static function change_status_via_ajax() {
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$checked = isset( $_POST['checked'] ) ? sanitize_text_field( $_POST['checked'] ) : '';
		$result  = update_post_meta( $post_id, 'wce_enable', $checked );
		wp_send_json_success( $result );
	}
	public static function change_status_wccs_via_ajax() {
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$checked = isset( $_POST['checked'] ) ? sanitize_text_field( $_POST['checked'] ) : '';
		$result  = update_post_meta( $post_id, 'wccs_enable', $checked );
		wp_send_json_success( $result );
	}
	public static function change_email_from() {
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$checked = isset( $_POST['checked'] ) ? sanitize_text_field( $_POST['checked'] ) : '';
		$result  = update_post_meta( $post_id, 'email_from', $checked );
		wp_send_json_success( $result );
	}

	public static function test_cart_reminder_email($testemailaddress, $post_id) {
				//$wceenabled = get_post_meta( $post_id, 'wce_enable', true );
				$template_header = get_post( $post_id);
				$template_header = $template_header->post_content;
				$template_body = get_post_meta( $post_id, 'body_content', true );
				$template_subject = get_post_meta( $post_id, 'SCR_reminder_subject', true );

				$$templateHTML = "";

				//Verkettung
				$templateHTML = $templateHTML.$template_header;

				$wkUrl = wc_get_cart_url();
				$sitelogo = get_custom_logo();

				$templateHTML = str_replace("[SITE_LOGO]", $sitelogo, $templateHTML);
				$templateHTML = str_replace("[CART_URL]", $wkUrl, $templateHTML);

				$args = array(
				    'limit' => 1,
				    'orderby'  => 'name'
				);

				$product = wc_get_products($args);
				$product = $product[0];

				$pName = $product->get_name();
				$pUrl = get_permalink($product->get_id());
				$pStatus = $product->get_status();
				$pPrice = $product->get_price().get_woocommerce_currency_symbol();
				$pImage = $product->get_image();

				$rep_body = str_replace("[PRODUCT_IMAGE]", $pImage, $template_body);
				$rep_body = str_replace("[PRODUCT_URL]", $pUrl, $rep_body);
				$rep_body = str_replace("[PRODUCT_NAME]", $pName, $rep_body);
				$rep_body = str_replace("[PRODUCT_PRICE]", $pPrice, $rep_body);
				$rep_body = str_replace("[CART_URL]", $wkUrl, $rep_body);
				$rep_body = str_replace("[SITE_LOGO]", $sitelogo, $rep_body);

				$templateHTML = str_replace("[CART_PRODUCTS]", $rep_body, $templateHTML);

				$to = $testemailaddress;
				$subject = $template_subject;
				$body = $templateHTML;

				$from = get_post_meta( $post_id, 'wcce_email_from', true );
				//Split Email for email Header
				$email_heading = SCR_ITJ_Meta_Post_Editor::SplitEmailAddress($from);
				$from = ucfirst($email_heading["user"]).' <'.$from.'>';

				if(empty($from)) {
					$from = "example <cart@reminder.com";
				}
				$headers[] = 'From: '.$from;
				$headers[] = 'Content-Type: text/html; charset=UTF-8';

				//Übergebe Template an Email funktion und sende an Person die Email
				return wp_mail( $to, $subject, $body, $headers );
	}
}
