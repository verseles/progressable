<?php

namespace Verseles\Progressable\Tests;

use LogicException;
use Orchestra\Testbench\TestCase;
use Verseles\Progressable\Progressable;

class ProgressableStepTest extends TestCase {
    use Progressable;

    private string $testId;

    protected function setUp(): void {
        parent::setUp();
        $this->testId = uniqid('step_test_', true);
        $this->progress = 0;
        $this->currentStep = 0;
        $this->totalSteps = null;
        unset($this->overallUniqueName);
    }

    public function test_set_and_get_total_steps(): void {
        $this->setTotalSteps(100);
        $this->assertEquals(100, $this->getTotalSteps());
    }

    public function test_set_step_updates_progress(): void {
        $this->setOverallUniqueName('test_step_progress_'.$this->testId);
        $this->setTotalSteps(10);

        $this->setStep(5);
        $this->assertEquals(5, $this->getCurrentStep());
        $this->assertEquals(50, $this->getLocalProgress());

        $this->setStep(10);
        $this->assertEquals(10, $this->getCurrentStep());
        $this->assertEquals(100, $this->getLocalProgress());
    }

    public function test_increment_step(): void {
        $this->setOverallUniqueName('test_increment_step_'.$this->testId);
        $this->setTotalSteps(10);

        $this->incrementStep();
        $this->assertEquals(1, $this->getCurrentStep());
        $this->assertEquals(10, $this->getLocalProgress());

        $this->incrementStep(2);
        $this->assertEquals(3, $this->getCurrentStep());
        $this->assertEquals(30, $this->getLocalProgress());
    }

    public function test_increment_step_without_total_steps_throws_exception(): void {
        $this->expectException(LogicException::class);
        $this->incrementStep();
    }

    public function test_set_step_without_total_steps_throws_exception(): void {
        $this->expectException(LogicException::class);
        $this->setStep(5);
    }

    public function test_reset_local_progress_resets_step(): void {
        $this->setOverallUniqueName('test_reset_step_'.$this->testId);
        $this->setTotalSteps(10);
        $this->setStep(5);

        $this->resetLocalProgress();
        $this->assertEquals(0, $this->getCurrentStep());
        $this->assertEquals(0, $this->getLocalProgress());
    }

    public function test_step_logic_with_zero_total_steps(): void {
        $this->setOverallUniqueName('test_zero_steps_'.$this->testId);
        $this->setTotalSteps(0);
        // If total steps is 0, setting any step should result in 100% progress if we consider it done,
        // or maybe throw exception?
        // Let's say if totalSteps is 0, progress is 100 immediately?
        // Or maybe progress is undefined?
        // If I have 0 steps to do, I'm done.

        $this->setStep(0);
        $this->assertEquals(100, $this->getLocalProgress());
    }

    public function test_set_step_bounds(): void {
        $this->setOverallUniqueName('test_step_bounds_'.$this->testId);
        $this->setTotalSteps(10);

        // If I set step 11 out of 10, progress should be 100 (capped by setLocalProgress)
        $this->setStep(11);
        $this->assertEquals(11, $this->getCurrentStep());
        $this->assertEquals(100, $this->getLocalProgress());

        // If I set step -1, progress should be 0
        $this->setStep(-1);
        $this->assertEquals(-1, $this->getCurrentStep());
        $this->assertEquals(0, $this->getLocalProgress());
    }
}
