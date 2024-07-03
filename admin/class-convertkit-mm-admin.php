<?php
/**
 * The admin-specific functionality for ConvertKit Paid Memberships Pro
 *
 * @link       http://www.convertkit.com
 * @since      1.0.0
 *
 * @package    ConvertKit_MM
 * @subpackage ConvertKit_MM/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    ConvertKit_MM
 * @subpackage ConvertKit_MM/admin
 * @author     Daniel Espinoza <daniel@growdevelopment.com>
 */
class ConvertKit_MM_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * API functionality class
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     ConvertKit_MM_API $api
	 */
	private $api;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string $plugin_name       The name of this plugin.
	 * @param    string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		require_once plugin_dir_path( __DIR__ ) . 'includes/class-convertkit-mm-api.php';

		$api_key = $this->get_option( 'api-key' );

		$this->api = new ConvertKit_MM_API( $api_key );

	}

	/**
	 *  Register settings for the plugin.
	 *
	 * The mapping section is dynamic and depends on defined membership levels and defined tags.
	 *
	 * @since       1.0.0
	 * @return      void
	 */
	public function register_settings() {

		register_setting(
			$this->plugin_name . '-options',
			$this->plugin_name . '-options',
			array( $this, 'validate_options' )
		);

		add_settings_section(
			$this->plugin_name . '-display-options',
			apply_filters( $this->plugin_name . '_display_section_title', esc_html__( 'General', 'convertkit-mm' ) ),
			array( $this, 'display_options_section' ),
			$this->plugin_name
		);

		add_settings_field(
			'api-key',
			apply_filters( $this->plugin_name . '_display_api_key', esc_html__( 'API Key', 'convertkit-mm' ) ),
			array( $this, 'display_options_api_key' ),
			$this->plugin_name,
			$this->plugin_name . '-display-options'
		);
		add_settings_field(
			'debug',
			apply_filters( $this->plugin_name . '_display_debug', esc_html__( 'Debug', 'convertkit-mm' ) ),
			array( $this, 'display_options_debug' ),
			$this->plugin_name,
			$this->plugin_name . '-display-options'
		);
		add_settings_section(
			$this->plugin_name . '-ck-mapping',
			apply_filters( $this->plugin_name . '_display_mapping_title', esc_html__( 'Assign Tags', 'convertkit-mm' ) ),
			array( $this, 'display_mapping_section' ),
			$this->plugin_name
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
				apply_filters( $this->plugin_name . '_display_convertkit_mapping', esc_html__( 'Mapping', 'convertkit-mm' ) ),
				array( $this, 'display_options_empty_mapping' ),
				$this->plugin_name,
				$this->plugin_name . '-ck-mapping'
			);
		} else {
			foreach ( $levels as $key => $name ) {
				add_settings_field(
					'convertkit-mapping-' . $key,
					apply_filters( $this->plugin_name . '_display_convertkit_mapping_' . $key, $name ),
					array( $this, 'display_options_convertkit_mapping' ),
					$this->plugin_name,
					$this->plugin_name . '-ck-mapping',
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
				apply_filters( $this->plugin_name . '_display_convertkit_mapping_product', esc_html__( 'Mapping', 'convertkit-mm' ) ),
				array( $this, 'display_options_empty_mapping_products' ),
				$this->plugin_name,
				$this->plugin_name . '-ck-mapping'
			);
		} else {
			foreach ( $products as $key => $name ) {
				add_settings_field(
					'convertkit-mapping-product-' . $key,
					apply_filters( $this->plugin_name . '_display_convertkit_mapping_product_' . $key, $name ),
					array( $this, 'display_options_convertkit_mapping_product' ),
					$this->plugin_name,
					$this->plugin_name . '-ck-mapping',
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
				apply_filters( $this->plugin_name . '_display_convertkit_mapping_bundle', esc_html__( 'Mapping', 'convertkit-mm' ) ),
				array( $this, 'display_options_empty_mapping_bundles' ),
				$this->plugin_name,
				$this->plugin_name . '-ck-mapping'
			);
		} else {
			foreach ( $bundles as $key => $name ) {
				add_settings_field(
					'convertkit-mapping-bundle-' . $key,
					apply_filters( $this->plugin_name . '_display_convertkit_mapping_bundle_' . $key, $name ),
					array( $this, 'display_options_convertkit_mapping_bundle' ),
					$this->plugin_name,
					$this->plugin_name . '-ck-mapping',
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
			apply_filters( $this->plugin_name . '_settings_page_title', esc_html__( 'ConvertKit MemberMouse Settings', 'convertkit-mm' ) ),
			apply_filters( $this->plugin_name . '_settings_menu_title', esc_html__( 'ConvertKit MemberMouse', 'convertkit-mm' ) ),
			'manage_options',
			$this->plugin_name,
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
		do_settings_sections( $this->plugin_name );
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

		$settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=' . $this->plugin_name ), esc_html__( 'Settings', 'convertkit-mm' ) );
		array_unshift( $links, $settings_link );
		return $links;

	}

	/**
	 * Outputs the API Key field in the settings screen
	 *
	 * @since       1.0.0
	 */
	public function display_options_api_key() {

		$api_key = $this->get_option( 'api-key' );
		?>
		<input type="text" id="<?php echo esc_attr( $this->plugin_name ); ?>-options[api-key]" name="<?php echo esc_attr( $this->plugin_name ); ?>-options[api-key]" value="<?php echo esc_attr( $api_key ); ?>" /><br/>
		<p class="description"><a href="https://app.convertkit.com/account/edit" target="_blank"><?php echo esc_html__( 'Get your ConvertKit API Key', 'convertkit-mm' ); ?></a></p>
		<?php

	}

	/**
	 * Outputs the Debug field in the settings screen
	 *
	 * @since       1.0.0
	 */
	public function display_options_debug() {

		$debug = $this->get_option( 'debug' );
		?>
		<input type="checkbox" id="<?php echo esc_attr( $this->plugin_name ); ?>-options[debug]" name="<?php echo esc_attr( $this->plugin_name ); ?>-options[debug]"<?php echo ( ( 'on' === $debug ) ? ' checked' : '' ); ?> />
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
		$tag         = $this->get_option( $option_name );
		$tag_cancel  = $this->get_option( $option_name . '-cancel' );
		$api_key     = $this->get_option( 'api-key' );

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
				<select id="<?php echo esc_attr( $this->plugin_name ); ?>-options[<?php echo esc_attr( $option_name ); ?>]" name="<?php echo esc_attr( $this->plugin_name ); ?>-options[<?php echo esc_attr( $option_name ); ?>]">
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
				<select id="<?php echo esc_attr( $this->plugin_name ); ?>-options[<?php echo esc_attr( $option_name ); ?>-cancel]" name="<?php echo esc_attr( $this->plugin_name ); ?>-options[<?php echo esc_attr( $option_name ); ?>-cancel]">
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
		$tag         = $this->get_option( $option_name );
		$api_key     = $this->get_option( 'api-key' );

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
				<select id="<?php echo esc_attr( $this->plugin_name ); ?>-options[<?php echo esc_attr( $option_name ); ?>]" name="<?php echo esc_attr( $this->plugin_name ); ?>-options[<?php echo esc_attr( $option_name ); ?>]">
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
		$tag         = $this->get_option( $option_name );
		$api_key     = $this->get_option( 'api-key' );

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
				<select id="<?php echo esc_attr( $this->plugin_name ); ?>-options[<?php echo esc_attr( $option_name ); ?>]" name="<?php echo esc_attr( $this->plugin_name ); ?>-options[<?php echo esc_attr( $option_name ); ?>]">
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
	 * A member was added to MemberMouse
	 *
	 * Member Data is sent to this hook including the new `membership_level`
	 * If membership level is > 0 then the user is being added to level with that ID.
	 * For info what is contained in the member_data see link.
	 *
	 * @see https://membermouse.uservoice.com/knowledgebase/articles/319072-membermouse-wordpress-hooks#member-data
	 *
	 * @since 1.0.0
	 *
	 * @param array $member_data    Member data.
	 */
	public function add_member( $member_data ) {

		if ( isset( $member_data['membership_level'] ) ) {
			$user_email = $member_data['email'];
			$first_name = rawurlencode( $member_data['first_name'] );
			$mapping    = 'convertkit-mapping-' . $member_data['membership_level'];
			$tag_id     = $this->get_option( $mapping );

			if ( ! empty( $tag_id ) ) {
				$this->api->add_tag_to_user( $user_email, $first_name, $tag_id );
				convertkit_mm_log( 'tag', 'Add tag ' . $tag_id . ' to user ' . $user_email . ' (' . $first_name . ')' );
			}
		}

	}

	/**
	 * Called when a member's status is changed in MemberMouse.
	 *
	 * @since   1.0.0
	 *
	 * @param array $member_data    Member data.
	 */
	public function status_change_member( $member_data ) {

		if ( isset( $member_data['membership_level'] ) ) {
			if ( isset( $member_data['status_name'] ) && 'Canceled' === $member_data['status_name'] ) {
				$user_email = $member_data['email'];
				$first_name = rawurlencode( $member_data['first_name'] );
				$mapping    = 'convertkit-mapping-' . $member_data['membership_level'] . '-cancel';
				$tag_id     = $this->get_option( $mapping );

				if ( ! empty( $tag_id ) ) {
					$this->api->add_tag_to_user( $user_email, $first_name, $tag_id );
					convertkit_mm_log( 'tag', 'Delete tag ' . $tag_id . ' to user ' . $user_email . ' (' . $first_name . ')' );
				}
			}
		}

	}

	/**
	 * Called when a member is deleted in MemberMouse.
	 *
	 * @since   1.0.0
	 *
	 * @param array $member_data    Member data.
	 */
	public function delete_member( $member_data ) {

		if ( isset( $member_data['membership_level'] ) ) {
			$user_email = $member_data['email'];
			$first_name = rawurlencode( $member_data['first_name'] );
			$mapping    = 'convertkit-mapping-' . $member_data['membership_level'] . '-cancel';
			$tag_id     = $this->get_option( $mapping );

			if ( ! empty( $tag_id ) ) {
				$this->api->add_tag_to_user( $user_email, $first_name, $tag_id );
				convertkit_mm_log( 'tag', 'Delete tag ' . $tag_id . ' to user ' . $user_email . ' (' . $user_name . ')' );
			}
		}

	}

	/**
	 * Assign a tag to the subscriber when purchasing a MemberMouse Product
	 * that is configured to tag a subscriber.
	 *
	 * @since   1.2.0
	 *
	 * @param   array $purchase_data  Checkout purchase data.
	 */
	public function purchase_product( $purchase_data ) {

		// Fetch data from purchase array.
		$user_email = $purchase_data['email'];
		$first_name = rawurlencode( $purchase_data['first_name'] );
		$mapping    = 'convertkit-mapping-product-' . $purchase_data['product_id'];
		$tag_id     = $this->get_option( $mapping );

		// If no tag assigned to this Product, bail.
		if ( empty( $tag_id ) ) {
			return;
		}

		// Assign tag to subscriber in ConvertKit.
		$this->api->add_tag_to_user( $user_email, $first_name, $tag_id );
		convertkit_mm_log( 'tag', 'Add product tag ' . $tag_id . ' to user ' . $user_email . ' (' . $first_name . ')' );

	}

	/**
	 * Assign a tag to the subscriber when purchasing a MemberMouse Product
	 * that is assigned to a bundle, and the bundle is configured to tag a subscriber.
	 *
	 * @since   1.2.0
	 *
	 * @param   array $purchase_data  Checkout purchase data.
	 */
	public function add_bundle( $purchase_data ) {

		// Fetch data from purchase array.
		$user_email = $purchase_data['email'];
		$first_name = rawurlencode( $purchase_data['first_name'] );
		$mapping    = 'convertkit-mapping-bundle-' . $purchase_data['bundle_id'];
		$tag_id     = $this->get_option( $mapping );

		// If no tag assigned to this Bundle, bail.
		if ( empty( $tag_id ) ) {
			return;
		}

		// Assign tag to subscriber in ConvertKit.
		$this->api->add_tag_to_user( $user_email, $first_name, $tag_id );
		convertkit_mm_log( 'tag', 'Add bundle tag ' . $tag_id . ' to user ' . $user_email . ' (' . $first_name . ')' );

	}

	/**
	 * Assign a tag to the subscriber when a member's bundle status is changed in MemberMouse.
	 *
	 * @since   1.2.0
	 *
	 * @param   array $member_data    Member data.
	 */
	public function status_change_bundle( $member_data ) {

		// Determine the status change.
		switch ( $member_data['bundle_status_name'] ) {
			case 'Active':
				$mapping = 'convertkit-mapping-bundle-' . $member_data['bundle_id'];
				break;
			case 'Canceled':
				$mapping = 'convertkit-mapping-bundle-' . $member_data['bundle_id'] . '-cancel';
				break;

			default:
				// Unsupported status at this time.
				return;
		}

		// Fetch data from member array.
		$user_email = $member_data['email'];
		$first_name = rawurlencode( $member_data['first_name'] );
		$tag_id     = $this->get_option( $mapping );

		// If no tag assigned to this Bundle, bail.
		if ( empty( $tag_id ) ) {
			return;
		}

		// Assign tag to subscriber in ConvertKit.
		$this->api->add_tag_to_user( $user_email, $first_name, $tag_id );
		convertkit_mm_log( 'tag', 'Add bundle tag ' . $tag_id . ' to user ' . $user_email . ' (' . $first_name . ')' );

	}

	/**
	 * Get the setting option requested.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $option_name    Option name.
	 * @return  string                  Option value
	 */
	public function get_option( $option_name ) {

		$options = get_option( $this->plugin_name . '-options' );
		$option  = '';

		if ( ! empty( $options[ $option_name ] ) ) {
			$option = $options[ $option_name ];
		}

		return $option;

	}

}
