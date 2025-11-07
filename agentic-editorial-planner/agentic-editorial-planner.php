<?php
/**
 * Plugin Name: Agentic Editorial Planner
 * Plugin URI:  https://example.com/agentic-editorial-planner
 * Description: Plan editorial content with custom tasks, statuses, and front-end boards.
 * Version:     1.0.0
 * Author:      Codex AI
 * Author URI:  https://example.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: agentic-editorial-planner
 *
 * @package AgenticEditorialPlanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AEP_PLUGIN_FILE', __FILE__ );
define( 'AEP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'AEP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once AEP_PLUGIN_PATH . 'includes/class-aep-plugin.php';

/**
 * Initializes the plugin.
 *
 * @return void
 */
function aep_run_plugin() {
	Agentic_Editorial_Planner::instance();
}

add_action( 'plugins_loaded', 'aep_run_plugin' );

/**
 * Handles plugin activation tasks.
 *
 * @return void
 */
function aep_activate_plugin() {
	$instance = Agentic_Editorial_Planner::instance();
	$instance->register_post_type();
	$instance->register_taxonomies();

	$defaults = array(
		'todo'        => array(
			'label' => __( 'To Do', 'agentic-editorial-planner' ),
			'color' => '#faf089',
		),
		'in-progress' => array(
			'label' => __( 'In Progress', 'agentic-editorial-planner' ),
			'color' => '#63b3ed',
		),
		'review'      => array(
			'label' => __( 'Review', 'agentic-editorial-planner' ),
			'color' => '#fbb6ce',
		),
		'complete'    => array(
			'label' => __( 'Complete', 'agentic-editorial-planner' ),
			'color' => '#68d391',
		),
	);

	foreach ( $defaults as $slug => $data ) {
		$term = get_term_by( 'slug', $slug, 'aep_status' );

		if ( ! $term ) {
			$result = wp_insert_term(
				$data['label'],
				'aep_status',
				array(
					'slug' => $slug,
				)
			);

			if ( ! is_wp_error( $result ) && isset( $result['term_id'] ) ) {
				update_term_meta( $result['term_id'], 'aep_color', $data['color'] );
			}
		} else {
			$color = get_term_meta( $term->term_id, 'aep_color', true );
			if ( ! $color ) {
				update_term_meta( $term->term_id, 'aep_color', $data['color'] );
			}
		}
	}
}

register_activation_hook( __FILE__, 'aep_activate_plugin' );
