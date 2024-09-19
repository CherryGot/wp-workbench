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
      $no_error = \__( 'Unspecified error', 'padelpoint-integration' );
      $message  = ! empty( $receipt['message'] ) ? $receipt['message'] : $no_error;
      return sprintf(
        /* Translators: %s is the error message, */
        \__(
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
          \__(
            'The order forwarded successfully on PadelPoint with reference: %d',
            'padelpoint-integration'
          ),
          $receipt['Numero de pedido']
        );
      }
      else {
        return \__(
          'The order request to PadelPoint was successful but no valid reference was generated.',
          'padelpoint-integration'
        );
      }
    }
    else {
      return \__(
        'The order request to PadelPoint was successful but no valid reference was generated.',
        'padelpoint-integration'
      );
    }
  }

}
