<?php

namespace Verseles\Progressable\Tests;

use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Verseles\Progressable\Progressable;

class EtaTest extends TestCase {
    use Progressable;

    private string $testId;

    protected function setUp(): void {
        parent::setUp();
        $this->testId = uniqid('test_', true);

        // Reset properties
        $this->progress = 0;
        // $this->startTime = null; // Will be available after trait update
        unset($this->overallUniqueName);
    }

    public function test_eta_is_null_initially(): void {
        $this->setOverallUniqueName('test_eta_init_'.$this->testId);
        // Method doesn't exist yet, so this test will fail until implemented
        if (method_exists($this, 'getEstimatedTimeRemaining')) {
            $this->assertNull($this->getEstimatedTimeRemaining());
        } else {
            $this->markTestSkipped('getEstimatedTimeRemaining not implemented yet');
        }
    }

    public function test_eta_calculation(): void {
        if (! method_exists($this, 'getEstimatedTimeRemaining')) {
            $this->markTestSkipped('getEstimatedTimeRemaining not implemented yet');
        }

        Carbon::setTestNow(Carbon::now());

        $this->setOverallUniqueName('test_eta_calc_'.$this->testId);

        // Start progress
        $this->setLocalProgress(0);

        // Advance time by 10 seconds
        Carbon::setTestNow(Carbon::now()->addSeconds(10));

        // Set progress to 10%
        // Rate = 10% / 10s = 1% per second
        // Remaining = 90%
        // ETA = 90s
        $this->setLocalProgress(10);

        $this->assertEquals(90, $this->getEstimatedTimeRemaining());

        // Advance time by another 10 seconds (total 20s)
        Carbon::setTestNow(Carbon::now()->addSeconds(10));

        // Set progress to 50%
        // Rate = 50% / 20s = 2.5% per second
        // Remaining = 50%
        // ETA = 50 / 2.5 = 20s
        $this->setLocalProgress(50);

        $this->assertEquals(20, $this->getEstimatedTimeRemaining());
    }

    public function test_eta_is_zero_when_complete(): void {
        if (! method_exists($this, 'getEstimatedTimeRemaining')) {
            $this->markTestSkipped('getEstimatedTimeRemaining not implemented yet');
        }

        $this->setOverallUniqueName('test_eta_complete_'.$this->testId);
        $this->setLocalProgress(100);
        $this->assertEquals(0, $this->getEstimatedTimeRemaining());
    }

    public function test_reset_clears_start_time(): void {
        if (! method_exists($this, 'getEstimatedTimeRemaining')) {
            $this->markTestSkipped('getEstimatedTimeRemaining not implemented yet');
        }

        Carbon::setTestNow(Carbon::now());

        $this->setOverallUniqueName('test_eta_reset_'.$this->testId);
        $this->setLocalProgress(10);

        // Ensure start time was set (indirectly via ETA calculation)
        Carbon::setTestNow(Carbon::now()->addSeconds(10));
        $this->assertNotNull($this->getEstimatedTimeRemaining());

        $this->resetLocalProgress();

        // After reset, ETA should be null (because progress is 0 and start time should be cleared)
        $this->assertNull($this->getEstimatedTimeRemaining());

        // Start again. Current time is T+10s.
        // We set progress to 0 (reset).
        // Advance 10s to T+20s.
        Carbon::setTestNow(Carbon::now()->addSeconds(10));

        // Set progress to 20%.
        // Elapsed since NEW start (T+10s) is 10s.
        // Rate = 20% / 10s = 2% per second.
        // Remaining 80%. ETA = 40s.

        $this->setLocalProgress(20);
        $this->assertEquals(40, $this->getEstimatedTimeRemaining());
    }

    public function test_start_time_persistence(): void {
        if (! method_exists($this, 'getEstimatedTimeRemaining')) {
            $this->markTestSkipped('getEstimatedTimeRemaining not implemented yet');
        }

        Carbon::setTestNow(Carbon::now());
        $uniqueName = 'test_eta_persistence_'.$this->testId;

        $this->setOverallUniqueName($uniqueName);
        $this->setLocalProgress(10); // Start time set at T0

        // Create new instance simulating another process or request
        $obj2 = new class {
            use Progressable;
        };
        $obj2->setOverallUniqueName($uniqueName);

        // Advance time
        Carbon::setTestNow(Carbon::now()->addSeconds(10));

        // Obj2 updates progress to 20%
        // It should pick up start time from T0
        // Elapsed = 10s. Progress = 20%. Rate = 2% / s.
        // Remaining = 80%. ETA = 40s.
        $obj2->setLocalProgress(20);

        $this->assertEquals(40, $obj2->getEstimatedTimeRemaining());
    }
}
