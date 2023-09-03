<?php
/**
 * GenZ Admin — Supercharged WordPress Admin Interface
 *
 * @package           cherrygot/genz-admin
 * @author            Chakrapani Gautam <contact@cherrygot.me>
 *
 * @wordpress-plugin
 *
 * phpcs:disable Generic.Files.LineLength.MaxExceeded
 *
 * Plugin Name:       GenZ Admin
 * Plugin URI:        https://github.com/CherryGot/GenZAdmin/
 * Description:       Are you tired of using the same old admin theme in WordPress and looking for something new and refreshing? Look no further than Genz Admin! This supercharged, next-gen WordPress admin interface is designed to be modern, sleek, and eye-catching, delivering a beautiful and user-friendly alternative to your standard WordPress editing experience.
 * Version:           0.0.3
 * Requires at least: 6.3
 * Requires PHP:      8.0
 * Author:            Chakrapani Gautam <contact@cherrygot.me>
 * Author URI:        https://cherrygot.me/
 * Text Domain:       genz-admin
 * License:           GPL v3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Update URI:        https://github.com/CherryGot/GenZAdmin/
 */

declare ( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
  die( 'Not today my friend, not today. Get back to what you were doing!' );
}

if ( ! defined( 'GENZ_ADMIN_ASSETS_URL' ) ) {
  $asset_url = \plugins_url( path: '/assets', plugin: __FILE__ );
  define( 'GENZ_ADMIN_ASSETS_URL', $asset_url );
}

if ( ! defined( 'GENZ_ADMIN_ASSETS_PATH' ) ) {
  define( 'GENZ_ADMIN_ASSETS_PATH', \plugin_dir_path( __FILE__ ) . 'assets' );
}

require_once 'vendor/autoload.php';

