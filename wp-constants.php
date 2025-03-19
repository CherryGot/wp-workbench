<?php
/**
 * The wp-config file for the local WordPress setup. The values here are hard coded since it's just
 * meant to be used for development purposes only.
 *
 * @package cherrygot/wp-workbench
 */

declare ( strict_types = 1 );

/** The name of the database for WordPress */
if ( ! defined( 'DB_NAME' ) ) {
  define( 'DB_NAME', 'wordpress' );
}

/** Database username */
if ( ! defined( 'DB_USER' ) ) {
  define( 'DB_USER', 'wordpress' );
}

/** Database password */
if ( ! defined( 'DB_PASSWORD' ) ) {
  define( 'DB_PASSWORD', 'wordpress' );
}

/** Database hostname */
if ( ! defined( 'DB_HOST' ) ) {
  define( 'DB_HOST', 'database' );
}

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY', 'put your unique phrase here' );
define( 'SECURE_AUTH_KEY', 'put your unique phrase here' );
define( 'LOGGED_IN_KEY', 'put your unique phrase here' );
define( 'NONCE_KEY', 'put your unique phrase here' );
define( 'AUTH_SALT', 'put your unique phrase here' );
define( 'SECURE_AUTH_SALT', 'put your unique phrase here' );
define( 'LOGGED_IN_SALT', 'put your unique phrase here' );
define( 'NONCE_SALT', 'put your unique phrase here' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
if ( empty( $table_prefix ) ) {
  $table_prefix = 'wp_';
}

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

/* Add any custom values between this line and the "stop editing" line. */

define( 'FS_METHOD', 'direct' );
define( 'WP_MEMORY_LIMIT', '384M' );

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
  define( 'ABSPATH', __DIR__ . '/' );
}
