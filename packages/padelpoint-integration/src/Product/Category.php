<?php
/**
 * Contains logic to import categories into the system.
 *
 * @package arpadel
 */

declare ( strict_types = 1 );

namespace PadelPoint\Product;

/**
 * The class to wrap up the logic.
 */
class Category {

  /**
   * Imports the raw category data found in the catalog and converts/updates them into a
   * corresponding product category in the DB. Once the relationship is established, it returns an
   * ID of the same.
   *
   * @param array<string,mixed> $categoria The raw categories data from API.
   */
  public static function import_and_get_id( array $categoria ): int {
    if ( $categoria['CODIGO'] <= 0 ) {
      error_log( "Weird category with CODIGO set to 0: {$categoria['NOMBRE']}" );
      return 0;
    }

    $data = array(
      'description' => $categoria['DESCRIPCION'],
      'slug'        => \sanitize_title( $categoria['NOMBRE'] ),
      'name'        => $categoria['NOMBRE'],
    );

    $terms = \get_terms(
      array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'number'     => 1,
        'fields'     => 'ids',
        'orderby'    => 'name',
        'meta_key'   => '_codigo', // Just following the _ pattern from rest of the codebase.
        'meta_value' => $categoria['CODIGO'],
      )
    );
    if ( ! empty( $terms ) ) {
      $result = \wp_update_term( $terms[0], 'product_cat', $data );
      if ( \is_wp_error( $result ) ) {
        error_log( "Error al actualizar la categoría: {$result->get_error_message()}" );
        return 0;
      }

      error_log( "Categoría actualizada: {$categoria['NOMBRE']} (ID: {$terms[0]})" . PHP_EOL );
      return $terms[0];
    }

    $duplication_counter = 1;
    while ( ! empty( \term_exists( $data['slug'], 'product_cat' ) ) ) {
      $duplication_counter += 1;
      $data['slug']        .= "-$duplication_counter";
    }

    $term = \wp_insert_term( $categoria['NOMBRE'], 'product_cat', $data );
    if ( \is_wp_error( $term ) ) {
      error_log( "Error al insertar la categoría: {$term->get_error_message()}" );
      return 0;
    }

    \update_term_meta( $term['term_id'], '_codigo', $categoria['CODIGO'] );
    error_log( "Categoría insertada: {$categoria['NOMBRE']} (ID: {$term['term_id']})" . PHP_EOL );
    return $term['term_id'];
  }

  /**
   * Assigns a parent to a product category.
   *
   * @param int $term_id The child term that needs a parent.
   * @param int $parent_term_id The parent term.
   */
  public static function assign_parent( int $term_id, int $parent_term_id ): void {
    $result = \wp_update_term( $term_id, 'product_cat', array( 'parent' => $parent_term_id ) );
    if ( \is_wp_error( $result ) ) {
      error_log( "Error al actualizar la relación de jerarquía: {$result->get_error_message()}" );
      return;
    }

    error_log( "Relación de jerarquía configurada: (ID: $term_id) con padre ID: $parent_term_id" );
  }

}
