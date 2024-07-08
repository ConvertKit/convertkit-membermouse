<?php
/**
 * Tests that subscribers are added to ConvertKit and tagged
 * based on the product purchased.
 *
 * @since   1.2.0
 */
class ProductTagCest
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
	 * setting when purchasing the given product.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testMemberTaggedWhenProductPurchased(AcceptanceTester $I)
	{
		// Create a product.
		$productReferenceKey = 'pTRLc9';
		$productID           = $I->memberMouseCreateProduct($I, 'Product', $productReferenceKey);

		// Setup Plugin to tag users purchasing the product to the
		// ConvertKit Tag ID.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-product-1' => $_ENV['CONVERTKIT_API_TAG_ID'],
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Enable test mode for payments.
		$I->amOnAdminPage('admin.php?page=payment_settings');
		$I->checkOption('test_payment_service_enabled');
		$I->click('Save Payment Methods');
		$I->wait(5);
		$I->acceptPopup();

		// Logout.
		// We don't use logOut() as MemberMouse hijacks the logout process with a redirect,
		// resulting in the logOut() assertion `loggedout=true` failing.
		$I->amOnPage('wp-login.php?action=logout');
		$I->click("//a[contains(@href,'action=logout')]");

		// Navigate to purchase screen for the product.
		$I->amOnPage('checkout/?rid=' . $productReferenceKey);

		// Complete checkout.
		$I->fillField('mm_field_first_name', 'First');
		$I->fillField('mm_field_last_name', 'Last');
		$I->fillField('mm_field_email', $emailAddress);
		$I->fillField('mm_field_password', '12345678');
		$I->fillField('mm_field_phone', '12345678');
		$I->fillField('mm_field_cc_number', '4242424242424242');
		$I->fillField('mm_field_cc_cvv', '123');
		$I->selectOption('mm_field_cc_exp_year', '2038');
		$I->fillField('mm_field_billing_address', '123 Main Street');
		$I->fillField('mm_field_billing_city', 'Nashville');
		$I->selectOption('#mm_field_billing_state_dd', 'Tennessee');
		$I->fillField('mm_field_billing_zip', '37208');

		// Submit.
		$I->click('Submit Order');

		// Wait for confirmation.
		$I->waitForText('Thank you for your order');

		// Check subscriber exists.
		$subscriberID = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the tag.
		$I->apiCheckSubscriberHasTag($I, $subscriberID, $_ENV['CONVERTKIT_API_TAG_ID']);
	}

	/**
	 * Test that the member is not tagged when the configured "apply tag on add"
	 * setting is "none" for the given purchased product.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testMemberNotTaggedWhenProductPurchased(AcceptanceTester $I)
	{
		// Create a product.
		$productReferenceKey = 'pTRLc9';
		$productID           = $I->memberMouseCreateProduct($I, 'Product', $productReferenceKey);

		// Setup Plugin to not tag users purchasing the product to the
		// ConvertKit Tag ID.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-product-1' => '',
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Enable test mode for payments.
		$I->amOnAdminPage('admin.php?page=payment_settings');
		$I->checkOption('test_payment_service_enabled');
		$I->click('Save Payment Methods');
		$I->wait(5);
		$I->acceptPopup();

		// Logout.
		// We don't use logOut() as MemberMouse hijacks the logout process with a redirect,
		// resulting in the logOut() assertion `loggedout=true` failing.
		$I->amOnPage('wp-login.php?action=logout');
		$I->click("//a[contains(@href,'action=logout')]");

		// Navigate to purchase screen for the product.
		$I->amOnPage('checkout/?rid=' . $productReferenceKey);

		// Complete checkout.
		$I->fillField('mm_field_first_name', 'First');
		$I->fillField('mm_field_last_name', 'Last');
		$I->fillField('mm_field_email', $emailAddress);
		$I->fillField('mm_field_password', '12345678');
		$I->fillField('mm_field_phone', '12345678');
		$I->fillField('mm_field_cc_number', '4242424242424242');
		$I->fillField('mm_field_cc_cvv', '123');
		$I->selectOption('mm_field_cc_exp_year', '2038');
		$I->fillField('mm_field_billing_address', '123 Main Street');
		$I->fillField('mm_field_billing_city', 'Nashville');
		$I->selectOption('#mm_field_billing_state_dd', 'Tennessee');
		$I->fillField('mm_field_billing_zip', '37208');

		// Submit.
		$I->click('Submit Order');

		// Wait for confirmation.
		$I->waitForText('Thank you for your order');

		// Check subscriber does not exist.
		$subscriberID = $I->apiCheckSubscriberDoesNotExist($I, $emailAddress);
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
