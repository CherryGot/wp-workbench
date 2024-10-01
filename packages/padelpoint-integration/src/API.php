<?php
/**
 * This file here contains the actual logic to contact the PadelPoint API.
 *
 * @package arpadel
 */

declare ( strict_types = 1 );

namespace PadelPoint;

/**
 * Class to wrap up all the functions.
 */
class API {

  private const ENDPOINT = 'https://api.originalpadelpoint.com/WebServices';

  /**
   * Prepare Basic Authentication hash from the user and password fetched from DB.
   */
  private static function get_auth_hash(): string {
    $user = \get_option( Constants::SETTING_FIELD_LOGIN, '' );
    $pswd = \get_option( Constants::SETTING_FIELD_PASSWORD, '' );
    return base64_encode( "$user:$pswd" );
  }

  /**
   * Make an authenticated request to a path on the endpoint.
   *
   * @param string              $path The path to access.
   * @param array<string,mixed> $args The arguments needed to make the request.
   * @return array<string,mixed> Response data from the request.
   */
  private static function make_request( string $path, array $args ): array {
    $args['headers']['Authorization'] = 'Basic ' . static::get_auth_hash();
    $response                         = \wp_remote_request( static::ENDPOINT . $path, $args );
    if ( \is_wp_error( $response ) ) {
      Utils::log( 'Error al conectar con la API: ' . $response->get_error_message() . PHP_EOL );
      return array();
    }

    $body = \wp_remote_retrieve_body( $response );
    $body = trim( $body, "\xEF\xBB\xBF" );
    $data = json_decode( $body, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
      Utils::log( 'Error al decodificar JSON: ' . json_last_error_msg() . PHP_EOL );
      return array(
        'error'   => true,
        'message' => json_last_error_msg(),
      );
    }

    if ( isset( $data['error'] ) && $data['error'] ) {
      Utils::log( 'Error en la respuesta de la API: ' . $data['message'] . PHP_EOL );
      return $data;
    }

    return $data;
  }

  /**
   * Makes a request to catalog endpoint and returns the data from API response.
   *
   * @param bool $reload A flag to mark whether to request data from remote API or local file.
   * @return array<string,mixed> The data from the catalog response.
   */
  public static function get_catalog( bool $reload = false ): array {
    $catalog_file_path = PADELPOINT_INTEGRATION_PATH . 'dist/catalog.json';
    if ( ! $reload ) {
      return json_decode( file_get_contents( $catalog_file_path ) ?: '{}', true );
    }

    if ( file_exists( $catalog_file_path ) ) {
      \wp_delete_file( $catalog_file_path );
    }

    if ( ! is_dir( PADELPOINT_INTEGRATION_PATH . 'dist' ) ) {
      \wp_mkdir_p( PADELPOINT_INTEGRATION_PATH . 'dist' );
    }

    $catalog = static::make_request(
      '/obtener_json_catalogo_completo.php',
      array(
        'method'  => 'GET',
        'timeout' => 60,
      )
    );
    file_put_contents( $catalog_file_path, \wp_json_encode( $catalog ) );

    return $catalog;
  }

  /**
   * Send a request to order articles on PadelPoint.
   *
   * @param array<string,mixed> $order_data The data about order.
   * @return array<string,mixed> The response from API.
   */
  public static function place_order( array $order_data ): array {
    return static::make_request(
      '/insertar_pedido.php',
      array(
        'method'  => 'POST',
        'headers' => array(
          'Content-Type' => 'application/json',
        ),
        'body'    => \wp_json_encode( $order_data ),
      )
    );
  }

  /**
   * For a given SKU, fetches the stock related details.
   *
   * @param string $sku The sku of the product.
   * @return array<string,mixed> The response from the API.
   */
  public static function check_availability( string $sku ): array {
    return static::make_request(
      '/consultar_disponibilidad.php',
      array(
        'method' => 'GET',
        'body'   => array(
          'CODIGO' => $sku,
        ),
      )
    );
  }

}
