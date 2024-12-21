<?php
/**
 * CTA registry service.
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Services;

use PopupMaker\Base\Service;
use PopupMaker\Models\CallToAction;

defined( 'ABSPATH' ) || exit;

/**
 * Call To Action service.
 *
 * @since X.X.X
 */
class CallToActions extends Service {
	/**
	 * Array of all call to actions.
	 *
	 * @var array<int,CallToAction>|null
	 */

	/**
	 * Array of all call to actions sorted by priority.
	 *
	 * @var CallToAction[]|null
	 */
	public $ctas;

	/**
	 * Array of all call to actions by ID.
	 *
	 * @var array<int,CallToAction>|null
	 */
	public $ctas_by_id;

	/**
	 * Array of all call to actions by UUID.
	 *
	 * @var array<string,CallToAction>|null
	 */
	public $ctas_by_uuid;

	/**
	 * Initialize the service.
	 */
	public function __construct() {
		// Fire action to dependent services to initialize.
		do_action( 'popup_maker/services/call_to_actions/init', $this );
	}

	/**
	 * Get a list of all call to actions.
	 *
	 * @return array<int,CallToAction>
	 */
	public function get_call_to_actions( $args = [] ) {
		$post_type = $this->container->get( 'PostType' )->get_type_key( 'pum_cta' );

		$query_args = wp_parse_args( $args, [
			'post_type'      => $post_type,
			'posts_per_page' => - 1,
		] );

		$query_results = new \WP_Query( $query_args );

		$ctas = [];

		foreach ( $query_results->posts as $post ) {
			$cta                              = new CallToAction( $post );
			$ctas[ $cta->id ]                 = $cta;
			$this->ctas_by_id[ $cta->id ]     = $cta;
			$this->ctas_by_uuid[ $cta->uuid ] = $cta;
		}

		return array_values( $ctas );
	}

	/**
	 * Get call to action by ID.
	 *
	 * @param int $cta_id Call to action ID.
	 *
	 * @return CallToAction|null
	 */
	public function get_by_id( $cta_id = 0 ) {
		// If call to action is an ID, get the object.
		if ( is_numeric( $cta_id ) && isset( $this->ctas_by_id[ $cta_id ] ) ) {
			return $this->ctas_by_id[ $cta_id ];
		}

		$cta_post_type = $this->container->get( 'PostType' )->get_type_key( 'pum_cta' );

		// Query for a call to action by post ID.
		if ( is_numeric( $cta_id ) ) {
			$post = get_post( $cta_id );

			if ( $post && $post->post_type === $cta_post_type ) {
				$cta                              = new CallToAction( $post );
				$this->ctas_by_id[ $cta->id ]     = $cta;
				$this->ctas_by_uuid[ $cta->uuid ] = $cta;

				return $cta;
			}
		}

		return null;
	}

	/**
	 * Get call to action, by UUID.
	 *
	 * @param string $uuid Call to action UUID.
	 *
	 * @return CallToAction|null
	 */
	public function get_by_uuid( $uuid = '' ) {
		if ( isset( $this->ctas_by_uuid[ $uuid ] ) ) {
			return $this->ctas_by_uuid[ $uuid ];
		}

		$cta_post_type = $this->container->get( 'PostType' )->get_type_key( 'pum_cta' );

		$post = query_posts( [
			'post_type'      => $cta_post_type,
			'meta_key'       => 'uuid', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $uuid, // phpcs:ignore WordPress.DB.SlowDBQuery
			'posts_per_page' => 1,
		] );

		if ( $post ) {
			$cta                              = new CallToAction( $post[0] );
			$this->ctas_by_id[ $cta->id ]     = $cta;
			$this->ctas_by_uuid[ $cta->uuid ] = $cta;

			return $cta;
		}

		return null;
	}
}
