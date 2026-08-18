<?php
/**
 * The base configuration for WordPress
 *
 * @package WordPress
 */

// IMPORTANT: this file needs to stay in-sync with the official WordPress Docker image.

// a helper function to lookup "env_FILE", "env", then fallback
if (!function_exists('getenv_docker')) {
	// https://github.com/docker-library/wordpress/issues/588
	function getenv_docker($env, $default) {
		if ($fileEnv = getenv($env . '_FILE')) {
			return rtrim(file_get_contents($fileEnv), "\r\n");
		}
		else if (($val = getenv($env)) !== false) {
			return $val;
		}
		else {
			return $default;
		}
	}
}

// ** Database settings ** //

define( 'DB_NAME', getenv_docker('WORDPRESS_DB_NAME', 'wordpress') );

define( 'DB_USER', getenv_docker('WORDPRESS_DB_USER', 'example username') );

define( 'DB_PASSWORD', getenv_docker('WORDPRESS_DB_PASSWORD', 'example password') );

define( 'DB_HOST', getenv_docker('WORDPRESS_DB_HOST', 'mysql') );

define( 'DB_CHARSET', getenv_docker('WORDPRESS_DB_CHARSET', 'utf8mb4') );

define( 'DB_COLLATE', getenv_docker('WORDPRESS_DB_COLLATE', '') );


// ** Authentication unique keys and salts ** //

define( 'AUTH_KEY',         getenv_docker('WORDPRESS_AUTH_KEY',         'b09ba37ce06a8b60138b501e719e567db8233ebf') );
define( 'SECURE_AUTH_KEY',  getenv_docker('WORDPRESS_SECURE_AUTH_KEY',  'a748c4b3c56f87d2c2a1b8340012b5d1a1fdbb1d') );
define( 'LOGGED_IN_KEY',    getenv_docker('WORDPRESS_LOGGED_IN_KEY',    'bb61289efe1e235f35f0a6b3d04303b663cd9ca8') );
define( 'NONCE_KEY',        getenv_docker('WORDPRESS_NONCE_KEY',        '99893974355764425f699dda68df349a8eca2084') );

define( 'AUTH_SALT',        getenv_docker('WORDPRESS_AUTH_SALT',        '920f11b98ed20e53e6a4aee43bbac6f6a97a7eff') );
define( 'SECURE_AUTH_SALT', getenv_docker('WORDPRESS_SECURE_AUTH_SALT', '8b3b40207d98deef8840b95199465f0506825fd5') );
define( 'LOGGED_IN_SALT',   getenv_docker('WORDPRESS_LOGGED_IN_SALT',   '26c70a8864ab1a91a5a5aa0a81951c1c21181273') );
define( 'NONCE_SALT',       getenv_docker('WORDPRESS_NONCE_SALT',       'fb49debac383f917e0c3bd58414193d88bec2d44') );


// ** WordPress table prefix ** //

$table_prefix = getenv_docker('WORDPRESS_TABLE_PREFIX', 'wp_');


// ** WordPress debugging mode ** //

define( 'WP_DEBUG', !!getenv_docker('WORDPRESS_DEBUG', '') );


/* Add any custom values between this line and the "stop editing" line. */


// -----------------------------------------------------------------------------
// Reverse proxy / HTTPS configuration
// -----------------------------------------------------------------------------

// ALB / reverse proxy
if (
	isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
	strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false
) {
	$_SERVER['HTTPS'] = 'on';
}

// CloudFront
if (
	isset($_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO']) &&
	$_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'] === 'https'
) {
	$_SERVER['HTTPS'] = 'on';
}


// -----------------------------------------------------------------------------
// Environment-specific WordPress configuration
// -----------------------------------------------------------------------------

// Allows values such as:
//
// define('WP_HOME', 'https://example.com');
// define('WP_SITEURL', 'https://example.com');
//
// to be provided through WORDPRESS_CONFIG_EXTRA.

if ($configExtra = getenv_docker('WORDPRESS_CONFIG_EXTRA', '')) {
	eval($configExtra);
}


// -----------------------------------------------------------------------------
// Fallback URL configuration
// -----------------------------------------------------------------------------

// If WP_HOME / WP_SITEURL were not explicitly defined through
// WORDPRESS_CONFIG_EXTRA, use the current HTTP host.
//
// This keeps the Docker setup flexible for local environments.

if (!defined('WP_HOME') || !defined('WP_SITEURL')) {

	$http_host = $_SERVER['HTTP_HOST'] ?? '';

	if ($http_host !== '') {

		$scheme = 'http';

		if (
			(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
			(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
				strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false) ||
			(isset($_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO']) &&
				$_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'] === 'https')
		) {
			$scheme = 'https';
		}

		if (!defined('WP_HOME')) {
			define('WP_HOME', $scheme . '://' . $http_host);
		}

		if (!defined('WP_SITEURL')) {
			define('WP_SITEURL', $scheme . '://' . $http_host);
		}
	}
}


/* That's all, stop editing! Happy publishing. */


/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}


/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';