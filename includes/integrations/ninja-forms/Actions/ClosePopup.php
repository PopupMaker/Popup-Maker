<?php
/**
 * Integrations for ninja-forms actions close popup
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NF_Action_SuccessMessage
 */
final class NF_PUM_Actions_ClosePopup extends NF_Abstracts_Action {

	/**
	 * @var string
	 */
	protected $_name = 'closepopup';

	/**
	 * @var array
	 */
	protected $_tags = [];

	/**
	 * @var string
	 */
	protected $_timing = 'late';

	/**
	 * @var int
	 */
	protected $_priority = 10;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->_nicename = __( 'Close Popup', 'popup-maker' );

		$settings = [
			'close_delay' => [
				'name'        => 'close_delay',
				'type'        => 'number',
				'group'       => 'primary',
				'label'       => __( 'Delay', 'popup-maker' ) . ' (' . __( 'seconds', 'popup-maker' ) . ')',
				'placeholder' => '',
				'width'       => 'full',
				'value'       => __( '0', 'popup-maker' ),
			],
		];

		$this->_settings = array_merge( $this->_settings, $settings );
	}

	/*
	* PUBLIC METHODS
	*/

	public function save( $action_settings ) {

	}

	public function process( $action_settings, $form_id, $data ) {

		if ( ! isset( $data['actions'] ) || ! isset( $data['actions']['closepopup'] ) ) {
			$data['actions']['closepopup'] = true;
		}

		if ( isset( $action_settings['close_delay'] ) ) {

			$data['actions']['closedelay'] = intval( $action_settings['close_delay'] );

			if ( strlen( $data['actions']['closedelay'] ) >= 3 ) {
				$data['actions']['closedelay'] = $data['actions']['closedelay'] / 1000;
			}

			$data['actions']['closepopup'] = $data['actions']['closedelay'];
		}

		return $data;
	}
}
