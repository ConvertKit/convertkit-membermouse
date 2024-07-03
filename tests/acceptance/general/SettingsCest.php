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
		// Go to the Plugin's Settings > General Screen.
		$I->amOnAdminPage('options-general.php?page=convertkit-mm');

		// Click save settings.
		$I->click('Save Settings');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);
	}

	/**
	 * Test that saving a valid API Key on the settings screen
	 * works with no errors.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSaveValidAPIKey(AcceptanceTester $I)
	{
		// Go to the Plugin's Settings > General Screen.
		$I->amOnAdminPage('options-general.php?page=convertkit-mm');

		// Complete API Field.
		$I->fillField('convertkit-mm-options[api-key]', $_ENV['CONVERTKIT_API_KEY']);

		// Click save settings.
		$I->click('Save Settings');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm settings saved.
		$I->see('Settings saved.');
	}

	/**
	 * Test that saving an invalid API Key on the settings screen
	 * works with no errors.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSaveInvalidAPIKey(AcceptanceTester $I)
	{
		// Go to the Plugin's Settings > General Screen.
		$I->amOnAdminPage('options-general.php?page=convertkit-mm');

		// Complete API Field.
		$I->fillField('convertkit-mm-options[api-key]', 'fakeApiKey');

		// Click save settings.
		$I->click('Save Settings');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm settings saved.
		// The Plugin doesn't show an error if an invalid API Key is present; in the future
		// we'll want to add a notice in the Plugin and then test for it here.
		$I->see('Settings saved.');
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

		// Go to the Plugin's Settings > General Screen.
		$I->amOnAdminPage('options-general.php?page=convertkit-mm');

		// Assign tags.
		$I->selectOption('convertkit-mm-options[convertkit-mapping-1]', $_ENV['CONVERTKIT_API_TAG_NAME']);

		// Click save settings.
		$I->click('Save Settings');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm settings saved.
		$I->see('Settings saved.');
		$I->seeOptionIsSelected('convertkit-mm-options[convertkit-mapping-1]', $_ENV['CONVERTKIT_API_TAG_NAME']);

		// Change tag back to 'None'.
		$I->selectOption('convertkit-mm-options[convertkit-mapping-1]', '(None)');

		// Click save settings.
		$I->click('Save Settings');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm settings saved.
		$I->see('Settings saved.');
		$I->seeOptionIsSelected('convertkit-mm-options[convertkit-mapping-1]', '(None)');
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
		// Setup Plugin.
		$I->setupConvertKitPlugin($I);

		// Go to the Plugin's Settings > General Screen.
		$I->amOnAdminPage('options-general.php?page=convertkit-mm');

		// Assign tags.
		$I->selectOption('convertkit-mm-options[convertkit-mapping-1]', $_ENV['CONVERTKIT_API_TAG_NAME']);

		// Click save settings.
		$I->click('Save Settings');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm settings saved.
		$I->see('Settings saved.');
		$I->seeOptionIsSelected('convertkit-mm-options[convertkit-mapping-1]', $_ENV['CONVERTKIT_API_TAG_NAME']);

		// Change tag back to 'None'.
		$I->selectOption('convertkit-mm-options[convertkit-mapping-1]', '(None)');

		// Click save settings.
		$I->click('Save Settings');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm settings saved.
		$I->see('Settings saved.');
		$I->seeOptionIsSelected('convertkit-mm-options[convertkit-mapping-1]', '(None)');
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
