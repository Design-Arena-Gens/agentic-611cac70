<?php
/**
 * Meta box markup for task details.
 *
 * @package AgenticEditorialPlanner
 */

?>
<p>
	<label for="aep_due_date"><?php esc_html_e( 'Due Date', 'agentic-editorial-planner' ); ?></label>
	<input
		type="date"
		name="aep_due_date"
		id="aep_due_date"
		class="widefat"
		value="<?php echo esc_attr( $due_date ); ?>"
	/>
</p>

<p>
	<label for="aep_owner"><?php esc_html_e( 'Owner', 'agentic-editorial-planner' ); ?></label>
	<select name="aep_owner" id="aep_owner" class="widefat">
		<option value="0"><?php esc_html_e( 'Unassigned', 'agentic-editorial-planner' ); ?></option>
		<?php foreach ( $users as $user ) : ?>
			<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( (int) $owner_id, (int) $user->ID ); ?>>
				<?php echo esc_html( $user->display_name ); ?>
			</option>
		<?php endforeach; ?>
	</select>
</p>

<p>
	<label for="aep_brief_link"><?php esc_html_e( 'Brief URL', 'agentic-editorial-planner' ); ?></label>
	<input
		type="url"
		name="aep_brief_link"
		id="aep_brief_link"
		class="widefat"
		value="<?php echo esc_url( $brief_link ); ?>"
		placeholder="https://"
	/>
</p>

