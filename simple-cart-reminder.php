<?php
/**
 * Plugin Name: Simple Cart Reminder for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/simple-cart-reminder/
 * Description: Create templates and schedule your cart reminder emails
 * Version: 1.0.5
 * Author: ITServiceJung
 * Author URI: http://itservicejung.de
 * Text Domain: scrlang
 * Domain Path: /languages
 * License: GPL-2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Tested up to: 5.5.1
 */

 /*
 Copyright 2020 itservicejung.de

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */


if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!function_exists('is_plugin_active')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-includes/default-constants.php');

Class SCR_ITJ_Reminder
{

    public static function init()
    {
        self::SCR_ITJ_define();
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array(__CLASS__, 'SCR_ITJ_woocommerce_admin_notice'));
            return;
        }

        // Register class autoloader.
        spl_autoload_register(array(__CLASS__, 'SCR_ITJ_autoload'));

        // Load text
      //  load_plugin_textdomain('scrlang', false, SCR_ITJ_DIR . 'languages/');


        add_action('admin_enqueue_scripts', array(__CLASS__, 'SCR_ITJ_scripts'), 99);

        // Includes
        SCR_ITJ_Plugin_Info::init();
        SCR_ITJ_Ajax::init();
        SCR_ITJ_Dashboard::init();
        SCR_ITJ_Meta_Post_Editor::init();

        add_filter('plugin_action_links_' . SCR_ITJ_PLUGIN_BASENAME, array(
            __CLASS__,
            'SCR_ITJ_plugin_action_links'
        ));
    }

    public static function SCR_ITJ_plugin_action_links($links)
    {
        $action_links = array(
            'settings' => '<a href="post-new.php?post_type=simple_cart_reminder">' . esc_html__('Add New Reminder', 'add-new-reminder') . '</a>',
        );

        return array_merge($action_links, $links);
    }

    /**
     *  Admin scripts
     */
    public static function SCR_ITJ_scripts()
    {

        $post_type = get_post_type();
        if (!is_customize_preview() && $post_type === 'simple_cart_reminder') {
            self::SCR_ITJ_delete_script();
        }
        // style
        wp_enqueue_style('woo-cart-email-checkbox', SCR_ITJ_CSS . 'semantic/checkbox.min.css');
        wp_enqueue_style('woo-cart-email-reminders-style', SCR_ITJ_CSS . 'styles.css');

        //script
        wp_enqueue_script('woo-cart-email-checkbox', SCR_ITJ_JS . 'libs/checkbox.min.js', array('jquery'), SCR_ITJ_VERSION);

        wp_enqueue_script('woo-cart-email-reminders-scripts', SCR_ITJ_JS . 'js-scripts.js', array('jquery'), SCR_ITJ_VERSION);
        wp_localize_script('woo-cart-email-reminders-scripts', 'WC_CART', array(
            'adminUrl' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('SCR_none')
        ));

    }

    public static function SCR_ITJ_delete_script()
    {
        global $wp_scripts;
        $scripts = $wp_scripts->registered;
        foreach ($scripts as $k => $script) {
            preg_match('/^\/wp-/i', $script->src, $result);
            if (count(array_filter($result)) < 1) {
                if ($script->handle != 'query-monitor') {
                    wp_dequeue_script($script->handle);
                } //delete script not belong to wp
            }
        }
    }

    public static function SCR_ITJ_autoload($class_name)
    {
        // Generate file path from class name.
        $exists = array(
            'Ajax',
            'Plugin_Info',
            'Meta_Post_Editor'
        );
        $backend_files = array(
            'Dashboard'
        );

        foreach ($exists as $exist) {
            include_once SCR_ITJ_HELPERS . $exist . '.php';
        }
        foreach ($backend_files as $backend_file) {
            include_once SCR_ITJ_BACKEND . $backend_file . '.php';
        }
    }


    public static function SCR_ITJ_define()
    {
        define('SCR_ITJ_VERSION', '1.0.5');
        define('SCR_ITJ_plugin_name', 'Simple Cart Reminder for WooCommerce');
        define('SCR_ITJ_post_type', 'simple_cart_reminder');
        define('SCR_ITJ_DIR',plugin_dir_path( __FILE__ ));
        define('SCR_ITJ_PLUGIN_BASENAME', plugin_basename(__FILE__));
        define('SCR_ITJ_LANGUAGES', SCR_ITJ_DIR . "languages" . DIRECTORY_SEPARATOR);
        define('SCR_ITJ_INCLUDES', SCR_ITJ_DIR . "includes" . DIRECTORY_SEPARATOR);
        define('SCR_ITJ_BACKEND', SCR_ITJ_INCLUDES . "backend" . DIRECTORY_SEPARATOR);
        define('SCR_ITJ_HELPERS', SCR_ITJ_INCLUDES . "helpers" . DIRECTORY_SEPARATOR);
        $plugin_url = plugins_url('', __FILE__);
        $plugin_url = str_replace('/includes', '', $plugin_url);
        define('SCR_ITJ_CSS', $plugin_url . "/assets/css/");
        define('SCR_ITJ_CSS_DIR', SCR_ITJ_DIR . "css" . DIRECTORY_SEPARATOR);
        define('SCR_ITJ_JS', $plugin_url . "/assets/js/");
        define('SCR_ITJ_JS_DIR', SCR_ITJ_DIR . "js" . DIRECTORY_SEPARATOR);
        define('SCR_ITJ_IMAGES', $plugin_url . "/assets/images/");
    }

    public static function SCR_ITJ_woocommerce_admin_notice()
    {
        ?>
        <div class="error">
            <p><?php _e('Cart Reminder for WooCommerce is enabled and stays in standby. It requires WooCommerce in order to work.', 'scrlang'); ?></p>
        </div>
        <?php
    }
}

if (!function_exists('wce_reminder_loaded')) {
    function wce_reminder_loaded()
    {
        SCR_ITJ_Reminder::init();

    }
}

add_action( 'plugins_loaded','SCR_ITJ_load_plugin_textdomain',0 );
function SCR_ITJ_load_plugin_textdomain() {
  load_plugin_textdomain( 'scrlang', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}

add_action('plugins_loaded', 'wce_reminder_loaded', 11);
