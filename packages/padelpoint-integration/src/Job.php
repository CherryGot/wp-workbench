<?php
/**
 * Contains the main jobs that this plugin does. The initial requirements were -
 * 1. It should import the catalog, aka, the categories, articles and sets present from the API.
 * 2. It should forward an order via API to the padelpoint.
 * 3. Once order is placed, it should update the availability of products.
 *
 * @package arpadel
 */

declare ( strict_types = 1 );

namespace PadelPoint;

/**
 * Wrapper around the functions doing the 'job'.
 */
class Job {

  /**
   * Reads the catalog from the API and stores it in the options for later consumption. Also resets
   * the status of the import process and the API category to local category id map.
   */
  public static function fetch_and_store_catalog(): void {
    $import_stats = \get_option( Constants::SETTING_FIELD_IMPORT_STATS, '' );
    $import_stats = ! empty( $import_stats ) ? $import_stats : array();
    if ( ! empty( $import_stats ) ) {
      if (
        $import_stats['articles'] < $import_stats['articles_count'] ||
        $import_stats['sets'] < $import_stats['sets_count']
      ) {
        return; // There is an import already running.
      }
    }

    \delete_option( Constants::SETTING_FIELD_CATEGORY_MAP );
    \delete_option( Constants::SETTING_FIELD_IMPORT_STATS );

    $catalog = API::get_catalog( ! defined( 'IS_DEVELOP_MODE' ) || ! IS_DEVELOP_MODE );
    if ( isset( $catalog['error'] ) && $catalog['error'] ) {
      error_log( 'Catalog downloaded contains the following error: ' . $catalog['message'] );
      return;
    }

    $categoria_map = array();
    $categorias    = isset( $catalog['categorias'] ) ? $catalog['categorias'] : array();
    error_log( 'Total de categorías a procesar: ' . count( $categorias ) . PHP_EOL );

    // Crear todas las categorías primero.
    foreach ( $categorias as $categoria ) {
      error_log( "Attempting to insert the category: {$categoria['NOMBRE']}..." );

      $term_id = Product\Category::import_and_get_id( $categoria );
      if ( $term_id > 0 ) {
        $categoria_map[ $categoria['CODIGO'] ] = $term_id;
      }
    }

    // Configurar relaciones de jerarquía.
    foreach ( $categorias as $categoria ) {
      if ( isset( $categoria_map[ $categoria['CODIGO'] ] ) ) {
        if (
          0 !== $categoria['CATEGORIA_PADRE'] &&
          isset( $categoria_map[ $categoria['CATEGORIA_PADRE'] ] )
        ) {
          error_log( "Assigning parent for the category: {$categoria['NOMBRE']}..." );
          Product\Category::assign_parent(
            $categoria_map[ $categoria['CODIGO'] ],
            $categoria_map[ $categoria['CATEGORIA_PADRE'] ]
          );
        }
      }
    }

    \add_option( Constants::SETTING_FIELD_CATEGORY_MAP, $categoria_map, '', false );
    error_log( 'Total de categorías procesadas: ' . count( $categoria_map ) . PHP_EOL );

    $import_stats = array(
      'categories'       => count( $categoria_map ),
      'categories_count' => $catalog['cantidad_categorias'] ?: count( $catalog['categorias'] ),
      'articles'         => 0,
      'articles_count'   => $catalog['cantidad_articulos'] ?: count( $catalog['articulos'] ),
      'sets'             => 0,
      'sets_count'       => $catalog['cantidad_conjuntos'] ?: count( $catalog['conjuntos'] ),
    );
    \add_option( Constants::SETTING_FIELD_IMPORT_STATS, $import_stats );
  }

