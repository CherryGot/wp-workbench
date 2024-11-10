<?php
/**
 * Contains core logic to work with the "Articulos" product type from the catalog.
 *
 * @package arpadel
 */

declare (strict_types = 1);

namespace PadelPoint\Product\Types;

use PadelPoint\Utils;

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
        'post_status' => array( 'publish', 'draft', 'future', 'private', 'trash' ),
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
      $meta_input['_sale_price']    = $articulo['PRECIO'];
    }

    if (
      isset( $articulo['PRECIO_RECOMENDADO'] ) &&
      ! empty( $articulo['PRECIO_RECOMENDADO'] ) &&
      (float) $articulo['PRECIO_RECOMENDADO'] > 0.0
    ) {
      $meta_input['_regular_price'] = $articulo['PRECIO_RECOMENDADO'];
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
          'post_status' => 'draft',
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

    $fields = array( 'MARCA' ); // Add more API fields to save as ACF field here, if needed.
    foreach ( $fields as $field ) {
      if ( isset( $articulo[ $field ] ) ) {
        \update_field( strtolower( $field ), $articulo[ $field ], $post_id );
      }
    }

    return $post_id;
  }

  /**
   * Overrides the default get_image() function definition.
   *
   * @param string              $size (default: 'woocommerce_thumbnail').
   * @param array<string,mixed> $attr Image attributes.
   * @param bool                $p Whether to use placeholder image when no image found.
   * @return string The image tag with the thumbnail in it as src.
   *
   * phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
   */
  public function get_image( $size = 'woocommerce_thumbnail', $attr = array(), $p = true ): string {
    // phpcs:enable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint

    $image           = parent::get_image( $size, $attr, $p );
    $placeholder_src = \wc_placeholder_img_src( $size );

    if ( false === strpos( $image, $placeholder_src ) ) {
      return $image;
    }

    $api_image = self::get_api_image_src( '', $this->get_id(), $this );
    $image     = str_replace( $placeholder_src, $api_image, $image );
    return preg_replace( '/srcset="[^"]+"/', '', $image );
  }

  /**
   * Gets the main image of the product from the API imported fields, in case the existing image
   * is empty or a placeholder.
   *
   * @param string $src The existing image src to be used as product image.
   * @param int    $pid The ID of the product in question.
   * @param object $product The product itself.
   * @return string The relevant image URL.
   */
  public static function get_api_image_src( string $src, int $pid, object $product ): string {
    if ( ! ( $product instanceof \WC_Product ) ) {
      $product = \wc_get_product( $pid );
    }

    if ( self::SLUG !== $product->get_type() ) {
      return $src;
    }

    $placeholder_url = \wc_placeholder_img_src();
    if ( ! empty( $src ) && $src !== $placeholder_url ) {
      return $src;
    }

    for ( $i = 1; $i <= 2; $i += 1 ) {
      $img = \get_field( "imagen_$i", $pid );
      if ( ! empty( $img ) ) {
        return $img;
      }
    }

    return $src;
  }

  /**
   * Empties the html for a product thumbnail in case the article has an API image.
   *
   * @param string $html The thumbnail html.
   * @return string The updated thumbnail html.
   */
  public static function remove_thumbnail_placeholder_html( string $html ): string {
    if ( false === strpos( $html, 'woocommerce-product-gallery__image--placeholder' ) ) {
      return $html;
    }

    global $product;

    $thumb_src = self::get_api_image_src( '', $product->get_id(), $product );
    if ( empty( $thumb_src ) ) {
      return $html;
    }

    return '';
  }

  /**
   * Prepares and renders the HTML for thumbnails generated from API images.
   */
  public static function render_thumbnail_html(): void {
    global $product;
    if ( empty( $product ) || self::SLUG !== $product->get_type() ) {
      return;
    }

    $has_no_main_image_yet = false;
    if ( empty( $product->get_image_id() ) ) {
      $has_no_main_image_yet = true;
    }

    $images = array();
    for ( $i = 1; $i <= 2; $i += 1 ) {
      $img = \get_field( "imagen_$i", $product->get_id() );
      if ( ! empty( $img ) ) {
        $images[] = $img;
      }
    }

    if ( count( $images ) <= 0 ) {
      return;
    }

    echo \wp_kses_post(
      Utils::get_gallery_image_html( $images[0], $has_no_main_image_yet )
    );
    if ( isset( $images[1] ) ) {
      echo \wp_kses_post( Utils::get_gallery_image_html( $images[1] ) );
    }
  }

}
