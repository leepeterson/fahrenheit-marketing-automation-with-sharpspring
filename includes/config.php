<?php

class WC_SS_Plugin_Config
{

  private static $_options;

  private static $_instance = null;

  public static function get_instance() {
    if ( self::$_instance == null ) {
      self::$_instance = new WC_SS_Plugin_Config();
    }
    return self::$_instance;
  }

  public function __construct() {

    self::$_options = get_option( 'wc_ss_plugin_settings' );

    add_action( 'admin_menu', [$this, 'add_admin_menu'] );
    add_action( 'admin_init', [$this, 'settings_init'] );

  }

  public function get_options() {

    return self::$_options;

  }

  public function add_admin_menu() {

      add_submenu_page( 'options-general.php', 'WooCommerce SharpSpring Integration', 'WooCommerce SharpSpring', 'manage_options', 'wc-ss-plugin', [$this, 'options_page'] );

  }

  public function options_page() {

?>
  <div class="wrap">
  <h1>WooCommerce SharpSpring Integration</h1>
  <form action='options.php' method='post'>


<?php
    settings_fields( 'wc_ss_plugin_settings_group' );
    do_settings_sections( 'wc_ss_plugin_settings_page' );
    submit_button();
?>

  </form>
  </div>
<?php

  }

  public function settings_init() {

    register_setting( 'wc_ss_plugin_settings_group', 'wc_ss_plugin_settings' );

    add_settings_section(
      'wc_ss_plugin_settings',
      __( 'WooCommerce SharpSpring Integration Settings', 'woocommerce-sharpspring' ),
      [$this, 'settings_section_callback'],
      'wc_ss_plugin_settings_page'
    );
    add_settings_field(
      'sharpspring_api_key',
      __( 'SharpSpring API Key', 'woocommerce-sharpspring' ),
      [$this, 'sharpspring_api_key_render'],
      'wc_ss_plugin_settings_page',
      'wc_ss_plugin_settings'
    );
    add_settings_field(
      'sharpspring_secret_key',
      __( 'SharpSpring Secret Key', 'woocommerce-sharpspring' ),
      [$this, 'sharpspring_secret_key_render'],
      'wc_ss_plugin_settings_page',
      'wc_ss_plugin_settings'
    );
    add_settings_field(
      'add_customers_automatically',
      __( 'Add customers as leads automatically', 'woocommerce-sharpspring' ),
      [$this, 'add_customers_automatically_render'],
      'wc_ss_plugin_settings_page',
      'wc_ss_plugin_settings'
    );
    add_settings_field(
      'error_email_recipients',
      __( 'Error Email Recipients', 'woocommerce-sharpspring' ),
      [$this, 'error_email_recipients_render'],
      'wc_ss_plugin_settings_page',
      'wc_ss_plugin_settings'
    );

  }

  public function settings_section_callback() {

    echo __( 'Set your SharpSpring Integration preferences', 'woocommerce-sharpspring' );

  }

  public function sharpspring_api_key_render() {

    $options = get_option( 'wc_ss_plugin_settings' );
  ?>
    <input type='text' name='wc_ss_plugin_settings[sharpspring_api_key]' value='<?php echo $options['sharpspring_api_key']; ?>'>
  <?php

  }

  public function sharpspring_secret_key_render() {

    $options = get_option( 'wc_ss_plugin_settings' );
  ?>
    <input type='text' name='wc_ss_plugin_settings[sharpspring_secret_key]' value='<?php echo $options['sharpspring_secret_key']; ?>'>
  <?php

  }

  public function add_customers_automatically_render() {

    $options = get_option( 'wc_ss_plugin_settings' );
  ?>
    <input type='checkbox' name='wc_ss_plugin_settings[add_customers_automatically]' <?php checked('on', $options['add_customers_automatically'], true); ?>'>
  <?php

  }

  public function error_email_recipients_render() {

    $options = get_option( 'wc_ss_plugin_settings' );
  ?>
    <input type='text' name='wc_ss_plugin_settings[error_email_recipients]' value='<?php echo $options['error_email_recipients']; ?>'>
  <?php

  }

}

