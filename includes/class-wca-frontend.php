<?php
/**
 * Chargement conditionnel de l'animation côté visiteur.
 *
 * @package WPCursorAnimate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Décide du chargement et injecte scripts, styles et configuration.
 */
class WCA_Frontend {

	/**
	 * Accroche les hooks du frontend.
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Détermine si l'animation doit être chargée sur la requête courante.
	 *
	 * @return bool
	 */
	public function should_load() {
		$settings = WCA_Settings::get();

		$should = true;

		if ( empty( $settings['enabled'] ) || is_admin() || wp_is_mobile() ) {
			$should = false;
		} else {
			$mode = $settings['page_mode'];
			$ids  = $settings['page_ids'];

			if ( 'all' !== $mode ) {
				$page_id = (int) get_queried_object_id();

				if ( 'include' === $mode ) {
					$should = in_array( $page_id, $ids, true );
				} elseif ( 'exclude' === $mode ) {
					$should = ! in_array( $page_id, $ids, true );
				}
			}
		}

		/**
		 * Filtre la décision finale de chargement.
		 *
		 * @param bool  $should   Décision calculée.
		 * @param array $settings Réglages courants.
		 */
		return (bool) apply_filters( 'wca_should_load', $should, $settings );
	}

	/**
	 * Ajoute la classe body quand l'animation est active.
	 *
	 * @param string[] $classes Classes existantes.
	 * @return string[]
	 */
	public function body_class( $classes ) {
		if ( $this->should_load() ) {
			$classes[] = 'wca-active';
		}

		return $classes;
	}

	/**
	 * Enfile CSS/JS et transmet la configuration au script.
	 */
	public function enqueue_assets() {
		if ( ! $this->should_load() ) {
			return;
		}

		$settings = WCA_Settings::get();

		wp_enqueue_style(
			'wca-cursor',
			WCA_PLUGIN_URL . 'public/css/cursor-animate.css',
			array(),
			WCA_VERSION
		);

		wp_enqueue_script(
			'wca-cursor',
			WCA_PLUGIN_URL . 'public/js/cursor-animate.js',
			array(),
			WCA_VERSION,
			true
		);

		$intensity_map = array(
			'low'    => 0.35,
			'medium' => 0.7,
			'high'   => 1.5,
		);
		$intensity     = isset( $intensity_map[ $settings['smoke_intensity'] ] ) ? $intensity_map[ $settings['smoke_intensity'] ] : 0.7;

		wp_localize_script(
			'wca-cursor',
			'wcaConfig',
			array(
				'imageUrl'       => WCA_Settings::cursor_image_url( $settings ),
				'size'           => (int) $settings['size'],
				'smoothing'      => (float) $settings['smoothing'],
				'smokeEnabled'   => (bool) $settings['smoke_enabled'],
				'smokeIntensity' => (float) $intensity,
				'nativeOnClickable' => (bool) $settings['native_on_clickable'],
			)
		);
	}
}
