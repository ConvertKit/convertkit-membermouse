<?php
/**
 * Tests that subscribers are added to ConvertKit and tagged
 * based on the membership level.
 *
 * @since   1.2.0
 */
class MemberTagCest
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
	public function testUserSubscribedAndTagged(AcceptanceTester $I)
	{
		// Setup Plugin to tag users added to the Free Membership level to the
		// ConvertKit Tag ID.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-1' => $_ENV['CONVERTKIT_API_TAG_ID'],
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Navigate to MemberMouse > Manage Members.
		$I->amOnAdminPage('admin.php?page=manage_members');

		// Create Member.
		$I->click('Create Member');
		$I->waitForElementVisible('#mm-new-member-form-container');
		$I->fillField('#mm-new-first-name', 'First');
		$I->fillField('#mm-new-last-name', 'Last');
		$I->fillField('#mm-new-email', $emailAddress);
		$I->fillField('#mm-new-password', '12345678');
		$I->click('Create Member', '.mm-dialog-button-container');
		$I->waitForElementNotVisible('#mm-new-member-form-container');

		// Accept popup once user created.
		// We have to wait as there's no specific event MemberMouse fires to tell
		// us it completed adding the member.
		$I->wait(3);
		$I->acceptPopup();
		$I->wait(3);

		// Check subscriber exists.
		$subscriberID = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the tag.
		$I->apiCheckSubscriberHasTag($I, $subscriberID, $_ENV['CONVERTKIT_API_TAG_ID']);
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
