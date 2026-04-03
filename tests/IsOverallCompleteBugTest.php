<?php
namespace Verseles\Progressable\Tests;

use Verseles\Progressable\Progressable;
use Orchestra\Testbench\TestCase;

class DummyProgressableOverallComplete
{
    use Progressable;
}

class IsOverallCompleteBugTest extends TestCase
{
    public function testIsOverallCompleteIsAccurateAndDoesNotReturnTruePrematurely()
    {
        $a = new DummyProgressableOverallComplete();
        $a->setOverallUniqueName('test-overall-complete-bug');
        $a->setLocalKey('a');
        $a->setLocalProgress(100);

        $b = new DummyProgressableOverallComplete();
        $b->setOverallUniqueName('test-overall-complete-bug');
        $b->setLocalKey('b');
        $b->setLocalProgress(99.4);

        // Before the fix, this evaluates to true because getOverallProgress(0) rounds 99.7 to 100.
        $this->assertFalse($a->isOverallComplete(), 'isOverallComplete should return false when not all processes are 100% complete.');

        $b->setLocalProgress(100);

        $this->assertTrue($a->isOverallComplete(), 'isOverallComplete should return true when all processes are 100% complete.');
    }
}
