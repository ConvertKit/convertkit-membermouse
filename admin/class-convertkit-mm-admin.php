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
	 * @var     string
	 */
	const SETTINGS_PAGE_SLUG = 'convertkit-mm';

	public $settings_key =  CONVERTKIT_MM_NAME . '-options';

	/**
	 * API functionality class
	 *
	 * @since   1.0.0
	 *
	 * @var     ConvertKit_MM_API
	 */
	private $api;

	/**
	 * Initialize the class
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		// Register settings screen.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_convertkit-membermouse/convertkit-membermouse.php', array( $this, 'settings_link' ) );

	}

	/**
	 * Enqueue JavaScript in Admin
	 *
	 * @since   1.9.6
	 *
	 * @param   string $hook   Hook.
	 */
	public function enqueue_scripts( $hook ) {

		// Bail if we are not on the Settings screen.
		if ( $hook !== 'settings_page_' . self::SETTINGS_PAGE_SLUG ) {
			return;
		}

	}

	/**
	 * Enqueue CSS for the Settings Screens at Settings > ConvertKit
	 *
	 * @since   1.9.6
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
	 * Register settings for the plugin.
	 *
	 * The mapping section is dynamic and depends on defined membership levels and defined tags.
	 *
	 * @since       1.0.0
	 * @return      void
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
			array( $this, 'display_options_section' ),
			CONVERTKIT_MM_NAME
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
				'value'		  => '1',
				'checked'	  => true,
				'label' => __( 'Log requests to file and output browser console messages.', 'convertkit-mm' ),
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
		$this->api = new ConvertKit_MM_API( convertkit_mm_get_option( 'api-key' ) );

		// Get all tags from ConvertKit.
		$tags = $this->api->get_tags();

		// @TODO If no tags bail.

		// Regsiter "Tagging: Membership Levels" settings section and fields.
		add_settings_section(
			CONVERTKIT_MM_NAME . '-ck-mapping-membership-levels',
			__( 'Tagging: Membership Levels', 'convertkit-mm' ),
			array( $this, 'display_mapping_section' ),
			CONVERTKIT_MM_NAME
		);
		$levels   = $this->get_mm_membership_levels();
		foreach ( $levels as $key => $name ) {
			add_settings_field(
				'convertkit-mapping-' . $key,
				$name,
				array( $this, 'tag_callback' ),
				CONVERTKIT_MM_NAME,
				CONVERTKIT_MM_NAME . '-ck-mapping-membership-levels',
				array(
					'key'  => $key,
					'name' => $name,
					'tags' => $tags,
				)
			);
		}

		// Regsiter "Tagging: Products" settings section and fields.
		add_settings_section(
			CONVERTKIT_MM_NAME . '-ck-mapping-products',
			__( 'Tagging: Products', 'convertkit-mm' ),
			array( $this, 'display_mapping_section' ),
			CONVERTKIT_MM_NAME
		);
		$products = $this->get_mm_products();
		foreach ( $products as $key => $name ) {
			add_settings_field(
				'convertkit-mapping-product-' . $key,
				$name,
				array( $this, 'tag_callback' ),
				CONVERTKIT_MM_NAME,
				CONVERTKIT_MM_NAME . '-ck-mapping-products',
				array(
					'key'  => $key,
					'name' => $name,
					'tags' => $tags,
				)
			);
		}

		// Regsiter "Bundles: Membership Levels" settings section and fields.
		add_settings_section(
			CONVERTKIT_MM_NAME . '-ck-mapping-bundles',
			__( 'Tagging: Bundles', 'convertkit-mm' ),
			array( $this, 'display_mapping_section' ),
			CONVERTKIT_MM_NAME
		);
		$bundles  = $this->get_mm_bundles();
		foreach ( $bundles as $key => $name ) {
			add_settings_field(
				'convertkit-mapping-bundle-' . $key,
				$name,
				array( $this, 'tag_callback' ),
				CONVERTKIT_MM_NAME,
				CONVERTKIT_MM_NAME . '-ck-mapping-bundles',
				array(
					'key'  => $key,
					'name' => $name,
					'tags' => $tags,
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
			<h1><?php esc_html_e( 'ConvertKit', 'convertkit' ); ?></h1>

			<?php
			// Output Help link tab, if it exists.
			$documentation_url = 'blah';
			if ( $documentation_url !== false ) {
				printf(
					'<a href="%s" class="convertkit-docs" target="_blank">%s</a>',
					esc_attr( $documentation_url ),
					esc_html__( 'Help', 'convertkit' )
				);
			}
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
				// Output Help link, if it exists.
				$documentation_url = 'blah';
				if ( $documentation_url !== false ) {
					printf(
						'%s <a href="%s" target="_blank">%s</a>',
						esc_html__( 'If you need help setting up the plugin please refer to the', 'convertkit' ),
						esc_attr( $documentation_url ),
						esc_html__( 'plugin documentation', 'convertkit' )
					);
				}
				?>
			</p>
		</div>
		<?php

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
	 * Outputs a description for the General section of the settings screen
	 *
	 * @since       1.0.0
	 */
	public function display_options_section() {

		echo '<p>' . esc_html__( 'Add your API key below, and then choose when to tag subscribers based on MemberMouse actions.', 'convertkit-mm' ) . '</p>';

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
			echo $html;
		}

		// Return field with description appended to it.
		echo $html . $this->get_description( $args['description'] );

	}

	/**
	 * Renders a checkbox field.
	 *
	 * @since   1.3.0
	 *
	 * @param 	array 	$args 	Setting field arguments.
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
			echo $html;
		}

		// Return field with description appended to it.
		echo $html . $this->get_description( $args['description'] );

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
	 * Outputs a description for the Mapping section of the settings screen
	 *
	 * @since       1.0.0
	 */
	public function display_mapping_section() {

		echo '<p>' . esc_html__( 'Below is a list of the defined MemberMouse Membership Levels. Assign a membership level to a ConvertKit tag that will be assigned to members of that level.', 'convertkit-mm' ) . '</p>';

	}

	/**
	 * Outputs a notice in the Mapping section when no MemberMouse levels have been added
	 *
	 * @since       1.0.0
	 */
	public function display_options_empty_mapping() {

		?>
		<p>
			<?php echo esc_html__( 'No MM Membership Levels have been added yet.', 'convertkit-mm' ); ?><br/>
			<?php
			printf(
				/* translators: Link to MemberMouse Membership Levels */
				esc_html__( 'You can add one <a href="%s">here</a>.', 'convertkit-mm' ),
				esc_url( get_admin_url( null, '/admin.php?page=mmro-membershiplevels' ) )
			);
			?>
		</p>
		<?php

	}

	/**
	 * Display membership level to tag mapping for the specified key.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args   Field arguments.
	 */
	public function display_options_convertkit_mapping( $args ) {

		$option_name = 'convertkit-mapping-' . $args['key'];
		$tag         = convertkit_mm_get_option( $option_name );
		$tag_cancel  = convertkit_mm_get_option( $option_name . '-cancel' );
		$api_key     = convertkit_mm_get_option( 'api-key' );

		if ( empty( $api_key ) ) {
			?>
			<p><?php echo esc_html__( 'Enter API key to retrieve list of tags.', 'convertkit-mm' ); ?></p>
			<?php
		} elseif ( is_null( $args['tags'] ) ) {
			?>
			<p><?php echo esc_html__( 'No tags were returned from ConvertKit.', 'convertkit-mm' ); ?></p>
			<?php
		} else {
			?>
				<select id="<?php echo esc_attr( CONVERTKIT_MM_NAME ); ?>-options[<?php echo esc_attr( $option_name ); ?>]" name="<?php echo esc_attr( CONVERTKIT_MM_NAME ); ?>-options[<?php echo esc_attr( $option_name ); ?>]">
					<option value=""<?php selected( $tag, '' ); ?>><?php echo esc_attr__( '(None)', 'convertkit-mm' ); ?></option>
					<?php
					foreach ( $args['tags'] as $value => $text ) {
						?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $tag, $value ); ?>><?php echo esc_attr( $text ); ?></option>
						<?php
					}
					?>
				</select>
			</td>
			<td>
				<select id="<?php echo esc_attr( CONVERTKIT_MM_NAME ); ?>-options[<?php echo esc_attr( $option_name ); ?>-cancel]" name="<?php echo esc_attr( CONVERTKIT_MM_NAME ); ?>-options[<?php echo esc_attr( $option_name ); ?>-cancel]">
					<option value=""<?php selected( $tag_cancel, '' ); ?>><?php echo esc_attr__( '(None)', 'convertkit-mm' ); ?></option>
					<?php
					foreach ( $args['tags'] as $value => $text ) {
						?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $tag_cancel, $value ); ?>><?php echo esc_attr( $text ); ?></option>
						<?php
					}
					?>
				</select>
			<?php
		}

	}

	/**
	 * Outputs a notice in the Mapping section when no MemberMouse products have been added
	 *
	 * @since       1.2.0
	 */
	public function display_options_empty_mapping_products() {

		?>
		<p>
			<?php echo esc_html__( 'No MM Products have been added yet.', 'convertkit-mm' ); ?><br/>
			<?php
			printf(
				/* translators: Link to MemberMouse Membership Levels */
				esc_html__( 'You can add one <a href="%s">here</a>.', 'convertkit-mm' ),
				esc_url( get_admin_url( null, '/admin.php?page=product_settings' ) )
			);
			?>
		</p>
		<?php

	}

	/**
	 * Display product to tag mapping for the specified key.
	 *
	 * @since 1.2.0
	 *
	 * @param array $args   Field arguments.
	 */
	public function display_options_convertkit_mapping_product( $args ) {

		$option_name = 'convertkit-mapping-product-' . $args['key'];
		$tag         = convertkit_mm_get_option( $option_name );
		$api_key     = convertkit_mm_get_option( 'api-key' );

		if ( empty( $api_key ) ) {
			?>
			<p><?php echo esc_html__( 'Enter API key to retrieve list of tags.', 'convertkit-mm' ); ?></p>
			<?php
		} elseif ( is_null( $args['tags'] ) ) {
			?>
			<p><?php echo esc_html__( 'No tags were returned from ConvertKit.', 'convertkit-mm' ); ?></p>
			<?php
		} else {
			?>
				<select id="<?php echo esc_attr( CONVERTKIT_MM_NAME ); ?>-options[<?php echo esc_attr( $option_name ); ?>]" name="<?php echo esc_attr( CONVERTKIT_MM_NAME ); ?>-options[<?php echo esc_attr( $option_name ); ?>]">
					<option value=""<?php selected( $tag, '' ); ?>><?php echo esc_attr__( '(None)', 'convertkit-mm' ); ?></option>
					<?php
					foreach ( $args['tags'] as $value => $text ) {
						?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $tag, $value ); ?>><?php echo esc_attr( $text ); ?></option>
						<?php
					}
					?>
				</select>
			</td>
			<td>
				&nbsp;
			<?php
		}

	}

	/**
	 * Outputs a notice in the Mapping section when no MemberMouse products have been added
	 *
	 * @since       1.2.0
	 */
	public function display_options_empty_mapping_bundles() {

		?>
		<p>
			<?php echo esc_html__( 'No MM Bundles have been added yet.', 'convertkit-mm' ); ?><br/>
			<?php
			printf(
				/* translators: Link to MemberMouse Membership Levels */
				esc_html__( 'You can add one <a href="%s">here</a>.', 'convertkit-mm' ),
				esc_url( get_admin_url( null, '/admin.php?page=product_settings&module=bundles' ) )
			);
			?>
		</p>
		<?php

	}

	/**
	 * Display bundle to tag mapping for the specified key.
	 *
	 * @since 1.2.0
	 *
	 * @param array $args   Field arguments.
	 */
	public function display_options_convertkit_mapping_bundle( $args ) {

		$option_name = 'convertkit-mapping-bundle-' . $args['key'];
		$tag         = convertkit_mm_get_option( $option_name );
		$tag_cancel  = convertkit_mm_get_option( $option_name . '-cancel' );
		$api_key     = convertkit_mm_get_option( 'api-key' );

		if ( empty( $api_key ) ) {
			?>
			<p><?php echo esc_html__( 'Enter API key to retrieve list of tags.', 'convertkit-mm' ); ?></p>
			<?php
		} elseif ( is_null( $args['tags'] ) ) {
			?>
			<p><?php echo esc_html__( 'No tags were returned from ConvertKit.', 'convertkit-mm' ); ?></p>
			<?php
		} else {
			?>
				<select id="<?php echo esc_attr( CONVERTKIT_MM_NAME ); ?>-options[<?php echo esc_attr( $option_name ); ?>]" name="<?php echo esc_attr( CONVERTKIT_MM_NAME ); ?>-options[<?php echo esc_attr( $option_name ); ?>]">
					<option value=""<?php selected( $tag, '' ); ?>><?php echo esc_attr__( '(None)', 'convertkit-mm' ); ?></option>
					<?php
					foreach ( $args['tags'] as $value => $text ) {
						?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $tag, $value ); ?>><?php echo esc_attr( $text ); ?></option>
						<?php
					}
					?>
				</select>
			</td>
			<td>
				<select id="<?php echo esc_attr( CONVERTKIT_MM_NAME ); ?>-options[<?php echo esc_attr( $option_name ); ?>-cancel]" name="<?php echo esc_attr( CONVERTKIT_MM_NAME ); ?>-options[<?php echo esc_attr( $option_name ); ?>-cancel]">
					<option value=""<?php selected( $tag_cancel, '' ); ?>><?php echo esc_attr__( '(None)', 'convertkit-mm' ); ?></option>
					<?php
					foreach ( $args['tags'] as $value => $text ) {
						?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $tag_cancel, $value ); ?>><?php echo esc_attr( $text ); ?></option>
						<?php
					}
					?>
				</select>
			<?php
		}

	}

	/**
	 * Get all MemberMouse membership levels
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_mm_membership_levels() {

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
	public function get_mm_products() {

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
	public function get_mm_bundles() {

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

}