  /**
   * Imports product regularly by reading from the catalog stored in wp_options table.
   */
  public static function import_products(): void {
    $import_stats = \get_option( Constants::SETTING_FIELD_IMPORT_STATS, '' );
    $import_stats = ! empty( $import_stats ) ? $import_stats : array();
    if ( empty( $import_stats ) ) {
      return;
    }

    if (
      $import_stats['articles'] >= $import_stats['articles_count'] &&
      $import_stats['sets'] >= $import_stats['sets_count']
    ) {
      return;
    }

    $is_already_running = \get_transient( 'import_product_queue_processing' );
    if ( ! empty( $is_already_running ) ) {
      return;
    }

    \set_transient( 'import_product_queue_processing', true, 30 * 60 ); // 30 minutes.

    $catalog = API::get_catalog();
    $catalog = ! empty( $catalog ) || ! empty( $catalog['error'] ) ? $catalog : array();
    if ( empty( $catalog ) ) {
      return;
    }

    $categoria_map = \get_option( Constants::SETTING_FIELD_CATEGORY_MAP, '' );
    $categoria_map = ! empty( $categoria_map ) ? $categoria_map : array();

    $did_article_import_run = false;
    if ( isset( $catalog['articulos'] ) && is_array( $catalog['articulos'] ) ) {
      $batch_size = 500;
      $articles   = array();

      if ( $import_stats['articles'] < $import_stats['articles_count'] ) {
        $articles = array_slice( $catalog['articulos'], $import_stats['articles'], $batch_size );
        static::import_articles( $articles, $categoria_map );
        $import_stats['articles'] += count( $articles );
        $did_article_import_run    = true;
      }
    }

    if ( $did_article_import_run ) {
      \update_option( Constants::SETTING_FIELD_IMPORT_STATS, $import_stats );
      \delete_transient( 'import_product_queue_processing' );
      return;
    }

    if ( isset( $catalog['conjuntos'] ) && is_array( $catalog['conjuntos'] ) ) {
      $batch_size = 100;
      $sets       = array();

      if ( $import_stats['sets'] < $import_stats['sets_count'] ) {
        $sets = array_slice( $catalog['conjuntos'], $import_stats['sets'], $batch_size );
        static::import_sets( $sets, $categoria_map );
        $import_stats['sets'] += count( $sets );
      }
    }

    \update_option( Constants::SETTING_FIELD_IMPORT_STATS, $import_stats );
    \delete_transient( 'import_product_queue_processing' );
  }

  /**
   * Imports a batch of articles from the catalog.
   *
   * @param array<string,mixed> $articles An array of raw article data from catalog.
   * @param array<int,int>      $categoria_map The map of catalog category ID to native category ID.
   */
  private static function import_articles( array $articles, array $categoria_map ): void {
    foreach ( $articles as $article ) {
      error_log( "Importing article with code {$article['CODIGO']}: {$article['DESCRIPCION']}..." );
      if ( $article['CODIGO'] <= 0 ) {
        error_log( 'Weird Article with SKU set to 0. Skipping.' . PHP_EOL );
        continue;
      }

      if ( isset( $article['CODIGOS_CATWEB'] ) ) {
        $codigos_categorias = array_map( 'trim', explode( ',', $article['CODIGOS_CATWEB'] ) );
        $article['CODIGOS_CATWEB'] = array_filter(
          array_map( fn ( $codigo ) => $categoria_map[ $codigo ] ?? null, $codigos_categorias )
        );
      }

      $post_id = Product\Types\Article::import_and_get_id( $article );
      if ( $post_id <= 0 ) {
        error_log( 'Could not create or update article, skipping.' . PHP_EOL );
        continue;
      }

      error_log( "Producto insertado: {$article['DESCRIPCION']} (ID: $post_id)" . PHP_EOL );
    }
  }

  /**
   * Imports a batch of sets from the catalog.
   *
   * @param array<string,mixed> $sets An array of raw set data from catalog.
   * @param array<int,int>      $categoria_map The map of catalog category ID to native category ID.
   */
  private static function import_sets( array $sets, array $categoria_map ): void {
    foreach ( $sets as $set ) {
      error_log( "Importing Conjunto with SKU {$set['sku']}: {$set['descripcion']}..." );
      if ( $set['sku'] <= 0 ) {
        error_log( 'Weird Conjunto with SKU set to 0. Skipping' . PHP_EOL );
        continue;
      }

      $set_id = Product\Types\Set::import_and_get_id( $set );
      if ( $set_id <= 0 ) {
        error_log( 'Could not create or update set, skipping.' . PHP_EOL );
        continue;
      }

      foreach ( $set['lineas'] as $item ) {
        error_log( "Importing variant with code {$item['CODIGO']} for set: {$set_id}..." );
        if ( $item['CODIGO'] <= 0 ) {
          error_log( 'Weird variant with SKU set to 0. Skipping' );
          continue;
        }

        $item['CATEGORIAS_WEB'] = ! empty( $item['CATEGORIAS_WEB'] ) ? $item['CATEGORIAS_WEB'] : '';
        $codigos_categorias     = array_map( 'trim', explode( ',', $item['CATEGORIAS_WEB'] ) );
        $item['CATEGORIAS_WEB'] = array_filter(
          array_map( fn ( $codigo ) => $categoria_map[ $codigo ] ?? null, $codigos_categorias )
        );

        $post_id = Product\Types\Set::import_variant( $set_id, $item );
        if ( $post_id <= 0 ) {
          error_log( 'Could not create or update variant, skipping.' );
          continue;
        }

        error_log( 'Variant updated/inserted: (ID: $post_id)' );
      }

      error_log( "Producto insertado: {$set['descripcion']} (ID: $set_id)" . PHP_EOL );
    }
  }

