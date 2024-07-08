<?php
/**
 * Actions class.
 *
 * @package ConvertKit_MM
 * @author ConvertKit
 */

/**
 * Actions class.
 *
 * @package ConvertKit_MM
 * @author ConvertKit
 */
class ConvertKit_MM_Actions {

	/**
	 * Constructor. Registers hooks when specific MemberMouse actions are performed,
	 * such as adding a member, purchasing a product and being assigned to a bundle.
	 *
	 * @since   1.2.0
	 */
	public function __construct() {

		// Tag on Membership Level.
		add_action( 'mm_member_add', array( $this, 'add_member' ) );
		add_action( 'mm_member_membership_change', array( $this, 'add_member' ) );
		add_action( 'mm_member_status_change', array( $this, 'status_change_member' ) );
		add_action( 'mm_member_delete', array( $this, 'delete_member' ) );

		// Tag on Product.
		add_action( 'mm_product_purchase', array( $this, 'purchase_product' ) );

		// Tag on Bundle.
		add_action( 'mm_bundles_add', array( $this, 'add_bundle' ) );
		add_action( 'mm_bundles_status_change', array( $this, 'status_change_bundle' ) );

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
	 * @since   1.0.0
	 *
	 * @param   array $member_data    Member data.
	 */
	public function add_member( $member_data ) {

		// Bail if no membership level data exists.
		if ( ! isset( $member_data['membership_level'] ) ) {
			return;
		}

		// Fetch data from member array.
		$user_email = $member_data['email'];
		$first_name = rawurlencode( $member_data['first_name'] );
		$mapping    = 'convertkit-mapping-' . $member_data['membership_level'];
		$tag_id     = convertkit_mm_get_option( $mapping );

		// Bail if no tag mapping exists, as this means we don't need to tag the subscriber.
		if ( empty( $tag_id ) ) {
			return;
		}

		// Subscribe and tag.
		$this->add_tag_to_user( $user_email, $first_name, $tag_id );
		convertkit_mm_log( 'tag', 'Add tag ' . $tag_id . ' to user ' . $user_email . ' (' . $first_name . ')' );

	}

	/**
	 * Called when a member's status is changed in MemberMouse.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $member_data    Member data.
	 */
	public function status_change_member( $member_data ) {

		// Bail if no membership level data exists.
		if ( ! isset( $member_data['membership_level'] ) ) {
			return;
		}

		// Bail if the status isn't set to cancelled.
		if ( ! isset( $member_data['status_name'] ) ) {
			return;
		}
		if ( $member_data['status_name'] !== 'Canceled' ) {
			return;
		}

		// Fetch data from member array.
		$user_email = $member_data['email'];
		$first_name = rawurlencode( $member_data['first_name'] );
		$mapping    = 'convertkit-mapping-' . $member_data['membership_level'] . '-cancel';
		$tag_id     = convertkit_mm_get_option( $mapping );

		// Bail if no tag mapping exists, as this means we don't need to tag the subscriber.
		if ( empty( $tag_id ) ) {
			return;
		}

		// Subscribe and tag.
		$this->add_tag_to_user( $user_email, $first_name, $tag_id );
		convertkit_mm_log( 'tag', 'Delete tag ' . $tag_id . ' to user ' . $user_email . ' (' . $first_name . ')' );

	}

	/**
	 * Called when a member is deleted in MemberMouse.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $member_data    Member data.
	 */
	public function delete_member( $member_data ) {

		// Bail if no membership level data exists.
		if ( ! isset( $member_data['membership_level'] ) ) {
			return;
		}

		// Fetch data from member array.
		$user_email = $member_data['email'];
		$first_name = rawurlencode( $member_data['first_name'] );
		$mapping    = 'convertkit-mapping-' . $member_data['membership_level'] . '-cancel';
		$tag_id     = convertkit_mm_get_option( $mapping );

		// Bail if no tag mapping exists, as this means we don't need to tag the subscriber.
		if ( empty( $tag_id ) ) {
			return;
		}

		// Subscribe and tag.
		$this->add_tag_to_user( $user_email, $first_name, $tag_id );
		convertkit_mm_log( 'tag', 'Delete tag ' . $tag_id . ' to user ' . $user_email . ' (' . $user_name . ')' );

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
		$tag_id     = convertkit_mm_get_option( $mapping );

		// If no tag assigned to this Product, bail.
		if ( empty( $tag_id ) ) {
			return;
		}

		// Assign tag to subscriber in ConvertKit.
		$this->add_tag_to_user( $user_email, $first_name, $tag_id );
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
		$tag_id     = convertkit_mm_get_option( $mapping );

		// If no tag assigned to this Bundle, bail.
		if ( empty( $tag_id ) ) {
			return;
		}

		// Assign tag to subscriber in ConvertKit.
		$this->add_tag_to_user( $user_email, $first_name, $tag_id );
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
		$tag_id     = convertkit_mm_get_option( $mapping );

		// If no tag assigned to this Bundle, bail.
		if ( empty( $tag_id ) ) {
			return;
		}

		// Assign tag to subscriber in ConvertKit.
		$this->add_tag_to_user( $user_email, $first_name, $tag_id );
		convertkit_mm_log( 'tag', 'Add bundle tag ' . $tag_id . ' to user ' . $user_email . ' (' . $first_name . ')' );

	}

	/**
	 * Initializes the API, subscribing the given email address and assigning the
	 * subscriber to the ConvertKit Tag ID.
	 *
	 * @since   1.2.0
	 *
	 * @param   string $email          Email Address.
	 * @param   string $first_name     First Name.
	 * @param   int    $tag_id         Tag ID.
	 */
	private function add_tag_to_user( $email, $first_name, $tag_id ) {

		// Initialize API.
		$api_key   = convertkit_mm_get_option( 'api-key' );
		$api = new ConvertKit_MM_API( $api_key );

		// Send request.
		$api->add_tag_to_user( $email, $first_name, absint( $tag_id ) );

	}

}
