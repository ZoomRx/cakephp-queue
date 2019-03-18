<?php

namespace Queue\Model\Entity;

use Cake\ORM\Entity;

class QueuedTask extends Entity {

    //Constants for field "status" 
    const CANCELED = 2;

    //Constants for field "task_group" 
    const LOW_PRIORITY = 0;
    const HIGH_PRIORITY = 1;
    const TRANSACTIONS = 2;
    const TRANSCRIPTIONS = 3;

    const GROUP_MAP = [
        'low' => self::LOW_PRIORITY,
        'high' => self::HIGH_PRIORITY,
        'transactions' => self::TRANSACTIONS,
        'transcriptions' => self::TRANSCRIPTIONS
    ];

    /*
    protected function _getStatus() {
        // $this->virtualFields['status'] = '(CASE WHEN ' . 'notbefore > NOW() THEN \'NOT_READY\' WHEN ' . 'fetched IS null THEN \'NOT_STARTED\' WHEN ' . 'fetched IS NOT null AND ' . 'completed IS null AND ' . 'failed = 0 THEN \'IN_PROGRESS\' WHEN ' . 'fetched IS NOT null AND ' . 'completed IS null AND ' . 'failed > 0 THEN \'FAILED\' WHEN ' . 'fetched IS NOT null AND ' . 'completed IS NOT null THEN \'COMPLETED\' ELSE \'UNKNOWN\' END)';
    }
    */

}
