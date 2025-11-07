<?php
/**
 * REST controller for tasks.
 *
 * @package AgenticEditorialPlanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST controller handling editorial task board interactions.
 */
class Agentic_Editorial_Planner_REST_Controller extends WP_REST_Controller {
	/**
	 * Singleton instance.
	 *
	 * @var Agentic_Editorial_Planner_REST_Controller|null
	 */
	private static $instance = null;

	/**
	 * Task post type.
	 *
	 * @var string
	 */
	private $post_type;

	/**
	 * Status taxonomy.
	 *
	 * @var string
	 */
	private $status_taxonomy;

	/**
	 * Priority taxonomy.
	 *
	 * @var string
	 */
	private $priority_taxonomy;

	/**
	 * Returns the controller instance.
	 *
	 * @param string $post_type Post type slug.
	 * @param string $status_taxonomy Status taxonomy slug.
	 * @param string $priority_taxonomy Priority taxonomy slug.
	 *
	 * @return Agentic_Editorial_Planner_REST_Controller
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
		$this->namespace        = 'agentic/v1';
		$this->rest_base        = 'tasks';

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
			)
		);
	}

	/**
	 * Checks permissions.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return bool|WP_Error
	 */
	public function permissions_check( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'You cannot access editorial tasks.', 'agentic-editorial-planner' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Retrieves tasks grouped by status.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		$terms = get_terms(
			array(
				'taxonomy'   => $this->status_taxonomy,
				'hide_empty' => false,
				'orderby'    => 'term_order',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) ) {
			return rest_ensure_response(
				array(
					'columns' => array(),
					'tasks'   => array(),
				)
			);
		}

		$columns = array();
		foreach ( $terms as $term ) {
			$columns[] = array(
				'id'    => $term->term_id,
				'slug'  => $term->slug,
				'name'  => $term->name,
				'color' => get_term_meta( $term->term_id, 'aep_color', true ),
			);
		}

		$query = new WP_Query(
			array(
				'post_type'      => $this->post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'meta_value',
				'meta_key'       => 'aep_due_date',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);

		$tasks = array();
		foreach ( $query->posts as $post_id ) {
			$status_terms = wp_get_post_terms( $post_id, $this->status_taxonomy, array( 'fields' => 'ids' ) );
			$priority     = wp_get_post_terms( $post_id, $this->priority_taxonomy );
			$priority_term = $priority && ! is_wp_error( $priority ) && isset( $priority[0] ) ? $priority[0] : null;

			$tasks[] = array(
				'id'           => $post_id,
				'title'        => get_the_title( $post_id ),
				'excerpt'      => get_the_excerpt( $post_id ),
				'status'       => $status_terms ? $status_terms[0] : null,
				'priority'     => $priority_term ? $priority_term->slug : null,
				'priorityName' => $priority_term ? $priority_term->name : null,
				'dueDate'      => get_post_meta( $post_id, 'aep_due_date', true ),
				'owner'        => (int) get_post_meta( $post_id, 'aep_owner', true ),
				'briefLink'    => get_post_meta( $post_id, 'aep_brief_link', true ),
				'permalink'    => get_edit_post_link( $post_id, '' ),
				'authorName'   => get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) ),
				'ownerName'    => $this->resolve_user_name( (int) get_post_meta( $post_id, 'aep_owner', true ) ),
			);
		}

		$priorities = get_terms(
			array(
				'taxonomy'   => $this->priority_taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		$priority_options = array();
		if ( ! is_wp_error( $priorities ) ) {
			foreach ( $priorities as $priority ) {
				$priority_options[] = array(
					'id'   => $priority->term_id,
					'slug' => $priority->slug,
					'name' => $priority->name,
				);
			}
		}

		$users = get_users(
			array(
				'role__in' => array( 'administrator', 'editor', 'author' ),
				'orderby'  => 'display_name',
				'order'    => 'ASC',
				'fields'   => array( 'ID', 'display_name' ),
			)
		);

		$owner_options = array_map(
			function( $user ) {
				return array(
					'id'   => $user->ID,
					'name' => $user->display_name,
				);
			},
			$users
		);

		return rest_ensure_response(
			array(
				'columns'    => $columns,
				'tasks'      => $tasks,
				'priorities' => $priority_options,
				'owners'     => $owner_options,
			)
		);
	}

	/**
	 * Updates a task.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$post_id = (int) $request->get_param( 'id' );

		if ( ! $post_id || $this->post_type !== get_post_type( $post_id ) ) {
			return new WP_Error(
				'invalid_task',
				__( 'Task not found.', 'agentic-editorial-planner' ),
				array( 'status' => WP_Http::NOT_FOUND )
			);
		}

		$fields = $request->get_json_params();

		if ( isset( $fields['title'] ) ) {
			$updated = wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => sanitize_text_field( $fields['title'] ),
				),
				true
			);

			if ( is_wp_error( $updated ) ) {
				return $updated;
			}
		}

		if ( isset( $fields['status'] ) && $fields['status'] ) {
			wp_set_object_terms( $post_id, (int) $fields['status'], $this->status_taxonomy );
		}

		if ( isset( $fields['priority'] ) ) {
			if ( $fields['priority'] ) {
				wp_set_object_terms( $post_id, $fields['priority'], $this->priority_taxonomy );
			} else {
				wp_set_object_terms( $post_id, array(), $this->priority_taxonomy );
			}
		}

		if ( isset( $fields['dueDate'] ) ) {
			$due = sanitize_text_field( $fields['dueDate'] );
			update_post_meta( $post_id, 'aep_due_date', $due );
		}

		if ( isset( $fields['owner'] ) ) {
			update_post_meta( $post_id, 'aep_owner', absint( $fields['owner'] ) );
		}

		if ( isset( $fields['briefLink'] ) ) {
			update_post_meta( $post_id, 'aep_brief_link', esc_url_raw( $fields['briefLink'] ) );
		}

		return $this->get_items( $request );
	}

	/**
	 * Resolves a user name from ID.
	 *
	 * @param int $user_id User identifier.
	 *
	 * @return string|null
	 */
	private function resolve_user_name( $user_id ) {
		if ( ! $user_id ) {
			return null;
		}

		$user = get_user_by( 'id', $user_id );
		return $user ? $user->display_name : null;
	}
}
