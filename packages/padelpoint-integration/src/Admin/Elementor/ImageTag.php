<?php
/**
 * This file here contains logic to extend the Woocommerce Image data tag class from Elementor
 * to include the image files we receive from PadelPoint API.
 *
 * @package arpadel
 */

declare ( strict_types = 1 );

namespace PadelPoint\Admin\Elementor;

use PadelPoint\Product\Types;

if ( ! class_exists( '\\ElementorPro\\Modules\\Woocommerce\\Tags\\Product_Image' ) ) {
  return;
}

/**
 * Does the same thing as filedoc says, just PSR-4 standard.
 */
class ImageTag extends \ElementorPro\Modules\Woocommerce\Tags\Product_Image {

  /**
   * Extends the existing get_value() implementation to consider using API images for PadelPoint
   * products, if native WP images can't be found.
   *
   * @param array<string,mixed> $options The set of options.
   * @return array<string,mixed> The value of the tag.
   */
  public function get_value( array $options = array() ): array {
    $current_value = parent::get_value( $options );
    if ( ! empty( $current_value ) ) {
      return $current_value;
    }

    $product = $this->get_product( $this->get_settings( 'product_id' ) );
    if ( ! $product ) {
      return array();
    }

    if ( $product->get_type() === Types\Article::SLUG ) {
      return array(
        'id'  => 0,
        'url' => Types\Article::get_api_image_src( '', $product->get_id(), $product ),
      );
    }

    if ( $product->get_type() === Types\Set::SLUG ) {
      return array(
        'id'  => 0,
        'url' => Types\Set::get_api_image_src( '', $product->get_id(), $product ),
      );
    }

    return array();
  }

}