  /**
   * After a woocommerce payment is done, process the order to forward it to PadelPoint, prepare
   * notes about the reference received from the API and update the availability of products after
   * this order.
   *
   * @param int $order_id The order for which payment has been made.
   */
  public static function process_order( int $order_id ): void {
    if ( $order_id <= 0 ) {
      return;
    }

    $order = new \WC_Order( $order_id );
    if ( ! $order->meta_exists( 'padelpoint-reference' ) ) {
      $receipt = self::forward_order( $order );
      if (
        isset( $receipt['Numero de pedido'] ) &&
        is_numeric( $receipt['Numero de pedido'] ) &&
        (int) $receipt['Numero de pedido'] > 0
      ) {
        $order->add_meta_data( 'padelpoint-reference', $receipt['Numero de pedido'], true );
      }

      $note = static::get_note_from_order_receipt( $receipt );
      $order->add_order_note( $note );

      $order->save();
    }

    $product_types = array( Product\Types\Article::SLUG, Product\Types\Set::SLUG );
    $find_products = function ( $item ) use ( $product_types ) {
      $product = $item->get_product();
      if ( 'variation' === $product->get_type() ) {
        $product = \wc_get_product( $product->get_parent_id() );
      }

      if ( ! $product || ! in_array( $product->get_type(), $product_types, true ) ) {
        return null;
      }

      return $product;
    };

    $products = array_filter( array_map( $find_products, $order->get_items() ) );
    static::update_availabilities( $products );
  }

  /**
   * Forwards a Woocommerce order to PadelPoint via API.
   *
   * @param \WC_Order $order the woocommerce order.
   * @return array<string,mixed> Response received from API.
   */
  private static function forward_order( \WC_Order $order ): array {
    $address_lines = $order->get_shipping_address_1();
    if ( ! empty( $order->get_shipping_address_2() ) ) {
      $address_lines .= ",\n {$order->get_shipping_address_2()}";
    }

    $order_notes = $order->get_customer_note();
    if ( defined( 'IS_DEVELOP_MODE' ) && IS_DEVELOP_MODE ) {
      $order_notes .= "\n\n==== DEVELOPER NOTE ====\n";
      $order_notes .= 'This is a dummy order to test the order functionality. Do not proceed!';
    }

    $products      = array();
    $product_types = array( Product\Types\Article::SLUG, Product\Types\Set::SLUG );
    foreach ( $order->get_items() as $i => $item ) {
      if ( empty( $item->get_product() ) || empty( $item->get_product()->get_sku() ) ) {
        continue;
      }

      $type = $item->get_product()->get_type();
      if ( 'variation' === $type ) {
        $temp = \wc_get_product( $item->get_product()->get_parent_id() );
        $type = $temp ? $temp->get_type() : $type;
      }

      if ( ! in_array( $type, $product_types, true ) ) {
        continue;
      }

      $products[] = array(
        'ARTICULO' => $item->get_product()->get_sku(),
        'CANTIDAD' => $item->get_quantity(),
      );
    }

    $country = $order->get_shipping_country();
    $states  = \WC()->countries->get_states( $country );
    if ( array_key_exists( $country, \WC()->countries->countries ) ) {
      $country = \WC()->countries->countries[ $country ];
    }

    $state = $order->get_shipping_state();
    if ( ! empty( $states ) && array_key_exists( $state, $states ) ) {
      $state = $states[ $state ];
    }

    $warehouse_order = array(
      'COMENTARIO_ENVIO' => $order_notes,
      'ID_PEDIDO'        => $order->get_id(),
      'DIRECCION_ENVIO'  => array(
        'NOMBRE'        => $order->get_shipping_first_name(),
        'APELLIDOS'     => $order->get_shipping_last_name(),
        'DIRECCION'     => $address_lines,
        'POBLACION'     => $order->get_shipping_city(),
        'PROVINCIA'     => $state,
        'PAIS'          => $country,
        'CODIGO_POSTAL' => $order->get_shipping_postcode(),
        'TELEFONO'      => $order->get_billing_phone(), // No shipping phone apparantly.
      ),
      'LINEAS'           => $products,
    );
    return API::place_order( $warehouse_order );
  }

