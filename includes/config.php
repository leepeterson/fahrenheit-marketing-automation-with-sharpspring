<?php

class FM_SS_Plugin_Config
{

  private static $_options;

  private static $_instance = null;

  public static function get_instance() {
    if ( self::$_instance == null ) {
      self::$_instance = new FM_SS_Plugin_Config();
    }
    return self::$_instance;
  }

  public function __construct() {

    self::$_options = (get_option( 'fm_ss_plugin_settings' )) ? get_option( 'fm_ss_plugin_settings' ) : get_option( 'wc_ss_plugin_settings' );

    if (empty(self::$_options['store_name'])){
      self::$_options['store_name'] = get_bloginfo('name');
    }

    add_action( 'admin_menu', [$this, 'add_admin_menu'] );
    add_action( 'admin_init', [$this, 'settings_init'] );

  }

  public function get_options() {

    return self::$_options;

  }

  public function add_admin_menu() {

      add_submenu_page( 'options-general.php', 'Fahrenheit Marketing Automation with SharpSpring', 'Fahrenheit Marketing Automation with SharpSpring', 'manage_options', 'fm-ss-plugin', [$this, 'options_page'] );

  }

  public function options_page() {

?>
  <div class="wrap">
  <h1>Fahrenheit Marketing Automation with SharpSpring</h1>
  <form action='options.php' method='post'>


<?php
    settings_fields( 'fm_ss_plugin_settings_group' );
    do_settings_sections( 'fm_ss_plugin_settings_page' );
    submit_button();
?>

  </form>
  </div>
<?php

  }

  public function settings_init() {

    register_setting( 'fm_ss_plugin_settings_group', 'fm_ss_plugin_settings' );

    // General Settings
    add_settings_section(
      'fm_ss_plugin_settings',
      __( 'General Settings', 'fm-sharpspring' ),
      [$this, 'settings_section_callback'],
      'fm_ss_plugin_settings_page'
    );
    add_settings_field(
      'sharpspring_domain',
      __( 'SharpSpring Domain', 'fm-sharpspring' ),
      [$this, 'sharpspring_domain_render'],
      'fm_ss_plugin_settings_page',
      'fm_ss_plugin_settings'
    );
    add_settings_field(
      'sharpspring_account',
      __( 'SharpSpring Account', 'fm-sharpspring' ),
      [$this, 'sharpspring_account_render'],
      'fm_ss_plugin_settings_page',
      'fm_ss_plugin_settings'
    );
    add_settings_field(
      'enable_pageview_tracking',
      __( 'Enable Pageview Tracking', 'fm-sharpspring' ),
      [$this, 'enable_pageview_tracking_render'],
      'fm_ss_plugin_settings_page',
      'fm_ss_plugin_settings'
    );

    // Gravity Forms
    add_settings_section(
      'fm_ss_plugin_gf_settings',
      __( 'Gravity Forms SharpSpring Integration Settings', 'fm-sharpspring' ),
      [$this, 'gf_settings_section_callback'],
      'fm_ss_plugin_settings_page'
    );
    add_settings_field(
      'enable_gravity_form_tracking',
      __( 'Enable Gravity Form Tracking', 'fm-sharpspring' ),
      [$this, 'enable_gravity_form_tracking_render'],
      'fm_ss_plugin_settings_page',
      'fm_ss_plugin_gf_settings'
    );

    // WooCommerce
    add_settings_section(
      'fm_ss_plugin_wc_settings',
      __( 'WooCommerce SharpSpring Integration Settings', 'fm-sharpspring' ),
      [$this, 'wc_settings_section_callback'],
      'fm_ss_plugin_settings_page'
    );
    add_settings_field(
      'store_name',
      __( 'Store Name', 'fm-sharpspring' ),
      [$this, 'store_name_render'],
      'fm_ss_plugin_settings_page',
      'fm_ss_plugin_wc_settings'
    );
    add_settings_field(
      'enable_shopping_cart_tracking',
      __( 'Enable Shopping Cart Tracking', 'fm-sharpspring' ),
      [$this, 'enable_shopping_cart_tracking_render'],
      'fm_ss_plugin_settings_page',
      'fm_ss_plugin_wc_settings'
    );

    // Unused
    add_settings_section(
      'fm_ss_plugin_unused_settings',
      __( 'Unused Settings', 'fm-sharpspring' ),
      [$this, 'unused_settings_section_callback'],
      'fm_ss_plugin_settings_page'
    );
    add_settings_field(
      'sharpspring_api_key',
      __( 'SharpSpring API Key', 'fm-sharpspring' ),
      [$this, 'sharpspring_api_key_render'],
      'fm_ss_plugin_settings_page',
      'fm_ss_plugin_unused_settings'
    );
    add_settings_field(
      'sharpspring_secret_key',
      __( 'SharpSpring Secret Key', 'fm-sharpspring' ),
      [$this, 'sharpspring_secret_key_render'],
      'fm_ss_plugin_settings_page',
      'fm_ss_plugin_unused_settings'
    );
    add_settings_field(
      'error_email_recipients',
      __( 'Error Email Recipients', 'fm-sharpspring' ),
      [$this, 'error_email_recipients_render'],
      'fm_ss_plugin_settings_page',
      'fm_ss_plugin_unused_settings'
    );

  }

