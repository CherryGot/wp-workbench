<?php
/**
 * Common things used throughout the codebase.
 *
 * @package arpadel
 */

declare ( strict_types = 1 );

namespace PadelPoint;

/**
 * Class to wrap up as per PSR-4 standard.
 */
class Utils {

  /**
   * Logs on to debug.log file when corresponding flags are set to true.
   *
   * @param string $message The message to log.
   */
  public static function log( string $message ): void {
    if ( defined( 'WP_DEBUG' ) && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG && WP_DEBUG_LOG ) {
      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
      error_log( "[PadelPoint Integration]: $message" );
    }
  }

  /**
   * For a given url, this function returns html to render a gallery image item.
   *
   * @param string $src The image url.
   * @param bool   $is_main Whether the image is the main item of the gallery.
   * @return string The html.
   */
  public static function get_gallery_image_html( string $src, bool $is_main = false ): string {
    $flexslider = (bool) \apply_filters(
      'woocommerce_single_product_flexslider_enabled',
      \get_theme_support( 'wc-product-gallery-slider' )
    );
    $image_size = \apply_filters(
      'woocommerce_gallery_image_size',
      $flexslider || $is_main ? 'woocommerce_single' : ''
    );

    $split_src = explode( '/', $src );
    $alt_text  = trim( end( $split_src ) );
    $image     = '<img
      class="' . \esc_attr( $is_main ? 'wp-post-image' : '' ) . ' size-' . $image_size . '"
      alt="' . \esc_attr( $alt_text ) . '"
      src="' . \esc_url( $src ) . '"
      data-src="' . \esc_url( $src ) . '"
    />';

    return '<div
      data-thumb="' . \esc_url( $src ) . '"
      data-thumb-alt="' . \esc_attr( $alt_text ) . '"
      class="woocommerce-product-gallery__image"
    >
      <a href="' . \esc_url( $src ) . '">' . $image . '</a>
    </div>';
  }

}
