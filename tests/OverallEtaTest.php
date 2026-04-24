<?php

namespace Verseles\Progressable\Tests;

use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Verseles\Progressable\Progressable;

class OverallEtaTest extends TestCase {
    use Progressable;

    private string $testId;

    protected function setUp(): void {
        parent::setUp();
        $this->testId = uniqid('test_', true);

        // Reset properties
        $this->progress = 0;
        unset($this->overallUniqueName);
    }

    public function test_overall_eta_is_null_initially(): void {
        $this->setOverallUniqueName('test_overall_eta_init_'.$this->testId);
        $this->assertNull($this->getOverallEstimatedTimeRemaining());
    }

    public function test_overall_eta_calculation(): void {
        Carbon::setTestNow(Carbon::now());

        $uniqueName = 'test_overall_eta_calc_'.$this->testId;
        $this->setOverallUniqueName($uniqueName);

        // First process starts at T0
        $this->setLocalKey('process_1');
        $this->setLocalProgress(0);

        // Second process starts at T+5s
        Carbon::setTestNow(Carbon::now()->addSeconds(5));

        $obj2 = new class {
            use Progressable;
        };
        $obj2->setOverallUniqueName($uniqueName);
        $obj2->setLocalKey('process_2');
        $obj2->setLocalProgress(0);

        // Advance to T+10s
        Carbon::setTestNow(Carbon::now()->addSeconds(5));

        // Set process 1 to 20% and process 2 to 0% -> overall = 10%
        // Min start time is T0, so elapsed is 10s.
        // Rate = 10% / 10s = 1% per second.
        // Remaining = 90%. ETA = 90s.
        $this->setLocalProgress(20);

        $this->assertEquals(90, $this->getOverallEstimatedTimeRemaining());
        $this->assertEquals(90, $obj2->getOverallEstimatedTimeRemaining());
    }

    public function test_overall_eta_is_zero_when_complete(): void {
        $uniqueName = 'test_overall_eta_complete_'.$this->testId;

        $this->setOverallUniqueName($uniqueName);
        $this->setLocalKey('process_1');
        $this->setLocalProgress(100);

        $obj2 = new class {
            use Progressable;
        };
        $obj2->setOverallUniqueName($uniqueName);
        $obj2->setLocalKey('process_2');
        $obj2->setLocalProgress(100);

        $this->assertEquals(0, $this->getOverallEstimatedTimeRemaining());
    }
}
