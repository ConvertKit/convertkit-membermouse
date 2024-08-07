<?php
/**
 * ConvertKit Resource class.
 *
 * @package CKWC
 * @author ConvertKit
 */

/**
 * Abstract class defining variables and functions for a ConvertKit API Resource
 * (forms, sequences, tags).
 *
 * @since   1.3.0
 */
class ConvertKit_MM_Actions_Resource extends ConvertKit_Resource_V4 {

	/**
	 * Constructor.
	 *
	 * @since   1.3.0
	 *
	 * @param   ConvertKit_MM_API $api_instance   API Instance.
	 */
	public function __construct( $api_instance ) {

		// Initialize the API using the supplied ConvertKit_MM_API instance.
		$this->api = $api_instance;

		// Call parent initialization function.
		parent::init();

	}

}
