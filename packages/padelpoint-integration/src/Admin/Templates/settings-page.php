<?php
/**
 * The template to render the padelpoint settings page.
 *
 * @package arpadel
 */

declare ( strict_types = 1 );

use PadelPoint\Constants; ?>

<div class="wrap">
  <h1><?php esc_html_e( 'PadelPoint Settings', 'padelpoint-integration' ); ?></h1>
  <p>
    <?php
    esc_html_e(
      'Enter PadelPoint credentials and other settings here to make the API work.',
      'padelpoint-integration'
    ); ?>
  </p>

  <form method="post" action="options.php">
    <?php
    \settings_fields( 'padelpoint-settings' );
    \do_settings_sections( 'padelpoint-settings' );
    \submit_button(); ?>
  </form>

  <h2><?php esc_html_e( 'Manual Catalog Import', 'padelpoint-integration' ); ?></h2>
  <p>
    <?php
    esc_html_e(
      // phpcs:ignore Generic.Files.LineLength.MaxExceeded
      'For troubleshooting purposes or when automatic updates fail to fetch recent changes, you may need to manually initiate the catalog import process.',
      'padelpoint-integration'
    ); ?>
  </p>

  <?php
  $import_already_running = false;

  $import_stats = \get_option( Constants::SETTING_FIELD_IMPORT_STATS, '' );
  $import_stats = ! empty( $import_stats ) ? $import_stats : array();
  if ( ! empty( $import_stats ) ) {
    if (
      $import_stats['articles'] < $import_stats['articles_count'] ||
      $import_stats['sets'] < $import_stats['sets_count']
    ) {
      $import_already_running = true;
    }
  }

  if ( $import_already_running ) : ?>
    <p>
      <?php
      esc_html_e(
        'An import is already in the process, can not initiate a manual import right now.',
        'padelpoint-integration'
      ); ?>
    </p>
  <?php else : ?>
    <form method="post" action="<?php echo \esc_url( \admin_url( 'admin.php' ) ); ?>">
      <?php
      $action = Constants::ACTION_SLUG_MANUAL_IMPORT;
      echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '" />';
      \wp_nonce_field( $action );
      \submit_button( __( 'Run Import', 'padelpoint-integration' ), 'secondary' ); ?>
    </form>
  <?php endif; ?>
</div>
