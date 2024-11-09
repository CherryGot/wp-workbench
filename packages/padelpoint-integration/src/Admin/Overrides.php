<?php
/**
 * This file here contains functions that overrides default behaviour of the admin interface.
 *
 * @package arpadel
 */

declare ( strict_types = 1 );

namespace PadelPoint\Admin;

/**
 * Class to follow PSR-4 standards.
 */
class Overrides {

  /**
   * Replaces the default Woocommerce Product Image dynamic tag class with the one that we have
   * defined.
   *
   * @param object $manager The elementor dynamic tags manager.
   */
  public static function replace_product_image_dynamic_tag( object $manager ): void {
    if ( ! ( $manager instanceof \Elementor\Core\DynamicTags\Manager ) ) {
      return;
    }

    $manager->unregister( 'woocommerce-product-image-tag' );
    $manager->register( new Elementor\ImageTag() );
  }

}
