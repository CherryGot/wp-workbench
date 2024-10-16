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

  /**
   * Imports a set(conjunto) present in padelpoint API and populates necessary ACF fields.
   *
   * @param array<string,mixed> $set The raw set product from catalog.
   * @return int The post id of the created/updated product.
   */
  public static function import_and_get_id( array $set ): int {
    $product = \get_posts(
      array(
        'post_type'   => 'product',
        'meta_key'    => '_sku',
        'meta_value'  => $set['sku'],
        'numberposts' => 1,
        'fields'      => 'ids',
      )
    );

    if ( empty( $product ) ) {
      $product = new static();
      $product->set_status( 'draft' );
    }
    else {
      $product = new static( $product[0] );
    }

    $product->set_name( $set['descripcion'] );
    $product->set_children( array() );
    $product->set_sku( $set['sku'] );

    $attributes = array();
    foreach ( array( 'descripcion_multiplicador1', 'descripcion_multiplicador2' ) as $i => $attr ) {
      if ( empty( $set[ $attr ] ) ) {
        continue;
      }

      $attr_key = strtoupper( str_replace( 'descripcion_', '', $attr ) );

      $attributes[ \sanitize_title( $set[ $attr ] ) ] = array(
        'name'         => $set[ $attr ],
        'value'        => implode(
          ' | ',
          array_filter( array_map( fn ( $variant ) => $variant[ $attr_key ], $set['lineas'] ) )
        ),
        'position'     => $i,
        'is_visible'   => 1,
        'is_variation' => 1,
        'is_taxonomy'  => 0,
      );

      // Storing attribute slug to name map to reference later on.
      $product->update_meta_data( strtolower( $attr_key ), $set[ $attr ] );
    }

    $post_id = $product->save();
    if ( $post_id <= 0 ) {
      return 0;
    }

    if ( ! empty( $attributes ) ) {
      \update_post_meta( $post_id, '_product_attributes', $attributes );
    }

    \wp_set_object_terms( $post_id, static::SLUG, 'product_type' );
    \wp_set_object_terms( $post_id, array(), 'product_cat' );

    // Right now, I'm just saving "marca" as ACF field, if it exists. But there can be more added
    // here after this line, if there is demand.
    if ( isset( $set['marca'] ) ) {
      \update_field( 'marca', $set['marca'], $post_id );
    }

    return $post_id;
  }

  /**
   * Imports an individual variation of a set and returns its ID.
   *
   * @param int                 $set_id The ID of the set product.
   * @param array<string,mixed> $variant The raw data about a variation from the API.
   */
  public static function import_variant( int $set_id, array $variant ): int {
    $set = new static( $set_id );

    $attr = array();
    foreach ( array( 'multiplicador1', 'multiplicador2' ) as $key ) {
      $meta_value = $set->get_meta( $key );
      if ( ! empty( $meta_value ) ) {
        $attr[ 'attribute_' . \sanitize_title( $meta_value ) ] = $variant[ strtoupper( $key ) ];
      }
    }

    $post_id   = 0;
    $variation = \get_posts(
      array(
        'post_type'   => 'product_variation',
        'meta_key'    => '_sku',
        'meta_value'  => $variant['CODIGO'],
        'numberposts' => 1,
        'fields'      => 'ids',
      )
    );

    $meta_input = array(
      '_regular_price' => $variant['PRECIO'],
      '_price'         => $variant['PRECIO'],
      '_weight'        => $variant['PESO'],
      '_manage_stock'  => 'yes',
    );

    if ( $variation ) {
      $post_id = $variation[0];
      \wp_update_post(
        array(
          'ID'          => $post_id,
          'post_parent' => $set_id,
          'meta_input'  => array_merge( $attr, $meta_input ),
        )
      );
    }
    else {
      $post_id = \wp_insert_post(
        array(
          'post_type'   => 'product_variation',
          'post_status' => 'publish',
          'post_parent' => $set_id,
          'meta_input'  => array_merge( $attr, $meta_input, array( '_sku' => $variant['CODIGO'] ) ),
        )
      );
    }

    if ( $post_id <= 0 ) {
      return 0;
    }

    \wc_update_product_stock( $post_id, $variant['STOCK'] );
    static::sync( $set_id );

    /*
    Important: The API returns categories for each variant in CATEGORIAS_WEB. However, woocommerce
    does not have the concept of categories for product "variations". This also makes sense. A
    variation belonging to a product will still belong to the same set of categories.

    This can be further confirmed by examining the catalog. If there are more than one lineas of a
    conjunto, you will notice that all of the lineas will have the exact same CATEGORIAS_WEB. OR,
    the first lineas will have all the CATEGORIAS_WEB and subsquent lineas will have CATEGORIAS_WEB
    as null.

    So here, we are just going to assign the CATEGORIAS_WEB of lineas to the conjunto directly.
    Beacuse that's the only thing we can do right now.
    */
    \wp_set_object_terms( $set_id, $variant['CATEGORIAS_WEB'], 'product_cat', true );
    if ( count( $variant['CATEGORIAS_WEB'] ) > 0 ) {
      $default_term = \get_option( 'default_product_cat', 0 );
      if ( ! empty( $default_term ) && is_numeric( $default_term ) && (int) $default_term > 0 ) {
        \wp_remove_object_terms( $set_id, (int) $default_term, 'product_cat' );
      }
    }

    // Actualizar campos personalizados (ACF).
    if ( ! empty( $variant['IMAGENES'] ) ) {
      for ( $i = 0; $i < 2; $i += 1 ) { // Currently, expecting only two images.
        if ( isset( $variant['IMAGENES'][ $i ] ) ) {
          \update_field( 'imagen_' . ( $i + 1 ), $variant['IMAGENES'][ $i ], $post_id );
        }
      }
    }

    return $post_id;
  }

  /**
   * Returns the same add to cart handler as variable product type, since we don't want to define
   * our custom handler.
   *
   * @param string $product_type The type the handler is going to use by default.
   */
  public static function get_add_to_cart_handler( string $product_type ): string {
    if ( static::SLUG === $product_type ) {
      return 'variable';
    }

    return $product_type;
  }

  /**
   * Updates the map of data types and their data store class names for our custom product type.
   *
   * @param array<string,string> $stores Existing store map.
   * @return array<string,string> Updated store map.
   */
  public static function add_data_store_class( array $stores ): array {
    $stores[ 'product-' . static::SLUG ] = 'WC_Product_Variable_Data_Store_CPT';
    return $stores;
  }

}
