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
}
