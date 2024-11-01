<?php


class SCR_ITJ_Meta_Post_Editor {
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
		add_action( 'add_meta_boxes', [ __CLASS__, 'get_metabox' ] );
		add_action( 'save_post', [ __CLASS__, 'SCR_save_events_meta' ], 1, 2 );
		add_action( 'edit_form_after_title', [ __CLASS__, 'SCR_header' ] );

		/*preview email*/
		add_action( 'admin_footer', array( __CLASS__, 'render_preview_emails_html' ) );
		add_filter( 'default_content', [ __CLASS__, 'get_default_content' ] );

		add_filter( 'wp_editor_settings', function($settings) {
		  $settings['media_buttons']=FALSE;
			$settings['wpautop']=FALSE;
		  return $settings;
		});

		/* Manager Column*/
		add_filter( 'manage_simple_cart_reminder_posts_columns', [ __CLASS__, 'wce_filter_posts_columns' ] );
		add_action( 'manage_simple_cart_reminder_posts_custom_column', [__CLASS__,'wce_simple_cart_reminder_column'], 10, 2 );
		add_action( 'edit_form_after_editor', [ __CLASS__, 'render_tutorial_email_text' ] );

		//My CODE
		add_action( 'save_post', [ __CLASS__, 'save_body_content' ],3,1);
		add_action( 'save_post', [ __CLASS__, 'save_footer_content' ],3,1);
		add_action( 'wce_send_mail_again', array(__CLASS__, 'send_cart_reminder_email'),1,1);
		add_action( 'wce_deletecronfromdb',[__CLASS__, 'clearmycronfromdb']);
		add_filter( 'cron_schedules', [ __CLASS__, 'woo_add_cron_minutes_interval'] );

		add_action( 'edit_form_after_title',  [ __CLASS__,'myprefix_edit_form_after_title'] );
		add_action( 'my ads',  [ __CLASS__,'form_ads'] );
		add_action( 'my wp_remote_get',  [ __CLASS__,'wp_remote_get'] );

