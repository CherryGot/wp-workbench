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
    <form
      method="post"
      onsubmit="maybe_warn_about_categories_import(event)"
      action="<?php echo \esc_url( \admin_url( 'admin.php' ) ); ?>"
    >
      <?php
      $action = Constants::ACTION_SLUG_MANUAL_IMPORT;
      echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '" />';
      \wp_nonce_field( $action );

      printf(
        '<p>
          <input
            value="1"
            type="checkbox"
            id="reset_categories"
            name="reset_categories"
            style="vertical-align: text-bottom"
          />
          <label for="reset_categories">%s</label>
        </p>',
        \esc_html__(
          'While importing, reset the parent-child relationship among product categories.',
          'padelpoint-integration'
        )
      );

      \submit_button( __( 'Run Import', 'padelpoint-integration' ), 'secondary' ); ?>
    </form>
    <script>
      function maybe_warn_about_categories_import( event ) {
        const formData = new FormData( event.target );
        if ( formData.has( 'reset_categories' ) && '1' === formData.get('reset_categories') ) {
          const proceed = confirm( `
            <?php
            esc_html_e(
              // phpcs:ignore Generic.Files.LineLength.MaxExceeded
              "You are about to reset the parent-child relationships among product categories with this import. This will rewrite the existing relationships that some of the terms may have.\n\nAre you sure you want to proceed?",
              'padelpoint-integration'
            ); ?>
          `.trim() );

          if ( ! proceed ) {
            event.preventDefault();
            return false;
          }
        }

        return true;
      }
    </script>
  <?php endif; ?>
</div>
