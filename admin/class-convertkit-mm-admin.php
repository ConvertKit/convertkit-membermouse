<?php
/**
 * Admin class.
 *
 * @package ConvertKit_MM
 * @author ConvertKit
 */

/**
 * Registers the admin settings screen and saves settings.
 *
 * @package ConvertKit_MM
 * @author ConvertKit
 */
class ConvertKit_MM_Admin {

	/**
	 * Holds the Settings Page Slug
	 *
	 * @since   1.3.0
	 *
	 * @var     string
	 */
	const SETTINGS_PAGE_SLUG = 'convertkit-mm';

	/**
	 * Options table key
	 *
	 * @since   1.3.0
	 *
	 * @var string
	 */
	public $settings_key = CONVERTKIT_MM_NAME . '-options';

	/**
	 * Initialize the class
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		// Register settings screen.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_convertkit-membermouse/convertkit-membermouse.php', array( $this, 'settings_link' ) );

	}

	/**
	 * Enqueue CSS for the Settings Screens at Settings > ConvertKit
	 *
	 * @since   1.3.0
	 *
	 * @param   string $hook   Hook.
	 */
	public function enqueue_styles( $hook ) {

		// Bail if we are not on the Settings screen.
		if ( $hook !== 'settings_page_' . self::SETTINGS_PAGE_SLUG ) {
			return;
		}

		// Always enqueue Settings CSS, as this is used for the UI across all settings sections.
		wp_enqueue_style( 'convertkit-admin-settings', CONVERTKIT_MM_URL . 'resources/backend/css/settings.css', array(), CONVERTKIT_MM_VERSION );

	}