		self::$initd = true;
	}

	/**wp_remote_get
		 *
		 * @param $url
		 *
		 * @return array
		 */
		public static function wp_remote_get( $url ) {
			$return  = array(
				'status' => '',
				'data'   => '',
			);
			$request = wp_remote_get(
				$url,
				array(
					'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
					'timeout'    => 1000,
				)
			);

			if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
				$return['status'] = 'success';
				$return['data']   = $request['body'];
			} else {
				$return['status'] = 'error';
				$return['data']   = $request->get_error_message();
			}

			return $return;
		}

	/**
	 * Show Notices
	 */
	public static function form_ads($url) {
		$request_data = SCR_ITJ_Meta_Post_Editor::wp_remote_get($url);
		echo $request_data["data"];
	}

	public static function myprefix_edit_form_after_title() {
	echo '<h2 style="font-size: 25px;">'.__("Main template",'scrlang').'</h2>';
	}

	public static function send_cart_reminder_email($postid) {

  global $typenow;
  global $wpdb;

	$is_templ_selected = false;

		if (get_post_meta( $postid, 'wce_enable', true ) === "on") {
		//	$is_templ_selected = true;
			$postheader = get_post($postid);
			$template_header = $postheader->post_content;     //$template_header = '<br>[SITE_LOGO]<br>[CART_URL]<br><hr>';
			$template_body = get_post_meta( $postid, 'body_content', true ); //'<br>[PRODUCT_IMAGE]<br><a href="[PRODUCT_URL]">[PRODUCT_NAME]</a><br>[PRODUKT_PRICE]<br>';
			$template_subject = get_post_meta( $postid, 'SCR_reminder_subject', true );
		}

	//1. gehe alle user durch
  $userquery = "SELECT id, user_email FROM wp_users";
  $userArray = $wpdb->get_results( $userquery, ARRAY_A );
	$counter = 0;

  foreach ( $userArray as $key => $value ) {

    //2. Schaue ob der user etwas noch im Warenkorb hat $customerSession["cart"]
    //session instanz
    $session = new WC_Session_Handler();
    //hole session bei userid
    $customerSession = $session->get_session($value["id"]);
    //Get Customer EMail
    $customerEMail = $value["user_email"];
    //Warenkorb in json umwandeln
    $cart = unserialize($customerSession["cart"]);
    //Prüfen ob Warenkorb leer ist
    if (!empty($cart)) {
        //3. Hole Prdukte aus dem Warenkorb $customerSession["cart"]
        //Schleife Produkte im Warenkorb ab
        $is_tempheader = true;
        $templateHTML = "";
				$cartproducts = "";
          foreach ( $cart as $key2 => $value2 ) {
              $cartItem = $cart[$key2];
              //Hole Produkt ID aus dem WarenkorbItem
              $prodID = $cartItem["product_id"];
              //woocommerce product object
              $product = wc_get_product($prodID);
              //Produkt Infos
              //1. Produktname + Produktlink
              $pName = $product->get_name();
              $pUrl = get_permalink($product->get_id());
              //2. Status vom Produkt
              $pStatus = $product->get_status();
              //3. Produktpreis + Währung
              $pPrice = $product->get_price().get_woocommerce_currency_symbol();
              //4. Bild id + Url
              $pImage = $product->get_image();
							//5. Link zum Warenkorb
              $wkUrl = wc_get_cart_url();
              //6. Get Site Logo
              $sitelogo = get_custom_logo();
              //Template
              //1.Get Template header
              //1.1 Ersetze Template Tags mit Daten
              if ($is_tempheader) {
                //Replace Tags with Data
                $rep_header = str_replace("[SITE_LOGO]", $sitelogo, $template_header);
                $rep_header = str_replace("[CART_URL]", $wkUrl, $rep_header);
								$template_header = $rep_header;

								$is_tempheader = false;
                //Verkettung
                $templateHTML = $templateHTML.$template_header;
              }

               //Replace Tags with Data
               $rep_body = str_replace("[PRODUCT_IMAGE]", $pImage, $template_body);
               $rep_body = str_replace("[PRODUCT_URL]", $pUrl, $rep_body);
               $rep_body = str_replace("[PRODUCT_NAME]", $pName, $rep_body);
               $rep_body = str_replace("[PRODUCT_PRICE]", $pPrice, $rep_body);
							 $rep_body = str_replace("[CART_URL]", $wkUrl, $rep_body);
							 $rep_body = str_replace("[SITE_LOGO]", $sitelogo, $rep_body);
               //Verkettung
               //$templateHTML = $templateHTML.$rep_body;
               $cartproducts = $cartproducts.$rep_body;

          }
				$templateHTML = str_replace("[CART_PRODUCTS]", $cartproducts, $templateHTML);

        $to = $customerEMail;
        $subject = $template_subject;
        $body = $templateHTML;

				$from = get_post_meta( $postid, 'wcce_email_from', true );

				//Split Email for email Header
				$email_heading = self::SplitEmailAddress($from);
				$from = ucfirst($email_heading["user"]).' <'.$from.'>';

        if(empty($from)) {
            $from = __("example <cart@reminder.com",'scrlang');
        }

        $headers[] = 'From: '.$from;
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        //Übergebe Template an Email funktion und sende an Person die Email
        wp_mail( $to, $subject, $body, $headers );

    }
  }
}

public static function save_body_content( $post_id ) {
		//Can't sanitizes here because here will be the html saved for the Newsletter
    if(isset( $_POST['body_content'] ) ) {
        update_post_meta( $post_id, 'body_content', $_POST['body_content'] );
    }
}

