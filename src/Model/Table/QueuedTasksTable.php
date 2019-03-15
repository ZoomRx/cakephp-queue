<?php

namespace App\Model\Table;

use App\Model\Entity\QueuedTask;
use Cake\Core\Configure;
use Cake\Database\Expression\QueryExpression;
use Cake\Filesystem\Folder;
use Cake\Log\Log;
use Queue\Model\Table\QueuedTasksTable as BaseQueuedTasksTable;

class QueuedTasksTable extends BaseQueuedTasksTable
{
    /**
     * Get running background workers count
     *
     * @param string $groupType - task group type (low/high)
     * @return int count
     */
    public function existingRunWorkersCount($groupType)
    {
        $pidFilePath = Configure::read('Queue.pidfilepath');
        $groups = QueuedTask::GROUP_MAP;
        $thisGroupWorkerCount = 0;
        if (!empty($pidFilePath)) {
            $dir = new Folder($pidFilePath);
            $files = $dir->find('queue_' . $groupType . '_.*\.pid');
            $pids = [];
            foreach ($files as $file) {
                $splits = explode('_', $file);
                $pids[] = explode('.', $splits[2])[0];
                $thisGroupWorkerCount++;
            }

            if ($thisGroupWorkerCount >= MAX_QUEUE_RUN_WORKERS[$groupType]) {
                // Ensure all pid are currently running
                $runningProcess = 0;
                foreach ($pids as $pid) {
                    $output = [];
                    exec("ps -p $pid", $output);
                    if (count($output) > 1) {
                        // Process is running
                        $runningProcess++;
                    } else {
                        $file = $pidFilePath . 'queue_' . $groupType . '_' . $pid . '.pid';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                    }
                }
                return $runningProcess;
            } else {
                return $thisGroupWorkerCount;
            }
        }
    }

    /**
     * Spawn a background queue plugin run worker
     *
     * @param string $groupType - task group type (low/high)
     * @return void
     */
    public function spawnBackgroundThread($groupType)
    {
        $cmd = 'nohup ' . ROOT . DS . 'bin' . DS . 'cake Queue.Queue runworker -g ' . $groupType . ' > /dev/null &';
        //$cmd = 'cd ' . ROOT . ' && bin' . DS . 'cake Queue.Queue runworker -g ' . $groupTypeToTextMap[$group] . ' 2>&1';
        exec($cmd);
    }

    /**
     * Check worker limit and initiate runworker
     * @param int $group - priority
     * @return bool, true -> if starts the worker otherwise false
     */
    public function runWorker($group = QueuedTask::LOW_PRIORITY)
    {
        $groupTypeToTextMap = array_flip(QueuedTask::GROUP_MAP);
        if (!isset($groupTypeToTextMap[$group])) {
            $group = QueuedTask::LOW_PRIORITY;
        }

        $groupType = $groupTypeToTextMap[$group];
        $runningProcess = $this->existingRunWorkersCount($groupType);

        if ($runningProcess >= MAX_QUEUE_RUN_WORKERS[$groupType]) {
            Log::debug('Max runworker count reached');
            return false;
        }

        $this->spawnBackgroundThread($groupType);
        
        return true;
    }

    /**
     * Cancel a batch from the Queue list
     * @param int $referenceId - reference Id given when we crate a job
     * @param string $jobName|null  optional QueueTask name
     * @return Query result
     */
    public function cancelTask($referenceId, $jobName = null)
    {
        $query = $this->query()
           ->update()
            ->set(['status' => QueuedTask::CANCELED])
            ->where([
                'reference' => $referenceId,
                'completed IS NULL'
            ]);
        if ($jobName !== null) {
            $query->andWhere(['jobtype' => $jobName]);
        }
        
        return $query->execute();
    }

    /**
     * Cancel a batch from the Queue list
     * @param int $referenceId - reference Id given when we crate a job
     * @param string $jobName  QueueTask name
     * @return Query result
     */
    public function deleteTask($referenceId, $jobName = null)
    {
        return $this->deleteAll([
                'reference' => $referenceId,
                'jobtype' => $jobName,
                'fetched IS NOT NULL',
                'completed IS NOT NULL'
            ]);
    }

    /**
     * Return  statistics about jobs still in the Database.
     *
     * @return array|Query
     */
    public function getQueuedTasksStatus()
    {
        $query = $this->find();

        $tasks = $query
            ->select([
                'reference',
                'jobtype',
                'task_group',
                'canceled' => $query->func()
                    ->sum("IF(" . QueuedTask::STATUS_READBLE_FORMAT_CONDITION['canceled'] . " , 1, 0)"),
                'created_date' => $query->func()->min('created'),
                'total_jobs' => $query->func()->count('*'),
                'not_started' => $query->func()
                    ->sum("IF(" . QueuedTask::STATUS_READBLE_FORMAT_CONDITION['not_started'] . " , 1, 0)"),
                'in_progress' => $query->func()
                    ->sum("IF(" . QueuedTask::STATUS_READBLE_FORMAT_CONDITION['in_progress'] . " , 1, 0)"),
                'failed' => $query->func()
                    ->sum("IF(" . QueuedTask::STATUS_READBLE_FORMAT_CONDITION['failed'] . " , 1, 0)"),
                'total_completes' => $query->func()
                    ->sum("IF(" . QueuedTask::STATUS_READBLE_FORMAT_CONDITION['completed'] . " , 1, 0)"),
            ])
            ->group(['reference', 'jobtype']);
        return $tasks;
    }

    /**
     * Returns the number of uncompleted jobs per task_group in the Queue.
     *
     * @return Query object
     */
    public function getPendingJobsCount()
    {
        $query = $this->find('list', [
            'keyField' => 'group_type',
            'valueField' => 'Job_count'
        ]);
        $query
            ->select([
                'group_type' => 'task_group',
                'Job_count' => $query->func()->count('*')
            ])
            ->where([
                'completed IS NULL'
            ])
            ->group(['task_group']);
        return $query;
    }

    /**
     * Update (success/failure) messsage
     *
     * @param int $id ID of the job
     * @param array $message any array
     * (should contain the following format ['success' => [], 'error' => [], 'warning' => []])
     * @return query object
     */
    public function setMessage($id, $message)
    {
        $jobEntity = $this->get($id);

        $jobEntity->message = json_encode($message);
        return $this->save($jobEntity);
    }

    /**
     * Return all jobs
     *
     * @return array|Query
     */
    public function getTasks()
    {
        $query = $this->find();
        $statusText = $query->newExpr()->addCase(
            [
                new QueryExpression(QueuedTask::STATUS_READBLE_FORMAT_CONDITION['not_ready']),
                new QueryExpression(QueuedTask::STATUS_READBLE_FORMAT_CONDITION['not_started']),
                new QueryExpression(QueuedTask::STATUS_READBLE_FORMAT_CONDITION['in_progress']),
                new QueryExpression(QueuedTask::STATUS_READBLE_FORMAT_CONDITION['failed']),
                new QueryExpression(QueuedTask::STATUS_READBLE_FORMAT_CONDITION['completed']),
                new QueryExpression(QueuedTask::STATUS_READBLE_FORMAT_CONDITION['canceled'])
            ],
            array_values(QueuedTask::STATUS_READBLE_FORMAT)
        );
        $tasks = $query
            ->select([
                'id',
                'reference',
                'jobtype',
                'task_group',
                'created',
                'fetched',
                'completed',
                'status_text' => $statusText,
                'message'
            ]);
        return $tasks;
    }
}
