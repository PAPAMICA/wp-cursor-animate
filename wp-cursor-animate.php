<?php
/**
 * Plugin Name:       Curseur animé Kart
 * Plugin URI:        https://github.com/PAPAMICA/wp-cursor-animate
 * Description:       Remplace le curseur des visiteurs par un kart animé qui suit la direction du mouvement, avec un effet de fumée. Page de réglages pour activer globalement ou par pages et personnaliser le curseur.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            PAPAMICA
 * Author URI:        https://github.com/PAPAMICA
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-cursor-animate
 * Domain Path:       /languages
 *
 * @package WPCursorAnimate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCA_VERSION', '1.0.0' );
define( 'WCA_PLUGIN_FILE', __FILE__ );
define( 'WCA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoloader minimal pour les classes WCA_*.
 *
 * Les classes admin vivent dans admin/, les autres dans includes/.
 */
spl_autoload_register(
	static function ( $class ) {
		if ( 0 !== strpos( $class, 'WCA_' ) ) {
			return;
		}

		$slug     = 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
		$is_admin = 'WCA_Admin' === $class;
		$dir      = $is_admin ? WCA_PLUGIN_DIR . 'admin/' : WCA_PLUGIN_DIR . 'includes/';
		$path     = $dir . $slug;

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);

/**
 * Bootstrap du plugin une fois WordPress chargé.
 */
add_action(
	'plugins_loaded',
	static function () {
		WCA_Plugin::instance();
	}
);
