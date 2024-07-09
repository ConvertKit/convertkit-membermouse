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
		add_filter( 'plugin_action_links_convertkit-membermouse/convertkit-membermouse.php', array( $this, 'settings_link' ) );
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Initialize API.
		$api_key   = convertkit_mm_get_option( 'api-key' );
		$this->api = new ConvertKit_MM_API( $api_key );

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

		register_setting(
			CONVERTKIT_MM_NAME . '-options',
			CONVERTKIT_MM_NAME . '-options',
			array( $this, 'validate_options' )
		);

		add_settings_section(
			CONVERTKIT_MM_NAME . '-display-options',
			apply_filters( CONVERTKIT_MM_NAME . '_display_section_title', esc_html__( 'General', 'convertkit-mm' ) ),
			array( $this, 'display_options_section' ),
			CONVERTKIT_MM_NAME
		);

		add_settings_field(
			'api-key',
			apply_filters( CONVERTKIT_MM_NAME . '_display_api_key', esc_html__( 'API Key', 'convertkit-mm' ) ),
			array( $this, 'display_options_api_key' ),
			CONVERTKIT_MM_NAME,
			CONVERTKIT_MM_NAME . '-display-options'
		);
		add_settings_field(
			'debug',
			apply_filters( CONVERTKIT_MM_NAME . '_display_debug', esc_html__( 'Debug', 'convertkit-mm' ) ),
			array( $this, 'display_options_debug' ),
			CONVERTKIT_MM_NAME,
			CONVERTKIT_MM_NAME . '-display-options'
		);
		add_settings_section(
			CONVERTKIT_MM_NAME . '-ck-mapping',
			apply_filters( CONVERTKIT_MM_NAME . '_display_mapping_title', esc_html__( 'Assign Tags', 'convertkit-mm' ) ),
			array( $this, 'display_mapping_section' ),
			CONVERTKIT_MM_NAME
		);

		// Get all MemberMouse membership levels, products and bundles.
		$levels   = $this->get_mm_membership_levels();
		$products = $this->get_mm_products();
		$bundles  = $this->get_mm_bundles();

		// Get all tags from ConvertKit.
		$tags = $this->api->get_tags();

		// Output level to tag mappings.
		if ( empty( $levels ) ) {
			add_settings_field(
				'convertkit-empty-mapping',
				apply_filters( CONVERTKIT_MM_NAME . '_display_convertkit_mapping', esc_html__( 'Mapping', 'convertkit-mm' ) ),
				array( $this, 'display_options_empty_mapping' ),
				CONVERTKIT_MM_NAME,
				CONVERTKIT_MM_NAME . '-ck-mapping'
			);
		} else {
			foreach ( $levels as $key => $name ) {
				add_settings_field(
					'convertkit-mapping-' . $key,
					apply_filters( CONVERTKIT_MM_NAME . '_display_convertkit_mapping_' . $key, $name ),
					array( $this, 'display_options_convertkit_mapping' ),
					CONVERTKIT_MM_NAME,
					CONVERTKIT_MM_NAME . '-ck-mapping',
					array(
						'key'  => $key,
						'name' => $name,
						'tags' => $tags,
					)
				);
			}
		}

		// Output product to tag mappings.
		if ( empty( $products ) ) {
			add_settings_field(
				'convertkit-empty-mapping',
				apply_filters( CONVERTKIT_MM_NAME . '_display_convertkit_mapping_product', esc_html__( 'Mapping', 'convertkit-mm' ) ),
				array( $this, 'display_options_empty_mapping_products' ),
				CONVERTKIT_MM_NAME,
				CONVERTKIT_MM_NAME . '-ck-mapping'
			);
		} else {
			foreach ( $products as $key => $name ) {
				add_settings_field(
					'convertkit-mapping-product-' . $key,
					apply_filters( CONVERTKIT_MM_NAME . '_display_convertkit_mapping_product_' . $key, $name ),
					array( $this, 'display_options_convertkit_mapping_product' ),
					CONVERTKIT_MM_NAME,
					CONVERTKIT_MM_NAME . '-ck-mapping',
					array(
						'key'  => $key,
						'name' => $name,
						'tags' => $tags,
					)
				);
			}
		}

		// Output bundle to tag mappings.
		if ( empty( $bundles ) ) {
			add_settings_field(
				'convertkit-empty-mapping',
				apply_filters( CONVERTKIT_MM_NAME . '_display_convertkit_mapping_bundle', esc_html__( 'Mapping', 'convertkit-mm' ) ),
				array( $this, 'display_options_empty_mapping_bundles' ),
				CONVERTKIT_MM_NAME,
				CONVERTKIT_MM_NAME . '-ck-mapping'
			);
		} else {
			foreach ( $bundles as $key => $name ) {
				add_settings_field(
					'convertkit-mapping-bundle-' . $key,
					apply_filters( CONVERTKIT_MM_NAME . '_display_convertkit_mapping_bundle_' . $key, $name ),
					array( $this, 'display_options_convertkit_mapping_bundle' ),
					CONVERTKIT_MM_NAME,
					CONVERTKIT_MM_NAME . '-ck-mapping',
					array(
						'key'  => $key,
						'name' => $name,
						'tags' => $tags,
					)
				);
			}
		}

	}

	/**
	 * Adds a settings page link to a menu
	 *
	 * @since       1.0.0
	 * @return      void
	 */
	public function add_menu() {

		add_options_page(
			apply_filters( CONVERTKIT_MM_NAME . '_settings_page_title', esc_html__( 'ConvertKit MemberMouse Settings', 'convertkit-mm' ) ),
			apply_filters( CONVERTKIT_MM_NAME . '_settings_menu_title', esc_html__( 'ConvertKit MemberMouse', 'convertkit-mm' ) ),
			'manage_options',
			CONVERTKIT_MM_NAME,
			array( $this, 'options_page' )
		);

	}

	/**
	 * Creates the options page
	 *
	 * @since       1.0.0
	 * @return      void
	 */
	public function options_page() {

		?><div class="wrap"><h1><?php echo esc_html( get_admin_page_title() ); ?></h1></div>
		<form action="options.php" method="post">
		<?php
		settings_fields( 'convertkit-mm-options' );
		do_settings_sections( CONVERTKIT_MM_NAME );
		submit_button( 'Save Settings' );
		?>
		</form>
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

		echo '<p>' . esc_html__( 'Add your API key below and then choose a default form to add subscribers to.', 'convertkit-mm' ) . '</p>';

	}

	/**
	 * Outputs a description for the Mapping section of the settings screen
	 *
	 * @since       1.0.0
	 */
	public function display_mapping_section() {

		echo '<p>' . esc_html__( 'Below is a list of the defined MemberMouse Membership Levels. Assign a membership level to a ConvertKit tag that will be assigned to members of that level.', 'convertkit-mm' ) . '</p>';
		echo '<table class="form-table"><tbody><tr><th scope="row">Membership</th><td><strong>Apply tag on add</strong></td><td><strong>Apply tag on cancel</strong></td></tr></tbody></table>';

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
	 * Outputs the API Key field in the settings screen
	 *
	 * @since       1.0.0
	 */
	public function display_options_api_key() {

		$api_key = convertkit_mm_get_option( 'api-key' );
		?>
		<input type="text" id="<?php echo esc_attr( CONVERTKIT_MM_NAME ); ?>-options[api-key]" name="<?php echo esc_attr( CONVERTKIT_MM_NAME ); ?>-options[api-key]" value="<?php echo esc_attr( $api_key ); ?>" /><br/>
		<p class="description"><a href="https://app.convertkit.com/account/edit" target="_blank"><?php echo esc_html__( 'Get your ConvertKit API Key', 'convertkit-mm' ); ?></a></p>
		<?php

	}

	/**
	 * Outputs the Debug field in the settings screen
	 *
	 * @since       1.0.0
	 */
	public function display_options_debug() {

		$debug = convertkit_mm_get_option( 'debug' );
		?>
		<input type="checkbox" id="<?php echo esc_attr( CONVERTKIT_MM_NAME ); ?>-options[debug]" name="<?php echo esc_attr( CONVERTKIT_MM_NAME ); ?>-options[debug]"<?php echo ( ( 'on' === $debug ) ? ' checked' : '' ); ?> />
		<?php
		echo esc_html__( 'Add data to a debug log.', 'convertkit-mm' );

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

}
