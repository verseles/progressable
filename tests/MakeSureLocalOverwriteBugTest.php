<?php
namespace Verseles\Progressable\Tests;

use Orchestra\Testbench\TestCase;
use Verseles\Progressable\Progressable;

class MakeSureLocalOverwriteBugTest extends TestCase {
    public function test_make_sure_local_does_not_overwrite_existing_progress() {
        $task1 = new class { use Progressable; };
        $task1->setOverallUniqueName('my-group');
        $task1->setLocalKey('task-1');
        $task1->setLocalProgress(50);

        // Assert state correctly saved
        $data = $task1->getOverallProgressData();
        $this->assertEquals(50, $data['task-1']['progress']);

        // Now imagine this is another PHP process/request but handling the same logical task
        $task2 = new class { use Progressable; };
        $task2->setOverallUniqueName('my-group');
        $task2->setLocalKey('task-1');

        $data = $task2->getOverallProgressData();

        // Progress should still be 50, not reset to 0!
        $this->assertEquals(50, $data['task-1']['progress']);
    }
}
