<?php
/**
 * Tests the Plugin settings screen.
 *
 * @since   1.2.0
 */
class SettingsCest
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
		$I->memberMouseSetupPlugin($I);
	}

	/**
	 * Test that no PHP errors or notices are displayed on the Plugin's Setting screen
	 * and a Connect button is displayed when no credentials exist.
	 *
	 * @since   1.3.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testNoCredentials(AcceptanceTester $I)
	{
		// Go to the Plugin's Settings Screen.
		$I->amOnAdminPage('options-general.php?page=convertkit-mm');

		// Confirm no option is displayed to save settings, as the Plugin isn't authenticated.
		$I->dontSeeElementInDOM('input#submit');

		// Confirm the Connect button displays.
		$I->see('Connect');
		$I->dontSee('Disconnect');

		// Check that a link to the OAuth auth screen exists and includes the state parameter.
		$I->seeInSource('<a href="https://app.convertkit.com/oauth/authorize?client_id=' . $_ENV['CONVERTKIT_OAUTH_CLIENT_ID'] . '&amp;response_type=code&amp;redirect_uri=' . urlencode( $_ENV['CONVERTKIT_OAUTH_REDIRECT_URI'] ) );
		$I->seeInSource(
			'&amp;state=' . $I->apiEncodeState(
				$_ENV['TEST_SITE_WP_URL'] . '/wp-admin/options-general.php?page=convertkit-mm',
				$_ENV['CONVERTKIT_OAUTH_CLIENT_ID']
			)
		);

		// Click the connect button.
		$I->click('Connect');

		// Confirm the ConvertKit hosted OAuth login screen is displayed.
		$I->waitForElementVisible('body.sessions');
		$I->seeInSource('oauth/authorize?client_id=' . $_ENV['CONVERTKIT_OAUTH_CLIENT_ID']);
	}


	/**
	 * Test that no PHP errors or notices are displayed on the Plugin's Setting screen,
	 * and a warning is displayed that the supplied credentials are invalid, when
	 * e.g. the access token has been revoked.
	 *
	 * @since   1.3.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testInvalidCredentials(AcceptanceTester $I)
	{
		// Setup Plugin.
		$I->setupConvertKitPlugin(
			$I,
			[
				'access_token'  => 'fakeAccessToken',
				'refresh_token' => 'fakeRefreshToken',
			]
		);

		// Go to the Plugin's Settings Screen.
		$I->amOnAdminPage('options-general.php?page=convertkit-mm');

		// Confirm the Connect button displays.
		$I->see('Connect');
		$I->dontSee('Disconnect');
		$I->dontSeeElementInDOM('input#submit');
	}

	/**
	 * Test that no PHP errors or notices are displayed on the Plugin's Setting screen,
	 * when valid credentials exist.
	 *
	 * @since   1.3.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testValidCredentials(AcceptanceTester $I)
	{
		// Setup Plugin.
		$I->setupConvertKitPlugin($I);

		// Go to the Plugin's Settings Screen.
		$I->amOnAdminPage('options-general.php?page=convertkit-mm');

		// Confirm the Disconnect and Save Settings buttons display.
		$I->see('Disconnect');
		$I->seeElementInDOM('input#submit');

		// Save Settings to confirm credentials are not lost.
		$I->click('Save Settings');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the Disconnect and Save Settings buttons display.
		$I->see('Disconnect');
		$I->seeElementInDOM('input#submit');

		// Disconnect the Plugin connection to ConvertKit.
		$I->click('Disconnect');

		// Confirm the Connect button displays.
		$I->see('Connect');
		$I->dontSee('Disconnect');
		$I->dontSeeElementInDOM('input#submit');

		// Check that the option table no longer contains cached resources.
		$I->dontSeeOptionInDatabase('convertkit-mm-tags');
	}

	/**
	 * Test that saving settings on the settings screen with no changes
	 * works with no errors.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSaveSettingsWithNoChanges(AcceptanceTester $I)
	{
		// Setup Plugin.
		$I->setupConvertKitPlugin($I);

		// Go to the Plugin's Settings Screen.
		$I->amOnAdminPage('options-general.php?page=convertkit-mm');

		// Click save settings.
		$I->click('Save Settings');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);
	}

	/**
	 * Test that level to tag mapping changes on the settings screen
	 * works with no errors.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSaveLevelTagAssignment(AcceptanceTester $I)
	{
		// Setup Plugin.
		$I->setupConvertKitPlugin($I);

		// Go to the Plugin's Settings Screen.
		$I->amOnAdminPage('options-general.php?page=convertkit-mm');

		// Assign tags.
		$I->selectOption('convertkit-mm-options[convertkit-mapping-1]', $_ENV['CONVERTKIT_API_TAG_NAME']);
		$I->selectOption('convertkit-mm-options[convertkit-mapping-1-cancel]', $_ENV['CONVERTKIT_API_TAG_CANCEL_NAME']);

		// Click save settings.
		$I->click('Save Settings');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm settings saved.
		$I->see('Settings saved.');
		$I->seeOptionIsSelected('convertkit-mm-options[convertkit-mapping-1]', $_ENV['CONVERTKIT_API_TAG_NAME']);
		$I->seeOptionIsSelected('convertkit-mm-options[convertkit-mapping-1-cancel]', $_ENV['CONVERTKIT_API_TAG_CANCEL_NAME']);

		// Change tag back to 'None'.
		$I->selectOption('convertkit-mm-options[convertkit-mapping-1]', '(None)');
		$I->selectOption('convertkit-mm-options[convertkit-mapping-1-cancel]', '(None)');

		// Click save settings.
		$I->click('Save Settings');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm settings saved.
		$I->see('Settings saved.');
		$I->seeOptionIsSelected('convertkit-mm-options[convertkit-mapping-1]', '(None)');
		$I->seeOptionIsSelected('convertkit-mm-options[convertkit-mapping-1-cancel]', '(None)');
	}

	/**
	 * Test that product to tag mapping changes on the settings screen
	 * works with no errors.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSaveProductTagAssignment(AcceptanceTester $I)
	{
		// Create a product.
		$productID = $I->memberMouseCreateProduct($I, 'Product', $_ENV['MEMBERMOUSE_PRODUCT_REFERENCE_KEY']);

		// Setup Plugin.
		$I->setupConvertKitPlugin($I);

		// Go to the Plugin's Settings Screen.
		$I->amOnAdminPage('options-general.php?page=convertkit-mm');

		// Assign tags.
		$I->selectOption('convertkit-mm-options[convertkit-mapping-product-' . $productID . ']', $_ENV['CONVERTKIT_API_TAG_NAME']);

		// Click save settings.
		$I->click('Save Settings');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm settings saved.
		$I->see('Settings saved.');
		$I->seeOptionIsSelected('convertkit-mm-options[convertkit-mapping-product-' . $productID . ']', $_ENV['CONVERTKIT_API_TAG_NAME']);

		// Change tag back to 'None'.
		$I->selectOption('convertkit-mm-options[convertkit-mapping-product-' . $productID . ']', '(None)');

		// Click save settings.
		$I->click('Save Settings');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm settings saved.
		$I->see('Settings saved.');
		$I->seeOptionIsSelected('convertkit-mm-options[convertkit-mapping-product-' . $productID . ']', '(None)');
	}

	/**
	 * Test that bundle to tag mapping changes on the settings screen
	 * works with no errors.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSaveBundleTagAssignment(AcceptanceTester $I)
	{
		// Create a product.
		$productID = $I->memberMouseCreateProduct($I, 'Product', $_ENV['MEMBERMOUSE_PRODUCT_REFERENCE_KEY']);

		// Create bundle.
		$bundleID = $I->memberMouseCreateBundle($I, 'Bundle', [ $productID ]);

		// Setup Plugin.
		$I->setupConvertKitPlugin($I);

		// Go to the Plugin's Settings Screen.
		$I->amOnAdminPage('options-general.php?page=convertkit-mm');

		// Assign tags.
		$I->selectOption('convertkit-mm-options[convertkit-mapping-bundle-' . $bundleID . ']', $_ENV['CONVERTKIT_API_TAG_NAME']);
		$I->selectOption('convertkit-mm-options[convertkit-mapping-bundle-' . $bundleID . '-cancel]', $_ENV['CONVERTKIT_API_TAG_CANCEL_NAME']);

		// Click save settings.
		$I->click('Save Settings');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm settings saved.
		$I->see('Settings saved.');
		$I->seeOptionIsSelected('convertkit-mm-options[convertkit-mapping-bundle-' . $bundleID . ']', $_ENV['CONVERTKIT_API_TAG_NAME']);
		$I->seeOptionIsSelected('convertkit-mm-options[convertkit-mapping-bundle-' . $bundleID . '-cancel]', $_ENV['CONVERTKIT_API_TAG_CANCEL_NAME']);

		// Change tag back to 'None'.
		$I->selectOption('convertkit-mm-options[convertkit-mapping-bundle-' . $bundleID . ']', '(None)');
		$I->selectOption('convertkit-mm-options[convertkit-mapping-bundle-' . $bundleID . '-cancel]', '(None)');

		// Click save settings.
		$I->click('Save Settings');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm settings saved.
		$I->see('Settings saved.');
		$I->seeOptionIsSelected('convertkit-mm-options[convertkit-mapping-bundle-' . $bundleID . ']', '(None)');
		$I->seeOptionIsSelected('convertkit-mm-options[convertkit-mapping-bundle-' . $bundleID . '-cancel]', '(None)');
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
		$I->deactivateConvertKitPlugin($I);
		$I->resetConvertKitPlugin($I);
	}
}
