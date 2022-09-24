<?php
/**
 * Entry point for plugin's business logic.
 *
 * @package cherrygot/kruti-admin
 */

namespace Kruti;

/**
 * Alters the default wp style object for remove admin styles.
 *
 * @param \WP_Styles $styles
 */
function remove_default_wp_admin_styles( \WP_Styles $styles ) {
  $styles_to_remvoe = array( 'dashicons', 'common', 'admin-bar' );
  foreach ( $styles_to_remvoe as $style ) {
    $styles->remove( $style );
  }
}
add_action( 'wp_default_styles', 'Kruti\\remove_default_wp_admin_styles', 100 );

/**
 * Enqueues the generated static assets to the wp admin.
 */
function enqueue_kruti_admin_assets() {
  wp_enqueue_style( 'kruti-admin', KRUTI_ADMIN_ASSETS_URL . '/compiled/style.css', false, '1.0.0' );
}
add_action( 'admin_enqueue_scripts', 'Kruti\\enqueue_kruti_admin_assets' );
