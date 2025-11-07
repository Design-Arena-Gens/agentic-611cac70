<?php
/**
 * Shortcode task board template.
 *
 * @package AgenticEditorialPlanner
 */

$status_args = array(
	'taxonomy'   => $this->status_taxonomy,
	'hide_empty' => false,
	'orderby'    => 'term_order',
	'order'      => 'ASC',
);

if ( ! empty( $status_slugs ) ) {
	$status_args['slug'] = $status_slugs;
}

$statuses = get_terms( $status_args );

if ( is_wp_error( $statuses ) || empty( $statuses ) ) :
	?>
	<div class="aep-board-empty">
		<?php esc_html_e( 'No statuses available. Please create at least one status.', 'agentic-editorial-planner' ); ?>
	</div>
	<?php
	return;
endif;

$tasks_by_status = array_fill_keys(
	wp_list_pluck( $statuses, 'term_id' ),
	array()
);

if ( $query->have_posts() ) :
	while ( $query->have_posts() ) :
		$query->the_post();
		$post_id      = get_the_ID();
		$status_terms = wp_get_post_terms( $post_id, $this->status_taxonomy, array( 'fields' => 'ids' ) );
		$status_id    = ! empty( $status_terms ) ? $status_terms[0] : null;

		if ( $status_id && isset( $tasks_by_status[ $status_id ] ) ) {
			$priority_terms = wp_get_post_terms( $post_id, $this->priority_taxonomy );
			$priority_term  = $priority_terms && ! is_wp_error( $priority_terms ) && isset( $priority_terms[0] ) ? $priority_terms[0] : null;

			$tasks_by_status[ $status_id ][] = array(
				'id'            => $post_id,
				'title'         => get_the_title(),
				'summary'       => get_the_excerpt(),
				'permalink'     => get_permalink(),
				'due_date'      => get_post_meta( $post_id, 'aep_due_date', true ),
				'owner'         => get_user_by( 'id', (int) get_post_meta( $post_id, 'aep_owner', true ) ),
				'priority'      => $priority_term ? $priority_term->slug : null,
				'priority_name' => $priority_term ? $priority_term->name : null,
			);
		}
	endwhile;
	wp_reset_postdata();
endif;

?>

<div class="aep-frontend-board" role="list">
	<?php foreach ( $statuses as $status ) : ?>
		<section class="aep-frontend-column" role="listitem">
			<header class="aep-frontend-column__header">
				<span><?php echo esc_html( $status->name ); ?></span>
				<span><?php echo esc_html( count( $tasks_by_status[ $status->term_id ] ) ); ?></span>
			</header>
			<div class="aep-frontend-column__body">
				<?php if ( empty( $tasks_by_status[ $status->term_id ] ) ) : ?>
					<p class="aep-board-empty"><?php esc_html_e( 'No tasks at the moment.', 'agentic-editorial-planner' ); ?></p>
				<?php else : ?>
					<?php foreach ( $tasks_by_status[ $status->term_id ] as $task ) : ?>
						<article class="aep-task-card">
							<h3 class="aep-task-card__title">
								<a class="aep-task-card__link" href="<?php echo esc_url( $task['permalink'] ); ?>">
									<?php echo esc_html( $task['title'] ); ?>
								</a>
							</h3>
							<?php if ( $task['summary'] ) : ?>
								<div class="aep-task-card__content">
									<?php echo wp_kses_post( wpautop( $task['summary'] ) ); ?>
								</div>
							<?php endif; ?>
							<div class="aep-task-card__meta">
								<?php if ( $task['due_date'] ) : ?>
									<span><?php echo esc_html( gmdate( 'M j, Y', strtotime( $task['due_date'] ) ) ); ?></span>
								<?php endif; ?>
								<?php if ( $task['owner'] ) : ?>
									<span><?php echo esc_html( $task['owner']->display_name ); ?></span>
								<?php endif; ?>
								<?php if ( ! empty( $task['priority_name'] ) ) : ?>
									<span><?php echo esc_html( $task['priority_name'] ); ?></span>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</section>
	<?php endforeach; ?>
</div>
