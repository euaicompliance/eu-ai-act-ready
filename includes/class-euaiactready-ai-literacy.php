<?php
/**
 * EU AI Act Ready - AI Literacy (Article 4)
 *
 * Stores a checklist of AI literacy obligations that Article 4 of the EU AI Act
 * requires deployers to address regarding staff awareness and training.
 *
 * @package EUAIACTREADY
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages the AI literacy checklist and its stored completion state.
 */
class Euaiactready_AI_Literacy {

	const OPTION_PREFIX = 'euaiactready_literacy_';

	/**
	 * Return default checklist task definitions with translated labels.
	 *
	 * Defined as a method (not a static property) so __() is called with
	 * string literals — required by WordPress.WP.I18n.NonSingularStringLiteralText.
	 *
	 * @return array
	 */
	private static function get_default_tasks() {
		return array(
			array(
				'id'    => 'staff_informed',
				/* translators: AI literacy checklist item */
				'label' => __( 'Staff and contributors are aware of which AI tools are used on this site', 'eu-ai-act-ready' ),
			),
			array(
				'id'    => 'policy_documented',
				/* translators: AI literacy checklist item */
				'label' => __( 'An internal AI usage policy has been documented', 'eu-ai-act-ready' ),
			),
			array(
				'id'    => 'training_completed',
				/* translators: AI literacy checklist item */
				'label' => __( 'Team members who use AI tools have completed relevant AI literacy training', 'eu-ai-act-ready' ),
			),
			array(
				'id'    => 'review_scheduled',
				/* translators: AI literacy checklist item */
				'label' => __( 'A schedule is in place to review and update AI tool usage and policies', 'eu-ai-act-ready' ),
			),
			array(
				'id'    => 'visitors_informed',
				/* translators: AI literacy checklist item */
				'label' => __( 'Site visitors are informed about AI-generated content and AI systems used', 'eu-ai-act-ready' ),
			),
		);
	}

	/**
	 * Return the task list, each item augmented with its current checked state.
	 *
	 * @return array
	 */
	public static function get_tasks() {
		$tasks = array();
		foreach ( self::get_default_tasks() as $task ) {
			$tasks[] = array(
				'id'      => $task['id'],
				'label'   => $task['label'],
				'checked' => (bool) get_option( self::OPTION_PREFIX . $task['id'], false ),
			);
		}
		return $tasks;
	}

	/**
	 * Persist the checked state for all tasks from a $_POST submission.
	 *
	 * @param array $submitted Associative array of task_id => checked (truthy/falsy).
	 * @return void
	 */
	public static function save( array $submitted ) {
		foreach ( self::get_default_tasks() as $task ) {
			$checked = ! empty( $submitted[ $task['id'] ] );
			update_option( self::OPTION_PREFIX . $task['id'], $checked ? 1 : 0, false );
		}

		/**
		 * Fired after the AI literacy checklist is saved.
		 *
		 * @param array $submitted Submitted values.
		 */
		do_action( 'euaiactready_literacy_saved', $submitted );
	}

	/**
	 * Return true if every task in the checklist is checked.
	 *
	 * @return bool
	 */
	public static function is_complete() {
		foreach ( self::get_default_tasks() as $task ) {
			if ( ! get_option( self::OPTION_PREFIX . $task['id'], false ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Return the number of completed tasks.
	 *
	 * @return int
	 */
	public static function get_completed_count() {
		$count = 0;
		foreach ( self::get_default_tasks() as $task ) {
			if ( get_option( self::OPTION_PREFIX . $task['id'], false ) ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Return the total number of tasks.
	 *
	 * @return int
	 */
	public static function get_total_count() {
		return count( self::get_default_tasks() );
	}
}
