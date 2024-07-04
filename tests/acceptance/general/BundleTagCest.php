<?php
/**
 * Tests that subscribers are added to ConvertKit and tagged
 * based on the bundle assigned to a purchased product.
 *
 * @since   1.2.0
 */
class BundleTagCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _before(AcceptanceTester $I)
	{
		// Activate Plugins.
		$I->activateConvertKitPlugin($I);
		$I->activateThirdPartyPlugin($I, 'membermouse-platform');
	}

	/**
	 * Test that the member is tagged with the configured "apply tag on add"
	 * setting when purchasing a product assigned to the given bundle
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testMemberTaggedWhenBundleAdded(AcceptanceTester $I)
	{
		// Create a product.
		$productReferenceKey = 'pTRLc9';
		$productID           = $I->memberMouseCreateProduct($I, 'Product', $productReferenceKey);

		// Create bundle.
		$bundleID = $I->memberMouseCreateBundle($I, 'Bundle', [ $productID ]);

		// Setup Plugin to tag users purchasing the bundle to the
		// ConvertKit Tag ID.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-bundle-' . $bundleID => $_ENV['CONVERTKIT_API_TAG_ID'],
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Enable test mode for payments.
		$I->memberMouseEnableTestModeForPayments($I);

		// Logout.
		$I->memberMouseLogOut($I);

		// Complete checkout.
		$I->memberMouseCheckoutProduct($I, $productReferenceKey);

		// Check subscriber exists.
		$subscriberID = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the tag.
		$I->apiCheckSubscriberHasTag($I, $subscriberID, $_ENV['CONVERTKIT_API_TAG_ID']);
	}

	public function testMemberTaggedWhenBundleChanged(AcceptanceTester $I)
	{
	}

	/**
	 * Test that the member is tagged with the configured "apply tag on cancelled"
	 * setting when the given bundle for the member is cancelled.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testMemberTaggedWhenBundleCancelled(AcceptanceTester $I)
	{
		// Create a product.
		$productReferenceKey = 'pTRLc9';
		$productID           = $I->memberMouseCreateProduct($I, 'Product', $productReferenceKey);

		// Create bundle.
		$bundleID = $I->memberMouseCreateBundle($I, 'Bundle', [ $productID ]);

		// Setup Plugin to tag users purchasing the bundle to the
		// ConvertKit Tag ID.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-bundle-' . $bundleID => $_ENV['CONVERTKIT_API_TAG_ID'],
				'convertkit-mapping-bundle-' . $bundleID . '-cancel' => $_ENV['CONVERTKIT_API_TAG_CANCEL_ID'],
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Enable test mode for payments.
		$I->memberMouseEnableTestModeForPayments($I);

		// Logout.
		$I->memberMouseLogOut($I);

		// Complete checkout.
		$I->memberMouseCheckoutProduct($I, $productReferenceKey);

		// Check subscriber exists.
		$subscriberID = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the tag.
		$I->apiCheckSubscriberHasTag($I, $subscriberID, $_ENV['CONVERTKIT_API_TAG_ID']);

		// Cancel the user's bundle.
		$I->amOnAdminPage('admin.php?page=manage_members');
		$I->click($emailAddress);
		$I->click('Access Rights');
		$I->click('a[title="Cancel Paid Bundle"]');

		// Accept popups
		// We have to wait as there's no specific event MemberMouse fires to tell
		// us it completed changing the membership level.
		$I->wait(3);
		$I->acceptPopup();
		$I->wait(3);
		$I->acceptPopup();

		// Check that the subscriber has been assigned to the cancelled tag.
		$I->apiCheckSubscriberHasTag($I, $subscriberID, $_ENV['CONVERTKIT_API_TAG_CANCEL_ID']);
	}

	/**
	 * Test that the member is not tagged when the configured "apply tag on add"
	 * setting is "none" for the given bundle.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testMemberNotTaggedWhenBundleAdded(AcceptanceTester $I)
	{
		// Create a product.
		$productReferenceKey = 'pTRLc9';
		$productID           = $I->memberMouseCreateProduct($I, 'Product', $productReferenceKey);

		// Create bundle.
		$bundleID = $I->memberMouseCreateBundle($I, 'Bundle', [ $productID ]);

		// Setup Plugin to not tag users purchasing the bundle to the
		// ConvertKit Tag ID.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-bundle-' . $bundleID => '',
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Enable test mode for payments.
		$I->memberMouseEnableTestModeForPayments($I);

		// Logout.
		$I->memberMouseLogOut($I);

		// Complete checkout.
		$I->memberMouseCheckoutProduct($I, $productReferenceKey);

		// Check subscriber does not exist.
		$subscriberID = $I->apiCheckSubscriberDoesNotExist($I, $emailAddress);
	}

	public function testMemberNotTaggedWhenBundleChanged(AcceptanceTester $I)
	{
	}

	/**
	 * Test that the member is not tagged when the configured "apply tag on cancelled"
	 * setting is set to 'None' and the given bundle for the member is cancelled.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testMemberNotTaggedWhenBundleCancelled(AcceptanceTester $I)
	{
		// Create a product.
		$productReferenceKey = 'pTRLc9';
		$productID           = $I->memberMouseCreateProduct($I, 'Product', $productReferenceKey);

		// Create bundle.
		$bundleID = $I->memberMouseCreateBundle($I, 'Bundle', [ $productID ]);

		// Setup Plugin to tag users purchasing the bundle to the
		// ConvertKit Tag ID.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-bundle-' . $bundleID => $_ENV['CONVERTKIT_API_TAG_ID'],
				'convertkit-mapping-bundle-' . $bundleID . '-cancel' => '',
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Enable test mode for payments.
		$I->memberMouseEnableTestModeForPayments($I);

		// Logout.
		$I->memberMouseLogOut($I);

		// Complete checkout.
		$I->memberMouseCheckoutProduct($I, $productReferenceKey);

		// Check subscriber exists.
		$subscriberID = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the tag.
		$I->apiCheckSubscriberHasTag($I, $subscriberID, $_ENV['CONVERTKIT_API_TAG_ID']);

		// Cancel the user's bundle.
		$I->amOnAdminPage('admin.php?page=manage_members');
		$I->click($emailAddress);
		$I->click('Access Rights');
		$I->click('a[title="Cancel Paid Bundle"]');

		// Accept popups
		// We have to wait as there's no specific event MemberMouse fires to tell
		// us it completed changing the membership level.
		$I->wait(3);
		$I->acceptPopup();
		$I->wait(3);
		$I->acceptPopup();

		// Check that the subscriber is still assigned to the first tag and has no additional tags.
		$I->apiCheckSubscriberHasTag($I, $subscriberID, $_ENV['CONVERTKIT_API_TAG_ID']);
		$I->apiCheckSubscriberTagCount($I, $subscriberID, 1);
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _passed(AcceptanceTester $I)
	{
		$I->deactivateThirdPartyPlugin($I, 'membermouse-platform');
		$I->deactivateConvertKitPlugin($I);
		$I->resetConvertKitPlugin($I);
	}
}
