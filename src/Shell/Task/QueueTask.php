<?php
/**
 * @author Andy Carter
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Queue\Shell\Task;

use Cake\Console\Shell;

/**
 * Queue Task.
 *
 * Common Queue plugin tasks properties and methods to be extended by custom
 * tasks.
 */
class QueueTask extends Shell {

	/**
	 * Adding the QueueTask Model
	 *
	 * @var string
	 */
	public $modelClass = 'Queue.QueuedTasks';

	/**
	 * Timeout for run, after which the Task is reassigned to a new worker.
	 *
	 * @var int
	 */
	public $timeout = 172800; //@Aravind - hack, to avoid task refetching in adjacent runworker if timeout occurs. Since we are cleaning up with same time, there is no possibility to refetch same task for timeout.

	/**
	 * Number of times a failed instance of this task should be restarted before giving up.
	 *
	 * @var int
	 */
	public $retries = 0;

	/**
	 * @var bool
	 */
	public $autoUnserialize = true;

	/**
	 * Add functionality.
	 *
	 * @return void
	 */
	public function add() {
	}

	/**
	 * Run function.
	 * This function is executed, when a worker is executing a task.
	 * The return parameter will determine, if the task will be marked completed, or be requeued.
	 *
	 * @param array $data The array passed to QueuedTask->createJob()
	 * @param int|null $id The id of the QueuedTask
	 * @return bool Success
	 */
	public function run($data, $id = null) {
		return true;
	}

	/**
	 * Cancel function.
	 * This function is executed, when a worker is executing a task and the task has canceled by admin.
	 *
	 * @param int|null $referenceId The id of the batch\
	 * @param array $data The array QueuedTasks row
	 * @return void
	 */
	public function canceled($referenceId, $data) {
	}

}
