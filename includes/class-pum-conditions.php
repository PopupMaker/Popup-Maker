<?php
/**
 * Conditions
 *
 * @package     PUM
 * @subpackage  Classes/PUM_Conditions
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PUM_Conditions {

    public static $instance;

    public $conditions = array();

    public $group_labels = null;

    public $condition_sort_order = array();

    public static function instance() {
        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof PUM_Conditions ) ) {
            self::$instance = new PUM_Conditions;
        }

        return self::$instance;
    }

    public function add_conditions( $conditions = array() ) {
        foreach ( $conditions as $key => $condition ) {

            if ( ! $condition instanceof PUM_Condition && is_array( $condition ) ) {
                if ( empty( $condition['id'] ) && ! is_numeric( $key ) ) {
                    $condition['id'] = $key;
                }

                $condition = new PUM_Condition( $condition );
            }

            $this->add_condition( $condition );

        }
    }

    public function add_condition( $condition = null ) {
        if ( ! $condition instanceof PUM_Condition ) {
            return;
        }

        if ( ! isset ( $this->conditions[ $condition->id ] ) ) {
            $this->conditions[ $condition->id ] = $condition;
        }

        return;
    }

    public function get_conditions() {
        return $this->conditions;
    }

    public function condition_sort_order() {
        if ( ! $this->condition_sort_order ) {

            $order = array(
                    __( 'General', 'popup-maker' )    => 1,
                    __( 'Pages', 'popup-maker' )      => 5,
                    __( 'Posts', 'popup-maker' )      => 5,
                    __( 'Categories', 'popup-maker' ) => 14,
                    __( 'Tags', 'popup-maker' )       => 14,
                    __( 'Format', 'popup-maker' )     => 16,
            );

            $post_types = get_post_types( array( 'public' => true, '_builtin' => false ), 'objects' );
            foreach ( $post_types as $name => $post_type ) {
                $order[ $post_type->labels->name ] = 10;
            }

            $taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => false ), 'objects' );
            foreach ( $taxonomies as $tax_name => $taxonomy ) {
                $order[ $taxonomy->labels->name ] = 15;
            }

            $this->condition_sort_order = apply_filters( 'pum_condition_sort_order', $order );

        }

        return $this->condition_sort_order;
    }

    public function sort_condition_groups( $a, $b ) {

        $order = $this->condition_sort_order();

        $ai = isset( $order[ $a ] ) ? intval( $order[ $a ] ) : 10;
        $bi = isset( $order[ $b ] ) ? intval( $order[ $b ] ) : 10;

        if ( $ai == $bi ) {
            return 0;
        }

        // Compare their positions in line.
        return $ai > $bi ? 1 : -1;
    }

    public function get_conditions_by_group() {
        $groups = array();

        foreach ( $this->get_conditions() as $condition ) {
            $groups[ $condition->group ][ $condition->get_id() ] = $condition;
        }

        uksort( $groups, array( $this, 'sort_condition_groups' ) );

        return $groups;
    }

    public function conditions_selectbox( $args = array() ) {
        $args = wp_parse_args( $args, array(
                'id'      => '',
                'name'    => '',
                'current' => '',
        ) );

        // TODO: Generate this using PUM_Fields. Use a switch to generate a templ version when needed. ?>
        <select class="target facet-select" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>">
            <option value=""><?php _e( 'Select a condition', 'popup-maker' ); ?></option>
            <?php foreach ( $this->get_conditions_by_group() as $group => $conditions ) : ?>
                <optgroup label="<?php echo esc_attr_e( $group ); ?>">
                    <?php foreach ( $conditions as $id => $condition ) : ?>
                        <option value="<?php echo $id; ?>" <?php selected( $args['current'], $id ); ?>>
                            <?php echo $condition->get_label( 'name' ); ?>
                        </option>
                    <?php endforeach ?>
                </optgroup>
            <?php endforeach ?>
        </select><?php
    }

    public function get_condition( $condition = null ) {
        return isset( $this->conditions[ $condition ] ) ? $this->conditions[ $condition ] : null;
    }

    public function validate_condition( $condition = array() ) {
        if ( empty( $condition ) || empty( $condition['target'] ) ) {
            return new WP_Error( 'empty_condition', __( "Invalid condition[target].", "popup-maker" ) );
        }

        return $this->get_condition( $condition['target'] )->sanitize_fields( $condition );;
    }

}
