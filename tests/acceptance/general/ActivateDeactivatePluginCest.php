<?php
/**
 * Tests Plugin activation and deactivation.
 *
 * @since   1.2.0
 */
class ActivateDeactivatePluginCest
{
	/**
	 * Test that activating the Plugin and the MemberMouse Plugins works
	 * with no errors.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testPluginActivationDeactivation(AcceptanceTester $I)
	{
		$I->activateConvertKitPlugin($I);
		$I->activateThirdPartyPlugin($I, 'membermouse');
		$I->deactivateConvertKitPlugin($I);
		$I->deactivateThirdPartyPlugin($I, 'membermouse');
	}

	/**
	 * Test that activating the Plugin, without activating the MemberMouse Plugin, works
	 * with no errors.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testPluginActivationDeactivationWithoutWPForms(AcceptanceTester $I)
	{
		$I->activateConvertKitPlugin($I);
		$I->deactivateConvertKitPlugin($I);
	}
}
