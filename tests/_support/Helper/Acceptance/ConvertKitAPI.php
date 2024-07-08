<?php
namespace Helper\Acceptance;

/**
 * Helper methods and actions related to the ConvertKit API,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.2.0
 */
class ConvertKitAPI extends \Codeception\Module
{
	/**
	 * Check the given email address exists as a subscriber.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I              AcceptanceTester.
	 * @param   string           $emailAddress   Email Address.
	 * @return  int                              Subscriber ID.
	 */
	public function apiCheckSubscriberExists($I, $emailAddress)
	{
		// Run request.
		$results = $this->apiRequest(
			'subscribers',
			'GET',
			[
				'email_address' => $emailAddress,
			]
		);

		// Check at least one subscriber was returned and it matches the email address.
		$I->assertGreaterThan(0, $results['total_subscribers']);
		$I->assertEquals($emailAddress, $results['subscribers'][0]['email_address']);

		return $results['subscribers'][0]['id'];
	}

	/**
	 * Check the given subscriber ID has been assigned to the given tag ID.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $subscriberID  Subscriber ID.
	 * @param   int              $tagID         Tag ID.
	 */
	public function apiCheckSubscriberHasTag($I, $subscriberID, $tagID)
	{
		// Run request.
		$results = $this->apiRequest(
			'subscribers/' . $subscriberID . '/tags',
			'GET'
		);

		// Confirm the tag has been assigned to the subscriber.
		$I->assertEquals($tagID, $results['tags'][0]['id']);
	}

	/**
	 * Check the given subscriber ID has no tags assigned.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $subscriberID  Subscriber ID.
	 */
	public function apiCheckSubscriberHasNoTags($I, $subscriberID)
	{
		// Run request.
		$results = $this->apiRequest(
			'subscribers/' . $subscriberID . '/tags',
			'GET'
		);

		// Confirm no tags have been assigned to the subscriber.
		$I->assertCount(0, $results['tags']);
	}

	/**
	 * Check the given subscriber ID has been assigned to the given number
	 * of tags.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $subscriberID  Subscriber ID.
	 * @param   int              $numberOfTags  Number of tags.
	 */
	public function apiCheckSubscriberTagCount($I, $subscriberID, $numberOfTags)
	{
		// Run request.
		$results = $this->apiRequest(
			'subscribers/' . $subscriberID . '/tags',
			'GET'
		);

		// Confirm the correct number of tags have been assigned to the subscriber.
		$I->assertEquals($numberOfTags, count($results['tags']));
	}

	/**
	 * Check the given email address does not exists as a subscriber.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   string           $emailAddress   Email Address.
	 */
	public function apiCheckSubscriberDoesNotExist($I, $emailAddress)
	{
		// Run request.
		$results = $this->apiRequest(
			'subscribers',
			'GET',
			[
				'email_address' => $emailAddress,
			]
		);

		// Check no subscribers are returned by this request.
		$I->assertEquals(0, $results['total_subscribers']);
	}

	/**
	 * Sends a request to the ConvertKit API, typically used to read an endpoint to confirm
	 * that data in an Acceptance Test was added/edited/deleted successfully.
	 *
	 * @since   1.2.0
	 *
	 * @param   string $endpoint   Endpoint.
	 * @param   string $method     Method (GET|POST|PUT).
	 * @param   array  $params     Endpoint Parameters.
	 */
	public function apiRequest($endpoint, $method = 'GET', $params = array())
	{
		// Build query parameters.
		$params = array_merge(
			$params,
			[
				'api_key'    => $_ENV['CONVERTKIT_API_KEY'],
				'api_secret' => $_ENV['CONVERTKIT_API_SECRET'],
			]
		);

		// Send request.
		try {
			$client = new \GuzzleHttp\Client();
			$result = $client->request(
				$method,
				'https://api.convertkit.com/v3/' . $endpoint . '?' . http_build_query($params),
				[
					'headers' => [
						'Accept-Encoding' => 'gzip',
						'timeout'         => 5,
					],
				]
			);

			// Return JSON decoded response.
			return json_decode($result->getBody()->getContents(), true);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			return [];
		}
	}
}
