<?php if ( ! defined( 'ABSPATH' ) ) {
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
	protected $_tags = array();

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

		$settings = array(
			'close_delay' => array(
				'name'        => 'close_delay',
				'type'        => 'number',
				'group'       => 'primary',
				'label'       => __( 'Delay', 'popup-maker' ),
				'placeholder' => '',
				'width'       => 'full',
				'value'       => __( '0', 'popup-maker' ),
			),
		);

		$this->_settings = array_merge( $this->_settings, $settings );
	}

	/*
	* PUBLIC METHODS
	*/

	public function save( $action_settings ) {

	}

	public function process( $action_settings, $form_id, $data ) {

		if ( ! isset( $data['actions'] ) || ! isset( $data['actions']['closepopup'] ) ) {
			$data['actions']['closepopup'] = 0;
		}

		if ( isset( $action_settings['close_delay'] ) ) {
			$data['actions']['closepopup'] = intval( $action_settings['close_delay'] );
		}

		return $data;
	}
}