	/**
	 * Register settings sections and fields on the settings screen.
	 *
	 * @since       1.0.0
	 */
	public function register_settings() {

		// Register settings store.
		register_setting(
			CONVERTKIT_MM_NAME . '-options',
			CONVERTKIT_MM_NAME . '-options',
			array( $this, 'validate_options' )
		);

		// Register "General" settings section and fields.
		add_settings_section(
			CONVERTKIT_MM_NAME . '-display-options',
			__( 'General', 'convertkit-mm' ),
			array( $this, 'display_section_introduction' ),
			CONVERTKIT_MM_NAME,
			array(
				'description' => esc_html__( 'Add your API key below, and then choose when to tag subscribers based on MemberMouse actions.', 'convertkit-mm' ),
			)
		);
		add_settings_field(
			'api-key',
			__( 'API Key', 'convertkit-mm' ),
			array( $this, 'text_field_callback' ),
			CONVERTKIT_MM_NAME,
			CONVERTKIT_MM_NAME . '-display-options',
			array(
				'name'        => 'api-key',
				'label_for'   => 'api-key',
				'css_classes' => array( 'widefat' ),
				'description' => array(
					'<a href="https://app.convertkit.com/account/edit" target="_blank">' . esc_html__( 'Get your ConvertKit API Key', 'convertkit-mm' ) . '</a>',
				),
			)
		);
		add_settings_field(
			'debug',
			__( 'Debug', 'convertkit-mm' ),
			array( $this, 'checkbox_field_callback' ),
			CONVERTKIT_MM_NAME,
			CONVERTKIT_MM_NAME . '-display-options',
			array(
				'name'        => 'debug',
				'value'       => '1',
				'checked'     => true,
				'label'       => __( 'Log requests to file and output browser console messages.', 'convertkit-mm' ),
				'label_for'   => 'debug',
				'description' => array(
					__( 'You can ignore this unless you\'re working with our support team to resolve an issue. Decheck this option to improve performance.', 'convertkit-mm' ),
				),
			)
		);

		// If the API hasn't been configured, don't display any further settings, as
		// we cannot fetch tags from the API to populate dropdown fields.
		if ( empty( convertkit_mm_get_option( 'api-key' ) ) ) {
			return;
		}

		// Initialize API.
		$api = new ConvertKit_MM_API( convertkit_mm_get_option( 'api-key' ) );

		// Get all tags from ConvertKit.
		$tags = $api->get_tags();

		// Bail if no tags, as there are no further configuration settings without having ConvertKit Tags.
		if ( is_null( $tags ) ) {
			return;
		}

		// Regsiter "Tagging: Membership Levels" settings section and fields.
		add_settings_section(
			CONVERTKIT_MM_NAME . '-ck-mapping-membership-levels',
			__( 'Tagging: Membership Levels', 'convertkit-mm' ),
			array( $this, 'display_section_introduction' ),
			CONVERTKIT_MM_NAME,
			array(
				'before_section' => '<div class="section">',
				'after_section'  => '</div>',
				'description'    => esc_html__( 'For each MemberMouse Membership Level, assign a ConvertKit Tag that you wish to be assigned to members of that level.', 'convertkit-mm' ),
			)
		);
		$levels = $this->get_mm_membership_levels();
		foreach ( $levels as $key => $name ) {
			add_settings_field(
				'convertkit-mapping-' . $key,
				$name,
				array( $this, 'mapping_fields_callback' ),
				CONVERTKIT_MM_NAME,
				CONVERTKIT_MM_NAME . '-ck-mapping-membership-levels',
				array(
					'key'          => $key,

					'name'         => 'convertkit-mapping-' . $key,
					'value'        => convertkit_mm_get_option( 'convertkit-mapping-' . $key ),

					'name_cancel'  => 'convertkit-mapping-' . $key . '-cancel',
					'value_cancel' => convertkit_mm_get_option( 'convertkit-mapping-' . $key . '-cancel' ),

					'options'      => $tags,
				)
			);
		}

		// Register "Tagging: Products" settings section and fields.
		add_settings_section(
			CONVERTKIT_MM_NAME . '-ck-mapping-products',
			__( 'Tagging: Products', 'convertkit-mm' ),
			array( $this, 'display_section_introduction' ),
			CONVERTKIT_MM_NAME,
			array(
				'before_section' => '<div class="section">',
				'after_section'  => '</div>',
				'description'    => esc_html__( 'For each MemberMouse Product, assign a ConvertKit Tag that you wish to be assigned to members of that MemberMouse Product.', 'convertkit-mm' ),
			)
		);
		$products = $this->get_mm_products();
		foreach ( $products as $key => $name ) {
			add_settings_field(
				'convertkit-mapping-product-' . $key,
				$name,
				array( $this, 'mapping_fields_callback' ),
				CONVERTKIT_MM_NAME,
				CONVERTKIT_MM_NAME . '-ck-mapping-products',
				array(
					'key'     => $key,

					'name'    => 'convertkit-mapping-product-' . $key,
					'value'   => convertkit_mm_get_option( 'convertkit-mapping-product-' . $key ),

					'options' => $tags,
				)
			);
		}

		// Regsiter "Tagging: Bundles" settings section and fields.
		add_settings_section(
			CONVERTKIT_MM_NAME . '-ck-mapping-bundles',
			__( 'Tagging: Bundles', 'convertkit-mm' ),
			array( $this, 'display_section_introduction' ),
			CONVERTKIT_MM_NAME,
			array(
				'before_section' => '<div class="section">',
				'after_section'  => '</div>',
				'description'    => esc_html__( 'For each MemberMouse Bundle, assign a ConvertKit Tag that you wish to be assigned to members of that bundle.', 'convertkit-mm' ),
			)
		);
		$bundles = $this->get_mm_bundles();
		foreach ( $bundles as $key => $name ) {
			add_settings_field(
				'convertkit-mapping-bundle-' . $key,
				$name,
				array( $this, 'mapping_fields_callback' ),
				CONVERTKIT_MM_NAME,
				CONVERTKIT_MM_NAME . '-ck-mapping-bundles',
				array(
					'key'          => $key,

					'name'         => 'convertkit-mapping-bundle-' . $key,
					'value'        => convertkit_mm_get_option( 'convertkit-mapping-bundle-' . $key ),

					'name_cancel'  => 'convertkit-mapping-bundle-' . $key . '-cancel',
					'value_cancel' => convertkit_mm_get_option( 'convertkit-mapping-bundle-' . $key . '-cancel' ),

					'options'      => $tags,
				)
			);
		}

	}

