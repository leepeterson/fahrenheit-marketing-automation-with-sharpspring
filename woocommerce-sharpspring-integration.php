<?php
/**
 * Plugin Name: WooCommerce SharpSpring Integration
 * Plugin URI: https://www.fahrenheit.io/
 * Description: Add WooCommerce customers as SharpSpring leads
 * Version: 1.0.0
 * Author: Fahrenheit Marketing
 * Author URI: https://www.fahrenheit.io/
 * Requires at least: 4.6
 * Tested up to: 4.6
 *
 * Text Domain: humann
 * Domain Path: /i18n/languages/
 *
 * @package WooCommerce SharpSpring
 * @category Core
 * @author Fahrenheit Marketing
 */
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

require(__DIR__ . "/includes/config.php");

class WC_SS_Plugin {

  private static $debug = false;
  private static $params;

  public static function get_options() {
    $config = WC_SS_Plugin_Config::get_instance();
    return $config->get_options();
  }

  public function __construct() {
    $config = WC_SS_Plugin_Config::get_instance();
    self::$params = $config->get_options();
    add_action('woocommerce_order_action_send_lead_to_sharpspring', array( $this,'send_lead_to_sharpspring'));
    add_filter('woocommerce_order_actions',  array( $this,'order_actions'), 10, 1);
  }

  public function send_lead_to_sharpspring($order) {
    $meta = get_post_meta($order->id);
    $lead = array(
      "first" => $meta["_billing_first_name"][0],
      "last" => $meta["_billing_last_name"][0],
      "email" => $meta["_billing_email"][0]
    );
    $order->add_order_note('Attempting to send lead to SharpSpring: ' . join(',', $lead));
  }

  public function order_actions($actions) {
    $actions['send_lead_to_sharpspring'] = __("Send lead to SharpSpring", 'woocommerce-sharpspring');
    return $actions;
  }
}

new WC_SS_Plugin();

