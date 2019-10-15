<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

class AlterQueuedJobsToCleanObsoleteJobs extends AbstractMigration
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
        $table = $this->table('queued_jobs');

        $table->changeColumn('job_group', 'string', [
            'length' => 255,
            'null' => true,
            'default' => null
        ]);

        $table->changeColumn('reference', 'string', [
            'length' => 255,
            'null' => true,
            'default' => null
        ]);

        //Since changing task_group and reference field type from int to string, migrate all values appropriately
        $queuedJobTable = TableRegistry::get('QueuedJobs');

        //cleanup all obsolete jobs
        $queuedJobTable->deleteAll(['failed >' => 0]);

        //update reference for existing undone jobs
        $queuedJobTable->updateAll(
            ['reference' => null],
            ['completed IS' => null]
        );
    }
}