	/**
	 * Adds a settings page link to a menu
	 *
	 * @since       1.0.0
	 * @return      void
	 */
	public function add_settings_page() {

		add_options_page(
			apply_filters( CONVERTKIT_MM_NAME . '_settings_page_title', esc_html__( 'ConvertKit MemberMouse Settings', 'convertkit-mm' ) ),
			apply_filters( CONVERTKIT_MM_NAME . '_settings_menu_title', esc_html__( 'ConvertKit MemberMouse', 'convertkit-mm' ) ),
			'manage_options',
			CONVERTKIT_MM_NAME,
			array( $this, 'display_settings_page' )
		);

	}

	/**
	 * Creates the options page
	 *
	 * @since       1.0.0
	 * @return      void
	 */
	public function display_settings_page() {

		?>
		<header>
			<h1><?php esc_html_e( 'ConvertKit for MemberMouse', 'convertkit-mm' ); ?></h1>

			<?php
			// Output Help link.
			$documentation_url = '@TODO';
			printf(
				'<a href="%s" class="convertkit-docs" target="_blank">%s</a>',
				esc_attr( $documentation_url ),
				esc_html__( 'Help', 'convertkit-mm' )
			);
			?>
		</header>

		<div class="wrap">
			<form method="post" action="options.php" enctype="multipart/form-data">
				<div class="metabox-holder">
					<div class="postbox">
						<?php
						do_settings_sections( CONVERTKIT_MM_NAME );
						settings_fields( 'convertkit-mm-options' );
						submit_button();
						?>
						</div>
				</div>
			</form>

			<p class="description">
				<?php
				// Output Help link.
				printf(
					'%s <a href="%s" target="_blank">%s</a>',
					esc_html__( 'If you need help setting up the plugin please refer to the', 'convertkit-mm' ),
					esc_attr( $documentation_url ),
					esc_html__( 'plugin documentation', 'convertkit-mm' )
				);
				?>
			</p>
		</div>
		<?php

	}

	/**
	 * Outputs a description at the start of a settings section, before its fields.
	 *
	 * @since       1.3.0
	 *
	 * @param   array $args   Section arguments.
	 */
	public function display_section_introduction( $args ) {

		echo '<p>' . esc_html( $args['description'] ) . '</p>';

	}

