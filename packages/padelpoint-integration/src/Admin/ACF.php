<?php
/**
 * This file here extends the support for the ACF on "product_variation" post type.
 *
 * Inspired from https://gist.github.com/shreyans94/05b10194cf2f57cf054a5cf3da3fd931
 *
 * @package arpadel
 */

declare ( strict_types = 1 );

namespace PadelPoint\Admin;

/**
 * Wraps the fucntions that filedoc was talking about.
 */
class ACF {

  /**
   * Product variation is a non-public post type. ACF by default doesn't allow non-public post types
   * to have custom fields. We override that behaviour here.
   *
   * @param string[] $post_types Existing supported post types.
   * @return string[] Updated ones.
   */
  public static function enable_variations( array $post_types ): array {
    return array_merge( $post_types, array( 'product_variation' ) );
  }

  /**
   * Render fields at the bottom of variations - does not account for field group order or placement
   *
   * @param int                 $loop Position in the loop of rendering a variation.
   * @param array<string,mixed> $_ Raw variation data.
   * @param \WP_Post            $variant The WordPress post associated with the variation.
   */
  public static function add_fields_for_variations( int $loop, array $_, \WP_Post $variant ): void {
    $prepare_field_name = function ( array $field ) use ( $loop ): array {
      $field['name'] = preg_replace( '/^acf\[/', "acf[variations][$loop][", $field['name'] );
      return $field;
    };
    \add_filter( 'acf/prepare_field', $prepare_field_name );

    $field_groups = \acf_get_field_groups();
    foreach ( $field_groups as $field_group ) {
      foreach ( $field_group['location'] as $group_locations ) {
        foreach ( $group_locations as $rule ) {
          // Although there can be several other rules.
          // Here, we are just focussing on post_type == product_variation.
          if (
            'post_type' === $rule['param'] &&
            '==' === $rule['operator'] &&
            'product_variation' === $rule['value']
          ) {
            \acf_render_fields( \acf_get_fields( $field_group ), $variant->ID );
            break 2;
          }
        }
      }
    }

    \remove_filter( 'acf/prepare_field', $prepare_field_name );
  }

  /**
   * Save custom fields when a variation is saved.
   *
   * @param int $variation_id The post id for variation.
   * @param int $index The index in the loop of available variations for a set.
   */
  public static function save_fields_for_variations( int $variation_id, int $index ): void {
    // phpcs:ignore WordPress.Security -- Nonce verified someplace else. Sanitized with wp_kses_post_deep().
    $acf = isset( $_POST['acf'] ) ? \wp_kses_post_deep( \wp_unslash( $_POST['acf'] ) ) : array();
    if ( empty( $acf ) || ! is_array( $acf ) || ! isset( $acf['variations'] ) ) {
      return;
    }

    if ( ! is_array( $acf['variations'] ) || ! array_key_exists( $index, $acf['variations'] ) ) {
      return;
    }

    $unique_updates = array();
    $fields         = (array) $acf['variations'][ $index ];
    foreach ( $fields as $key => $val ) {
      if ( strpos( $key, 'field_' ) === false ) {
        // repeater fields need to be parsed separately.
        foreach ( $val as $repeater_key => $repeater_val ) {
          if ( ! array_key_exists( $repeater_key, $unique_updates ) || ! empty( $repeater_val ) ) {
            $unique_updates[ $repeater_key ] = $repeater_val;
          }
        }
      }
      // non-repeater fields can be parsed normally.
      // The repeater fields are repeated here, but empty.
      // This causes the repeater that was updated above to be cleared.
      elseif ( ! array_key_exists( $key, $unique_updates ) || ! empty( $val ) ) {
        $unique_updates[ $key ] = $val;
      }
    }

    foreach ( $unique_updates as $key => $val ) {
      \update_field( $key, $val, $variation_id );
    }
  }

  /**
   * Some JS things.
   */
  public static function rebind_js_events(): void {
    ?>
    <script type="text/javascript">
      (function($) {
        $(document).on('woocommerce_variations_loaded', function () {
          acf.do_action('append', $('#post'));
        });
      })(jQuery);
    </script>
    <?php
  }

}
