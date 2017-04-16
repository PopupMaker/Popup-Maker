<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Action_SuccessMessage
 */
final class NF_PUM_Actions_OpenPopup extends NF_Abstracts_Action
{
	/**
	 * @var string
	 */
	protected $_name  = 'openpopup';

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
	public function __construct()
	{
		parent::__construct();

		$this->_nicename = __( 'Open Popup', 'popup-maker' );

		$settings = array(
			'popup' => array(
				'name' => 'popup',
				'type' => 'select',
				'group' => 'primary',
				'label' => __( 'Popup ID', 'popup-maker' ),
				'placeholder' => '',
				'width' => 'full',
				'options' => $this->get_popup_list(),
			),
		);

		$this->_settings = array_merge( $this->_settings, $settings );
	}

	/*
	* PUBLIC METHODS
	*/

	public function save( $action_settings )
	{

	}

	public function process( $action_settings, $form_id, $data )
	{
		if ( ! isset( $data['actions'] ) || ! isset( $data['actions']['openpopup'] ) ) {
			$data['actions']['openpopup'] = false;
		}

		if ( isset( $action_settings['popup'] ) ) {
			$data['actions']['openpopup'] = intval( $action_settings['popup'] );
		}

		return $data;
	}

	public function get_popup_list() {
		$popup_list = array(
			array(
				'value' => '',
				'label' => __( 'Select a popup', 'popup-maker' )
			)
		);

		$popups = get_posts( array(
			'post_type'      => 'popup',
			'post_status'    => array( 'publish' ),
			'posts_per_page' => - 1,
		) );

		foreach ( $popups as $popup ) {
			$popup_list[] = array(
				'value' => $popup->ID,
				'label' => $popup->post_title
			);

		}

		return $popup_list;
	}

}
