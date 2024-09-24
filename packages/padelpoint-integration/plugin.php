<?php
/**
 * Plugin Name:      PadelPoint API Integration
 * phpcs:ignore Generic.Files.LineLength.MaxExceeded
 * Description:      Integration with the PadelPoint API to import product catalogue, check their availability and insert orders.
 * Version:          0.0.0
 * Author:           Chakrapani Gautam [https://cherrygot.me], Wendy Alarcón Mego
 * Requires Plugins: woocommerce, advanced-custom-fields
 * Text Domain:      padelpoint-integration
 * Domain Path:      /languages
 *
 * @package arpadel
 */

declare ( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! defined( 'PADELPOINT_INTEGRATION_PATH' ) ) {
  define( 'PADELPOINT_INTEGRATION_PATH', plugin_dir_path( __FILE__ ) );
}

require 'vendor/autoload.php';

use PadelPoint\Product\Types;

add_action( 'plugins_loaded', array( PadelPoint\Admin\Extensions::class, 'load_translations' ) );

$init_handler = function (): void {
  add_filter( 'product_type_selector', array( Types\Article::class, 'register' ) );
  add_filter( 'product_type_selector', array( Types\Set::class, 'register' ) );

  add_filter( 'woocommerce_product_class', array( Types\Article::class, 'resolve_class' ), 10, 2 );
  add_filter( 'woocommerce_product_class', array( Types\Set::class, 'resolve_class' ), 10, 2 );

  add_action(
    'woocommerce_' . PadelPoint\Product\Types\Article::SLUG . '_add_to_cart',
    'woocommerce_simple_add_to_cart'
  );
  add_action(
    'woocommerce_' . PadelPoint\Product\Types\Set::SLUG . '_add_to_cart',
    'woocommerce_variable_add_to_cart'
  );

  $callback = array( PadelPoint\Product\Types\Set::class, 'add_data_store_class' );
  add_filter( 'woocommerce_data_stores', $callback );

  $callback = array( PadelPoint\Product\Types\Set::class, 'get_add_to_cart_handler' );
  add_filter( 'woocommerce_add_to_cart_handler', $callback );
};
add_action( 'init', $init_handler );

$admin_footer_handler = function (): void {
  Types\Article::enable_simple_fields();
  Types\Set::enable_variable_fields();
};
add_action( 'admin_footer', $admin_footer_handler );

if ( ! wp_next_scheduled( 'fetch_catalog_event' ) ) {
  wp_schedule_event( time(), 'twicedaily', 'fetch_catalog_event' );
}
add_action( 'fetch_catalog_event', array( PadelPoint\Job::class, 'fetch_and_store_catalog' ) );

add_filter( 'acf/get_post_types', array( PadelPoint\Admin\ACF::class, 'enable_variations' ) );

$callback = array( PadelPoint\Admin\ACF::class, 'add_fields_for_variations' );
add_action( 'woocommerce_product_after_variable_attributes', $callback, 10, 3 );

$callback = array( PadelPoint\Admin\ACF::class, 'save_fields_for_variations' );
add_action( 'woocommerce_save_product_variation', $callback, 10, 2 );

add_action( 'acf/input/admin_footer', array( PadelPoint\Admin\ACF::class, 'rebind_js_events' ) );

add_action( 'admin_menu', array( PadelPoint\Admin\Extensions::class, 'init' ) );

$admin_init_handler = function (): void {
  PadelPoint\Job::import_products();
  PadelPoint\Admin\Extensions::register_setting_fields();
};
add_action( 'admin_init', $admin_init_handler );

add_action( 'woocommerce_payment_complete', array( PadelPoint\Job::class, 'process_order' ) );

$callback = array( PadelPoint\Admin\Extensions::class, 'add_update_availability_button' );
add_action( 'post_submitbox_minor_actions', $callback );

$callback = array( PadelPoint\Admin\Extensions::class, 'handle_update_availbility_submission' );
add_action( 'post_updated', $callback );

add_action( 'admin_notices', array( PadelPoint\Admin\Extensions::class, 'show_notice' ) );