	/**
	 * Renders a text input field.
	 *
	 * @since   1.3.0
	 *
	 * @param   array $args   Setting field arguments.
	 */
	public function text_field_callback( $args ) {

		// Output field.
		$html = sprintf(
			'<input type="text" class="%s" id="%s" name="%s[%s]" value="%s" />',
			( is_array( $args['css_classes'] ) ? implode( ' ', $args['css_classes'] ) : 'regular-text' ),
			$args['name'],
			$this->settings_key,
			$args['name'],
			convertkit_mm_get_option( $args['name'] )
		);

		// If no description exists, just return the field.
		if ( empty( $args['description'] ) ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		// Return field with description appended to it.
		echo $html . $this->get_description( $args['description'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Renders a checkbox field.
	 *
	 * @since   1.3.0
	 *
	 * @param   array $args   Setting field arguments.
	 */
	public function checkbox_field_callback( $args ) {

		$html = '';

		if ( $args['label'] ) {
			$html .= sprintf(
				'<label for="%s">',
				$args['name']
			);
		}

		$html .= sprintf(
			'<input type="checkbox" id="%s" name="%s[%s]" class="%s" value="%s" %s />',
			$args['name'],
			$this->settings_key,
			$args['name'],
			( array_key_exists( 'css_classes', $args ) && is_array( $args['css_classes'] ) ? implode( ' ', $args['css_classes'] ) : '' ),
			$args['value'],
			( $args['checked'] ? ' checked' : '' )
		);

		if ( $args['label'] ) {
			$html .= sprintf(
				'%s</label>',
				$args['label']
			);
		}

		// If no description exists, just return the field.
		if ( empty( $args['description'] ) ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		// Return field with description appended to it.
		echo $html . $this->get_description( $args['description'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Renders the fields for the tag mapping section in the settings screen
	 * (membership levels, products and bundles).
	 *
	 * @since   1.3.0
	 *
	 * @param   array $args   Setting field arguments.
	 */
	public function mapping_fields_callback( $args ) {

		$html = $this->get_select_field(
			$args['name'],
			$args['value'],
			$args['options']
		);

		// If a cancel option is not specified, return the single field now.
		if ( ! array_key_exists( 'name_cancel', $args ) ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		$html .= $this->get_select_field(
			$args['name_cancel'],
			$args['value_cancel'],
			$args['options']
		);

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Returns a select dropdown field.
	 *
	 * @since   1.9.6
	 *
	 * @param   string $name            Name.
	 * @param   string $value           Value.
	 * @param   array  $options         Options / Choices.
	 * @return  string                       HTML Select Field
	 */
	private function get_select_field( $name, $value = '', $options = array() ) {

		// Build opening <select> tag.
		$html = sprintf(
			'<select id="%s" name="%s[%s]" size="1">',
			$name,
			$this->settings_key,
			$name
		);

		// Build <option> tags.
		foreach ( $options as $option => $label ) {
			$html .= sprintf(
				'<option value="%s"%s>%s</option>',
				$option,
				selected( $value, $option, false ),
				$label
			);
		}

		// Close <select>.
		$html .= '</select>';

		return $html;

	}

	/**
	 * Returns the given text wrapped in a paragraph with the description class.
	 *
	 * @since   1.9.6
	 *
	 * @param   bool|string|array $description    Description.
	 * @return  string                              HTML Description
	 */
	private function get_description( $description ) {

		// Return blank string if no description specified.
		if ( ! $description ) {
			return '';
		}

		// Return description in paragraph if a string.
		if ( ! is_array( $description ) ) {
			return '<p class="description">' . $description . '</p>';
		}

		// Return description lines in a paragraph, using breaklines for each description entry in the array.
		return '<p class="description">' . implode( '<br />', $description ) . '</p>';

	}

	/**
	 * Validates saved options
	 *
	 * @since       1.0.0
	 *
	 * @param       array $input          Submitted plugin options.
	 * @return      array                 Validated plugin options
	 */
	public function validate_options( $input ) {

		return $input;

	}

	/**
	 * Adds a link to the plugin settings page
	 *
	 * @since       1.0.0
	 *
	 * @param       array $links    Settings links.
	 * @return      array           Settings links
	 */
	public function settings_link( $links ) {

		$settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=' . CONVERTKIT_MM_NAME ), esc_html__( 'Settings', 'convertkit-mm' ) );
		array_unshift( $links, $settings_link );
		return $links;

	}

	/**
	 * Get all MemberMouse membership levels
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_mm_membership_levels() {

		global $wpdb;

		$levels = array();
		if ( ! defined( 'MM_TABLE_MEMBERSHIP_LEVELS' ) ) {
			return $levels;
		}

		$result = $wpdb->get_results( 'SELECT id, name, status FROM ' . MM_TABLE_MEMBERSHIP_LEVELS, OBJECT ); // phpcs:ignore WordPress.DB.PreparedSQL

		foreach ( $result as $_level ) {
			$levels[ $_level->id ] = $_level->name;
		}

		return $levels;

	}

	/**
	 * Get all MemberMouse products
	 *
	 * @since   1.2.0
	 *
	 * @return  array
	 */
	private function get_mm_products() {

		global $wpdb;

		$products = array();
		if ( ! defined( 'MM_TABLE_PRODUCTS' ) ) {
			return $products;
		}

		$result = $wpdb->get_results( 'SELECT id, name FROM ' . MM_TABLE_PRODUCTS, OBJECT ); // phpcs:ignore WordPress.DB.PreparedSQL

		foreach ( $result as $product ) {
			$products[ $product->id ] = $product->name;
		}

		return $products;

	}

	/**
	 * Get all MemberMouse bundles
	 *
	 * @since   1.2.0
	 *
	 * @return  array
	 */
	private function get_mm_bundles() {

		global $wpdb;

		$bundles = array();
		if ( ! defined( 'MM_TABLE_BUNDLES' ) ) {
			return $bundles;
		}

		$result = $wpdb->get_results( 'SELECT id, name FROM ' . MM_TABLE_BUNDLES, OBJECT ); // phpcs:ignore WordPress.DB.PreparedSQL

		foreach ( $result as $bundle ) {
			$bundles[ $bundle->id ] = $bundle->name;
		}

		return $bundles;

	}

}
