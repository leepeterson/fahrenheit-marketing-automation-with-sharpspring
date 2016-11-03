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
    if (!(isset(self::$params["sharpspring_api_key"]) &&
          isset(self::$params["sharpspring_secret_key"]) &&
          isset(self::$params["sharpspring_domain"]) &&
          isset(self::$params["sharpspring_account"])
    )){
      return; 
    }
    add_action('woocommerce_order_action_send_lead_to_sharpspring', array( $this,'send_lead_to_sharpspring'));
    add_filter('woocommerce_order_actions',  array( $this,'order_actions'), 10, 1);
    add_action('add_meta_boxes', array( $this,'order_metabox'));
    add_action('woocommerce_cart_loaded_from_session', array( $this, 'page_tracking' ), 10);
    add_action('woocommerce_cart_loaded_from_session', array( $this, 'shopping_cart_tracking' ), 10);
    add_action('woocommerce_thankyou', array( $this, 'order_tracking' ), 10, 1);
  }

  public function send_lead_to_sharpspring($order) {
    $meta = get_post_meta($order->id);

    $lead = array(
      "firstName" => $meta["_billing_first_name"][0],
      "lastName" => $meta["_billing_last_name"][0],
      "emailAddress" => $meta["_billing_email"][0]
    );
    $order->add_order_note('Attempting to send lead to SharpSpring: ' . join(',', $lead));

    $method = 'createLeads';
    $requestID = $order->id;

    $data = array(
      'method' => $method,
      'params' => $lead,
      'id' => $requestID
    );

    $queryString = http_build_query(array(
      'accountID' => self::$params["sharpspring_api_key"],
      'secretKey' => self::$params["sharpspring_secret_key"]
    ));
    $url = "http://api.sharpspring.com/pubapi/v1/?$queryString";
    $args = array(
      'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
      'body' => json_encode($data)
    );

    $response = wp_remote_post($url, $args);
    add_post_meta($order->id, '_ss_import_result', $response);
  }

  public function order_actions($actions) {
    $actions['send_lead_to_sharpspring'] = __("Send lead to SharpSpring", 'woocommerce-sharpspring');
    return $actions;
  }

  public function order_metabox() {
    add_meta_box('ss-box', 'SharpSpring Data', array( $this, 'order_metabox_output' ), 'shop_order', 'normal', 'low');
  }

  public function order_metabox_output($order) {
    $data = get_post_meta($order->ID);
    echo "<dl>";
    if (!empty($data)) foreach ($data as $key => $values) {
      if (strpos($key, '_ss') === 0) {
        foreach ($values as $value) {
          if (isset($value)){
            echo "<dt>$key</dt>";
            echo '<dd><pre style="max-height: 100px; overflow-y: scroll; padding: 0.3em; border: 1px solid #ccc;">'.$value.'</pre><a href="#" style="float: right; font-size: 12px;" onclick="var event = arguments[0] || window.event; event.preventDefault(); jQuery(this).hide().parent(\'dd\').find(\'pre\').css(\'max-height\', \'none\');">expand</a><br style="clear:all"></dd>';
          }
        }
      }
    }
    echo "</dl>";
  }

  public function get_transaction_id() {
    if (WC()->session->__isset('ss_transaction_id')){
      return WC()->session->get('ss_transaction_id');
    } else {

      $new_transaction_id = rand(1000,5000) . time();
      WC()->session->set('ss_transaction_id', $new_transaction_id);

      return $new_transaction_id;
    }
  }

  public function page_tracking() {
    wp_register_script ('ss_page_tracking',
      plugins_url('scripts/ss_page_tracking.js', __FILE__), null, null, true);

    $ss_account_settings = array(
      'domain'  => self::$params['sharpspring_domain'],
      'account' => self::$params['sharpspring_account']
    );

    wp_localize_script( 'ss_page_tracking', 'ss_account_settings', $ss_account_settings );
    wp_enqueue_script( 'ss_page_tracking' );
  }

  public function shopping_cart_tracking() {
    wp_register_script( 'ss_shopping_cart_tracking',
      plugins_url('scripts/ss_shopping_cart_tracking.js', __FILE__), null, null, true);

    $cart = WC()->cart;
    $customer = WC()->customer;

    $tracking_data = array(
      'transaction_data'      => array(
        'transactionID'   => $this->get_transaction_id(),
        'store_name'      => self::$params['store_name'],
        'total'           => $cart->total,
        'tax'             => $cart->tax_total,
        'shipping'        => $cart->shipping_total,
        'city'            => ($customer->get_city())      ? $customer->get_city()     : 'Austin',
        'state'           => ($customer->get_state())     ? $customer->get_state()    : 'TX',
        'zipcode'         => ($customer->get_postcode())  ? $customer->get_postcode() : '78759',
        'country'         => ($customer->get_country())   ? $customer->get_country()  : 'USA'
      ),
      'cart_contents'         => $cart->cart_contents,
      'removed_cart_contents' => $cart->removed_cart_contents
    );

    wp_localize_script( 'ss_shopping_cart_tracking', 'ss_shopping_cart_tracking_data', $tracking_data );

    wp_enqueue_script( 'ss_shopping_cart_tracking' );
  }

  public function order_tracking($order_id) {
    wp_register_script( 'ss_order_tracking',
      plugins_url('scripts/ss_order_tracking.js', __FILE__), null, null, true);

    $transactionID = $this->get_transaction_id();

    $order = new WC_Order($order_id);
    add_post_meta($order_id, 'ss_transaction_id', $transactionID);
    $order->add_order_note('Sent transactionID ' . $transactionID . ' to SharpSpring');

    WC()->session->__unset('ss_transaction_id');

    $tracking_data = array(
      'transactionID' => $transactionID
    );

    wp_localize_script( 'ss_order_tracking', 'ss_order_tracking_data', $tracking_data );
    wp_enqueue_script( 'ss_order_tracking' );
  }
}

new WC_SS_Plugin();

