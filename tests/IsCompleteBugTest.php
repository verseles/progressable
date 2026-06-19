<?php

namespace Verseles\Progressable\Tests;

use Orchestra\Testbench\TestCase;
use Verseles\Progressable\Progressable;

class DummyProgressableCompleteBug {
    use Progressable;
}

class IsCompleteBugTest extends TestCase {
    public function test_is_complete_does_not_return_true_prematurely() {
        $a = new DummyProgressableCompleteBug;
        $a->setPrecision(0);
        $a->setOverallUniqueName('test_complete_bug');
        $a->setLocalKey('a');
        $a->setLocalProgress(99.6); // getLocalProgress() will return 100

        $this->assertEquals(100, $a->getLocalProgress());
        $this->assertFalse($a->isComplete(), 'isComplete should return false when progress < 100, even if rounded to 100.');

        $a->setLocalProgress(100);
        $this->assertTrue($a->isComplete(), 'isComplete should return true when progress is 100.');
    }
}
