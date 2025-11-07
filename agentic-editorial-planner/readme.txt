=== Agentic Editorial Planner ===
Contributors: codex-ai
Tags: editorial, tasks, kanban, planning, workflow
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plan, manage, and visualize editorial tasks inside WordPress with a customizable kanban board and shortcode.

== Description ==

Agentic Editorial Planner introduces an internal workflow toolkit tailored for content teams:

* Custom post type **Editorial Tasks** with rich task data.
* Configurable **Statuses** (columns) with color-coded kanban display.
* Optional **Priorities** taxonomy to categorize work.
* Task metadata for due date, owner, and external brief link.
* REST-powered admin board for quick inline updates.
* Front-end shortcode `[aep_task_board]` to share progress on public pages.

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/`.
2. Activate **Agentic Editorial Planner** through the *Plugins* screen.
3. Visit *Editorial Planner → Statuses* to configure workflow stages.
4. Add tasks under *Editorial Planner → Editorial Tasks*.
5. View the kanban board under *Editorial Planner → Task Board* or embed `[aep_task_board]` in posts or pages.

== Frequently Asked Questions ==

= How do I set column colors? =
Edit a Status term and pick a color from the color picker field. The admin kanban board displays this color badge.

= Can authors update tasks from the board? =
Anyone capable of editing posts (Authors, Editors, Administrators) can update tasks inline.

== Changelog ==

= 1.0.0 =
* Initial release with task post type, status & priority taxonomies, admin board, shortcode, and REST endpoints.

