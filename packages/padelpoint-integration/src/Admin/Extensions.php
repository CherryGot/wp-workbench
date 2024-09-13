<?php
/**
 * Functions to extend the Admin user interface.
 *
 * @package arpadel
 */

declare ( strict_types = 1 );

namespace PadelPoint\Admin;

/**
 * Wraps functions to follow PSR4 standard.
 */
class Extensions {

  /**
   * Initialize our Admin extensions.
   */
  public static function init(): void {
    \add_options_page(
      \__( 'PadelPoint Settings', 'padelpoint-integration' ),
      \__( 'PadelPoint Settings', 'padelpoint-integration' ),
      'manage_options',
      'padelpoint-settings',
      array( static::class, 'render_settings_page' )
    );
  }

  /**
   * Renders PadelPoint Settings page.
   */
  public static function render_settings_page(): void {
    ?>
    <div class="wrap">
      <h1><?php \esc_html_e( 'PadelPoint Settings', 'padelpoint-integration' ); ?></h1>
      <p>
        <?php
        \esc_html_e(
          'Enter PadelPoint credentials and other settings here to make the API work.',
          'padelpoint-integration'
        );
        ?>
      </p>

      <form method="post" action="options.php">
        <?php
        \settings_fields( 'padelpoint-settings' );
        \do_settings_sections( 'padelpoint-settings' );
        \submit_button();
        ?>
      </form>
    </div>
    <?php
  }

  /**
   * Registering settings and fields to read values from user input.
   */
  public static function register_setting_fields(): void {
    \register_setting( 'padelpoint-settings', 'padelpoint-login' );
    \register_setting( 'padelpoint-settings', 'padelpoint-password' );

    \add_settings_field(
      'padelpoint-login-field',
      \__( 'Username / Email', 'padelpoint-integration' ),
      self::form_id_field( 'padelpoint-login' ),
      'padelpoint-settings'
    );
    \add_settings_field(
      'padelpoint-password-field',
      \__( 'Password', 'padelpoint-integration' ),
      self::form_id_field( 'padelpoint-password', 'password' ),
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

}
