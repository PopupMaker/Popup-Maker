<?php
/**
 * PHPStan stub for the Query Monitor plugin integration in ContentControl.
 *
 * @package ContentControl
 * @license MIT
 */

 // phpcs:disable Generic.Files.OneObjectStructurePerFile, Squiz.Commenting.ClassComment, Generic.CodeAnalysis.UnusedFunctionParameter


class QM_Data {}

/**
 * Represents a data collector in the Query Monitor plugin.
 */
abstract class QM_Collector {

	/**
	 * The ID of the collector.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * The data.
	 *
	 * @var QM_Data
	 */
	protected $data;

	/**
	 * Sets up the collector.
	 *
	 * @return void
	 */
	public function set_up() {}

	/**
	 * Tears down the collector.
	 *
	 * @return void
	 */
	public function tear_down() {}

	/**
	 * Retrieves the name of the collector.
	 *
	 * @return QM_Data The name of the collector.
	 */
	public function get_data() {
		return $this->data;
	}
}

/**
 * Represents a data collector for the Query Monitor plugin.
 */
class QM_DataCollector extends QM_Collector {

}

/**
 * A collection of data collectors for the Query Monitor plugin.
 */
class QM_Collectors {

	/**
	 * Adds a collector to the collection.
	 *
	 * @param QM_Collector|QM_DataCollector $collector The collector to add.
	 * @return void
	 */
	public static function add( $collector ) {}

	/**
	 * Retrieves a collector by its ID.
	 *
	 * @param string $id The ID of the collector to retrieve.
	 * @return QM_Collector|QM_DataCollector|null The collector if found, or null otherwise.
	 */
	public static function get( $id ) {
		return null;
	}
}

	/**
	 * Base class for generating HTML-based output for the Query Monitor plugin.
	 */
class QM_Output_Html {

	/**
	 * The collector associated with this output.
	 *
	 *  @var QM_Collector
	 * */
	protected $collector;

	/**
	 * Constructor.
	 *
	 * @param QM_Collector $collector The collector to associate with this output.
	 */
	public function __construct( $collector ) {}

	/**
	 * Fix menu defaults.
	 *
	 * @param array<string,string> $menu Menu items.
	 *
	 * @return array<string,string> Menu items.
	 */
	public function menu( $menu ) {
		return $menu;
	}

	/**
	 * Output before the non-tabular output.
	 *
	 * @return void
	 */
	public function before_non_tabular_output() {}

	/**
	 * Output after the non-tabular output.
	 *
	 * @return void
	 */
	public function after_non_tabular_output() {}

	/**
	 * Collector.
	 *
	 * @return QM_Collector
	 */
	public function get_collector() {
		return $this->collector;
	}
}
