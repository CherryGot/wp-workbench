<?php
/**
 * The template to render the padelpoint settings page.
 *
 * @package arpadel
 */

declare ( strict_types = 1 );

use PadelPoint\Constants;

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
?>

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

  <h2><?php esc_html_e( 'Tools' ); ?></h2>

  <?php if ( $import_already_running ) : ?>
    <div style="display: flex; align-items: center; justify-content: space-between">
  <?php else : ?>
    <form
      method="post"
      onsubmit="maybe_warn_about_categories_import(event)"
      action="<?php echo \esc_url( \admin_url( 'admin.php' ) ); ?>"
      style="display: flex; align-items: center; justify-content: space-between"
    >
  <?php endif; ?>

  <div>
    <h4><?php esc_html_e( 'Manual Catalog Import', 'padelpoint-integration' ); ?></h4>
    <p>
      <?php
      esc_html_e(
        // phpcs:ignore Generic.Files.LineLength.MaxExceeded
        'For troubleshooting purposes or when automatic updates fail to fetch recent changes, you may need to manually initiate the catalog import process.',
        'padelpoint-integration'
      ); ?>
    </p>

    <?php if ( $import_already_running ) : ?>
      <p>
        <?php
        esc_html_e(
          'An import is already in the process, can not initiate a manual import right now.',
          'padelpoint-integration'
        ); ?>
      </p>
    <?php else : // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterOpen
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
        \esc_html(
          __(
            'While importing, reset the parent-child relationship among product categories.',
            'padelpoint-integration'
          )
        )
      );
    endif; ?>
  </div>

  <div>
    <?php
    \submit_button(
      __( 'Run Import', 'padelpoint-integration' ),
      'secondary',
      'submit',
      true,
      $import_already_running ? array( 'disabled' => true ) : array()
    );
    ?>
  </div>

  <?php if ( $import_already_running ) : ?>
    </div>
  <?php else : ?>
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

  <hr/>

  <?php if ( $import_already_running ) : ?>
    <div style="display: flex; align-items: center; justify-content: space-between">
  <?php else : ?>
    <form
      method="post"
      onsubmit="maybe_warn_about_categories_import(event)"
      action="<?php echo \esc_url( \admin_url( 'admin.php' ) ); ?>"
      style="display: flex; align-items: center; justify-content: space-between"
    >
  <?php endif; ?>

  <div>
    <h4><?php esc_html_e( 'Synchronize PadelPoint Sets', 'padelpoint-integration' ); ?></h4>
    <p>
      <?php
      esc_html_e(
        'In the event when the Sets show "Read More" button on UI, sync them to fix the issue.',
        'padelpoint-integration'
      ); ?>
    </p>

    <?php if ( $import_already_running ) : ?>
      <p>
        <?php
        esc_html_e(
          'An import is already in the process, please wait for it to finish.',
          'padelpoint-integration'
        ); ?>
      </p>
    <?php else : // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterOpen
      $action = Constants::ACTION_SLUG_SYNCRONIZE_SETS;
      echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '" />';
      \wp_nonce_field( $action );
    endif; ?>
  </div>

  <div>
    <?php
    \submit_button(
      __( 'Sync Sets', 'padelpoint-integration' ),
      'secondary',
      'submit',
      true,
      $import_already_running ? array( 'disabled' => true ) : array()
    );
    ?>
  </div>

  <?php if ( $import_already_running ) : ?>
    </div>
  <?php else : ?>
    </form>
  <?php endif; ?>

  <hr/>
</div>
