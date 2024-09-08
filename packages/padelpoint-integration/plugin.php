<?php
/**
 * Plugin Name: PadelPoint API Integration
 * phpcs:ignore Generic.Files.LineLength.MaxExceeded
 * Description: Integration with the PadelPoint API to import product catalogue, check their availability and insert orders.
 * Version:     0.0.0
 * Author:      Chakrapani Gautam [https://cherrygot.me], Wendy Alarcón Mego
 *
 * @package arpadel
 */

declare ( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

require 'vendor/autoload.php';

use PadelPoint\Product\Types;

$init_handler = function (): void {
  add_filter( 'product_type_selector', array( Types\Article::class, 'register' ) );
  add_filter( 'product_type_selector', array( Types\Set::class, 'register' ) );

  add_filter( 'woocommerce_product_class', array( Types\Article::class, 'resolve_class' ), 10, 2 );
  add_filter( 'woocommerce_product_class', array( Types\Set::class, 'resolve_class' ), 10, 2 );
};
add_action( 'init', $init_handler );

$admin_footer_handler = function (): void {
  Types\Article::enable_simple_fields();
  Types\Set::enable_variable_fields();
};
add_action( 'admin_footer', $admin_footer_handler );
