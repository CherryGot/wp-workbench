<?php
/**
 * Functions to extend the Admin user interface.
 *
 * @package arpadel
 */

declare ( strict_types = 1 );

namespace PadelPoint\Admin;

use PadelPoint\Constants;
use PadelPoint\Product\Types;

/**
 * Wraps functions to follow PSR4 standard.
 */
class Extensions {

  /**
   * Loads translation domain for this plugin.
   */
  public static function load_translations(): void {
    $domain_path = \plugin_basename( PADELPOINT_INTEGRATION_PATH ) . '/languages';
    \load_plugin_textdomain( 'padelpoint-integration', false, $domain_path );
  }

  /**
   * Initialize our Admin extensions.
   */
  public static function init(): void {
    \add_options_page(
      __( 'PadelPoint Settings', 'padelpoint-integration' ),
      __( 'PadelPoint Settings', 'padelpoint-integration' ),
      'manage_options',
      'padelpoint-settings',
      function (): void {
        require_once 'Templates/settings-page.php';
      }
    );
  }

  /**
   * Fetch and store catalog when requested for manaul import.
   */
  public static function handle_manual_import_request(): void {
    if (
      ! isset( $_REQUEST['_wpnonce'] ) || empty( $_REQUEST['_wpnonce'] ) ||
      ! \wp_verify_nonce(
        \sanitize_key( \wp_unslash( $_REQUEST['_wpnonce'] ) ),
        Constants::ACTION_SLUG_MANUAL_IMPORT
      )
    ) {
      return;
    }

    \PadelPoint\Job::fetch_and_store_catalog();
    \wp_safe_redirect( \wp_get_referer() );
    exit();
  }

  /**
   * Registering settings and fields to read values from user input.
   */
  public static function register_setting_fields(): void {
    \register_setting( 'padelpoint-settings', Constants::SETTING_FIELD_LOGIN );
    \register_setting( 'padelpoint-settings', Constants::SETTING_FIELD_PASSWORD );

    \add_settings_field(
      'padelpoint-login-field',
      __( 'Username / Email', 'padelpoint-integration' ),
      self::form_id_field( Constants::SETTING_FIELD_LOGIN ),
      'padelpoint-settings'
    );
    \add_settings_field(
      'padelpoint-password-field',
      __( 'Password', 'padelpoint-integration' ),
      self::form_id_field( Constants::SETTING_FIELD_PASSWORD, 'password' ),
      'padelpoint-settings'
    );

    \add_settings_section( 'default', '', fn () => '', 'padelpoint-settings' );
  }

  /**
   * Generates a callback for registration of user inputs.
   *
   * @param string $setting The setting key to read value of.
   * @param string $type The type of input field.
   * @return \Closure A callable function to be used for setting field registration.
   */
  private static function form_id_field( string $setting, string $type = 'text' ): \Closure {
    return function () use ( $setting, $type ): void {
      $value = \get_option( $setting );
      echo '<input
        name="' . \esc_attr( $setting ) . '"
        type="' . \esc_attr( $type ) . '"
        value="' . \esc_attr( $value . '' ) . '"
        class="regular-text"
      />';
    };
  }

  /**
   * Adds an "Update Availability" button on the product edit screen.
   *
   * @param mixed $post The post currently being edited.
   */
  public static function add_update_availability_button( mixed $post ): void {
    $post = \get_post( $post );
    if ( 'product' !== $post->post_type ) {
      return;
    }

    $product       = \wc_get_product( $post );
    $product_types = array( Types\Article::SLUG, Types\Set::SLUG );
    if ( empty( $product ) || ! in_array( $product->get_type(), $product_types, true ) ) {
      return;
    } ?>

    <div id="update-availability-action">
      <button
        value="1"
        type="submit"
        class="button"
        id="update-availability"
        name="update-availability"
      >
        <?php echo esc_html__( 'Update Availability', 'padelpoint-integration' ); ?>
      </button>
      <style>
        #update-availability { margin-right: 4px }
      </style>
    </div>
    <?php
  }

  /**
   * Handles the scenario when "Update Availability" button is clicked on the edit page.
   *
   * @param int $post_id The post being edited.
   */
  public static function handle_update_availbility_submission( int $post_id ): void {
    $flag = false;
    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( isset( $_POST['update-availability'] ) ) {
      // phpcs:ignore WordPress.Security.NonceVerification.Missing
      $flag = \sanitize_key( \wp_unslash( $_POST['update-availability'] ) ) === '1';
    }

    if ( ! $flag ) {
      return;
    }

    $post = \get_post( $post_id );
    if ( 'product' !== $post->post_type ) {
      return;
    }

    $product       = \wc_get_product( $post );
    $product_types = array( Types\Article::SLUG, Types\Set::SLUG );
    if ( empty( $product ) || ! in_array( $product->get_type(), $product_types, true ) ) {
      return;
    }

    /*
    We want to unset this since post_updated hook is called everytime when wp_update_post is
    used. This leads to an infinite loop since this hook is called again and again. There
    must be some better way to do it, but for now, this works.
    */
    unset( $_POST['update-availability'] );
    \PadelPoint\Job::update_availabilities( array( $product ) );
  }

  /**
   * Displays status of import status on admin pages.
   */
  public static function show_notice(): void {
    $import_stats = \get_option( Constants::SETTING_FIELD_IMPORT_STATS, '' );
    $import_stats = ! empty( $import_stats ) ? $import_stats : array();
    if ( empty( $import_stats ) ) {
      return;
    }

    $notice = __( 'Latest import from PadelPoint API is finished.', 'padelpoint-integration' );

    if (
      $import_stats['articles'] < $import_stats['articles_count'] ||
      $import_stats['sets'] < $import_stats['sets_count']
    ) {
      $notice = sprintf(
        /* Translators: %d here are counts of categories, articles and sets. */
        __(
          // phpcs:ignore Generic.Files.LineLength.MaxExceeded
          'Importing contents from PadelPoint API. %1$d out of %2$d categories are available. Products processed: %3$d of %4$d articles, %5$d of %6$d sets.',
          'padelpoint-integration'
        ),
        $import_stats['categories'],
        $import_stats['categories_count'],
        $import_stats['articles'],
        $import_stats['articles_count'],
        $import_stats['sets'],
        $import_stats['sets_count']
      );
    } ?>

    <div class="notice notice-info is-dismissible">
      <p><?php echo esc_html( $notice ); ?></p>
    </div>
    <?php
  }

}
