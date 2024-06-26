<?php
/**
 * ConvertKit API specific functionality
 *
 * @link       http://www.convertkit.com
 * @since      1.0.0
 *
 * @package    ConvertKit_MM
 * @subpackage ConvertKit_MM/includes
 */

/**
 * ConvertKit API specific functionality.
 *
 * Handles all API calls.
 *
 * @package    ConvertKit_MM
 * @subpackage ConvertKit_MM/includes
 * @author     Daniel Espinoza <daniel@growdevelopment.com>
 */
class ConvertKit_MM_API {

	/**
	 * API version
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	protected $api_version = 'v3';

	/**
	 * API URL
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	protected $api_url = 'https://api.convertkit.com';

	/**
	 * API Key
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	protected $api_key;

	/**
	 * ConvertKit Forms
	 *
	 * @since   1.0.0
	 *
	 * @var     array
	 */
	protected $forms;

	/**
	 * ConvertKit Tags
	 *
	 * @since   1.0.0
	 *
	 * @var     array
	 */
	protected $tags;

	/**
	 * Initialize the class.
	 *
	 * @since    1.0.0
	 *
	 * @param    string $api_key    API Key.
	 */
	public function __construct( $api_key ) {

		$this->api_key = $api_key;

	}

	/**
	 * Get an array of forms and IDs from the API
	 *
	 * @since   1.0.0
	 *
	 * @return mixed
	 */
	public function get_forms() {

		$forms = get_transient( 'convertkit_mm_form_data' );

		if ( false === $forms ) {
			$data = $this->do_api_call( 'forms' );

			if ( ! is_wp_error( $data ) ) {

				$forms = $data;
				set_transient( 'convertkit_mm_form_data', $forms, 24 * 24 );
			}
		}

		if ( ! empty( $forms ) && isset( $forms['forms'] ) && ! empty( $forms['forms'] ) ) {

			foreach ( $forms['forms'] as $key => $form ) {
				$this->forms[ $form['id'] ] = $form['name'];
			}
		}

		return $this->forms;

	}


	/**
	 * Get an array of tags and IDs from the API
	 *
	 * @since   1.0.0
	 *
	 * @return mixed
	 */
	public function get_tags() {

		$tags = get_transient( 'convertkit_mm_tag_data' );

		if ( false === $tags || empty( $tags ) ) {
			$data = $this->do_api_call( 'tags' );

			if ( ! is_wp_error( $data ) ) {

				$tags = $data;
				set_transient( 'convertkit_mm_tag_data', $tags, 24 * 24 );
			}
		}

		if ( ! empty( $tags ) && isset( $tags['tags'] ) && ! empty( $tags['tags'] ) ) {

			foreach ( $tags['tags'] as $key => $tag ) {
				$this->tags[ $tag['id'] ] = $tag['name'];
			}
		}

		return $this->tags;

	}

	/**
	 * Add a tag to the subscriber.
	 *
	 * @since   1.0.0
	 *
	 * @param string $user_email    Email Address.
	 * @param string $first_name    First name.
	 * @param int    $tag_id        ConvertKit Tag ID.
	 */
	public function add_tag_to_user( $user_email, $first_name, $tag_id ) {

		$args = array(
			'first_name' => $first_name,
			'email'      => $user_email,
		);

		$this->do_api_call( 'tags/' . $tag_id . '/subscribe', $args, 'POST' );

	}

	/**
	 * Make a remote call to ConvertKit's API.
	 *
	 * @since   1.0.0
	 * @param   string $path           API endpoint.
	 * @param   array  $query_args     Query arguments.
	 * @param   string $method         Request method.
	 * @param   null   $body           Body.
	 * @param   array  $request_args   Request arguments.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function do_api_call( $path, $query_args = array(), $method = 'GET', $body = null, $request_args = array() ) {

		$api_key = $this->api_key;

		if ( '' === $api_key ) {
			return array();
		}

		// Setup the URL endpoint.
		$request_url           = $this->api_url . '/' . $this->api_version . '/' . $path;
		$query_args['api_key'] = $api_key;
		$request_url           = add_query_arg( $query_args, $request_url );

		// Setup the request args.
		$request_args = array_merge(
			array(
				'body'    => $body,
				'headers' => array(
					'Accept' => 'application/json',
				),
				'method'  => $method,
				'timeout' => 30,

			),
			$request_args
		);

		convertkit_mm_log( 'api', 'Request url: ' . $request_url );
		convertkit_mm_log( 'api', 'Request args: ' . print_r( $request_args, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions

		// Do the request.
		$response = wp_remote_request( $request_url, $request_args );

		// Handle the response.
		if ( is_wp_error( $response ) ) {
			return $response;
		} else {
			$response_body = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $response_body, true );

			if ( is_null( $response_data ) ) {
				convertkit_mm_log( 'api', 'Response data not null. ' . print_r( $response, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
				return new WP_Error( 'parse_failed', __( 'Could not parse response from ConvertKit', 'convertkit-mm' ) );
			} elseif ( isset( $response_data['error'] ) && isset( $response_data['message'] ) ) {
				return new WP_Error( $response_data['error'], $response_data['message'] );
			} else {
				return $response_data;
			}
		}

	}

}
