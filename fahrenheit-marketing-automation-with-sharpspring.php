<?php
/**
 * Plugin Name: Fahrenheit Marketing Automation with SharpSpring
 * Description: SharpSpring Integration with support for WooCommerce and Gravity Forms by Fahrenheit Marketing
 * Version: 0.8.2
 * Author: Fahrenheit Marketing
 * Author URI: https://www.fahrenheit.io/
 * Requires at least: 4.6
 * Tested up to: 4.6
 *
 * Text Domain: humann
 * Domain Path: /i18n/languages/
 *
 * @author Fahrenheit Marketing
 */
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

require(__DIR__ . "/includes/config.php");

class FM_SS_Plugin {

  private static $debug = false;
  private static $params;

  public static function get_options() {
    $config = FM_SS_Plugin_Config::get_instance();
    return $config->get_options();
  }

  public function __construct() {
    self::$params = $this->get_options();

    add_action('wp_enqueue_scripts', array( $this, 'ss_enqueue' ), 10);

    if (isset(self::$params["enable_pageview_tracking"])){
      add_action('wp_enqueue_scripts', array( $this, 'page_tracking' ), 11);
    }

    if (isset(self::$params["enable_shopping_cart_tracking"])){
      add_action('woocommerce_cart_loaded_from_session', array( $this, 'shopping_cart_tracking' ), 10);
      add_action('woocommerce_thankyou', array( $this, 'order_tracking' ), 10, 1);
    }

    if (isset(self::$params["enable_gravity_form_tracking"])){
      add_filter('gform_form_settings', array( $this, 'gform_sharpspring_form_settings' ), 10, 2);
      add_filter('gform_pre_form_settings_save', array( $this, 'gform_sharpspring_save_settings' ), 10, 1);
      add_filter('gform_get_form_filter', array( $this, 'gform_add_ss_tracking' ), 10, 2);
    }

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

  public function ss_enqueue() {
    wp_register_script ('ss_init',
      plugins_url('scripts/ss_init.js', __FILE__), null, null, true);
    wp_register_script ('ss_page_tracking',
      plugins_url('scripts/ss_page_tracking.js', __FILE__), array('ss_init'), null, true);
    wp_register_script( 'ss_shopping_cart_tracking',
      plugins_url('scripts/ss_shopping_cart_tracking.js', __FILE__), array('ss_init'), null, true);

    $domain = isset(self::$params['sharpspring_domain']) ? self::$params['sharpspring_domain'] : '';
    $account = isset(self::$params['sharpspring_account']) ? self::$params['sharpspring_account'] : '';

    $ss_account_settings = array(
      'domain'  => $domain,
      'account' => $account
    );

    wp_localize_script( 'ss_init', 'ss_account_settings', $ss_account_settings );
    wp_enqueue_script( 'ss_init' );
  }

  public function page_tracking() {
    $domain = isset(self::$params['sharpspring_domain']) ? self::$params['sharpspring_domain'] : '';
    $account = isset(self::$params['sharpspring_account']) ? self::$params['sharpspring_account'] : '';

    $ss_account_settings = array(
      'domain'  => $domain,
      'account' => $account
    );

    wp_localize_script( 'ss_page_tracking', 'ss_account_settings', $ss_account_settings );
    wp_enqueue_script( 'ss_page_tracking' );
  }

  public function shopping_cart_tracking() {
    if (defined('DOING_AJAX')){ return; }

    if (!is_user_logged_in()){
      return;
    }

    if (preg_match('/order-received/', $_SERVER['REQUEST_URI'])){
      return;
    }

    $cart = WC()->cart;

    if (empty($cart->cart_contents) && empty($cart->removed_cart_contents)){
      return;
    }

    $transactionID = $this->get_transaction_id();
    $user = wp_get_current_user();
    $customer = WC()->customer;
    $store_name = isset(self::$params['store_name']) ? self::$params['store_name'] : '';

    $tracking_data = array(
      'transaction_data'      => array(
        'transactionID'   => $transactionID,
        'storeName'       => $store_name,
        'total'           => $cart->total,
        'tax'             => $cart->tax_total,
        'shipping'        => $cart->shipping_total,
        'city'            => ($customer->get_city())      ? $customer->get_city()     : 'Austin',
        'state'           => ($customer->get_state())     ? $customer->get_state()    : 'TX',
        'zipcode'         => ($customer->get_postcode())  ? $customer->get_postcode() : '78759',
        'country'         => ($customer->get_country())   ? $customer->get_country()  : 'USA',
        'firstName'       => $user->user_firstname,
        'lastName'        => $user->user_lastname,
        'emailAddress'    => $user->user_email
      ),
      'cart_contents'     => array_map(function($item) use ($transactionID) {
        $cats = wp_get_post_terms($item['product_id'], 'product_cat', array('fields' => 'names'));
        return array(
          'transactionID' => $transactionID,
          'quantity'      => $item['quantity'],
          'itemCode'      => $item['product_id'],
          'category'      => $cats ? $cats[0] : 'none',
          'productName'   => $item['data']->post->post_title
        );
      }, $cart->cart_contents),
      'removed_cart_contents' => array_map(function($item) use ($transactionID) {
        $cats = wp_get_post_terms($item['product_id'], 'product_cat', array('fields' => 'names'));
        return array(
          'transactionID' => $transactionID,
          'quantity'      => $item['quantity'],
          'itemCode'      => $item['product_id'],
          'category'      => $cats ? $cats[0] : 'none',
          'productName'   => ''
        );
      }, $cart->removed_cart_contents)
    );

    add_action( 'wp_enqueue_scripts', function() use ($tracking_data) {
      wp_localize_script( 'ss_shopping_cart_tracking', 'ss_shopping_cart_tracking_data', $tracking_data );
      wp_enqueue_script( 'ss_shopping_cart_tracking' );
    }, 20);
  }

  public function order_tracking($order_id) {

    if (get_post_meta($order_id, 'ss_transaction_id', true)){
      // Only track order completion once
      return;
    }

    $transactionID = $this->get_transaction_id();

    $order = new WC_Order($order_id);
    add_post_meta($order_id, 'ss_transaction_id', $transactionID);
    $store_name = isset(self::$params['store_name']) ? self::$params['store_name'] : '';

    $tracking_data = array(
      'transaction_data'      => array(
        'transactionID'   => $transactionID,
        'storeName'       => $store_name,
        'total'           => $order->get_total(),
        'tax'             => $order->get_total_tax(),
        'shipping'        => $order->get_total_shipping(),
        'city'            => $order->billing_city,
        'state'           => $order->billing_state,
        'zipcode'         => $order->billing_postcode,
        'country'         => $order->billing_country,
        'firstName'       => $order->billing_first_name,
        'lastName'        => $order->billing_last_name,
        'emailAddress'    => $order->billing_email
      ),
      'cart_contents'     => array_map(function($item) use ($transactionID) {
        $cats = wp_get_post_terms($item['product_id'], 'product_cat', array('fields' => 'names'));
        return array(
          'transactionID' => $transactionID,
          'quantity'      => $item['qty'],
          'itemCode'      => $item['product_id'],
          'price'         => round($item['line_subtotal'] / $item['qty'], 2),
          'category'      => $cats ? $cats[0] : 'none',
          'productName'   => $item['name']
        );
      }, $order->get_items()),
      'orderComplete'     => $order_id
    );

    wp_localize_script( 'ss_shopping_cart_tracking', 'ss_shopping_cart_tracking_data', $tracking_data );
    wp_enqueue_script( 'ss_shopping_cart_tracking' );

    WC()->session->__unset('ss_transaction_id');

  }

  public function gform_sharpspring_form_settings($settings, $form){
    $settings['SharpSpring Settings']['gform_sharpspring_tracking'] = '
      <tr>
        <th>SharpSpring Tracking Code</th>
        <td><textarea class="fieldwidth-3" name="sharpspring_tracking_code">' . rgar($form, 'sharpspring_tracking_code') . '</textarea></td>
      </tr>
    ';
    return $settings;
  }

  public function gform_sharpspring_save_settings($form){
    $form['sharpspring_tracking_code'] = rgpost( 'sharpspring_tracking_code' );
    return $form;
  }

  public function gform_add_ss_tracking($form_string, $form){
    $tracking_code = rgar($form, 'sharpspring_tracking_code');
    $form_id = $form['id'];
    if (!empty($tracking_code)){
      $escaped = htmlentities(str_replace( array( "__ss_noform.push(['endpoint', '", "']);" ), array('start', 'end'), $tracking_code));
      preg_match_all('!https?://\S+!', $escaped, $tracking_url);
      $tracking_url = str_replace("end", "", $tracking_url[0]);
      preg_match('/start(.*?)end/', $escaped, $endpoint);
      $tracking_code_string = "
<script type=\"text/javascript\">
var __ss_noform = __ss_noform || [];
__ss_noform.push(['baseURI', '$tracking_url[0]']);
__ss_noform.push(['form', 'gform_$form_id', '$endpoint[1]']);
</script>
<script type=\"text/javascript\" src=\"$tracking_url[1]\" ></script>";
      $form_string .= $tracking_code_string;
    }
    return $form_string;
  }

}

new FM_SS_Plugin();

