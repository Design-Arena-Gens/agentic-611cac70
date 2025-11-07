<?php
/**
 * Core plugin bootstrap.
 *
 * @package AgenticEditorialPlanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once AEP_PLUGIN_PATH . 'includes/class-aep-rest-controller.php';
require_once AEP_PLUGIN_PATH . 'includes/class-aep-shortcodes.php';

/**
 * Main plugin class.
 */
class Agentic_Editorial_Planner {
	/**
	 * Singleton instance.
	 *
	 * @var Agentic_Editorial_Planner|null
	 */
	private static $instance = null;

	/**
	 * CPT slug.
	 *
	 * @var string
	 */
	private $post_type = 'aep_task';

	/**
	 * Status taxonomy slug.
	 *
	 * @var string
	 */
	private $status_taxonomy = 'aep_status';

	/**
	 * Priority taxonomy slug.
	 *
	 * @var string
	 */
	private $priority_taxonomy = 'aep_priority';

	/**
	 * Admin page slug.
	 *
	 * @var string
	 */
	private $menu_slug = 'aep-task-board';

	/**
	 * Returns the plugin instance.
	 *
	 * @return Agentic_Editorial_Planner
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'init', array( $this, 'register_meta_fields' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post_' . $this->post_type, array( $this, 'save_meta_box_data' ) );
		add_filter( 'manage_edit-' . $this->post_type . '_columns', array( $this, 'register_columns' ) );
		add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'render_columns' ), 10, 2 );
		add_action( $this->status_taxonomy . '_add_form_fields', array( $this, 'render_status_meta_add_form' ) );
		add_action( $this->status_taxonomy . '_edit_form_fields', array( $this, 'render_status_meta_edit_form' ), 10, 1 );
		add_action( 'created_' . $this->status_taxonomy, array( $this, 'save_status_meta' ), 10, 2 );
		add_action( 'edited_' . $this->status_taxonomy, array( $this, 'save_status_meta' ), 10, 2 );

		Agentic_Editorial_Planner_REST_Controller::instance( $this->post_type, $this->status_taxonomy, $this->priority_taxonomy );
		Agentic_Editorial_Planner_Shortcodes::instance( $this->post_type, $this->status_taxonomy, $this->priority_taxonomy );
	}

	/**
	 * Renders color picker for status add form.
	 *
	 * @return void
	 */
	public function render_status_meta_add_form() {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script( 'jquery' );
		?>
		<div class="form-field term-color-wrap">
			<label for="aep_status_color"><?php esc_html_e( 'Column Color', 'agentic-editorial-planner' ); ?></label>
			<input type="text" name="aep_status_color" id="aep_status_color" value="" class="wp-color-picker-field" data-default-color="#3b82f6" />
			<p class="description"><?php esc_html_e( 'Select a color for this status column.', 'agentic-editorial-planner' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Renders color picker for status edit form.
	 *
	 * @param WP_Term $term Current term.
	 *
	 * @return void
	 */
	public function render_status_meta_edit_form( $term ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script( 'jquery' );
		$value = get_term_meta( $term->term_id, 'aep_color', true );
		?>
		<tr class="form-field term-color-wrap">
			<th scope="row">
				<label for="aep_status_color"><?php esc_html_e( 'Column Color', 'agentic-editorial-planner' ); ?></label>
			</th>
			<td>
				<input type="text" name="aep_status_color" id="aep_status_color" value="<?php echo esc_attr( $value ? $value : '#3b82f6' ); ?>" class="wp-color-picker-field" data-default-color="#3b82f6" />
				<p class="description"><?php esc_html_e( 'Select a color for this status column.', 'agentic-editorial-planner' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Saves status term meta.
	 *
	 * @param int $term_id Term ID.
	 * @param int $tt_id Not used.
	 *
	 * @return void
	 */
	public function save_status_meta( $term_id, $tt_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( isset( $_POST['aep_status_color'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$color = sanitize_hex_color( wp_unslash( $_POST['aep_status_color'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( $color ) {
				update_term_meta( $term_id, 'aep_color', $color );
			}
		}
	}

	/**
	 * Registers the task post type.
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Editorial Tasks', 'agentic-editorial-planner' ),
			'singular_name'      => __( 'Editorial Task', 'agentic-editorial-planner' ),
			'add_new'            => __( 'Add Task', 'agentic-editorial-planner' ),
			'add_new_item'       => __( 'Add New Task', 'agentic-editorial-planner' ),
			'edit_item'          => __( 'Edit Task', 'agentic-editorial-planner' ),
			'new_item'           => __( 'New Task', 'agentic-editorial-planner' ),
			'view_item'          => __( 'View Task', 'agentic-editorial-planner' ),
			'search_items'       => __( 'Search Tasks', 'agentic-editorial-planner' ),
			'not_found'          => __( 'No tasks found', 'agentic-editorial-planner' ),
			'not_found_in_trash' => __( 'No tasks found in trash', 'agentic-editorial-planner' ),
			'menu_name'          => __( 'Editorial Planner', 'agentic-editorial-planner' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => array( 'title', 'editor', 'excerpt' ),
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-schedule',
		);

		register_post_type( $this->post_type, $args );
	}

	/**
	 * Registers status and priority taxonomies.
	 *
	 * @return void
	 */
	public function register_taxonomies() {
		$status_labels = array(
			'name'              => __( 'Statuses', 'agentic-editorial-planner' ),
			'singular_name'     => __( 'Status', 'agentic-editorial-planner' ),
			'search_items'      => __( 'Search Statuses', 'agentic-editorial-planner' ),
			'all_items'         => __( 'All Statuses', 'agentic-editorial-planner' ),
			'edit_item'         => __( 'Edit Status', 'agentic-editorial-planner' ),
			'update_item'       => __( 'Update Status', 'agentic-editorial-planner' ),
			'add_new_item'      => __( 'Add New Status', 'agentic-editorial-planner' ),
			'new_item_name'     => __( 'New Status Name', 'agentic-editorial-planner' ),
			'menu_name'         => __( 'Statuses', 'agentic-editorial-planner' ),
		);

		$status_args = array(
			'hierarchical'      => true,
			'labels'            => $status_labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => false,
			'show_in_rest'      => true,
		);

		register_taxonomy( $this->status_taxonomy, $this->post_type, $status_args );

		$priority_labels = array(
			'name'              => __( 'Priorities', 'agentic-editorial-planner' ),
			'singular_name'     => __( 'Priority', 'agentic-editorial-planner' ),
			'search_items'      => __( 'Search Priorities', 'agentic-editorial-planner' ),
			'all_items'         => __( 'All Priorities', 'agentic-editorial-planner' ),
			'edit_item'         => __( 'Edit Priority', 'agentic-editorial-planner' ),
			'update_item'       => __( 'Update Priority', 'agentic-editorial-planner' ),
			'add_new_item'      => __( 'Add New Priority', 'agentic-editorial-planner' ),
			'new_item_name'     => __( 'New Priority Name', 'agentic-editorial-planner' ),
			'menu_name'         => __( 'Priorities', 'agentic-editorial-planner' ),
		);

		$priority_args = array(
			'hierarchical'      => false,
			'labels'            => $priority_labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => false,
			'show_in_rest'      => true,
		);

		register_taxonomy( $this->priority_taxonomy, $this->post_type, $priority_args );
	}

	/**
	 * Registers meta fields exposed via REST API.
	 *
	 * @return void
	 */
	public function register_meta_fields() {
		$fields = array(
			'aep_due_date'   => array(
				'type'         => 'string',
				'single'       => true,
				'show_in_rest' => true,
				'description'  => __( 'Due date in ISO 8601 format.', 'agentic-editorial-planner' ),
			),
			'aep_owner'      => array(
				'type'         => 'integer',
				'single'       => true,
				'show_in_rest' => true,
				'description'  => __( 'Assigned author ID.', 'agentic-editorial-planner' ),
			),
			'aep_brief_link' => array(
				'type'         => 'string',
				'single'       => true,
				'show_in_rest' => true,
				'description'  => __( 'External brief URL.', 'agentic-editorial-planner' ),
			),
		);

		foreach ( $fields as $key => $args ) {
			register_post_meta(
				$this->post_type,
				$key,
				wp_parse_args(
					$args,
					array(
						'auth_callback' => function() {
							return current_user_can( 'edit_posts' );
						},
					)
				)
			);
		}
	}

	/**
	 * Registers editor side meta boxes.
	 *
	 * @return void
	 */
	public function register_meta_boxes() {
		add_meta_box(
			'aep-task-details',
			__( 'Task Details', 'agentic-editorial-planner' ),
			array( $this, 'render_meta_box' ),
			$this->post_type,
			'side',
			'high'
		);
	}

	/**
	 * Renders the task meta box.
	 *
	 * @param WP_Post $post Current post object.
	 *
	 * @return void
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'aep_save_task_details', 'aep_task_nonce' );

		$due_date   = get_post_meta( $post->ID, 'aep_due_date', true );
		$owner_id   = get_post_meta( $post->ID, 'aep_owner', true );
		$brief_link = get_post_meta( $post->ID, 'aep_brief_link', true );

		$users = get_users(
			array(
				'role__in' => array( 'administrator', 'editor', 'author' ),
				'orderby'  => 'display_name',
				'order'    => 'ASC',
				'fields'   => array( 'ID', 'display_name' ),
			)
		);

		include AEP_PLUGIN_PATH . 'views/meta-box-task.php';
	}

	/**
	 * Saves meta box fields.
	 *
	 * @param int $post_id Post identifier.
	 *
	 * @return void
	 */
	public function save_meta_box_data( $post_id ) {
		if ( ! isset( $_POST['aep_task_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aep_task_nonce'] ) ), 'aep_save_task_details' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$due_date = isset( $_POST['aep_due_date'] ) ? sanitize_text_field( wp_unslash( $_POST['aep_due_date'] ) ) : '';
		if ( $due_date ) {
			$timestamp = strtotime( $due_date );
			$due_date  = $timestamp ? gmdate( 'Y-m-d', $timestamp ) : '';
		}

		update_post_meta( $post_id, 'aep_due_date', $due_date );

		$owner = isset( $_POST['aep_owner'] ) ? absint( $_POST['aep_owner'] ) : 0;
		update_post_meta( $post_id, 'aep_owner', $owner );

		$brief_link = isset( $_POST['aep_brief_link'] ) ? esc_url_raw( wp_unslash( $_POST['aep_brief_link'] ) ) : '';
		update_post_meta( $post_id, 'aep_brief_link', $brief_link );
	}

	/**
	 * Adds custom columns to the list table.
	 *
	 * @param array $columns Table columns.
	 *
	 * @return array
	 */
	public function register_columns( $columns ) {
		$offset = array_slice( $columns, 0, 2, true );
		$tail   = array_slice( $columns, 2, null, true );

		$extra = array(
			'aep_due_date' => __( 'Due Date', 'agentic-editorial-planner' ),
			'aep_owner'    => __( 'Owner', 'agentic-editorial-planner' ),
		);

		return array_merge( $offset, $extra, $tail );
	}

	/**
	 * Renders custom column content.
	 *
	 * @param string $column Column key.
	 * @param int    $post_id Post identifier.
	 *
	 * @return void
	 */
	public function render_columns( $column, $post_id ) {
		if ( 'aep_due_date' === $column ) {
			$due = get_post_meta( $post_id, 'aep_due_date', true );
			echo esc_html( $due ? gmdate( 'M j, Y', strtotime( $due ) ) : '—' );
			return;
		}

		if ( 'aep_owner' === $column ) {
			$owner = get_post_meta( $post_id, 'aep_owner', true );
			$user  = $owner ? get_user_by( 'id', (int) $owner ) : null;
			echo esc_html( $user ? $user->display_name : '—' );
			return;
		}
	}

	/**
	 * Registers a custom admin page.
	 *
	 * @return void
	 */
	public function register_admin_page() {
		add_submenu_page(
			'edit.php?post_type=' . $this->post_type,
			__( 'Task Board', 'agentic-editorial-planner' ),
			__( 'Task Board', 'agentic-editorial-planner' ),
			'edit_posts',
			$this->menu_slug,
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Renders the Task Board admin page.
	 *
	 * @return void
	 */
	public function render_admin_page() {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Editorial Task Board', 'agentic-editorial-planner' ) . '</h1>';
		echo '<div id="aep-task-board-root" data-rest="' . esc_url( rest_url( 'agentic/v1/tasks' ) ) . '"></div>';
		echo '</div>';
	}

	/**
	 * Loads admin scripts.
	 *
	 * @param string $hook Current admin hook.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		$is_board = false !== strpos( $hook, $this->menu_slug );
		$is_cpt   = 'edit.php' === $hook && isset( $_GET['post_type'] ) && $this->post_type === $_GET['post_type']; // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! $is_board && ! $is_cpt ) {
			return;
		}

		wp_enqueue_style(
			'agentic-editorial-planner-admin',
			AEP_PLUGIN_URL . 'assets/admin.css',
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'agentic-editorial-planner-board',
			AEP_PLUGIN_URL . 'assets/admin-board.js',
			array( 'wp-api-fetch', 'wp-dom-ready' ),
			'1.0.0',
			true
		);

		wp_localize_script(
			'agentic-editorial-planner-board',
			'aepPlanner',
			array(
				'endpoint' => rest_url( 'agentic/v1/tasks' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
			)
		);
	}
}
		wp_print_inline_script_tag( 'jQuery( function( $ ) { $( "#aep_status_color" ).wpColorPicker(); } );' );
		wp_print_inline_script_tag( 'jQuery( function( $ ) { $( "#aep_status_color" ).wpColorPicker(); } );' );
