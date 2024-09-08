<?php
/**
 * Contains core logic to work with the "Conjuntos" product type from the catalog.
 *
 * @package arpadel
 */

declare (strict_types = 1);

namespace PadelPoint\Product\Types;

/**
 * Wraps the logic that filedoc was talking about.
 */
class Set extends \WC_Product_Variable {

  public const SLUG = 'padelpoint-set';

  /**
   * The return value of this member function is later used to define new product type.
   */
  public function get_type(): string {
    return static::SLUG;
  }

  /**
   * Registers this custom product type to appear in the product type selector.
   *
   * @param array<string,string> $types The list of already registered product types.
   * @return array<string,string> Updated types.
   */
  public static function register( array $types ): array {
    $types[ static::SLUG ] = __( 'PadelPoint Set', 'padelpoint-integration' );
    return $types;
  }

  /**
   * Resolves the product class to use based on the product type slug.
   *
   * @param string $classname    The existing classname that maybe overriden in the function.
   * @param string $product_type The slug for the type of product.
   */
  public static function resolve_class( string $classname, string $product_type ): string {
    if ( static::SLUG === $product_type ) {
      return static::class;
    }

    return $classname;
  }

  /**
   * Since this product type is just an extension over Variable Product type, we need to make sure
   * that the woocommerce metabox shows relevant fields for our custom product type.
   */
  public static function enable_variable_fields(): void {
    $show = 'show_if_' . static::SLUG;
    $hide = 'hide_if_' . static::SLUG;
    ?>
    <script>
      jQuery(document).ready(function($) {
        $('#woocommerce-product-data .show_if_variable').addClass(
          '<?php echo esc_attr( $show ); ?>'
        );
        $('#woocommerce-product-data .hide_if_variable').addClass(
          '<?php echo esc_attr( $hide ); ?>'
        );
        $('#woocommerce-product-data #product-type').change();
      });
    </script>
    <?php
  }

}
