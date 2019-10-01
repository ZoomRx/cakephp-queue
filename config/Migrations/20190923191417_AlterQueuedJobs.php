<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

class AlterQueuedJobs extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        //Since changing task_group and reference field type from int to string, migrate all values appropriately
        $queuedJobTable = TableRegistry::get('QueuedJobs');

        //cleanup all obsolete jobs
        $queuedJobTable->deleteAll(['failed' > 0]);

        //update reference for existing undone jobs
        $queuedJobTable->updateAll(
            ['reference' => null],
            ['completed IS' => null]
        );
    }
}
