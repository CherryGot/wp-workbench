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
    $user = \get_option( 'padelpoint-login', '' );
    $pswd = \get_option( 'padelpoint-password', '' );
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
      error_log( 'Error al conectar con la API: ' . $response->get_error_message() . PHP_EOL );
      return array();
    }

    $body = \wp_remote_retrieve_body( $response );
    $body = trim( $body, "\xEF\xBB\xBF" );
    $data = json_decode( $body, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
      error_log( 'Error al decodificar JSON: ' . json_last_error_msg() . PHP_EOL );
      return array(
        'error'   => true,
        'message' => json_last_error_msg(),
      );
    }

    if ( isset( $data['error'] ) && $data['error'] ) {
      error_log( 'Error en la respuesta de la API: ' . $data['message'] . PHP_EOL );
      return $data;
    }

    return $data;
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

}
