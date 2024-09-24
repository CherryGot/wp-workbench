<?php
/**
 * Contains core logic to work with the "Articulos" product type from the catalog.
 *
 * @package arpadel
 */

declare (strict_types = 1);

namespace PadelPoint\Product\Types;

/**
 * Wraps the logic that filedoc was talking about.
 */
class Article extends \WC_Product_Simple {

  public const SLUG = 'padelpoint-article';

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
    $types[ static::SLUG ] = __( 'PadelPoint Article', 'padelpoint-integration' );
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
   * Since this product type is just an extension over Simple Product type, we need to make sure
   * that the woocommerce metabox shows relevant fields for our custom product type.
   */
  public static function enable_simple_fields(): void {
    $show = 'show_if_' . static::SLUG;
    $hide = 'hide_if_' . static::SLUG;
    ?>
    <script>
      jQuery(document).ready(function($) {
        $('#woocommerce-product-data .show_if_simple').addClass(
          '<?php echo esc_attr( $show ); ?>'
        );
        $('#woocommerce-product-data .hide_if_simple').addClass(
          '<?php echo esc_attr( $hide ); ?>'
        );
        $('#woocommerce-product-data #product-type').change();
      });
    </script>
    <?php
  }

  /**
   * Imports an articulo present in padelpoint API and populates necessary ACF fields.
   *
   * @param array<string,mixed> $articulo The raw article product from catalog.
   * @return int The post id of the created/updated product.
   */
  public static function import_and_get_id( array $articulo ): int {
    $post_id          = 0;
    $existing_product = \get_posts(
      array(
        'post_type'   => 'product',
        'meta_key'    => '_sku',
        'meta_value'  => $articulo['CODIGO'],
        'numberposts' => 1,
        'fields'      => 'ids',
      )
    );

    $meta_input = array(
      '_manage_stock' => 'yes',
      '_stock'        => $articulo['STOCK'],
    );

    if ( isset( $articulo['PRECIO'] ) && ! empty( $articulo['PRECIO'] ) ) {
      $meta_input['_regular_price'] = $articulo['PRECIO'];
      $meta_input['_price']         = $articulo['PRECIO'];
    }

    if ( isset( $articulo['PESO'] ) && ! empty( $articulo['PESO'] ) ) {
      $meta_input['_weight'] = $articulo['PESO'];
    }

    if ( $existing_product ) {
      // Actualizar el producto existente.
      $post_id = $existing_product[0];
      \wp_update_post(
        array(
          'ID'         => $post_id,
          'post_title' => $articulo['DESCRIPCION'],
          'meta_input' => $meta_input,
        )
      );
    }
    else {
      // Crear nuevo producto.
      $post_id = \wp_insert_post(
        array(
          'post_title'  => $articulo['DESCRIPCION'],
          'post_type'   => 'product',
          'post_status' => 'publish',
          'meta_input'  => array_merge( $meta_input, array( '_sku' => $articulo['CODIGO'] ) ),
        )
      );
      \wp_set_object_terms( $post_id, static::SLUG, 'product_type' );
    }

    if ( $post_id <= 0 ) {
      return 0;
    }

    if ( isset( $articulo['CODIGOS_CATWEB'] ) ) {
      wp_set_object_terms( $post_id, $articulo['CODIGOS_CATWEB'], 'product_cat' );
    }

    // Actualizar campos personalizados (ACF).
    if ( ! empty( $articulo['IMAGENES'] ) ) {
      for ( $i = 0; $i < 2; $i += 1 ) { // Currently, expecting only two images.
        if ( isset( $articulo['IMAGENES'][ $i ] ) ) {
          \update_field( 'imagen_' . ( $i + 1 ), $articulo['IMAGENES'][ $i ], $post_id );
        }
      }
    }

    $fields  = array( 'MARCA', 'FORMA', 'SUPERFICIE', 'JUGADOR', 'NUCLEO', 'NIVEL' );
    $fields += array( 'TIPO_DE_JUEGO', 'DUREZA', 'PESO', 'TIPO_DE_SUELA' );
    foreach ( $fields as $field ) {
      if ( isset( $articulo[ $field ] ) ) {
        \update_field( strtolower( $field ), $articulo[ $field ], $post_id );
      }
    }

    return $post_id;
  }

}
