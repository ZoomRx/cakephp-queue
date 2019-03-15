<?php
namespace App\Model\Entity;

use Queue\Model\Entity\QueuedTask as BaseQueuedTasksEntity;

/**
 * QueuedTask Entity.
 *
 * @property int $id
 * @property string $jobtype
 * @property string $data
 * @property int $task_group
 * @property int $reference
 * @property \Cake\I18n\Time $created
 * @property \Cake\I18n\Time $notbefore
 * @property \Cake\I18n\Time $fetched
 * @property \Cake\I18n\Time $completed
 * @property float $progress
 * @property int $failed
 * @property string $message
 * @property string $workerkey
 * @property int $status
 */
class QueuedTask extends BaseQueuedTasksEntity
{

    const STATUS_READBLE_FORMAT_CONDITION = [
        'not_ready' => 'notbefore > NOW()',
        'not_started' => 'fetched IS NULL AND status IS NULL',
        'in_progress' => 'fetched IS NOT NULL AND completed IS NULL AND failed = 0',
        'failed' => 'fetched IS NOT NULL AND completed IS NULL AND failed > 0 AND status IS NULL',
        'completed' => 'fetched IS NOT NULL AND completed IS NOT NULL AND status IS NULL',
        'canceled' => 'status = ' . self::CANCELED
    ];

    const STATUS_READBLE_FORMAT = [
        'not_ready' => 'NOT READY',
        'not_started' => 'NOT STARTED',
        'in_progress' => 'IN PROGRESS',
        'failed' => 'FAILED',
        'completed' => 'COMPLETED',
        'canceled' => 'CANCELED'
    ];

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'id' => false,
    ];    
}
