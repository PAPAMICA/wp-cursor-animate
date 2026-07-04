<?php
/**
 * Page de réglages de l'administration.
 *
 * @package WPCursorAnimate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Construit la page Réglages → Curseur animé.
 */
class WCA_Admin {

	const PAGE_SLUG = 'wp-cursor-animate';

	/**
	 * Gestion des réglages.
	 *
	 * @var WCA_Settings
	 */
	private $settings;

	/**
	 * Constructeur.
	 *
	 * @param WCA_Settings $settings Instance de réglages.
	 */
	public function __construct( WCA_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Accroche les hooks admin.
	 */
	public function register() {
		add_action( 'admin_init', array( $this->settings, 'register' ) );
		add_action( 'admin_init', array( $this, 'register_fields' ) );
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Ajoute la page sous le menu Réglages.
	 */
	public function add_menu() {
		add_options_page(
			__( 'Curseur animé', 'wp-cursor-animate' ),
			__( 'Curseur animé', 'wp-cursor-animate' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enfile la Media Library pour le sélecteur d'image personnalisée.
	 *
	 * @param string $hook Suffixe de la page courante.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_media();
	}

	/**
	 * Déclare sections et champs de la Settings API.
	 */
	public function register_fields() {
		add_settings_section(
			'wca_section_general',
			__( 'Général', 'wp-cursor-animate' ),
			'__return_false',
			self::PAGE_SLUG
		);

		add_settings_field(
			'wca_enabled',
			__( 'Activation globale', 'wp-cursor-animate' ),
			array( $this, 'field_enabled' ),
			self::PAGE_SLUG,
			'wca_section_general'
		);

		add_settings_section(
			'wca_section_targeting',
			__( 'Ciblage des pages', 'wp-cursor-animate' ),
			'__return_false',
			self::PAGE_SLUG
		);

		add_settings_field(
			'wca_page_mode',
			__( 'Mode de ciblage', 'wp-cursor-animate' ),
			array( $this, 'field_page_mode' ),
			self::PAGE_SLUG,
			'wca_section_targeting'
		);

		add_settings_field(
			'wca_page_ids',
			__( 'Pages concernées', 'wp-cursor-animate' ),
			array( $this, 'field_page_ids' ),
			self::PAGE_SLUG,
			'wca_section_targeting'
		);

		add_settings_section(
			'wca_section_cursor',
			__( 'Curseur', 'wp-cursor-animate' ),
			'__return_false',
			self::PAGE_SLUG
		);

		add_settings_field(
			'wca_cursor',
			__( 'Type de curseur', 'wp-cursor-animate' ),
			array( $this, 'field_cursor' ),
			self::PAGE_SLUG,
			'wca_section_cursor'
		);

		add_settings_field(
			'wca_custom_image',
			__( 'Image personnalisée', 'wp-cursor-animate' ),
			array( $this, 'field_custom_image' ),
			self::PAGE_SLUG,
			'wca_section_cursor'
		);

		add_settings_section(
			'wca_section_options',
			__( 'Options du curseur', 'wp-cursor-animate' ),
			'__return_false',
			self::PAGE_SLUG
		);

		add_settings_field(
			'wca_size',
			__( 'Taille', 'wp-cursor-animate' ),
			array( $this, 'field_size' ),
			self::PAGE_SLUG,
			'wca_section_options'
		);

		add_settings_field(
			'wca_smoothing',
			__( 'Fluidité', 'wp-cursor-animate' ),
			array( $this, 'field_smoothing' ),
			self::PAGE_SLUG,
			'wca_section_options'
		);

		add_settings_field(
			'wca_smoke_enabled',
			__( 'Effet de fumée', 'wp-cursor-animate' ),
			array( $this, 'field_smoke_enabled' ),
			self::PAGE_SLUG,
			'wca_section_options'
		);

		add_settings_field(
			'wca_smoke_intensity',
			__( 'Intensité de la fumée', 'wp-cursor-animate' ),
			array( $this, 'field_smoke_intensity' ),
			self::PAGE_SLUG,
			'wca_section_options'
		);

		add_settings_field(
			'wca_native_on_clickable',
			__( 'Curseur natif sur éléments cliquables', 'wp-cursor-animate' ),
			array( $this, 'field_native_on_clickable' ),
			self::PAGE_SLUG,
			'wca_section_options'
		);
	}

	/**
	 * Nom de champ helper pour l'option en tableau.
	 *
	 * @param string $key Clé de réglage.
	 * @return string
	 */
	private function name( $key ) {
		return WCA_Settings::OPTION_KEY . '[' . $key . ']';
	}

	/**
	 * Rendu de la page complète.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'wca_settings_group' );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Champ : activation globale.
	 */
	public function field_enabled() {
		$settings = WCA_Settings::get();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->name( 'enabled' ) ); ?>" value="1" <?php checked( 1, $settings['enabled'] ); ?> />
			<?php esc_html_e( 'Activer le curseur animé sur le site.', 'wp-cursor-animate' ); ?>
		</label>
		<?php
	}

	/**
	 * Champ : mode de ciblage.
	 */
	public function field_page_mode() {
		$settings = WCA_Settings::get();
		$modes    = array(
			'all'     => __( 'Toutes les pages', 'wp-cursor-animate' ),
			'include' => __( 'Uniquement les pages sélectionnées', 'wp-cursor-animate' ),
			'exclude' => __( 'Toutes les pages sauf celles sélectionnées', 'wp-cursor-animate' ),
		);
		?>
		<select name="<?php echo esc_attr( $this->name( 'page_mode' ) ); ?>">
			<?php foreach ( $modes as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['page_mode'], $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Champ : sélection multiple des contenus ciblés.
	 */
	public function field_page_ids() {
		$settings = WCA_Settings::get();
		$selected = (array) $settings['page_ids'];

		$items = get_posts(
			array(
				'post_type'      => array( 'page', 'post' ),
				'post_status'    => 'publish',
				'numberposts'    => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'suppress_filters' => false,
			)
		);
		?>
		<select name="<?php echo esc_attr( $this->name( 'page_ids' ) ); ?>[]" multiple size="10" style="min-width:320px;">
			<?php foreach ( $items as $item ) : ?>
				<option value="<?php echo esc_attr( $item->ID ); ?>" <?php selected( in_array( (int) $item->ID, array_map( 'intval', $selected ), true ) ); ?>>
					<?php echo esc_html( $item->post_title ) . ' (' . esc_html( $item->post_type ) . ')'; ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php esc_html_e( 'Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs éléments. Utilisé uniquement si le mode ci-dessus n\'est pas « Toutes les pages ».', 'wp-cursor-animate' ); ?></p>
		<?php
	}

	/**
	 * Champ : type de curseur.
	 */
	public function field_cursor() {
		$settings = WCA_Settings::get();
		$cursors  = WCA_Settings::available_cursors();
		?>
		<select name="<?php echo esc_attr( $this->name( 'cursor' ) ); ?>">
			<?php foreach ( $cursors as $key => $data ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['cursor'], $key ); ?>>
					<?php echo esc_html( $data['label'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Champ : image personnalisée via Media Library.
	 */
	public function field_custom_image() {
		$settings = WCA_Settings::get();
		$value    = $settings['custom_image'];
		?>
		<div class="wca-media-field">
			<input type="url" class="regular-text" id="wca_custom_image" name="<?php echo esc_attr( $this->name( 'custom_image' ) ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="https://…" />
			<button type="button" class="button" id="wca_custom_image_select"><?php esc_html_e( 'Choisir une image', 'wp-cursor-animate' ); ?></button>
			<button type="button" class="button" id="wca_custom_image_clear"><?php esc_html_e( 'Réinitialiser', 'wp-cursor-animate' ); ?></button>
			<p class="description"><?php esc_html_e( 'Laissez vide pour utiliser le type de curseur ci-dessus. Une image orientée vers la droite donnera la meilleure rotation.', 'wp-cursor-animate' ); ?></p>
			<div class="wca-media-preview">
				<?php if ( $value ) : ?>
					<img src="<?php echo esc_url( $value ); ?>" alt="" style="max-width:64px;height:auto;" />
				<?php endif; ?>
			</div>
		</div>
		<script>
		( function () {
			var frame;
			var select = document.getElementById( 'wca_custom_image_select' );
			var clear = document.getElementById( 'wca_custom_image_clear' );
			var input = document.getElementById( 'wca_custom_image' );
			var preview = document.querySelector( '.wca-media-preview' );

			if ( ! select || ! window.wp || ! window.wp.media ) {
				return;
			}

			select.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				if ( frame ) {
					frame.open();
					return;
				}
				frame = window.wp.media( {
					title: '<?php echo esc_js( __( 'Choisir une image de curseur', 'wp-cursor-animate' ) ); ?>',
					button: { text: '<?php echo esc_js( __( 'Utiliser cette image', 'wp-cursor-animate' ) ); ?>' },
					multiple: false
				} );
				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					input.value = attachment.url;
					preview.innerHTML = '<img src="' + attachment.url + '" alt="" style="max-width:64px;height:auto;" />';
				} );
				frame.open();
			} );

			clear.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				input.value = '';
				preview.innerHTML = '';
			} );
		} )();
		</script>
		<?php
	}

	/**
	 * Champ : taille du curseur.
	 */
	public function field_size() {
		$settings = WCA_Settings::get();
		?>
		<input type="range" min="24" max="96" step="1" name="<?php echo esc_attr( $this->name( 'size' ) ); ?>" value="<?php echo esc_attr( $settings['size'] ); ?>" oninput="this.nextElementSibling.textContent = this.value + ' px';" />
		<span><?php echo esc_html( $settings['size'] . ' px' ); ?></span>
		<?php
	}

	/**
	 * Champ : fluidité (facteur de lissage).
	 */
	public function field_smoothing() {
		$settings = WCA_Settings::get();
		?>
		<input type="range" min="0.05" max="0.5" step="0.01" name="<?php echo esc_attr( $this->name( 'smoothing' ) ); ?>" value="<?php echo esc_attr( $settings['smoothing'] ); ?>" oninput="this.nextElementSibling.textContent = this.value;" />
		<span><?php echo esc_html( $settings['smoothing'] ); ?></span>
		<p class="description"><?php esc_html_e( 'Valeur basse = kart plus « traînant », valeur haute = kart plus réactif.', 'wp-cursor-animate' ); ?></p>
		<?php
	}

	/**
	 * Champ : activation de la fumée.
	 */
	public function field_smoke_enabled() {
		$settings = WCA_Settings::get();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->name( 'smoke_enabled' ) ); ?>" value="1" <?php checked( 1, $settings['smoke_enabled'] ); ?> />
			<?php esc_html_e( 'Afficher un panache de fumée derrière le kart lors du mouvement.', 'wp-cursor-animate' ); ?>
		</label>
		<?php
	}

	/**
	 * Champ : intensité de la fumée.
	 */
	public function field_smoke_intensity() {
		$settings   = WCA_Settings::get();
		$intensities = array(
			'low'    => __( 'Faible', 'wp-cursor-animate' ),
			'medium' => __( 'Moyenne', 'wp-cursor-animate' ),
			'high'   => __( 'Forte', 'wp-cursor-animate' ),
		);
		?>
		<select name="<?php echo esc_attr( $this->name( 'smoke_intensity' ) ); ?>">
			<?php foreach ( $intensities as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['smoke_intensity'], $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Champ : curseur natif au survol d'éléments cliquables.
	 */
	public function field_native_on_clickable() {
		$settings = WCA_Settings::get();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->name( 'native_on_clickable' ) ); ?>" value="1" <?php checked( 1, $settings['native_on_clickable'] ); ?> />
			<?php esc_html_e( 'Rétablir le curseur normal (pointeur, texte, etc.) au survol des liens, boutons et champs de formulaire.', 'wp-cursor-animate' ); ?>
		</label>
		<?php
	}
}
