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
	 * @since   1.2.0
	 *
	 * @var     string
	 */
	const SETTINGS_PAGE_SLUG = 'convertkit-mm';

	/**
	 * Holds the ConvertKit Settings class.
	 *
	 * @since   1.2.2
	 *
	 * @var     null|ConvertKit_MM_Settings
	 */
	public $settings;

	/**
	 * Holds the ConvertKit Tags.
	 *
	 * @since   1.2.0
	 *
	 * @var     null|array
	 */
	public $tags;

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
					__( 'You can ignore this unless you\'re working with our support team to resolve an issue. Uncheck this option to improve performance.', 'convertkit-mm' ),
				),
			)
		);

		// If the API hasn't been configured, don't display any further settings, as
		// we cannot fetch tags from the API to populate dropdown fields.
		$this->settings = new ConvertKit_MM_Settings();
		if ( ! $this->settings->has_api_key() ) {
			return;
		}

		// Initialize API.
		$api = new ConvertKit_MM_API( $this->settings->get_api_key() );

		// Get all tags from ConvertKit.
		$this->tags = $api->get_tags();

		// Bail if no tags, as there are no further configuration settings without having ConvertKit Tags.
		if ( is_null( $this->tags ) ) {
			return;
		}

		// Regsiter "Tagging: Membership Levels" settings section and fields.
		$this->register_settings_membership_levels();

		// Register "Tagging: Products" settings section and fields.
		$this->register_settings_products();

		// Regsiter "Tagging: Bundles" settings section and fields.
		$this->register_settings_bundles();

	}

	/**
	 * Registers the settings section and fields for Membership Levels.
	 *
	 * @since   1.3.0
	 */
	private function register_settings_membership_levels() {

		// Get membership levels.
		$levels = $this->get_mm_membership_levels();

		// Define description for the settings section, depending on whether any bundles exist.
		if ( count( $levels ) ) {
			$description = esc_html__( 'For each MemberMouse membership level, assign a ConvertKit Tag that you wish to be assigned to members of that level.', 'convertkit-mm' );
		} else {
			$description = esc_html__( 'No membership levels exist in MemberMouse. Add a membership level first, and then reload this settings screen to assign tags to members by membership level.', 'convertkit-mm' );
		}

		// Register settings section.
		add_settings_section(
			CONVERTKIT_MM_NAME . '-ck-mapping-membership-levels',
			__( 'Tagging: Membership Levels', 'convertkit-mm' ),
			array( $this, 'display_section_introduction' ),
			CONVERTKIT_MM_NAME,
			array(
				'before_section' => '<div class="section">',
				'after_section'  => '</div>',
				'description'    => $description,
			)
		);

		// If no levels exist, don't display any settings fields.
		if ( ! $levels ) {
			return;
		}

		// Output settings fields for each level.
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
					'value'        => $this->settings->get_membership_level_mapping( $key ),

					'name_cancel'  => 'convertkit-mapping-' . $key . '-cancel',
					'value_cancel' => $this->settings->get_membership_level_cancellation_mapping( $key ),

					'options'      => $this->tags,
				)
			);
		}

	}

	/**
	 * Registers the settings section and fields for Products.
	 *
	 * @since   1.3.0
	 */
	private function register_settings_products() {

		// Get products.
		$products = $this->get_mm_products();

		// Define description for the settings section, depending on whether any products exist.
		if ( count( $products ) ) {
			$description = esc_html__( 'For each MemberMouse product, assign a ConvertKit Tag that you wish to be assigned to members of that product.', 'convertkit-mm' );
		} else {
			$description = esc_html__( 'No products exist in MemberMouse. Add a product first, and then reload this settings screen to assign tags to members of products.', 'convertkit-mm' );
		}

		add_settings_section(
			CONVERTKIT_MM_NAME . '-ck-mapping-products',
			__( 'Tagging: Products', 'convertkit-mm' ),
			array( $this, 'display_section_introduction' ),
			CONVERTKIT_MM_NAME,
			array(
				'before_section' => '<div class="section">',
				'after_section'  => '</div>',
				'description'    => $description,
			)
		);

		// If no products exist, don't display any settings fields.
		if ( ! $products ) {
			return;
		}

		// Output settings fields for each product.
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
					'value'   => $this->settings->get_product_mapping( $key ),

					'options' => $this->tags,
				)
			);
		}

	}

	/**
	 * Registers the settings section and fields for Bundles.
	 *
	 * @since   1.3.0
	 */
	private function register_settings_bundles() {

		// Get bundles.
		$bundles = $this->get_mm_bundles();

		// Define description for the settings section, depending on whether any bundles exist.
		if ( count( $bundles ) ) {
			$description = esc_html__( 'For each MemberMouse bundle, assign a ConvertKit Tag that you wish to be assigned to members of that bundle.', 'convertkit-mm' );
		} else {
			$description = esc_html__( 'No bundles exist in MemberMouse. Add a bundle first, and then reload this settings screen to assign tags to members of bundles.', 'convertkit-mm' );
		}

		// Register settings section.
		add_settings_section(
			CONVERTKIT_MM_NAME . '-ck-mapping-bundles',
			__( 'Tagging: Bundles', 'convertkit-mm' ),
			array( $this, 'display_section_introduction' ),
			CONVERTKIT_MM_NAME,
			array(
				'before_section' => '<div class="section">',
				'after_section'  => '</div>',
				'description'    => $description,
			)
		);

		// If no bundles exist, don't display any settings fields.
		if ( ! $bundles ) {
			return;
		}

		// Output settings fields for each bundle.
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
					'value'        => $this->settings->get_bundle_mapping( $key ),

					'name_cancel'  => 'convertkit-mapping-bundle-' . $key . '-cancel',
					'value_cancel' => $this->settings->get_bundle_cancellation_mapping( $key ),

					'options'      => $this->tags,
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
			$documentation_url = 'https://help.convertkit.com/en/articles/2502605-membermouse-integration';
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
						submit_button( __( 'Save Settings', 'convertkit-mm' ) );
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
			$this->settings::SETTINGS_NAME,
			$args['name'],
			$this->settings->get_by_key( $args['name'] )
		);

		// Output field with description appended to it.
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
			$this->settings::SETTINGS_NAME,
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

		// Output field with description appended to it.
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
			$args['options'],
			__( 'Apply tag on add', 'convertkit-mm' )
		);

		// If a cancel option is not specified, return the single field now.
		if ( ! array_key_exists( 'name_cancel', $args ) ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		$html .= $this->get_select_field(
			$args['name_cancel'],
			$args['value_cancel'],
			$args['options'],
			__( 'Apply tag on cancel', 'convertkit-mm' )
		);

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Returns a select dropdown field.
	 *
	 * @since   1.3.0
	 *
	 * @param   string      $name            Name.
	 * @param   string      $value           Value.
	 * @param   array       $options         Options / Choices.
	 * @param   bool|string $label           Label.
	 * @return  string                           HTML Select Field
	 */
	private function get_select_field( $name, $value = '', $options = array(), $label = false ) {

		$html = '';

		if ( $label ) {
			$html .= sprintf(
				'<div><label for="%s">%s</label>',
				$name,
				$label
			);
		}

		// Build opening <select> tag.
		$html .= sprintf(
			'<select id="%s" name="%s[%s]" size="1">',
			$name,
			$this->settings::SETTINGS_NAME,
			$name
		);

		// Add 'None' option.
		$html .= sprintf(
			'<option value=""%s>%s</option>',
			selected( $value, '', false ),
			esc_attr__( '(None)', 'convertkit-mm' )
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

		if ( $label ) {
			$html .= '</div>';
		}

		return $html;

	}

	/**
	 * Returns the given text wrapped in a paragraph with the description class,
	 * if a description is specified.
	 *
	 * @since   1.3.0
	 *
	 * @param   string|array $description    Description.
	 * @return  string                       HTML Description
	 */
	private function get_description( $description = '' ) {

		// Return blank string if no description specified.
		if ( empty( $description ) ) {
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