  /**
   * Prepares a note string to attach based on the status received after forwarding the order.
   *
   * @param array<string,mixed> $receipt The response received after forwardinge order.
   */
  private static function get_note_from_order_receipt( array $receipt ): string {
    if ( isset( $receipt['error'] ) && $receipt['error'] ) {
      $no_error = __( 'Unspecified error', 'padelpoint-integration' );
      $message  = ! empty( $receipt['message'] ) ? $receipt['message'] : $no_error;
      return sprintf(
        /* Translators: %s is the error message, */
        __(
          'The order was not forwarded to PadelPoint because of the following error: %s',
          'padelpoint-integration'
        ),
        $message
      );
    }
    elseif ( isset( $receipt['Numero de pedido'] ) && is_numeric( $receipt['Numero de pedido'] ) ) {
      if ( $receipt['Numero de pedido'] > 0 ) {
        return sprintf(
          /* Translators: %d is the reference ID for order. */
          __(
            'The order forwarded successfully on PadelPoint with reference: %d',
            'padelpoint-integration'
          ),
          $receipt['Numero de pedido']
        );
      }
      else {
        return __(
          'The order request to PadelPoint was successful but no valid reference was generated.',
          'padelpoint-integration'
        );
      }
    }
    else {
      return __(
        'The order request to PadelPoint was successful but no valid reference was generated.',
        'padelpoint-integration'
      );
    }
  }

  /**
   * Requests API to get availabilies of the products specified.
   *
   * @param \WC_Product[] $products
   * @return array<int,mixed> Status of update availability request.
   */
  public static function update_availabilities( array $products ): array {
    if ( empty( $products ) ) {
      return array();
    }

    $status_map = array();
    foreach ( $products as $product ) {
      $availability = API::check_availability( $product->get_sku() );
      if ( isset( $availability['error'] ) && $availability['error'] ) {
        $status_map[ $product->get_id() ] = $availability;
      }

      if ( isset( $availability['data'] ) && is_array( $availability['data'] ) ) {
        $categoria_map = \get_option( Constants::SETTING_FIELD_CATEGORY_MAP, '' );
        $categoria_map = ! empty( $categoria_map ) ? $categoria_map : array();

        $status_map[ $product->get_id() ] = true; // Signifying that product is found.

        if ( Product\Types\Article::SLUG === $product->get_type() ) {
          static::import_articles( array( $availability['data'] ), $categoria_map );
        }

        if ( Product\Types\Set::SLUG === $product->get_type() ) {
          // API design is stupid. The keys are not same in catalog and availability responses.
          $set                               = $availability['data'];
          $set['sku']                        = $set['CODIGO'];
          $set['descripcion']                = $set['DESCRIPCION'];
          $set['lineas']                     = $set['LINEAS'];
          $set['descripcion_multiplicador1'] = $set['DESCRIPCION_MULTIPLICADOR1'];
          $set['descripcion_multiplicador2'] = $set['DESCRIPCION_MULTIPLICADOR2'];

          foreach ( $set['lineas'] as $i => $item ) {
            $set['lineas'][ $i ]['CODIGO']         = $item['ARTICULO'];
            $set['lineas'][ $i ]['MULTIPLICADOR1'] = $item['MULTI1'];
            $set['lineas'][ $i ]['MULTIPLICADOR2'] = $item['MULTI2'];
            $set['lineas'][ $i ]['CATEGORIAS_WEB'] = $item['CODIGOS_CATWEB'];
          }

          static::import_sets( array( $set ), $categoria_map );
        }
      }
    }

    return $status_map;
  }

}
