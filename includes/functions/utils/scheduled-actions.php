<?php

/**
 * Enqueue an action to run one time, as soon as possible (rather a specific scheduled time).
 *
 * This method creates a new action with the NULLSchedule. This schedule maps to a MySQL datetime string of
 * 0000-00-00 00:00:00. This is done to create a psuedo "async action" type that is fully backward compatible.
 * Existing queries to claim actions claim by date, meaning actions scheduled for 0000-00-00 00:00:00 will
 * always be claimed prior to actions scheduled for a specific date. This makes sure that any async action is
 * given priority in queue processing. This has the added advantage of making sure async actions can be
 * claimed by both the existing WP Cron and WP CLI runners, as well as a new async request runner.
 *
 * @param string $hook The hook to trigger when this action runs
 * @param array  $args Args to pass when the hook is triggered
 *
 * @return string The ID of the stored action
 */
function pum_enqueue_async_action( $hook = 'none', $args = [] ) {
	return as_enqueue_async_action( $hook, $args, 'popup-maker' );
}

/**
 * Schedule an action to run one time
 *
 * @param int    $timestamp Unix timestamp when the action will run
 * @param string $hook      The hook to trigger
 * @param array  $args      Arguments to pass when the hook triggers
 *
 * @return string The job ID
 */
function pum_schedule_single_action( $timestamp, $hook, $args = [] ) {
	return as_schedule_single_action( $timestamp, $hook, $args, 'popup-maker' );
}

/**
 * Schedule a recurring action
 * Create the first instance of an action recurring on a given interval.
 *
 * @param int    $timestamp           Unix timestamp when the action will run the first time
 * @param int    $interval_in_seconds How long to wait between runs
 * @param string $hook                The hook to trigger
 * @param array  $args                Arguments to pass when the hook triggers
 *
 * @return string The job ID
 */
function pum_schedule_recurring_action( $timestamp, $interval_in_seconds, $hook, $args = [] ) {
	return as_schedule_recurring_action( $timestamp, $interval_in_seconds, $hook, $args, 'popup-maker' );
}


/**
 * Schedule an action that recurs on a cron-like schedule.
 *
 * @param int    $timestamp The first instance of the action will be scheduled
 *                          to run at a time calculated after this timestamp matching the cron
 *                          expression. This can be used to delay the first instance of the action.
 * @param string $schedule  A cron-link schedule string
 * @param string $hook      The hook to trigger
 * @param array  $args      Arguments to pass when the hook triggers
 *
 * @return string The job ID
 * @see http://en.wikipedia.org/wiki/Cron
 *      *    *    *    *    *    *
 *      ┬    ┬    ┬    ┬    ┬    ┬
 *      |    |    |    |    |    |
 *      |    |    |    |    |    + year [optional]
 *      |    |    |    |    +----- day of week (0 - 7) (Sunday=0 or 7)
 *      |    |    |    +---------- month (1 - 12)
 *      |    |    +--------------- day of month (1 - 31)
 *      |    +-------------------- hour (0 - 23)
 *      +------------------------- min (0 - 59)
 */
function pum_schedule_cron_action( $timestamp, $schedule, $hook, $args = [] ) {
	return as_schedule_cron_action( $timestamp, $schedule, $hook, $args, 'popup-maker' );
}

/**
 * Cancel the next occurrence of a scheduled action.
 *
 * While only the next instance of a recurring or cron action is unscheduled by this method, that will also prevent
 * all future instances of that recurring or cron action from being run. Recurring and cron actions are scheduled in
 * a sequence instead of all being scheduled at once. Each successive occurrence of a recurring action is scheduled
 * only after the former action is run. If the next instance is never run, because it's unscheduled by this function,
 * then the following instance will never be scheduled (or exist), which is effectively the same as being unscheduled
 * by this method also.
 *
 * @param string $hook The hook that the job will trigger
 * @param array  $args Args that would have been passed to the job
 *
 * @return string The scheduled action ID if a scheduled action was found, or empty string if no matching action found.
 */
function pum_unschedule_action( $hook, $args = [] ) {
	return as_unschedule_action( $hook, $args, 'popup-maker' );
}

/**
 * Cancel all occurrences of a scheduled action.
 *
 * @param string $hook The hook that the job will trigger
 * @param array  $args Args that would have been passed to the job
 *
 * @return void
 */
function pum_unschedule_all_actions_by_hook( $hook ) {
	as_unschedule_all_actions( $hook, null );
}

/**
 * Cancel all occurrences of a scheduled action.
 *
 * @param string $hook The hook that the job will trigger
 * @param array  $args Args that would have been passed to the job
 *
 * @return void
 */
function pum_unschedule_all_group_actions() {
	as_unschedule_all_actions( null, null, 'popup-maker' );
}

/**
 * Cancel all occurrences of a scheduled action.
 *
 * @param string $hook The hook that the job will trigger
 * @param array  $args Args that would have been passed to the job
 *
 * @return void
 */
function pum_unschedule_all_actions( $hook, $args = [], $group_only = false ) {
	as_unschedule_all_actions( $hook, $args, $group_only ? 'popup-maker' : '' );
}

/**
 * Check if there is an existing action in the queue with a given hook, args and group combination.
 *
 * An action in the queue could be pending, in-progress or aysnc. If the is pending for a time in
 * future, its scheduled date will be returned as a timestamp. If it is currently being run, or an
 * async action sitting in the queue waiting to be processed, in which case boolean true will be
 * returned. Or there may be no async, in-progress or pending action for this hook, in which case,
 * boolean false will be the return value.
 *
 * @param string $hook
 * @param array  $args
 *
 * @return int|bool The timestamp for the next occurrence of a pending scheduled action, true for an async or in-progress action or false if there is no matching action.
 */
function pum_next_scheduled_action( $hook, $args = [] ) {
	return as_next_scheduled_action( $hook, $args, 'popup-maker' );
}

/**
 * Find scheduled actions
 *
 * @param array  $args          Possible arguments, with their default values:
 *                              'hook' => '' - the name of the action that will be triggered
 *                              'args' => NULL - the args array that will be passed with the action
 *                              'date' => NULL - the scheduled date of the action. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime(). Used in UTC timezone.
 *                              'date_compare' => '<=' - operator for testing "date". accepted values are '!=', '>', '>=', '<', '<=', '='
 *                              'modified' => NULL - the date the action was last updated. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime(). Used in UTC timezone.
 *                              'modified_compare' => '<=' - operator for testing "modified". accepted values are '!=', '>', '>=', '<', '<=', '='
 *                              'group' => '' - the group the action belongs to
 *                              'status' => '' - ActionScheduler_Store::STATUS_COMPLETE or ActionScheduler_Store::STATUS_PENDING
 *                              'claimed' => NULL - TRUE to find claimed actions, FALSE to find unclaimed actions, a string to find a specific claim ID
 *                              'per_page' => 5 - Number of results to return
 *                              'offset' => 0
 *                              'orderby' => 'date' - accepted values are 'hook', 'group', 'modified', or 'date'
 *                              'order' => 'ASC'
 *
 * @param string $return_format OBJECT, ARRAY_A, or ids
 *
 * @return array
 */
function pum_get_scheduled_actions( $args = [], $return_format = OBJECT ) {
	return as_get_scheduled_actions( $args, $return_format );
}