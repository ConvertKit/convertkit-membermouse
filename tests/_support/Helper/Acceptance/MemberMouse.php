<?php
namespace Helper\Acceptance;

/**
 * Helper methods and actions related to the MemberMouse Plugin,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.2.0
 */
class MemberMouse extends \Codeception\Module
{
	/**
	 * Helper method to create a membership level.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I     AcceptanceTester.
	 * @param   string           $name  Membership Level Name.
	 * @return  int                     Membership Level ID.
	 */
	public function memberMouseCreateMembershipLevel($I, $name)
	{
		return $I->haveInDatabase(
			'wp_mm_membership_levels',
			[
				'reference_key'      => 'm9BvU2',
				'is_free'            => 1,
				'is_default'         => 0,
				'name'               => $name,
				'description'        => $name,
				'wp_role'            => 'mm-ignore-role',
				'default_product_id' => 0,
				'status'             => 1,
			]
		);
	}

	/**
	 * Helper method to create a product.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I     AcceptanceTester.
	 * @param   string           $name  Product Name.
	 * @param   string           $key   Product Reference Key.
	 * @return  int                     Product ID.
	 */
	public function memberMouseCreateProduct($I, $name, $key)
	{
		return $I->haveInDatabase(
			'wp_mm_products',
			[
				'reference_key' => $key,
				'status'        => 1,
				'name'          => $name,
				'price'         => 1,
			]
		);
	}

	/**
	 * Helper method to create a bundle.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I            AcceptanceTester.
	 * @param   string           $name         Bundle Name.
	 * @param   array            $productIDs   Product IDs to assign to the bundle.
	 * @return  int                            Bundle ID.
	 */
	public function memberMouseCreateBundle($I, $name, $productIDs)
	{
		$bundleID = $I->haveInDatabase(
			'wp_mm_bundles',
			[
				'name'    => $name,
				'is_free' => 0,
				'status'  => 1,
			]
		);

		foreach ( $productIDs as $productID ) {
			$I->haveInDatabase(
				'wp_mm_bundle_products',
				[
					'bundle_id'  => $bundleID,
					'product_id' => $productID,
				]
			);
		}

		return $bundleID;
	}

	/**
	 * Helper method to enable test payments in MemberMouse.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I            AcceptanceTester.
	 */
	public function memberMouseEnableTestModeForPayments($I)
	{
		$I->amOnAdminPage('admin.php?page=payment_settings');
		$I->checkOption('test_payment_service_enabled');
		$I->click('Save Payment Methods');
		$I->wait(5);
		$I->acceptPopup();
	}

	/**
	 * Helper method to log out from WordPress when MemberMouse is enabled.
	 * We don't use logOut() as MemberMouse hijacks the logout process with a redirect,
	 * resulting in the logOut() assertion `loggedout=true` failing.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I            AcceptanceTester.
	 */
	public function memberMouseLogOut($I)
	{
		$I->amOnPage('wp-login.php?action=logout');
		$I->click("//a[contains(@href,'action=logout')]");
	}

	/**
	 * Helper method to complete the checkout process for the given MemberMouse
	 * Product by its reference key.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I                     AcceptanceTester.
	 * @param   string           $productReferenceKey   Product reference key.
	 */
	public function memberMouseCheckoutProduct($I, $productReferenceKey)
	{
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
	}
}