  public function settings_section_callback() {

    echo __( 'Set your SharpSpring Integration preferences', 'fm-sharpspring' );

  }

  public function gf_settings_section_callback() {

    echo __( 'Set your Gravity Forms SharpSpring Integration preferences', 'fm-sharpspring' );

  }

  public function wc_settings_section_callback() {

    echo __( 'Set your WooCommerce SharpSpring Integration preferences', 'fm-sharpspring' );

  }

  public function unused_settings_section_callback() {

    echo __( 'These settings are currently unused, but may be used someday.', 'fm-sharpspring' );

  }

  public function sharpspring_api_key_render() {

    $options = self::$_options;
    $sharpspring_api_key = isset($options['sharpspring_api_key']) ? $options['sharpspring_api_key'] : '';
  ?>
    <input type='text' name='fm_ss_plugin_settings[sharpspring_api_key]' value='<?php echo $sharpspring_api_key; ?>'>
  <?php

  }

  public function sharpspring_secret_key_render() {

    $options = self::$_options;
    $sharpspring_secret_key = isset($options['sharpspring_secret_key']) ? $options['sharpspring_secret_key'] : '';
  ?>
    <input type='text' name='fm_ss_plugin_settings[sharpspring_secret_key]' value='<?php echo $sharpspring_secret_key; ?>'>
  <?php

  }

  public function sharpspring_domain_render() {

    $options = self::$_options;
    $sharpspring_domain = isset($options['sharpspring_domain']) ? $options['sharpspring_domain'] : '';
  ?>
    <input type='text' name='fm_ss_plugin_settings[sharpspring_domain]' value='<?php echo $sharpspring_domain; ?>' placeholder="https://koi-XXXXXX.marketingautomation.services/net">
  <?php

  }

  public function sharpspring_account_render() {

    $options = self::$_options;
    $sharpspring_account = isset($options['sharpspring_account']) ? $options['sharpspring_account'] : '';
  ?>
    <input type='text' name='fm_ss_plugin_settings[sharpspring_account]' value='<?php echo $sharpspring_account; ?>' placeholder="KOI-XXXXXXXXXXX">
  <?php

  }

  public function store_name_render() {

    $options = self::$_options;
    $store_name = isset($options['store_name']) ? $options['store_name'] : '';
  ?>
    <input type='text' name='fm_ss_plugin_settings[store_name]' value='<?php echo $store_name; ?>'>
  <?php

  }

  public function enable_pageview_tracking_render() {

    $options = self::$_options;
    $enable_pageview_tracking = isset($options['enable_pageview_tracking']) ? $options['enable_pageview_tracking'] : '';
  ?>
    <input type='checkbox' name='fm_ss_plugin_settings[enable_pageview_tracking]' <?php checked('on', $enable_pageview_tracking, true); ?>>
  <?php

  }

  public function enable_shopping_cart_tracking_render() {

    $options = self::$_options;
    $enable_shopping_cart_tracking = isset($options['enable_shopping_cart_tracking']) ? $options['enable_shopping_cart_tracking'] : '';
  ?>
    <input type='checkbox' name='fm_ss_plugin_settings[enable_shopping_cart_tracking]' <?php checked('on', $enable_shopping_cart_tracking, true); ?>>
  <?php

  }

  public function enable_gravity_form_tracking_render() {

    $options = self::$_options;
    $enable_gravity_form_tracking = isset($options['enable_gravity_form_tracking']) ? $options['enable_gravity_form_tracking'] : '';
  ?>
    <input type='checkbox' name='fm_ss_plugin_settings[enable_gravity_form_tracking]' <?php checked('on', $enable_gravity_form_tracking, true); ?>>
  <?php

  }

  public function error_email_recipients_render() {

    $options = self::$_options;
    $error_email_recipients = isset($options['error_email_recipients']) ? $options['error_email_recipients'] : '';
  ?>
    <input type='text' name='fm_ss_plugin_settings[error_email_recipients]' value='<?php echo $error_email_recipients; ?>'>
  <?php

  }

}