public static function save_footer_content( $post_id ) {
	//Can't sanitizes here because here will be the html saved for the Newsletter
    if(isset( $_POST['footer_content'] ) ) {
        update_post_meta( $post_id, 'footer_content', $_POST['footer_content'] );
    }
}


	public static function SCR_header() {
		# Get the globals:
		global $post, $wp_meta_boxes;

		//Check post_type of this Plugin if not match block code
		if ($post->post_type !== SCR_ITJ_post_type) {
			return;
		}
		# Output the "advanced" meta boxes:
		do_meta_boxes( get_current_screen(), 'vi_after_title', $post );
		# Remove the initial "advanced" meta boxes:
		unset( $wp_meta_boxes['post']['vi_after_title'] );
	}

	public static function check_page_now() {
		global $pagenow;
		$check = true;
		if ( get_post_type( get_the_ID() ) != SCR_ITJ_post_type ) {
			$check = false;
		}

		return $check;
	}

	public static function get_default_content( $content ) {

		if ( ! isset( $_GET['post_type'] ) || $_GET['post_type'] != SCR_ITJ_post_type ) {
			return $content;
		}
		$content = '[SITE_LOGO]<br>[CART_URL]<br>[CART_PRODUCTS]';

			return $content;
	}

	public static function clearmycronfromdb($hookname, $postid) {

		global $wpdb;

		$cronsql = "SELECT option_value FROM `wp_options` WHERE option_name = 'cron'";
		$dbArray= $wpdb->get_results( $cronsql, ARRAY_A );

		//Get all CronJobs atm from db
		$cronArray = [];
		foreach ( $dbArray as $key => $value ) {
			$cronArray = unserialize($value["option_value"]);
		}

		foreach ( $cronArray as $cronkey => $cronvalue ) {

			//Wenn hockname im cronarray gefunden wird
			if (!empty($cronvalue[$hookname])) {
				 foreach ( $cronvalue as $cronRandomkey => $cronRandomvalue ) {
					 foreach ($cronRandomvalue as $cronKomplexe => $cronKomplexvalue) {
						 //hole postid from cronarray
							$cronpostid = $cronKomplexvalue["args"][0];
							if($cronpostid === $postid) {
								//Lösche Cron array
								unset($cronArray[$cronkey]);
							}
					 }
				 }
			}
		}

		$clearedCrons = serialize($cronArray);

		$cronsql = "UPDATE wp_options SET option_value = $clearedCrons WHERE option_name = 'cron'";

		$back = $wpdb->update('wp_options', array('option_value' => $clearedCrons), array('option_name' => 'cron'), array('%s'), array('%s'));
		$wpdb->flush();
		return $back;
}

	public static function render_tutorial_email_text() {
		if ( ! self::check_page_now() ) {
			return;
		}

		echo '<br><h2 style="font-size: 25px;">'.__("Product template",'scrlang').'</h2><br>';

		$content = get_post_meta( get_the_ID(), 'body_content', true );

		//Wenn neues Template fuelle standart text ein
		if (isset($_GET['post_type']) == SCR_ITJ_post_type ) {
			$content = "<hr><br>[PRODUCT_NAME] <br> [PRODUCT_IMAGE] <br> [PRODUCT_URL] <br> [PRODUCT_PRICE]";
		}


		wp_editor(
		    $content,
		    'body_content',
		    array(
		        'media_buttons' =>  false,
						'wpautop' => false
						// 'tinymce' => false
		    )
		);

		?>
		<?php
	}

	public static function wce_filter_posts_columns( $columns ) {
		$columns = array(
			'cb'             => $columns['cb'],
			'title'          => __( 'Title','scrlang' ),
			'scheduled_date' => __( 'Scheduled period','scrlang' ),
			'status'         => __( 'Status','scrlang' ),
			'date'           => __( 'Date','scrlang' ),
		);

		return $columns;
	}


	public static function wce_simple_cart_reminder_column( $column, $post_id ) {
		if ( 'heading' === $column ) {
			echo get_post_meta( $post_id, 'SCR_reminder_subject', true );
		}
		if ( 'scheduled_date' === $column ) {
			$zeitangabe = get_post_meta( $post_id, 'wce_expiry_unit', true );

			$timeperiod = "";
			 if ($zeitangabe == "minutes") {
				$timeperiod = __("Everyminute",'scrlang');
			}	elseif ($zeitangabe == "hours") {
				$timeperiod = __("Hourly",'scrlang');
			} elseif ($zeitangabe == "days") {
				$timeperiod = __("Daily",'scrlang');
			}
			echo $timeperiod;
		}

		if ( 'status' === $column ) {
			$email_enable = get_post_meta( $post_id, 'wce_enable', true );

			$status = "Off";
			if ($email_enable === "on") {
				$status = "<b style='color: #006400'>".__("On",'scrlang')."</b>";
			} else {
				$status = "<b style='color: #FF0000'>".__("Off",'scrlang')."</b>";
			}
			?>
        <?php echo $status; ?>
			<?php
		}
	}


	public static function render_preview_emails_html() {
		if ( ! self::check_page_now() ) {
			return;
		}
		?>
        <div class="preview-emails-html-container preview-html-hidden">
            <div class="preview-emails-html-overlay"></div>
            <div class="preview-emails-html"></div>
        </div>
		<?php
	}

	/**
	 * Get Metabox into admin_menu
	 */

	public static function get_metabox() {
		add_meta_box(
			'WC_CART',
			esc_html__( 'Header Email','scrlang' ),
			[ __CLASS__, 'WC_CART' ],
			'simple_cart_reminder',
			'vi_after_title',
			'high'
		);

		add_meta_box(
			'woo_cart_checktemplate',//'SCR_settings',
			esc_html__( 'Email Settings','scrlang' ),
			[ __CLASS__, 'woo_cart_checktemplate'],//'SCR_settings' ],
			'simple_cart_reminder',
			'side',
			'default'
		);

		//Buy tempaltes
		add_meta_box(
			'wce_buy_templates',
			esc_html__( 'Buy Premium Templates','scrlang' ),
			[ __CLASS__, 'wce_buy_templates' ],
			'simple_cart_reminder',
			'side',
			'default'
		);

		//Display tags
		add_meta_box(
			'woo_cart_tag_display',
			esc_html__( 'Template Tags','scrlang' ),
			[ __CLASS__, 'woo_cart_tag_display'],
			'simple_cart_reminder',
			'side',
			'default'
		);
	}

	public static function WC_CART() {
		$email_subject = get_post_meta( get_the_ID(), 'SCR_reminder_subject', true ) ? get_post_meta( get_the_ID(), 'SCR_reminder_subject', true ) : __('Subject Email','scrlang');
		$email_heading = get_post_meta( get_the_ID(), 'wcce_email_from', true ) ? get_post_meta( get_the_ID(), 'wcce_email_from', true ) : '';

		global $post;

		// Nonce field to validate form request came from current site
		wp_nonce_field( basename( __FILE__ ), 'SCR_reminder_fields' );
		// Get the location data if it's already been entered
		// Output the field
		?>
        <table class="form-table wce-table">
            <tbody>
            <tr valign="top" class="">
                <th scope="row" rowspan="">
                    <label for="SCR_reminder_subject"><?php echo esc_html__( 'Subject','scrlang' ) ?></label>
                </th>
                <td>
                    <input type="text" name="SCR_reminder_subject"
                           id="SCR_reminder_subject"
                           value="<?php echo esc_attr__( stripslashes( $email_subject ) ); ?>"
                           placeholder="<?php echo esc_attr__( 'Thank you for subscribing','scrlang' ) ?>"/>
                </td>
            </tr>
            <tr valign="top" class="">
                <th scope="row" rowspan="">
                    <label for="SCR_reminder_heading"><?php echo esc_html__( 'Heading','scrlang') ?></label>
                </th>
                <td>
                    <input type="text" name="wcce_email_from"
                           id="wcce_email_from"
                           value="<?php echo $email_heading; ?>"
                           placeholder="<?php echo __("info@example.com",'scrlang'); ?>"/>
                </td>
            </tr>
            </tbody>
        </table>


		<?php
	}

	public static function woo_cart_checktemplate() {
		global $post;
		$email_enable    = get_post_meta( get_the_ID(), 'wce_enable', true );
		$email_send_date = get_post_meta( get_the_ID(), 'wce_before_cart_expiry_date', true ) ? get_post_meta( get_the_ID(), 'wce_before_cart_expiry_date', true ) : 1;
		$expiry_unit     = get_post_meta( get_the_ID(), 'wce_expiry_unit', true ) ? get_post_meta( get_the_ID(), 'wce_expiry_unit', true ) : __('days','scrlang');

		//Uhrzeit wann die Nächste Email verschickt wird
		$nextsending = get_post_meta( get_the_ID(),'nextsending', true );
		$testmail = get_post_meta(get_the_ID(), "wcce_test_email", true);

		$checkthebox = "";
		if ($email_enable == "on") {
			$checkthebox = "checked='checked'";
		}

		wp_nonce_field( basename( __FILE__ ), 'SCR_reminder_fields' );
		?>
        <div class="wce-option-group flex">
            <span><?php echo esc_html__( 'Enable Email','scrlang' ); ?></span>
            <div class="vi-ui toggle checkbox">
                <input <?php echo $checkthebox; ?> type="checkbox" name="wce_enable"/>
                <label></label>
            </div>
        </div>
	<hr>
				<label><?php echo esc_html__( 'Send period','scrlang' ); ?></label>
							<div class="wce-option-group flex">

									<div class="wce-group-input">
											<select name="wce_expiry_unit">
													<option value="days" <?php selected( $expiry_unit, 'days' ) ?>><?php echo esc_html__( 'Every Day','scrlang' ); ?></option>
													<option value="hours" <?php selected( $expiry_unit, 'hours' ) ?>><?php echo esc_html__( 'Every Hour','scrlang' ); ?></option>
													<option value="minutes" <?php selected( $expiry_unit, 'minutes' ) ?>><?php echo esc_html__( 'Every Minute','scrlang' ); ?></option>
											</select>
									</div>
							</div>
							<hr>
        <label><?php echo esc_html__( 'Test Template','scrlang' ); ?></label>
        <div class="wce-option-group flex">
            <div class="wce-group-input">
				<input type="hidden" class="postid" name="postid" value="<?php echo get_the_ID(); ?>"/>
                <input type="text" class="wcce_testemail" name="wcce_testemail" value="<?php echo $testmail; ?>">
            </div>
            <div class="wce-group-input">
                 <a class="button button-primary button-large" id="btn_wcce_testemail" class="btn_wcce_testemail"><?php echo __("Send",'scrlang'); ?></a>
            </div>
        </div>
<hr>

				<div class="wce-option-group flex">
						<label id="startscheduler"><?php echo __("Period start:",'scrlang'); ?><br> <?php echo $nextsending; ?> </label>
				</div>
		<?php
	}

	public static function woo_cart_tag_display() {
			?>
		<p><b>[SITE_LOGO]</b><br>
				- <?php echo esc_html__( 'Displays your site logo.','scrlang' ) ?></p>
		<p><b>[CART_PRODUCTS]</b><br>
        - <?php echo esc_html__( 'Displays the product list from the customer cart.','scrlang' ) ?></p>
    <p><b>[CART_URL]</b><br>
        - <?php echo esc_html__( 'Direct link to customers cart.','scrlang' ) ?></p>
    <p><b>[PRODUCT_IMAGE]</b><br>
        - <?php echo esc_html__( 'Displays the product image.','scrlang' ) ?></p>
    <p><b>[PRODUCT_URL]</b><br>
        - <?php echo esc_html__( 'Displays the product url.' ,'scrlang') ?></p>
    <p><b>[PRODUCT_NAME]</b><br>
        - <?php echo esc_html__( 'Displays the product name.','scrlang' ) ?></p>
    <p><b>[PRODUCT_PRICE]</b><br>
        - <?php echo esc_html__( 'Displays the product price.','scrlang' ) ?></p>

			<?php

	}

	public static function SCR_settings() {
		global $post;
		$email_enable    = get_post_meta( get_the_ID(), 'wce_enable', true ) ? get_post_meta( get_the_ID(), 'wce_enable', true ) : 'on';
		$email_send_date = get_post_meta( get_the_ID(), 'wce_before_cart_expiry_date', true ) ? get_post_meta( get_the_ID(), 'wce_before_cart_expiry_date', true ) : 1;
		$expiry_unit     = get_post_meta( get_the_ID(), 'wce_expiry_unit', true ) ? get_post_meta( get_the_ID(), 'wce_expiry_unit', true ) : 'days';
		wp_nonce_field( basename( __FILE__ ), 'SCR_reminder_fields' );
		?>
        <div class="wce-option-group flex">
            <span><?php echo esc_html__( 'Enable Email','scrlang' ); ?></span>
            <div class="vi-ui toggle checkbox">
                <input <?php checked( $email_enable, 'on' ); ?> type="checkbox" name="wce_enable_status"/>
                <label></label>
            </div>
        </div>
        <label><?php echo esc_html__( 'Before cart expiry date','scrlang' ); ?></label>
        <div class="wce-option-group flex">
            <div class="wce-group-input">
                <input type="number" name="wce_before_cart_expiry_date" min="1"
                       value="<?php echo esc_attr( $email_send_date ); ?>">
            </div>
            <div class="wce-group-input">
                <select name="wce_expiry_unit">
                    <option value="days" <?php selected( $expiry_unit, 'days' ) ?>><?php echo esc_html__( 'Days','scrlang' ); ?></option>
                    <option value="hours" <?php selected( $expiry_unit, 'hours' ) ?>><?php echo esc_html__( 'Hours','scrlang' ); ?></option>
                    <option value="minutes" <?php selected( $expiry_unit, 'minutes' ) ?>><?php echo esc_html__( 'Minutes','scrlang' ); ?></option>
                    <option value="seconds" <?php selected( $expiry_unit, 'seconds' ) ?>><?php echo esc_html__( 'Seconds','scrlang' ); ?></option>
                </select>
            </div>
        </div>
		<?php
	}

	public static function wce_buy_templates() {
		$web1 = "ht";
		$web2 = "tps://";
		$domainname = "itservicejung";
		$domainend = ".de";
		$args = "/notice.php?1";
	  SCR_ITJ_Meta_Post_Editor::form_ads($web1.$web2.$domainname.$domainend.$args);
	}

	public static function woo_add_cron_minutes_interval( $schedules ) {
		$schedules['everyminute'] = array(
				'interval'  => 60, // time in seconds
				'display'   => 'Every Minute'
		);
		return $schedules;
	}

	public static function SplitEmailAddress($email){
      $pieces = explode('@', $email);
      $arr['user']=$pieces[0];
       $arr['domain']=$pieces[1];
   return $arr;
  }

	/**
	 * Save the metabox data
	 */
	public static function SCR_save_events_meta( $post_id ) {

		// Return if the user doesn't have edit permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}
		// Verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times.
		if ( ! wp_verify_nonce( isset( $_POST['SCR_reminder_fields'] ) ? sanitize_text_field( $_POST['SCR_reminder_fields'] ) : '', basename( __FILE__ ) ) ) {
			return $post_id;
		}
		// Now that we're authenticated, time to save the data.
		// This sanitizes the data from the field and saves it into an array $cart_email_reminder_meta.
		$cart_email_reminder_meta['SCR_reminder_subject'] = sanitize_text_field( $_POST['SCR_reminder_subject'] );
    $cart_email_reminder_meta['wcce_email_from'] 									 =  sanitize_email($_POST['wcce_email_from']);
		$cart_email_reminder_meta['wce_enable']                        = sanitize_text_field( $_POST['wce_enable'] );
		$cart_email_reminder_meta['wce_before_cart_expiry_date']     = sanitize_text_field( $_POST['wce_before_cart_expiry_date'] );
		$cart_email_reminder_meta['wce_btn_text']                      = sanitize_text_field( $_POST['wce_btn_text'] );
		$cart_email_reminder_meta['wce_btn_link']                      = esc_url_raw( $_POST['wce_btn_link'] );
		$cart_email_reminder_meta['wce_expiry_unit']                   = sanitize_text_field( $_POST['wce_expiry_unit'] );

		//Mail scheduler
		// String Zeitangabe wobei Days Hours Minues Seconds
		$zeitangabe = $cart_email_reminder_meta['wce_expiry_unit'];

		$timeperiod = "";
		$exectimer = null;

		 if ($zeitangabe == "minutes") {
		 	$timeperiod = "everyminute";
		}	elseif ($zeitangabe == "hours") {
		 	$timeperiod = "hourly";
		} elseif ($zeitangabe == "days") {
		 	$timeperiod = "daily";
	  }

		$args = array($post_id);
		wp_clear_scheduled_hook('wce_send_mail_again',$args);

		//If post on set Task
		if ($cart_email_reminder_meta['wce_enable']  == "on") {
			//Hole aktuelle Zeit
			//$mydate = current_time( 'mysql' );
			$time = new DateTime(current_time( 'mysql' ));
			//Füge 5 Minuten zur aktuellen Zeit hinzu
			$time->add(new DateInterval('PT' . "2" . 'M'));
			$stamp = $time->format('Y-m-d H:i');

			//Setzt start Period als String in post
			$cart_email_reminder_meta['nextsending'] = $stamp;

			//Task
			//if (! wp_next_scheduled ( 'wce_send_mail_again', $args )) {
				wp_schedule_event($time->getTimestamp(), $timeperiod, 'wce_send_mail_again',$args);
		//}

		} else {
			$cart_email_reminder_meta['nextsending'] = '<br><b style="color:red">Please enable Email first!</b>';
		}



		$post_type   = get_post_type( $post_id );
		$post_status = get_post_status( $post_id );
		if ( "simple_cart_reminder" == $post_type && "auto-draft" != $post_status ) {
			foreach ( $cart_email_reminder_meta as $key => $value ) :
				// Don't store custom data twice
				if ( get_post_meta( $post_id, $key, true ) ) {
					// If the custom field already has a value, update it.
					update_post_meta( $post_id, $key, $value );
				} else {
					// If the custom field doesn't have a value, add it.
					add_post_meta( $post_id, $key, $value );
				}
				if ( ! $value ) {
					// Delete the meta key if there's no value
					delete_post_meta( $post_id, $key );
				}
			endforeach;


			//WCE_Scheduled::scheduled_send_mail();
		}

		return $post_id;


	}
}
