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
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-convertkit-mm-api.php';

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

		// add_settings_section( $id, $title, $callback, $menu_slug );
		add_settings_section(
			$this->plugin_name . '-display-options',
			apply_filters( $this->plugin_name . '-display-section-title', __( 'General', 'convertkit-mm' ) ),
			array( $this, 'display_options_section' ),
			$this->plugin_name
		);

		// add_settings_field( $id, $title, $callback, $menu_slug, $section, $args );
		add_settings_field(
			'api-key',
			apply_filters( $this->plugin_name . '-display-api-key', __( 'API Key', 'convertkit-mm' ) ),
			array( $this, 'display_options_api_key' ),
			$this->plugin_name,
			$this->plugin_name . '-display-options'
		);

		// add_settings_section( $id, $title, $callback, $menu_slug );
		add_settings_section(
			$this->plugin_name . '-ck-mapping',
			apply_filters( $this->plugin_name . '-display-mapping-title', __( 'Assign Tags', 'convertkit-mm' ) ),
			array( $this, 'display_mapping_section' ),
			$this->plugin_name
		);

		// Get all MemberMouse membership levels
		$levels = $this->get_mm_membership_levels();

		// Get all tags from ConvertKit
		$tags = $this->api->get_tags();

		// No MM mappings created yet
		if ( empty ( $levels ) ){

			add_settings_field(
				'convertkit-empty-mapping',
				apply_filters( $this->plugin_name . '-display-convertkit-mapping', __( 'Mapping', 'convertkit-mm' ) ),
				array( $this, 'display_options_empty_mapping' ),
				$this->plugin_name,
				$this->plugin_name . '-ck-mapping'
			);

		} else {


			add_settings_field(
				'convertkit-membership-levels',
				apply_filters( $this->plugin_name . '-display-convertkit-mapping', __( 'Membership Levels', 'convertkit-mm' ) ),
				function(){return false;},
				$this->plugin_name,
				$this->plugin_name . '-ck-mapping'
			);

			foreach( $levels as $key => $name ) {

				add_settings_field(
					'convertkit-mapping-' . $key,
					apply_filters( $this->plugin_name . '-display-convertkit-mapping-' . $key , $name ),
					array( $this, 'display_options_convertkit_mapping' ),
					$this->plugin_name,
					$this->plugin_name . '-ck-mapping',
					array( 'key' => $key,
					       'name' => $name,
					       'tags' => $tags,
					)
				);
			}

		}

		// Get all MemberMouse bundles
		$bundles = $this->get_mm_bundles();

		// No MM mappings created yet
		if ( empty ( $bundles ) ){

			add_settings_field(
				'convertkit-empty-mapping',
				apply_filters( $this->plugin_name . '-display-convertkit-mapping', __( 'Mapping', 'convertkit-mm' ) ),
				array( $this, 'display_options_empty_mapping' ),
				$this->plugin_name,
				$this->plugin_name . '-ck-mapping'
			);

		} else {

			add_settings_field(
				'convertkit-bundles',
				apply_filters( $this->plugin_name . '-display-convertkit-mapping', __( 'Bundles', 'convertkit-mm' ) ),
				function(){return false;},
				$this->plugin_name,
				$this->plugin_name . '-ck-mapping'
			);


			foreach( $bundles as $key => $name ) {
				//add_settings_field($id, $title, $callback, $page, $section = 'default', $args = array()) {
				add_settings_field(
					'convertkit-mapping-bundle-' . $key,
					apply_filters( $this->plugin_name . '-display-convertkit-mapping-bundle-' . $key , $name ),
					array( $this, 'display_options_convertkit_mapping' ),
					$this->plugin_name,
					$this->plugin_name . '-ck-mapping',
					array( 'key' => 'bundle-' . $key,
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
	 * @since 		1.0.0
	 * @return 		void
	 */
	public function add_menu() {
		// add_options_page( $page_title, $menu_title, $capability, $menu_slug, $callback );
		add_options_page(
			apply_filters( $this->plugin_name . '-settings-page-title', __( 'ConvertKit MemberMouse Settings', 'convertkit-mm' ) ),
			apply_filters( $this->plugin_name . '-settings-menu-title', __( 'ConvertKit MemberMouse', 'convertkit-mm' ) ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'options_page' )
		);
	}


	/**
	 * Creates the options page
	 *
	 * @since 		1.0.0
	 * @return 		void
	 */
	public function options_page() {
		?><div class="wrap"><h1><?php echo esc_html( get_admin_page_title() ); ?></h1></div>
		<form action="options.php" method="post"><?php
		settings_fields( 'convertkit-mm-options' );
		do_settings_sections( $this->plugin_name );
		submit_button( 'Save Settings' );
		?></form><?php
	}


	/**
	 * Validates saved options
	 *
	 * @since 		1.0.0
	 * @param 		array 		$input 			array of submitted plugin options
	 * @return 		array 						array of validated plugin options
	 */
	public function validate_options( $input ) {


		return $input;
	}


	/**
	 * Creates a settings section
	 *
	 * @since 		1.0.0
	 * @param 		array 		$params 		Array of parameters for the section
	 * @return 		mixed 						The settings section
	 */
	public function display_options_section( $params ) {
		echo '<p>' . __( 'Add your API key below and then choose a default form to add subscribers to.','convertkit-mm') .'</p>';
	}


	/**
	 * Creates a settings section
	 *
	 * @since 		1.0.0
	 * @param 		array 		$params 		Array of parameters for the section
	 * @return 		mixed 						The settings section
	 */
	public function display_mapping_section( $params ) {
		echo '<p>' . __( 'Below is a list of the defined MemberMmouse Membership Levels. Assign a membership level to a ConvertKit tag that will be assigned to members of that level.','convertkit-mm') .'</p>';
	}


	/**
	 * Adds a link to the plugin settings page
	 *
	 * @since 		1.0.0
	 * @param 		array 		$links 		The current array of links
	 * @return 		array 					The modified array of links
	 */
	public function settings_link( $links ) {

		$settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=' . $this->plugin_name ), __( 'Settings', 'convertkit-mm' ) );
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Creates a settings input for the API key.
	 *
	 * @since 		1.0.0
	 * @return 		mixed 			The settings field
	 */
	public function display_options_api_key() {
		$api_key = $this->get_option( 'api-key' );

		?><input type="text" id="<?php echo $this->plugin_name; ?>-options[api-key]" name="<?php echo $this->plugin_name; ?>-options[api-key]" value="<?php echo esc_attr( $api_key ); ?>" /><br/>
		<p class="description"><a href="https://app.convertkit.com/account/edit" target="_blank"><?php echo __( 'Get your ConvertKit API Key', 'convertkit-mm' ); ?></a></p><?php
	}

	/**
	 * Empty mapping callback
	 *
	 * No MM Membership Levels have been added yet.
	 *
	 * @since 		1.0.0
	 * @return 		mixed 			The settings field
	 */
	public function display_options_empty_mapping() {
		?>
		<p><?php echo __( 'No MM Membership Levels have been added yet.', 'converkit-mm'); ?><br/>
			<?php echo sprintf( __( 'You can add one <a href="%s">here</a>.', 'converkit-mm'), get_admin_url( null, '/admin.php?page=mmro-membershiplevels' ) ); ?></p>
		<?php
	}

	/**
	 * Display mapping for the specified key.
	 *
	 * @since 1.0.0
	 * @param string $args
	 */
	public function display_options_convertkit_mapping( $args ) {

		$option_name = 'convertkit-mapping-' . $args['key'];
		$tag         = $this->get_option( $option_name );
		$api_key     = $this->get_option( 'api-key' );

		if ( empty( $api_key ) ) {
			?><p><?php echo __( 'Enter API key to retrieve list of tags.', 'convertkit-mm' ); ?></p><?php
		} elseif( is_null( $args['tags'] ) ) {
			?><p><?php echo __( 'No tags were returned from ConvertKit.', 'convertkit-mm' ); ?></p><?php
		} else {

			?><select id="<?php echo $this->plugin_name; ?>-options[<?php echo $option_name ?>]"
			          name="<?php echo $this->plugin_name; ?>-options[<?php echo $option_name ?>]"><?php
				?>
				<option value=""><?php echo __( 'None', 'convertkit-mm' ); ?></option>
			<?php

			foreach ( $args['tags'] as $value => $text ) {
				?>
				<option value="<?php echo $value; ?>" <?php selected( $tag, $value ); ?>><?php echo $text; ?></option><?php
			}
			?></select><?php
		}

	}

	/**
	 * Get all MemberMouse membership levels
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_mm_membership_levels(){

		global $wpdb;

		$levels = array();
		if ( defined( 'MM_TABLE_MEMBERSHIP_LEVELS' ) ) {
			$sql = "SELECT id, name, status FROM " . MM_TABLE_MEMBERSHIP_LEVELS;
		} else {
			return $levels;
		}

		$result = $wpdb->get_results( $sql, OBJECT );

		foreach ( $result as $_level ){
			$levels[ $_level->id ] = $_level->name;
		}

		return $levels;

	}

	/**
	 *
	 */
	public function get_mm_bundles(){

		global $wpdb;

		$levels = array();
		if ( defined( 'MM_TABLE_BUNDLES' ) ) {
			$sql = "SELECT id, name, status FROM " . MM_TABLE_BUNDLES;
		} else {
			return $levels;
		}

		$result = $wpdb->get_results( $sql, OBJECT );

		foreach ( $result as $_level ){
			$levels[ $_level->id ] = $_level->name;
		}

		return $levels;
	}

	/**
	 * A member was added to MemberMouse
	 *
	 * Member Data is sent to this hook including the new `membership_level`
	 * If membership level is > 0 then the user is being added to level with that ID.
	 * For info what is contained in the member_data see link.
	 * @see https://membermouse.uservoice.com/knowledgebase/articles/319072-membermouse-wordpress-hooks#member-data
	 *
	 * @since 1.0.0
	 * @param array $member_data
	 */
	public function add_member( $member_data ) {

		$this->log( "In add_member" );
		$this->log( "member data:\n" . print_r( $member_data, true ) );
		if( isset( $member_data['membership_level'] ) ){

			$user_email = $member_data['email'];
			$user_name = urlencode( $member_data['first_name'] . ' ' . $member_data['last_name'] );
			$mapping = 'convertkit-mapping-' . $member_data['membership_level'];
			$tag_id = $this->get_option( $mapping );

			if (! empty( $tag_id ) ){
				$this->api->add_tag_to_user( $user_email, $user_name, $tag_id );
			}
		}

	}

	public function add_bundle( $member_data, $bundle_data ) {

		$this->log( "In add_bundle" );
		$this->log( "member data:\n" . print_r( $member_data, true ) );
		$this->log( "bundle data:\n" . print_r( $bundle_data, true ) );
		if( isset( $member_data['bundle_id'] ) ) {

			$user_email = $member_data['email'];
			$user_name = urlencode( $member_data['first_name'] . ' ' . $member_data['last_name'] );
			$mapping = 'convertkit-mapping-bundle-' . $member_data['bundle_id'];
			$tag_id = $this->get_option( $mapping );

			if (! empty( $tag_id ) ){
				$this->api->add_tag_to_user( $user_email, $user_name, $tag_id );
			}
		}

	}

	/**
	 * Get the setting option requested.
	 *
	 * @since   1.0.0
	 * @param   $option_name
	 * @return  string $option
	 */
	public function get_option( $option_name ){

		$options = get_option( $this->plugin_name . '-options' );
		$option = '';

		if ( ! empty( $options[ $option_name ] ) ) {
			$option = $options[ $option_name ];
		}

		return $option;
	}

	/**
	 * Output data to log file
	 *
	 * @param string $message String to add to log.
	 */
	public function log( $message ) {

		$dir = dirname( __FILE__ );
		$handle = fopen( trailingslashit( $dir ) . 'log.txt', 'a' );
		if ( $handle ) {
			$time   = date_i18n( 'm-d-Y @ H:i:s -' );
			fwrite( $handle, $time . ' ' . $message . "\n" );
			fclose( $handle );
		}
	}

}