<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'rhrrist_wp872' );

/** Database username */
define( 'DB_USER', 'rhrrist_wp872' );

/** Database password */
define( 'DB_PASSWORD', ']8S4(O7iUp' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         '8camjmq8arljzzc64noegjkiixxah9ml62sm2kk18cargwr2uydqqm8hoxuagyqh' );
define( 'SECURE_AUTH_KEY',  'nfkpgsazqagpeyejsfulul5myhr0zshtvlfcnq4kmi4kqvtreaugf7vusdwp13lb' );
define( 'LOGGED_IN_KEY',    'e6yrb8c6ciszhvs21gao4lrpk8pzhy8ygojmb7zwbckff2c3ndgaxvcqnekhyg1k' );
define( 'NONCE_KEY',        'dcsczvhf9mzkxicawvbhslimnqgezop7unfhqvx8x3yfitrlmsoqwvz0dvx65qbs' );
define( 'AUTH_SALT',        '8ip2gdqaarfhymc4s4dy2xx7meeyznrmqe5bq28xduyvmklvr6s8yl9b9fu5phmm' );
define( 'SECURE_AUTH_SALT', 'g0lqk8vk5gowkx4vogugy2jravhrhltihaxqrusfxqj2p7ztgmat8ioewfusewtx' );
define( 'LOGGED_IN_SALT',   'dwijov2a4najyrzoliacv53hgpnudnt5mmgojpgh2xfm9ii8yceuarwe8xjvwvt0' );
define( 'NONCE_SALT',       'scvgjqzsfimetew470g7lb6k95weu0uslgbg0uwmx6zacqx3cgiorfb6hcrujrn9' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wpeb_';

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

/* Add any custom values between this line and the "stop editing" line. */

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
