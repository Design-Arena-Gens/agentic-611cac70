<?php
/**
 * Shortcodes for the editorial planner.
 *
 * @package AgenticEditorialPlanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers shortcodes and frontend assets.
 */
class Agentic_Editorial_Planner_Shortcodes {
	/**
	 * Singleton instance.
	 *
	 * @var Agentic_Editorial_Planner_Shortcodes|null
	 */
	private static $instance = null;

	/**
	 * Task post type slug.
	 *
	 * @var string
	 */
	private $post_type;

	/**
	 * Status taxonomy slug.
	 *
	 * @var string
	 */
	private $status_taxonomy;

	/**
	 * Priority taxonomy slug.
	 *
	 * @var string
	 */
	private $priority_taxonomy;

	/**
	 * Returns an instance.
	 *
	 * @param string $post_type Post type slug.
	 * @param string $status_taxonomy Status taxonomy slug.
	 * @param string $priority_taxonomy Priority taxonomy slug.
	 *
	 * @return Agentic_Editorial_Planner_Shortcodes
	 */
	public static function instance( $post_type, $status_taxonomy, $priority_taxonomy ) {
		if ( null === self::$instance ) {
			self::$instance = new self( $post_type, $status_taxonomy, $priority_taxonomy );
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @param string $post_type Post type slug.
	 * @param string $status_taxonomy Status taxonomy slug.
	 * @param string $priority_taxonomy Priority taxonomy slug.
	 */
	private function __construct( $post_type, $status_taxonomy, $priority_taxonomy ) {
		$this->post_type        = $post_type;
		$this->status_taxonomy  = $status_taxonomy;
		$this->priority_taxonomy = $priority_taxonomy;

		add_shortcode( 'aep_task_board', array( $this, 'render_task_board_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Registers frontend assets.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style(
			'agentic-editorial-planner-frontend',
			AEP_PLUGIN_URL . 'assets/frontend.css',
			array(),
			'1.0.0'
		);

		wp_register_script(
			'agentic-editorial-planner-frontend',
			AEP_PLUGIN_URL . 'assets/frontend-board.js',
			array(),
			'1.0.0',
			true
		);
	}

	/**
	 * Renders the task board shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public function render_task_board_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'statuses' => '',
				'limit'    => 50,
				'title'    => __( 'Editorial Task Board', 'agentic-editorial-planner' ),
			),
			$atts,
			'aep_task_board'
		);

		wp_enqueue_style( 'agentic-editorial-planner-frontend' );
		wp_enqueue_script( 'agentic-editorial-planner-frontend' );

		$status_slugs = array_filter( array_map( 'trim', explode( ',', $atts['statuses'] ) ) );

		$query_args = array(
			'post_type'      => $this->post_type,
			'post_status'    => 'publish',
			'posts_per_page' => (int) $atts['limit'],
			'orderby'        => 'meta_value',
			'meta_key'       => 'aep_due_date',
			'order'          => 'ASC',
		);

		if ( $status_slugs ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => $this->status_taxonomy,
					'field'    => 'slug',
					'terms'    => $status_slugs,
				),
			);
		}

		$query = new WP_Query( $query_args );

		ob_start();
		include AEP_PLUGIN_PATH . 'views/shortcode-board.php';
		return ob_get_clean();
	}
}

