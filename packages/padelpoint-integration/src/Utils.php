<?php
/**
 * Common things used throughout the codebase.
 *
 * @package arpadel
 */

declare ( strict_types = 1 );

namespace PadelPoint;

/**
 * Class to wrap up as per PSR-4 standard.
 */
class Utils {

  /**
   * Logs on to debug.log file when corresponding flags are set to true.
   *
   * @param string $message The message to log.
   */
  public static function log( string $message ): void {
    if ( defined( 'WP_DEBUG' ) && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG && WP_DEBUG_LOG ) {
      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
      error_log( "[PadelPoint Integration]: $message" );
    }
  }

}
