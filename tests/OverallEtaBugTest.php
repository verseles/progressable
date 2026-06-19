<?php

namespace Verseles\Progressable\Tests;

use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Verseles\Progressable\Progressable;

class DummyProgressableOverallEtaBug {
    use Progressable;
}

class OverallEtaBugTest extends TestCase {
    public function test_overall_eta_calculation_with_long_elapsed_time() {
        Carbon::setTestNow(Carbon::now());

        $uniqueName = 'test_eta_bug_long_' . uniqid();

        $a = new DummyProgressableOverallEtaBug;
        $a->setPrecision(0);
        $a->setOverallUniqueName($uniqueName);
        $a->setLocalKey('a');
        $a->setLocalProgress(100);

        Carbon::setTestNow(Carbon::now()->addSeconds(1000));

        $b = new DummyProgressableOverallEtaBug;
        $b->setPrecision(0);
        $b->setOverallUniqueName($uniqueName);
        $b->setLocalKey('b');
        // We want average progress to round to 100 with PHP_ROUND_HALF_ODD.
        // 99.6 rounds to 100.
        // a=100. Let b=99.2. Average = 99.6. getOverallProgress(0) = 100.
        // Unrounded average = 99.6.
        // Rate = 99.6 / 1000 = 0.0996.
        // Remaining = 100 - 99.6 = 0.4.
        // ETA = 0.4 / 0.0996 = 4.016 -> rounds to 4.
        $b->setLocalProgress(99.2);

        $this->assertEquals(100, $a->getOverallProgress());
        $this->assertFalse($a->isOverallComplete());

        $eta = $a->getOverallEstimatedTimeRemaining();
        $this->assertEquals(4, $eta, 'ETA should calculate correctly from unrounded progress, yielding 4 seconds.');
    }
}
