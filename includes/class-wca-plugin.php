<?php
/**
 * Orchestrateur principal du plugin.
 *
 * @package WPCursorAnimate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton qui câble les différentes parties du plugin.
 */
class WCA_Plugin {

	/**
	 * Instance unique.
	 *
	 * @var WCA_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Retourne l'instance unique.
	 *
	 * @return WCA_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Câble les composants selon le contexte.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		( new WCA_Frontend() )->register();

		if ( is_admin() ) {
			( new WCA_Admin( new WCA_Settings() ) )->register();
		}
	}

	/**
	 * Charge les traductions.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'wp-cursor-animate',
			false,
			dirname( plugin_basename( WCA_PLUGIN_FILE ) ) . '/languages'
		);
	}
}
