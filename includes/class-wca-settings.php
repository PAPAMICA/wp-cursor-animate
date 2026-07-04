<?php
/**
 * Gestion des réglages via la Settings API.
 *
 * @package WPCursorAnimate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enregistre, valide et expose les réglages du plugin.
 */
class WCA_Settings {

	const OPTION_KEY = 'wca_settings';

	/**
	 * Réglages par défaut.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'enabled'         => 1,
			'page_mode'       => 'all',
			'page_ids'        => array(),
			'cursor'          => 'kart',
			'custom_image'    => '',
			'size'            => 48,
			'smoothing'       => 0.18,
			'smoke_enabled'   => 1,
			'smoke_intensity' => 'medium',
		);
	}

	/**
	 * Récupère les réglages fusionnés avec les valeurs par défaut.
	 *
	 * @return array<string, mixed>
	 */
	public static function get() {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return wp_parse_args( $stored, self::defaults() );
	}

	/**
	 * Enregistre le réglage unique et ses callbacks de nettoyage.
	 */
	public function register() {
		register_setting(
			'wca_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Nettoie et valide l'ensemble des réglages soumis.
	 *
	 * @param mixed $input Valeurs brutes issues du formulaire.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ) {
		$defaults = self::defaults();

		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$clean = array();

		$clean['enabled']       = empty( $input['enabled'] ) ? 0 : 1;
		$clean['smoke_enabled'] = empty( $input['smoke_enabled'] ) ? 0 : 1;

		$mode                = isset( $input['page_mode'] ) ? (string) $input['page_mode'] : $defaults['page_mode'];
		$clean['page_mode']  = in_array( $mode, array( 'all', 'include', 'exclude' ), true ) ? $mode : $defaults['page_mode'];

		$ids               = isset( $input['page_ids'] ) && is_array( $input['page_ids'] ) ? $input['page_ids'] : array();
		$clean['page_ids'] = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

		$cursor           = isset( $input['cursor'] ) ? (string) $input['cursor'] : $defaults['cursor'];
		$clean['cursor']  = in_array( $cursor, array_keys( self::available_cursors() ), true ) ? $cursor : $defaults['cursor'];

		$clean['custom_image'] = isset( $input['custom_image'] ) ? esc_url_raw( trim( (string) $input['custom_image'] ) ) : '';

		$size          = isset( $input['size'] ) ? absint( $input['size'] ) : $defaults['size'];
		$clean['size'] = max( 24, min( 96, $size ) );

		$smoothing          = isset( $input['smoothing'] ) ? (float) $input['smoothing'] : $defaults['smoothing'];
		$clean['smoothing'] = max( 0.05, min( 0.5, round( $smoothing, 2 ) ) );

		$intensity                = isset( $input['smoke_intensity'] ) ? (string) $input['smoke_intensity'] : $defaults['smoke_intensity'];
		$clean['smoke_intensity'] = in_array( $intensity, array( 'low', 'medium', 'high' ), true ) ? $intensity : $defaults['smoke_intensity'];

		return $clean;
	}

	/**
	 * Liste des types de curseurs disponibles.
	 *
	 * Extensible via le filtre wca_cursors pour de futurs curseurs.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function available_cursors() {
		$cursors = array(
			'kart' => array(
				'label' => __( 'Kart', 'wp-cursor-animate' ),
				'image' => WCA_PLUGIN_URL . 'assets/go-kart.png',
			),
		);

		/**
		 * Filtre la liste des curseurs disponibles.
		 *
		 * @param array $cursors Tableau associatif slug => [label, image].
		 */
		return apply_filters( 'wca_cursors', $cursors );
	}

	/**
	 * URL de l'image du curseur en fonction des réglages.
	 *
	 * @param array<string, mixed> $settings Réglages courants.
	 * @return string
	 */
	public static function cursor_image_url( array $settings ) {
		if ( ! empty( $settings['custom_image'] ) ) {
			return $settings['custom_image'];
		}

		$cursors = self::available_cursors();
		$key     = isset( $settings['cursor'] ) ? $settings['cursor'] : 'kart';

		if ( isset( $cursors[ $key ]['image'] ) ) {
			return $cursors[ $key ]['image'];
		}

		return WCA_PLUGIN_URL . 'assets/go-kart.png';
	}
}
